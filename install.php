<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
global $db;

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_groups` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `owner` int(11) NOT NULL,
 `name` varchar(80) NOT NULL,
 `type` varchar(25) NOT NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_group_entries` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `groupid` int(11) NOT NULL,
 `user` int(11) NOT NULL,
 `number` varchar(100) default NULL,
 `fname` varchar(100) default NULL,
 `lname` varchar(100) default NULL,
 PRIMARY KEY (`id`)
);';

foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx("Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

?>
