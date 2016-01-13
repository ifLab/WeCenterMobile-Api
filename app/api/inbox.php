<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class inbox extends AWS_CONTROLLER
{
	public function setup()
	{
		HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}

	public function index_action()
	{
		$this->model('account')->update_inbox_unread($this->user_id);

		if( empty( $_GET['per_page'] ) ) $_GET['per_page'] = get_setting('contents_per_page');

		if ($inbox_dialog = $this->model('message')->get_inbox_message($_GET['page'], $_GET['per_page'], $this->user_id))
		{
			$inbox_total_rows = $this->model('message')->found_rows();

			foreach ($inbox_dialog as $key => $val)
			{
				$dialog_ids[] = $val['id'];

				if ($this->user_id == $val['recipient_uid'])
				{
					$inbox_dialog_uids[] = $val['sender_uid'];
				}
				else
				{
					$inbox_dialog_uids[] = $val['recipient_uid'];
				}
			}
		}

		if ($inbox_dialog_uids)
		{
			if ($users_info_query = $this->model('account')->get_user_info_by_uids($inbox_dialog_uids))
			{
				foreach ($users_info_query as $user)
				{
					$users_info[$user['uid']] = $user;
				}
			}
		}

		if ($dialog_ids)
		{
			$last_message = $this->model('message')->get_last_messages($dialog_ids);
		}

		if ($inbox_dialog)
		{
			foreach ($inbox_dialog as $key => $value)
			{
				if ($value['recipient_uid'] == $this->user_id AND $value['recipient_count']) // 当前处于接收用户
				{
					$data[$key]['user_name'] = $users_info[$value['sender_uid']]['user_name'];
					$data[$key]['url_token'] = $users_info[$value['sender_uid']]['url_token'];

					$data[$key]['unread'] = $value['recipient_unread'];
					$data[$key]['count'] = $value['recipient_count'];

					$data[$key]['uid'] = $value['sender_uid'];
				}
				else if ($value['sender_uid'] == $this->user_id AND $value['sender_count']) // 当前处于发送用户
				{
					$data[$key]['user_name'] = $users_info[$value['recipient_uid']]['user_name'];
					$data[$key]['url_token'] = $users_info[$value['recipient_uid']]['url_token'];

					$data[$key]['unread'] = $value['sender_unread'];
					$data[$key]['count'] = $value['sender_count'];
					$data[$key]['uid'] = $value['recipient_uid'];
				}

				$data[$key]['last_message'] = $last_message[$value['id']];
				$data[$key]['update_time'] = $value['update_time'];
				$data[$key]['id'] = $value['id'];
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'total_rows' => $inbox_total_rows,
			'rows' => $data
		), 1, null));
	
	}


	public function read_action()
	{
		if (!$dialog = $this->model('message')->get_dialog_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/inbox/');
		}

		if ($dialog['recipient_uid'] != $this->user_id AND $dialog['sender_uid'] != $this->user_id)
		{
			H::redirect_msg(AWS_APP::lang()->_t('指定的站内信不存在'), '/inbox/');
		}

		$this->model('message')->set_message_read($_GET['id'], $this->user_id);

		if ($list = $this->model('message')->get_message_by_dialog_id($_GET['id']))
		{
			if ($dialog['sender_uid'] != $this->user_id)
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['sender_uid']);
			}
			else
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($dialog['recipient_uid']);
			}

			foreach ($list as $key => $val)
			{
				if ($dialog['sender_uid'] == $this->user_id AND $val['sender_remove'])
				{
					unset($list[$key]);
				}
				else if ($dialog['sender_uid'] != $this->user_id AND $val['recipient_remove'])
				{
					unset($list[$key]);
				}
				else
				{
					$list[$key]['message'] = FORMAT::parse_links($val['message']);

					$list[$key]['user_name'] = $recipient_user['user_name'];
					$list[$key]['url_token'] = $recipient_user['url_token'];
				}
			}
		}

		$user_key = array( 'uid', 'user_name', 'name', 'avatar_file', 'namecard_pic', 'signature' );

		if( !empty( $recipient_user ) )
		{
			foreach ($recipient_user as $k => $v) 
			{
				 if( !in_array($k, $user_key) ) unset( $recipient_user[$k] );
			}
		}

		$recipient_user['avatar_file'] = get_avatar_url($recipient_user['uid'],'max');

		H::ajax_json_output(AWS_APP::RSM(array(
            'recipient_user' => $recipient_user,
            'rows' => $list
        ), 1, null));

	}



	public function send_action()
	{
		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入私信内容')));
		}

		if (!$recipient_user = $this->model('account')->get_user_info_by_username($_POST['recipient']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('接收私信的用户不存在')));
		}

		if ($recipient_user['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能给自己发私信')));
		}

		if ($recipient_user['inbox_recv'])
		{
			if (! $this->model('message')->check_permission($recipient_user['uid'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了只有 Ta 关注的人才能给 Ta 发送私信')));
			}
		}

		$this->model('message')->send_message($this->user_id, $recipient_user['uid'], $_POST['message']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}