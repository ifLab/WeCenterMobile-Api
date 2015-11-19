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

    public function get_answer_ids($uid)
    {
        return $this->fetch_all( 'answer', "uid = '".$uid."'" );
    }

    public function get_answer_favorite_count( $answer_id )
    {
        return $this->count( 'favorite', "item_id = '".$answer_id."' AND type = 'article'" );
    }

    public function get_user_article_count($uid)
    {
        return $this->count( 'article', "uid = '".$uid."'" );
    }

    public function get_clean_user_info($user_info)
    {
        $user_info_key = array( 'uid', 'user_name', 'signature' );

        if(is_array($user_info))
        {
            foreach ($user_info as $k => $v)
            {
                if(!in_array($k, $user_info_key)) unset($user_info[$k]);
            }

            $user_info['avatar_file'] = get_avatar_url($user_info['uid'],'mid');
        }

        return $user_info;
    }
}
