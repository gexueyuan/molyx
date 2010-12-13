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
class Db_Sqlite extends Db_Base
{
	private $_fields_num = array();
	private $_fields_count = array();

	public function __construct($config)
	{
		parent::__construct($config);

		$this->any_char = chr(0) . '*';
		$this->one_char = chr(0) . '?';
	}

	function connect()
	{
		$config = $this->config;

		$error = '';
		$this->connect_id = !empty($config['persistency']) ? @sqlite_popen($config['server'], 0666, $error) : @sqlite_open($config['server'], 0666, $error);

		if ($this->connect_id)
		{
			$this->version = sqlite_libversion();

			@sqlite_query('PRAGMA short_column_names = 1', $this->connect_id);
//			if (!isset($config['charset']) || $config['charset'] == 'utf8')
//			{
//				$config['charset'] = 'UTF-8';
//			}
//			@sqlite_query('PRAGMA encoding = "' . $config['charset'] . '"', $this->connect_id);
		}
		else
		{
			$this->halt($error);
		}

		return true;
	}

	protected function _query($sql)
	{
		$this->query_id = @sqlite_query($sql, $this->connect_id);
		$this->_fields_count[(int) $this->query_id] = 0;
	}

	protected function _queryUnbuffered($sql)
	{
		$this->query_id = @sqlite_unbuffered_query($sql, $this->connect_id);
	}

	protected function _queryLimit($sql, $total, $offset = 0, $cache_ttl = false, $cache_prefix = '')
	{
		if ($total > 0)
		{
			$sql .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);
		}

		return $this->query($sql, $cache_ttl, $cache_prefix);
	}

	protected function _fetch($query_id)
	{
		return @sqlite_fetch_array($query_id, SQLITE_ASSOC);
	}

	public function affectedRows()
	{
		return @sqlite_changes($this->connect_id);
	}

	protected function _numRows($query_id)
	{
		return @sqlite_num_rows($query_id);
	}

	public function insertId()
	{
		//$id = $this->queryFirst('SELECT LAST_INSERT_ID() as id');
		return @sqlite_last_insert_rowid($this->connect_id);
	}

	protected function _escape($str)
	{
		$return = @sqlite_escape_string($str);
		return "'$return'";
	}

	protected function _likeExpression($expression)
	{
		return 'GLOB ' . $expression;
	}

	protected function _freeResult($query_id)
	{
		$query_id = (int) $query_id;
		if (isset($this->_fields_num[$query_id]))
		{
			unset($this->_fields_num[$query_id], $this->_fields_count[$query_id]);
		}

		return true;
	}

	public function getTableNames()
	{
		$result = @sqlite_query("SELECT name FROM sqlite_master
			WHERE type='table'
			ORDER BY name", $this->connect_id);
		$tables = array();
		while ($row = @sqlite_fetch_array($result, SQLITE_ASSOC))
		{
			$tables[] = $row['name'];
		}

		return $tables;
	}

	public function _fetchField($query_id)
	{
		$key = (int) $query_id;
		if (!isset($this->fields_num[$key]))
		{
			$this->fields_num[$key] = @sqlite_num_fields($query_id);
		}

		if ($this->_fields_count[$key] == $this->fields_num[$key])
		{
			$this->_fields_count[$key] = 0;
		}

		return @sqlite_field_name($query_id, $this->_fields_count[$key]++);
	}

	public function getError()
	{
		return @sqlite_error_string(sqlite_last_error($this->connect_id));
	}

	/**
	 * 关闭数据库连接
	 */
	protected function _close()
	{
		return @sqlite_close($this->connect_id);
	}

	protected function _transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @sqlite_query('BEGIN', $this->connect_id);
			break;

			case 'commit':
				return @sqlite_query('COMMIT', $this->connect_id);
			break;

			case 'rollback':
				return @sqlite_query('ROLLBACK', $this->connect_id);
			break;
		}

		return true;
	}

	public function report($mode, $query = '')
	{
		switch ($mode)
		{
			case 'start':
			break;

			case 'fromcache':
				$endtime = microtime(true);

				$result = @sqlite_query($query, $this->connect_id);
				while ($void = @sqlite_fetch_array($result, SQLITE_ASSOC))
				{
					// Take the time spent on parsing rows into account
				}

				$splittime = microtime(true);

				$this->explain->recordFromCache($query, $endtime, $splittime);
			break;
		}
	}
}