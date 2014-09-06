<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class topic extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white";	// 黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		if ($this->user_info['permission']['visit_topic'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'topic';
			$rule_action['actions'][] = 'topic_best_answer';
		}
		
		return $rule_action;
	}

	public function square_action()
	{
		if (!$_GET['id'] AND !$this->user_id)
		{
			$_GET['id'] = 'hot';
		}

		if( !$_GET['per_page'] ) $_GET['per_page'] = 10;
		
		switch ($_GET['id'])
		{			
			default:
			case 'focus':
				if ($topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, calc_page_limit($_GET['page'], $_GET['per_page'])))
				{
					$topics_list_total_rows = $this->user_info['topic_focus_count'];
					
					foreach ($topics_list AS $key => $val)
					{
						$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
					}
				}
				
				$topic_key = array( 'topic_id', 'topic_title', 'topic_description', 'topic_pic' );
				if( !empty( $topics_list ) ){
					foreach ($topics_list as $k => $v) {
						foreach ($v as $k_k => $v_v) {
							if(  !in_array($k_k, $topic_key) ) unset( $topics_list[$k][$k_k] );
							if( $k_k = "topic_pic" ) $topics_list[$k][$k_k] = str_replace( '_32_32', '_100_100', $topics_list[$k][$k_k]);
						}
					}
				}

				H::ajax_json_output(AWS_APP::RSM(array(
						'total_rows' => $topics_list_total_rows,
						'rows' => $topics_list
					), 1, null));
			break;
			
			case 'hot':
				if (!$topics_list = AWS_APP::cache()->get('square_hot_topic_list_' . intval($_GET['page'])))
				{
					if ($topics_list = $this->model('topic')->get_topic_list(null, 'discuss_count DESC',$_GET['per_page'], $_GET['page']))
					{
						$topics_list_total_rows = $this->model('topic')->found_rows();
						
						AWS_APP::cache()->set('square_hot_topic_list_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
						
						foreach ($topics_list AS $key => $val)
						{
							$topics_list[$key]['action_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $val['topic_id']));
						}
					}
					
					AWS_APP::cache()->set('square_hot_topic_list_' . intval($_GET['page']), $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_hot_topic_list_total_rows');
				}
				
				$topic_key = array( 'topic_id', 'topic_title', 'topic_description', 'topic_pic' );
				if( !empty( $topics_list ) ){
					foreach ($topics_list as $k => $v) {
						foreach ($v as $k_k => $v_v) {
							if(  !in_array($k_k, $topic_key) ) unset( $topics_list[$k][$k_k] );
							if( $k_k = "topic_pic" ) $topics_list[$k][$k_k] = str_replace( '_32_32', '_100_100', $topics_list[$k][$k_k]);
						}
					}
				}

				H::ajax_json_output(AWS_APP::RSM(array(
						'total_rows' => $topics_list_total_rows,
						'rows' => $topics_list
					), 1, null));
				break;
			
			case 'today':
				if ($today_topics = rtrim(get_setting('today_topics'), ','))
				{
					if (!$today_topic = AWS_APP::cache()->get('square_today_topic_' . md5($today_topics)))
					{
						if ($today_topic = $this->model('topic')->get_topic_by_title(array_random(explode(',', $today_topics))))
						{					
							$today_topic['best_answer_users'] = $this->model('topic')->get_best_answer_users_by_topic_id($today_topic['topic_id'], 5);
							
							$today_topic['questions_list'] = $this->model('posts')->get_posts_list('question', 1, 3, 'new', explode(',', $today_topic['topic_id']));
							
							AWS_APP::cache()->set('square_today_topic_' . md5($today_topics), $today_topic, (strtotime('Tomorrow') - time()));
						}
					}
				}

					$topic_key = array( 'topic_id', 'topic_title', 'topic_description', 'topic_pic' );
					if( !empty( $topics_list ) ){
						foreach ($topics_list as $k => $v) {
							foreach ($v as $k_k => $v_v) {
								if(  !in_array($k_k, $topic_key) ) unset( $topics_list[$k][$k_k] );
								if( $k_k = "topic_pic" ) $topics_list[$k][$k_k] = str_replace( '_32_32', '_100_100', $topics_list[$k][$k_k]);
							}
						}
					}
					H::ajax_json_output(AWS_APP::RSM(array(
						'total_rows' => $topics_list_total_rows,
						'rows' => $topics_list
					), 1, null));
				break;
			break;

		}
	}

	public function topic_action()
	{	
		if (is_numeric($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if (!$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}
		
		if (!$topic_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题不存在')));
		}
		
		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_id($_GET['merged_id']);
				$topic_info['merged_tip'] = "您查看的话题已被合并到当前话题";
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}
		
		//此话题的最佳回答者
		//TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));
		
		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}
		
		$topic_info['topic_description'] = nl2br(FORMAT::parse_markdown($topic_info['topic_description']));

		H::ajax_json_output(AWS_APP::RSM(array(
			'topic_info' => $topic_info
		), 1, null));
	}


	public function topic_best_answer_action()
	{
		if (is_numeric($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if (!$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}
		
		if (!$topic_info)
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}
		
		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_id($_GET['merged_id']);
				$topic_info['merged_tip'] = "您查看的话题已被合并到当前话题";
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}

		$contents_topic_id = $topic_info['topic_id'];
		$contents_topic_title = $topic_info['topic_title'];
		
		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$merged_topic_ids[] = $val['source_id'];
			}
			
			$contents_topic_id .= ',' . implode(',', $merged_topic_ids);
			
			if ($merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids))
			{
				$contents_topic_title = array(
					$contents_topic_title
				);
				
				foreach($merged_topics_info AS $key => $val)
				{
					$contents_topic_title[] = $val['topic_title'];
				}
			}
			
			if ($contents_topic_title)
			{
				$contents_topic_title .= ',' . implode(',', $contents_topic_title);
			}
		}
		
		$best_answer = $this->model('topic')->get_topic_best_answer_action_list($contents_topic_id, $this->user_id, get_setting('contents_per_page'));

		$question_info_key = array('question_id','question_content');
		$answer_info_key = array('answer_id','answer_content','agree_count', 'uid');
		$best_answer_key = array('question_info','answer_info');

		if( empty( $best_answer ) ){
			 H::ajax_json_output(AWS_APP::RSM(array(
				'total_rows' => 0,
				), 1, null));
			 exit;
		}

		$new_best_answer = array();
		if( !empty( $best_answer ) ){
			foreach ($best_answer as $k => $v) {
				foreach ($v as $k_k => $v_v) {
					if(  !in_array($k_k, $best_answer_key) ) unset( $best_answer[$k][$k_k] );
					if( $k_k = "question_info" ){
						 //$topics_list[$k][$k_k] = str_replace( '_32_32', '_100_100', $topics_list[$k][$k_k]);
						 foreach ($best_answer[$k][$k_k] as $k_k_k => $v_v_v) {
						 	if(!in_array($k_k_k, $question_info_key)) unset( $best_answer[$k][$k_k][$k_k_k] );
						 }
					}
					if( $k_k = "answer_info" ){
						 //$topics_list[$k][$k_k] = str_replace( '_32_32', '_100_100', $topics_list[$k][$k_k]);
						 foreach ($best_answer[$k][$k_k] as $k_k_k => $v_v_v) {
						 	if(!in_array($k_k_k, $answer_info_key)) unset( $best_answer[$k][$k_k][$k_k_k] );
						 }
					}	
				}

				//取回答者信息(头像)
				if( !empty($best_answer[$k]['answer_info']['uid']) ){
					$user_info = $this->model('myapi')->get_user_info( $best_answer[$k]['answer_info']['uid'] );
					$best_answer[$k]['answer_info']['avatar_file'] = $user_info['avatar_file'];
					$best_answer[$k]['answer_info']['avatar_file'] = str_replace('min', 'max', $best_answer[$k]['answer_info']['avatar_file']);
				}

				$answer_attachs = $this->model('publish')->get_attach('answer', $best_answer[$k]['answer_info']['answer_id'], 'max');

				//附件
				//print_r( $answer_attachs );

				if( !empty( $answer_attachs ) ){
					preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $best_answer[$k]['answer_info']['answer_content'], $matches);
					foreach( $matches[0] as $m_k => $m_v ){
						 //print_r( $matches );
						 $my_num = substr($m_v, 8, -9);
						 $my_replace = "<img src='".$answer_attachs[$my_num]['attachment']."'/>";
						 $best_answer[$k]['answer_info']['answer_content'] = str_replace( $m_v, $my_replace, $best_answer[$k]['answer_info']['answer_content'] );
					}
				}

				//把\n替换成<br/>
				$best_answer[$k]['answer_info']['answer_content'] = str_replace("\n", "<br>",  $best_answer[$k]['answer_info']['answer_content'] );
				$new_best_answer[] = $best_answer[$k];
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
				'total_rows' => count( $best_answer ),
				'rows' => $new_best_answer
			), 1, null));
	}
}
