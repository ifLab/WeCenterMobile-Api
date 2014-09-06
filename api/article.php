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

class article extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		
		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'article';
			$rule_action['actions'][] = 'comment';
		}
		
		return $rule_action;
	}
	
	public function article_action()
	{	
		if (! isset($_GET['id']))
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误请求,缺少文章id')));
		}
		
		
		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在或已被删除')));
		}
		
		if ($article_info['has_attach'])
		{
			$article_info['attachs'] = $this->model('publish')->get_attach('article', $article_info['id'], 'min');
			
			$article_info['attachs_ids'] = FORMAT::parse_attachs($article_info['message'], true);
		}
		
		$article_info['user_info'] = $this->model('account')->get_user_info_by_uid($article_info['uid'], true);
		
		//$article_info['message'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($article_info['message'])));
		
		if ($this->user_id)
		{
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], null, $this->user_id);
		}
		
		$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], 1, 10);
		
		//TPL::assign('article_info', $article_info);  //文章信息
		
		//TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));
		$article_topics = $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article');  //文章所属话题
		
		//发起人擅长话题
		//TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($article_info['user_info']['uid'], $user['reputation'], 5));
				
		
		if ($_GET['item_id'])
		{
			$comments[] = $this->model('article')->get_comment_by_id($_GET['item_id']);
		}
		else
		{
			$comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], 100);
		}
		
		if ($comments AND $this->user_id)
		{
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], $this->user_id);
			}
		}
	
		//更新文章浏览数
		$this->model('article')->update_views($article_info['id']);
		
		//TPL::assign('comments', $comments);  评论
		//TPL::assign('comments_count', $article_info['comments']);  评论数目

		//markdown to html
		$article_info['message'] = nl2br( FORMAT::parse_markdown( $article_info['message'] ) );

		//如果有附件
		if(  $article_info['has_attach'] ){
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $article_info['message'], $matches);

			foreach( $matches[0] as $k => $v ){
				 $my_num = substr($v, 8, -9);
				 $my_replace = "<img src='".$article_info['attachs'][$my_num]['attachment']."'/>";
				 $article_info['message'] = str_replace($v, $my_replace, $article_info['message']);
				 unset( $article_info['attachs'][$my_num] );
			}

			if( !empty( $article_info['attachs'] ) ){
				foreach ($article_info['attachs'] as $key => $value) {
					$article_info['message'] .= "<br><img src='".$value['attachment']."'/>";
				}
			}
		}
		//把\n替换成<br/>
		//$article_info['message'] = str_replace("\n", "<br>", $article_info['message'] );


		
		$article_info_key = array( 'id', 'uid', 'user_info', 'title', 'message', 'votes' );
		if( !empty( $article_info ) ){
			foreach( $article_info as $k => $v ){
				if( !in_array( $k, $article_info_key ) ) unset( $article_info[$k] );
			}
		}

		$article_info['user_name'] = null;
		$article_info['avatar_file'] = null;
		if( !empty( $article_info['user_info'] ) ){
			 $article_info['user_name'] = $article_info['user_info']['user_name'];
			 if(!empty( $article_info['user_info']['avatar_file'] ))  $article_info['avatar_file'] = str_replace( 'min', 'max', $article_info['user_info']['avatar_file'] );
		}
		$article_info['signature'] = $this->model('myapi')->get_signature( $article_info['uid'] );

		unset( $article_info['user_info'] );


		$article_info['vote_value'] = 0;
		if( !empty($this->user_id) ){
			$ret = $this->model('myapi')->get_article_vote_value($this->user_id,$article_info['id']);
			if( !empty( $ret ) )  $article_info['vote_value'] = $ret;
		}

		$article_topics_key = array( 'topic_id', 'topic_title' );
		if( !empty( $article_topics ) ){
			foreach ($article_topics as $k => $v) {
				foreach ($v as $key => $value) {
					if( !in_array( $key, $article_topics_key ) ) unset( $article_topics[$k][$key] );
				}
			}
		}

		
		$info = array(
			'article_info' => $article_info,
			//'comments_count' => $comments_count,
			//'comments' => $comments,
			'article_topics' => $article_topics,
		);
		H::ajax_json_output(AWS_APP::RSM($info, 1, null));
		
	}

	public function comment_action()
	{	
		if (! isset($_GET['id']))
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误请求,缺少文章id')));
		}
		
		
		if (! $article_info = $this->model('article')->get_article_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文章不存在或已被删除')));
		}
		
		
		/*if ($this->user_id)
		{
			$article_info['vote_info'] = $this->model('article')->get_article_vote_by_id('article', $article_info['id'], null, $this->user_id);
		}*/
		
		//$article_info['vote_users'] = $this->model('article')->get_article_vote_users_by_id('article', $article_info['id'], 1, 10);
		
		//TPL::assign('article_info', $article_info);  //文章信息
		
		//TPL::assign('article_topics', $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article'));
		//$article_topics = $this->model('topic')->get_topics_by_item_id($article_info['id'], 'article');  //文章所属话题
		
		//发起人擅长话题
		//TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($article_info['user_info']['uid'], $user['reputation'], 5));
				
		
		if ($_GET['item_id'])
		{
			$comments[] = $this->model('article')->get_comment_by_id($_GET['item_id']);
		}
		else
		{
			$comments = $this->model('article')->get_comments($article_info['id'], $_GET['page'], 100);
		}
		
		if ($comments AND $this->user_id)
		{
			foreach ($comments AS $key => $val)
			{
				$comments[$key]['vote_info'] = $this->model('article')->get_article_vote_by_id('comment', $val['id'], $this->user_id);
			}
		}
	
		//更新文章浏览数
		$this->model('article')->update_views($article_info['id']);
		
		//TPL::assign('comments', $comments);  评论
		//TPL::assign('comments_count', $article_info['comments']);  评论数目

		//如果有附件
		/*
		if(  $article_info['has_attach'] ){
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $article_info['message'], $matches);

			foreach( $matches[0] as $k => $v ){
				 $my_num = substr($v, 8, -9);
				 $my_replace = "<img src='".$article_info['attachs'][$my_num]['attachment']."'/>";
				 $article_info['message'] = str_replace($v, $my_replace, $article_info['message']);
				 unset( $article_info['attachs'][$my_num] );
			}

			if( !empty( $article_info['attachs'] ) ){
				foreach ($article_info['attachs'] as $key => $value) {
					$article_info['message'] .= "<br><img src='".$value['attachment']."'/>";
				}
			}
		}*
		//把\n替换成<br/>
		$article_info['message'] = str_replace("\n", "<br>", $article_info['message'] );
		*/

		/*
		$article_info_key = array( 'id', 'title', 'message', 'votes' );
		if( !empty( $article_info ) ){
			foreach( $article_info as $k => $v ){
				if( !in_array( $k, $article_info_key ) ) unset( $article_info[$k] );
			}
		}


		$article_topics_key = array( 'topic_id', 'topic_title' );
		if( !empty( $article_topics ) ){
			foreach ($article_topics as $k => $v) {
				foreach ($v as $key => $value) {
					if( !in_array( $key, $article_topics_key ) ) unset( $article_topics[$k][$key] );
				}
			}
		}
		*/

		$comment_key = array( 'id', 'uid', 'message', 'votes', 'at_uid', 'add_time', 'user_info', 'at_user_info' );
		$user_info_key = array( 'uid', 'user_name', 'avatar_file' );
		if( !empty( $comments ) ){
			foreach(  $comments as $c_k => $c_v ){
				foreach( $c_v as $k => $v ){
					if( !in_array( $k, $comment_key ) ) unset( $comments[$c_k][$k] );
					if( $k = 'user_info' && !empty( $comments[$c_k]['user_info'] ) ){
						foreach ($comments[$c_k]['user_info'] as $key => $value) {
							if( !in_array( $key, $user_info_key ) ) unset( $comments[$c_k]['user_info'][$key] );
							if( $key == 'avatar_file' ) $comments[$c_k]['user_info'][$key] = str_replace('min', 'max', $comments[$c_k]['user_info'][$key]);
						}
					}

					if( $k = 'at_user_info' && !empty( $comments[$c_k]['at_user_info'] ) ){
						foreach ($comments[$c_k]['at_user_info'] as $key => $value) {
							if( !in_array( $key, $user_info_key ) ) unset( $comments[$c_k]['at_user_info'][$key] );
							if( $key == 'avatar_file' ) $comments[$c_k]['at_user_info'][$key] = str_replace('min', 'max', $comments[$c_k]['at_user_info'][$key]);
						}
					}
				}

				$comments[$c_k]['vote_value'] = 0;
				if( !empty($this->user_id) ){
					$ret = $this->model('myapi')->get_article_comment_vote_value($this->user_id,$c_v['id']);
					if( !empty( $ret ) )  $comments[$c_k]['vote_value'] = $ret;
				}

			}
		}
		
		$info = array(
			'total_rows' => $article_info['comments'],
			//'comments_count' => $comments_count,
			'rows' => $comments,
			//'article_topics' => $article_topics,
		);
		H::ajax_json_output(AWS_APP::RSM($info, 1, null));
		
	}
}
