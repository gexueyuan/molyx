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
class Db_Mysql extends Db_Base
{
	function connect()
	{
		$config = $this->config;

		$server = $config['server'] . (!empty($config['port']) ? ':' . $config['port'] : '');
		if (!isset($config['new_link']))
		{
			$config['new_link'] = true;
		}

		$this->connect_id = !empty($config['persistency']) ?
			@mysql_pconnect($server, $config['user'], $config['password'], $config['new_link']) :
			@mysql_connect($server, $config['user'], $config['password'], $config['new_link']);

		if ($this->connect_id && @mysql_select_db($config['database'], $this->connect_id))
		{
			$this->version = mysql_get_server_info($this->connect_id);

			if ($this->version > '4.1')
			{
				if (!isset($config['charset']))
				{
					$config['charset'] = 'utf8';
				}

				if (version_compare($this->version, '5.0.7', '>=')) // version_compare(PHP_VERSION, '5.2.3', '>=')
				{
					mysql_set_charset($config['charset'], $this->connect_id);
				}
				else
				{
					mysql_query("SET
						character_set_results = '{$config['charset']}',
						character_set_client = '{$config['charset']}',
						character_set_connection = '{$config['charset']}',
						character_set_database = '{$config['charset']}',
						character_set_server = '{$config['charset']}'", $this->connect_id);
				}
			}

			if (defined('EMPTY_SQL_MODE') && version_compare($this->version, '5.0.2', '>='))
			{
				$this->setSqlMode('');
			}
		}
		else
		{
			$this->halt('Can not connect MySQL Server or DataBase');
		}

		return true;
	}

	/**
	 * 设置 MySQL 不同的 sql_mode 可以解决一些 MySQL 的兼容问题
	 *
	 * @param string $mode
	 */
	public function setSqlMode($mode = '')
	{
		@mysql_query("SET SESSION sql_mode = '" . $this->escape($mode) . "'", $this->connect_id);
	}

	protected function _query($sql)
	{
		$this->query_id = @mysql_query($sql, $this->connect_id);
	}

	protected function _queryUnbuffered($sql)
	{
		$this->query_id = @mysql_unbuffered_query($sql, $this->connect_id);
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
		return @mysql_fetch_assoc($query_id);
	}

	public function affectedRows()
	{
		return @mysql_affected_rows($this->connect_id);
	}

	protected function _numRows($query_id)
	{
		return @mysql_num_rows($query_id);
	}

	public function insertId()
	{
		//$id = $this->query_first('SELECT LAST_INSERT_ID() as id');
		return @mysql_insert_id($this->connect_id);
	}

	protected function _escape($str)
	{
		$return = '';
		if ($this->connect_id)
		{
			$return = @mysql_real_escape_string($str, $this->connect_id);
		}
		else
		{
			$return = @mysql_escape_string($str);
		}

		return "'$return'";
	}

	protected function _likeExpression($expression)
	{
		return $expression;
	}

	protected function _freeResult($query_id)
	{
		return @mysql_free_result($query_id);
	}

	public function getTableNames()
	{
		$result = @mysql_query('SHOW TABLES FROM ' . $this->config['database'], $this->connect_id);
		$tables = array();
		while ($row = @mysql_fetch_row($result))
		{
			$tables[] = $row[0];
		}
		@mysql_free_result($result);
		return $tables;
	}

	public function _fetchField($query_id)
	{
		return @mysql_fetch_field($query_id);
	}

	public function getError()
	{
		return @mysql_error($this->connect_id);
	}

	/**
	 * 关闭数据库连接
	 */
	protected function _close()
	{
		return @mysql_close($this->connect_id);
	}

	protected function _transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @mysql_query('BEGIN', $this->connect_id);
			break;

			case 'commit':
				return @mysql_query('COMMIT', $this->connect_id);
			break;

			case 'rollback':
				return @mysql_query('ROLLBACK', $this->connect_id);
			break;
		}

		return true;
	}

	public function report($mode, $query = '')
	{
		static $test_prof = NULL;

		if ($test_prof === null)
		{
			$test_prof = false;
			if (version_compare($this->version, '5.0.37', '>=') && version_compare($this->version, '5.1', '<'))
			{
				$test_prof = true;
			}
		}

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

					// begin profiling
					if ($test_prof)
					{
						@mysql_query('SET profiling = 1;', $this->connect_id);
					}

					if ($result = @mysql_query("EXPLAIN $explain_query", $this->connect_id))
					{
						while ($row = @mysql_fetch_assoc($result))
						{
							$html_table = $this->explain->addSelectRow($query, $html_table, $row);
						}
					}
					@mysql_free_result($result);

					if ($html_table)
					{
						$this->explain->addHtmlHold('</table>');
					}

					if ($test_prof)
					{
						$html_table = false;

						// get the last profile
						if ($result = @mysql_query('SHOW PROFILE ALL;', $this->connect_id))
						{
							$this->explain->addHtmlHold('<br />');
							while ($row = @mysql_fetch_assoc($result))
							{
								// make <unknown> HTML safe
								if (!empty($row['Source_function']))
								{
									$row['Source_function'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $row['Source_function']);
								}

								// remove unsupported features
								foreach ($row as $key => $val)
								{
									if ($val === NULL)
									{
										unset($row[$key]);
									}
								}
								$html_table = $this->explain->addSelectRow($query, $html_table, $row);
							}
						}
						@mysql_free_result($result);

						if ($html_table)
						{
							$this->explain->addHtmlHold('</table>');
						}

						@mysql_query('SET profiling = 0;', $this->connect_id);
					}
				}
			break;

			case 'fromcache':
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];

				$result = @mysql_query($query, $this->connect_id);
				while ($void = @mysql_fetch_assoc($result))
				{
					// Take the time spent on parsing rows into account
				}
				@mysql_free_result($result);

				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->explain->recordFromCache($query, $endtime, $splittime);
			break;
		}
	}
}