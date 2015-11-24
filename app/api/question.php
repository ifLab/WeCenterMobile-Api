<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
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

class question extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		
		$rule_action['actions'] = array(
			'index'
		);
		

		return $rule_action;
	}


	public function setup()
	{
		HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}

	//删除问题
	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			if ($this->user_id != $question_info['published_uid'])
			{
				$this->model('account')->send_delete_message($question_info['published_uid'], $question_info['question_content'], $question_info['question_detail']);
			}

			$this->model('question')->remove_question($question_info['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function answer_comments_action()
	{
		if (! $_GET['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('答案不存在')));
		}

		if(! $answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('答案不存在')));
		}

		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);

		if(! $comments)
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$at_uids = $this->model('question')->parse_at_user($comments[$key]['message'],false,true);

			if($at_uids AND ($at_user_info = $this->model('account')->get_user_info_by_uids($at_uids)) )
			{
				foreach ($at_user_info as $k => $v) 
				{
					$comments[$key]['at_user'][$v['uid']] = $this->model('myapi')->get_clean_user_info($v);
				}
			}

			$comments[$key]['user_info'] = $this->model('myapi')->get_clean_user_info($user_infos[$val['uid']]);
		}

		H::ajax_json_output(AWS_APP::RSM($comments, 1, null));
	}

	//单条答案详情
	public function answer_action()
	{
		if (! $_GET['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('答案不存在')));
		}

		if( !$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']) )
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('答案不存在')));
		}

		if($answer_info['uid'])
		{
			$user_info = $this->model('account')->get_user_info_by_uid( $answer_info['uid'], true );
		}	

		$answer['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($answer['answer_content']))));


		$answer_info['user_vote_status'] = 0;
		$answer_info['user_thanks_status'] = 0;

		if( $this->user_id AND $ret = $this->model('answer')->get_answer_vote_status($answer_info['answer_id'], $this->user_id) )
		{
			$answer_info['user_vote_status'] = $ret['vote_value'];
		}
			
		if( $this->user_id AND $this->model('answer')->user_rated('thanks', $answer_info['answer_id'], $this->user_id) )
		{
			$answer_info['user_thanks_status'] = 1;
		}
		

		$user_key = array( 'uid', 'user_name', 'signature' );

		//作者信息
		if( !empty( $user_info ) )
		{
			foreach ($user_info as $k => $v) 
			{
				if( !in_array($k, $user_key) ) unset( $user_info[$k] );
			}

			$user_info['avatar_file'] = get_avatar_url($user_info['uid'],'mid');
		}

		$answer_info['user_info'] = $user_info;

		$answer_info['question_content'] = '';

		if( $question_info = $this->model('question')->get_question_info_by_id( $answer_info['question_id'] ) )
		{
			$answer_info['question_content'] = $question_info['question_content'];
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'answer' => $answer_info
		), 1, null));
	}



	public function save_answer_comment_action()
	{
		if (! $_POST['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回复不存在')));
		}

		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有发表评论的权限')));
		}

		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入评论内容')));
		}

		if (get_setting('comment_limit') > 0 AND cjk_strlen($_POST['message']) > get_setting('comment_limit'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字节', get_setting('comment_limit'))));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);
		$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['message']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		$this->model('answer')->insert_answer_comment($_POST['answer_id'], $this->user_id, $_POST['message']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_POST['answer_id']),
			'type_name' => 'answer'
		), 1, null));
	}


	public function index_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在或已被删除')));
 		}

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

		$question_info['redirect'] = $this->model('question')->get_redirect($question_info['question_id']);

		if ($question_info['redirect']['target_id'])
		{
			$target_question = $this->model('question')->get_question_info_by_id($question_info['redirect']['target_id']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_question = $this->model('question')->get_question_info_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('从问题 %s 跳转而来', '<a href="' . get_js_url('/question/' . $_GET['rf'] . '?rf=false') . '">' . $from_question['question_content'] . '</a>');
			}
		}

		if ($question_info['redirect'] and ! $_GET['rf'])
		{
			if ($target_question)
			{
				HTTP::redirect('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']);
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}
		else if ($question_info['redirect'])
		{
			if ($target_question)
			{
				$message = AWS_APP::lang()->_t('此问题将跳转至') . ' <a href="' . get_js_url('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']) . '">' . $target_question['question_content'] . '</a>';

				if ($this->user_id AND ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR (!$this->question_info['lock'] AND $this->user_info['permission']['redirect_question'])))
				{
					$message .= '&nbsp; (<a href="javascript:;" onclick="AWS.ajax_request(G_BASE_URL + \'/question/ajax/redirect/\', \'item_id=' . $question_info['question_id'] . '\');">' . AWS_APP::lang()->_t('撤消重定向') . '</a>)';
				}

				$redirect_message[] = $message;
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}

		$question_info['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['published_uid'], true);

		if ($_GET['column'] != 'log')
		{
			$this->model('question')->calc_popular_value($question_info['question_id']);
			$this->model('question')->update_views($question_info['question_id']);

			if (is_digits($_GET['uid']))
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
			else if (! $this->user_id AND !$this->user_info['permission']['answer_show'])
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
				$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $this->user_id);

				$answer_users_rated_thanks = $this->model('answer')->users_rated('thanks', $answer_ids, $this->user_id);
				$answer_users_rated_uninterested = $this->model('answer')->users_rated('uninterested', $answer_ids, $this->user_id);
            }

			foreach ($answer_list as $answer)
			{

				$answer['user_rated_thanks'] = $answer_users_rated_thanks[$answer['answer_id']];
				$answer['user_rated_uninterested'] = $answer_users_rated_uninterested[$answer['answer_id']];

				$answer['answer_content'] = cjk_substr( strip_ubb($answer['answer_content']),0,100);

				//$answer['agree_users'] = $answer_agree_users[$answer['answer_id']];
				$answer['agree_status'] = $answer_vote_status[$answer['answer_id']];

                $answer['user_info']['avatar_file'] = get_avatar_url($answer['uid']);

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


			$question_info['user_answered'] = 0;
			//如果系统设置了用户只能回答一次  
			if (get_setting('answer_unique') == 'Y')
			{
				if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
				{
					$question_info['user_answered'] = 1;
				}
				else
				{
					$question_info['user_answered'] = 0;
				}
			}

		}



		$question_info['user_follow_check'] = 0;
		$question_info['user_question_focus'] = 0;
		$question_info['user_thanks'] = 0;

		if ($this->user_id)
		{	
			if($this->model('question')->get_question_thanks($question_info['question_id'], $this->user_id))
			{
				$question_info['user_thanks'] = 1;
			}

			//当前用户是否已关注该问题作者
			if( $this->model('follow')->user_follow_check($this->user_id, $question_info['published_uid']) )
			{
				$question_info['user_follow_check'] = 1;
			}

			if( $this->model('question')->has_focus_question($question_info['question_id'], $this->user_id) )
			{
				$question_info['user_question_focus'] = 1;
			}

		}

		$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($question_info['question_detail'])));

		$question_topics = $this->model('topic')->get_topics_by_item_id($question_info['question_id'], 'question');

        $question_info['answer_count'] = $answer_count;
          
        //clean
        $question_key = array( 'question_id', 'question_content', 'question_detail', 'add_time', 'update_time', 'answer_count', 'view_count', 'agree_count', 'focus_count', 'against_count', 'thanks_count', 'comment_count', 'user_info', 'user_answered', 'user_thanks', 'user_follow_check', 'user_question_focus' );
        $user_key = array( 'uid', 'user_name', 'namecard_pic', 'signature' );
        $topics_key = array( 'topic_id', 'topic_title' );

        foreach ($question_info as $k => $v) 
        {
        	if( !in_array($k, $question_key) ) unset( $question_info[$k] );
        }

        //作者信息
		if( !empty( $question_info['user_info'] ) )
		{
			foreach ($question_info['user_info'] as $k => $v) 
			{
				if( !in_array($k, $user_key) ) unset( $question_info['user_info'][$k] );
			}

			$question_info['user_info']['avatar_file'] = get_avatar_url($question_info['user_info']['uid'],'mid');
		}

		if( !empty( $answers ) )
		{
			foreach ($answers as $key => $value) 
			{
				if( !empty( $value['user_info'] ) )
				{
					foreach ($value['user_info'] as $k => $v)
					{
						if( !in_array($k, $user_key) ) unset( $answers[$key]['user_info'][$k] );
					}

					$answers[$key]['user_info']['avatar_file'] = get_avatar_url($answers[$key]['user_info']['uid'],'mid');
				}

				$answers[$key]['answer_content'] = strip_tags( $value['answer_content'] );
			}
		}


		if( !empty($question_topics) )
		{
			foreach ($question_topics as $key => $val)
			{
				foreach ($val as $k => $v)
				{
					if( !in_array($k, $topics_key) ) unset( $question_topics[$key][$k] );
				}
			}
		}


		//$question_info['answers'] = $answers;

        H::ajax_json_output(AWS_APP::RSM(array(
            'question_info' => $question_info,
            'question_topics' => $question_topics,
            'answers' => array_values($answers)
        ), 1, null));    
	}




	public function unverify_modify_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']) or ! $_GET['log_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在'), '/');
		}

		if (($question_info['published_uid'] != $this->user_id) AND (! $this->user_info['permission']['is_administortar']) AND (! $this->user_info['permission']['is_moderator']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'), '/');
		}

		$this->model('question')->unverify_modify($_GET['question_id'], $_GET['log_id']);

		H::redirect_msg(AWS_APP::lang()->_t('取消确认修改成功, 正在返回...'), '/question/id-' . $_GET['question_id'] . '__column-log__rf-false');
	}

	public function verify_modify_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']) or ! $_GET['log_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在'), '/');
		}

		if (($question_info['published_uid'] != $this->user_id) AND (! $this->user_info['permission']['is_administortar']) AND (! $this->user_info['permission']['is_moderator']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'), '/');
		}

		$this->model('question')->verify_modify($_GET['question_id'], $_GET['log_id']);

		H::redirect_msg(AWS_APP::lang()->_t('确认修改成功, 正在返回...'), '/question/id-' . $_GET['question_id'] . '__column-log__rf-false');
	}






    public function update_answer_action()
    {
        if (! $answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('答案不存在')));
        }

        if ($_POST['do_delete'])
        {
            if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
            }

            $this->model('answer')->remove_answer_by_id($_POST['answer_id']);

            // 通知回复的作者
            if ($this->user_id != $answer_info['uid'])
            {
                $this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_REMOVE_ANSWER, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
                    'from_uid' => $this->user_id,
                    'question_id' => $answer_info['question_id']
                ));
            }

            $this->model('question')->save_last_answer($answer_info['question_id']);

            H::ajax_json_output(AWS_APP::RSM(array('type'=>'remove'), 1, null));
        }

        $answer_content = trim($_POST['answer_content'], "\r\n\t");

        if (!$answer_content)
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
        }

        if (strlen($answer_content) < get_setting('answer_length_lower'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字节', get_setting('answer_length_lower'))));
        }

        if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($answer_content))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
        }

        if (!$this->model('publish')->insert_attach_is_self_upload($answer_content, $_POST['attach_ids']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
        }

        if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个回复')));
        }

        if ($answer_info['uid'] == $this->user_id and (time() - $answer_info['add_time'] > get_setting('answer_edit_time') * 60) and get_setting('answer_edit_time') and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经超过允许编辑的时限')));
        }

        $this->model('answer')->update_answer($_POST['answer_id'], $answer_info['question_id'], $answer_content, $_POST['attach_access_key']);

      	 H::ajax_json_output(AWS_APP::RSM(array('type'=>'update'), 1, null));
    }






}