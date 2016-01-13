<?php
if (!defined('IN_ANWSION'))
{
	die;
}

class log extends AWS_CONTROLLER
{
	public function setup()
	{
		HTTP::no_cache_header();

		if(! $this->model('myapi')->verify_signature(get_class(),$_GET['mobile_sign']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('验签失败')));
		}
	}


	public function crash_action()
	{
		if (trim($_POST['content']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('参数不能为空')));
		}

		$this->model('myapi')->save_app_log($_POST['content']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}