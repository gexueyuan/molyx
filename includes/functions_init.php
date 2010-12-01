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

/**
 * PHP5 自动加载对象文件
 */
function __autoload($class_name)
{
	$path = ROOT_PATH . 'libraries/';
	$path .= (strpos($class_name, '_') !== false) ? str_replace('_', '/', $class_name) : $class_name;
    require $path . '.php';
}

/**
 * 删除所有 register_globals 建立的变量
 */
function deregister_globals()
{
	$not_unset = array(
		'GLOBALS' => true,
		'_GET' => true,
		'_POST' => true,
		'_COOKIE' => true,
		'_REQUEST' => true,
		'_SERVER' => true,
		'_SESSION' => true,
		'_ENV' => true,
		'_FILES' => true
	);

	if (!isset($_SESSION) || !is_array($_SESSION))
	{
		$_SESSION = array();
	}

	$input = array_merge(
		array_keys($_GET),
		array_keys($_POST),
		array_keys($_COOKIE),
		array_keys($_SERVER),
		array_keys($_SESSION),
		array_keys($_ENV),
		array_keys($_FILES)
	);

	foreach ($input as $varname)
	{
		if (isset($not_unset[$varname]))
		{
			if ($varname !== 'GLOBALS' || isset($_GET['GLOBALS']) || isset($_POST['GLOBALS']) || isset($_SERVER['GLOBALS']) || isset($_SESSION['GLOBALS']) || isset($_ENV['GLOBALS']) || isset($_FILES['GLOBALS']))
			{
				exit;
			}
			else
			{
				$cookie = &$_COOKIE;
				while (isset($cookie['GLOBALS']))
				{
					foreach ($cookie['GLOBALS'] as $registered_var => $value)
					{
						if (!isset($not_unset[$registered_var]))
						{
							unset($GLOBALS[$registered_var]);
						}
					}
					$cookie = &$cookie['GLOBALS'];
				}
			}
		}

		unset($GLOBALS[$varname]);
	}

	unset($input);
}

/**
 * 去除 magic_quotes 的修改
 *
 * @param mixed $value
 * @param integer $depth
 */
function stripslashes_deep(&$value, $depth = 0)
{
	if (is_array($value))
	{
	    foreach ($value as $key => $val)
	    {
	        if (is_string($val))
	        {
	            $value[$key] = stripslashes($val);
	        }
	        else if (is_array($val) && $depth < 10)
	        {
	            stripslashes_deep($value[$key], $depth + 1);
	        }
	    }
	}
}

/**
 * 清除跨站脚本
 */
function xss_clean($var)
{
	return preg_replace('/(java|vb)script/i', '\\1 script', utf8::htmlspecialchars($var));
}

/**
 * 删除链接中的 sid
 *
 * @param string $url
 */
function remove_sid($url)
{
	if (strpos($url, '?s=') !== false)
	{
		$url = preg_replace('/(\?)s=[a-z0-9]+(&amp;|&)?/', '\1', $url);
	}
	else if (strpos($url, '&s=') !== false)
	{
		$url = preg_replace('/&s=[a-z0-9]+(&)?/', '\1', $url);
	}
	else if (strpos($url, '&amp;s=') !== false)
	{
		$url = preg_replace('/&amp;s=[a-z0-9]+(&amp;)?/', '\1', $url);
	}

	return $url;
}

/**
 * 格式化路径，将 \ 和 // 转换为 /
 */
function format_path($path)
{
	if (strpos($path, '\\') !== false)
	{
		$path = str_replace('\\', '/', $path);
	}
	if (strpos($path, '//') !== false)
	{
		$path = str_replace('//', '/', $path);
	}
	return $path;
}

/**
 * 读取保存串行化字符串的文件
 * .php 文件去除开头的结束语句
 */
function read_serialize_file($filename)
{
	if ($return = @file_get_contents($filename))
	{
		if (strrchr($filename, '.') == '.php')
		{
			$return = substr($return, 14);
		}
		if ($return === 'a:0:{}')
		{
			return array();
		}
		else
		{
			return @unserialize($return);
		}
	}
	return false;
}