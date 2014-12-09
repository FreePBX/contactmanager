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
 `displayname` varchar(100) default NULL,
 `fname` varchar(100) default NULL,
 `lname` varchar(100) default NULL,
 `title` varchar(100) default NULL,
 `company` varchar(100) default NULL,
 `address` varchar(200) default NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_numbers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `entryid` int(11) NOT NULL,
 `number` varchar(100) default NULL,
 `extension` varchar(100) default NULL,
 `type` varchar(100),
 `flags` varchar(100),
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_xmpps` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `entryid` int(11) NOT NULL,
 `xmpp` varchar(100) default NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_emails` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `entryid` int(11) NOT NULL,
 `email` varchar(100) default NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `contactmanager_entry_websites` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `entryid` int(11) NOT NULL,
 `website` varchar(100) default NULL,
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

outn(_("checking for displayname field.."));
$sql = "SELECT `displayname` FROM contactmanager_group_entries";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	$sql = "ALTER TABLE contactmanager_group_entries ADD `displayname` varchar(100)";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("ERROR failed to update displayname field"));
	} else {
		out(_("OK"));
	}
} else {
	out(_("already exists"));
}

outn(_("checking for address field.."));
$sql = "SELECT `address` FROM contactmanager_group_entries";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	$sql = "ALTER TABLE contactmanager_group_entries ADD `address` varchar(200)";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("ERROR failed to update address field"));
	} else {
		out(_("OK"));
	}
} else {
	out(_("already exists"));
}

outn(_("checking for extension field.."));
$sql = "SELECT `extension` FROM contactmanager_entry_numbers";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
	$sql = "ALTER TABLE contactmanager_entry_numbers ADD `extension` varchar(100)";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		out(_("ERROR failed to update extension field"));
	} else {
		out(_("OK"));
	}
} else {
	out(_("already exists"));
}

?>
