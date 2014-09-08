#用户中心相关列表接口

###以下列表接口一般都接收三个参数：uid(必须)，page(可选)，per_page(可选)

##获取我的文章列表

> URL：my_article.php （http://www.example.com/api/my_article.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] Array(

> > Int(文章ID)

> > String(文章标题)

> > String(文章预览)

> > Date(发表日期)

> )

> 可能返回的错误原因：

- 参数不完整

##获取我的提问列表

> URL：my_question.php （http://www.example.com/api/my_question.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] Array(

> > Int(问题ID)

> > String(问题标题)

> > String(问题预览)

> > Date(提问时间)

> )

> 可能返回的错误原因：

- 参数不完整

## 获取我的关注问题列表

> URL：my_focus_question.php （http://www.example.com/api/my_focus_question.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] Array(

> > Int(问题ID)

> > String(问题标题)

> > Date(提问时间)

> > Int(关注人数)

> > Int(答案数)

> )

## 获取我的回复列表

> URL：my_answer.php （http://www.example.com/api/my_answer.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] Array(

> > Int(问题ID)

> > String(问题标题)

> > String(回复用户头像URI)

> > Int(回复ID)

> > String(回复预览)

> > Int(回复赞同数)

> )

## 我关注的用户列表

> URL：my_focus_user.php （http://www.example.com/api/my_focus_user.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] String(用户头像URI)

> [Out] String(用户名)

> [Out] String(签名singnature)

> edit by huangchen

## 关注我的用户列表

> URL：my_fans_user.php （http://www.example.com/api/my_fans_user.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> 与我关注的用户列表字段相同 

## 获取职位列表

用于展示／修改个人从属行业等信息  （默认全部返回，可以加per_page和page参数）

> URL：job_list.php （http://www.example.com/api/job_list.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(页数)

> [Out] Array(

> > Int(职业ID)

> > String(职业名称)

> )


## 我关注的话题列表

> URL：my_focus_topic.php （http://www.example.com/api/my_focus_topic.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(UID)

> [In] Int(页数)

> [Out] String(图片url) （话题图片路径：http://www.example.com/uploads/topic/）

> [Out] String(标题)

> [Out] String(描述)
