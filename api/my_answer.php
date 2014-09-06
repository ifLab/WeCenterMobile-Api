<?php
include "ci_db.php";

$get = $_GET;
$per_page = 10;
if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['uid'] ) ) exit_error('参数不完整');
if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'answer')->where(array('uid'=>$get['uid']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_success($total_rows);

//修正page
$get['page'] = ($get['page']<1)?1:$get['page'];
$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];


$offset = ($get['page']-1)*$per_page.','.$per_page;

$rows = $db->select("answer_id,question_id,answer_content,agree_count")->from(TABLE_PREFIX.'answer')->where(array('uid'=>$get['uid']))->order_by('add_time','DESC')->limit($per_page,$offset)->get()->result_array();

$avatar = $db->select('avatar_file')->from(TABLE_PREFIX.'users')->where(array('uid'=>$get['uid']))->get()->row_array();
if( !empty( $avatar['avatar_file'] ) ) $avatar['avatar_file'] = @str_replace( 'min', 'max', $avatar['avatar_file'] );

foreach( $rows as $k => $v ){
	$rows[$k]['avatar_file'] = $avatar['avatar_file'];
	$rows[$k]['question_title'] = '';
	$question = $db->select("question_content as question_title")->from(TABLE_PREFIX.'question')->where(array('question_id'=>$v['question_id']))->get()->row_array();
	if( !empty( $question ) ) $rows[$k]['question_title'] = $question['question_title'];
}

exit_success(array('total_rows'=>$total_rows['total_rows'],'rows'=>$rows));