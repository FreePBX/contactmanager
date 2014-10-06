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
 `fname` varchar(100) default NULL,
 `lname` varchar(100) default NULL,
 `title` varchar(100) default NULL,
 `company` varchar(100) default NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_numbers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `entryid` int(11) NOT NULL,
 `number` varchar(100) default NULL,
 `type` varchar(100),
 `flags` varchar(100),
 PRIMARY KEY (`id`)
);';

foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx("Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

outn(_("checking for title field.."));
$sql = "SELECT `title` FROM contactmanager_group_entries";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	$sql = "ALTER TABLE contactmanager_group_entries ADD `title` varchar(100), ADD `company` varchar(100)";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("ERROR failed to update title field"));
	} else {
		out(_("OK"));
	}
} else {
	out(_("already exists"));
}

?>
