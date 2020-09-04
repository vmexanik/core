<?

$aInitSql=array("
CREATE TABLE IF NOT EXISTS `sms_delayed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(15) DEFAULT NULL,
  `message` text NOT NULL,
  `post` int(11) unsigned NOT NULL DEFAULT '0',
  `sent_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
");

?>