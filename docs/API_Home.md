## 首页接口

##### associate_action 代码说明

+ 101 发布问题 (history_id,associate_action,add_time,uid,user_info,associate_id,question_info)
+ 105 关注问题 (history_id,associate_action,add_time,uid,user_info,associate_id,question_info)
+ 201 回答问题 (history_id,associate_action,add_time,uid,user_info,associate_id,answer_info,question_info)
+ 204 赞同问题回答 (history_id,associate_action,add_time,uid,user_info,associate_id,answer_info,question_info)
+ 501 发布文章 (history_id,associate_action,add_time,uid,user_info,associate_id,article_info)
+ 502 赞同文章 (history_id,associate_action,add_time,uid,user_info,associate_id,article_info)


##首页

> URL：api/explore/   （ http://www.example.com/?/api/home/ ）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数：

- per_page (int)  可选，默认20
- page (int)  可选，默认0，（从第0页开始的）

> 返回的信息：

- 这个请直接看 http://w.hihwei.com/?/api/home/ 这个页面（装个postman一目了然）

- NOTE：特别提醒下，这个接口返回的total_rows是当前页的信息总条数，那，你如何知道信息全部加载完了呢，从第一页开始，当你加载第n页的时候，发现它返回的total_rows是0。恭喜你，已全部加载完成！

- 再解释下：(部分字段含义)

- - history_id 如果要缓存，这个字段有点用的
- - associate_action  上面有解释的 
- - add_time 动作发生的时间
- - associate_id  （如果associate_action是1打头的，则是问题id;2打头，则是回答id,5打头则是文章id）



