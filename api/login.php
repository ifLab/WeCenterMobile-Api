<?php
include "./lib/config.php";
include "./lib/class.MySQL.php";
include "./lib/functions.inc.php";

if( empty( $_POST['user_name'] ) || empty( $_POST['password'] ) ) exit( return_error( 'Invalid Parameters' ) );

$my = new MySQL( DB_HOST, DB_USER, DB_PWD, DB_NAME );
$table = TABLE_PREFIX.'users';
$ret = $my->Select( 'uid,email,password,salt', $table, array('user_name'=>$_POST['user_name']) );
if( !is_array($ret) ) exit( return_error( 'user_name error' ) );

if( $ret['password'] != compile_password( $_POST['password'], $ret['salt'] ) ) exit( return_error( 'password error' ) ); 

$data = array( 'uid'=>$ret['uid'], 'email'=>$ret['email'] );
exit( json_encode( array( 'error' => '', 'data'=>$data ) ) );
