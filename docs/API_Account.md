#用户账号相关接口

##用户注册

> URL：api/account/register_process/  (http://www.example.com/?/api/account/register_process/)

> HTTP请求方式

- POST

> 请求参数：

- user_name (string)
- password (string) 
- email (string)

> 返回结果：

- uid 用户编号

> 可能返回的错误原因：

- 本站目前关闭注册
- 本站只能通过邀请注册
- 本站只能通过微信注册
- 请输入用户名
- 用户名已经存在
- 用户名包含无效字符
- 用户名中包含敏感词或系统保留字
- E-Mail 已经被使用, 或格式不正确
- 密码长度不符合规则

##用户登录


> URL：api/account/login_process/  (http://www.example.com/?/api/account/login_process/)

> HTTP请求方式

- POST

> 请求参数：

- user_name (string) [可以是用户名也可以是邮箱]
- password (string) 

> 返回结果：

- uid 用户编号

- user_name 用户名

- avatar_file 头像

> 可能返回的错误原因：

- 请输入正确的帐号或密码
- 抱歉, 你的账号已经被禁止登录

##用户头像修改

> URL：api/account/avatar_upload/ （http://www.example.com/?/api/account/avatar_upload/）

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数：

- user_avatar (图片文件域) 

> 返回结果：

- preview 头像地址

> 可能返回的错误原因：

- 文件类型无效
- 文件尺寸过大
- 上传失败, 请与管理员联系
- 。。。错误原因太多，没法一一解释，此处省略百万字（By Hwei）

## 获取用户信息

> URL：api/account/get_userinfo/ （DEMO:http://www.example.com/?/api/account/get_userinfo/?uid=10）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数：

> [In] Int(UID)

> [Out] String(用户头像URI)

> [Out] String(用户名)

> [Out] String(签名)

> [Out] Int(我的话题数)

> [Out] Int(我关注的人数)

> [Out] Int(关注我的人数)

> [Out] Int(赞同我的次数)

> [Out] Int(感谢我的次数)

> [Out] Int(答案被收藏次数)

> [Out] Int(当前登录用户是否关注了该用户,如果已关注，则has_focus为1，否则为0)

## 获取用户信息

> URL：user.php （http://www.example.com/api/user.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [Out] String(用户头像URI)

> [Out] String(用户名)

> [Out] Int(我的话题数)

> [Out] Int(我关注的人数)

> [Out] Int(关注我的人数)

> [Out] Int(赞同我的次数)

> [Out] Int(感谢我的次数)

> [Out] Int(答案被收藏次数)

##用于展示用户信息  

> URL：profile.php （http://www.example.com/api/profile.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [Out] String(用户名)

> [Out] Int(性别ID)

> [Out] String(个人简介)

> [Out] Int(行业ID)

> [Out] Date(生日)

##用于修改用户信息  （除uid和user_name外，其他为可选）

> URL：profile_setting.php （http://www.example.com/api/profile_setting.php）

> HTTP请求方式

- POST

> 请求参数：

> [In] Int(UID)  uid (必须)

> [In] String(用户名)   user_name （必须）

> [In] Int(性别ID)  sex (tinyint，1：男  2：女  3：保密)    

> [In] String(个人简介)  signature (string，个人签名[简介])

> [In] Int(行业ID) job_id(int) 

> [In] Date(生日)  birthday(int，Unix 时间戳)

> 如果成功，errno为1，不多解释，因“说在前面”有解释

##获取用户uid

> URL：api/account/get_uid/  (http://www.example.com/?/api/account/get_uid/)

> HTTP请求方式

- GET

> Header

- COOKIE

> 返回结果：

- uid

> 可能返回的错误原因：

- 禁止访问

## 用户关注，取消关注操作

> URL：follow/ajax/follow_people/  （DEMO：http://w.hihwei.com/?/follow/ajax/follow_people/?uid=5）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数 

- uid (用户ID)

- NOTE：不需要传其他参数，如果当前用户已经关注该用户，会取消关注，反之则关注
