#话题相关接口

## 单个话题列表

> URL：topic.php （http://www.example.com/api/topic.php）

> HTTP请求方式

- GET

> 请求参数：

> [In] Int uid （必须）

> [In] Int topic_id （必须）

> [Out] String(图片url) （话题图片路径：http://www.example.com/uploads/topic/）

> [Out] String(标题)

> [Out] String(描述)

> [Out] Int(当前用户是否已关注，关注：1  未关注：0)

## 话题关注，取消关注操作（推荐）

> URL： topic/ajax/focus_topic/  （DEMO：http://www.example.com/?/topic/ajax/focus_topic/?topic_id=17）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数 

- topic_id (话题ID)

- NOTE：不需要传其他参数，如果当前用户已经关注该话题，会取消关注，反之则关注

## 话题关注，取消关注操作

> URL：focus_topic.php （http://www.example.com/api/focus_topic.php）

> HTTP请求方式

- POST

> 请求参数：

> [In] Int uid （必须）

> [In] Int topic_id （必须）

> [In] String type （选，如果是取消关注，此值设为 'cancel' ）

> 如果成功，errno为1，不多解释，因“说在前面”有解释

##话题广场 （我关注的话题列表，热门话题列表，今日话题列表）

> URL：api/topic/square/

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数：

- id (string) [可能的值：focus（我关注的话题）hot（热门话题）today（今日话题）]
- page (页码)

> 返回结果：

- 话题列表

## 精华列表 

> URL： api/topic/topic_best_answer/  （DEMO：http://w.hihwei.com/?/api/topic/topic_best_answer/?id=4）

> HTTP请求方式

- GET

> 请求参数 

> [In] id （话题ID）

> [Out] int (问题ID)

> [Out] String(问题标题)

> [Out] int (回答者uid)

> [Out] String(回答者头像URL)

> [Out] int (回答id)

> [Out] String(赞的人数)

> [Out] String(回复内容)

## 精华详情，点击精华列表的item进入的界面

> URL： api/question/best_answer_detail/  （DEMO：http://w.hihwei.com/?/api/question/best_answer_detail/?id=2）

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数 

> [In] id (问题ID)

> [Out] int (问题ID)

> [Out] String(问题标题)

> [Out] uid (回答者头像uid)

> [Out] String(回答者头像url)

> [Out] String(回答者姓名)

> [Out] String(回答者一句话介绍)

> [Out] int (回答ID)

> [Out] String(该回答赞的人数)

> [Out] String(回答详情)

> [Out] Int(评论的个数)

> [Out] 当前用户是否已感谢 (如果已感谢,has_thanks为1，否则为0)

##单个话题信息 （不建议）

> URL：api/topic/topic/

> HTTP请求方式

- GET

> Header

- COOKIE

> 请求参数：

- id (string) [可以是topic的id或title]
> 返回结果：

- topic_info 话题信息

> 可能返回的错误原因：

- 话题不存在
