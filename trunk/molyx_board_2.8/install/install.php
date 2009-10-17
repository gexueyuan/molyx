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
# $Id: install.php 2018 2007-05-29 11:05:50Z develop_tong $
# **************************************************************************#
error_reporting (E_ERROR | E_WARNING | E_PARSE);
define('ROOT_PATH', './../');
define('IN_MXB', true);
define('TIMENOW', time());
@set_time_limit(0);
@set_magic_quotes_runtime(0);
if (PHP_VERSION < '6' && function_exists('ini_get'))
{
	$safe_mode = @ini_get('safe_mode') ? 1 : 0;
}
else
{
	$safe_mode = 0;
}
define('SAFE_MODE', $safe_mode);

require_once(ROOT_PATH . 'install/install_functions.php');
require_once(ROOT_PATH . 'includes/functions_init.php');
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
			$language = 'zh-cn';
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
	p_selectlang($error, $language);
	p_footer('welcome', 0, 0);
	exit;
}
$_POST['lang'] = $_POST['lang'] ? $_POST['lang'] : $_GET['lang'];
$_GET['lang'] = $_POST['lang'];
require(ROOT_PATH . 'install/install_lang_' . $_POST['lang'] . '.php');

if ($_ENV['REQUEST_URI'] OR $_SERVER['REQUEST_URI'])
{
	$scriptpath = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_ENV['REQUEST_URI'];
}
else
{
	if ($_ENV['PATH_INFO'] OR $_SERVER['PATH_INFO'])
	{
		$scriptpath = $_SERVER['PATH_INFO'] ? $_SERVER['PATH_INFO']: $_ENV['PATH_INFO'];
	}
	else if ($_ENV['REDIRECT_URL'] OR $_SERVER['REDIRECT_URL'])
	{
		$scriptpath = $_SERVER['REDIRECT_URL'] ? $_SERVER['REDIRECT_URL']: $_ENV['REDIRECT_URL'];
	}
	else
	{
		$scriptpath = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
	}

	if ($_ENV['QUERY_STRING'] OR $_SERVER['QUERY_STRING'])
	{
		$scriptpath .= '?' . ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : $_ENV['QUERY_STRING']);
	}
}
define('SCRIPTPATH', $scriptpath);

if (version_compare(PHP_VERSION, '4.3.3', '<'))
{
	p_errormsg(lng('error'), lng('versiontoolow'));
}

if (!isset($action))
{
	$action = '';
}
if (!install_allowed() && $action != '')
{
	$action = 'deny';
}

switch ($action)
{
	case 'generate_config':
		header('Content-Type: text/octetstream');
		header('Content-Disposition: attachment; filename="config.php"');
		header('Pragma: no-cache');
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');

		$config = array(
			'servername' => $hostname,
			'dbname' => $db,
			'dbusername' => $user,
			'dbpassword' => $pass,
			'tableprefix' => $prefix,
			'superadmin' => $superadmin,
			'technicalemail' => $technicalemail,
			'dbtype' => $dbtype,
		);
		echo config_file($config);
		break;

	case 'createadmin':
		if (strlen($admin_pass) < 5)
		{
			p_errormsg(lng('error'), lng('adminpwtooshort'));
		}
		if ($admin_pass != $twopassword)
		{
			p_errormsg(lng('error'), lng('passwordnowmatch'));
		}
		define('DB_EXPLAIN', false);
		define('DB_QUERIES', false);
		define('TABLE_PREFIX', $prefix);

		$dbtype = ($dbtype) ? trim($dbtype) : 'mysql';
		require_once(ROOT_PATH . 'includes/db/db_base.php');
		require_once(ROOT_PATH . 'includes/db/db_' . $dbtype . '.php');
		$DB = new db;
		$DB->connect($hostname, $user, $pass, $db);
		require_once(ROOT_PATH . 'includes/functions.php');
		$forums->func = new functions();
		$forums->func->check_lang();

		$salt = generate_user_salt();

		$DB->insert($prefix . 'user', array(
			'name' => $admin_user,
			'email' => $admin_email,
			'password' => md5(md5($admin_pass) . $salt),
			'usergroupid' => 4,
			'joindate' => TIMENOW,
			'timezoneoffset' => 8,
			'options' => 103,
			'salt' => $salt,
			'pmfolders' => '',
			'signature' => '',
		));
		$adminid = $DB->insert_id();
		define('CACHE_TABLE', $prefix . 'cache');
		$forums->func->recache('all');

		require_once(ROOT_PATH . 'includes/adminfunctions.php');
		adminfunctions::recount_stats();

		$technicalemail = $emailreceived ? $emailreceived : $admin_email;

		p_header();
		p_prewrite($hostname, $user, $pass, $db, $prefix, $adminid, $emailreceived);
		p_footer('writeconfig', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'db' => $db,
			'superadmin' => $adminid,
			'prefix' => $prefix,
			'technicalemail' => $technicalemail,
			'dbtype' => $dbtype
		));
		break;

	case 'sitesetting':

		$connect_id = mysql_connect($hostname, $user, $pass);
		$version = @mysql_get_server_info($connect_id);
		if ($version > '4.1')
		{
			@mysql_query("SET NAMES 'utf8'", $connect_id);
		}
