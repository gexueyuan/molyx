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
# $Id: global.php 460 2008-01-08 01:04:21Z develop_tong $
# **************************************************************************#
define('IN_MXB', true);
define('ROOT_PATH', './../');
define ('IN_ACP', true);
require_once(ROOT_PATH . 'includes/init.php');
if (function_exists('set_time_limit') && !SAFE_MODE)
{
	@set_time_limit(0);
}

if (!isset($content_type))
{
	header('Content-Type:text/html; charset=UTF-8');
}

$forums = new stdClass();
$forums->noheader = 0;
$forums->forum_read = $forums->lang = array();

require_once(ROOT_PATH . 'includes/functions.php');
$forums->func = new functions();
$_INPUT = init_input();
if (empty($_INPUT['url']))
{
	$url = REFERRER;
}
else
{
	if ($_INPUT['url'] == REFERRER)
	{
		$url = 'index.php';
	}
	else
	{
		$url = &$_INPUT['url'];
	}
}
if ($url == SCRIPTPATH || empty($url))
{
	$url = 'index.php';
}
$forums->url = xss_clean($url);
if (USE_SHUTDOWN)
{
	register_shutdown_function(array(&$forums->func, 'do_shutdown'));
}
$forums->func->check_cache('settings');
$forums->func->check_cache('cron');
$bboptions = $forums->cache['settings'];

$forums->imageurl = '../images/controlpanel';
$bboptions['uploadurl'] = $bboptions['uploadurl'] ? $bboptions['uploadurl'] : $bboptions['bburl'] . '/data/uploads';
$bboptions['uploadfolder'] = $bboptions['uploadfolder'] ? $bboptions['uploadfolder'] : ROOT_PATH . 'data/uploads';

$forums->func->check_lang();
$forums->func->load_lang('admin');
$forums->func->load_lang('init');

require_once(ROOT_PATH . 'includes/functions_forum.php');
$forums->forum = new functions_forum();

require_once(ROOT_PATH . 'includes/sessions.php');
$session = new session();

require_once(ROOT_PATH . 'includes/adminfunctions.php');
$forums->admin = new adminfunctions();

require_once(ROOT_PATH . 'includes/adminfunctions_forum.php');
$forums->adminforum = new adminfunctions_forum();
$forums->func->check_cache('adminforum');
if (empty($forums->adminforum->forumcache))
{
	$forums->adminforum->forumcache = $forums->cache['adminforum'];
}

$session_validated = 0;
$this_session = array();
$validate = false;

$buffer = ob_get_contents();
ob_end_clean();
ob_start();
echo $buffer;

