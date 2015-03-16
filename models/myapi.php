<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class myapi_class extends AWS_MODEL
{
	//取用户头像
    public function get_user_info($uid)
    {
        return $this->fetch_row( 'users', "uid = '".$uid."'" );
    }
    
    public function get_answer_ids($uid)
    {
        return $this->fetch_all( 'answer', "uid = '".$uid."'" );
    }

    public function get_answer_favorite_count( $answer_id )
    {
        return $this->count( 'favorite', "answer_id = '".$answer_id."'" );
    }

    public function get_question_info( $id ){
    	return $this->fetch_row( 'question', "question_id = '".$id."'" );
    }

    public function get_answer_info( $id ){
    	return $this->fetch_row( 'answer', "answer_id = '".$id."'" );
    }

    //获取签名
    public function get_signature( $id ){
    	return $this->fetch_one( 'users_attrib', 'signature', "uid = '".$id."'" );
    }

    public function has_thanks( $uid, $answer_id ){
    	return $this->fetch_one( 'answer_thanks', 'id', "uid = '".$uid."' AND answer_id = '".$answer_id."'" );
    }

    public function has_focus( $uid, $other_uid ){
    	return $this->fetch_one( 'user_follow', 'follow_id', "fans_uid = '".$uid."' AND friend_uid = '".$other_uid."'" );
    }

    //当前用户是否已关注该问题
    public function has_focus_question( $uid, $question_id  ){
        return $this->fetch_one( 'question_focus', 'focus_id', "uid = '".$uid."' AND question_id = '".$question_id."'" );
    }

    //获取用户头像
    public function get_avatar_file( $id ){
        return $this->fetch_one( 'users', 'avatar_file', "uid = '".$id."'" );
    }

    
    public function get_vote_value( $uid, $answer_id ){
        return $this->fetch_one( 'answer_vote', 'vote_value', "vote_uid = '".$uid."' AND answer_id = '".$answer_id."'" );
    }


    //回答信息
    public function get_answer( $answer_id ){
        return $this->fetch_row( 'answer', "answer_id = '".$answer_id."'" );
    }

    //回答信息
    public function get_answer_attach( $answer_id ){
        return $this->fetch_all( 'attach', "item_type = 'answer' AND item_id = '".$answer_id."'" );
    }

    //当前用户是否赞或踩了该文章
    public function get_article_vote_value( $uid, $article_id ){
        return $this->fetch_one( 'article_vote', 'rating', "type = 'article' AND uid  = '".$uid."' AND item_id = '".$article_id."'" );
    }

    //当前用户是否赞或踩了该文章评论
    public function get_article_comment_vote_value( $uid, $id ){
        return $this->fetch_one( 'article_vote', 'rating', "type = 'comment' AND uid  = '".$uid."' AND item_id = '".$id."'" );
    }

}
