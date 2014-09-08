#文章相关接口

## 文章的详细 

> URL： api/article/article/  （  DEMO: http://w.hihwei.com/?/api/article/article/?id=2  ）

> HTTP请求方式

- GET

> 请求参数 

> [in] int(文章的id)

> [out] 文章的信息(

> > 文章的标题及详细信息

> > 点赞的人数

> > [Out] Int(用户ID)

> > [Out] String(用户名)

> > [Out] String(用户签名)

> > [Out] String(用户头像)

> > [Out] Int(当前登录用户是否赞或踩了该回答,如果已赞，则vote_value为1，如果已踩，vote_value为-1，否则为0) 

> > 相关的话题

> )


## 文章的评论列表

> URL： api/article/comment/  （  DEMO: http://w.hihwei.com/?/api/article/comment/?id=2  ）

> HTTP请求方式

- GET

> 请求参数 

- id (文章ID)

- page (分页页码)

>  [out] 评论(

> > 评论的id

> > 评论用户的用户名

> > 评论用户的用户id

> > 评论用户的头像

> > 发布的时间

> > 点赞人数

> > [Out] Int(当前登录用户是否赞评论,如果已赞，则vote_value为1，否则为0) 

> > 评论＠的人信息（如果有则有）

> )

> 图释：

![image](https://raw.githubusercontent.com/ifLab/WeCenterMobile-Api/master/docs/img/at_comment.png)

## 发表文章评论

> URL：api/publish/save_comment/

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数：

- article_id (文章ID)

- message (评论的内容)

- at_uid (可选，如果是回复某条评论，则要指定被回复者uid)

> 返回结果：

- comment_id (评论ID)

> 可能返回的错误信息：

- 指定文章不存在

- 已经锁定的文章不能回复

- 请输入回复内容

- 你所在的用户组不允许发布站外链接

## 对文章评论点赞和取消操作

> URL： article/ajax/article_vote/  （http://w.hihwei.com/?/article/ajax/article_vote/）

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数 

- type ( 对文章评论操作，参数值是: comment )

- item_id (comment的id)

- rating  ( 有两种值，1（赞）0（取消赞） )


## 文章点赞,踩，及取消操作

> URL： article/ajax/article_vote/  （http://w.hihwei.com/?/article/ajax/article_vote/）

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数 

- type ( 对文章操作，参数值是: article )

- item_id (article的id)

- rating  ( 有三种值，1（赞） -1（踩）  0（取消赞或者取消踩） )

