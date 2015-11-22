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
		if ((isset($_GET['perpage']) AND intval($_GET['perpage']) > 0))
		{
			$this->per_page = intval($_GET['perpage']);
		}

		$data = $this->model('actions')->get_user_actions($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", $_GET['actions'], $this->user_id);

		TPL::assign('list', $data);

		if (is_mobile())
		{
			$template_dir = 'm';
		}
		else
		{
			$template_dir = 'people';
		}

		if ($_GET['actions'] == '201')
		{
			TPL::output($template_dir . '/ajax/user_actions_questions_201');
		}
		else if ($_GET['actions'] == '101')
		{
			TPL::output($template_dir . '/ajax/user_actions_questions_101');
		}
		else
		{
			TPL::output($template_dir . '/ajax/user_actions');
		}
	}


    public function user_actions_by_where_action()
    {
        if ((isset($_GET['perpage']) AND intval($_GET['perpage']) > 0))
        {
            $this->per_page = intval($_GET['perpage']);
        }
        $associate_type = $_GET['type'];

        if(!in_array($associate_type,array(ACTION_LOG::CATEGORY_ARTICLE,
                                            ACTION_LOG::CATEGORY_QUESTION,
                                            ACTION_LOG::CATEGORY_ANSWER,
                                            ACTION_LOG::CATEGORY_NEWS)))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数有误')));
        }

        $associate_action = $_GET['actions'];

        if( empty( $_GET['page'] ) )  $_GET['page'] = 0;
				 
				if($data = $this->model('actions')->get_user_action_by_where($associate_type , $associate_action ,$_GET['uid'] ,(intval($_GET['page']) * $this->per_page) . ", {$this->per_page}"))
				{
		    	foreach ($data as $k => $v)
					{
						if( !empty( $v['info']['message'] ) AND ( strlen( $v['info']['message'] ) > 80 ) )
						{
							 $data[$k]['info']['message'] =  cjk_substr(strip_tags( $v['info']['message'] ),0,80,'utf-8').'...';
						}
					}
				}else
				{
					$data = null;
				}
					
        H::ajax_json_output(AWS_APP::RSM(array(
					'total_rows' => count( $data ),
					'rows' => $data
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

		

		$user_key = array( 'uid', 'user_name', 'name', 'avatar_file', 'namecard_pic', 'signature', 'reputation', 'agree_count' );

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
				'total_rows' => count( $users_list ),
				'rows' => $ret
		), 1, null));
	}

	public function topics_action()
	{
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
					$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']];
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
}