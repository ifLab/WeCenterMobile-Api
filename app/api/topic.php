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
		
		
		$rule_action['actions'][] = 'square';
		$rule_action['actions'][] = 'topic';
		$rule_action['actions'][] = 'topic_best_answer';
		$rule_action['actions'][] = 'get_hot_topics';
		$rule_action['actions'][] = 'hot_topics';
	
		
		return $rule_action;
	}


	public function setup()
	{
		//HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}


	public function get_hot_topics_action()
	{		
		$ret = $this->model('topic')->get_hot_topics();
		
		H::ajax_json_output(AWS_APP::RSM($ret, 1, null));
	}


	public function hot_topics_action()
	{
		switch ($_GET['day'])
		{
			case 'month':
				$order = 'discuss_count_last_month DESC';
			break;

			case 'week':
				$order = 'discuss_count_last_week DESC';
			break;

			default:
				$order = 'discuss_count DESC';
			break;
		}

		$cache_key = 'square_hot_topic_list' . md5($order) . '_' . intval($_GET['page']);

		if (!$topics_list = AWS_APP::cache()->get($cache_key))
		{
			if ($topics_list = $this->model('topic')->get_topic_list(null, $order, 20, $_GET['page']))
			{
				$topics_list_total_rows = $this->model('topic')->found_rows();

				AWS_APP::cache()->set('square_hot_topic_list_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
			}

			AWS_APP::cache()->set($cache_key, $topics_list, get_setting('cache_level_low'));
		}
		else
		{
			$topics_list_total_rows = AWS_APP::cache()->get('square_hot_topic_list_total_rows');
		}

		if($topics_list)
		{
			foreach ($topics_list as $key => $val)
			{
				if($val['topic_pic'])
				{
					$topics_list[$key]['topic_pic'] = get_setting('upload_url').'/topic/'.$val['topic_pic'];
				}
			}
		}

		H::ajax_json_output(AWS_APP::RSM(array(
				'total_rows' => count( $topics_list ),
				'rows' => $topics_list
		), 1, null));
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
				$topic_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']);
				$topic_info['merged_tip'] = "您查看的话题已被合并到当前话题";
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}
		
		//此话题的最佳回答者
		//TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users_by_topic_id($topic_info['topic_id'], 5));
		
		$topic_info['has_focus'] = 0;

		if ($this->user_id AND $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']))
		{
			$topic_info['has_focus'] = 1;
		}

		if($topic_info['topic_pic'])
		{
			$topic_info['topic_pic'] = get_setting('upload_url').'/topic/'.$topic_info['topic_pic'];
		}
		
		$topic_info['topic_description'] = nl2br(FORMAT::parse_markdown($topic_info['topic_description']));

		H::ajax_json_output(AWS_APP::RSM($topic_info, 1, null));
	}

	//获取多个话题信息
	public function topics_action()
	{	
		if(!is_array($_POST['topics'])) 
		{
			$_POST['topics'] = explode(',',$_POST['topics']); //英文逗号隔开
		}

		if(!$_POST['topics'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数错误')));
		}
			
		if (!$topic_info = $this->model('topic')->get_topics_by_ids($_POST['topics']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('话题不存在')));
		}

		foreach ($topic_info as $key => $val)
		{
			if($val['topic_pic'])
			{
				$topic_info[$key]['topic_pic'] = get_setting('upload_url').'/topic/'.$val['topic_pic'];
			}

			$topic_info[$key]['has_focus'] = 0;

			if ($this->user_id AND $this->model('topic')->has_focus_topic($this->user_id, $val['topic_id']))
			{
				$topic_info[$key]['has_focus'] = 1;
			}

			$topic_info[$key]['topic_description'] = nl2br(FORMAT::parse_markdown($val['topic_description']));
		}
	
		H::ajax_json_output(AWS_APP::RSM(array_values($topic_info), 1, null));
	}


	public function topic_best_answer_list_action()
	{
		if(! $_GET['topic_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('参数错误')));
		}
		
		$_GET['page'] = $_GET['page'] ? ($_GET['page']-1) : 0;
		
		$action_list = $this->model('topic')->get_topic_best_answer_action_list(intval($_GET['topic_id']), $this->user_id, intval($_GET['page']) * get_setting('contents_per_page') . ', ' . get_setting('contents_per_page'));
			
		$question_info_key = array('question_id','question_content');
		$answer_info_key = array('answer_id','answer_content','add_time','against_count','agree_count','comment_count','thanks_count','agree_status');

		if($action_list)
		{
			foreach ($action_list as $key => $val)
			{
				foreach ($val as $kk => $vv)
				{
					if(! in_array($kk, array('question_info','user_info','answer_info'))) unset($action_list[$key][$kk]);

					if($kk == 'question_info')
					{
						foreach ($vv as $k => $v) 
						{
							if(!in_array($k, $question_info_key)) unset($action_list[$key][$kk][$k]);
						}
					}

					if($kk == 'user_info')
					{
						$action_list[$key][$kk] = $this->model('myapi')->get_clean_user_info($vv);
					}

					if($kk == 'answer_info')
					{
						foreach ($vv as $k => $v) 
						{
							if(!in_array($k, $answer_info_key)) unset($action_list[$key][$kk][$k]);
						}

						$vv['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($vv['answer_content']))));
						$action_list[$key][$kk]['answer_content']  = cjk_substr(trim(strip_tags($vv['answer_content'])),0,100);

					}
				}
			}
		}
		else
		{
			 $action_list = null;
		}

		H::ajax_json_output(AWS_APP::RSM(array(
					'total_rows' => count($action_list),
					'rows' => array_values($action_list)
				), 1, null));
	}

}