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

class Db_Sql
{
	private $db;
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function __destruct()
	{
		unset($this->db);
	}

	/**
	 * 建立 insert/update/select 语句使用的子句
	 *
	 * @param string $query 可选值: INSERT, INSERT_SELECT, MULTI_INSERT, UPDATE, SELECT
	 * @param array $array 构造子句的数组
	 * @return string
	 */
	private function clause($query, $array)
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
				$values .= ($query == 'INSERT') ? ', ' . $this->validate($var) : ', `' . $var . '`';
			}
			$fields = substr($fields, 2);
			$values = substr($values, 2);
			$query = ($query == 'INSERT') ?
				' (' . $fields . ') VALUES (' . $values . ')' :
				' (' . $fields . ') SELECT ' . $values . ' ';
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
						$value .= ', ' . $this->validate($var);
					}
					$value = substr($value, 2);
					$values .= ', (' . $value . ')';
				}
				else
				{
					return $this->clause('INSERT', $array);
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
				if (strpos($key, '.') !== false)
				{
					$key = str_replace('.', '`.`', $key);
				}
				$values .= "$sep `$key` = " . $this->validate($var, $key);
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
	public function in($field, $value = '', $negate = false)
	{
		if (!is_array($value))
		{
			return $field . ($negate ? ' <> ' : ' = ') . $this->validate($value);
		}
		else if (count($value) == 1)
		{
			$var = @reset($value);
			return $field . ($negate ? ' <> ' : ' = ') . $this->validate($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map(array($this, 'validate'), $value)) . ')';
		}
	}

	/**
	 * 建立 LIKE 语句.
	 *
	 * @param boolean $expression 语句
	 */
	public function like($expression, $search = '*', $any = true)
	{
		$replace = $any ? $this->any_char : $this->one_char;
		$expression = str_replace($search, $replace, $expression);
		$expression = str_replace(array('_', '%'), array('\\_', '\\%'), $expression);
		$expression = str_replace(array(chr(0) . '\\_', chr(0) . '\\%'), array('_', '%'), $expression);

		return $this->db->likeExpression(' LIKE \'' . $this->escapeString($expression) . '\'');
	}

	/**
	 * 建立 SELECT 语句
	 *
	 * @param string $query 可能的值 SELECT, SELECT_DISTINCT
	 */
	public function select($query, $array)
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

			$table_str = substr($table_str, 2);
			$sql .= (strpos($this->type, 'mysql') !== false) ? "($table_str)" : $table_str;
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
				$array['WHERE'] = $this->clause('SELECT', $array['WHERE']);
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

		if (!empty($array['HAVING']))
		{
			$sql .= ' HAVING ' . $array['HAVING'];
		}

		if (!empty($array['ORDER_BY']))
		{
			if (is_array($array['ORDER_BY']))
			{
				$array['ORDER_BY'] = implode(', ', $array['ORDER_BY']);
			}
			$sql .= ' ORDER BY ' . $array['ORDER_BY'];
		}

		return $sql;
	}

	/**
	 * 建立 INSERT 语句
	 */
	public function insert($table, $array, $type = 'INSERT', $prefix = 'INSERT')
	{
		if (!is_array($array) || empty($array))
		{
			return false;
		}
		return "$prefix INTO $table " . $this->clause($type, $array);
	}


	/**
	 * 建立 UPDATE 语句
	 */
	public function update($table, $array, $where = '')
	{
		if (!is_array($array) || empty($array))
		{
			return false;
		}

		if (is_array($where))
		{
			$where = $this->clause('SELECT', $where);
		}
		return "UPDATE $table SET " . $this->clause('UPDATE', $array) . ($where ? " WHERE $where" : '');
	}

	/**
	 * 建立 UPDATE CASE 语句
	 *
	 * @param string $table 表名
	 * @param string $id_filed ID 字段名
	 * @param array $sql_array
	 */
	public function updateCase($table, $id_filed, $sql_array)
	{
		if (!is_array($sql_array) || empty($sql_array))
		{
			return false;
		}

		$ids = '';
		$sql = "UPDATE `$table` SET";
		foreach ($sql_array as $set_field => $array)
		{
			$sql .= " `$set_field` = ";
			if (isset($array[1]) && in_array($array[1], array('+', '-', '*', '/'), true))
			{
				$sql .= "`$set_field` {$array[1]}";
				$array = $array[0];
			}

			if (is_array($array))
			{
				$set = '';
				$sql .= 'CASE';
				foreach ($array as $k => $v)
				{
					if ($k)
					{
						$k = $this->validate($k);
						$v = $this->validate($v, $set_field);
						$sql .= " WHEN $id_filed = $k THEN $v";
						if (strpos($ids . ',', ",$k,") === false)
						{
							$ids .= ",$k";
						}
					}
				}
				$sql .= ' ELSE 0 END';
			}
			else
			{
				$sql .= $this->validate($array);
			}
			$sql .= ', ';
		}
		$sql = substr($sql, 0, -2);

		if ($ids)
		{
			return $sql .= " WHERE `$id_filed` IN (0$ids)";
		}
		else
		{
			return false;
		}
	}

	/**
	 * 建立 DELETE 语句
	 */
	public function delete($table, $where = '')
	{
		if (is_array($where))
		{
			$where = $this->clause('SELECT', $where);
		}
		return 'DELETE FROM ' . $table . ($where ? ' WHERE ' . $where : '');
	}

	/**
	 * 验证 SQL 语句中的用到的变量
	 * $set_field 不为空时特别的 $var 结构
	 *	 - array('field_name', true) => field = field_name
	 *   - array('^[0-9]+$', '^[/*+-]$') => filed = set_field [/*+-] [0-9]+
	 *
	 * @param mixed $var 将变量转换为 SQL 语句中可以直接使用的字符串
	 */
	public function validate($var, $set_field = '')
	{
		if ($set_field && is_array($var) && isset($var[0]) && isset($var[1]))
		{
			if ($var[1] === true)
			{
				return $var[0];
			}
			else if (in_array($var[1], array('+', '-', '*', '/'), true) && is_numeric($var[0]))
			{
				return "`$set_field` {$var[1]} {$var[0]}";
			}
		}

		if (is_numeric($var))
		{
			if (is_string($var))
			{
				return "'$var'";
			}
			return $var;
		}
		else if (is_bool($var))
		{
			return (int) $var;
		}
		else if (is_array($var))
		{
			$var = serialize($var);
		}

		return "'" . $this->db->escape($var) . "'";
	}
}