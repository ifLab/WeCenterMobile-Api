<?php
include "ci_db.php";

$get = $_GET;
$per_page = 100;  //默认全部返回
if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['id'] ) ) exit_error('参数不完整');
if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'answer_comments')->where(array('answer_id '=>$get['id']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_success($total_rows);

//修正page
$get['page'] = ($get['page']<1)?1:$get['page'];
$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];

$offset = ($get['page']-1)*$per_page.','.$per_page;


$comment = $db->query("SELECT users.uid,user_name,message as content,time as add_time
					   FROM ".TABLE_PREFIX."answer_comments AS answer_comments LEFT JOIN ".TABLE_PREFIX."users AS users ON users.uid = answer_comments.uid
					   WHERE answer_comments.answer_id ='".$get['id']."'")->result_array();

//$answer = $db->select("question_id,answer_content,add_time,agree_count,uid,comment_count,has_attach")->from(TABLE_PREFIX.'answer_comments')->where(array('answer_id'=>$get['id']))->get()->row_array();
foreach( $comment as $k => $v ){
	if( preg_match('/@([0-9]+):/', $v['content'], $matches) ){
		$comment[$k]['content'] = str_replace($matches[0], '', $v['content']);
		$at_user_id = substr($matches[0], 1, -1);
		$rows = $db->select('uid,user_name')->from(TABLE_PREFIX.'users')->where(array('uid '=>$at_user_id))->get()->row_array();
		$comment[$k]['at_user'] = $rows;
	}

	//if( $v['content'] )
}
exit_success($comment);


