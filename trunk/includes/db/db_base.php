<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
class db_base
{
	var $connect_id = '';
	var $database = '';
	var $type = 'mysql';
	var $open_queries = array();
	var $shutdown_queries = array();
	var $query_result = '';
	var $query_count = 0;
	var $return_die = 0;
	var $failed = 0;
	var $version = 0;
	var $technicalemail = '';
	var $debug = false;
	var $explain = null;
	var $cache = null;

	function debug_init()
	{
		if (defined('DEVELOPER_MODE') && DEVELOPER_MODE)
		{
			$this->debug = true;
			if (!empty($_GET['explain']))
			{
				require_once(ROOT_PATH . 'includes/db/db_explain.php');
				$this->explain = new db_explain($this);
			}
		}
	}

	/**
	 * 执行 INSERT.
	 */
	function insert($table, $array, $type = 'INSERT', $func = 'query_unbuffered')
	{
		if (($sql = $this->sql_insert($table, $array, $type)))
		{
			return $this->$func($sql);
		}
		return false;
	}

	/**
	 * 执行 SHUTDOWN INSERT.
	 */
	function shutdown_insert($table, $array)
	{
		return $this->insert($table, $array, 'INSERT', 'shutdown_query');
	}

	/**
	 * 多列 INSERT
	 */
	function multi_insert($table, $array)
	{
		return $this->insert($table, $array, 'MULTI_INSERT');
	}

	/**
	 * 多列 INSERT
	 */
	function insert_select($table, $array)
	{
		return $this->insert($table, $array, 'INSERT_SELECT');
	}

	/**
	 * 执行 REPLACE.
	 */
	function replace($table, $array, $type = 'INSERT')
	{
		if (($sql = $this->sql_replace($table, $array, $type)))
		{
			return $this->query_unbuffered($sql);
		}
		return false;
	}

	/**
	 * 多列 INSERT
	 */
	function multi_replace($table, $array)
	{
		return $this->replace($table, $array, 'MULTI_INSERT');
	}

	/**
	 * 执行 UPDATE.
	 */
	function update($table, $array, $where = '', $func = 'query_unbuffered')
	{

		if (($sql = $this->sql_update($table, $array, $where)))
		{
			return $this->$func($sql);
		}
		return false;
	}

	/**
	 * 执行 SHUTDOWN UPDATE.
	 */
	function shutdown_update($table, $array, $where = '')
	{
		return $this->update($table, $array, $where, 'shutdown_query');
	}

	/**
	 * 执行 UPDATE, 使用 CASE 语法根据不同的 id_filed 更新不同的值
	 */
	function update_case($table, $id_filed, $sql_array)
	{
		if (($sql = $this->sql_update_case($table, $id_filed, $sql_array)))
		{
			return $this->query_unbuffered($sql);
		}
		return false;
	}

	/**
	 * 执行 DELETE.
	 */
	function delete($table, $where = '', $func = 'query_unbuffered')
	{
		$sql = $this->sql_delete($table, $where);
		return $this->$func($sql);
	}

	/**
	 * 执行 SHUTDOWN DELETE.
	 */
	function shutdown_delete($table, $where = '')
	{
		return $this->delete($table, $where, 'shutdown_query');
	}

	/**
	 * 执行 SELECT
	 *
	 * @param mixed $sql 构造 SELECT 的数组或 SQL 语句
	 */
	function select($sql, $cache_ttl = false, $cache_prefix = '', $type = 'SELECT')
	{
		if (is_array($sql))
		{
			$sql = $this->sql_select($type, $sql);
		}
		return $this->query($sql, $cache_ttl, $cache_prefix);
	}

	/**
	 * 使用 select() 方法执行 SELECT DISTINCT
	 */
	function select_distinct($array, $cache_ttl = false, $cache_prefix = '')
	{
		return $this->select($array, $cache_ttl, $cache_prefix, 'SELECT DISTINCT');
	}

	/**
	 * 读取第一行
	 *
	 * @param mixed $sql SQL 语句或者构造 SELECT 的数组
	 */
	function query_first($sql = '', $cache_ttl = false, $cache_prefix = '')
	{
		if (is_array($sql))
		{
			if (!isset($sql['LIMIT']))
			{
				$sql['LIMIT'] = 1;
			}
			$sql = $this->sql_select('SELECT', $sql);
		}
		else if (strpos($sql, 'LIMIT ') === false)
		{
			$sql .= ' LIMIT 1';
		}

		$result = $this->query($sql, $cache_ttl, $cache_prefix);
		$row = $this->fetch_array($result);
		$this->free_result($result);
		return $row;
	}

