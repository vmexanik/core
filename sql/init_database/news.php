<?

$aInitSql=array("
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section` varchar(50) NOT NULL DEFAULT 'site',
  `short` varchar(255) NOT NULL DEFAULT '',
  `full` text,
  `date` int(11) NOT NULL DEFAULT '0',
  `visible` int(11) DEFAULT '1',
  `num` int(11) NOT NULL DEFAULT '0',
  `has_full_link` int(11) DEFAULT '0',
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `section` (`section`),
  KEY `date` (`date`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB;
");

?>