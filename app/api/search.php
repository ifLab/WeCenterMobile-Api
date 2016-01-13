<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class search extends AWS_CONTROLLER
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
	}
	
	public function index_action()
	{
		$_GET['per_page'] = $_GET['per_page'] ? intval($_GET['per_page']) : get_setting('contents_per_page');

		$result = $this->model('search')->search(cjk_substr($_GET['q'], 0, 64), $_GET['type'], $_GET['page'], $_GET['per_page'], $_GET['topic_ids'], $_GET['is_recommend']);

		if (!$result)
		{
			$result = array();
		}

		if ($_GET['is_question_id'] AND is_digits($_GET['q']))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['q']);

			if ($question_info)
			{
				$result[] = $this->model('search')->prase_result_info($question_info);
			}
		}

		if($result)
		{
			$key_arr = array('type','search_id','name','detail');

			foreach($result as $key => $val)
			{
				foreach ($val as $k => $v)
				{
					if(!in_array($k, $key_arr)) unset($result[$key][$k]);
				}
			}
		}

		
		H::ajax_json_output(AWS_APP::RSM(array(
								'total_rows' => count($result),
								'rows' => $result
						), 1, null));
	}
}
