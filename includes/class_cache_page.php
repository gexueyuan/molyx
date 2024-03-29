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
 * 全页面缓存
 */
class cache_page
{
	var $dir = '';
	var $name = '';
	var $ttl;

	/**
	 * @param intager $ttl 过期时间, 单位: s
	 * @param string $prefix 前缀
	 */
	function cache_page($ttl = 3600, $prefix = '')
	{
		$this->ttl = $ttl;
		$this->name = ROOT_PATH . 'cache/pages/';
		$prefix = $prefix ? THIS_SCRIPT . '_' . $prefix : THIS_SCRIPT;

		$prefix = str_replace('_', '/', $prefix);
		$count = count(explode('/', $prefix));
		$this->name .= $prefix . '/';
		checkdir($this->name);
	}

	/**
	 * 页面缓存开启
	 */
	function start()
	{
		if (!empty($_GET))
		{
			$input = $_GET;
		}

		if (!empty($_POST))
		{
			$input = array_merge($input, $_POST);
		}
		ksort($input);
		$this->name .= md5(SCRIPT . implode('&', $input)) . '.php';
		$cache_time = @filemtime($this->name);
		if ($cache_time && TIMENOW < ($cache_time + $this->ttl))
		{
			global $bboptions;
			if ($bboptions['gzipoutput'])
			{
				ob_start('ob_gzhandler');
			}

			//include($this->name);
			@readfile($this->name);
			exit();
		}
		else
		{
			ob_start();
		}
	}

	/**
	 * 写入缓存文件
	 */
	function end()
	{
		$content = ob_get_contents();
		file_write($this->name, $content);
	}

	/**
	 * 清理过期缓存
	 *
	 * @param intager $ttl 过期时间, 单位: s
	 */
	function garbage_clear($ttl, $dir = '')
	{
		if (empty($dir))
		{
			$dir = ROOT_PATH . 'cache/pages/';
		}
		$dh = opendir($dir);
		while (($entry = readdir($dh)) !== false)
		{
			if ($entry == '.' || $entry == '..')
			{
				continue;
			}
			$name = $dir . $entry;
			if (is_dir($name))
			{
				$this->garbage_clear($ttl, $name);
			}
			else if (is_file($name) && strrchr($entry, '.') == '.php' && TIMENOW > (filemtime($name) + $ttl))
			{
				@unlink($name);
			}
		}
		@closedir($dh);
	}

	/**
	 * 请空缓存
	 */
	function clear($prefix = '')
	{
		$prefix = $prefix ? $prefix . '_' : '';
		$dir = ROOT_PATH . 'cache/pages/' . str_replace('_', '/', $prefix);
		$dh = opendir($dir);
		while (($entry = readdir($dh)) !== false)
		{
			if ($entry == '.' || $entry == '..')
			{
				continue;
			}
			$name = $dir . $entry;

			if (is_dir($name))
			{
				$this->clear($name);
			}
			else if (is_file($name) && strrchr($entry, '.') == '.php')
			{
				@unlink($name);
			}
		}
		@closedir($dh);
	}
}
?>