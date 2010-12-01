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
abstract class Db_Pdo_Base extends Db_Base
{
	protected $pdo;
	protected $stmt;
	protected $_count = 0;

	public function __construct($config)
	{
		parent::__construct($config);
	}

	public function __destruct()
	{
		unset($this->pdo, $this->stmt);
		parent::__destruct();
	}

	protected function _query($sql)
	{
		if (false === ($this->stmt = $this->pdo->query($sql)))
		{
			$this->halt("Query Errors:\n$sql");
		}

		$this->stmt->setFetchMode(PDO::FETCH_ASSOC);

		++$this->_count;
	}

	protected function _queryUnbuffered($sql)
	{
		$this->pdo->exec($sql);
	}

	protected function _fetch($query_id)
	{
		return $this->stmt->fetch();
	}

	public function affectedRows()
	{
		return is_int($this->stmt) ? $this->stmt : 0;
	}

	protected function _numRows($query_id)
	{
		return @$this->stmt->rowCount();
	}

	public function insertId()
	{
		//$id = $this->query_first('SELECT LAST_INSERT_ID() as id');
		return $this->pdo->lastInsertId();
	}

	protected function _likeExpression($expression)
	{
		return $expression;
	}

	protected function _freeResult($query_id)
	{
		$this->stmt = null;
		return true;
	}

	public function _fetchField($query_id)
	{
		return $this->stmt->fetchColumn();
	}

	public function escape($str)
	{
		$return = $this->pdo->quote($str);
		if (strpos($return, "'") === 0)
		{
			$return = substr($return, 1, -1);
		}

		return $return;
	}

	public function getError()
	{
		$info = ($this->stmt) ? $this->stmt->errorInfo() : $this->pdo->errorInfo();
		return $info[2];
	}

	protected function _close()
	{
		return $this->pdo = null;
	}

	protected function _transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return $this->pdo->beginTransaction();
			break;

			case 'commit':
				return $this->pdo->commit();
			break;

			case 'rollback':
				return $this->pdo->rollBack();
			break;
		}

		return true;
	}
}