#发现接口

##发现（最新，推荐，热门[30天，7天，当天]，等待回复）

> URL：api/explore/   （ http://www.example.com/?/api/explore/ ）

> HTTP请求方式

- GET

> 请求参数：

- per_page (int)  可选，默认10
- page (int)  可选，默认1
- day (int)  可选，默认30
- is_recommend (int)  可选，有1和0两种值，默认0  [如果你是要返回“推荐”栏目的数据，这个参数值设为1，sort_type可以不设]
- sort_type （string） 可选，有new，hot，unresponsive三种值，默认new    new：最新  hot：热门  unresponsive：等待回复

> 返回的信息：

- 这个请直接看 http://w.hihwei.com/?/api/explore/ 这个页面（装个jsonview一目了然）

- NOTE：特别提醒下，这个接口返回的total_rows是当前页的信息总条数，那，你如何知道信息全部加载完了呢，从第一页开始，当你加载第n页的时候，发现它返回的total_rows是0。恭喜你，已全部加载完成！

- 再解释下：(部分字段含义)

- - answer_users 贡献者信息
- - answer 此问题的最后一个回答信息（包括回答者） 如果问题现在0回复，那就没有
- - topics 问题关联的话题
- - user_info 提问者信息
- - update_time 最后更新时间  即最后一个回答发布时间
