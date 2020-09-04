<?

$aInitSql=array("
CREATE TABLE IF NOT EXISTS `capcha_hash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_` enum('mathematic') NOT NULL DEFAULT 'mathematic',
  `hash` varchar(50) NOT NULL,
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_` (`type_`,`hash`)
)
");

?>