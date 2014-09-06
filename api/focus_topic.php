<?php
include "ci_db.php";
$get = $_POST;

if( empty( $get['uid'] ) || empty( $get['topic_id'] ) ) exit_error('参数不完整');
if( empty( $get['type'] ) ) $get['type'] = 'focus';

$total_rows = $db->select("COUNT(*) AS total_rows")->from(TABLE_PREFIX.'topic_focus')->where(array('topic_id'=>$get['topic_id'],'uid'=>$get['uid']))->get()->row_array();


if( $get['type'] == 'cancel' ){  //取消关注
	if( $total_rows['total_rows']  == 0 )  exit_success('success');

	//取消关系
	$db->delete( TABLE_PREFIX."topic_focus", array(
												'topic_id'=>$get['topic_id'],
												'uid'=>$get['uid']
											) );

	//更新topic
	$db->query( "UPDATE ".TABLE_PREFIX."topic 
				SET focus_count = focus_count - 1 
				WHERE topic_id = ".$get['topic_id']
			 );
	//更新users
	$db->query( "UPDATE ".TABLE_PREFIX."users
				SET topic_focus_count = topic_focus_count - 1 
				WHERE uid = ".$get['uid']
			 );

}else{  //关注
	if( $total_rows['total_rows']  > 0 )  exit_success('success');


	//建立关系
	$db->insert( TABLE_PREFIX."topic_focus", array(
												'topic_id'=>$get['topic_id'],
												'uid'=>$get['uid'],
												'add_time'=>time()
											) );

	//更新topic
	$db->query( "UPDATE ".TABLE_PREFIX."topic 
				SET focus_count = focus_count + 1 
				WHERE topic_id = ".$get['topic_id']
			 );
	//更新users
	$db->query( "UPDATE ".TABLE_PREFIX."users
				SET topic_focus_count = topic_focus_count + 1 
				WHERE uid = ".$get['uid']
			 );
}



exit_success('success');