if (defined('IN_SQL') && $fp = @fopen(ROOT_PATH . 'data/dbbackup/unlock.dbb', 'r'))
{
	$validate = true;
	fclose($fp);
}
else if ($_INPUT['login'] != 'yes')
{
	if (!defined('IN_SQL') && file_exists(ROOT_PATH . 'data/dbbackup/unlock.dbb'))
	{
		$forums->admin->print_cp_error($forums->lang['unlockfileexist']);
	}
	if (!$_INPUT['s'])
	{
		$forums->admin->print_cp_login();
	}
	else
	{
		$DB->query('SELECT *
			FROM ' . TABLE_PREFIX . "adminsession
			WHERE sessionhash='{$_INPUT['s']}'");
		$row = $DB->fetch_array();
		if ($row['sessionhash'] == '' || $row['userid'] == '')
		{
			$forums->admin->print_cp_login();
		}
		else
		{
			$user = $DB->query_first('SELECT u.*, g.*
				FROM ' . TABLE_PREFIX . 'user u, ' . TABLE_PREFIX . 'usergroup g
				WHERE id=' . intval($row['userid']) . '
					AND u.usergroupid=g.usergroupid');
			$session->user = $user;
			$session->build_group_permissions();
			$bbuserinfo = $session->user;
			if ($bbuserinfo['id'] == '')
			{
				$forums->admin->print_cp_login($forums->lang['usernotexist']);
			}
			else
			{
				if ($row['password'] != $bbuserinfo['password'])
				{
					$forums->admin->print_cp_login($forums->lang['passwordwrong']);
				}
				else
				{
					$admin = explode(',', SUPERADMIN);
					if ($bbuserinfo['cancontrolpanel'] != 1 AND !in_array($bbuserinfo['id'], $admin))
					{
						$forums->admin->print_cp_login($forums->lang['noadmincpperms']);
					}
					else
					{
						$session_validated = 1;
						$this_session = $row;
					}
				}
			}
		}
	}
}
else
{
	$username = trim($_INPUT['username']);
	$username = $DB->escape_string(str_replace('|', '&#124;', $username));
	if (empty($username))
	{
		$forums->admin->print_cp_login($forums->lang['requireusername']);
	}
	if (empty($_INPUT['password']))
	{
		$forums->admin->print_cp_login($forums->lang['requirepassword']);
	}
	$user = $DB->query_first('SELECT u.*, g.*
		FROM ' . TABLE_PREFIX . 'user u, ' . TABLE_PREFIX . "usergroup g
		WHERE (LOWER(u.name)='" . strtolower($username) . "'
		OR u.name='$username')
			AND u.usergroupid=g.usergroupid");
	$session->user = $user;
	$session->build_group_permissions();
	$user = $session->user;
	if (empty($user['id']))
	{
		$forums->admin->print_cp_login($forums->lang['usernotexist']);
	}
	$password = md5($_INPUT['password']);
	if ($user['password'] != md5($password . $user['salt']))
	{
		$forums->admin->print_cp_login($forums->lang['passwordwrong']);
	}
	else
	{
		$admin = explode(',', SUPERADMIN);
		if ($user['cancontrolpanel'] != 1 && !in_array($bbuserinfo['id'], $admin))
		{
			$forums->admin->print_cp_login($forums->lang['noadmincpperms']);
		}
		else
		{
			$forums->sessionid = md5(uniqid(microtime()));
			$DB->insert(TABLE_PREFIX . 'adminsession', array(
				'sessionhash' => $forums->sessionid,
				'host' => IPADDRESS,
				'username' => $user['name'],
				'userid' => $user['id'],
				'password' => $user['password'],
				'location' => 'index',
				'logintime' => TIMENOW,
				'lastactivity' => TIMENOW,
			));
			$forums->func->standard_redirect('./index.php?frames=1&amp;s=' . $forums->sessionid . '&amp;reffer_url=' . urlencode($_INPUT['reffer_url']));
		}
	}
}

if (!$validate)
{
	if ($session_validated)
	{
		if ($this_session['lastactivity'] < (TIMENOW - 60 * 60 * 2))
		{
			$session_validated = 0;
			$forums->admin->print_cp_login($forums->lang['loginovertime']);
		}
		else if ($this_session['host'] != IPADDRESS)
		{
			$session_validated = 0;
			$forums->admin->print_cp_login($forums->lang['ipaddressnotmatch']);
		}
		$forums->sessionid = $this_session['sessionhash'];
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin))
		{
			$forums->adminperms = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . 'administrator
			WHERE aid = ' . $bbuserinfo['id']);
		}
		$DB->update(TABLE_PREFIX . 'adminsession', array(
			'lastactivity' => TIMENOW,
			'location' => SCRIPT
		), "userid={$bbuserinfo['id']} AND sessionhash='{$forums->sessionid}'");
		$forums->sessionurl = 's=' . $forums->sessionid . '&amp;';
		$forums->js_sessionurl = 's=' . $forums->sessionid . '&';
	}
	else
	{
		$forums->admin->print_cp_login($forums->lang['loginovertime']);
	}
}

if ($_INPUT['frames'])
{
	$forums->admin->print_frame_set();
}
else if ($_INPUT['do'] == 'menu')
{
	$forums->admin->menu();
}
else if ($_INPUT['do'] == 'nav')
{
	$forums->admin->nav();
}

?>