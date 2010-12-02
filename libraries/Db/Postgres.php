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
class Db_Postgres extends Db_Base
{
	private $_sql = '';
	private $_fields_num = array();
	private $_fields_count = array();

	function connect()
	{
		$config = $this->config;

		$connect = '';

		if ($config['user'])
		{
			$connect .= "user={$config['user']} ";
		}

		if ($config['password'])
		{
			$connect .= "password={$config['password']} ";
		}

		if ($config['server'])
		{
			if ($config['server'] !== 'localhost')
			{
				$connect .= "host={$config['server']} ";
			}

			if ($config['port'])
			{
				$connect .= "port={$config['port']} ";
			}
		}

		$schema = '';

		if ($config['database'])
		{
			if (strpos($config['database'], '.') !== false)
			{
				list($config['database'], $schema) = explode('.', $config['database']);
			}
			$connect .= "dbname={$config['database']}";
		}

		if (!isset($config['new_link']))
		{
			$config['new_link'] = true;
		}

		if (!empty($config['persistency']))
		{
			$this->connect_id = $config['new_link'] ? @pg_pconnect($connect, PGSQL_CONNECT_FORCE_NEW) : @pg_pconnect($connect);
		}
		else
		{
			$this->connect_id = $config['new_link'] ? @pg_connect($connect, PGSQL_CONNECT_FORCE_NEW) : @pg_connect($connect);
		}

		if ($this->connect_id)
		{
			$this->version = pg_parameter_status($this->connect_id, 'server_version');
			if (version_compare($this->version, '8.2', '<'))
			{
				$this->multi_insert = false;
			}

			if ($schema !== '')
			{
				@pg_query($this->connect_id, 'SET search_path TO ' . $schema);
			}
		}
		else
		{
			$this->halt('Can not connect PostgresSQL Server or DataBase');
		}

		return true;
	}

	protected function _query($sql)
	{
		$this->query_id = @pg_query($this->connect_id, $sql);
		$this->_fields_count[(int) $this->query_id] = 0;
	}

	protected function _queryUnbuffered($sql)
	{
		$this->_sql = $sql;
		$this->query_id = @pg_query($this->connect_id, $sql);
	}

	protected function _queryLimit($sql, $total, $offset = 0, $cache_ttl = false, $cache_prefix = '')
	{
		if ($total > 0)
		{
			$sql .= "\n LIMIT $total OFFSET $offset";
		}

		return $this->query($sql, $cache_ttl, $cache_prefix);
	}

	protected function _fetch($query_id)
	{
		return @pg_fetch_assoc($query_id, NULL);
	}

	public function affectedRows()
	{
		return @pg_affected_rows($this->connect_id);
	}

	protected function _numRows($query_id)
	{
		return @pg_num_rows($query_id);
	}

	public function insertId()
	{
		$query_id = $this->query_id;

		if ($query_id !== false && $this->_sql != '')
		{
			if (preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is", $this->_sql, $tablename))
			{
				$query = "SELECT currval('" . $tablename[1] . "_seq') AS last_value";
				$temp = @pg_query($this->connect_id, $query);

				if (!$temp)
				{
					return false;
				}

				$temp_result = @pg_fetch_assoc($temp, NULL);
				@pg_free_result($query_id);

				return ($temp_result) ? $temp_result['last_value'] : false;
			}
		}

		return false;
	}

	protected function _escape($str)
	{
		$return = @pg_escape_string($str);
		return "'$return'";
	}

	protected function _likeExpression($expression)
	{
		return $expression;
	}

	protected function _freeResult($query_id)
	{
		$key = (int) $query_id;
		if (isset($this->_fields_num[$key]))
		{
			unset($this->_fields_num[$key], $this->_fields_count[$key]);
		}

		return @pg_free_result($query_id);
	}

	public function getTableNames()
	{
		$result = @pg_query($this->connect_id, 'SELECT relname FROM pg_statio_user_tables');
		$tables = array();
		while ($row = @pg_fetch_assoc($result, NULL))
		{
			$tables[] = $row['relname'];
		}
		@pg_free_result($result);
		return $tables;
	}

	public function _fetchField($query_id)
	{
		$key = (int) $query_id;
		if (!isset($this->fields_num[$key]))
		{
			$this->fields_num[$key] = @pg_num_fields($query_id);
		}

		if ($this->_fields_count[$key] == $this->fields_num[$key])
		{
			$this->_fields_count[$key] = 0;
		}

		return @pg_field_name($query_id, $this->_fields_count[$key]++);
	}

	public function getError()
	{
		return @pg_last_error($this->connect_id);
	}

	/**
	 * 关闭数据库连接
	 */
	protected function _close()
	{
		return @pg_close($this->connect_id);
	}

	protected function _transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @pg_query($this->connect_id, 'BEGIN');
			break;

			case 'commit':
				return @pg_query($this->connect_id, 'COMMIT');
			break;

			case 'rollback':
				return @pg_query($this->connect_id, 'ROLLBACK');
			break;
		}

		return true;
	}

	public function report($mode, $query = '')
	{
		switch ($mode)
		{
			case 'start':
				$explain_query = $query;
				if (preg_match('/UPDATE ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))
				{
					$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
				}
				else if (preg_match('/DELETE FROM ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))
				{
					$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
				}

				if (preg_match('/^SELECT/', $explain_query))
				{
					$html_table = false;

					if ($result = @pg_query($this->connect_id, "EXPLAIN $explain_query"))
					{
						while ($row = @pg_fetch_assoc($result, NULL))
						{
							$html_table = $this->explain->addSelectRow($query, $html_table, $row);
						}
					}
					@pg_free_result($result);

					if ($html_table)
					{
						$this->explain->addHtmlHold('</table>');
					}
				}
			break;

			case 'fromcache':
				$endtime = microtime(true);

				$result = @pg_query($this->connect_id, $query);
				while ($void = @pg_fetch_assoc($result, NULL))
				{
					// Take the time spent on parsing rows into account
				}
				@pg_free_result($result);

				$splittime = microtime(true);

				$this->explain->recordFromCache($query, $endtime, $splittime);
			break;
		}
	}
}