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
	function connect($server, $user, $password, $database)
	{
		$this->connect_id = @mysql_connect($server, $user, $password);

		if ($this->connect_id && @mysql_select_db($database, $this->connect_id))
		{
			$this->version = mysql_get_server_info($this->connect_id);
			if ($this->version > '4.1')
			{
				mysql_query("SET NAMES 'utf8'", $this->connect_id);
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

		$this->type = 'mysql';
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
		@mysql_query("SET @@sql_mode = '" . $this->escape_string($mode) . "'", $this->connect_id);
		$this->return_die = $return_die;
	}

	function query($sql, $cache_ttl = false, $cache_prefix = '', $query_type = 'mysql_query')
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
			if (false === ($this->query_id = $query_type($sql, $this->connect_id)))
			{
				$this->halt("Query Errors:\n$sql");
			}

			if ($cache_ttl !== false)
			{
				$this->open_queries[(int) $this->query_id] = $this->query_id;
				$rowset = array();
				while ($row = $this->fetch_array($this->query_id))
				{
					$rowset[] = $row;
				}
				$this->free_result($this->query_id);
				$this->query_id = $this->cache->save($sql, $rowset, $cache_ttl, $cache_prefix);
				unset($row, $rowset);
			}
			else if (strpos($sql, 'SELECT') === 0)
			{
				$this->open_queries[(int) $this->query_id] = $this->query_id;
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
		return $this->query($query_id, false, '', 'mysql_unbuffered_query');
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
		else
		{
			return @mysql_fetch_assoc($query_id);
		}
	}

	function affected_rows()
	{
		return $this->connect_id ? mysql_affected_rows($this->connect_id) : 0;
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
		else
		{
			return @mysql_num_rows($query_id);
		}
	}

	function geterrno()
	{
		$this->errno = $this->connect_id ? mysql_errno($this->connect_id) : '';
		return $this->errno;
	}

	function insert_id()
	{
		return $this->connect_id ? mysql_insert_id($this->connect_id) : 0;
	}

	function escape_string($str)
	{
		if ($this->connect_id)
		{
			return mysql_real_escape_string($str, $this->connect_id);
		}
		else
		{
			return mysql_real_escape_string($str);
		}
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

		if (isset($this->open_queries[(int) $query_id]))
		{
			unset($this->open_queries[(int) $query_id]);
			return @mysql_free_result($query_id);
		}
		return false;
	}

	function _close_db()
	{
		$return = $this->connect_id ? mysql_close($this->connect_id) : false;
		$this->connect_id = null;
		return $return;
	}

	function get_table_names()
	{
		$result = mysql_query('SHOW TABLES FROM ' . $this->database, $this->connect_id);
		$tables = array();
		while ($row = mysql_fetch_array($result, MYSQL_NUM))
		{
			$tables[] = $row[0];
		}
		mysql_free_result($result);
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
		else
		{
			while ($field = mysql_fetch_field($query_id))
			{
				$fields[] = $field;
			}
		}
		return $fields;
	}

	function get_error()
	{
		return $this->connect_id ? mysql_error($this->connect_id) : '';
	}
}
?>