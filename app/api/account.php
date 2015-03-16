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
			'avatar_upload',
			'get_uid',
			'get_userinfo'
		);
		return $rule_action;
	}
	
	public function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function get_uid_action(){
		if( $this->user_id ){
			H::ajax_json_output(AWS_APP::RSM(array(
				'uid' => $this->user_id
			), 1, null));
		}else{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('禁止访问')));
		}
	}

	public function get_userinfo_action(){
		if( empty( $_GET['uid'] ) ) H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数不完整')));

		$user_info = $this->model('myapi')->get_user_info( $_GET['uid'] );

		if( empty( $user_info ) )  H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));

		$user_info_key = array('user_name','avatar_file','fans_count','friend_count','question_count','answer_count','topic_focus_count','agree_count','thanks_count');

		foreach ($user_info as $k => $v) {
			if( !in_array($k, $user_info_key) ) unset( $user_info['$k'] );
		}

		$answer_ids = $this->model('myapi')->get_answer_ids( $_GET['uid'] );

		$user_info['answer_favorite_count'] = 0;
		foreach( $answer_ids as $v ){
			$user_info['answer_favorite_count'] += $this->model('myapi')->get_answer_favorite_count( $v['answer_id'] );
		}

		$user_info['avatar_file'] = @str_replace( 'min', 'max', $user_info['avatar_file'] );

		$user_info['has_focus'] = 0;
		$user_info['signature'] = $this->model('myapi')->get_signature( $_GET['uid'] );


		if( !empty($this->user_id) ){
			$ret = $this->model('myapi')->has_focus($this->user_id,$_GET['uid']);
			if( !empty( $ret ) )  $user_info['has_focus'] = 1;
		}

		H::ajax_json_output(AWS_APP::RSM($user_info, 1, null));
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

		$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

		$this->model('active')->set_user_email_valid_by_uid($uid); //auto active (mobile)  valid_email = 1
		$this->model('active')->active_user_by_uid($uid); //auto active (mobile)  group_id = 4
		
		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'uid' => $user_info['uid']
		), 1, null));
	}
	
	public function login_process_action()
	{		
		$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
		
		if (!$user_info)
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

			$expire = 60 * 60 * 24 * 360; //auto login

			$this->model('account')->update_user_last_login($user_info['uid']);
			$this->model('account')->setcookie_logout();
			
			$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);
			
			$user_info['avatar_file'] = @str_replace( 'min', 'max', $user_info['avatar_file'] );	
			H::ajax_json_output(AWS_APP::RSM(array(
				'uid' => $user_info['uid'],
				'user_name' => $user_info['user_name'],
				'avatar_file' => $user_info['avatar_file']
			), 1, null));
		}
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

		
}