//		if ($version > '5.0')
//		{
//			@mysql_query("SET @@sql_mode = ''", $connect_id);
//		}
		mysql_select_db($db, $connect_id);

		$uploadurl = $bburl . '/data/uploads';
		$uploadfolder = str_replace("\\", "/", realpath(ROOT_PATH) . "/data/uploads");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $hometitle . "' WHERE varname='hometitle'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $homeurl . "' WHERE varname='homeurl'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $bbtitle . "' WHERE varname='bbtitle'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $bburl . "' WHERE varname='bburl'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $uploadurl . "' WHERE varname='uploadurl'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $uploadfolder . "' WHERE varname='uploadfolder'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $emailreceived . "' WHERE varname='emailreceived'");
		mxb_query("UPDATE " . $prefix . "setting SET defaultvalue='" . $emailsend . "' WHERE varname='emailsend'");
		mxb_query("UPDATE " . $prefix . "setting SET value='" . $cookiedomain . "' WHERE varname='cookiedomain'");
		mxb_query("UPDATE " . $prefix . "setting SET value='" . $cookieprefix . "' WHERE varname='cookieprefix'");
		mxb_query("UPDATE " . $prefix . "setting SET value='" . $cookiepath . "' WHERE varname='cookiepath'");

		p_header();
		p_adminprofile();
		p_footer('createadmin', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'db' => $db,
			'prefix' => $prefix,
			'technicalemail' => $emailreceived,
			'dbtype' => $dbtype,)
		);
		break;

	case 'importstyles':

		p_header();
		define('DB_EXPLAIN', false);
		define('DB_QUERIES', false);
		define('TABLE_PREFIX', $prefix);

		$dbtype = ($dbtype) ? trim($dbtype) : 'mysql';
		require_once (ROOT_PATH . 'includes/db/db_base.php');
		require_once (ROOT_PATH . 'includes/db/db_' . $dbtype . '.php');
		$DB = new db;
		$DB->connect($hostname, $user, $pass, $db);
		require_once (ROOT_PATH . 'includes/functions.php');
		$forums->func = new functions();
		require_once(ROOT_PATH . 'includes/adminfunctions.php');
		$forums->admin = new adminfunctions();

		$DB->query_unbuffered("
				REPLACE INTO " . $prefix . "style
				(styleid, title, title_en, imagefolder, userselect, usedefault, parentid, parentlist, css, csscache, version)
				VALUES
				('1', 'Global Style', 'global', 'style_1', 0, 0, 0, 1, '', '', '" . $version . "')
			");
		$DB->query_unbuffered("
				REPLACE INTO " . $prefix . "style
				(title, title_en, imagefolder, userselect, usedefault, parentid, parentlist, css, csscache, version)
				VALUES
				('" . lng('defaultstyle') . "', 'default', 'style_1', 1, 1, 1, 1, '', '', '" . $version . "')
			");
		$styleid = $DB->insert_id();
		$parentlist = $styleid . ',1';
		$DB->query_unbuffered("UPDATE " . $prefix . "style SET parentlist='" . $parentlist . "' WHERE styleid = $styleid");

		require_once(ROOT_PATH . 'includes/adminfunctions_template.php');
		$recachestyle = new adminfunctions_template();
		$recachestyle->rebuildallcaches(2);

		p_settingsite();
		p_footer('sitesetting', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'db' => $db,
			'prefix' => $prefix,
			'dbtype' => $dbtype)
		);
		break;

	case 'createtables':
		$connect_id = mysql_connect($hostname, $user, $pass);
		$version = @mysql_get_server_info($connect_id);
		$charset = false;
		if ($version > '4.1')
		{
			@mysql_query("SET NAMES 'utf8'", $connect_id);
			$charset = true;
		}
		mysql_select_db($db, $connect_id);
		create_tables($delete_existing, $charset);

		p_header();
		p_importstyles();
		p_footer(
			'importstyles', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'db' => $db,
			'prefix' => $prefix,
			'dbtype' => $dbtype)
		);
		break;

	case 'writeconfig':
		if (!write_access(ROOT_PATH . 'includes/config.php'))
		{
			p_errormsg(lng('error'), lng('chmoderror'));
		}
		else
		{
			$config = array(
				'servername' => $hostname,
				'dbname' => $db,
				'dbusername' => $user,
				'dbpassword' => $pass,
				'tableprefix' => $prefix,
				'superadmin' => $superadmin,
				'technicalemail' => $technicalemail,
				'dbtype' => $dbtype,
			);
			if($fp = @fopen(ROOT_PATH . 'includes/config.php', 'wb'))
			{
				fwrite($fp, config_file($config));
				fclose($fp);
			}

			$finished = p_done();
			p_header();
			echo $finished;
			p_footer();
		}
		break;

	case 'setprefix':
		mysql_connect($hostname, $user, $pass);

		$db = '';
		if ($name_db && $selected_db == '_usefield')
		{
			$db = $name_db;
		}
		else
		{
			$db = $selected_db;
		}

		if (!db_exists($db))
		{
			mxb_query("CREATE DATABASE " . $db);
			if (!db_exists($db))
			{
				p_errormsg(lng('error'), sprintf(lng('mysqlerror'), $db, mysql_error()));
			}
		}

		mysql_select_db($db);

		$tables = array();
		$r_table = mysql_query("SHOW TABLES FROM " . $db);
		while ($row = @mysql_fetch_array($r_table, MYSQL_NUM))
		{
			$tables[] = $row[0];
		}

		p_header();
		p_chooseprefix($db, $tables, $tableprefix);
		p_footer('createtables', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'db' => $db,
			'dbtype' => $dbtype
		));
		break;

	case 'selectdb':
		$dbhandle = @mysql_connect($hostname, $user, $pass);
		if (!$dbhandle)
		{
			p_errormsg(lng('error'), sprintf(lng('connecterror'), mysql_error()));
		}

		$vars = mysql_query("SHOW VARIABLES");
		while ($row = mysql_fetch_array($vars))
		{
			$var[$row[0]] = $row[1];
		}

		$r_database = @mysql_list_dbs();

		$databases = '';
		$i = 0;
		$selected = '';
		while ($i < @mysql_num_rows($r_database))
		{
			$dbs = mysql_tablename($r_database, $i);
			if (trim($dbname) == $dbs)
			{
				$selected = 'selected="selected"';
			}
			$databases .= '<option value="' . $dbs . '" ' . $selected . '>' . lng('existingdb') . ': ' . $dbs . '</option>';
			$i++;
			$selected = '';
		}

		p_header();
		p_selectdb($databases);
		p_footer('setprefix', array(
			'hostname' => $hostname,
			'user' => $user,
			'pass' => $pass,
			'tableprefix' => $tableprefix,
			'dbtype' => $dbtype)
		);
		break;

	case 'mysqldata':
		p_header();
		p_mysqldata();
		p_footer('selectdb');
		break;

	case 'license':
		p_header();
		p_license();
		p_footer('diraccess');
		break;

	case 'diraccess':
		if ($accept != 'yes')
		{
			p_errormsg(lng('error'), lng('licaccept'));
		}
		else
		{
			p_header();
			p_diraccess();
			break;
		}

	case 'deny':
		p_header();
		p_deny_install();
		p_footer();
		break;

	case 'welcome':
		p_header();
		p_welcome();
		p_footer('license');
		break;

	default:
		p_header();
		p_selectlang();
		p_footer('welcome', 0, 0);
		break;
}

?>
