<?php
include "ci_db.php";
include "lib/Markdown.php"; 
include "lib/format.inc.php";

$get = $_GET;
//$per_page = 100;  //默认全部返回
//if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['id'] ) ) exit_error('参数不完整');
//if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'answer')->where(array('answer_id '=>$get['id']))->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_error('回答不存在');

//修正page
//$get['page'] = ($get['page']<1)?1:$get['page'];
//$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];

//$offset = ($get['page']-1)*$per_page.','.$per_page;


$answer = $db->select("question_id,answer_content,add_time,agree_count,uid,comment_count,has_attach")->from(TABLE_PREFIX.'answer')->where(array('answer_id'=>$get['id']))->get()->row_array();


$md = new Markdown();
$answer['answer_content'] = $md->transform( $answer['answer_content'] );

//如果存在附件
if( $answer['has_attach'] ){
	$rows = $db->select("id,add_time,file_location")->from(TABLE_PREFIX.'attach')->where(array('item_type'=>'answer','item_id'=>$get['id']))->get()->result_array();
	$answer_attach =array();
	foreach( $rows as $k => $v ){
		$answer_attach[$v['id']] = ANSWER_PIC.date('Ymd',$v['add_time']).'/'.$v['file_location'];
	}

	
	preg_match_all('/\[attach\]([0-9]+)\[\/attach]/', $answer['answer_content'], $matches);

	foreach( $matches[0] as $k => $v ){
		 $my_num = substr($v, 8, -9);
		 $my_replace = "<img src='".$answer_attach[$my_num]."'/>";
		 $answer['answer_content'] = str_replace($v, $my_replace, $answer['answer_content']);
	}
		
}

unset( $answer['has_attach'] );

//把\n替换成<br/>
//$answer['answer_content'] = str_replace("\n", "<br>", $answer['answer_content'] );

//print_r( $answer_attach );


exit_success($answer);


