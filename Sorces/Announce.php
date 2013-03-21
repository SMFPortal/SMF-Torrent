<?php
/**********************************************************************************
* Announce.php                                                                    *
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

function AnnounceMain()
{
  global $smcFunc, $user_info, $txt, $modSettings;

	$client = parseClientInfo();

	$user_info['ip'] = $client['ip'];
	$user_info['id'] = $client['id'];

	if (isset($_REQUEST['info_hash']))
		$info_hash = bin2hex($_REQUEST['info_hash']);
	else
		btFatalError($txt['invalid_request']);

	if ($client['cheater'])
		btFatalError($txt['tracker_not_authorized']);

	// Load torrent
	$request = $smcFunc['db_query']('', '
		SELECT id_torrent, seeders, leechers
		FROM {db_prefix}tracker_torrents
		WHERE info_hash = {string:info_hash}',
		array(
			'info_hash' => $info_hash,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
	{
		$smcFunc['db_free_result']($request);

		btFatalError($txt['torrent_not_authorized'] . ' (' . $info_hash . strlen($_REQUEST['info_hash']) .  ')');
	}

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$torrent = array(
		'id' => $row['id_torrent'],
		'seeders' => (int) $row['seeders'],
		'leechers' => (int) $row['leechers'],
	);

	unset($row);

	$client['left'] = (float) $_GET['left'];
	$client['port'] = (int) $_GET['port'];
	$client['event'] = isset($_GET['event']) ? $_GET['event'] : '';

	$client['is_seeder'] = $client['left'] == 0;
	$client['mode'] = $client['is_seeder'] ? 'seeders' : 'leechers';

	$client['uploaded'] = (float) $_GET['uploaded'];
	$client['downloaded'] = (float) $_GET['downloaded'];
	$client['agent'] = $_SERVER['HTTP_USER_AGENT'];

	$updatePeers = false;

	$time = time();

	$torrentUpdates = array();
	$downloadUpdates = array();
	$memberUpdates = array();

	// Values for all queries
	$values = array(
		'time_now' => $time,
		'event' => $client['event'],
		'download_left' => $client['left'],
		'downloaded' => $client['downloaded'],
		'uploaded' => $client['uploaded'],
		'peer_id' => $client['peer_id'],
		'torrent' => $torrent['id'],
		'member' => $client['id'],
		'new_upload' => 0,
		'new_download' => 0,
		'upload_rate' => 0,
		'download_rate' => 0,
		'time_elapsed' => 1,
	);

	if ($client['is_seeder'])
		$torrentUpdates['last_seeder'] = '{int:time_now}';

	if ($client['event'] == 'completed')
	{
		$torrentUpdates['downloads'] = 'downloads + 1';
		$downloadUpdates['complete_time'] = '{int:time_now}';
	}

	$request = $smcFunc['db_query']('', '
		SELECT
			peer_id, id_member, last_action, event,
			download_left, ip, port, downloaded, uploaded
		FROM {db_prefix}tracker_peers
		WHERE peer_id = {string:peer_id}
			AND id_torrent = {int:torrent}
			AND id_member = {int:member}',
		array(
			'peer_id' => $client['peer_id'],
			'torrent' => $torrent['id'],
			'member' => $client['id']
		)
	);

	// Update peer
	if ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// How long it was from last announce?
		$values['time_elapsed'] = $time - $row['last_action'];

		if ($values['time_elapsed'] < 1)
			$values['time_elapsed'] = 1;

		// What we need to update?
		$values['new_upload'] = $client['uploaded'] - $row['uploaded'];
		$values['new_download'] = $client['downloaded'] - $row['downloaded'];

		// How fast?
		$values['upload_rate'] = $values['new_upload'] / $values['time_elapsed'];
		$values['download_rate'] = $values['new_download'] / $values['time_elapsed'];

		// TODO: Move these directy to memberUpdate query?
		if ($values['upload_rate'] > 0)
			$memberUpdates['ulrate_max'] = "IF(ulrate_max < {float:upload_rate}, {float:upload_rate}, ulrate_max)";

		if ($values['download_rate'] > 0)
			$memberUpdates['dlrate_max'] = "IF(dlrate_max < {float:download_rate}, {float:download_rate}, dlrate_max)";

		$client['mode2'] = $row['download_left'] == 0 ? 'seeders' : 'leechers';

		// Stopped
		if ($client['event'] == 'stopped')
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}tracker_peers
				WHERE peer_id = {string:peer_id}
					AND id_torrent = {int:torrent}
					AND id_member = {int:member}',
				$values
			);

			//$updatePeers = true;

			$torrentUpdates[$client['mode2']] = "$client[mode2] - 1";
			$memberUpdates['active_' . $client['mode2']] = "active_$client[mode2] - 1";
		}
		else
		{
			if ($client['mode2'] != $client['mode'])
			{
				$torrentUpdates[$client['mode2']] = "$client[mode2] - 1";
				$memberUpdates['active_' . $client['mode2']] = "active_$client[mode2] - 1";

				$torrentUpdates[$client['mode']] = "$client[mode] + 1";
				$memberUpdates['active_' . $client['mode']] = "active_$client[mode] + 1";
			}

			$smcFunc['db_query']('', '
				UPDATE {db_prefix}tracker_peers
				SET
					last_action = {int:time_now},
					event = {string:event},
					download_left = {float:download_left},
					downloaded = {float:downloaded},
					uploaded = {float:uploaded},
					ulrate = {float:upload_rate},
					dlrate = {float:download_rate},
					ulrate_max = IF(ulrate_max < {float:upload_rate}, {float:upload_rate}, ulrate_max),
					dlrate_max = IF(dlrate_max < {float:download_rate}, {float:download_rate}, dlrate_max)
				WHERE peer_id = {string:peer_id}
					AND id_torrent = {int:torrent}
					AND id_member = {int:member}',
				$values
			);
		}
	}
	// It's new peer (or old whose connection has dropped)
	elseif ($client['event'] != 'stopped')
	{
		$torrentUpdates[$client['mode']] = "$client[mode] + 1";
		$memberUpdates['active_' . $client['mode']] = "active_$client[mode] + 1";

		$smcFunc['db_insert'](
			'insert',
			'{db_prefix}tracker_peers',
			array(
				'id_torrent' => 'int',
				'id_member' => 'int',
				'peer_id' => 'string',
				'agent' => 'string-60',
				'ip' => 'string-15',
				'port' => 'int',
				'last_action' => 'int',
				'event' => 'string-10',
				'download_left' => 'float',
				'downloaded' => 'float',
				'uploaded' => 'float',
				'ulrate_max' => 'float',
				'dlrate_max' => 'float',
				'ulrate' => 'float',
				'dlrate' => 'float',
			),
			array(
				$torrent['id'],
				$client['id'],
				$client['peer_id'],
				$client['agent'],
				$client['ip'],
				$client['port'],
				$values['time_now'],
				$values['event'],
				$values['download_left'],
				$values['downloaded'],
				$values['uploaded'],
				0,
				0,
				0,
				0,
			),
			array('peer_id', 'id_member', 'id_torrent')
		);
	}

	updateTorrentAnnounce($torrent, $values, $torrentUpdates, $updatePeers);

	// Stats table
	$request = $smcFunc['db_query']('', '
		SELECT start_time, complete_time
		FROM {db_prefix}tracker_downloaders
		WHERE id_torrent = {int:torrent}
			AND id_member = {int:member}', $values);

	if ($smcFunc['db_num_rows']($request) == 0)
	{
		// Updates many fields that shouldnd be 0 in case of data has been cleared by stupid admin?
		$smcFunc['db_insert'](
			'insert',
			'{db_prefix}tracker_downloaders',
			array(
				'id_torrent' => 'int',
				'id_member' => 'int',
				'start_time' => 'int',
				'last_action' => 'int',
				'seed_time' => 'int',
				'leech_time' => 'int',
				'download_left' => 'float',
				'downloaded' => 'float',
				'uploaded' => 'float',
				'complete_time' => 'int',
			),
			array(
				$torrent['id'],
				$client['id'],
				$time,
				$time,
				$client['is_seeder'] ? $values['time_elapsed'] : 0,
				!$client['is_seeder'] ? $values['time_elapsed'] : 0,
				$values['download_left'],
				$values['new_download'],
				$values['new_upload'],
				$client['is_seeder'] ? time() : 0,
			),
			array('id_torrent', 'id_member')
		);
	}
	else
	{
		$row = $smcFunc['db_fetch_assoc']($request);

		if ($row['start_time'] == 0)
			$downloadUpdates['start_time'] = $time;

		if ($row['complete_time'] == 0 && $client['is_seeder'])
			$downloadUpdates['complete_time'] = $time;

		// There's many dynamic fields
		$values['download_updates'] = '';
		if (!empty($downloadUpdates))
		{
			foreach ($downloadUpdates as $column => $value)
				$values['download_updates'] .= ",
				$column = $value";

			$values['download_updates'] = $smcFunc['db_quote']($values['download_updates'], $values);
		}

		$values['timefield'] = $client['is_seeder'] ? 'seed_time' : 'leech_time';

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}tracker_downloaders
			SET
				downloaded = downloaded + {float:new_download},
				uploaded = uploaded + {float:new_upload},
				download_left = IF(download_left < {float:download_left}, download_left, {float:download_left}),
				{raw:timefield} = {raw:timefield} + {int:time_elapsed}{raw:download_updates}
			WHERE id_torrent = {int:torrent}
				AND id_member = {int:member}',
			$values
		);

		unset($values['download_updates'], $downloadUpdates);
	}
	$smcFunc['db_free_result']($request);

	// There's many dynamic fields
	$values['member_updates'] = '';
	if (!empty($memberUpdates))
	{
		foreach ($memberUpdates as $column => $value)
			$values['member_updates'] .= ",
			$column = $value";

		$values['member_updates'] = $smcFunc['db_quote']($values['member_updates'], $values);
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
		SET
			downloaded = downloaded + {float:new_download},
			uploaded = uploaded + {float:new_upload}{raw:member_updates}
		WHERE id_member = {int:member}',
		$values
	);

	// Bye! Bye!
	unset($values['member_updates'], $memberUpdates);

	// Delete old peers!
	if (empty($modSettings['peersTime']) || time() - $modSettings['peersTime'] > 8600)
	{
		// This will make it less likely for multiple updates
		updateSettings(array('peersTime' => time()));
		cleanOldPeers(4200);
	}

	// If stopped then we don't need to give more peers
	if ($client['event'] == 'stopped')
	{
		btOutput(array(
			'complete' => (int) $torrent['seeders'],
			'incomplete' => (int) $torrent['leechers'],
			'interval' => 1200,
			'min interval' => 600,
			'peers' => array(),
		));
	}

	// Build the output data.
	$values['timelimit'] = time() - 3600;

	if (!$client['is_seeder'])
		$where = "id_torrent = {int:torrent}
			AND NOT peer_id = {string:peer_id}
			AND last_action > {int:timelimit}";
	else
		$where = "id_torrent = {int:torrent}
			AND NOT peer_id = {string:peer_id}
			AND last_action > {int:timelimit}
			AND download_left > 0";

	$outdata = array(
		'complete' => (int) $torrent['seeders'],
		'incomplete' => (int) $torrent['leechers'],
		'interval' => (int) 1200,
		'min interval' => (int) 600,
		'peers' => getPeerlist(
			$where,
			isset($_REQUEST['compact']) ? 'compact' : (isset($_REQUEST['no_peer_id']) ? 'no_peer_id' : 'full'),
			$values),
	);

	btOutput($outdata);
}

function updateTorrentAnnounce($torrent, $values, $torrentUpdates, $updatePeers)
{
	global $smcFunc;

	if ($updatePeers)
	{
		$request = $smcFunc['db_query']('', '
			SELECT DISTINCT id_member
			FROM {db_prefix}tracker_peers
			WHERE last_action < {int:time_limit}
				AND id_torrent = {int:torrent}',
			array(
				'time_limit' => time() - 4200,
				'torrent' => $values['torrent']
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			memberStatUpdate($row['id_member']);
		$smcFunc['db_free_result']($request);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}tracker_peers
			WHERE last_action < {int:time_limit}
				AND id_torrent = {int:torrent}',
			array(
				'time_limit' => time() - 4200,
				'torrent' => $values['torrent']
			)
		);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}tracker_peers
			WHERE download_left = 0
			AND id_torrent = {int:torrent}',
			array(
				'torrent' => $values['torrent']
			)
		);

		list ($torrentUpdates['seeders']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}tracker_peers
			WHERE download_left > 0
			AND id_torrent = {int:torrent}',
			array(
				'torrent' => $values['torrent']
			)
		);
		list ($torrentUpdates['leechers']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	}

	// There's many dynamic fields
	$values['torrent_updates'] = '';
	if (!empty($torrentUpdates))
	{
		foreach ($torrentUpdates as $column => $value)
			$values['torrent_updates'] .= ",
			$column = $value";

		$values['torrent_updates'] = $smcFunc['db_quote']($values['torrent_updates'], $values);
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}tracker_torrents
		SET
			last_action = {int:time_now},
			transfer = transfer + {float:new_upload}{raw:torrent_updates}
		WHERE id_torrent = {int:torrent}',
		$values
	);

	// Bye! Bye!
	unset($values['torrent_updates']);
}
?>
