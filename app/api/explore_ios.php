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

class explore_ios extends AWS_CONTROLLER
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
	
	//GET: category(选),per_page(选，默认:10),sort_type([new,hot]选,默认:最新),page(默认1),day(默认30),is_recommend
	public function index_action()
	{
		
		if ($_GET['category'])
		{
			if (is_numeric($_GET['category']))
			{
				$category_info = $this->model('system')->get_category_info($_GET['category']);
			}
			else
			{
				$category_info = $this->model('system')->get_category_info_by_url_token($_GET['category']);
			}
		}
		
		
		//可能感兴趣的人
		/*
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));
		}
		*/
		
		//热门用户
		/*
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_hot_users', $this->model('module')->sidebar_hot_users($this->user_id, 5));
		}*/
		
		//热门话题
		/*
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));
		}*/

		if (! $_GET['per_page'])
		{
			$_GET['per_page'] = 10;
		}

		if (! $_GET['sort_type'])
		{
			$_GET['sort_type'] = 'new';
		}
		
		if ($_GET['sort_type'] == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts(null, $category_info['id'], null, $_GET['day'], $_GET['page'], $_GET['per_page']);
		}
		else
		{				
			$posts_list = $this->model('posts')->get_posts_list(null, $_GET['page'], $_GET['per_page'], $_GET['sort_type'], null, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
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
		
		if( empty( $posts_list ) )  H::ajax_json_output(AWS_APP::RSM(array( 'total_rows'=>0 ), 1, null));

		$question_key = array( 'post_type', 'title', 'views', 'id', 'question_id', 'comments', 'question_content', 'add_time', 'update_time', 'published_uid', 'answer_count', 'answer_users', 'view_count', 'focus_count', 'topics', 'user_info', 'answer' );
		
		$answer_users_key = array( 'user_name', 'uid', 'avatar_file' );
		$topics_key = array( 'topic_id', 'topic_title' );
		$user_info_key = array( 'uid', 'user_name', 'avatar_file' );


		foreach( $posts_list as $key => $val ){
			foreach ($val  as $k => $v) {

				//贡献者
				if( $k == 'answer_users' && !empty($val['answer_users']) ){
					foreach( $val['answer_users'] as $answer_users_k => $answer_users_v ){
						foreach ($answer_users_v as $answer_users_k_k => $answer_users_v_v) {
							if( !in_array($answer_users_k_k, $answer_users_key) ) unset( $posts_list[$key][$k][$answer_users_k][$answer_users_k_k] );
							if( $answer_users_k_k == 'avatar_file' ){
								$posts_list[$key][$k][$answer_users_k][$answer_users_k_k] = str_replace('min', 'max', $posts_list[$key][$k][$answer_users_k][$answer_users_k_k]);
							}
						}
					}
				}

				//话题
				if( $k == 'topics' && !empty($val['topics']) ){
					foreach( $val['topics'] as $topics_k => $topics_v ){
						foreach ($topics_v as $topics_k_k => $topics_v_v) {
							if( !in_array($topics_k_k, $topics_key) ) unset( $posts_list[$key][$k][$topics_k][$topics_k_k] );
						}
					}
				}

				//提问者信息
				if( $k == 'user_info' ){
					foreach( $val['user_info'] as $user_info_k => $user_info_v ){
							if( !in_array($user_info_k, $user_info_key) ) unset( $posts_list[$key][$k][$user_info_k] );
							if(  $user_info_k == 'avatar_file' ){
								$posts_list[$key][$k][$user_info_k] = str_replace('min', 'max', $posts_list[$key][$k][$user_info_k]);
							}
					}
				}

				//最新回答
				if( $k == 'answer' && !empty($val['answer']['user_info']) ){
					foreach( $val['answer']['user_info'] as $user_info_k => $user_info_v ){
							if( !in_array($user_info_k, $user_info_key) ) unset( $posts_list[$key][$k]['user_info'][$user_info_k] );
					}
				}


				if( !in_array($k, $question_key) ) unset( $posts_list[$key][$k] );
			}
		}

		
		$rows = array();
		if( !empty( $posts_list ) ){
			foreach ($posts_list as $key => $value) {
					if( isset( $value['question_id'] ) ) $id = 'question_id_'.$value['question_id'];
					else $id = 'article_id_'.$value['id'];
					$rows[$id] =  $value;
			}
		}

		//print_r( $rows );


		 H::ajax_json_output(AWS_APP::RSM(array( 'total_rows'=>count($posts_list),'rows'=>$rows ), 1, null));

		//TPL::assign('posts_list', $posts_list);
		//TPL::assign('posts_list_bit', TPL::output('explore/ajax/list', false));
		
		//TPL::output('explore/index');
	}
}