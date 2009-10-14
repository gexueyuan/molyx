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
# $Id: upgrade.php 412 2007-11-18 13:20:44Z develop_tong $
# **************************************************************************#
error_reporting (E_ERROR | E_WARNING | E_PARSE);
define('ROOT_PATH', './../');
define('IN_MXB', true);
define('TIMENOW', time());
@set_time_limit(0);
@set_magic_quotes_runtime(0);
if (function_exists('ini_get'))
{
	$safe_mode = @ini_get("safe_mode") ? 1 : 0;
}
else
{
	$safe_mode = 1;
}
define('SAFE_MODE', $safe_mode);

require_once(ROOT_PATH . 'install/install_functions.php');
require_once(ROOT_PATH . 'includes/config.php');
if (!$_GET['lang'] && !$_POST['lang'] && !$_COOKIE['lang'])
{
	/*
	preg_match('/^([a-z-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
	$language = $matches[1];
	switch ($language)
	{
		case 'zh':
		case 'zh-cn':
			$language = 'zh-cn';
			break;
		case 'zh-tw':
		case 'zh-hk':
		case 'zh-mo':
		case 'zh-sg':
			$language = 'zh-tw';
			break;
		default:
			$language = 'en-us';
			break;
	}
	*/
	$language = 'zh-cn';
	require(ROOT_PATH . 'install/install_lang_' . $language . '.php');
	p_header();
	if ($_POST['next'])
	{
		$error = true;
	}
	p_selectlang($error, $language, 'upgrade');
	p_footer('update', array('l_username' => $l_username,
			'l_userpassword' => $l_userpassword
			));
	exit;
}
$_POST['lang'] = $_POST['lang']?$_POST['lang']:$_GET['lang'];
$_GET['lang'] = $_POST['lang'];
require(ROOT_PATH . 'install/install_lang_' . $_POST['lang'] . '.php');

define('TABLE_PREFIX', $config['tableprefix']);
require_once(ROOT_PATH . "includes/functions.php");
require_once(ROOT_PATH . "includes/functions_init.php");
$forums->func = new functions();
require_once (ROOT_PATH . 'includes/db/db_base.php');
require_once (ROOT_PATH . 'includes/db/db_mysql.php');
$DB = new db;
$DB->technicalemail = $config['technicalemail'];
$DB->dbcharset = $config['dbcharset'];
$DB->connect($config['servername'], $config['dbusername'], $config['dbpassword'], $config['dbname']);

$r_registry = $DB->query_first("SELECT defaultvalue FROM " . TABLE_PREFIX . "setting WHERE varname='version'");
$version = $r_registry['defaultvalue'];

$user = $DB->query_first("SELECT id,usergroupid,membergroupids,password, salt FROM " . TABLE_PREFIX . "user WHERE name='" . addslashes($l_username) . "'");

if ($user['id'])
{
	if ($user['password'] != md5(md5($l_userpassword) . $user['salt']))
	{
		$action = 'login';
		$loginsys = false;
	}
	if ($user['usergroupid'] == 4 OR preg_match("/,4,/i", "," . $user['membergroupids'] . ",") OR preg_match("/," . $user['id'] . ",/i", "," . $config['superadmin'] . ","))
	{
	}
	else
	{
		$action = 'login';
		$loginsys = false;
	}
}
else
{
	$action = 'login';
	$loginsys = false;
}
@header("Content-Type:text/html; charset=UTF-8");
switch ($action)
{
	case 'login':
		p_header();
		p_loginform($loginsys);
		p_footer('welcome');
		break;

	case 'importstyles':

		require_once (ROOT_PATH . 'includes/functions.php');
		$forums->func = new functions();
		$forums->func->check_lang();
		require_once(ROOT_PATH . 'includes/adminfunctions.php');
		$forums->admin = new adminfunctions();

		$DB->query_unbuffered("
				REPLACE INTO " . TABLE_PREFIX . "style
				(styleid, title, title_en, imagefolder, userselect, usedefault, parentid, parentlist, css, csscache, version)
				VALUES
				('1', 'Global Style', 'global', 'style_1', 0, 0, 0, 1, '', '', '" . $version . "')
			");
		$DB->query_unbuffered("
				REPLACE INTO " . TABLE_PREFIX . "style
				(title, title_en, imagefolder, userselect, usedefault, parentid, parentlist, css, csscache, version)
				VALUES
				('" . lng('defaultstyle') . "', 'default', 'style_1', 1, 1, 1, 1, '', '', '" . $version . "')
			");
		$styleid = $DB->insert_id();
		$parentlist = $styleid . ',1';
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "style SET parentlist='" . $parentlist . "' WHERE styleid = $styleid");

		require_once(ROOT_PATH . 'includes/adminfunctions_template.php');
		$recachestyle = new adminfunctions_template();
		$recachestyle->rebuildallcaches(2);

		$finished = p_done(1);
		p_header();
		echo $finished;
		p_footer();
		break;
	case 'startupdate':
		include $scriptname;
		$update = new CUpdate;
		$add_charset = $DB->dbcharset ? " default charset=" . $DB->dbcharset : "";
		require_once (ROOT_PATH . 'includes/functions.php');
		$forums->func = new functions();

		if (!$update->AllowUpdate())
		{
			p_errormsg(lng('error'),
				lng('cantexec'));
		}
		p_header(1);
		ob_flush();

		if ($update->RunUpdate())
		{
			p_errormsg(lng('error'),
				$update->GetError());
		}

		p_header(1);
		p_footer('importstyles', array('scriptname' => $scriptname,
				'l_username' => $l_username,
				'l_userpassword' => $l_userpassword
				));
		break;
	case 'update':
		$scriptname = ROOT_PATH . 'install/upgrades/' . $scriptname;
		if (!file_exists($scriptname) || !$scriptname)
		{
			p_errormsg(lng('error'), lng('notfound'));
		}
		else
		{
			include $scriptname;
			$update = new CUpdate;
			$update->Notes = lng('updatenotes');

			if ($update->UpdaterVer > $cfg['updater_ver'])
			{
				p_errormsg(lng('error'), lng('tooold'));
			}
			else
			{
				p_header();
				p_updateinfo($update);
				p_footer('startupdate', array('scriptname' => $scriptname,
						'l_username' => $l_username,
						'l_userpassword' => $l_userpassword
						));
			}
		}
		break;

	case 'welcome':
	default:
		$a_file = array();
		$dp = opendir(ROOT_PATH . 'install/upgrades/');
		while ($file = readdir($dp))
		{
			if (substr($file, -7, 7) == '.update')
			{
				$a_file[] = $file;
			}
		}
		natsort($a_file);
		p_header();
		p_updatewelcome($a_file);
		p_footer('update', array('l_username' => $l_username,
				'l_userpassword' => $l_userpassword
				));
		break;
}

?>