	function shutdown_query($sql = '')
	{
		if (USE_SHUTDOWN)
		{
			$this->shutdown_queries[] = $sql;
		}
		else
		{
			if ($this->explain !== null)
			{
				$this->explain->shutdown = true;
			}
			$this->query_unbuffered($sql);
		}
	}

	/**
	 * 返回执行过的查询数
	 */
	function query_count()
	{
		return $this->query_count;
	}

	/**
	 * 关闭数据库连接
	 */
	function close_db()
	{
		$this->return_die = 0;
		if (!empty($this->shutdown_queries))
		{
			foreach($this->shutdown_queries as $query)
			{
				$this->query_unbuffered($query);
			}
		}
		$this->return_die = 1;
		$this->shutdown_queries = array();

		if ($this->connect_id)
		{
			if (!empty($this->open_queries))
			{
				foreach ($this->open_queries as $query_id)
				{
					$this->free_result($query_id);
				}
			}
			return $this->_close_db();
		}
		return false;
	}

	/**
	 * 验证 MySQL 语句中的用到的变量
	 */
	function validate($var)
	{
		if (is_numeric($var))
		{
			if (is_string($var) && $var[0] == '0')
			{
				return "'$var'";
			}
			return $var;
		}
		else if (is_bool($var))
		{
			return intval($var);
		}
		else if (is_array($var))
		{
			$var = serialize($var);
		}
		return "'" . $this->escape_string($var) . "'";
	}

	function halt($message = '')
	{
		require_once(ROOT_PATH . 'includes/db/db_error.php');
		db_error($message, $this);
	}

	/**
	 * 建立 SQL 缓存对象
	 */
	function init_cache()
	{
		require_once(ROOT_PATH . 'includes/db/db_cache.php');
		$this->cache = new db_cache();
	}

