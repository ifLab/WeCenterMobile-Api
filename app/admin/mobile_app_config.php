<?php
/*
+--------------------------------------------------------------------------
|   
| By Hwei. 2015-11-01.
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class mobile_app_config extends AWS_ADMIN_CONTROLLER
{

	public function setup()
	{
		$admin_menu = (array)AWS_APP::config()->get('admin_menu');

        $admin_menu['mobile_app_config']['select'] = true;

		TPL::assign('menu_list', $admin_menu);
	}

	//配置信息
	public function index_action()
	{
		if($_POST['mobile_app_secret'])
		{
			$this->model('myapi')->save_mobile_app_secret(trim($_POST['mobile_app_secret']));

			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('保存设置成功')));
		}

		TPL::output('admin/mobile_app_config');
	}	

	
}