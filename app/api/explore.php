<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class explore extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		if ($this->user_info['permission']['visit_explore'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
		}
		
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

	
	//GET: category(选),per_page(选，默认:10),sort_type([new,hot]选,默认:最新),page(默认1),day(默认30),is_recommend
	public function index_action()
	{
		$per_page = get_setting('contents_per_page');

		if($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}

		
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


		if (! $_GET['sort_type'] AND !$_GET['is_recommend'])
		{
			$_GET['sort_type'] = 'new';
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts(null, $category_info['id'], null, $_GET['day'], $_GET['page'],$per_page);
		}
		else
		{
			$posts_list = $this->model('posts')->get_posts_list(null, $_GET['page'], $per_page, $_GET['sort_type'], null, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		}

		if ($posts_list)
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
				}
			}
		}

		$question_key = array( 'post_type', 'question_id', 'question_content', 'add_time', 'answer_count', 'view_count', 'agree_count', 'against_count', 'answer_users', 'topics', 'user_info' );
		$article_key = array( 'post_type', 'id', 'title', 'message', 'add_time', 'views', 'votes', 'topics', 'user_info' );
		$topics_key = array( 'topic_id', 'topic_title' );
		$user_info_key = array( 'uid', 'user_name' );

		if($posts_list)
		{
			foreach ($posts_list as $key => $val)
			{	
				$posts_list_key = $article_key;

				if($val['post_type'] == 'question')
				{
					$posts_list_key = $question_key;
				}

				foreach ($val as $k => $v)
				{
					if(!in_array($k, $posts_list_key)) unset($posts_list[$key][$k]);
				}

				if($val['user_info']) 
				{
					foreach ($val['user_info'] as $k => $v)
					{
						if(!in_array($k, $user_info_key)) unset($posts_list[$key]['user_info'][$k]);
					}

					$posts_list[$key]['user_info']['avatar_file'] = get_avatar_url($posts_list[$key]['user_info']['uid'],'mid');
				}

				if(is_array($val['topics'])) 
				{
					foreach ($val['topics'] as $kk => $vv)
					{
						foreach ($vv as $k => $v)
						{
							if(!in_array($k, $topics_key)) unset($posts_list[$key]['topics'][$kk][$k]);
						}
					}
				}

				if(is_array($val['answer_users'])) 
				{
					foreach ($val['answer_users'] as $kk => $vv)
					{
						foreach ($vv as $k => $v)
						{
							if(!in_array($k, $user_info_key)) unset($posts_list[$key]['answer_users'][$kk][$k]);
						}

						$posts_list[$key]['answer_users'][$kk]['avatar_file'] = get_avatar_url($posts_list[$key]['answer_users'][$kk]['uid'],'mid');
					}
				}
			}	
		}

		H::ajax_json_output(AWS_APP::RSM(array( 
							 	'total_rows'=>count($posts_list),
							 	'rows'=>$posts_list 
		 				), 1, null));
	}
}
