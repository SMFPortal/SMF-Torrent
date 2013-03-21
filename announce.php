<?php
/**********************************************************************************
* announce.php                                                                    *
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

define('SMF', 'SSI');
define('WIRELESS', false);

if (function_exists('set_magic_quotes_runtime'))
  @set_magic_quotes_runtime(0);
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL);
$time_start = microtime();

require_once(dirname(__FILE__) . '/Settings.php');

if ((empty($cachedir) || !file_exists($cachedir)) && file_exists($boarddir . '/cache'))
	$cachedir = $boarddir . '/cache';

require_once($sourcedir . '/QueryString.php');
require_once($sourcedir . '/Subs.php');
require_once($sourcedir . '/Errors.php');
require_once($sourcedir . '/Load.php');
require_once($sourcedir . '/Security.php');
require_once($sourcedir . '/Subs-Tracker.php');
require_once($sourcedir . '/Announce.php');
require_once($sourcedir . '/Scrape.php');
require_once($sourcedir . '/BEncode.php');

if (@version_compare(PHP_VERSION, '5') == -1)
	require_once($sourcedir . '/Subs-Compat.php');

$smcFunc = array();

loadDatabase();
reloadSettings();

$context = array();

if (get_magic_quotes_gpc() != 0)
	$_GET = stripslashes__recursive($_GET);

// Some clients may send invalid query
if (isset($_GET['p']) && is_string($_GET['p']) && strpos($_GET['p'], '?') !== false)
{
	$temp = explode('?', $_GET['p'], 2);
	$_GET[$k] = $temp[0];

	@list ($key, $val) = @explode('=', $temp[1], 2);
	if (!isset($_GET[$key]))
		$_GET[$key] = $val;

	unset($temp);
}

// BT doesn't use post
$_REQUEST = $_GET;

// Make sure we know the URL of the current request.
$_SERVER['REQUEST_URL'] = $_SERVER['REQUEST_URI'];

loadLanguage('Tracker');

if (!empty($maintenance))
	btFatalError($mtitle . ':' . $mmessage);
elseif (empty($modSettings['enableTracker']))
	btFatalError($txt['tracker_disabled']);

if (!defined('scrape') && !isset($_REQUEST['scrape']))
	AnnounceMain();
else
	ScrapeMain();

btFatalError($txt['invalid_request']);
?>
