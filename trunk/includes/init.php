<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
if (!defined('IN_MXB') || isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
{
	exit();
}
define('STARTTIME', microtime()); // 统计PHP执行时间开始
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
	/**
	 * 获得两次执行之间代码的运行时间
	 */
	function runtime()
	{
		static $starttime = 0;
		$mtime = explode(' ', microtime());
		if ($starttime === 0)
		{
			$starttime = $mtime[1] + $mtime[0];
			return;
		}
		printf('%6fs', $mtime[1] + $mtime[0] - $starttime);
		$starttime = 0;
	}

	$base_memory_usage = 0;
	if (function_exists('memory_get_usage'))
	{
		$base_memory_usage = memory_get_usage();
	}
}

if (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS)
{
	// 如果打开 DISPLAY_ERRORS 后不显示错误信息可以去掉下面三行的注释符
	// 如果依然不显示请打开 ERROR_LOG, 通过 data/errorlog 下的日志文件查看错误
	//if (function_exists('ini_set') && !@ini_get('display_errors'))
	//{
	//	@ini_set('display_errors', 1);
	//}
	if (!defined('E_DEPRECATED'))
	{
		define('E_DEPRECATED', 8192);
	}
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

	require_once(ROOT_PATH . 'includes/error/error_handler.php');
	$error_handler = new error_handler();
	set_error_handler(array(&$error_handler, 'handler'));
}
else
{
	error_reporting(0);
}

// 防止 PHP 5.1.x 使用时间函数报错
if (function_exists('date_default_timezone_set'))
{
    date_default_timezone_set(date_default_timezone_get());
}
define('TIMENOW', isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time());
define('TODAY', strtotime(date('Y-m-d 00:00:00', TIMENOW)));

// PHP 6 以后不需要再执行下面的操作
if (PHP_VERSION < '6.0.0')
{
	if (version_compare(PHP_VERSION, '5.3.0', '<'))
	{
		@set_magic_quotes_runtime(0);
	}

	// 删除全局注册的变量
	$register_globals = @ini_get('register_globals');
	if ($register_globals == '1' || strtolower($register_globals) == 'on' || !function_exists('ini_get'))
	{
		deregister_globals();
	}

	// 去掉所有输入变量的魔法引号
	define('MAGIC_QUOTES_GPC', @get_magic_quotes_gpc() ? true : false);
	if (MAGIC_QUOTES_GPC)
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

	define('SAFE_MODE', (@ini_get('safe_mode') || @strtolower(ini_get('safe_mode')) == 'on') ? true : false);
}
else
{
	define('MAGIC_QUOTES_GPC', false);
	define('SAFE_MODE', false);
}

$ip = $_SERVER['REMOTE_ADDR'];
define('IPADDRESS', $ip);

if (isset($_SERVER['HTTP_CLIENT_IP']))
{
	$ip = $_SERVER['HTTP_CLIENT_IP'];
}
else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
{
	foreach ($matches[0] as $v)
	{
		if (!preg_match("#^(10|172\.16|192\.168)\.#", $v))
		{
			$ip = $v;
			break;
		}
	}
}
else if (isset($_SERVER['HTTP_FROM']))
{
	$ip = $_SERVER['HTTP_FROM'];
}
define('ALT_IP', $ip);

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

$DB = null;
if (!defined('WITHOUT_DB') || !WITHOUT_DB)
{
	define('TABLE_PREFIX', $config['tableprefix']);
	define('CACHE_TABLE', $config['tableprefix'] . 'cache');

	$db_file = 'mysql';
	if (in_array($config['dbtype'], array('mysqli', 'pdo')))
	{
		$db_file = $config['dbtype'];
	}
	require_once(ROOT_PATH . 'includes/db/db_base.php');
	require_once(ROOT_PATH . 'includes/db/db_' . $db_file . '.php');

	$DB = new db;
	$DB->technicalemail = $config['technicalemail'];
	$DB->connect($config['servername'], $config['dbusername'], $config['dbpassword'], $config['dbname']);
}
unset($config);

if (!defined('USE_SHUTDOWN'))
{
	define('USE_SHUTDOWN', true);
}