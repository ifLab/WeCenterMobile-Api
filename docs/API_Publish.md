#发布相关接口

##上传附件

> URL：api/publish/attach_upload/  (http://www.example.com/?/api/publish/attach_upload/)

> HTTP请求方式

- GET

> 请求参数：

- id (string) [附件类型，可能的值：question,article,answer(默认)]

- attach_access_key (string) (任意32位字符串，为了避免与历史的重复，建议 md5( time().rand(1,99) ) 这样生成, 发布一个问题时有多个附件，上传每个附件时这个值必须保持一致)

- POST

> 请求参数：

- qqfile (文件域名称)

> 返回结果：

- attach_access_key （其实可以无视，这是你给我的，现在还给你，待会点发布的时候，这个也要提交的）

- attach_id （这个是供插入在正文里的，如：[attach]9[/attach] ）

- thumb （如果是图片，会返回90*90的缩略图供预览）

> 可能返回的错误信息：

- 请选择要上传的文件

- 文件类型无效

- 文件尺寸过大

- 上传失败, 请与管理员联系

## 发布问题

> URL：api/publish/publish_question/  (http://www.example.com/?/api/publish/publish_question/)

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数：

- question_content (问题标题，note:不是内容，人家就这样设计的，别怨写接口的人，其实他也不能理解)

- question_detail (问题详情，即正文内容)

- attach_access_key (可选，如果传了附件的话，必须要有这个)

- topics (string, 话题标题，多个话题标题请以英文逗号隔开)

> 返回结果：

- question_id （问题ID)

> 可能返回的错误信息：

- 你没有权限发布问题 (没有cookie信息，就会有这种结果)

- 请输入问题标题

- 问题标题字数不得少于 5 个字

- 你所在的用户组不允许发布站外链接

- 请为问题添加话题

## 回答问题

> URL：api/publish/save_answer/  (http://www.example.com/?/api/publish/save_answer/)

> HTTP请求方式

- POST

> Header

- COOKIE

> 请求参数：

- question_id (问题ID)

- answer_content (回答的内容)

- attach_access_key (可选，如果传了附件的话，必须要有这个)

> 返回结果：

- answer_id （回答ID)

> 可能返回的错误信息：

- 问题不存在

- 已经锁定的问题不能回复

- 请输入回复内容

- 不能回复自己发布的问题，你可以修改问题内容

- 一个问题只能回复一次，你可以编辑回复过的回复

- 你所在的用户组不允许发布站外链接