	/**
	 * 读取使用数据库缓存的数据
	 */
	function read_cache($name = '')
	{
		$where = !empty($name) ? ' WHERE ' . $this->sql_in('title', $name) : '';

		$cache = array();
		$result = $this->query('SELECT `title`, `data`, `is_array`, `time`
			FROM ' . CACHE_TABLE . "
			$where");
		while ($row = $this->fetch_array($result))
		{
			if ($row['time'] == 0 || $row['time'] > TIMENOW)
			{
				if ($row['is_array'])
				{
					$row['data'] = @unserialize($row['data']);
				}
				$cache[$row['title']] = $row['data'];
			}
			else
			{
				$cache[$row['title']] = false;
			}
		}
		unset($row);
		$this->free_result($result);
		return $cache;
	}

	/**
	 * 更新使用数据库缓存的数据
	 * array(
	 * 	array('名称', '数据', '存活时间')
	 * )
	 */
	function update_cache($data, $value = '', $ttl = 0)
	{
		if (!is_array($data))
		{
			$data = array(array($data, $value, $ttl));
		}

		$sql_array = array();
		foreach ($data as $cache)
		{
			if (is_array($cache[1]))
			{
				$data = serialize($cache[1]);
				$is_array = 1;
			}
			else
			{
				$data = $cache[1];
				$is_array = 0;
			}
			$sql_array[] = array(
				'title' => $cache[0],
				'data' => $data,
				'is_array' => $is_array,
				'time' => (isset($cache[2]) && $cache[2] > 0) ? (TIMENOW + $cache[2]) : 0
			);
		}
		return $this->multi_replace(CACHE_TABLE, $sql_array);
	}

	/**
	 * 建立 insert/update/select 语句使用的子句
	 *
	 * @param string $query 可选值: INSERT, INSERT_SELECT, MULTI_INSERT, UPDATE, SELECT
	 * @param array $array 构造子句的数组
	 * @return string
	 */
	function sql_clause($query, $array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$query = strtoupper($query);
		$fields = $values = '';
		if ($query == 'INSERT' || $query == 'INSERT_SELECT')
		{
			foreach ($array as $key => $var)
			{
				$fields .= ', `' . $key . '`';

				if (is_array($var) && is_string($var[0]))
				{
					// INSERT SELECT
					$values .= ', ' . $var[0];
				}
				else
				{
					$values .= ', ' . $this->sql_validate($var);
				}
			}
			$fields = substr($fields, 2);
			$values = substr($values, 2);
			$query = ($query == 'INSERT') ? ' (' . $fields . ') VALUES (' . $values . ')' : ' (' . $fields . ') SELECT ' . $values . ' ';
		}
		else if ($query == 'MULTI_INSERT')
		{
			foreach ($array as $sql_array)
			{
				if (is_array($sql_array))
				{
					$value = '';
					foreach ($sql_array as $key => $var)
					{
						$value .= ', ' . $this->sql_validate($var);
					}
					$value = substr($value, 2);
					$values .= ', (' . $value . ')';
				}
				else
				{
					return $this->sql_clause('INSERT', $array);
				}
			}
			$values = substr($values, 2);
			$query = ' (`' . implode('`, `', array_keys($array[0])) . '`) VALUES ' . $values;
		}
		else if ($query == 'UPDATE' || $query == 'SELECT')
		{
			$values = '';
			$sep = ($query == 'UPDATE') ? ',' : ' AND';
			foreach ($array as $key => $var)
			{
				$values .= "$sep `$key` = " . $this->sql_validate($var, $key);
			}
			$query = substr($values, strlen($sep));
		}

		return $query;
	}

	/**
	 * 建立 IN, NOT IN, =, <> SQL字符串.
	 *
	 * @param boolean $negate 是否是否定
	 */
	function sql_in($field, $value = '', $negate = false)
	{
		if (!is_array($value))
		{
			return $field . ($negate ? ' <> ' : ' = ') . $this->sql_validate($value);
		}
		else if (count($value) == 1)
		{
			$var = @reset($value);
			return $field . ($negate ? ' <> ' : ' = ') . $this->sql_validate($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map(array(&$this, 'sql_validate'), $value)) . ')';
		}
	}

	/**
	 * 建立 SELECT 语句
	 *
	 * @param string $query 可能的值 SELECT, SELECT_DISTINCT
	 */
	function sql_select($query, $array)
	{
		$query = strtoupper($query);
		if (in_array($query, array('SELECT', 'SELECT DISTINCT')))
		{
			$sql = $query . ' ';
		}
		else
		{
			return '';
		}

		if (empty($array['SELECT']))
		{
			$array['SELECT'] = '*';
		}
		elseif (is_array($array['SELECT']))
		{
			$select = '';
			foreach ($array['SELECT'] as $filed)
			{
				$select .= ', ';
				if (is_array($filed))
				{
					$select .= key($filed) . ' AS ' . current($filed);
				}
				else
				{
					$select .= $filed;
				}
			}
			$array['SELECT'] = substr($select, 2);
		}

		$sql .=  $array['SELECT'] . ' FROM ';
		if (is_array($array['FROM']))
		{
			$table_str = '';
			foreach ($array['FROM'] as $table_name => $alias)
			{
				if (is_array($alias))
				{
					foreach ($alias as $multi_alias)
					{
						$table_str .= ', `' . $table_name . '` ' . $multi_alias;
					}
				}
				else
				{
					$table_str .= ', `' . $table_name . '` ' . $alias;
				}
			}

			$sql .= '(' . substr($table_str, 2) . ')';
		}
		else
		{
			$sql .= '`' . $array['FROM'] . '`';
		}


		if (!empty($array['LEFT_JOIN']))
		{
			foreach ($array['LEFT_JOIN'] as $join)
			{
				$sql .= ' LEFT JOIN ' . key($join['FROM']) . ' ' . current($join['FROM']) . ' ON (' . $join['ON'] . ')';
			}
		}

		if (!empty($array['WHERE']))
		{
			if (is_array($array['WHERE']))
			{
				$array['WHERE'] = $this->sql_clause('SELECT', $array['WHERE']);
			}
			$sql .= ' WHERE ' . $array['WHERE'];
		}

		if (!empty($array['GROUP_BY']))
		{
			if (is_array($array['GROUP_BY']))
			{
				$array['GROUP_BY'] = implode(', ', $array['GROUP_BY']);
			}
			$sql .= ' GROUP BY ' . $array['GROUP_BY'];
		}

		if (!empty($array['ORDER_BY']))
		{
			if (is_array($array['ORDER_BY']))
			{
				$array['ORDER_BY'] = implode(', ', $array['ORDER_BY']);
			}
			$sql .= ' ORDER BY ' . $array['ORDER_BY'];
		}

		if (!empty($array['LIMIT']))
		{
			$offset = $total = 0;
			if (is_array($array['LIMIT']))
			{
				if (isset($array['LIMIT'][1]))
				{
					$offset = $array['LIMIT'][0];
					$total = $array['LIMIT'][1];
				}
				else
				{
					$total = $array['LIMIT'][0];
				}
			}
			else
			{
				$total = $array['LIMIT'];
			}

			$total = intval($total);
			$offset = intval($offset);
			$total = ($total < 0) ? 0 : $total;
			$offset = ($offset < 0) ? 0 : $offset;

			if ($total !== 0)
			{
				$sql .= ' LIMIT ' . (($offset !== 0) ? $offset . ', ' . $total : $total);
			}
		}

		return $sql;
	}

	/**
	 * 建立 INSERT 语句
	 */
	function sql_insert($table, $array, $type = 'INSERT', $prefix = 'INSERT')
	{
		if (!is_array($array) || empty($array))
		{
			return false;
		}
		return "$prefix INTO $table " . $this->sql_clause($type, $array);
	}

	/**
	 * 建立 REPLACE 语句
	 */
	function sql_replace($table, $array, $type = 'INSERT')
	{
		return $this->sql_insert($table, $array, $type, 'REPLACE');
	}

	/**
	 * 建立 UPDATE 语句
	 */
	function sql_update($table, $array, $where = '')
	{
		if (!is_array($array) || empty($array))
		{
			return false;
		}

		if (is_array($where))
		{
			$where = $this->sql_clause('SELECT', $where);
		}
		return "UPDATE $table SET " . $this->sql_clause('UPDATE', $array) . ($where ? " WHERE $where" : '');
	}

	/**
	 * 建立 UPDATE CASE 语句
	 *
	 * @param string $table 表名
	 * @param string $id_filed ID 字段名
	 * @param array $sql_array
	 */
	function sql_update_case($table, $id_filed, $sql_array)
	{
		if (!is_array($sql_array) || empty($sql_array))
		{
			return false;
		}

		$ids = array();
		$sql = "UPDATE `$table` SET";
		foreach ($sql_array as $set_field => $array)
		{
			$sql .= " `$set_field` =";
			if (isset($array[1]) && in_array($array[1], array('+', '-', '*', '/')))
			{
				$sql .= " `$set_field` {$array[1]}";
				$array = $array[0];
			}

			if (is_array($array))
			{
				$set = '';
				$sql .= ' (CASE';
				foreach ($array as $k => $v)
				{
					if ($k)
					{
						$k = $this->sql_validate($k);
						$v = $this->sql_validate($v, $set_field);
						$sql .= " WHEN $id_filed = $k THEN $v";
						if (!@in_array($k, $ids))
						{
							$ids[] = $k;
						}
					}
				}
				$sql .= ' ELSE 0 END)';
			}
			else
			{
				$sql .= $this->sql_validate($array[0]);
			}
			$sql .= ', ';
		}
		$sql = substr($sql, 0, -2);

		if ($ids)
		{
			$ids = implode(',', $ids);
			return $sql .= " WHERE `$id_filed` IN ($ids)";
		}
		else
		{
			return false;
		}
	}

	/**
	 * 建立 DELETE 语句
	 */
	function sql_delete($table, $where = '')
	{
		if (is_array($where))
		{
			$where = $this->sql_clause('SELECT', $where);
		}
		return 'DELETE FROM ' . $table . ($where ? ' WHERE ' . $where : '');
	}

	/**
	 * 验证 MySQL 语句中的用到的变量
	 * $set_field 不为空时特别的 $var 结构
	 *	 - array('field_name', true) => field = field_name
	 *   - array('^[0-9]+$', '^[/*+-]$') => filed = set_field [/*+-] [0-9]+
	 *
	 * @param mixed $var 将变量转换为 SQL 语句中可以直接使用的字符串
	 */
	function sql_validate($var, $set_field = '')
	{
		if ($set_field && isset($var[0]) && isset($var[1]))
		{
			if ($var[1] === true)
			{
				return $var[0];
			}
			else if (in_array($var[1], array('+', '-', '*', '/')) && is_numeric($var[0]))
			{
				return "`$set_field` {$var[1]} {$var[0]}";
			}
		}

		return $this->validate($var);
	}
}
?>