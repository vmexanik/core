<?

$aUpdateSql=array(
"
ALTER TABLE `admin`
  DROP `passwd`,
  DROP `code`,
  DROP `value`;
",
"
ALTER TABLE  `admin` ADD  `password` VARCHAR( 100 ) NULL AFTER  `login` ,
ADD  `salt` VARCHAR( 10 ) NULL AFTER  `password`;
",
);

?>