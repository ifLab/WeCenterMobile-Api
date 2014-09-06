<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class question extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		
		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'question',
				'square',
				'best_answer_detail',
				'answer_detail'
			);
		}
		
		return $rule_action;
	}
	public function answer_detail_action(){
		if( empty( $_GET['id'] ) )  H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误请求,缺少回答id')));

		$answer = $this->model('myapi')->get_answer($_GET['id']);
		if( empty( $answer ) )  H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回答不存在')));

		//markdown to html
		$answer['answer_content'] = nl2br( FORMAT::parse_markdown( $answer['answer_content'] ) );

		//如果存在附件
		if( $answer['has_attach'] ){
			$rows = $this->model('myapi')->get_answer_attach($_GET['id']);
			$answer_attach =array();
			foreach( $rows as $k => $v ){
				$answer_attach[$v['id']] = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/answer/'.date('Ymd',$v['add_time']).'/'.$v['file_location'];
			}

			
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $answer['answer_content'], $matches);

			foreach( $matches[0] as $k => $v ){
				 $my_num = substr($v, 8, -9);
				 $my_replace = "<img src='".$answer_attach[$my_num]."'/>";
				 $answer['answer_content'] = str_replace($v, $my_replace, $answer['answer_content']);
			}
				
		}

		//把\n替换成<br/>
		//$answer['answer_content'] = str_replace("\n", "<br>", $answer['answer_content'] );

		$user_info = $this->model('myapi')->get_user_info( $answer['uid'] );


		$answer_key = array( 'answer_id', 'question_id', 'answer_content', 'add_time', 'agree_count', 'uid', 'comment_count' );
		foreach ($answer as $key => $value) {
			if( !in_array( $key , $answer_key ) ) unset( $answer[$key] );
		}

		$user_info_key = array( 'user_name', 'avatar_file' );
		foreach ($user_info as $key => $value) {
			if( !in_array( $key , $user_info_key ) ) unset( $user_info[$key] );
		}

		$user_info['signature'] = $this->model('myapi')->get_signature( $answer['uid'] );
		if( !empty( $user_info['avatar_file'] ) )  $user_info['avatar_file'] = str_replace('min', 'max', $user_info['avatar_file']);
			
		$answer['vote_value'] = 0;
		if( !empty($this->user_id) ){
			$ret = $this->model('myapi')->get_vote_value($this->user_id,$answer['answer_id']);
			if( !empty( $ret ) )  $answer['vote_value'] = $ret;
		}

		$answer = array_merge( $answer, $user_info );
		H::ajax_json_output(AWS_APP::RSM($answer, 1, null));
	}

	public function best_answer_detail_action(){
		if( empty( $_GET['id'] ) )  H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误请求,缺少问题id')));

		$best_answer = array();
		$ret = $this->model('myapi')->get_question_info( $_GET['id'] );
		if( !$ret ) H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在或已被删除')));

		$best_answer['question_info']['question_content'] = $ret['question_content'];
		$best_answer['question_info']['question_id'] = $ret['question_id'];

		$ret = $this->model('myapi')->get_answer_info( $ret['best_answer'] );
		if( !$ret ) H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('最佳回复不存在')));


		//markdown to html
		$ret['answer_content'] = nl2br( FORMAT::parse_markdown( $ret['answer_content'] ) );

		//answer_content校正
		$answer_attachs = $this->model('publish')->get_attach('answer', $ret['answer_id'], 'max');

		if( !empty( $answer_attachs ) ){
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/',$ret['answer_content'], $matches);
			foreach( $matches[0] as $m_k => $m_v ){
				 //print_r( $matches );
				 $my_num = substr($m_v, 8, -9);
				 $my_replace = "<img src='".$answer_attachs[$my_num]['attachment']."'/>";
				 $ret['answer_content'] = str_replace( $m_v, $my_replace, $ret['answer_content'] );
			}
		}

		//把\n替换成<br/>
		//$ret['answer_content'] = str_replace("\n", "<br>",  $ret['answer_content'] );

		$best_answer['answer_info']['answer_id'] = $ret['answer_id'];
		$best_answer['answer_info']['answer_content '] = $ret['answer_content'];
		$best_answer['answer_info']['comment_count'] = $ret['comment_count'];
		$best_answer['answer_info']['agree_count'] = $ret['agree_count'];

		$user_info = $this->model('myapi')->get_user_info($ret['uid']);
		$best_answer['answer_info']['avatar_file'] = str_replace('min', 'max',$user_info['avatar_file']);
		$best_answer['answer_info']['user_name'] = $user_info['user_name'];
		$best_answer['answer_info']['uid'] = $ret['uid'];
		$best_answer['answer_info']['signature'] = $this->model('myapi')->get_signature($ret['uid']);

		//是否已经感谢
		$best_answer['answer_info']['has_thanks'] = 0;
		if( !empty($this->user_id) ){
			 $ret = $this->model('myapi')->has_thanks($this->user_id,$ret['answer_id']);
			 if( !empty( $ret ) )  $best_answer['answer_info']['has_thanks'] = 1;
		}

		H::ajax_json_output(AWS_APP::RSM($best_answer, 1, null));
		//print_r( $best_answer );exit;
	}

	public function question_action()
	{
		if ( !isset($_GET['id']))
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误请求,缺少问题id')));
		}
		

		//获取问题信息
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}
		
		//???????
		if (! $_GET['sort'] or $_GET['sort'] != 'ASC')
		{
			$_GET['sort'] = 'DESC';
		}
		else
		{
			$_GET['sort'] = 'ASC';
		}
		
		if (get_setting('unfold_question_comments') == 'Y')
		{
			$_GET['comment_unfold'] = 'all';
		}
		
		
		//问题附件
		if ($question_info['has_attach'])
		{
			$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min');
			
			$question_info['attachs_ids'] = FORMAT::parse_attachs($question_info['question_detail'], true);

			//print_r($question_info['attachs_ids']);
			//exit();
		}

		//问题所属分类
		/*
		if ($question_info['category_id'] AND get_setting('category_enable') == 'Y')
		{
			$question_info['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);
		}*/

		//问题发起人
		//$question_info['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['published_uid'], true);


		
		if ($_GET['column'] != 'log')
		{
			$this->model('question')->calc_popular_value($question_info['question_id']);
			$this->model('question')->update_views($question_info['question_id']);
			
			if (is_numeric($_GET['uid']))
			{
				$answer_list_where[] = 'uid = ' . intval($_GET['uid']);
				$answer_count_where = 'uid = ' . intval($_GET['uid']);
			}
			else if ($_GET['uid'] == 'focus' and $this->user_id)
			{
				if ($friends = $this->model('follow')->get_user_friends($this->user_id, false))
				{
					foreach ($friends as $key => $val)
					{
						$follow_uids[] = $val['uid'];
					}
				}
				else
				{
					$follow_uids[] = 0;
				}
				
				$answer_list_where[] = 'uid IN(' . implode($follow_uids, ',') . ')';
				$answer_count_where = 'uid IN(' . implode($follow_uids, ',') . ')';
				$answer_order_by = 'add_time ASC';
			}
			else if ($_GET['sort_key'] == 'add_time')
			{
				$answer_order_by = $_GET['sort_key'] . " " . $_GET['sort'];
			}
			else
			{
				$answer_order_by = "agree_count " . $_GET['sort'] . ", against_count ASC, add_time ASC";
			}
			
			if ($answer_count_where)
			{
				$answer_count = $this->model('answer')->get_answer_count_by_question_id($question_info['question_id'], $answer_count_where);
			}
			else
			{
				$answer_count = $question_info['answer_count'];
			}
			
			if (isset($_GET['answer_id']) and (! $this->user_id OR $_GET['single']))
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($_GET['answer_id']));
			}
			else if (! $this->user_id && !$this->user_info['permission']['answer_show'])
			{
				if ($question_info['best_answer'])
				{
					$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($question_info['best_answer']));
				}
				else
				{
					$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, null, 'agree_count DESC');
				}
			}
			else
			{
				if ($answer_list_where)
				{
					$answer_list_where = implode(' AND ', $answer_list_where);
				}
				
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], 100), $answer_list_where, $answer_order_by);
			}
			
			// 最佳回复预留
			$answers[0] = '';

			if (! is_array($answer_list))
			{
				$answer_list = array();
			}
			
			$answer_ids = array();
			$answer_uids = array();
			
			foreach ($answer_list as $answer)
			{
				$answer_ids[] = $answer['answer_id'];
				$answer_uids[] = $answer['uid'];
				
				if ($answer['has_attach'])
				{
					$has_attach_answer_ids[] = $answer['answer_id'];
				}
			}
			
			if (!in_array($question_info['best_answer'], $answer_ids) AND intval($_GET['page']) < 2)
			{
				$answer_list = array_merge($this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . $question_info['best_answer']), $answer_list);
			}
			
			if ($answer_ids)
			{
				$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);
				
				$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $this->user_id);
				
				$answer_users_rated_thanks = $this->model('answer')->users_rated('thanks', $answer_ids, $this->user_id);
				$answer_users_rated_uninterested = $this->model('answer')->users_rated('uninterested', $answer_ids, $this->user_id);
				$answer_attachs = $this->model('publish')->get_attachs('answer', $has_attach_answer_ids, 'min');
			}
			
			foreach ($answer_list as $answer)
			{
				//markdown to html
				$answer['answer_content'] = nl2br( FORMAT::parse_markdown( $answer['answer_content'] ) );

				if ($answer['has_attach'])
				{
					$answer['attachs'] = $answer_attachs[$answer['answer_id']];
					
					$answer['insert_attach_ids'] = FORMAT::parse_attachs($answer['answer_content'], true);


					//By Hwei
					preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $answer['answer_content'], $matches);

					foreach( $matches[0] as $k => $v ){
						 $my_num = substr($v, 8, -9);
						 $my_replace = "<img src='".$answer['attachs'][$my_num]['attachment']."'/>";
						 $answer['answer_content'] = str_replace($v, $my_replace, $answer['answer_content']);
					}

				}
				
				$answer['user_rated_thanks'] = $answer_users_rated_thanks[$answer['answer_id']];
				$answer['user_rated_uninterested'] = $answer_users_rated_uninterested[$answer['answer_id']];


				//把\n替换成<br/>
				//$answer['answer_content'] = str_replace("\n", "<br>", $answer['answer_content'] );
				
				//$answer['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($answer['answer_content']))));
				
				$answer['agree_users'] = $answer_agree_users[$answer['answer_id']];
				$answer['agree_status'] = $answer_vote_status[$answer['answer_id']];
				
				if ($question_info['best_answer'] == $answer['answer_id'] AND intval($_GET['page']) < 2)
				{
					$answers[0] = $answer;
				}
				else
				{
					$answers[] = $answer;
				}
			}
			
			if (! $answers[0])
			{
				unset($answers[0]);
			}
			
			if (get_setting('answer_unique') == 'Y')
			{
				if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
				{
					TPL::assign('user_answered', TRUE);
				}
			}
			
			//TPL::assign('answers', $answers);  回复
			//TPL::assign('answer_count', $answer_count);  //回复数目
		}

		//markdown to html
		$question_info['question_detail'] = nl2br( FORMAT::parse_markdown( $question_info['question_detail'] ) );

		//如果有附件替换附件
		if ($question_info['has_attach'])
		{
			preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $question_info['question_detail'], $matches);

			foreach( $matches[0] as $k => $v ){
				 $my_num = substr($v, 8, -9);
				 $my_replace = "<img src='".$question_info['attachs'][$my_num]['attachment']."'/>";
				 $question_info['question_detail'] = str_replace($v, $my_replace, $question_info['question_detail']);
			}
		}
		//把\n替换成<br/>
		//$question_info['question_detail'] = str_replace("\n", "<br>", $question_info['question_detail'] );


		//$question_info['question_detail'] = nl2br($question_info['question_detail']);
		
		
		//$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_markdown($question_info['question_detail'])));

		
		//TPL::assign('question_info', $question_info);  //单个问题信息

		//TPL::assign('question_focus', $this->model('question')->has_focus_question($question_info['question_id'], $this->user_id));

		
		$question_topics = $this->model('topic')->get_topics_by_item_id($question_info['question_id'], 'question');
		
		/*
		if (sizeof($question_topics) == 0 AND $this->user_id)
		{
			$related_topics = $this->model('question')->get_related_topics($question_info['question_content']);
			
			TPL::assign('related_topics', $related_topics);
		}*/
		
		//TPL::assign('question_topics', $question_topics);  //问题所属话题

		
		//TPL::assign('question_related_list', $this->model('question')->get_related_question_list($question_info['question_id'], $question_info['question_content']));
		//TPL::assign('question_related_links', $this->model('related')->get_related_links('question', $question_info['question_id']));
		

		
		//TPL::assign('attach_access_key', md5($this->user_id . time()));
		//TPL::assign('redirect_message', $redirect_message);
		
		//TPL::output('question/index');

		
		$question_info_key = array( 'question_id', 'question_content', 'question_detail', 'focus_count' );
		if( !empty( $question_info ) ){
			foreach( $question_info as $k => $v ){
				if( !in_array( $k, $question_info_key ) ) unset( $question_info[$k] );
			}
		}

		
		$answers_key = array( 'answer_id', 'answer_content', 'uid', 'user_name', 'agree_count' );
		if( !empty( $answers ) ){
			foreach ($answers as $k => $v) {
				foreach ($v as $key => $value) {
					if( !in_array( $key, $answers_key ) ) unset( $answers[$k][$key] );
				}
				//增加回答者头像和一句话签名
				
				$answers[$k]['avatar_file'] = $this->model('myapi')->get_avatar_file($v['uid']);
				if( !empty( $answers[$k]['avatar_file'] ) )  $answers[$k]['avatar_file'] = str_replace('min', 'max', $answers[$k]['avatar_file']);
				$answers[$k]['signature'] = $this->model('myapi')->get_signature($v['uid']);
				$answers[$k]['vote_value'] = 0;
				if( !empty($this->user_id) ){
					$ret = $this->model('myapi')->get_vote_value($this->user_id,$v['answer_id']);
					if( !empty( $ret ) )  $answers[$k]['vote_value'] = $ret;
				}
				
			}
		}

		$question_topics_key = array( 'topic_id', 'topic_title' );
		if( !empty( $question_topics ) ){
			foreach ($question_topics as $k => $v) {
				foreach ($v as $key => $value) {
					if( !in_array( $key, $question_topics_key ) ) unset( $question_topics[$k][$key] );
				}
			}
		}

		//当前用户是否已关注该问题
		$question_info['has_focus'] = 0;
		if( !empty($this->user_id) ){
			$ret = $this->model('myapi')->has_focus_question($this->user_id,$_GET['id']);
			if( !empty( $ret ) )  $question_info['has_focus'] = 1;
		}

		$info = array(
			'question_info' => $question_info,
			'answer_count' => $answer_count,
			'answers' => $answers,
			'question_topics' => $question_topics,
		);
		H::ajax_json_output(AWS_APP::RSM($info, 1, null));

	}
}
