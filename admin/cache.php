<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# copyright (c) 2004-2006 HOGE Software.
# official forum : http://molyx.com
# license : MolyX License, http://molyx.com/license
# MolyX2 is free software. You can redistribute this file and/or modify
# it under the terms of MolyX License. If you do not accept the Terms
# and Conditions stated in MolyX License, please do not redistribute
# this file.Please visit http://molyx.com/license periodically to review
# the Terms and Conditions, or contact HOGE Software.
#
# $Id$
# **************************************************************************#
require ('./global.php');

class cache
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditcaches'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}

		$forums->admin->nav[] = array('cache.php' , $forums->lang['managecache']);
		switch ($_INPUT['do'])
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
		global $forums, $DB, $_INPUT;
		if (! $_INPUT['id'])
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->cacheform();
		}
		if ($_INPUT['id'] == 'forum_cache')
		{
			$_INPUT['id'] = 'forum';
		}
		$forums->func->check_cache($_INPUT['id']);
		$out = print_r($forums->cache[$_INPUT['id']], true);
		$forums->admin->print_popup_header();
		echo "<pre>" . $out . "</pre>";
		$forums->admin->print_popup_footer();
	}

	function cacheend()
	{
		global $forums, $DB, $_INPUT;
		$action = "";
		foreach($_INPUT AS $k => $v)
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
				$forums->func->recache('all');
				$forums->main_msg = sprintf($forums->lang['cacheupdated'], $forums->lang[ $action ]);
				break;
			case 'forum_cache':
				$forums->func->recache('forum');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'usergroup':
				$forums->func->recache('usergroup');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'style':
				$forums->func->recache('style');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'moderator':
				$forums->func->recache('moderator');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'stats':
				$forums->func->recache('stats');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'ranks':
				$forums->func->recache('ranks');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'olranks':
				$forums->func->recache('olranks');
				$forums->main_msg = sprintf($forums->lang['cacheupdated'], $forums->lang['olranks']);
				break;
			case 'birthdays':
				$forums->func->recache('birthdays');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'bbcode':
				$forums->func->recache('bbcode');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'banksettings':
				$forums->func->recache('banksettings');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'settings':
				$forums->func->recache('settings');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'smile':
				$forums->func->recache('smile');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'icon':
				$forums->func->recache('icon');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'badword':
				$forums->func->recache('badword');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'banfilter':
				$forums->func->recache('banfilter');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'attachtype':
				$forums->func->recache('attachmenttype');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'announcement':
				$forums->func->recache('announcement');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'league':
				$forums->func->recache('league');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'credit':
				$forums->func->recache('credit');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'realjs':
				$forums->func->recache('realjs');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'st':
				$forums->func->recache('st');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'ad':
				$forums->func->recache('ad');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'cron':
				$forums->func->recache('cron');
				$forums->main_msg = $forums->lang['cacheupdated'];
				break;
			case 'splittable':
				$forums->func->recache('splittable');
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
				$size = @filesize(ROOT_PATH . 'cache/cache/' . $fk . '.php');
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