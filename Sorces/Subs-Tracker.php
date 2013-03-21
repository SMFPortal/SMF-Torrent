<?php
/**********************************************************************************
* Subs-Tracker.php                                                                *
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

function loadTorrent($id_torrent)
{
  global $context, $sourcedir, $scripturl, $db_prefix, $modSettings, $txt, $smcFunc, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT
			t.id_torrent, t.id_category, t.name, t.torrentname, t.torrentfile, t.description, t.filesize,
			t.member_name, t.id_member, t.files, t.added, t.last_action, t.seeders, t.leechers, t.transfer,
			t.is_disabled, t.downloads, c.cat_name
		FROM {db_prefix}tracker_torrents AS t
			LEFT JOIN {db_prefix}tracker_category AS c ON (c.id_category = t.id_category)
		WHERE id_torrent = {int:torrent}
		LIMIT 1',
		array(
			'torrent' => $id_torrent,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['torrent'] = array(
		'name' => $row['name'],
		'description' => parse_bbc($row['description']),
		'download_as' => $row['torrentname'],
		'torrent_file' => $row['torrentfile'],
		'href' => $scripturl . '?action=tracker;torrent=' . $row['id_torrent'],
		'download_link' => '<a href="' . getDownloadURL($row['id_torrent']) . '">' . $row['torrentname'] . '</a>',
		'download_href' => getDownloadURL($row['id_torrent']),
		'delete_link' => '<a href="' . $scripturl . '?action=tracker;sa=delete;torrent=' . $row['id_torrent'] . '">' . $txt['tracker_delete'] . '</a>',
		'delete_href' => $scripturl . '?action=tracker;sa=delete;torrent=' . $row['id_torrent'],
		'size' => size_readable($row['filesize']),
		'size_bytes' => $row['filesize'],
		'files' => array(),
		'num_seeders' => $row['seeders'],
		'num_leechers' => $row['leechers'],
		'downloads' => $row['downloads'],
		'seeders' => array(),
		'leechers' => array(),
		'complete' => array(),
		'incomplete' => array(),
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

	$files = explode("\n", $row['files']);

	foreach ($files as $file)
	{
		list ($file, $size) = explode('|', $file);

		$context['torrent']['files'][] = array(
			'path' => $file,
			'size' => size_readable($size),
		);
	}

	unset($row);

	return true;
}

// Format
function ratioColor($ul, $dl, $html = true)
{
	if ($dl == 0 && $ul == 0)
		return '---';
	elseif ($dl == 0)
		return '∞';
	elseif ($ul == 0)
		$ratio = 0;
	else
		$ratio = $ul / $dl;

	if (!$html)
		return number_format($ratio, 2);

	$colors = array(
		array(0, 'ff0000'),
		array(0.1, 'ee0000'),
		array(0.2, 'dd0000'),
		array(0.3, 'cc0000'),
		array(0.4, 'bb0000'),
		array(0.5, 'aa0000'),
		array(0.7, '990000'),
		array(0.8, '880000'),
		array(0.9, '770000'),
		array(1, '007700'),
	);

	$color = '';

	foreach ($colors as $row)
	{
		list ($required, $color) = $row;

		if ($required > $ratio)
			break;
	}

	return '<span style="color: #' . $color . '">' . number_format($ratio, 2) . '</span>';
}

function size_readable($size, $unit = null, $retstring = null, $si = false)
{
	if (!is_numeric($size))
		return $size;

	if ($si === true)
	{
		$sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
		$mod   = 1000;
	}
	else
	{
		$sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
		$mod   = 1024;
	}
	$ii = count($sizes) - 1;

	$unit = array_search((string) $unit, $sizes);

	if ($unit === null || $unit === false)
		$unit = $ii;

	if ($retstring === null)
		$retstring = '%s %s';

	// Loop
	$i = 0;
	while ($unit != $i && $size >= 1000 && $i < $ii)
	{
		$size /= $mod;
		$i++;
	}

	return sprintf($retstring, number_format($size, 2), $sizes[$i]);
}

function duration_format($seconds, $limit = false)
{
	global $txt, $smcFunc;

	if (!$seconds)
		return '---';

	$seconds = abs((int) $seconds);

	$takes_time = array(
		array(
			's' => 31536000,
			't' => 'duration_year',
		),
		array(
			's' => 2626560,
			't' => 'duration_week',
		),
		array(
			's' => 604800,
			't' => 'duration_month',
		),
		array(
			's' => 86400,
			't' => 'duration_day',
		),
		array(
			's' => 3600,
			't' => 'duration_hour',
		),
		array(
			's' => 60,
			't' => 'duration_minute',
		),
		array(
			's' => 1,
			't' => 'duration_second',
		),
	);

	$output = array();

	foreach ($takes_time as $i => $info)
	{
		if ($seconds < $info['s'])
			continue;

		$s = floor($seconds / $info['s']);
		$seconds -= $s * $info['s'];

		$output[] = $s . ' ' . $txt[$info['t']];
	}

	if ($limit === false)
		return $smcFunc['ucfirst'](implode(' ', array_slice($output, 0, 2)));

	return $smcFunc['ucfirst'](implode(' ', array_slice($output, 0, $limit)));
}

// Passkey
function generatePasskey($id_member)
{
	global $db_prefix, $smcFunc;

	$passkey = md5(uniqid(rand(), true));

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
		SET passkey = {string:passkey}
		WHERE id_member = {int:member}',
		array(
			'passkey' => $passkey,
			'member' => $id_member,
		)
	);

	return $passkey;
}

function checkPasskey($passkey)
{
	global $user_info, $smcFunc, $user_info, $modSettings;

	if (empty($modSettings['enablePasskey']) || empty($passkey))
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}members
		WHERE passkey = {string:passkey}
		LIMIT 1',
		array(
			'passkey' => $passkey
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;

	list ($id_member) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $id_member;
}

function getPassKey($id_member = false, $insert = false)
{
	global $user_info, $db_prefix, $smcFunc, $modSettings;

	if (empty($modSettings['enablePasskey']))
		return false;

	// Use the current user?
	if ($id_member == false && !empty($user_info['passkey']))
		return $user_info['passkey'];
	elseif ($id_member == false && $insert)
		return generatePasskey($user_info['id']);
	elseif ($id_member == false)
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT passkey
		FROM {db_prefix}members
		WHERE id_member = {int:member}
		LIMIT 1',
		array(
			'member' => $id_member
		)
	);

	list ($passkey) = $smcFunc['db_fetch_row']($request);

	if (empty($passkey))
		return generatePasskey($id_member);

	$smcFunc['db_free_result']($request);

	if (empty($passkey))
		return false;

	return $passkey;
}

// URL
function getDownloadURL($id, $id_member = false, $full = false)
{
	global $modSettings, $user_info, $scripturl;

	if ($id_member == false)
		$id_member = $user_info['id'];

	if (empty($modSettings['enablePasskey']) || !$full || $id_member == 0)
		return $scripturl . '?action=tracker;sa=download;torrent=' . $id;

	return $scripturl . '?action=tracker;sa=download;torrent=' . $id . ';p=' . getPassKey($id_member, true);
}

function getAnnounceURL($id_member = false)
{
	global $user_info, $scripturl, $boardurl, $modSettings;

	if (empty($modSettings['enablePasskey']) || $id_member === 0)
		return $boardurl . '/announce.php';

	if ($id_member == false)
		$id_member = $user_info['id'];

	return $boardurl . '/announce.php?p=' . getPassKey($id_member, true);
}

// Peer ID
function parsePeerId($id)
{
	// Azureus style
	if (substr($id, 0, 1) == '-')
	{
		$ohjelmat = array(
			'AG' => 'Ares',
			'A~' => 'Ares',
			'AR' => 'Arctic',
			'AV' => 'Avicora',
			'AX' => 'BitPump',
			'AZ' => 'Azureus',
			'BB' => 'BitBuddy',
			'BC' => 'BitComet',
			'BF' => 'Bitflu',
			'BG' => 'BTG (uses Rasterbar libtorrent)',
			'BR' => 'BitRocket',
			'BS' => 'BTSlave',
			'BX' => '~Bittorrent X',
			'CD' => 'Enhanced CTorrent',
			'CT' => 'CTorrent',
			'DE' => 'DelugeTorrent',
			'DP' => 'Propagate Data Client',
			'EB' => 'EBit',
			'ES' => 'electric sheep',
			'HL' => 'Halite',
			'HN' => 'Hydranode',
			'KT' => 'KTorrent',
			'LH' => 'LH-ABC',
			'LP' => 'Lphant',
			'LT' => 'libtorrent',
			'lt' => 'libTorrent',
			'MP' => 'MooPolice',
			'MT' => 'MoonlightTorrent',
			'PD' => 'Pando',
			'qB' => 'qBittorrent',
			'QT' => 'Qt 4 Torrent example',
			'RT' => 'Retriever',
			'S~' => 'Shareaza alpha/beta',
			'SB' => '~Swiftbit',
			'SS' => 'SwarmScope',
			'ST' => 'SymTorrent',
			'st' => 'sharktorrent',
			'SZ' => 'Shareaza',
			'TN' => 'TorrentDotNET',
			'TR' => 'Transmission',
			'TS' => 'Torrentstorm',
			'TT' => 'TuoTu',
			'UL' => 'uLeecher!',
			'UT' => 'µTorrent',
			'XL' => 'Xunlei',
			'XT' => 'XanTorrent',
			'XX' => 'Xtorrent',
			'ZT' => 'ZipTorrent'
		);

		$versio = array($id{3}, $id{4}, $id{5}, $id{6});

		if ($versio[3] == 'b')
		{
			unset($versio[3]);
			$versio[2] .= 'b';
		}

		if (isset($ohjelmat[substr($id, 1, 2)]))
			$ohjelma = $ohjelmat[substr($id, 1, 2)] . '/' . implode('.', $versio);
		else
			$ohjelma = substr($id, 1, 2) . '/' . implode('.', $versio);
	}
	elseif (substr($id, 0, 4) == 'exbc')
		$ohjelma = 'BitComet/' . bindec($id{4}) . '.' . bindec($id{5});
	// Opera
	elseif (substr($id, 0, 2) == 'OP')
		$ohjelma = 'Opera/' . substr($id, 3, 4);
	// XBT
	elseif (substr($id, 0, 3) == 'XBT')
		$ohjelma = 'XBT Client/' . $id{3} . '.'. $id{4} . '.'. $id{5};
	// Shadow/Mainline style?
	else
	{
		$ohjelmat = array(
			'A' => 'ABC',
			'O' => 'Osprey Permaseed',
			'Q' => 'BTQueue',
			'R' => 'Tribler',
			'S' => 'Shadow\'s client',
			'T' => 'BitTornado',
			'U' => 'UPnP NAT Bit Torrent',
			'M' => 'Mainline',
		);

		$shad = substr($id, 0, 1);

		if (isset($ohjelmat[$shad]))
		{
			$char = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz.-';

			$v = array();

			for ($i = 1; $i <= 7; $i++)
			{
				if ($shad != 'M' && $i == 6)
					break;

				if ($id{$i} == '-' && $shad == 'M')
				{
					//$v[] = 0;
					continue;
				}
				else
				{
					if ($shad == 'M')
						$v[] = $id{$i};
					else
						$v[] = strpos($char, $id{$i});
				}
			}

			$ohjelma = $ohjelmat[$shad] . '/' . implode('.', $v);
		}
	}
	if (empty($ohjelma))
		return 'unknown';

	return $ohjelma;
}

// binary
function hex2bin($hex)
{
	$result = '';
	for ($i = 0; $i < strlen($hex); $i += 2)
		$result .= chr(hexdec(substr($hex,$i,2)));
	return $result;
}

// Member functions
function memberStatUpdate($id_member = 0)
{
	global $user_info, $smcFunc, $db_prefix;

	if ($id_member == 0)
		$id_member = $user_info['id'];

	$member = array();

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE id_member = {int:member}
			AND download_left = 0',
		array(
			'member' => $id_member,
		)
	);
	list ($member['active_seeds']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE id_member = {int:member}
			AND download_left > 0',
		array(
			'member' => $id_member,
		)
	);
	list ($member['active_leechs']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
		SET
			active_seeders = {int:seeding},
			active_leechers = {int:leeching}
		WHERE id_member = {int:member}',
		array(
			'seeding' => $member['active_seeds'],
			'leeching' => $member['active_leechs'],
			'member' => $id_member,
		)
	);
}

// Torrent functions
function torrentStatUpdate($tid)
{
	global $db_prefix;

	$torrent = array();

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE id_torrent = {int:torrent}
			AND download_left = 0',
		array(
			'torrent' => $tid,
		)
	);
	list ($torrent['seeders']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE id_torrent = {int:torrent}
			AND download_left > 0',
		array(
			'torrent' => $tid,
		)
	);
	list ($torrent['leechers']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}tracker_torrents
		SET
			seeders = {int:seeders},
			leechers = {int:leechers}
		WHERE id_torrent = {int:torrent}',
		array(
			'torrent' => $tid,
			'seeders' => $torrent['seeders'],
			'leechers' => $torrent['leechers'],
		)
	);
}

// Peer table cleanup
function cleanOldPeers($limit = 5200)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT DISTINCT id_member
		FROM {db_prefix}tracker_peers
		WHERE last_action < {int:time_limit}',
		array(
			'time_limit' => time() - $limit,
		)
	);

	$members = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$members[] = $row['id_member'];

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}tracker_peers
		WHERE last_action < {int:time_limit}',
		array(
			'time_limit' => time() - $limit,
		)
	);

	$request = $smcFunc['db_query']('', '
		SELECT id_torrent, COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE download_left = 0
		GROUP BY id_torrent'
	);

	$torrents = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($torrent[$row['id_torrent']]))
			$torrents[$row['id_torrent']] = array(
				'seeders' => 0,
				'leechers' => 0,
			);

		$torrents[$row['id_torrent']]['seeders'] = $row['cnt'];
	}
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT id_torrent, COUNT(*) AS cnt
		FROM {db_prefix}tracker_peers
		WHERE download_left > 0
		GROUP BY id_torrent'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($torrents[$row['id_torrent']]))
			$torrents[$row['id_torrent']] = array(
				'seeders' => 0,
				'leechers' => 0,
			);

		$torrents[$row['id_torrent']]['leechers'] = $row['cnt'];
	}
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}tracker_torrents
		SET seeders = 0, leechers = 0'
	);

	foreach ($torrents as $id => $torrent)
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}tracker_torrents
			SET
				seeders = {int:seeders},
				leechers = {int:leechers}
			WHERE id_torrent = {int:torrent}',
			array(
				'torrent' => $id,
				'seeders' => $torrent['seeders'],
				'leechers' => $torrent['leechers'],
			)
		);

	foreach ($members as $member)
		memberStatUpdate($member);
}

// Output
function btOutput($outdata, $type = 'resp', $name = '')
{
	if ($type == 'resp')
	{
		header('Content-Type: text/plain');

		$gzip = $name != 'no-gzip' && ($name == 'gzip' || $_SERVER['HTTP_ACCEPT_ENCODING'] == 'gzip');

		if ($gzip)
			header('Content-Encoding: gzip');

		echo BEncode(btOutputSort($outdata), $gzip);
	}
	else
	{
		header('Content-Type: application/x-bittorrent');
		header('Content-Disposition: attachment; filename="' . $name . '"');

		echo BEncode($outdata);
	}

	obExit(false);
}

function btOutputSort($array)
{
	$i = 0;

	if (empty($array))
		return $array;

	foreach($array as $key => $value)
		$keys[$i++] = $key;

	$k2 = $keys;
	sort($keys);

	for ($i=0 ; isset($keys[$i]); $i++)
	{
		if ($k2[$i] != $keys[$i])
			log_error('Wrong order: ' .$keys[$i] . ' Was: ' . implode(',', $k2) . ' Correct: ' . implode(',', $keys));

		$return[$keys[$i]] = $array[$keys[$i]];
	}

	return $return;
}

function getPeerlist($where, $type, &$values)
{
	global $smcFunc;

	if ($type != 'compact')
		$peers = array();
	else
		$peers = '';

	if ($type != 'full')
		$columns = 'ip, port';
	else
		$columns = 'peer_id, ip, port';

	$request = $smcFunc['db_query']('', '
		SELECT ' . $columns . '
		FROM {db_prefix}tracker_peers
		WHERE ' . $where . '
		ORDER BY RAND()
		LIMIT 15',
		$values
	);

	// Collect datas about the peer.
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($type == 'full')
			$peers[] = array(
				'ip' => $row['ip'],
				'peer id' => hex2bin($row['peer_id']),
				'port' => (int) $row['port']
			);
		elseif ($type == 'no_peer_id')
			$peers[] = array(
				'ip' => $row['ip'],
				'port' => (int) $row['port']
			);
		else
		{
			$ip = explode('.', $row['ip']);
			if (count($ip) != 4)
			{
				log_error($row['ip']);
				continue;
			}

			$peers .= pack('C*', $ip[0], $ip[1], $ip[2], $ip[3]) . pack('n*', $row['port']);
		}
	}
	$smcFunc['db_free_result']($request);

	return $peers;
}

function parseClientInfo()
{
	global $modSettings, $txt;

	$client = array();

	if (empty($_REQUEST['peer_id']) || strlen($_REQUEST['peer_id']) != 20)
		btFatalError($txt['invalid_request']);

	$client['peer_id'] = bin2hex(trim($_REQUEST['peer_id']));

	if (empty($modSettings['enablePasskey']))
		parseClientInfo2($client);

	if (isset($_REQUEST['p']))
	{
		$passkey = $_REQUEST['p'];

		$id_member = checkPasskey($passkey);

		if ($id_member !== false)
			$client += array(
				'id' => (int) $id_member,
				'passkey' => $passkey,
				'cheater' => false,
				'ip' => $_SERVER['REMOTE_ADDR'],
			);
		else
			parseClientInfo2($client, true);

		return $client;
	}

	parseClientInfo2($client, true);

	return $client;
}

function parseClientInfo2(&$client, $cheater = false)
{
	global $smcFunc, $user_info, $txt;

	$ip = array_unique(array($_SERVER['REMOTE_ADDR'], $user_info['ip']));

	$request = $smcFunc['db_query']('', '
		SELECT p.id_member, IFNULL(mem.real_name, IFNULL(member_name, {string:empty})) AS name
		FROM {db_prefix}tracker_peers AS p
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = p.id_member)
		WHERE peer_id = {string:peer_id}',
		array(
			'peer_id' => $client['peer_id'],
			'empty' => '',
		)
	);

	if ($smcFunc['db_num_rows']($request) > 0)
		list ($uid, $username) = $smcFunc['db_fetch_row']($request);
	else
	{
		$request2 = $smcFunc['db_query']('', '
			SELECT p.id_member, IFNULL(mem.real_name, IFNULL(mem.member_name, {string:empty})) AS name
			FROM {db_prefix}tracker_peers AS p
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = p.id_member)
			WHERE ip IN({array_string:ip})
			LIMIT 1',
			array(
				'ip' => $ip,
				'empty' => '',
			)
		);

		if ($smcFunc['db_num_rows']($request2) > 0)
			list ($uid, $username) = $smcFunc['db_fetch_row']($request2);
		else
		{
			$request3 = $smcFunc['db_query']('', '
				SELECT id_member, IFNULL(real_name, IFNULL(member_name, {string:empty}))
				FROM {db_prefix}members
				WHERE member_ip IN({array_string:ip})
				LIMIT 1',
				array(
					'ip' => $ip,
					'empty' => '',
				)
			);

			if ($smcFunc['db_num_rows']($request3) > 0)
				list ($uid, $username) = $smcFunc['db_fetch_row']($request3);
			else
			{
				list ($uid, $username) = array(0, '');

				// /me assumes you wanna cheat
				$cheater = true;
			}
			$smcFunc['db_free_result']($request3);
		}
		$smcFunc['db_free_result']($request2);
	}
	$smcFunc['db_free_result']($request);

	$client['id'] = $uid;
	$client['passkey'] = '';
	$client['cheater'] = $cheater;
	$client['ip'] = $_SERVER['REMOTE_ADDR'];
}

function btFatalError($reason)
{
	btOutput(array(
		'failure reason' => $reason
	));
}

function createTorrent($metaData, $torrentOptions, $posterOptions = array())
{
	global $smcFunc, $user_info, $modSettings;

	if (is_string($metaData))
		$metaData = BDecode($metaData);
	elseif (!is_array($metaData))
		return false;

	if (!isset($metaData['info']) || !isset($metaData['info']['piece length']) || !isset($metaData['info']['pieces']) || !isset($metaData['info']['name']))
		return false;

	if (empty($posterOptions))
		$posterOptions = array(
			'id' => $user_info['id'],
			'name' => $user_info['name'],
		);

	$is_external = 0;
	$tracker = '';

	if (!empty($modSettings['trackerAllowExternal']) && !empty($metaData['announce']) && $metaData['announce'] != getAnnounceURL(0))
	{
		$is_external = 1;
		$tracker = $metaData['announce'];
	}
	// Disable DHT for tracking stats
	else
	{
		$metaData['announce'] = getAnnounceURL(0);
		$metaData['info']['private'] = 1;
	}

	$files = array();
	$size = (float) 0;

	// Single file
	if (isset($metaData['info']['length']))
	{
		$size = (float) $metaData['info']['length'];
		$files[] = $metaData['info']['name'] . '|' . $metaData['info']['length'];
	}
	// Multiple files
	elseif (isset($metaData['info']['files']))
	{
		foreach ($metaData['info']['files'] as $file)
		{
			$size += (float) $file['length'];
			$files[] = implode('/', $file['path']) . '|' . $file['length'];
		}
	}

	$infoHash = sha1(BEncode($metaData['info']));

	$request = $smcFunc['db_query']('', '
		SELECT id_torrent
		FROM {db_prefix}tracker_torrents
		WHERE info_hash = {string:info_hash}',
		array(
			'info_hash' => $infoHash,
		)
	);

	if ($smcFunc['db_num_rows']($request) != 0)
	{
		list ($id_torrent) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $id_torrent;
	}
	$smcFunc['db_free_result']($request);

	$torrentFile = $user_info['id'] . '-' . $infoHash . '.torrent';
	file_put_contents($modSettings['torrentDirectory'] . '/' . $torrentFile, BEncode($metaData));

	$smcFunc['db_insert']('insert',
		'{db_prefix}tracker_torrents',
		array(
			'id_category' => 'int',
			'name' => 'string',
			'torrentname' => 'string',
			'torrentfile' => 'string',
			'description' => 'string',
			'info_hash' => 'string-60',
			'filesize' => 'float',
			'files' => 'string',
			'member_name' => 'string',
			'id_member' => 'int',
			'added' => 'int',
			'last_action' => 'int',
			'last_seeder' => 'int',
			'seeders' => 'int',
			'leechers' => 'int',
			'transfer' => 'int',
			'is_disabled' => 'int',
			'is_external' => 'int',
			'external_tracker' => 'string-255'
		),
		array(
			empty($torrentOptions['category']) ? 0 : $torrentOptions['category'],
			empty($torrentOptions['name']) ? $metaData['info']['name'] : $torrentOptions['name'],
			empty($torrentOptions['torrent_name']) ? $metaData['info']['name'] : basename($torrentOptions['torrent_name'], '.torrent'),
			$torrentFile,
			empty($torrentOptions['description']) ? '' : $torrentOptions['description'],
			$infoHash,
			$size,
			implode("\n", $files),
			$posterOptions['name'],
			$posterOptions['id'],
			time(),
			time(),
			0,
			0,
			0,
			0,
			0,
			$is_external,
			$tracker,
		),
		array('id_torrent')
	);

	$id_torrent = $smcFunc['db_insert_id']('{db_prefix}tracker_torrents', 'id_torrent');

	return $id_torrent;
}

function deleteTorrent($id_torrent)
{
	global $smcFunc, $user_info, $modSettings;

	if (!is_array($id_torrent))
		$id_torrent = array((int) $id_torrent);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}tracker_torrents
		WHERE id_torrent IN({array_int:torrent})',
		array(
			'torrent' => $id_torrent,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}tracker_peers
		WHERE id_torrent IN({array_int:torrent})',
		array(
			'torrent' => $id_torrent,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}tracker_downloaders
		WHERE id_torrent IN({array_int:torrent})',
		array(
			'torrent' => $id_torrent,
		)
	);
}

?>
