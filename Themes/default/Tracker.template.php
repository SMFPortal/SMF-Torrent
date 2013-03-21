<?php
// Version: 0.1; Tracker

function template_torrent_list()
{
  global $scripturl, $txt, $context;

	echo '
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="middletext" style="padding-bottom: 4px;" valign="bottom">', $txt['pages'], ': ', $context['page_index'], '   &nbsp;&nbsp;<a href="#tracker_bottom"><b>', $txt['go_down'], '</b></a></td>
				<td style="padding-right: 1ex;" align="right">
				</td>
			</tr>
		</table>
		<div class="tborder">
			<table class="bordercolor" border="0" cellpadding="4" cellspacing="1" width="100%">';

	if (!empty($context['torrents']))
	{
		print_r($context['torrents']);
	}
	// There are no games installed / found.
	else
	{
		echo '
				<tr>
					<td class="catbg3"><b>', $txt['tracker_no_torrents'], '</b></td>
				</tr>';
	}

	echo '
			</table>
		</div>
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="middletext">', $txt['pages'], ': ', $context['page_index'], '   &nbsp;&nbsp;<a href="#tracker_top"><b>', $txt['go_up'], '</b></a></td>
				<td style="padding-right: 1ex;" align="right">
				</td>
			</tr>
		</table>';

}

