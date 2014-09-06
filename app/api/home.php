<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class home extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array(
			'index'
		);
		
		return $rule_action;
	}

	public function index_action()
	{		
		if (! $this->user_id)
		{
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请先登录或注册')));
		}

		$this->per_page = 20;
		if( !empty( $_GET['per_page'] ) )  $this->per_page = intval( $_GET['per_page'] );


		$data = $this->model('myhome')->home_activity($this->user_id, (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}");
		

		if (!is_array($data))
		{
			$data = array();
		}

		//$new_data = array_reverse( $data, true );
		
		H::ajax_json_output(AWS_APP::RSM(array(
					'total_rows' => count( $data ),
					'rows' => $data
			), 1, null));
	}
	
}