<?php
include "ci_db.php";
$get = $_GET;

if( empty( $get['uid'] ) ) exit_error('参数不完整');

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'users')->where(array('uid'=>$get['uid']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_error('用户不存在');


$rows = $db->query("SELECT users.uid,user_name,sex,birthday,job_id,signature 
					FROM ".TABLE_PREFIX."users AS users LEFT JOIN ".TABLE_PREFIX."users_attrib AS attrib ON users.uid = attrib.uid
					WHERE users.uid = ".$get['uid'])->result_array();

exit_success($rows);