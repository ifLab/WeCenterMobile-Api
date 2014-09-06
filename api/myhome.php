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

class myhome_class extends AWS_MODEL
{
	public function home_activity($uid, $limit = 10)
	{		
		// 我关注的话题	
		/*	
		if ($user_focus_topics_ids = $this->model('topic')->get_focus_topic_ids_by_uid($uid))
		{
			if ($user_focus_topics_questions_ids = $this->model('topic')->get_item_ids_by_topics_ids($user_focus_topics_ids, 'question', 1000))
			{
				if ($user_focus_topics_info = $this->model('question')->get_topic_info_by_question_ids($user_focus_topics_questions_ids))
				{
					foreach ($user_focus_topics_info AS $key => $user_focus_topics_info_by_question)
					{
						foreach ($user_focus_topics_info_by_question AS $_key => $_val)
						{
							if (!in_array($_val['topic_id'], $user_focus_topics_ids))
							{
								unset($user_focus_topics_info[$key][$_key]);
							}
						}
					}
				}
			}
			
			$user_focus_topics_article_ids = $this->model('topic')->get_item_ids_by_topics_ids($user_focus_topics_ids, 'article', 1000);
		}
		*/

		// 我关注的问题
		if ($user_focus_questions_ids = $this->model('question')->get_focus_question_ids_by_uid($uid))
		{			
			// 回复问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id IN (" . implode(',', $user_focus_questions_ids) . ") AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
		}
		
		/*
		// 我关注的话题
		if ($user_focus_topics_questions_ids)
		{
			// 回复问题, 新增问题, 赞同答案
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ") AND associate_action IN (" . ACTION_LOG::ANSWER_QUESTION . ", " . ACTION_LOG::ADD_QUESTION . ", " . ACTION_LOG::ADD_AGREE . ") AND uid <> " . $uid . ")";
		}
		
		if ($user_focus_topics_article_ids)
		{
			// 发表文章
			$where_in[] = "(associate_id IN (" . implode(',', $user_focus_topics_article_ids) . ") AND associate_action = " . ACTION_LOG::ADD_ARTICLE . " AND uid <> " . $uid . ")";
		}
		*/
		
		// 我关注的人
		if ($user_follow_uids = $this->model('follow')->get_user_friends_ids($uid))
		{
			// 添加问题, 回复问题, 添加文章
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_ARTICLE . '))';
			
			// 增加赞同
			$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action IN(" . ACTION_LOG::ADD_AGREE .", " . ACTION_LOG::ADD_AGREE_ARTICLE . ") AND uid <> " . $uid . ")";
			
			// 添加问题关注
			if ($user_focus_questions_ids)
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . " AND associate_id NOT IN (" . implode(',', $user_focus_questions_ids) . "))";
			}
			else
			{
				$where_in[] = "(uid IN (" . implode(',', $user_follow_uids) . ") AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . ")";
			}
		}
		else
		{
			$user_follow_uids = array();
		}
		
		// 添加问题, 添加文章
		$where_in[] = "(associate_action IN (" . ACTION_LOG::ADD_QUESTION . ", " . ACTION_LOG::ADD_ARTICLE . ") AND uid = " . $uid . ")";
		
		if ($questions_uninterested_ids = $this->model('question')->get_question_uninterested($uid))
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id NOT IN (" . implode(',', $questions_uninterested_ids) . "))";
		}
		else
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . ")";
		}
			
		if ($where_in AND $where)
		{
			$where .= ' AND (' . implode(' OR ', $where_in) . ')';
		}
		else if ($where_in)
		{
			$where = implode(' OR ', $where_in);
		}
		
		if (! $action_list = ACTION_LOG::get_actions_fresh_by_where($where, $limit))
		{
			return false;
		}
		
		foreach ($action_list as $key => $val)
		{
			if (in_array($val['associate_action'], array(   //如果是添加文章或赞同文章
				ACTION_LOG::ADD_ARTICLE, 
				ACTION_LOG::ADD_AGREE_ARTICLE
			)))
			{
				$action_list_article_ids[] = $val['associate_id'];  //文章id
			}
			else
			{
				$action_list_question_ids[] = $val['associate_id'];  //问题id
				
				if (in_array($val['associate_action'], array(   //如果是回复问题或增加赞同
					ACTION_LOG::ANSWER_QUESTION, 
					ACTION_LOG::ADD_AGREE
				)) AND $val['associate_attached'])
				{
					$action_list_answer_ids[] = $val['associate_attached'];   //回答id
				}
			}
			
			if (! $action_list_uids[$val['uid']])
			{
				$action_list_uids[$val['uid']] = $val['uid'];   
			}

			//删除移动版多余字段
			//unset( $action_list[$key]['fold_status'] );
			//unset( $action_list[$key]['anonymous'] );
			//unset( $action_list[$key]['history_id'] );
		
		}
		
		if ($action_list_question_ids)   //问题内容
		{
			$question_infos = $this->model('question')->get_question_info_by_ids($action_list_question_ids);

			$question_infos_key = array( 'question_id', 'question_content' );
			if( !empty( $question_infos ) ){
				foreach ($question_infos as $key => $value) {
					foreach ($value as $k => $v) {
						if( !in_array( $k , $question_infos_key ) ) unset( $question_infos[$key][$k] );
					}
				}
			}

		}

		if ($action_list_answer_ids)   //回答内容
		{			
			$answer_infos = $this->model('answer')->get_answers_by_ids($action_list_answer_ids);
			$answer_attachs = $this->model('publish')->get_attachs('answer', $action_list_answer_ids, 'min');



			$answer_infos_key = array( 'answer_id', 'question_id', 'answer_content', 'agree_count' );

			if( !empty( $answer_infos ) ){
				foreach ($answer_infos as $key => $value) {
				
					//如果有附件
					if( $answer_infos[$key]['has_attach'] ){
						preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $answer_infos[$key]['answer_content'], $matches);

						foreach( $matches[0] as $k => $v ){
							 $my_num = substr($v, 8, -9);
							 $my_replace = "<img src='".$answer_attachs[$answer_infos[$key]['answer_id']][$my_num]['attachment']."'/>";
							 $answer_infos[$key]['answer_content'] = str_replace($v, $my_replace, $answer_infos[$key]['answer_content']);
						}
					}

					//把\n替换成<br/>
					$answer_infos[$key]['answer_content'] = str_replace("\n", "<br>", $answer_infos[$key]['answer_content'] );

					foreach ($value as $k => $v) {
						if( !in_array( $k , $answer_infos_key ) ) unset( $answer_infos[$key][$k] );
					}

				}
			}

		}		
		
		if ($action_list_uids)   //作者信息
		{
			$user_info_lists = $this->model('account')->get_user_info_by_uids($action_list_uids, true);

			$user_info_lists_key = array( 'uid', 'user_name', 'avatar_file' );
			if( !empty( $user_info_lists ) ){
				foreach ($user_info_lists as $key => $value) {
					foreach ($value as $k => $v) {
						if( !in_array( $k , $user_info_lists_key ) ) unset( $user_info_lists[$key][$k] );
					}
					$user_info_lists[$key]['avatar_file'] = str_replace('min', 'max', $value['avatar_file']);
				}
			}

		}
		
		if ($action_list_article_ids)  //文章内容
		{
			$article_infos = $this->model('article')->get_article_info_by_ids($action_list_article_ids);

			$article_infos_key = array( 'id', 'title' );
			if( !empty( $article_infos ) ){
				foreach ($article_infos as $key => $value) {
					foreach ($value as $k => $v) {
						if( !in_array( $k , $article_infos_key ) ) unset( $article_infos[$key][$k] );
					}
				}
			}

		}
				
		// 重组信息		
		foreach ($action_list as $key => $val)
		{
			$action_list[$key]['user_info'] = $user_info_lists[$val['uid']];
			
			/*
			if ($user_focus_topics_info[$val['associate_id']] AND !in_array($action_list[$key]['uid'], $user_follow_uids) AND $action_list[$key]['uid'] != $uid)
			{
				$topic_info = end($user_focus_topics_info[$val['associate_id']]);
			}
			else
			{
				unset($topic_info);
			}*/
			
			switch ($val['associate_action'])
			{
				//文章信息
				case ACTION_LOG::ADD_ARTICLE:   
				case ACTION_LOG::ADD_AGREE_ARTICLE:   
					$article_info = $article_infos[$val['associate_id']];
					
					$action_list[$key]['title'] = $article_info['title'];
					//$action_list[$key]['link'] = get_js_url('/article/' . $article_info['id']);
					
					$action_list[$key]['article_info'] = $article_info;
				break;
				
				//问题信息
				default:
					$question_info = $question_infos[$val['associate_id']];
					
					//$action_list[$key]['title'] = $question_info['question_content'];
					//$action_list[$key]['link'] = get_js_url('/question/' . $question_info['question_id']);
					
					// 是否关注
					
					if ($user_focus_questions_ids)
					{
						if (in_array($question_info['question_id'], $user_focus_questions_ids))
						{
							$question_info['has_focus'] = TRUE;
						}
					}
					
					// 对于回复问题的
					if ($answer_infos[$val['associate_attached']] AND in_array($val['associate_action'], array(
						ACTION_LOG::ANSWER_QUESTION, 
						ACTION_LOG::ADD_AGREE
					)))
					{
						$action_list[$key]['answer_info'] = $answer_infos[$val['associate_attached']];
					}
					
					$action_list[$key]['question_info'] = $question_info;
					
					// 处理回复
					if ($action_list[$key]['answer_info']['answer_id'])
					{
						if ($action_list[$key]['answer_info']['anonymous'] AND $val['associate_action'] == ACTION_LOG::ANSWER_QUESTION)
						{
							unset($action_list[$key]);
							
							continue;
						}
						
						$final_list_answer_ids[] = $action_list[$key]['answer_info']['answer_id'];
						
						/*
						if ($action_list[$key]['answer_info']['has_attach'])
						{
							$action_list[$key]['answer_info']['attachs'] = $answer_attachs[$action_list[$key]['answer_info']['answer_id']];
						}
						*/
					}
				break;
			}
			
		}
		
		if ($final_list_answer_ids)
		{
			//$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($final_list_answer_ids);
			$answer_vote_status = $this->model('answer')->get_answer_vote_status($final_list_answer_ids, $uid);
		}
		
		foreach ($action_list as $key => $val)
		{
			if (isset($action_list[$key]['answer_info']['answer_id']))
			{
				$answer_id = $action_list[$key]['answer_info']['answer_id'];
				
				/*
				if (isset($answer_agree_users[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_users'] = $answer_agree_users[$answer_id];
				}*/
				
				//我有没有赞同该回答
				if (isset($answer_vote_status[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_status'] = $answer_vote_status[$answer_id];
				}
			}
		}
		
		$action_list_key = array( 'history_id', 'associate_action', 'add_time', 'uid', 'user_info', 'associate_id', 'question_info', 'answer_info', 'article_info' );

		if( !empty( $action_list ) ){
				foreach ($action_list as $key => $value) {
					foreach ($value as $k => $v) {
						if( !in_array( $k , $action_list_key ) ) unset( $action_list[$key][$k] );
					}
				}
		}


		if( !empty( $action_list ) ) $action_list = array_values( $action_list );   //??????????

		return $action_list;
	}
}