<?php
/**********************************************************************************
* installDatabase.php                                                             *
***********************************************************************************
* SMF Torrent                                                                     *
* =============================================================================== *
* Software Version:           SMF Torrent 0.1                                     *
* Software by:                WasdMan  (http://smf-portal.hu)                     *
* Copyright 2013 by:          WasdMan  (http://smf-portal.hu)                     *
* Support, News, Updates at:  http://smf-portal.hu                                *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

global $txt, $boarddir, $sourcedir, $modSettings, $context, $settings, $db_prefix, $forum_version, $smcFunc;
global $db_package_log, $issueSettings, $issueTables, $issuePermissions, $issue_version, $issueRename;
global $db_connection, $db_name;

// Ugh...
if (!isset($forum_version))
{
  require_once(dirname(__FILE__) . '/index.php');
}

require_once($sourcedir . '/TorrentDatabase.php');

$tbl = array_keys($tables);

// Add prefixes to array
foreach ($tbl as $id => $table)
	$tbl[$id] = $db_prefix . $table;

db_extend('packages');

$tbl = array_intersect($tbl, $smcFunc['db_list_tables']());

doTables($tbl, $tables);
doSettings($addSettings);
doPermission($permissions);

?>
