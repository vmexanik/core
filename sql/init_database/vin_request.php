<?

$aInitSql=array('
CREATE TABLE IF NOT EXISTS `vin_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_manager_fixed` int(11) NOT NULL,
  `refuse_for` varchar(20) NOT NULL,
  `post` int(11) NOT NULL,
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_comment` text NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `is_archive` int(11) NOT NULL,
  `order_status` enum(\'new\',\'work\',\'parsed\',\'ordered\',\'refused\',\'end\') NOT NULL DEFAULT \'new\',
  `marka` varchar(50) NOT NULL,
  `vin` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `country_producer` varchar(255) NOT NULL,
  `engine` varchar(50) NOT NULL,
  `month` varchar(15) NOT NULL,
  `year` varchar(10) NOT NULL,
  `volume` varchar(50) NOT NULL,
  `body` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `is_remember` int(11) NOT NULL,
  `remember_text` varchar(255) NOT NULL,
  `part_description` text NOT NULL,
  `kpp` varchar(255) NOT NULL,
  `additional` varchar(255) NOT NULL,
  `manager_comment` varchar(255) NOT NULL,
  `part_array` text NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `wheel` varchar(255) NOT NULL,
  `utable` varchar(255) NOT NULL,
  `engine_number` varchar(255) NOT NULL,
  `engine_code` varchar(255) NOT NULL,
  `engine_volume` varchar(255) NOT NULL,
  `kpp_number` varchar(255) NOT NULL,
  `passport_image_name` varchar(255) DEFAULT NULL,
  `passport_image_name_small` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `post` (`post`),
  KEY `is_archive` (`is_archive`)
);
','

CREATE TABLE IF NOT EXISTS `template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `type_` enum(\'letter\',\'bill\',\'content\') NOT NULL DEFAULT \'letter\',
  `is_smarty` int(1) NOT NULL DEFAULT \'0\',
  `priority` int(11) NOT NULL DEFAULT \'3\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_2` (`code`),
  KEY `code` (`code`),
  KEY `type_` (`type_`)
) ;

','
INSERT INTO `template` ( `code`, `name`, `content`, `type_`, `is_smarty`, `priority`) VALUES
( \'unregistered_vin_request\', \'unregistered_vin_request\', \'<h1>VIN запрос пользователя</h1>\n<div class="notes">Ваш запрос отправлен!</div>\n<p class="info">После обработки - Вам будет выслано уведомление на телефон или почту. Вы сможете просмотреть обработаный ВИН запрос после того, как залогинитесь. Далеее нужно проценить подобраные менеджером коды запчастей, выбрать наиболее подходящий Вам вариант цена, срок доставки, поставщик и положить запчасти себе в корзину. Из запчастей в корзине формируются заказы, за них надо внести предоплату - только в такой последовательности Ваш заказ пойдет в работу и будет доставлен в указанный в прайсах срок.</p>\', \'letter\', 0, 3),
( \'vin_request_sent\', \'Ответ на запрос по VIN \', \'<table width="100%" cellspacing="1" cellpadding="1" border="0">\n    <tbody>\n        <tr>\n            <td><a href="http://www.mstarproject.ru"><img width="224" height="88" border="0" src="http://mstarproject.mstarproject.com/image/design/es_top_left.jpg" alt="" /></a></td>\n            <td style="text-align: right;">По всем вопросам обращайтесь: г.Москва, Верейская ул, д.32, стр.32А, <br />\n            тел./факс: (495) 662-47-12<br />\n            e-mail: <a target="_blank" href="mailto:sales@mstarproject.ru">sales@mstarproject.ru</a></td>\n        </tr>\n    </tbody>\n</table>\n<h3>Ответ на запрос по VIN № {$aVinRequest.id}</h3>\n<p>Ваш менеджер обработал Ваш VIN-запрос и подобрал коды к запрашиваемым Вами запчастям<br />\n<br />\nВаш логин на сайте: {$aVinRequest.login}<br />\n<br />\nВаш пароль: {$aVinRequest.password}<br />\n<br />\nВам необходимо открыть обработаный VIN-запрос<br />\nhttp://www.mstarproject.ru/?action=vin_request<br />\n<br />\nи проценив подобраные детали в нашем <a href="http://www.mstarproject.ru/?action=catalog">каталоге</a>, выбрать подходящую Вам цену, поставщика и срок доставки запчасти и отправить ее в корзину. После этого вы сможете оформить заказ, он поступит в работу и Ваш менеджер приступит к выполнению Вашего заказа.</p>\', \'letter\', 0, 3),
( \'vin_request_refused\', \'Отказ на запрос по VIN \', \'<table width="100%" cellspacing="1" cellpadding="1" border="0">\n    <tbody>\n        <tr>\n            <td><a href="http://www.mstarproject.ru"><img width="224" height="88" border="0" src="http://mstarproject.mstarproject.com/image/design/es_top_left.jpg" alt="" /></a></td>\n            <td style="text-align: right;">По всем вопросам обращайтесь: г.Москва, Верейская ул, д.32, стр.32А, <br />\n            тел./факс: (495) 662-47-12<br />\n            e-mail: <a target="_blank" href="mailto:info@mstarproject.ru">info@mstarproject.ru</a></td>\n        </tr>\n    </tbody>\n</table>\n<p>&nbsp;&nbsp;</p>\n<p><b>Ответ на запрос по Vin  № </b><strong>{$aVinRequest.id}</strong></p>\n<p>Ваш менеджер обработал Ваш VIN-запрос и отказал Вашу заявку</p>\n<p><strong>Ваш логин на сайте</strong>: {$aVinRequest.login}<br />\n<br />\n<span style="font-weight: bold;">Ваш пароль</span>: {$aVinRequest.password}</p>\n<p>Что бы увидеть причину отказа, зайдите в <a target="_blank" href="http://www.mstarproject.ru/?action=vin_request">VIN запросы</a>&nbsp; под своим логином и паролем<br />\n<a target="_blank" href="../../../?action=vin_request">http://www.mstarproject.ru/?action=vin_request</a></p>\n<p>&nbsp;</p>\n<p>Комментарий менеджера: {$aVinRequest.manager_comment}</p>\n<p>&nbsp;</p>\n<p>Ваш менеджер на сайте: <strong>{$aManager.name}</strong>. Все текущие вопросы по работе на сайте задавайте ему через систему внутренних <a href="../../../?action=message"><strong>сообщений</strong></a> (<strong>ник: {$aManager.login}</strong>): либо по телефону <strong>{$aManager.phone}</strong>.&nbsp;</p>\n<p>Настроить систему почтовых оповещений вы можете на странице &laquo;<a href="../../../?action=notification"><strong>Оповещения</strong></a>&raquo;. Используйте Ваш логин и пароль для доступа к этой странице.</p>\n<p>Вы можете ответить на это письмо, если у Вас возникли вопросы. При ответе просьба цитировать всю переписку.</p>\', \'letter\', 1, 3),
( \'parsed_vin_request\', \'Шаблон смс уведомления\', \'<p>mstarproject.mstarroject.com - vash zapros {$aVinRequest.id} obrabotan. Vash login: {$aCustomer.login} password: {$aCustomer.password}</p>\', \'letter\', 1, 2);
','

CREATE TABLE IF NOT EXISTS `translate_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_2` (`code`)
) ;

','

INSERT INTO `translate_text` ( `code`, `content`) VALUES
( \'vin_request phone example\', \'<strong><span style="color: rgb(255, 0, 0);">+38050</span> 1234568</strong>\'),
( \'describe spare parts\', \'Опишите подробно заказываемые запчасти ниже\'),
( \'vin_request_add_right\', \'<p>vin_request_add_right</p>\n<p>Текстовое описание справа от формы вин запросов в админке - Перевод Текстов</p>\n<p>VIN (Vehicle Identification Number) - Номер шаси (кузова, рамы), всегда 17 символов. VIN код указан в тех паспорте Вашего автомобиля. На изображении ниже приведен пример VIN кода, указаного в тех паспорте автомобиля</p>\n<p><img width="250" hspace="10" height="56" alt="" src="/imgbank/vin1.jpg" /></p>\n<p>Если вин код указан не полностью или с ошибкой - по вин запросу придет отказ.</p>\n<p>Опишите подробно заказываемые запчасти, вводя каждую деталь отдельно в новой строке.</p>\n<p><b>В каждой строке должна быть только одна деталь!</b></p>\n<p>После обработки - Вам будет выслано уведомление на почту. Вы сможете просмотреть обработаный ВИН запрос после того, как залогинитесь. Далеее нужно проценить подобраные менеджером коды запчастей, выбрать наиболее подходящий Вам вариант цена,срок доставки,поставщик и положить запчасти себе в корзину. Из запчастей в корзине формируются заказы, за них надо внести предоплату - только в такой последовательности Ваш заказ пойдет в работу и будет доставлен в указанный в прайсах срок.</p>\n<p><span style="color: rgb(255, 0, 0);"><strong>Внимание!!!</strong></span>  Для запроса дателей <strong><span style="color: rgb(255, 0, 0);">Renault </span></strong>необходимы данные &quot;овальной таблички&quot;. Эта табличка находится на правой центральной стоке передней двери. Данные заполняете в поле &quot;Комментарий&quot;</p>\n<table width="100%" cellspacing="5" cellpadding="5" border="0">\n    <tbody>\n        <tr>\n            <td>&nbsp;</td>\n            <td>&nbsp;</td>\n        </tr>\n    </tbody>\n</table>\n<p>&nbsp;</p>\');
');

?>