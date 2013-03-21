<?php
/**********************************************************************************
* TorrentDatabase.php                                                             *
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

$tracker_version = '0.1 Alpha rev 108';

$addSettings = array(
	'torrentDirectory' => array($boarddir . '/Torrent', false),
	'enableTracker' => array(true, false),
	'trackerAllowExternal' => array(false, false),
	'enablePasskey' => array(true, false),
	'scrapePasskey' => array(false, false),
	'checkConnectable' => array(true, false),
);

$permissions = array(

);

$tables = array(
	'members' => array(
		'name' => 'members',
		'smf' => true,
		'columns' => array(
			array(
				'name' => 'passkey',
				'type' => 'varchar',
				'size' => 32,
			),
			array(
				'name' => 'active_leechers',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'active_seeders',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'tracker_connectable',
				'type' => 'int',
				'size' => 1,
				'unsigned' => true,
			),
			array(
				'name' => 'dlrate_max',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'ulrate_max',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'downloaded',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
			array(
				'name' => 'uploaded',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'name' => 'passkey',
				'type' => 'index',
				'columns' => array('passkey')
			),
		)
	),
	'tracker_category' => array(
		'name' => 'tracker_category',
		'columns' => array(
			array(
				'name' => 'id_category',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'cat_name',
				'type' => 'varchar',
				'size' => 50,
			),
			array(
				'name' => 'cat_order',
				'type' => 'int',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_category')
			),
		),
	),
	'tracker_downloaders' => array(
		'name' => 'tracker_downloaders',
		'columns' => array(
			array(
				'name' => 'id_torrent',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'start_time',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'complete_time',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'last_action',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'leech_time',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'seed_time',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'downloaded',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
			array(
				'name' => 'uploaded',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
			array(
				'name' => 'download_left',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_torrent', 'id_member')
			),
			array(
				'type' => 'index',
				'columns' => array('id_member')
			),
			array(
				'type' => 'index',
				'columns' => array('id_torrent')
			),
		),
	),
	'tracker_torrents' => array(
		'name' => 'tracker_torrents',
		'columns' => array(
			array(
				'name' => 'id_torrent',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_category',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'torrentname',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'torrentfile',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'description',
				'type' => 'text',
			),
			array(
				'name' => 'info_hash',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'filesize',
				'type' => 'bigint',
				'size' => 20,
				'unsigned' => true,
			),
			array(
				'name' => 'files',
				'type' => 'text',
			),
			array(
				'name' => 'member_name',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'added',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'last_action',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'last_seeder',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'seeders',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'leechers',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'downloads',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'transfer',
				'type' => 'bigint',
				'size' => 30,
				'unsigned' => true,
			),
			array(
				'name' => 'is_disabled',
				'type' => 'int',
				'size' => 2,
				'unsigned' => true,
			),
			array(
				'name' => 'is_external',
				'type' => 'int',
				'size' => 2,
				'unsigned' => true,
			),
			array(
				'name' => 'external_tracker',
				'type' => 'varchar',
				'size' => 60,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_torrent')
			),
			array(
				'type' => 'unique',
				'columns' => array('info_hash')
			),
		),
	),
	'tracker_peers' => array(
		'name' => 'tracker_peers',
		'columns' => array(
			array(
				'name' => 'peer_id',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_torrent',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'agent',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'event',
				'type' => 'varchar',
				'size' => 10,
			),
			array(
				'name' => 'last_action',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'ip',
				'type' => 'varchar',
				'size' => 24,
			),
			array(
				'name' => 'port',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'download_left',
				'type' => 'bigint',
				'size' => 20,
				'unsigned' => true,
			),
			array(
				'name' => 'downloaded',
				'type' => 'bigint',
				'size' => 20,
				'unsigned' => true,
			),
			array(
				'name' => 'uploaded',
				'type' => 'bigint',
				'size' => 20,
				'unsigned' => true,
			),
			array(
				'name' => 'dlrate',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'ulrate',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'dlrate_max',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'ulrate_max',
				'type' => 'int',
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('peer_id', 'id_member', 'id_torrent')
			),
			array(
				'type' => 'index',
				'columns' => array('id_torrent')
			)
		),
	),
);

// Functions
function doTables($tbl, $tables, $columnRename = array(), $smf2 = true)
{
	global $smcFunc, $db_prefix, $db_type;

	foreach ($tables as $table)
	{
		$table_name = $db_prefix . $table['name'];

		if (!empty($columnRename))
		{
			$table = $smcFunc['db_table_structure']($table_name);

			foreach ($table['columns'] as $column)
			{
				if (isset($columnRename[$column['name']]))
				{
					$old_name = $column['name'];
					$column['name'] = $columnRename[$column['name']];
					$smcFunc['db_change_column']($table_name, $old_name, $column, array('no_prefix' => true));
				}
			}
		}

		if (empty($table['smf']))
			$smcFunc['db_create_table']($table_name, $table['columns'], $table['indexes'], array('no_prefix' => true));

		if (in_array($table_name, $tbl))
		{
			foreach ($table['columns'] as $column)
			{
				$smcFunc['db_add_column']($table_name, $column, array('no_prefix' => true));

				// TEMPORARY until SMF package functions works with this
				if (isset($column['unsigned']) && $db_type == 'mysql')
				{
					$column['size'] = isset($column['size']) ? $column['size'] : null;

					list ($type, $size) = $smcFunc['db_calculate_type']($column['type'], $column['size']);
					if ($size !== null)
						$type = $type . '(' . $size . ')';

					$smcFunc['db_query']('', "
						ALTER TABLE $table_name
						CHANGE COLUMN $column[name] $column[name] $type UNSIGNED " . (empty($column['null']) ? 'NOT NULL' : '') . ' ' .
							(empty($column['default']) ? '' : "default '$column[default]'") . ' ' .
							(empty($column['auto']) ? '' : 'auto_increment') . ' ',
						'security_override'
					);
				}
			}

			// Update table
			foreach ($table['indexes'] as $index)
			{
				if ($index['type'] != 'primary')
					$smcFunc['db_add_index']($table_name, $index, array('no_prefix' => true));
			}
		}
	}
}

function doSettings($addSettings, $smf2 = true)
{
	global $smcFunc;

	$update = array();

	foreach ($addSettings as $variable => $s)
	{
		list ($value, $overwrite) = $s;

		$result = $smcFunc['db_query']('', '
			SELECT value
			FROM {db_prefix}settings
			WHERE variable = {string:variable}',
			array(
				'variable' => $variable,
			)
		);

		if ($smcFunc['db_num_rows']($result) == 0 || $overwrite == true)
			$update[$variable] = $value;
	}

	if (!empty($update))
		updateSettings($update);
}

function doPermission($permissions, $smf2 = true)
{
	global $smcFunc;

	$perm = array();

	foreach ($permissions as $permission => $default)
	{
		$result = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}permissions
			WHERE permission = {string:permission}',
			array(
				'permission' => $permission
			)
		);

		list ($num) = $smcFunc['db_fetch_row']($result);

		if ($num == 0)
		{
			foreach ($default as $grp)
				$perm[] = array($grp, $permission);
		}
	}

	$group = $smf2 ? 'id_group': 'ID_GROUP';

	if (empty($perm))
		return;

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}permissions',
		array(
			$group => 'int',
			'permission' => 'string'
		),
		$perm,
		array()
	);
}

?>