function template_torrent_view()
{
	global $scripturl, $txt, $context;

	echo '
	<table border="0" width="85%" cellpadding="4" cellspacing="1" align="center" class="bordercolor">
		<tr class="titlebg">
			<td height="26" colspan="2">
				', $context['torrent']['name'], '
			</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['tracker_download'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['download_link'], '</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['tracker_added'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['added'], '</td>
		</tr><tr>
			<td class="windowbg" width="30" valign="top">', $txt['tracker_description'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['description'], '</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['tracker_category'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['category']['link'], '</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['torrent_transfer'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['transfer'], ' (', sprintf($txt['tracker_downloaded_times'], $context['torrent']['downloads']), ')</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['torrent_size'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['filesize'], '</td>
		</tr><tr>
			<td class="windowbg" width="30" valign="top">', $txt['torrent_seeders'], '</td>
			<td class="windowbg2" valign="top">';

	if (!empty($context['torrent']['seeders']))
	{
		echo '
				<table class="bordercolor" width="100%" cellpadding="4" cellspacing="1">
					<tr class="titlebg">
						<td>', $txt['tracker_m_name'], '</td>
						<td>', $txt['torrent_downloaded'], '</td>
						<td>', $txt['torrent_uploaded'], '</td>
						<td>', $txt['torrent_ratio'], '</td>
					</tr>';

		$alternate = false;
		foreach ($context['torrent']['seeders'] as $peer)
		{
			echo '
					<tr class="windowbg', $alternate ? '2' : '', '">
						<td>', $peer['link'], '<div class="smalltext">', $txt['tracker_idle'] , ': ', $peer['idle'], '</div></td>
						<td style="text-align: center">', $peer['downloaded'], '</td>
						<td style="text-align: center">', $peer['uploaded'], '<div class="smalltext">', $peer['upload_rate'], '</div></td>
						<td style="text-align: center">', $peer['ratio'], '</td>
					</tr>';

			$alternate = !$alternate;
		}

		echo '
				</table>';
	}
	else
		echo '
				', $context['torrent']['num_seeders'], ' <a href="', $context['torrent']['href'], ';showPeers" class="smalltext">', $txt['tracker_show_list'], '</a>';

	echo '
			</td>
		</tr><tr>
			<td class="windowbg" width="30" valign="top">', $txt['torrent_leechers'], '</td>
			<td class="windowbg2" valign="top">';

	if (!empty($context['torrent']['leechers']))
	{
		echo '
				<table class="bordercolor" width="100%" cellpadding="4" cellspacing="1">
					<tr class="titlebg">
						<td>', $txt['tracker_m_name'], '</td>
						<td>', $txt['torrent_downloaded'], '</td>
						<td>', $txt['torrent_uploaded'], '</td>
						<td>', $txt['torrent_ratio'], '</td>
					</tr>';

		$alternate = false;
		foreach ($context['torrent']['leechers'] as $peer)
		{
			echo '
					<tr class="windowbg', $alternate ? '2' : '', '">
						<td>', $peer['link'], '<div class="smalltext">', $txt['tracker_idle'] , ': ', $peer['idle'], '</div></td>
						<td style="text-align: center">', $peer['downloaded'], '<div class="smalltext">', $peer['process'], '% - ', $peer['download_rate'], '</div></td>
						<td style="text-align: center">', $peer['uploaded'], '<div class="smalltext">', $peer['upload_rate'], '</div></td>
						<td style="text-align: center">', $peer['ratio'], '</td>
					</tr>';

			$alternate = !$alternate;
		}

		echo '
				</table>';
	}
	else
		echo '
				', $context['torrent']['num_leechers'], ' <a href="', $context['torrent']['href'], ';showPeers" class="smalltext">', $txt['tracker_show_list'], '</a>';

	echo '
			</td>
		</tr><tr>
			<td class="windowbg" width="30" valign="top">', $txt['torrent_completed'], '</td>
			<td class="windowbg2" valign="top">';

	if (!empty($context['torrent']['complete']))
	{
		echo '
				<table class="bordercolor" width="100%" cellpadding="4" cellspacing="1">
					<tr class="titlebg">
						<td>', $txt['tracker_m_name'], '</td>
						<td>', $txt['torrent_downloaded'], '</td>
						<td>', $txt['torrent_uploaded'], '</td>
						<td>', $txt['torrent_ratio'], '</td>
					</tr>';

		$alternate = false;
		foreach ($context['torrent']['complete'] as $peer)
		{
			echo '
					<tr class="windowbg', $alternate ? '2' : '', '">
						<td>', $peer['link'], '<div class="smalltext">', $txt['tracker_complete_time'] , ': ', $peer['complete_time'], '</div></td>
						<td style="text-align: center">', $peer['downloaded'], '<div class="smalltext">', $peer['leech_time'], '</div></td>
						<td style="text-align: center">', $peer['uploaded'], '<div class="smalltext">', $peer['seed_time'], '</div></td>
						<td style="text-align: center">', $peer['ratio'], '</td>
					</tr>';

			$alternate = !$alternate;
		}

		echo '
				</table>';
	}
	else
		echo '
				<a href="', $context['torrent']['href'], ';showPeers" class="smalltext">', $txt['tracker_show_list'], '</a>';

	echo '
			</td>
		</tr><tr>
			<td class="windowbg" width="30" valign="top">', $txt['tracker_files'], '</td>
			<td class="windowbg2" valign="top">';

	if (!isset($_REQUEST['showFiles']))
		echo '
				<div id="flink"><a href="', $context['torrent']['href'], ';showFiles" onclick="document.getElementById(\'flink\').style.display = \'none\'; document.getElementById(\'flist\').style.display = \'block\'; return false" class="smalltext">', $txt['tracker_show_list'], '</a></div>';
	echo '
				<ul id="flist"', isset($_REQUEST['showFiles']) ? '' : ' style="display: none"', ' class="smalltext">';

	foreach ($context['torrent']['files'] as $file)
		echo '
					<li>', $file['path'], ' - ', $file['size'], '</li>';

	echo '
				</ul>
			</td>
		</tr><tr>
			<td class="windowbg" width="30">', $txt['tracker_uploader'], '</td>
			<td class="windowbg2" valign="top">', $context['torrent']['uploader']['link'], '</td>
		</tr>
	</table>';
}

function template_tracker_user_statistics()
{
	global $scripturl, $txt, $context, $settings, $memberContext;

	echo '
	<table border="0" width="85%" cellpadding="4" cellspacing="1" align="center" class="bordercolor">
		<tr class="titlebg">
			<td height="26" colspan="2">
				', $txt['tracker_stats'], ' - ', $context['member']['name'], '
			</td>
		</tr>';

	foreach ($context['member']['torrents'] as $group => $torrents)
	{
		if (empty($torrents))
			continue;

		echo '
		<tr class="titlebg">
			<td height="26" colspan="2">
				', $txt['tracker_group_' . $group], '
			</td>
		</tr>
		<tr>
			<td class="windowbg" width="20" valign="middle" align="center"><img src="', $settings['images_url'], '/stats_info.gif" width="20" height="20" alt="" /></td>
			<td class="windowbg2" valign="top">
				<table border="0" cellpadding="1" cellspacing="0" width="100%">
					<tr>
						<td>', $txt['torrent_name'], '</td>
						<td>', $txt['torrent_size'], '</td>
						<td>', $txt['torrent_downloaded'], '</td>
						<td>', $txt['torrent_uploaded'], '</td>
						<td>', $txt['torrent_ratio'], '</td>
					</tr>';

		foreach ($torrents as $torrent)
		{
			echo '
					<tr>
						<td width="60%">', $torrent['href'], '</td>
						<td class="smalltext">', $torrent['size'], '</td>
						<td class="smalltext"><abbr title="', $torrent['leech_time'], '">', $torrent['downloaded'], '</abbr></td>
						<td class="smalltext"><abbr title="', $torrent['seed_time'], '">', $torrent['uploaded'], '<br /></abbr></td>
						<td class="smalltext">', $torrent['ratio'], '</td>
					</tr>';
		}

		echo '
				</table>
			</td>
		</tr>';
	}

		echo '
	</table>';
}

?>
