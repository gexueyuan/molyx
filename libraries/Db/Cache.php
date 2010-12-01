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
class Db_Cache
{
	private $db;
	private $row = array();
	private $count = array();
	private $pointer = array();

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function __destruct()
	{
		unset($this->db);
	}

	/**
	 * 载入 SQL 缓存
	 */
	public function load($sql, $prefix)
	{
		$name = $this->getName($sql, $prefix);
		$query_id = count($this->row);

		if (false === ($filename = $this->getPath($name)))
		{
			return false;
		}

		if (file_exists($filename))
		{
			include($filename);

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
			}
		}

		return false;
	}

	/**
	 * 缓存 SQL 结果
	 *
	 * @param array $rowset SQL 查询结果数组
	 * @param intager $ttl 有效时间 s
	 */
	public function save($sql, $rowset, $ttl, $prefix)
	{
		$name = $this->getName($sql, $prefix);

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
	public function clear($prefix = '')
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
	public function fetch($query_id)
	{
		$query_id = (int) $query_id;
		if ($this->pointer[$query_id] < $this->count[$query_id])
		{
			return $this->row[$query_id][$this->pointer[$query_id]++];
		}

		return false;
	}

	/**
	 * 读取字段名
	 */
	public function fetchFields($query_id)
	{
		$query_id = (int) $query_id;
		return array_keys(current($this->row[$query_id]));
	}

	public function freeResult($query_id)
	{
		$query_id = (int) $query_id;
		unset($this->row[$query_id], $this->count[$query_id], $this->pointer[$query_id]);
	}

	/**
	 * 读取数据数量
	 */
	public function num_rows($query_id)
	{
		$query_id = (int) $query_id;
		return $this->count[$query_id];
	}

	/**
	 * SQL 缓存路径
	 */
	private function getPath($path)
	{
		if (!SAFE_MODE)
		{
			$filename = str_replace('_', '/', $path) . 'php';
			$filename = ROOT_PATH . 'cache/sql/' . $filename;
			if (!checkdir($filename, true))
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

	private function getName($sql, $prefix)
	{
		$sql = str_replace(array("\n", "\r", "\t", '  '), ' ', $sql);
		if (strpos($sql, '  ') !== false)
		{
			$sql = preg_replace('/ +/', ' ', $sql);
		}

		return ($prefix ? $prefix . '_' : '') . md5($sql);
	}
}