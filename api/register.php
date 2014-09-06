<?php
include "./lib/config.php";
include "./lib/class.MySQL.php";
include "./lib/functions.inc.php";

if( empty( $_POST['user_name'] ) || empty( $_POST['email'] ) || empty( $_POST['password'] ) ) exit( return_error( 'Invalid Parameters' ) );

$my = new MySQL( DB_HOST, DB_USER, DB_PWD, DB_NAME );
$table = TABLE_PREFIX.'users';
$ret = $my->Select( 'uid', $table, array('user_name'=>$_POST['user_name']) );
if( is_array($ret) ) exit( return_error( 'user_name already exists' ) );
$ret = $my->Select( 'uid', $table, array('email'=>$_POST['email']) );
if( is_array($ret) ) exit( return_error( 'email already exists' ) );

$salt = fetch_salt();
$rows = array(
	'user_name' => $_POST['user_name'],
	'email' => $_POST['email'],
	'salt' => $salt,
	'password' => compile_password( $_POST['password'], $salt ),
	'valid_email' => '1'
);
$ret = $my->Insert( $rows, $table );
if( !$ret ) exit( return_error( 'Insert Failed' ) ); 

$ret = $my->Select( 'uid', $table, array('user_name'=>$_POST['user_name']) );
exit( json_encode( array('error' => '', 'data'=>$ret ) ) );
