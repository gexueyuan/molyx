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

class debug
{
	static private $obj = NULL;
	static private $debug = false;
	static private $log_file = '';

	public static function init()
	{
		self::$debug = (defined('DEVELOPER_MODE') && DEVELOPER_MODE);

		if (defined('ERROR_LOG') && ERROR_LOG)
		{
			self::$log_file = ROOT_DIR . 'data/errorlog/' . date('Ym') . '_log.txt';
		}

		if (self::$debug && self::$obj === NULL)
		{
			self::$obj = new Debug_Console(self::$log_file);
		}
	}

	public static function handler($errno, $errstr, $errfile, $errline)
	{
		if (!($errno & error_reporting()))
		{
			return;
		}

		static $errtype = array (
			1 => array('Error', 'E_ERROR'),
			2 => array('Warning', 'E_WARNING'),
			4 => array('Error', 'E_PARSE'),
			8 => array('Notice', 'E_NOTICE'),
			16 => array('Error', 'E_CORE_ERROR'),
			32 => array('Error', 'E_CORE_WARNING'),
			64 => array('Error', 'E_COMPILE_ERROR'),
			128 => array('Error', 'E_COMPILE_WARNING'),
			256 => array('Error', 'E_USER_ERROR'),
			512 => array('Warning', 'E_USER_WARNING'),
			1024 => array('Notice', 'E_USER_NOTICE'),
			2047 => array('Error', 'E_ALL'),
			2048 => array('Suggestion', 'E_STRICT'),
			8192 => array('Deprecated', 'E_DEPRECATED'),
			16384 => array('Deprecated', 'E_USER_DEPRECATED'),
		);

		static $c = array(
			'default' => '#000000',
			'keyword' => '#0000A0',
			'number'  => '#800080',
			'string'  => '#404040',
			'comment' => '#808080',
		);

		$errstr = str_replace("'", '"', $errstr);
		$errstr = str_replace('href="function.', 'target="_blank" href="http://www.php.net/', $errstr);
		$errstr = self::replace_root($errstr);

		if (!empty(self::$log_file))
		{
			$text = date('Y-m-d H:i:s') . '#' . $errno . ':' . $errstr . '@' . $errfile . ' Line ' . $errline . "\n-----------\n";
			file_put_contents(self::$log_file, $text, FILE_APPEND);
		}

		$errfile = self::replace_root($errfile);

		$trace = array();
		if ($this->debug)
		{
			while (ob_get_level())
			{
				ob_end_clean();
			}

			if (function_exists('debug_backtrace'))
			{
				$trace = debug_backtrace();
				array_shift($trace);

				echo '<script type="text/javascript" src="' . ROOT_PATH . 'scripts/error.js" defer="defer" charset="UTF-8"></script>';
			}
		}

		include ROOT_DIR . '/libraries/Debug/Tpl.php';

		if (self::$debug)
		{
			self::$obj->errorHandlerCallback($errno, $errstr, $errfile, $errline);
		}
	}

	/**
	 * 过滤目录字符串, 防止绝对路径暴露
	 *
	 * @param string $dirname 目录名
	 */
	private function replace_root($dirname)
	{
		$dir = format_path($dirname);
		if (strpos($dir, ROOT_DIR) !== false)
		{
			$dirname = str_replace(ROOT_DIR, '', $dir);
		}
		return $dirname;
	}

	/**
	 * show debug info of a variable in debugConsole,
	 * add own text for documentation or hints
	 */
	public static function dump($variable, $text)
	{
		if (!self::$debug)
		{
			var_dump($variable);
			return;
		}

		self::$obj->dump($variable, $text);
	}

	/**
	 * watch value changes of a variable in debugConsole
	 */
	public static function watch($name)
	{
		if (!self::$debug)
		{
			return;
		}

		return self::$obj->watchVariable($name);
	}

	/**
	 * show checkpoint info in debugConsole to make sure
	 * that a certain program line has been passed
	 */
	public static function here($message = NULL)
	{
		if (empty($message))
		{
			$message = NULL;
		}

		if (!self::$debug)
		{
			echo $message;
			return;
		}

		self::$obj->passedCheckpoint($message);
	}

	/**
	 * starts a new timer clock and returns its handle
	 */
	public static function start($comment = '')
	{
		if (!self::$debug)
		{
			return;
		}

		return self::$obj->startTimer($comment);
	}

	/**
	 * stops and shows a certain timer clock in debugConsole
	 */
	public static function stop($handle = NULL)
	{
		if (!self::$debug)
		{
			return;
		}
		return self::$obj->stopTimer($handle);
	}
}

debug::init();