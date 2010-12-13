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
class Db_Pdo_Mysql extends Db_Pdo_Base
{
	function connect($server, $user, $password, $database)
	{
		$config = $this->config;

		$dsn = 'mysql:host=' . $config['server'] . ';dbname=' . $config['database'];
		if (!empty($config['port']))
		{
			$dsn .= ';port=' . $config['port'];
		}

		$param = NULL;
		if (!empty($config['persistency']))
		{
			$param = array(PDO::ATTR_PERSISTENT => true);
		}

		$this->pdo = new PDO($dsn, $config['user'], $config['password'], $param);

		if ($this->pdo)
		{
			$this->version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

			if ($this->version > '4.1')
			{
				if (!isset($config['charset']))
				{
					$config['charset'] = 'utf8';
				}

				$this->pdo->exec("SET
					character_set_results = '{$config['charset']}',
					character_set_client = '{$config['charset']}',
					character_set_connection = '{$config['charset']}',
					character_set_database = '{$config['charset']}',
					character_set_server = '{$config['charset']}'");
			}

			if (defined('EMPTY_SQL_MODE') && $this->version > '5.0')
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
	function setSqlMode($mode = '')
	{
		$this->pdo->exec("SET SESSION sql_mode = " . $this->pdo->quote($mode));
	}

	protected function _queryLimit($sql, $total, $offset = 0, $cache_ttl = false, $cache_prefix = '')
	{
		if ($total > 0)
		{
			$sql .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);
		}

		return $this->query($sql, $cache_ttl, $cache_prefix);
	}

	public function getTableNames()
	{
		$stmt = $this->pdo->query('SHOW TABLES FROM ' . $this->config['database']);
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$tables = array();
		while ($row = $stmt->fetch())
		{
			$tables[] = $row[0];
		}
		$stmt = NULL;

		return $tables;
	}

	public function report($mode, $query = '')
	{
		static $test_prof = NULL;

		if ($test_prof === NULL)
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
						$this->pdo->exec('SET profiling = 1;');
					}

					if ($result = $this->pdo->query("EXPLAIN $explain_query"))
					{
						$result->setFetchMode(PDO::FETCH_ASSOC);
						while ($row = $result->fetch())
						{
							$html_table = $this->explain->addSelectRow($query, $html_table, $row);
						}
					}
					$result = NULL;

					if ($html_table)
					{
						$this->explain->addHtmlHold('</table>');
					}

					if ($test_prof)
					{
						$html_table = false;

						// get the last profile
						if ($result = $this->pdo->query('SHOW PROFILE ALL;'))
						{
							$this->explain->addHtmlHold('<br />');
							$result->setFetchMode(PDO::FETCH_ASSOC);
							while ($row = $result->fetch())
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
						$result = NULL;

						if ($html_table)
						{
							$this->explain->addHtmlHold('</table>');
						}

						$this->pdo->exec('SET profiling = 0;');
					}
				}
			break;

			case 'fromcache':
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];

				$result = $this->pdo->query($query);
				$result->setFetchMode(PDO::FETCH_ASSOC);
				while ($void = $result->fetch())
				{
					// Take the time spent on parsing rows into account
				}
				$result = NULL;

				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->explain->recordFromCache($query, $endtime, $splittime);
			break;
		}
	}
}