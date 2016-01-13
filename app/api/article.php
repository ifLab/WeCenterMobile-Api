<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class article extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		//$rule_action['actions'][] = 'square';
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'save_comment';

		return $rule_action;
	}

	public function setup()
	{
		//HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}



	//删除文章
	public function remove_article_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除文章的权限')));
		}

		if ($article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			if ($this->user_id != $article_info['uid'])
			{
				$this->model('account')->send_delete_message($article_info['uid'], $article_info['title'], $article_info['message']);
			}

			$this->model('article')->remove_article($article_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	//文章和文章评论点赞
	public function article_vote_action()
	{
		switch ($_POST['type'])
		{
			case 'article':
				$item_info = $this->model('article')->get_article_info_by_id($_POST['item_id']);
			break;

			case 'comment':
				$item_info = $this->model('article')->get_comment_by_id($_POST['item_id']);
			break;

		}

		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的内容进行投票')));
		}

		$reputation_factor = $this->model('account')->get_user_group_by_id($this->user_info['reputation_group'], 'reputation_factor');

		$this->model('article')->article_vote($_POST['type'], $_POST['item_id'], $_POST['rating'], $this->user_id, $reputation_factor, $item_info['uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	//获取用户收藏的文章列表
	public function get_favorite_article_action()
	{
		if( empty( $_GET['page'] ) ) $_GET['page'] = 1;

        if( empty( $_GET['per_page'] ) ) $_GET['per_page'] = 10;

        $uid = $this->user_id;

        if( !empty( $_GET['uid'] ) ) $uid = intval( $_GET['uid'] );

        $data = $this->model('api')->get_favorite_article($uid, $_GET['page'], $_GET['per_page']);

        if( !empty($data['rows']) )
        {
        	foreach ($data['rows'] as $k => $v)
        	{
        		 $data['rows'][$k]['message'] = cjk_substr(strip_ubb( $v['message']),0,80,'utf-8');
         	}
        }

        if( empty( $data['rows'] ) ) 
        {
        	$data['rows'] = null;
        	$data['total_rows'] = 0;
        }

        H::ajax_json_output(AWS_APP::RSM($data, 1, null));
	}

	//article_id  message  at_uid
	public function save_comment_action()
	{

		if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定文章不存在')));
		}

		if ($article_info['lock'] AND !($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能回复')));
		}

		$message = trim($_POST['message'], "\r\n\t");

		if (! $message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		if (strlen($message) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字节', get_setting('answer_length_lower'))));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($message))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		if ($this->publish_approval_valid($message))
		{
			$this->model('publish')->publish_approval('article_comment', array(
				'article_id' => intval($_POST['article_id']),
				'message' => $message,
				'at_uid' => intval($_POST['at_uid'])
			), $this->user_id);

			H::ajax_json_output(AWS_APP::RSM(null, '0', AWS_APP::lang()->_t('发布成功, 请等待管理员审核...')));
		}
		else
		{
			$comment_id = $this->model('publish')->publish_article_comment($_POST['article_id'], $message, $this->user_id, $_POST['at_uid']);

			H::ajax_json_output(AWS_APP::RSM(array(
                    'comment_id' => $comment_id
                ), 1, null));
		}
	}


	public function remove_comment_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除评论的权限')));
		}

		if ($comment_info = $this->model('article')->get_comment_by_id($_POST['comment_id']))
		{
			$this->model('article')->remove_comment($comment_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	public function index_action()
	{

		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('文章不存在或已被删除'), '/');
		}

		if ($article_info['has_attach'])
		{
			$article_info['attachs'] = $this->model('publish')->get_attach('article', $article_info['id'], 'min');

			$article_info['attachs_ids'] = FORMAT::parse_attachs($article_info['message'], true);
		}

		$article_info['user_info'] = $this->model('account')->get_user_info_by_uid($article_info['uid'], true);
		
		$article_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($article_info['message'])));

		if ($this->user_id)
		{
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], null, $this->user_id);
		}

		//赞了该文章的用户信息
		$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], 1, 10);

		//文章话题
		$article_topics = $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article');

		$comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], 100);

		if ($comments AND $this->user_id)
		{
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], 1, $this->user_id);
				$comments[$key]['message'] = $this->model('question')->parse_at_user($val['message']);
			}
	    }

	    $article_info['user_follow_check'] = 0;
	    if ($this->user_id AND $this->model('follow')->user_follow_check($this->user_id, $article_info['uid']))
		{
			$article_info['user_follow_check'] = 1;
		}

        $this->model('article')->update_views($article_info['id']);

        //作者信息
        if( $article_info['user_info'] )
        {
          	$article_info['user_info'] = $this->model('myapi')->get_clean_user_info($article_info['user_info']);
        }

        //点赞者信息
        if( !empty( $article_info['vote_users'] ) )
        {
            foreach ($article_info['vote_users'] as $key => $value)
            {
               $article_info['vote_users'][$key] = $this->model('myapi')->get_clean_user_info($value);
            }
        }

        $topics_key = array( 'topic_id', 'topic_title' );

        if($article_topics) 
		{
			foreach ($article_topics as $kk => $vv)
			{
				foreach ($vv as $k => $v)
				{
					if(!in_array($k, $topics_key)) unset($article_topics[$kk][$k]);
				}
			}
		}

		//评论里评论者信息
		if( !empty( $comments ) )
		{
			foreach ($comments as $key => $value)
			{
				if( !empty( $value['user_info'] ) )
				{
					 $comments[$key]['user_info'] =  $this->model('myapi')->get_clean_user_info($value['user_info']);
				}

				if( !empty( $value['at_user_info'] ) )
				{
					$comments[$key]['at_user_info'] =  $this->model('myapi')->get_clean_user_info($value['at_user_info']);
				}
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
            'article_info' => $article_info,
            'article_topics' => $article_topics,
            'comments' => $comments
        ), 1, null));
	}


    public function article_comments_action()
    {
       
        if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
        {
            H::redirect_msg(AWS_APP::lang()->_t('文章不存在或已被删除'), '/');
        }
      
        $comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], 100);

		if ($comments AND $this->user_id)
		{
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], 1, $this->user_id);
				$comments[$key]['message'] = $this->model('question')->parse_at_user($val['message']);
			}
	    }
       
       //评论里评论者信息
		if( !empty( $comments ) )
		{
			foreach ($comments as $key => $value)
			{
				if( !empty( $value['user_info'] ) )
				{
					 $comments[$key]['user_info'] =  $this->model('myapi')->get_clean_user_info($value['user_info']);
				}

				if( !empty( $value['at_user_info'] ) )
				{
					$comments[$key]['at_user_info'] =  $this->model('myapi')->get_clean_user_info($value['at_user_info']);
				}
			}
		}

        H::ajax_json_output(AWS_APP::RSM(array(
        		'total_rows' => count($comments),
        		'rows' => $comments
        	), 1, null));
    }





	public function index_square_action()
	{

		$this->crumb(AWS_APP::lang()->_t('文章'), '/article/');

		if ($_GET['category'])
		{
			if (is_digits($_GET['category']))
			{
				$category_info = $this->model('system')->get_category_info($_GET['category']);
			}
			else
			{
				$category_info = $this->model('system')->get_category_info_by_url_token($_GET['category']);
			}
		}

		if ($_GET['feature_id'])
		{
			$article_list = $this->model('article')->get_articles_list_by_topic_ids($_GET['page'], get_setting('contents_per_page'), 'add_time DESC', $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']));

			$article_list_total = $this->model('article')->article_list_total;

			if ($feature_info = $this->model('feature')->get_feature_by_id($_GET['feature_id']))
			{
				$this->crumb($feature_info['title'], '/article/feature_id-' . $feature_info['id']);

				TPL::assign('feature_info', $feature_info);
			}
		}
		else
		{
			$article_list = $this->model('article')->get_articles_list($category_info['id'], $_GET['page'], get_setting('contents_per_page'), 'add_time DESC');

			$article_list_total = $this->model('article')->found_rows();
		}

		if ($article_list)
		{
			foreach ($article_list AS $key => $val)
			{
				$article_ids[] = $val['id'];

				$article_uids[$val['uid']] = $val['uid'];
			}

			$article_topics = $this->model('topic')->get_topics_by_item_ids($article_ids, 'article');
			$article_users_info = $this->model('account')->get_user_info_by_uids($article_uids);

			foreach ($article_list AS $key => $val)
			{
				$article_list[$key]['user_info'] = $article_users_info[$val['uid']];
			}
		}

		// 导航
		if (TPL::is_output('block/content_nav_menu.tpl.htm', 'article/square'))
		{
			TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('article'));
		}

		//边栏热门话题
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'article/square'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));
		}

		if ($category_info)
		{
			TPL::assign('category_info', $category_info);

			$this->crumb($category_info['title'], '/article/category-' . $category_info['id']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		TPL::assign('article_list', $article_list);
		TPL::assign('article_topics', $article_topics);

		TPL::assign('hot_articles', $this->model('article')->get_articles_list(null, 1, 10, 'votes DESC'));

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/article/category_id-' . $_GET['category_id'] . '__feature_id-' . $_GET['feature_id']),
			'total_rows' => $article_list_total,
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::output('article/square');
	}
}
