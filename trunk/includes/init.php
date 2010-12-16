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

if (!defined('IN_MXB') || isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
{
	exit();
}

header('Content-Type:text/html; charset=UTF-8');

date_default_timezone_set(date_default_timezone_get());

define('STARTTIME', microtime(true)); // 统计PHP执行时间开始
define('IS_WIN', DIRECTORY_SEPARATOR == '\\'); // 服务器是否 Windows

// 获得基础路径的绝对路径
$dir = @realpath(ROOT_PATH);
if ($dir)
{
	if (IS_WIN)
	{
		$dir = str_replace('\\', '/', $dir);
	}
	if (substr($dir, -1) !== '/')
	{
		$dir .= '/';
	}
}
else
{
	$dir = ROOT_PATH;
}

define('ROOT_DIR', $dir);
define('DATA_DIR', ROOT_DIR . 'data/');
define('CACHE_DIR', ROOT_DIR . 'cache/');
define('LANGUAGE_DIR', ROOT_DIR . 'languanges/');

// 判断是否已安装
if (!@include(ROOT_PATH . 'includes/config.php'))
{
	echo 'The file "includes/config.php" does not exist. ';
	if (@file_exists(ROOT_PATH . 'install/install.php'))
	{
		echo '<br />You can run <a href="' . ROOT_PATH . 'install/install.php">install</a> to install MolyX Board';
	}
	exit();
}

require_once(ROOT_DIR . 'includes/functions_init.php');

if (!defined('IN_ACP'))
{
	define('IN_ACP', false);
}

// 判断开发模式
if (!defined('DEVELOPER_MODE'))
{
	define('DEVELOPER_MODE', false);
}
else if (DEVELOPER_MODE)
{
	$base_memory_usage = memory_get_usage();
}

$display_error = 0;
if (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS)
{
	// 如果打开 DISPLAY_ERRORS 后不显示错误信息可以去掉下面三行的注释符
	// 如果依然不显示请打开 ERROR_LOG, 通过 data/errorlog 下的日志文件查看错误
	/*
	if (function_exists('ini_set') && !@ini_get('display_errors'))
	{
		@ini_set('display_errors', 1);
	}
	*/

	$display_error = DEVELOPER_MODE ? (E_ALL | E_STRICT) : (E_ALL ^ E_NOTICE ^ E_DEPRECATED);
	debug::register();
}
error_reporting($display_error);

define('TIMENOW', isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time());
define('TODAY', strtotime('today'));

// PHP 6 以后不需要再执行下面的操作
if (PHP_VERSION < '6.0.0')
{
	/*
	if (function_exists('ini_set') && @ini_get('magic_quotes_runtime'))
	{
		@ini_set('magic_quotes_runtime', 0);
	}
	*/

	// 删除全局注册的变量
	$register_globals = @ini_get('register_globals');
	if ($register_globals == '1' || strtolower($register_globals) == 'on' || !function_exists('ini_get'))
	{
		deregister_globals();
	}

	// 去掉所有输入变量的魔法引号
	if (@get_magic_quotes_gpc())
	{
		stripslashes_deep($_REQUEST);
		stripslashes_deep($_GET);
		stripslashes_deep($_POST);
		stripslashes_deep($_COOKIE);

		if (is_array($_FILES))
		{
			foreach ($_FILES as $key => $val)
			{
				$_FILES[$key]['tmp_name'] = str_replace('\\', '\\\\', $val['tmp_name']);
			}
			stripslashes_deep($_FILES);
		}
	}
}


$ip = '';
foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_FROM') as $v)
{
	if (isset($_SERVER[$v]))
	{
		$ip = filter_var($_SERVER[$v], FILTER_VALIDATE_IP, ($v === 'HTTP_X_FORWARDED_FOR') ? FILTER_FLAG_NO_PRIV_RANGE : NULL);
		if ($ip !== false)
		{
			break;
		}
	}
}
define('IPADDRESS', $_SERVER['REMOTE_ADDR']);
define('ALT_IP', empty($ip) ? IPADDRESS : $ip);

if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'])
{
	$scriptpath = $_SERVER['REQUEST_URI'];
}
else if ((isset($_ENV['REQUEST_URI']) && $_ENV['REQUEST_URI']))
{
	$scriptpath = $_ENV['REQUEST_URI'];
}
else
{
	if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'])
	{
		$scriptpath = $_SERVER['PATH_INFO'];
	}
	else if (isset($_ENV['PATH_INFO']) && $_ENV['PATH_INFO'])
	{
		$scriptpath = $_ENV['PATH_INFO'];
	}
	else if (isset($_SERVER['REDIRECT_URL']) && $_SERVER['REDIRECT_URL'])
	{
		$scriptpath = $_SERVER['REDIRECT_URL'];
	}
	else if (isset($_ENV['REDIRECT_URL']) && $_ENV['REDIRECT_URL'])
	{
		$scriptpath = $_ENV['REDIRECT_URL'];
	}
	else if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'])
	{
		$scriptpath = $_SERVER['PHP_SELF'];
	}
	else
	{
		$scriptpath = $_ENV['PHP_SELF'];
	}

	$scriptpath .= '?';
	if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
	{
		$scriptpath .= $_SERVER['QUERY_STRING'];
	}
	else if (isset($_ENV['QUERY_STRING']) && $_ENV['QUERY_STRING'])
	{
		$scriptpath .= $_ENV['QUERY_STRING'];
	}
}
$scriptpath = xss_clean(remove_sid($scriptpath));
if (false !== ($quest_pos = strpos($scriptpath, '?')))
{
	$script = urldecode(substr($scriptpath, 0, $quest_pos));
	$scriptpath = $script . substr($scriptpath, $quest_pos);
}
else
{
	$script = $scriptpath = urldecode($scriptpath);
}
define('SCRIPTPATH', $scriptpath);
define('SCRIPT', $script);
define('USER_AGENT', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
define('REFERRER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
define('SUPERADMIN', $config['superadmin']);

define('VISIT_HASH', md5(IPADDRESS . '&' .  USER_AGENT));

define('CACHE_TABLE', $config['prefix'] . 'cache');
$DB = db::base($config);
unset($config);

if (!defined('USE_SHUTDOWN'))
{
	define('USE_SHUTDOWN', true);
}