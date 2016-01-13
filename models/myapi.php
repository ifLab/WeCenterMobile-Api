<?php
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


    public function verify_signature($class_name,$mobile_sign=null)
    {
        if (! $mobile_app_secret = AWS_APP::cache()->get('mobile_app_secret')) //缓存
        {
            if(! $mobile_app_secret = $this->fetch_one('system_setting','value',"varname = 'mobile_app_secret'"))
            {
                return true;  //从未设置  无需验证
            }
            
            AWS_APP::cache()->set('mobile_app_secret', $mobile_app_secret, 600);
        }

        if(! $mobile_app_secret = unserialize($mobile_app_secret) )
        {
            return true;  //留白  无需验证 
        }

        if(! $mobile_sign)
        {
            return false;
        }

        $mobile_app_secret_arr = explode("\n", $mobile_app_secret);

        foreach ($mobile_app_secret_arr as $key => $val) 
        { 
            if(md5($class_name.$val) == $mobile_sign)
            {
                return true;
            }
        }

        return false;
    }

    public function save_mobile_app_secret($mobile_app_secret)
    {
        if($this->fetch_row('system_setting', "varname = 'mobile_app_secret'"))  //修改
        {
            $this->update('system_setting', array(
                                'value' => serialize($mobile_app_secret)
                            ), "`varname` = 'mobile_app_secret'");

            $this->update('system_setting', array(
                                'value' => serialize(time())
                            ), "`varname` = 'mobile_app_secret_update_time'");
        }
        else  //新增
        {
            $this->insert('system_setting', array(
                            'value' => serialize($mobile_app_secret),
                            'varname' => 'mobile_app_secret'
                        ));

            $this->insert('system_setting', array(
                            'value' => serialize(time()),
                            'varname' => 'mobile_app_secret_update_time'
                        ));
        }

        AWS_APP::cache()->delete('mobile_app_secret');
    }

    public function save_app_log($content)
    {
        return $this->insert('app_log', array(
                'content' => htmlspecialchars($content),
                'add_time' => time()
            ));
    }

    public function get_app_log_list($where = null, $order = 'id DESC', $limit = 10, $page = null)
    {
        return $this->fetch_page('app_log', $where, $order, $page, $limit);
    }

}
