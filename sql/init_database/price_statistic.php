<?

$aInitSql=array('
CREATE TABLE IF NOT EXISTS `price_statistic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `id_provider` int(11) NOT NULL,
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `price` double(10,2) NOT NULL DEFAULT \'0.00\' COMMENT \'Purchase\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`,`id_provider`,`post_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
');

?>