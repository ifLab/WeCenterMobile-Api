<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class account extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; 
		$rule_action['actions'] = array(
			'register_process',
			'login_process',
			//'avatar_upload',
			'get_uid',
			'get_userinfo',
			'logout'
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

	public function get_uid_action()
	{
		if( $this->user_id )
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'uid' => $this->user_id
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先登录')));
		}
	}

	public function login_process_action()
	{		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (!$user_info = $this->model('ucenter')->login($_POST['user_name'], $_POST['password']))
			{
				$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
		}

		if (! $user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
		else
		{
			if ($user_info['forbidden'] == 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
			}

			if (get_setting('site_close') == 'Y' AND $user_info['group_id'] != 1)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, get_setting('close_notice')));
			}

			if (get_setting('register_valid_type') == 'approval' AND $user_info['group_id'] == 3)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站点会员注册需要经过管理员审核,请耐心等待')));
			}
			else
			{
				//if ($_POST['net_auto_login'])
				//{
					$expire = 60 * 60 * 24 * 360;
				//}

				$this->model('account')->update_user_last_login($user_info['uid']);
				$this->model('account')->logout();

				$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);

				$valid_email = 1;
				$is_first_login = 0;
				if (get_setting('register_valid_type') == 'email' AND !$user_info['valid_email'])
				{
					$valid_email = 0;
				}
				else if ($user_info['is_first_login'] AND !$_POST['_is_mobile'])
				{
					$is_first_login = 1;
				}
				
				$user_info['avatar_file'] = get_avatar_url($user_info['uid'],'max');

				H::ajax_json_output(AWS_APP::RSM(array(
					'uid' => $user_info['uid'],
					'user_name' => $user_info['user_name'],
					'avatar_file' => $user_info['avatar_file'],
					'valid_email' => $valid_email,
					'is_first_login' => $is_first_login
				), 1, null));
			}
		}
	}


	public function register_process_action()
	{	
		if (get_setting('register_type') == 'close')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站目前关闭注册')));
		}
		else if (get_setting('register_type') == 'invite' AND !$_POST['icode'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过邀请注册')));
		}
		else if (get_setting('register_type') == 'weixin')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('本站只能通过微信注册')));
		}

		if ($_POST['icode'])
		{
			if (!$invitation = $this->model('invitation')->check_code_available($_POST['icode']) AND $_POST['email'] == $invitation['invitation_email'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('邀请码无效或与邀请邮箱不一致')));
			}
		}

		if (trim($_POST['user_name']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入用户名')));
		}
		else if ($this->model('account')->check_username($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
		}
		else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名包含无效字符')));
		}
		else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']) OR trim($_POST['user_name']) != $_POST['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名中包含敏感词或系统保留字')));
		}

		if ($this->model('account')->check_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('E-Mail 已经被使用, 或格式不正确')));
		}

		if (strlen($_POST['password']) < 6 OR strlen($_POST['password']) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		// if (! $_POST['agreement_chk'])
		// {
		// 	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你必需同意用户协议才能继续')));
		// }

		// 检查验证码
		// if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']) AND get_setting('register_seccode') == 'Y')
		// {
		// 	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
		// }

		if (get_setting('ucenter_enabled') == 'Y')
		{
			$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email']);

			if (is_array($result))
			{
				$uid = $result['user_info']['uid'];
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
			}
		}
		else
		{
			$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);
		}

		if ($_POST['email'] == $invitation['invitation_email'])
		{
			$this->model('active')->set_user_email_valid_by_uid($uid);

			$this->model('active')->active_user_by_uid($uid);
		}

		$this->model('account')->setcookie_logout();
		$this->model('account')->setsession_logout();

		if ($_POST['icode'])
		{
			$follow_users = $this->model('invitation')->get_invitation_by_code($_POST['icode']);
		}
		else if (HTTP::get_cookie('fromuid'))
		{
			$follow_users = $this->model('account')->get_user_info_by_uid(HTTP::get_cookie('fromuid'));
		}

		if ($follow_users['uid'])
		{
			$this->model('follow')->user_follow_add($uid, $follow_users['uid']);
			$this->model('follow')->user_follow_add($follow_users['uid'], $uid);

			$this->model('integral')->process($follow_users['uid'], 'INVITE', get_setting('integral_system_config_invite'), '邀请注册: ' . $_POST['user_name'], $follow_users['uid']);
		}

		if ($_POST['icode'])
		{
			$this->model('invitation')->invitation_code_active($_POST['icode'], time(), fetch_ip(), $uid);
		}

		if (get_setting('register_valid_type') == 'N' OR (get_setting('register_valid_type') == 'email' AND get_setting('register_type') == 'invite'))
		{
			$this->model('active')->active_user_by_uid($uid);
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (get_setting('register_valid_type') == 'N' OR $user_info['group_id'] != 3 OR $_POST['email'] == $invitation['invitation_email'])
		{
			$valid_email = 1;
		}
		else
		{
			AWS_APP::session()->valid_email = $user_info['email'];
			$this->model('active')->new_valid_email($uid);
			$valid_email = 0;
		}

		$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);

		H::ajax_json_output(AWS_APP::RSM(array(
					'uid' => $user_info['uid'],
					'user_name' => $user_info['user_name'],
					'valid_email' => $valid_email
				), 1, null));
	}


	/* For iOS 2015.3.27. */  /* 2015.11.29 废弃 */
	public function get_avatars_action()
	{
		if( empty($_POST['uids']) )
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数不完整')));	
		}
		 
		$uid_arr = array_filter( array_unique( explode('_',$_POST['uids']) ) );
		
		if( empty($uid_arr) ) 
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数不正确')));
		} 
		else
		{
			$data = array();
			foreach ($uid_arr as $k => $v)
			{
				$data[$v] = get_avatar_url($v,'min');
			}

			H::ajax_json_output(AWS_APP::RSM($data, 1, null));
		}
	}


	public function get_userinfo_action()
	{
		if(!isset($_GET['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数不完整')));
		} 
			
		if(!$user_info = $this->model('account')->get_user_info_by_uid($_GET['uid'],true))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
		}

		$this->model('people')->update_views($user_info['uid']);

        $user_info_key = array('uid','user_name','sex','province','city','avatar_file','fans_count','friend_count','article_count','question_count','answer_count','topic_focus_count','agree_count','thanks_count','views_count','signature');

        foreach($user_info as $k => $v) 
        {
            if(!in_array($k, $user_info_key)) unset($user_info[$k]);
        }

        $answer_ids = $this->model('myapi')->get_answer_ids($user_info['uid']);

        $user_info['answer_favorite_count'] = 0;
        foreach( $answer_ids as $v )
        {
            $user_info['answer_favorite_count'] += $this->model('myapi')->get_answer_favorite_count($v['answer_id']);
        }

        $user_info['avatar_file'] = get_avatar_url($user_info['uid'],'max');

		//2015.5.17  3.1.2新版中用户数据表中新增了该字段	
		if(!isset($user_info['article_count']))
		{
			$user_info['article_count'] = $this->model('myapi')->get_user_article_count($user_info['uid']);
		}

        $user_info['has_focus'] = 0;
       
        if($this->user_id AND $this->model('follow')->user_follow_check($this->user_id,$user_info['uid']))
        {
            $user_info['has_focus'] = 1;
        }
	
		H::ajax_json_output(AWS_APP::RSM($user_info, 1, null));
	}

	
	function avatar_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, '', 1),
			'is_image' => TRUE,
			'max_size' => get_setting('upload_avatar_size_limit'),
			'file_name' => $this->model('account')->get_avatar($this->user_id, '', 2),
			'encrypt_name' => FALSE
		))->do_upload('user_avatar');
		
		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				default:
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));
				break;
				
				case 'upload_invalid_filetype':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件类型无效')));
				break;	
				
				case 'upload_invalid_filesize':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文件尺寸过大, 最大允许尺寸为 %s KB', get_setting('upload_size_limit'))));
				break;
			}
		}
		
		if (! $upload_data = AWS_APP::upload()->data())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
		}
		
		if ($upload_data['is_image'] == 1)
		{
			foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
			{			
				$thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($this->user_id, $key, 2);
				
				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();	
			}
		}
		
		$update_data['avatar_file'] = $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['min']);
		
		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);
		
		if (!$this->model('integral')->fetch_log($this->user_id, 'UPLOAD_AVATAR'))
		{
			$this->model('integral')->process($this->user_id, 'UPLOAD_AVATAR', round((get_setting('integral_system_config_profile') * 0.2)), '上传头像');
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'preview' => get_setting('upload_url') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['max'])
		), 1, null));
	}	

	public function logout_action()
	{
        $this->model('account')->setcookie_logout();	// 清除 COOKIE
        $this->model('account')->setsession_logout();	// 清除 Session

        $this->model('admin')->admin_logout();

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function get_jobs_list_action()
	{
		$jobs_list = $this->model('work')->get_jobs_list();

		H::ajax_json_output(AWS_APP::RSM(array(
			'jobs_list' => $jobs_list
		), 1, null));
	}

	public function modify_password_action()
	{
		if (!$_POST['old_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入当前密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入相同的确认密码')));
		}

		if (strlen($_POST['password']) < 6 OR strlen($_POST['password']) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if ($this->model('account')->update_user_password($_POST['old_password'], $_POST['password'], $this->user_id, $this->user_info['salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('密码修改成功, 请牢记新密码')));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的当前密码')));
		}
	}
}
