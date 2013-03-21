<?php
/**********************************************************************************
* Tracker.php                                                                     *
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

if (!defined('SMF'))
  die('Hacking attempt...');

function TrackerMain()
{
	global $sourcedir, $modSettings;

	loadTracker('main');

	if (empty($modSettings['enableTracker']))
		fatal_lang_error('tracker_disabled');

	$subActions = array(
		'list' => array('Tracker.php', 'TrackerList', 'tracker_view'),
		'view' => array('Tracker.php', 'TrackerView'),
		'delete' => array(),
		'download' => array('Tracker.php', 'TrackerDownload'),
	);

	if (!isset($_REQUEST['sa']) && isset($_REQUEST['torrent']))
		$_REQUEST['sa'] = 'view';

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][2]))
		isAllowedTo($subActions[$_REQUEST['sa']][2]);

	require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
	$subActions[$_REQUEST['sa']][1]();
}

function loadTracker($mode = 'main')
{
	global $context, $modSettings, $settings, $tracker_version;

	if (!empty($tracker_version))
		return;

	$tracker_version = '0.1 Alpha rev 108';

	$context['tracker'] = array();

	loadLanguage('Tracker');
	loadTemplate('Tracker');

	if ($mode == 'main')
	{
		$context['html_headers'] .= '
		<link rel="stylesheet" type="text/css" id="tracker" name="tracker" href="' . $settings['default_theme_url'] . '/css/tracker.css" />';
	}
}

function TrackerList()
{
	global $context, $sourcedir, $scripturl, $db_prefix, $modSettings, $txt, $smcFunc, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}tracker_torrents AS t'
	);

	list ($countTorrents) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['torrents_per_page'] = 25;

	$context['page_index'] = constructPageIndex($scripturl . '?action=tracker', $_REQUEST['start'], $countTorrents, $context['torrents_per_page']);

	$request = $smcFunc['db_query']('', '
		SELECT
			t.id_torrent, t.id_category, t.name, t.torrentname, t.torrentfile, t.filesize,
			t.member_name, t.id_member, t.added, t.last_action, t.seeders, t.leechers, t.transfer,
			t.is_disabled, t.downloads, c.cat_name
		FROM {db_prefix}tracker_torrents AS t
			LEFT JOIN {db_prefix}tracker_category AS c ON (c.id_category = t.id_category)
		LIMIT {int:start},{int:torrents_per_page}',
		array(
			'start' => $_REQUEST['start'],
			'torrents_per_page' => $context['torrents_per_page'],
		)
	);

	$context['torrents'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['torrents'][] = array(
			'name' => $row['name'],
			'link' => '<a href="' . $scripturl . '?action=tracker;torrent=' . $row['id_torrent'] . '">' .  $row['name'] . '</a>',
			'href' => $scripturl . '?action=tracker;torrent=' . $row['id_torrent'],
			'download_link' => '<a href="' . getDownloadURL($row['id_torrent']) . '">' . $row['torrentname'] . '</a>',
			'download_href' => getDownloadURL($row['id_torrent']),
			'delete_link' => '<a href="' . $scripturl . '?action=tracker;sa=delete;torrent=' . $row['id_torrent'] . '">' . $txt['tracker_delete'] . '</a>',
			'delete_href' => $scripturl . '?action=tracker;sa=delete;torrent=' . $row['id_torrent'],
			'size' => size_readable($row['filesize']),
			'size_bytes' => $row['filesize'],
			'num_seeders' => $row['seeders'],
			'num_leechers' => $row['leechers'],
			'downloads' => $row['downloads'],
			'transfer' => size_readable($row['transfer']),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['cat_name'],
				'link' => '<a href="' . $scripturl . '?action=tracker;category=' . $row['id_category'] . '">' . $row['cat_name'] . '</a>',
			),
			'uploader' => array(
				'id' => $row['id_member'],
				'name' => $row['member_name'],
				'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['member_name'] . '</a>',
			),
			'added' => timeformat($row['added']),
			'last_action' => timeformat($row['last_action']),
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	//$context['page_title'] = sprintf($txt['tracker_view_title'], $context['torrent']['name']);
	$context['sub_template'] = 'torrent_list';
}

function TrackerView()
{
	global $context, $sourcedir, $scripturl, $db_prefix, $modSettings, $txt, $smcFunc, $user_info;

	if (empty($_REQUEST['torrent']) || !loadTorrent((int) $_REQUEST['torrent']))
		fatal_lang_error('torrent_not_found');

	// TODO: Cache this
	if (isset($_REQUEST['showPeers']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, p.last_action, p.download_left, p.downloaded, p.uploaded, p.dlrate, p.ulrate,
				mem.real_name
			FROM {db_prefix}tracker_peers AS p
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = p.id_member)
			WHERE id_torrent = {int:torrent}
			ORDER BY download_left DESC',
			array(
				'torrent' => $_REQUEST['torrent'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['torrent'][$row['download_left'] > 0 ? 'leechers' : 'seeders'][] = array(
				'name' => $row['real_name'],
				'link' => '<a href="' . $scripturl .'?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
				'idle' => duration_format(time() - $row['last_action']),
				'download_left' => size_readable($row['download_left']),
				'process' => round((($size - $row['download_left']) / $size) * 100, 2),
				'downloaded' => size_readable($row['downloaded']),
				'uploaded' => size_readable($row['uploaded']),
				'download_rate' => size_readable($row['dlrate']) . 's',
				'upload_rate' => size_readable($row['ulrate']) . 's',
				'ratio' => ratioColor($row['uploaded'], $row['downloaded']),
			);
		$smcFunc['db_free_result']($request);

		// TODO: Cache this
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.real_name, d.complete_time, d.last_action, d.leech_time, d.seed_time,
				d.downloaded, d.uploaded
			FROM {db_prefix}tracker_downloaders AS d
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = d.id_member)
			WHERE id_torrent = {int:torrent}
				AND download_left = 0',
			array(
				'torrent' => $_REQUEST['torrent'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['torrent']['complete'][] = array(
				'name' => $row['real_name'],
				'link' => '<a href="' . $scripturl .'?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
				'complete_time' => timeformat($row['complete_time']),
				'leech_time' => duration_format($row['leech_time']),
				'seed_time' => duration_format($row['seed_time']),
				'downloaded' => size_readable($row['downloaded']),
				'uploaded' => size_readable($row['uploaded']),
				'ratio' => ratioColor($row['uploaded'], $row['downloaded']),
			);
		$smcFunc['db_free_result']($request);
	}

	// Template
	$context['page_title'] = sprintf($txt['tracker_view_title'], $context['torrent']['name']);
	$context['sub_template'] = 'torrent_view';
}

function TrackerDelete()
{
	global $sourcedir, $modSettings, $txt, $smcFunc, $user_info;

	if (empty($_REQUEST['torrent']))
		fatal_lang_error('torrent_not_found');

	if (!is_array($_REQUEST['torrent']))
		$_REQUEST['torrent'] = array((int) $_REQUEST['torrent']);

	if (allowedTo('tracker_delete_any'))
		$where = '1 = 1';
	else
	{
		isAllowedTo('tracker_delete_own');

		$where = 'id_member = {int:id_member}';
	}

	$request = $smcFunc['db_query']('', '
		DELETE FROM {db_prefix}tracker_torrents
		WHERE id_torrent IN({array_int:torrents})
			AND ' . $where,
		array(
			'torrents' => $_REQUEST['torrent'],
			'id_member' => $user_info['id']
		)
	);

	redirectexit('action=tracker');
}

function TrackerDownload()
{
	global $sourcedir, $modSettings, $txt, $smcFunc, $user_info;

	if ($user_info['is_guest'] && !empty($modSettings['enablePasskey']))
	{
		if (!isset($_REQUEST['p']))
			is_not_guest();

		if (!$id_member = checkPasskey($_REQUEST['p']))
			fatal_lang_error('tracker_not_authorized');
	}
	elseif (!empty($modSettings['enablePasskey']))
	{
		$id_member = $user_info['id'];

		isAllowedTo('tracker_download');
	}
	else
	{
		isAllowedTo('tracker_download');
	}

	require_once($sourcedir . '/BEncode.php');

	// Get some info about the torrent file.
	$request = $smcFunc['db_query']('', '
		SELECT t.id_torrent, t.torrentname, t.torrentfile, t.name, t.is_disabled, t.is_external
		FROM {db_prefix}tracker_torrents AS t
		WHERE t.id_torrent = {int:torrent}
		LIMIT 1',
		array(
			'torrent' => $_REQUEST['torrent'],
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('torrent_not_found');

	$row = $smcFunc['db_fetch_assoc']($request);

	if ($row['is_disabled'])
	{
		$smcFunc['db_free_result']($request);

		fatal_lang_error('torrent_disabled');
	}

	$smcFunc['db_free_result']($request);

	// Check for the file.
	$fn = $modSettings['torrentDirectory'] . '/' . $row['torrentfile'];

	if (!file_exists($fn))
		fatal_lang_error('torrent_not_found');

	$torrent = BDecode(file_get_contents($fn));

	if (empty($row['is_external']))
		$torrent['announce'] = getAnnounceURL(isset($id_member) ? $id_member : false);

	btOutput($torrent, 'torrent', $row['torrentname'] . '.torrent');
}

function trackerStats2($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
	global $memberContext, $user_info, $user_profile, $smcFunc;

	loadTracker('');

	$context['member'] = &$memberContext[$memID];

	$context['member']['torrents'] = array(
		'incomplete' => array(),
		'complete' => array()
	);

	$request = $smcFunc['db_query']('', '
		SELECT t.id_torrent, t.name, t.filesize, t.is_disabled,
			d.start_time, d.complete_time, d.last_action, d.leech_time,
			d.seed_time, d.downloaded, d.uploaded, d.download_left
		FROM {db_prefix}tracker_downloaders AS d
			LEFT JOIN {db_prefix}tracker_torrents AS t ON (t.id_torrent = d.id_torrent)
		WHERE d.id_member = {int:member}
		ORDER BY start_time DESC',
		array(
			'member' => $memID
		));

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$group = $row['complete_time'] > 0 ? 'complete' : 'incomplete';

		$context['member']['torrents'][$group][] = array(
			'id' => $row['id_torrent'],
			'name' => $row['name'],
			'href' => empty($row['is_disabled']) ? '<a href="' . $scripturl . '?action=tracker;torrent=' . $row['id_torrent'] . '">' . $row['name'] . '</a>' : $row['name'],
			'seed_time' => duration_format($row['seed_time']),
			'leech_time' => duration_format($row['leech_time']),
			'start' => timeformat($row['start_time']),
			'completed' => timeformat($row['complete_time']),
			'last_action' => timeformat($row['last_action']),
			'last_action_ago' => duration_format(time() - $row['last_action']),
			'downloaded' => size_readable($row['downloaded']),
			'uploaded' => size_readable($row['uploaded']),
			'ratio' => ratioColor($row['uploaded'], $row['downloaded']),
			'size' => size_readable($row['filesize']),
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'tracker_user_statistics';
	$context['page_title'] = sprintf($txt['tracker_user_statistics'], $memberContext[$memID]['name']);
}

?>
