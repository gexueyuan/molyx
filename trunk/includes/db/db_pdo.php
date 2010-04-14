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
if (!class_exists('db_base'))
{
	return;
}

class db extends db_base
{
	var $pdo;
	var $stmt;

	function connect($server, $user, $password, $database)
	{
		$this->pdo = new PDO('mysql:host=' . $server . ';dbname=' . $database, $user, $password);

		if ($this->pdo)
		{
			$this->version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
			if ($this->version > '4.1')
			{
				$this->pdo->exec("SET NAMES 'utf8'");
			}
			if (defined('EMPTY_SQL_MODE') && $this->version > '5.0')
			{
				$this->set_sql_mode('');
			}
		}
		else
		{
			$this->halt('Can not connect MySQL Server or DataBase');
		}

		$this->type = 'pdo';
		$this->database = $database;
		$this->debug_init();
		return true;
	}

	/**
	 * 设置 MySQL 不同的 sql_mode 可以解决一些 MySQL 的兼容问题
	 *
	 * @param string $mode
	 */
	function set_sql_mode($mode = '')
	{
		$return_die = $this->return_die;
		$this->return_die = 1;
		$this->pdo->exec("SET @@sql_mode = " . $this->pdo->quote($mode));
		$this->return_die = $return_die;
	}

	function query($sql, $cache_ttl = false, $cache_prefix = '', $query_type = 'query')
	{
		if ($this->explain !== null)
		{
			$this->explain->start($sql);
		}

		if ($cache_ttl !== false)
		{
			if (is_null($this->cache))
			{
				$this->init_cache();
			}
			$this->query_id = $this->cache->load($sql, $cache_prefix);
		}
		else
		{
			$this->query_id = false;
		}

		if (false === $this->query_id)
		{
			if (false === ($this->stmt = $this->pdo->$query_type($sql)))
			{
				$this->halt("Query Errors:\n$sql");
			}

			if ($query_type == 'query')
			{
				$this->stmt->setFetchMode(PDO::FETCH_ASSOC);
			}

			$this->query_id += 1;

			if ($cache_ttl !== false)
			{
				$rowset = $this->stmt->fetchAll();
				$this->free_result($this->query_id);
				$this->query_id = $this->cache->save($sql, $rowset, $cache_ttl, $cache_prefix);
				unset($row, $rowset);
			}
			$this->query_count++;
		}

		if ($this->explain !== null)
		{
			$this->explain->stop($sql);
		}
		return $this->query_id;
	}

	function query_unbuffered($query_id = '')
	{
		return $this->query($query_id, false, '', 'exec');
	}

	function fetch_array($query_id = '')
	{
		if ($query_id == '')
		{
			$query_id = $this->query_id;
		}

		if (isset($this->cache->row[$query_id]))
		{
			return $this->cache->fetch_array($query_id);
		}
		else if ($this->stmt)
		{
			return $this->stmt->fetch();
		}
		return false;
	}

	function affected_rows()
	{
		return is_int($this->stmt) ? $this->stmt : 0;
	}

	function num_rows($query_id = '')
	{
		if ($query_id == '')
		{
			$query_id = $this->query_id;
		}

		if (isset($this->cache->row[$query_id]))
		{
			return $this->cache->count[$query_id];
		}
		else if ($this->stmt)
		{
			return @$this->stmt->rowCount();
		}
		return false;
	}

	function geterrno()
	{
		if ($this->stmt)
		{
			$this->errno = $this->stmt->errorCode();
		}
		else
		{
			$this->errno = $this->pdo->errorCode();
		}

		return $this->errno;
	}

	function insert_id()
	{
		return $this->pdo->lastInsertId();
	}

	function escape_string($str)
	{
		return substr($this->pdo->quote($str), 1, -1);
	}

	function free_result($query_id = '')
	{
		if ($query_id == '')
		{
			$query_id = $this->query_id;
		}

		if (isset($this->cache->row[$query_id]))
		{
			$this->cache->count = $this->cache->row = $this->cache->pointer = array();
			return true;
		}

		if ($this->stmt)
		{
			$this->stmt = null;
			return true;
		}
		return false;
	}

	function _close_db()
	{
		return $this->pdo = null;
	}

	function get_table_names()
	{
		$stmt = $this->pdo->query('SHOW TABLES FROM ' . $this->database);
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$tables = array();
		while ($row = $stmt->fetch())
		{
			$tables[] = $row[0];
		}
		return $tables;
	}

	function get_result_fields($query_id = '')
	{
		if ($query_id == '')
		{
			$query_id = $this->query_id;
		}

		$fields = array();
		if (isset($this->cache->row[$query_id]))
		{
			foreach ((array) $this->cache->row[$query_id] as $field)
			{
				$fields[] = $field;
			}
		}
		else if ($this->stmt)
		{
			while ($field = $this->stmt->fetchColumn())
			{
				$fields[] = $field;
			}
		}

		return $fields;
	}

	function get_error()
	{
		$info = ($this->stmt) ? $this->stmt->errorInfo() : $this->pdo->errorInfo();
		return $info[2];
	}

	/**
	 * 重定义 for PDO
	 */
	function validate($var)
	{
		if (is_numeric($var))
		{
			return $var;
		}
		else if (is_bool($var))
		{
			return intval($var);
		}
		return $this->pdo->quote($var);
	}
}
?>