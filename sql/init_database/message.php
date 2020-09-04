<?

$aInitSql=array("
CREATE TABLE IF NOT EXISTS `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user` int(11) NOT NULL DEFAULT '0',
  `id_message_folder` int(11) NOT NULL DEFAULT '0',
  `is_read` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `is_old` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `id_message_folder` (`id_message_folder`),
  KEY `timestamp` (`timestamp`),
  KEY `is_read` (`is_read`),
  KEY `is_old` (`is_old`),
  KEY `from` (`from`),
  KEY `to` (`to`)
) ENGINE=InnoDB;
","

INSERT INTO `message` (`id`, `from`, `to`, `subject`, `timestamp`, `post_date`, `id_user`, `id_message_folder`, `is_read`, `text`, `is_old`) VALUES
(3, 'mstar', 'manager', 'тестовое сообщение в личку', 1283261847, '2010-08-31 16:37:27', 252, 1, 1, 'Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. ', 0),
(4, 'mstar', 'manager', 'тестовое сообщение в личку', 1283261847, '2010-08-31 16:37:27', 2, 2, 1, 'Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. ', 0),
(5, 'manager', 'mstar', 'RE:тестовое сообщение в личку', 1283261919, '2010-08-31 16:38:39', 2, 1, 1, 'Тестовый реплай. Тестовый реплай. Тестовый реплай. Тестовый реплай. \r\n\n\nOn 31.08.2010 16:37:27 \r\nmstar wrote : \r\n----------------------------\r\nТестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. ', 0),
(6, 'manager', 'mstar', 'RE:тестовое сообщение в личку', 1283261921, '2010-08-31 16:38:41', 252, 2, 1, 'Тестовый реплай. Тестовый реплай. Тестовый реплай. Тестовый реплай. \r\n\n\nOn 31.08.2010 16:37:27 \r\nmstar wrote : \r\n----------------------------\r\nТестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. ', 0),
(7, 'mstar', 'manager', 'RE:RE:тестовое сообщение в личку', 1283262070, '2010-08-31 16:41:10', 252, 1, 0, 'фывфы фывфыв\r\n\n\nOn 31.08.2010 16:38:39 \r\nmanager wrote : \r\n----------------------------\r\nТестовый реплай. Тестовый реплай. Тестовый реплай. Тестовый реплай. \r\n\r\n\r\nOn 31.08.2010 16:37:27 \r\nmstar wrote : \r\n----------------------------\r\nТестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. Тестовый комент. ', 0);
","

CREATE TABLE IF NOT EXISTS `message_folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL DEFAULT '0',
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
","

INSERT INTO `message_folder` (`id`, `id_user`, `name`) VALUES
(1, -1, 'Inbox'),
(2, -1, 'Outbox'),
(3, -1, 'Draft'),
(4, -1, 'Deleted');

","

CREATE TABLE IF NOT EXISTS `message_note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `reply_to` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `is_closed` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `post` int(11) NOT NULL,
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB;
");

?>