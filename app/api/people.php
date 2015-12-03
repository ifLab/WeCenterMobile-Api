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

class people extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		$rule_action['actions'] = array(
			'index',
			'user_info',
			'user_actions_by_where'
		);

		return $rule_action;
	}

	public function setup()
	{
		$this->per_page = get_setting('contents_per_page');

		HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}


	//个人主页 获取用户信息
	public function index_action()
	{
        $user = $this->model('account')->get_user_info_by_uid($_GET['uid'], TRUE);

        if(!$user)
        {
        	H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户不存在')));
        }

        $this->model('people')->update_views($user['uid']);

        //$user['job_id'] = get_dict('工作职位',$user['job_id']);

        //教育经历
		$education_experience_list = $this->model('education')->get_education_experience_list($user['uid']);


        //工作经历
        
		$jobs_list = $this->model('work')->get_jobs_list();

		if ($work_experience_list = $this->model('work')->get_work_experience_list($user['uid']))
		{
			foreach ($work_experience_list as $key => $val)
			{
				$work_experience_list[$key]['job_name'] = $jobs_list[$val['job_id']];
			}
		}
		


		if( $this->model('follow')->user_follow_check($this->user_id, $user['uid']) )
		{
			$user['has_follow'] = 1;
		}

		//擅长话题
		//$reputation_topics = $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 6);

		/*
		$fans_list = $this->model('follow')->get_user_fans($user['uid'], 5);
		$friends_list = $this->model('follow')->get_user_friends($user['uid'], 5);
		$focus_topics = $this->model('topic')->get_focus_topic_list($user['uid'], 5);
		*/
		
		//clean
		if( !empty( $user ) )
		{
			$user_key = array( 'uid', 'user_name', 'name', 'company', 'work', 'avatar_file', 'sex', 'job_id', 'fans_count', 'friend_count', 'invite_count', 'question_count', 'news_count', 'article_count', 'answer_count', 'topic_focus_count', 'agree_count', 'thanks_count', 'reputation', 'draft_count', 'yue_count', 'province', 'signature', 'city' );
 			if($this->user_id == $user['uid'])
			{
				$user_key[] = 'mobile'; 
			}

			foreach ($user as $k => $v) 
			{
				if( !in_array($k, $user_key) ) unset( $user[$k] );
			}
 
		}

		//当前用户是否已关注该用户
		$user['has_focus'] = 0;

		if( $this->user_id AND ( $this->user_id != $user['uid'] ) )
		{
			//判断是否存在关注
			if ($this->model('follow')->user_follow_check($this->user_id, $user['uid']))
			{
				$user['has_focus'] = 1;
			}

			unset($user['mobile']);
		}
		
		if( !$this->user_id )
		{
			unset($user['mobile']);
		}
		

		$user['avatar_file'] = get_avatar_url( $user['uid'], 'max' );

		H::ajax_json_output(AWS_APP::RSM(array(
                'user_info' => $user,
                'education_experience_list' => $education_experience_list,
                'work_experience_list' => $work_experience_list
            ), 1, null));
	}



	public function user_actions_action()
	{
		if(! $_GET['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数错误')));
		}

		$_GET['page'] = $_GET['page'] ? ($_GET['page']-1) : 0;

		if ((isset($_GET['per_page']) AND intval($_GET['per_page']) > 0))
		{
			$this->per_page = intval($_GET['per_page']);
		}

		$data = $this->model('actions')->get_user_actions($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", $_GET['actions'], $this->user_id);

		if (!is_array($data))
		{
			$data = array();
		}
		else
		{
			$data_key = array( 'history_id', 'associate_action', 'answer_info', 'question_info', 'article_info', 'add_time' );
			$article_info_key = array( 'id', 'title', 'message', 'comments', 'views', 'add_time' );
			$answer_info_key = array( 'answer_id', 'answer_content', 'add_time', 'against_count', 'agree_count' );
			$question_info_key = array( 'question_id', 'question_content', 'add_time', 'update_time', 'answer_count', 'agree_count' );

			foreach ($data as $key => $val)
			{
				foreach ($val as $k => $v)
				{
					if(!in_array($k, $data_key)) unset($data[$key][$k]);
				}

				if($val['article_info']) 
				{
					foreach ($val['article_info'] as $k => $v)
					{
						if(!in_array($k, $article_info_key)) unset($data[$key]['article_info'][$k]);
					}
				}

				if($val['answer_info']) 
				{
					foreach ($val['answer_info'] as $k => $v)
					{
						if(!in_array($k, $answer_info_key)) unset($data[$key]['answer_info'][$k]);
					}
				}

				if($val['question_info']) 
				{
					foreach ($val['question_info'] as $k => $v)
					{
						if(!in_array($k, $question_info_key)) unset($data[$key]['question_info'][$k]);
					}
				}
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
					'total_rows' => count($data),
					'rows' => array_values($data)
			), 1, null));	
	}

    public function user_info_action()
	{
		if ($this->user_id == $_GET['uid'])
		{
			$user_info = $this->user_info;
		}
		else if (!$user_info = $this->model('account')->get_user_info_by_uid($_GET['uid'], ture))
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}

		if ($this->user_id != $user_info['uid'])
		{
			$user_follow_check = $this->model('follow')->user_follow_check($this->user_id, $user_info['uid']);
		}

		H::ajax_json_output(array(
			'reputation' => $user_info['reputation'],
			'agree_count' => $user_info['agree_count'],
			'thanks_count' => $user_info['thanks_count'],
			'type' => 'people',
			'uid' => $user_info['uid'],
			'user_name' => $user_info['user_name'],
			'avatar_file' => get_avatar_url($user_info['uid'], 'mid'),
			'signature' => $user_info['signature'],
			'focus' => ($user_follow_check ? true : false),
			'is_me' => (($this->user_id == $user_info['uid']) ? true : false),
			'url' => get_js_url('/people/' . $user_info['url_token']),
			'category_enable' => ((get_setting('category_enable') == 'Y') ? 1 : 0),
			'verified' => $user_info['verified'],
			'fans_count' => $user_info['fans_count']
		));
	}

	public function follows_action()
	{
		$_GET['page'] = $_GET['page'] ? ($_GET['page']-1) : 0;
		
		switch ($_GET['type'])
		{
			case 'follows':
				$users_list = $this->model('follow')->get_user_friends($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;

			case 'fans':
				$users_list = $this->model('follow')->get_user_fans($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}");
			break;
		}

		if ($users_list AND $this->user_id)
		{
			foreach ($users_list as $key => $val)
			{
				$users_ids[] = $val['uid'];
			}

			if ($users_ids)
			{
				$follow_checks = $this->model('follow')->users_follow_check($this->user_id, $users_ids);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['follow_check'] = $follow_checks[$val['uid']];
				}
			}
		}

		

		$user_key = array( 'uid', 'user_name', 'signature', 'reputation', 'agree_count', 'thanks_count' );

		if( !empty( $users_list ) )
		{
			foreach ($users_list as $key => $value) 
			{
				foreach ($value as $k => $v) {
					if( !in_array($k, $user_key) ) unset( $value[$k] );
				}
                $value['avatar_file'] = get_avatar_url($value['uid'],'mid');
                $ret[] = $value;
			}
		}

	   	H::ajax_json_output(AWS_APP::RSM(array(
				'total_rows' => count( $ret ),
				'rows' => $ret
		), 1, null));
	}

	public function topics_action()
	{
		$_GET['page'] = $_GET['page'] ? ($_GET['page']-1) : 0;
		
		if(! $_GET['uid'])
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数有误')));
		}

		if ($topic_list = $this->model('topic')->get_focus_topic_list($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}") AND $this->user_id)
		{
			$topic_ids = array();

			foreach ($topic_list as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}

			if ($topic_ids)
			{
				$topic_focus = $this->model('topic')->has_focus_topics($this->user_id, $topic_ids);

				foreach ($topic_list as $key => $val)
				{
					$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']] ? 1 : 0;
				
					if($val['topic_pic'])
					{
						$topic_list[$key]['topic_pic'] = get_setting('upload_url').'/topic/'.$val['topic_pic'];
					}
				}
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
				'total_rows' => count( $topic_list ),
				'rows' => $topic_list
		), 1, null));
	}




    public function favorite_action()
    {
        if ($action_list = $this->model('favorite')->get_item_list($_GET['tag'], $this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
        {
            foreach ($action_list AS $key => $val)
            {
                $item_ids[] = $val['item_id'];
            }
        }
        else
        {
            if (!$_GET['page'] OR $_GET['page'] == 1)
            {
                $this->model('favorite')->remove_favorite_tag(null, null, $_GET['tag'], $this->user_id);
            }
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'total_rows' => count( $action_list ),
            'rows' => $action_list
        ), 1, null));
    }


    public function profile_setting_action()
	{
		if (!$this->user_info['user_name'] OR $this->user_info['user_name'] == $this->user_info['email'] AND $_POST['user_name'])
		{
			$update_data['user_name'] = htmlspecialchars(trim($_POST['user_name']));

			if ($check_result = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', $check_result));
			}
		}

		if ($_POST['url_token'] AND $_POST['url_token'] != $this->user_info['url_token'])
		{
			if ($this->user_info['url_token_update'] AND $this->user_info['url_token_update'] > (time() - 3600 * 24 * 30))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你距离上次修改个性网址未满 30 天')));
			}

			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址只允许输入英文或数字')));
			}

			if ($this->model('account')->check_url_token($_POST['url_token'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址已经被占用请更换一个')));
			}

			if (preg_match("/^[\d]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('个性网址不允许为纯数字')));
			}

			$this->model('account')->update_url_token($_POST['url_token'], $this->user_id);
		}

		if ($update_data['user_name'] and $this->model('account')->check_username($update_data['user_name']) and $this->user_info['user_name'] != $update_data['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的姓名, 请重新填写')));
		}

		if (! H::valid_email($this->user_info['email']))
		{
			if (! H::valid_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的 E-Mail 地址')));
			}

			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('邮箱已经存在, 请使用新的邮箱')));
			}

			$update_data['email'] = $_POST['email'];

			$this->model('active')->new_valid_email($this->user_id, $_POST['email']);
		}

		if ($_POST['common_email'])
		{
			if (! H::valid_email($_POST['common_email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的常用邮箱地址')));
			}

			$update_data['common_email'] = $_POST['common_email'];
		}

		$update_data['sex'] = intval($_POST['sex']);

		$update_data['province'] = htmlspecialchars($_POST['province']);

		$update_data['city'] = htmlspecialchars($_POST['city']);

		if ($_POST['birthday_y'])
		{
			$update_data['birthday'] = intval(strtotime(intval($_POST['birthday_y']) . '-' . intval($_POST['birthday_m']) . '-' . intval($_POST['birthday_d'])));
		}

		if (!$this->user_info['verified'])
		{
			$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);
		}

		$update_data['job_id'] = intval($_POST['job_id']);

		if ($_POST['signature'] AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_SIGNATURE'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_SIGNATURE', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善一句话介绍'));
		}

		$update_attrib_data['qq'] = htmlspecialchars($_POST['qq']);
		$update_attrib_data['homepage'] = htmlspecialchars($_POST['homepage']);
		$update_data['mobile'] = htmlspecialchars($_POST['mobile']);

		if (($update_attrib_data['qq'] OR $update_attrib_data['homepage'] OR $update_data['mobile']) AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_CONTACT'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_CONTACT', round((get_setting('integral_system_config_profile') * 0.1)), AWS_APP::lang()->_t('完善联系资料'));
		}

		if (get_setting('auto_create_social_topics') == 'Y')
		{
			if ($_POST['city'])
			{
				$this->model('topic')->save_topic($_POST['city']);
			}

			if ($_POST['province'])
			{
				$this->model('topic')->save_topic($_POST['province']);
			}
		}

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);

		// 更新从表
		$this->model('account')->update_users_attrib_fields($update_attrib_data, $this->user_id);

		//$this->model('account')->set_default_timezone($_POST['default_timezone'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(AWS_APP::lang()->_t('个人资料保存成功'), 1, null));
	}
}