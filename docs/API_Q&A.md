#问题和回答相关接口

##问题详情

> URL：api/question/question/   （  DEMO: view-source:http://w.hihwei.com/?/api/question/question/?id=2  ）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int(问题ID)

> [Out] String(问题标题)

> [Out] String(问题正文)

> [Out] Int(问题关注数)

> [Out] 回答列表(

> > Int(ID)
 
> > Int(用户ID)

> > String(用户姓名)

> > String(用户头像)

> > String(用户一句话签名)

> > Int(赞同的人数)

> > String(回答预览)
 
> )

> [Out] Tag列表(

> > Int(ID)

> > Int(名称)

> )

## 关注该问题的用户列表

> URL：question_fans_user.php   （  DEMO: http://w.hihwei.com/api/question_fans_user.php?id=2  ）

> HTTP请求方式

- GET

> 请求参数：

> [in] id 问题编号

> [out] 用户id

> [out] 用户名

> [out] 用户头像

> [out] 用户一句话签名


## 回答的详细

> URL：answer_detail.php   （  DEMO: http://w.hihwei.com/api/answer_detail.php?id=4  ）

> HTTP请求方式

- GET

> 请求参数：

> [In] id 回答编号

> [Out] Int(用户ID)

> [Out] Int(赞同数)

> [Out] String(正文)

> [Out] Int(评论数)

> [Out] Date(日期) 


## 回答的详细 （第二版）

> URL：api/question/answer_detail/   （  DEMO: w.hihwei.com/?/api/question/answer_detail/?id=4  ）

> HTTP请求方式

- GET

> 请求参数：

> [In] id 回答编号

> [Out] Int(回答ID)

> [Out] Int(所属问题ID)

> [Out] Int(用户ID)

> [Out] String(用户名)

> [Out] String(用户签名)

> [Out] String(用户头像)

> [Out] Int(赞同数)

> [Out] String(正文)

> [Out] Int(评论数)

> [Out] Date(日期) 

> [Out] Int(当前登录用户是否赞或踩了该回答,如果已赞，则vote_value为1，如果已踩，vote_value为-1，否则为0) 


## 回答的评论列表

> URL：answer_comment.php  （  DEMO: http://w.hihwei.com/api/answer_comment.php?id=4  ）

> HTTP请求方式

- GET

> 请求参数：

> [In] id 回答编号

> [Out] Int(评论人ID)

> [Out] String(评论人姓名)

> [Out] String(评论正文)

> [Out] Date(日期)

> [Out] Array at_user (被@用户信息，如果有则会返回这个信息)   

> 图释：

![image](https://raw.githubusercontent.com/ifLab/FMobile-Design/master/api/at_user.png)


## 发布回答评论 && 对回答评论的回复 

> URL： question/ajax/save_answer_comment/  （http://w.hihwei.com/?/question/ajax/save_answer_comment/?answer_id=7）

> HTTP请求方式

- GET & POST

> Header

- COOKIE

> 请求参数 

- [GET] answer_id (回答ID) 

- [POST] message (评论内容)  【 如果对回答评论进行回复，此字段值格式是：@Hwei:你的回复很赞，跟Web版是一样的 】

## 对回答踩和赞的操作 

> URL： question/ajax/answer_vote/  （http://w.hihwei.com/?/question/ajax/answer_vote/）

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数 

- answer_id (回答ID) 

- value ( 如果是赞和取消赞，此参数值为：1，如果是踩和取消踩，此参数值为-1）

## 问题关注，取消关注操作

> URL： question/ajax/focus/  （DEMO：http://w.hihwei.com/?/question/ajax/focus/?question_id=45）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数 

- question_id (问题ID)

- NOTE：不需要传其他参数，如果当前用户已经关注该问题，会取消关注，反之则关注
