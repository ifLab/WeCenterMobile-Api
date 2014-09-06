<?php
include "ci_db.php";
$get = $_GET;

if( empty( $get['uid'] ) ) exit_error('参数不完整');

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'users')->where(array('uid'=>$get['uid']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_error('用户不存在');



$rows = $db->select("user_name,avatar_file,fans_count,friend_count,question_count,answer_count,topic_focus_count,agree_count,thanks_count")->from(TABLE_PREFIX.'users')->where(array('uid'=>$get['uid']))->get()->row_array();

$answer_id = $db->select('answer_id')->from(TABLE_PREFIX.'answer')->where(array('uid'=>$get['uid']))->get()->result_array();

$rows['answer_favorite_count'] = 0;
foreach( $answer_id as $v ){
	$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'favorite')->where(array('answer_id'=>$v['answer_id']))->get()->row_array();
	$rows['answer_favorite_count'] += $total_rows['total_rows'];
}
$rows['answer_favorite_count'] = (string)$rows['answer_favorite_count'];
$rows['avatar_file'] = @str_replace( 'min', 'max', $rows['avatar_file'] );

exit_success($rows);