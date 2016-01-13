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

		TPL::output('admin/app/mobile_app_config');
	}	


	//Crash Log 列表
	public function app_log_action()
	{
		$list = $this->model('myapi')->get_app_log_list('', 'id DESC', $this->per_page, $_GET['page']);

		$total_rows = $this->model('myapi')->found_rows();

		$url_param = array();

		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/admin/app_log/app_log/') . implode('__', $url_param),
			'total_rows' => $total_rows,
			'per_page' => $this->per_page
		))->create_links());

		TPL::assign('total_rows', $total_rows);
		TPL::assign('list', $list);

		TPL::output('admin/app/app_log');
	}	

}