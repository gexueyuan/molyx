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
class error_handler
{
	var $debug = false;
	var $error_log = true;
	var $root_path = '';
	var $error_reporting;
	var $trace_number = 1;
	var $skip_function = array(
		'include',
		'check_cache',
		'file_get_contents',
		'cache_load',
		'load_lang',
		'ini_get',
		'fopen',
		'fclose',
		'touch',
		'unlink',
		'start',
		'filemtime',
	);

	function error_handler()
	{
		$this->debug = (defined('DEVELOPER_MODE') && DEVELOPER_MODE);
		$this->error_log = (defined('ERROR_LOG') && ERROR_LOG);

		if (!$this->debug)
		{
			require_once(ROOT_PATH . 'includes/functions_init.php');
			$this->root_path = format_path(realpath(ROOT_PATH));
		}
		$this->error_reporting = error_reporting();
	}

	/**
	 * 错误处理函数
	 *
	 * @param intager $errno 错误类型
	 * @param string $errstr 错误信息
	 * @param string $errfile 出错文件
	 * @param intager $errline 出错行
	 */
	function handler($errno, $errstr, $errfile, $errline)
	{
		if (!($errno & $this->error_reporting) || THIS_SCRIPT == 'showsource')
		{
			// echo $errstr . ' ' . $errstr .  ' ' . $errfile . ' ' .$errline . '<br />'; // for notice
			return;
		}

		if ($errno == E_WARNING)
		{
			$start = (strpos($errstr, 'functions::') === 0) ? 11 : 0;
			$func = substr($errstr, $start, strpos($errstr, '(') - $start);
			if (in_array($func, $this->skip_function))
			{
				return;
			}
		}

		if ($this->error_log && !($errno & 1032))
		{
			$this->log_error($errno, $errstr, $errfile, $errline);
		}

		global $forums;
		if (isset($forums))
		{
			$lang = $forums->func->load_lang('debug', true);
		}
		else
		{
			include(ROOT_PATH . 'language/zh-cn/debug.php');
		}

		$errtype = array (
			1 => array('error', 'E_ERROR'),
			2 => array('warning', 'E_WARNING'),
			4 => array('error', 'E_PARSE'),
			8 => array('notice', 'E_NOTICE'),
			16 => array('error', 'E_CORE_ERROR'),
			32 => array('error', 'E_CORE_WARNING'),
			64 => array('error', 'E_COMPILE_ERROR'),
			128 => array('error', 'E_COMPILE_WARNING'),
			256 => array('error', 'E_USER_ERROR'),
			512 => array('warning', 'E_USER_WARNING'),
			1024 => array('notice', 'E_USER_NOTICE'),
			2047 => array('error', 'E_ALL'),
			2048 => array('warning', 'E_STRICT')
		);

		$c = array(
			'default' => '#000000',
			'keyword' => '#0000A0',
			'number'  => '#800080',
			'string'  => '#404040',
			'comment' => '#808080',
		);

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
				for ($i = 0; $i < $this->trace_number; $i++)
				{
					array_shift($trace);
				}

				echo '<script type="text/javascript" src="' . ROOT_PATH . 'scripts/error.js" defer="defer" charset="UTF-8"></script>';
			}
			include ROOT_PATH . 'includes/error/error_tpl.php';
			exit();
		}
		else
		{
			include ROOT_PATH . 'includes/error/error_tpl.php';
		}
	}

	/**
	 * 将错误记入文件
	 */
	function log_error($errno, $errstr, $errfile, $errline)
	{
		global $bbuserinfo;
		$array = array(
			'errno' => $errno,
			'errstr' => $errstr,
			'errfile' => $errfile,
			'errline' => $errline,
			'date' => date('Y-m-d H:i:s'),
			'userid' => isset($bbuserinfo['id']) ? $bbuserinfo['id'] : 0,
			'script' => SCRIPTPATH,
			'referrer' => REFERRER,
			'user_agent' => USER_AGENT,
			'ip' => IPADDRESS,
			'alt_ip' => ALT_IP,
		);
		$filename = ROOT_PATH . 'data/errorlog/' . date('Ymd') . '.php';
		$content = file_exists($filename) ? '' : '<' . "?php\n\$log_array = array();\n";
		$content .= '$log_array[] = ' . var_export($array, true) . ";\n";
		$fp = fopen($filename, 'a');
		fwrite($fp, $content);
		fclose($fp);
	}

	/**
	 * 过滤目录字符串, 防止绝对路径暴露
	 *
	 * @param string $dirname 目录名
	 */
	function replace_dir($dirname)
	{
		$dir = format_path($dirname);
		if ($this->root_path && strpos($dir, $this->root_path) !== false)
		{
			$dirname = str_replace($this->root_path, '.', $dir);
		}
		return $dirname;
	}
}
?>