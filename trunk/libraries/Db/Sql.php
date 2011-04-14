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
	private $sign = '';
	public function __construct($db)
	{
		$this->db = $db;

		switch ($db->getType())
		{
			case 'mysql':
			case 'mysqli':
			case 'pdo_mysql':
				$this->sign = '`';
			break;

			case 'postgres':
				$this->sign = '"';
			break;
		}
	}

	public function __destruct()
	{
		unset($this->db);
	}

	private function field($name)
	{
		if (is_array($name))
		{
			$name = implode($this->sign . ', ' . $this->sign, $name);
		}
		else if (strpos($name, '.') !== false)
		{
			$name = str_replace('.', $this->sign . '.' . $this->sign, $name);
		}

		return $this->sign . $name . $this->sign;
	}

	private function insertClause($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$fields = $values = '';
		foreach ($array as $key => $var)
		{
			$fields .= ', ' . $this->field($key);
			$values .= ', ' . $this->db->validate($var);
		}
		$fields = substr($fields, 2);
		$values = substr($values, 2);
		$query = ' (' . $fields . ') VALUES (' . $values . ')';

		return $query;
	}

	private function insertSelectClause($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$fields = $values = '';
		foreach ($array as $key => $var)
		{
			$fields .= ', ' . $this->field($key);
			$values .= ', ' . $this->field($var);
		}
		$fields = substr($fields, 2);
		$values = substr($values, 2);
		$query = ' (' . $fields . ') SELECT ' . $values . ' ';

		return $query;
	}

	private function insertMultiClause($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$fields = $values = '';
		foreach ($array as $sql_array)
		{
			if (is_array($sql_array))
			{
				$value = '';
				foreach ($sql_array as $key => $var)
				{
					$value .= ', ' . $this->db->validate($var);
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
		$query = ' (' . $this->field(array_keys($array[0])) . ') VALUES ' . $values;

		return $query;
	}

	private function updateClause($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$fields = $values = '';
		foreach ($array as $key => $var)
		{
			$values .= ', ' . $this->field($key) . ' = ' . $this->db->validate($var, $key);
		}
		$query = substr($values, 2);

		return $query;
	}

	private function whereClause($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$fields = $values = '';
		foreach ($array as $key => $var)
		{
			$values .= ' AND ' . $this->field($key) . ' = ' . $this->db->validate($var, $key);
		}
		$query = substr($values, 5);

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
			return $field . ($negate ? ' <> ' : ' = ') . $this->db->validate($value);
		}
		else if (count($value) == 1)
		{
			$var = @reset($value);
			return $field . ($negate ? ' <> ' : ' = ') . $this->db->validate($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map(array($this->db, 'validate'), $value)) . ')';
		}
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
						$table_str .= ', ' . $this->field($table_name) . ' ' . $multi_alias;
					}
				}
				else
				{
					$table_str .= ', ' . $this->field($table_name) . ' ' . $alias;
				}
			}

			$table_str = substr($table_str, 2);
			$sql .= (strpos($this->type, 'mysql') !== false) ? "($table_str)" : $table_str;
		}
		else
		{
			$sql .= $this->field($array['FROM']);
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
				$array['WHERE'] = $this->whereClause($array['WHERE']);
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

		$sql = "$prefix INTO $table ";
		switch ($type)
		{
			case 'INSERT':
				$sql .= $this->insertClause($array);
			break;

			case 'INSERT_SELECT':
				$sql .= $this->insertSelectClause($array);
			break;

			case 'MULTI_INSERT':
				$sql .= $this->insertMultiClause($array);
			break;
		}

		return $sql;
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

		$sql = "UPDATE $table SET " . $this->updateClause($array);
		if (!empty($where))
		{
			if (is_array($where))
			{
				$where = $this->whereClause($where);
			}

			$sql .= " WHERE $where";
		}

		return $sql;
	}

	/**
	 * 建立 UPDATE CASE 语句
	 *
	 * @param string $table 表名
	 * @param string $id_filed ID 字段名
	 * @param array $sql_array
	 */
	public function updateCase($table, $id_field, $sql_array)
	{
		if (!is_array($sql_array) || empty($sql_array))
		{
			return false;
		}

		$ids = '';
		$sql = 'UPDATE ' . $this->field($table) . ' SET';
		foreach ($sql_array as $set_field => $array)
		{
			$sql .= ' ' . $this->field($set_field) . ' = ';
			if (isset($array[1]) && in_array($array[1], array('+', '-', '*', '/'), true))
			{
				$sql .= $this->field($set_field) . " {$array[1]}";
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
						$k = $this->db->validate($k);
						$v = $this->db->validate($v, $set_field);
						$sql .= " WHEN $id_field = $k THEN $v";
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
				$sql .= $this->db->validate($array);
			}
			$sql .= ', ';
		}
		$sql = substr($sql, 0, -2);

		if ($ids)
		{
			return $sql .= ' WHERE ' . $this->field($id_field) . " IN (0$ids)";
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
		$sql = 'DELETE FROM ' . $table;

		if (!empty($where))
		{
			if (is_array($where))
			{
				$where = $this->whereClause($where);
			}

			$sql .= " WHERE $where";
		}

		return $sql;
	}


}