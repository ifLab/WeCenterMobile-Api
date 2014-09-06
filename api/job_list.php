<?php
include "ci_db.php";

$get = $_GET;
$per_page = 100; //默认全部返回
if( !empty( $get['per_page'] ) ) $per_page = $get['per_page'];

if( empty( $get['page'] ) ) $get['page'] = 1;

//查总数
$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'jobs')->get()->row_array();
if( $total_rows['total_rows']  == 0 )  exit_success($total_rows);

//修正page
$get['page'] = ($get['page']<1)?1:$get['page'];
$get['page'] = ($get['page']>ceil($total_rows['total_rows']/$per_page))?ceil($total_rows['total_rows']/$per_page):$get['page'];

$offset = ($get['page']-1)*$per_page.','.$per_page;

$rows = $db->select("id,job_name")->from(TABLE_PREFIX.'jobs')->limit($per_page,$offset)->get()->result_array();

exit_success(array('total_rows'=>$total_rows['total_rows'],'rows'=>$rows));