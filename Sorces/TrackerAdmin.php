<?php
/**********************************************************************************
* TrackerAdmin.php                                                                *
***********************************************************************************
* SMF Torrent                                                                     *
* =============================================================================== *
* Software Version:           SMF Torrent 0.1 Alpha                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2008 by:          Niko Pahajoki (http://www.madjoki.com)              *
* Support, News, Updates at:  http://www.madjoki.com                              *
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

function TrackerAdmin()
{
  global $sourcedir;

	require_once($sourcedir . '/ManageServer.php');

	loadLanguage('Tracker');
	isAllowedTo('tracker_admin');

	TrackerAdminSettings();
}

function TrackerAdminSettings($return_config = false)
{
	global $context, $scripturl, $txt;

	$config_vars = array(
			array('check', 'trackerEnabled'),
			array('check', 'trackerAllowExternal'),
			array('check', 'enablePasskey'),
			array('check', 'scrapePasskey'),
			array('check', 'checkConnectable'),
		'',
			array('text', 'torrentDirectory'),
	);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);

		writeLog();
		redirectexit('action=admin;area=trackersettings');
	}

	prepareDBSettingContext($config_vars);

	// Template
	$context['post_url'] = $scripturl . '?action=admin;area=trackersettings;save';
	$context['settings_title'] = $txt['tracker_settings'];
	$context['sub_template'] = 'show_settings';
}
?>
