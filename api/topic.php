<?php
include "ci_db.php";

$get = $_GET;
//$per_page = 10;
//if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['uid'] ) || empty( $get['topic_id'] ) ) exit_error('参数不完整');

//if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
//$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'topic')->where(array('topic_id'=>$get['topic_id']))->get()->row_array();
//if( $total_rows['total_rows']  == 0 )  exit_success('话题不存在');

//修正page
//$get['page'] = ($get['page']<1)?1:$get['page'];
//$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];

//$offset = ($get['page']-1)*$per_page.','.$per_page;

$rows = $db->select("topic_title,topic_description,topic_pic,focus_count")->from(TABLE_PREFIX.'topic')->where(array('topic_id'=>$get['topic_id']))->get()->row_array();
if( !$rows )  exit_success('话题不存在');

$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'topic_focus')->where(array('topic_id'=>$get['topic_id'],'uid'=>$get['uid']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  $rows['has_focus'] = 0;
else $rows['has_focus'] = 1;



$rows['topic_pic'] = @str_replace( '_32_32', '_100_100', $rows['topic_pic'] );


exit_success($rows);