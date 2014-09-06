<?php
include "ci_db.php";

$get = $_GET;
$per_page = 100;  //默认全部返回
if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['id'] ) ) exit_error('参数不完整');
if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'question_focus')->where(array('question_id '=>$get['id']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_success($total_rows);

//修正page
$get['page'] = ($get['page']<1)?1:$get['page'];
$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];

$offset = ($get['page']-1)*$per_page.','.$per_page;


$rows = $db->select("uid")->from(TABLE_PREFIX.'question_focus')->where(array('question_id'=>$get['id']))->order_by('add_time','DESC')->limit($per_page,$offset)->get()->result_array();
$str = array();
foreach( $rows as $k => $v ){
	$str[] = $v['uid'];
}
$str = implode(',',$str);

/*
$rows = $db->query("SELECT question_focus.uid,user_name,avatar_file,signature 
					FROM ".TABLE_PREFIX."question_focus AS question_focus LEFT JOIN ".TABLE_PREFIX.".users AS users ON question_focus.uid = users.uid
					LEFT JOIN ".TABLE_PREFIX."users_attrib AS attrib ON question_focus.uid = attrib.uid
					WHERE question_focus.question_id = '".$get['id']."' ORDER BY question_focus.add_time DESC LIMIT {$offset},{$per_page}")->result_array();
*/

$rows = $db->query("SELECT users.uid,user_name,avatar_file,signature 
					FROM ".TABLE_PREFIX."users AS users LEFT JOIN ".TABLE_PREFIX."users_attrib AS attrib ON users.uid = attrib.uid
					WHERE users.uid IN(".$str.")")->result_array();


//print_r( $db->_error_message() );

foreach ($rows as $k => $v) {
	$rows[$k]['avatar_file'] = @str_replace( 'min', 'max', $v['avatar_file'] );
}

//print_r( $db->_error_message() );

exit_success(array('total_rows'=>$total_rows['total_rows'],'rows'=>$rows));