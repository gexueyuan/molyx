<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
require ('./global.php');

class cache
{
	function show()
	{
		global $forums, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditcaches'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}

		$forums->admin->nav[] = array('cache.php' , $forums->lang['managecache']);
		switch (input::get('do', ''))
		{
			case 'cacheend':
				$this->cacheend();
				break;
			case 'viewcache':
				$this->viewcache();
				break;
			default:
				$this->cacheform();
				break;
		}
	}

	function viewcache()
	{
		global $forums, $DB;
		if (! input::get('id', ''))
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->cacheform();
		}
		if (input::str('id') == 'forum_cache')
		{
			input::set('id', 'forum');
		}
		cache::get(input::str('id'));
		$out = print_r($forums->cache[input::get('id', '')], true);
		$forums->admin->print_popup_header();
		echo "<pre>" . $out . "</pre>";
		$forums->admin->print_popup_footer();
	}

	function cacheend()
	{
		global $forums, $DB;
		$action = "";
		foreach($_REQUEST AS $k => $v)
		{
			if (strstr($k, 'update') AND $v != "")
			{
				$action = str_replace('update', '', $k);
				break;
			}
		}
		$forums->lang['cacheupdated'] = sprintf($forums->lang['cacheupdated'], $forums->lang[ $action ]);
		switch ($action)
		{
			case 'all':
				cache::update('all');
				$forums->main_msg = sprintf($forums->lang['cacheupdated'], $forums->lang[ $action ]);
				break;
			case 'forum_cache':
				cache::update('forum');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'usergroup':
				cache::update('usergroup');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'style':
				cache::update('style');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'moderator':
				cache::update('moderator');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'stats':
				cache::update('stats');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'ranks':
				cache::update('ranks');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'olranks':
				cache::update('olranks');
				$forums->main_msg = sprintf($forums->lang['cacheupdated'], $forums->lang['olranks']);
				break;
			case 'birthdays':
				cache::update('birthdays');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'bbcode':
				cache::update('bbcode');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'banksettings':
				cache::update('banksettings');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'settings':
				cache::update('settings');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'smile':
				cache::update('smile');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'icon':
				cache::update('icon');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'badword':
				cache::update('badword');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'banfilter':
				cache::update('banfilter');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'attachtype':
				cache::update('attachmenttype');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'announcement':
				cache::update('announcement');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'league':
				cache::update('league');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'credit':
				cache::update('credit');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'realjs':
				cache::update('realjs');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'st':
				cache::update('st');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'ad':
				cache::update('ad');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'cron':
				cache::update('cron');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'splittable':
				cache::update('splittable');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			default:
				$forums->main_msg = $forums->lang['noupdatecache'];
				break;
		}
		$this->cacheform();
	}

	function cacheform()
	{
		global $forums, $DB;
		$detail = $forums->lang['managecachedesc'];
		$pagetitle = $forums->lang['managecache'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$cache = array('forum_cache' => $forums->lang['forumcacheinfo'],
			'usergroup' => $forums->lang['usergroupinfo'],
			'style' => $forums->lang['styleinfo'],
			'moderator' => $forums->lang['moderatorinfo'],
			'stats' => $forums->lang['statsinfo'],
			'ranks' => $forums->lang['ranksinfo'],
			'birthdays' => $forums->lang['birthdaysinfo'],
			'bbcode' => $forums->lang['bbcodeinfo'],
			'settings' => $forums->lang['settingsinfo'],
			'smile' => $forums->lang['smileinfo'],
			'icon' => $forums->lang['iconinfo'],
			'badword' => $forums->lang['badwordinfo'],
			'banfilter' => $forums->lang['banfilterinfo'],
			'attachmenttype' => $forums->lang['attachtypeinfo'],
			'announcement' => $forums->lang['announcementinfo'],
			'cron' => $forums->lang['croninfo'],
			'league' => $forums->lang['leagueinfo'],
			'credit' => $forums->lang['creditinfo'],
			'realjs' => $forums->lang['realjsinfo'],
			'st' => $forums->lang['stinfo'],
			'ad' => $forums->lang['adinfo'],
			'splittable' => $forums->lang['splittableinfo'],
			);
		$forums->admin->print_form_header(array(1 => array('do' , 'cacheend'), 2 => array('updateall', '1')));
		$forums->admin->columns[] = array($forums->lang['cachename'], "60%");
		$forums->admin->columns[] = array($forums->lang['size'], "20%");
		$forums->admin->columns[] = array($forums->lang['option'], "20%");
		$forums->admin->print_table_start($forums->lang['cacheinfo']);
		$used = array();
		if (count($used) != count($cache))
		{
			foreach($cache AS $k => $v)
			{
				$fk = $k;
				if ($k == "forum_cache")
				{
					$fk = "forum";
				}
				$size = file_exists(ROOT_PATH . 'cache/cache/' . $fk . '.php') ? @filesize(ROOT_PATH . 'cache/cache/' . $fk . '.php') : 0;
				$size = ceil(intval($size) / 1024);
				$updatebutton = $forums->admin->print_button($forums->lang['update'], "cache.php?{$forums->sessionurl}do=cacheend&amp;update" . $k . "=1", 'button');
				$forums->admin->print_cells_row(array("<strong>" . $k . "</strong><div class='description'>{$cache[ $k ]}</div>", $size . ' Kb', "<div align='center'>" . $updatebutton . "<input type='button' onclick=\"pop_win('cache.php?{$forums->sessionurl}do=viewcache&amp;id={$k}','" . $forums->lang['view'] . "', 400,600)\" value='" . $forums->lang['view'] . "' class='button' /></div>",)
					);
			}
		}
		$forums->admin->print_form_submit($forums->lang['updateallcache']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new cache();
$output->show();

?>