<?php
include "ci_db.php";
$get = $_POST;

if( empty( $get['uid'] ) || empty( $get['user_name'] ) ) exit_error('参数不完整');

//查总数
$total_rows = $db->query("SELECT COUNT(*) AS total_rows
						  FROM ".TABLE_PREFIX."users 
						  WHERE user_name = '".$get['user_name']."' AND uid <> ".$get['uid'])->row_array();
if( $total_rows['total_rows']  != 0 )  exit_error('用户名已经存在');

if( !empty( $get['signature'] ) ) $db->update( TABLE_PREFIX."users_attrib", array('signature'=>$get['signature']), array('uid'=>$get['uid']) );

unset($get['signature']);
$where = array('uid'=>$get['uid']);
unset($get['uid']);

$db->update( TABLE_PREFIX."users", $get, $where );

exit_success('success');