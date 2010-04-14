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
class db_cache
{
	var $row = array();
	var $count = array();
	var $pointer = array();

	/**
	 * 载入 SQL 缓存
	 */
	function load($sql, $prefix)
	{
		$name = $prefix ? $prefix . '_' : '';
		$name .= md5(preg_replace('/[\n\r\s\t]+/', ' ', $sql));
		$query_id = count($this->row);

		if (false === ($filename = $this->get_path($name)))
		{
			return false;
		}

		@include($filename);

		if (isset($data))
		{
			$this->row[$query_id] = $data;
			$this->count[$query_id] = count($data);
			$this->pointer[$query_id] = 0;

			return $query_id;
		}
		else
		{
			@unlink($filename);
			return false;
		}
	}

	/**
	 * 缓存 SQL 结果
	 *
	 * @param array $rowset SQL 查询结果数组
	 * @param intager $ttl 有效时间 s
	 */
	function save($sql, $rowset, $ttl, $prefix)
	{
		$name = $prefix ? $prefix . '_' : '';
		$name .= md5(preg_replace('/[\n\r\s\t]+/', ' ', $sql));

		$query_id = count($this->row);
		$this->row[$query_id] = $rowset;
		$this->count[$query_id] = count($rowset);
		$this->pointer[$query_id] = 0;

		$rowset = '<?' . 'php' . "\n" .
			($ttl > 0 ? 'if (' . TIMENNOW + $ttl . ' < TIMENNOW) return;' . "\n" : '') .
			'$data = ' . var_export($rowset['data'], true) . ";\n?" . '>';
		$filename = $this->get_path($name);
		file_write($filename, $rowset);
		return $query_id;
	}

	/**
	 * 清理缓存
	 * @param string $prefix 空为清理全部
	 */
	function clear($prefix = '')
	{
		$prefix = $prefix ? $prefix . '_' : '';
		$dir = ROOT_PATH . 'cache/sql/' . (!SAFE_MODE ? str_replace('_', '/', $prefix) : '');
		$dh = opendir($dir);
		while (($entry = readdir($dh)) !== false)
		{
			if ($entry == '.' || $entry == '..')
			{
				continue;
			}
			$name = $dir . $entry;
			if (!SAFE_MODE)
			{
				if (is_dir($name))
				{
					$this->clear($name);
				}
				else if (is_file($name) && strrchr($entry, '.') == '.php')
				{
					@unlink($name);
				}
			}
			else if (is_file($name) && strpos($entry, $prefix) === 0)
			{
				@unlink($name);
			}
		}
		@closedir($dh);
	}

	/**
	 * 从缓存中 fetch 资料
	 */
	function fetch_array($query_id)
	{
		if ($this->pointer[$query_id] < $this->count[$query_id])
		{
			return $this->row[$query_id][$this->pointer[$query_id]++];
		}

		return false;
	}

	/**
	 * SQL 缓存路径
	 */
	function get_path($path)
	{
		if (!SAFE_MODE)
		{
			$filename = str_replace('_', '/', $path) . 'php';
			$filename = ROOT_PATH . 'cache/sql/' . $filename;
			if (!checkdir($filename, $count, true))
			{
				return false;
			}
		}
		else
		{
			$filename = $path . '.php';
		}
		return $filename;
	}
}
?>