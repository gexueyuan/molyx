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
class Db_Mysqli extends Db_Base
{
	function connect()
	{
		$config = $this->config;

		$port = !empty($config['port']) ? ':' . $config['port'] : NULL;

		$this->connect_id = @mysqli_connect($config['server'], $config['user'], $config['password'], $config['database'], $port);
		if ($this->connect_id && @mysqli_select_db($this->connect_id, $config['database']))
		{
			$this->version = mysqli_get_server_info($this->connect_id);

			if (!isset($config['charset']))
			{
				$config['charset'] = 'utf8';
			}

			if (version_compare($this->version, '5.0.6', '>=')) // version_compare(PHP_VERSION, '5.0.5', '>=')
			{
				mysqli_set_charset($this->connect_id, $config['charset']);
			}
			else
			{
				mysqli_query($this->connect_id, "SET
					character_set_results = '{$config['charset']}',
					character_set_client = '{$config['charset']}',
					character_set_connection = '{$config['charset']}',
					character_set_database = '{$config['charset']}',
					character_set_server = '{$config['charset']}'");
			}

			if (defined('EMPTY_SQL_MODE') && version_compare($this->version, '5.0.2', '>='))
			{
				$this->setSqlMode('');
			}
		}
		else
		{
			$this->halt('Can not connect MySQLi Server or DataBase');
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
		mysqli_query($this->connect_id, "SET SESSION sql_mode = '" . $this->escape_string($mode) . "'");
	}

	protected function _query($sql)
	{
		$this->query_id = @mysqli_query($this->connect_id, $sql);
	}

	protected function _queryUnbuffered($sql)
	{
		$this->query_id = @mysqli_real_query($this->connect_id, $sql);
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
		return @mysqli_fetch_assoc($query_id);
	}

	public function affectedRows()
	{
		return @mysqli_affected_rows($this->connect_id);
	}

	protected function _numRows($query_id)
	{
		return @mysqli_num_rows($query_id);
	}

	public function insertId()
	{
		//$id = $this->query_first('SELECT LAST_INSERT_ID() as id');
		return @mysqli_insert_id($this->connect_id);
	}

	protected function _escape($str)
	{
		return "'" . @mysqli_real_escape_string($this->connect_id, $str) . "'";
	}

	protected function _likeExpression($expression)
	{
		return $expression;
	}

	protected function _freeResult($query_id)
	{
		return @mysqli_free_result($query_id);
	}

	public function getTableNames()
	{
		$result = @mysqli_query($this->connect_id, 'SHOW TABLES FROM ' . $this->config['database']);
		$tables = array();
		while ($row = @mysqli_fetch_array($result))
		{
			$tables[] = $row[0];
		}
		@mysqli_free_result($result);
		return $tables;
	}

	public function _fetchField($query_id)
	{
		return @mysqli_fetch_field($query_id);
	}

	public function getError()
	{
		return @mysqli_error($this->connect_id);
	}

	/**
	 * 关闭数据库连接
	 */
	protected function _close()
	{
		return @mysqli_close($this->connect_id);
	}

	protected function _transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @mysqli_autocommit($this->connect_id, false);
			break;

			case 'commit':
				$result = @mysqli_commit($this->connect_id);
				@mysqli_autocommit($this->connect_id, true);
				return $result;
			break;

			case 'rollback':
				$result = @mysqli_rollback($this->connect_id);
				@mysqli_autocommit($this->connect_id, true);
				return $result;
			break;
		}

		return true;
	}

	public function report($mode, $query = '')
	{
		static $test_prof = NULL;

		// current detection method, might just switch to see the existance of INFORMATION_SCHEMA.PROFILING
		if ($test_prof === NULL)
		{
			$test_prof = false;
			if (strpos(mysqli_get_server_info($this->connect_id), 'community') !== false)
			{
				$ver = mysqli_get_server_version($this->connect_id);
				if ($ver >= 50037 && $ver < 50100)
				{
					$test_prof = true;
				}
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
						@mysqli_query($this->connect_id, 'SET profiling = 1;');
					}

					if ($result = @mysqli_query($this->connect_id, "EXPLAIN $explain_query"))
					{
						while ($row = @mysqli_fetch_assoc($result))
						{
							$html_table = $this->explain->addSelectRow($query, $html_table, $row);
						}
					}
					@mysqli_free_result($result);

					if ($html_table)
					{
						$this->explain->addHtmlHold('</table>');
					}

					if ($test_prof)
					{
						$html_table = false;

						// get the last profile
						if ($result = @mysqli_query($this->connect_id, 'SHOW PROFILE ALL;'))
						{
							$this->explain->addHtmlHold('<br /');
							while ($row = @mysqli_fetch_assoc($result))
							{
								// make <unknown> HTML safe
								if (!empty($row['Source_function']))
								{
									$row['Source_function'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $row['Source_function']);
								}

								// remove unsupported features
								foreach ($row as $key => $val)
								{
									if ($val === null)
									{
										unset($row[$key]);
									}
								}
								$html_table = $this->explain->addSelectRow($query, $html_table, $row);
							}
						}
						@mysqli_free_result($result);

						if ($html_table)
						{
							$this->explain->addHtmlHold('</table>');
						}

						@mysqli_query($this->connect_id, 'SET profiling = 0;');
					}
				}

			break;

			case 'fromcache':
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];

				$result = @mysqli_query($this->connect_id, $query);
				while ($void = @mysqli_fetch_assoc($result))
				{
					// Take the time spent on parsing rows into account
				}
				@mysqli_free_result($result);

				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->explain->recordFromCache($query, $endtime, $splittime);

			break;
		}
	}
}