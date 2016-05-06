CREATE TABLE IF NOT EXISTS `aws_app_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `content` text COMMENT '内容',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='APP崩溃异常信息' AUTO_INCREMENT=1;