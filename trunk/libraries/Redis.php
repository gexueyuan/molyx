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

class Redis
{
	private $_sock;

	public function connect($host = 'localhost', $port = 6379)
	{
		$errno = $errstr = '';
		if (!($this->_sock = fsockopen($host, $port, $errno, $errstr)))
		{
			$msg = "Cannot open socket to {$this->host}:{$this->port}";
			if ($errno || $errstr)
				$msg .= "," . ($errno ? " error $errno" : "") . ($errstr ? " $errstr" : "");
			trigger_error("$msg.", E_USER_ERROR);
		}
	}

	public function ping()
	{
		$this->write("PING\r\n");
		return $this->get_response();
	}

	public function set($name, $value, $preserve = false)
	{
		$this->write(($preserve ? 'SETNX' : 'SET') . " $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function get($name)
	{
		$this->write("GET $name\r\n");
		return $this->get_response();
	}

	public function getMultiple($keys)
	{
		$this->write("MGET " . implode(" ", $keys) . "\r\n");
		return $this->get_response();
	}

	public function incr($name, $amount = 1)
	{
		if ($amount == 1)
			$this->write("INCR $name\r\n");
		else
			$this->write("INCRBY $name $amount\r\n");
		return $this->get_response();
	}

	public function decr($name, $amount = 1)
	{
		if ($amount == 1)
			$this->write("DECR $name\r\n");
		else
			$this->write("DECRBY $name $amount\r\n");
		return $this->get_response();
	}

	public function exists($name)
	{
		$this->write("EXISTS $name\r\n");
		return $this->get_response();
	}

	public function delete($name)
	{
		$this->write("DEL $name\r\n");
		return $this->get_response();
	}

	public function getKeys($pattern)
	{
		$this->write("KEYS $pattern\r\n");
		$return = $this->get_response();
		if (!is_array($return))
		{
			$return = explode(' ', $return);
		}

		return $return;
	}

	public function randomKey()
	{
		$this->write("RANDOMKEY\r\n");
		return $this->get_response();
	}

	public function renameKey($src, $dst)
	{
		$this->write("RENAME $src $dst\r\n");
		return $this->get_response();
	}

	public function renameNx($src, $dst)
	{
		$this->write("RENAMENX $src $dst\r\n");
		return $this->get_response();
	}

	public function setTimeout($name, $time)
	{
		$this->write("EXPIRE $name $time\r\n");
		return $this->get_response();
	}

	public function expireAt($name, $time)
	{
		$this->write("EXPIREAT $name $time\r\n");
		return $this->get_response();
	}

	public function lPush($name, $value)
	{
		$this->write("LPUSH $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function rPush($name, $value)
	{
		$this->write("RPUSH $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function listTrim($name, $start, $end)
	{
		$this->write("LTRIM $name $start $end\r\n");
		return $this->get_response();
	}

	public function lGet($name, $index)
	{
		$this->write("LINDEX $name $index\r\n");
		return $this->get_response();
	}

	public function lPop($name, $value)
	{
		$this->write("LPOP $name\r\n");
		return $this->get_response();
	}

	public function rPop($name, $value)
	{
		$this->write("RPOP $name\r\n");
		return $this->get_response();
	}

	public function lSize($name)
	{
		$this->write("LLEN $name\r\n");
		return $this->get_response();
	}

	public function lGetRange($name, $start, $end)
	{
		$this->write("LRANGE $name $start $end\r\n");
		return $this->get_response();
	}

	public function sort($name, $query = false)
	{
		$this->write($query == false ? "SORT $name\r\n" : "SORT $name $query\r\n");
		return $this->get_response();
	}

	public function lSet($name, $value, $index)
	{
		$this->write("LSET $name $index $value\r\n");
		return $this->get_response();
	}

	public function sAdd($name, $value)
	{
		$this->write("SADD $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function sRemove($name, $value)
	{
		$this->write("SREM $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function sContains($name, $value)
	{
		$this->write("SISMEMBER $name " . strlen($value) . "\r\n$value\r\n");
		return $this->get_response();
	}

	public function sInter($sets)
	{
		$this->write('SINTER ' . implode(' ', $sets) . "\r\n");
		return $this->get_response();
	}

	public function sMembers($name)
	{
		$this->write("SMEMBERS $name\r\n");
		return $this->get_response();
	}

	public function sSize($name)
	{
		$this->write("SCARD $name\r\n");
		return $this->get_response();
	}

	public function select($name)
	{
		$this->write("SELECT $name\r\n");
		return $this->get_response();
	}

	public function move($name, $db)
	{
		$this->write("MOVE $name $db\r\n");
		return $this->get_response();
	}

	public function save()
	{
		$this->write("SAVE\r\n");
		return $this->get_response();
	}

	public function bgsave()
	{
		$this->write("BGSAVE\r\n");
		return $this->get_response();
	}

	public function lastSave()
	{
		$this->write("LASTSAVE\r\n");
		return $this->get_response();
	}

	public function flushDB()
	{
		$this->write("FLUSHDB\r\n");
		return $this->get_response();
	}

	public function flushAll()
	{
		$this->write("FLUSHALL\r\n");
		return $this->get_response();
	}

	public function info()
	{
		$this->write("INFO\r\n");
		$info = array ();
		$data = $this->get_response();
		foreach (explode("\r\n", $data) as $l)
		{
			if (!$l)
			{
				continue;
			}

			list($k, $v) = explode(':', $l, 2);
			$v = trim($v);

			if (strpos($k, 'db') === 0)
			{
				$_v = array();
				foreach (explode(',', $v) as $v1)
				{
					list($k1, $v1) = explode('=', $v1, 2);
					$_v[$k1] =  $v1;
				}

				$v = $_v;
			}

			$info[$k] = $v;
		}
		return $info;
	}

	public function close()
	{
		if ($this->_sock)
		{
			$this->write("QUIT\r\n");
			@fclose($this->_sock);
		}
		$this->_sock = null;
		return;
	}

	private function write($s)
	{
		return fwrite($this->_sock, $s);
	}

	private function read()
	{
		return fgets($this->_sock);
	}

	private function get_response()
	{
		$data = trim($this->read());

		$c = $data[0];
		$data = substr($data, 1);
		switch ($c)
		{
			case '-' :
				trigger_error($data, E_USER_ERROR);
			break;
			case '+' :
				return $data;
			case ':' :
				$i = strpos($data, '.') !== false ? (int) $data : (float) $data;
				if ((string) $i != $data)
					trigger_error("Cannot convert data '$c$data' to integer", E_USER_ERROR);
				return $i;
			case '$' :
				return $this->get_bulk_reply($c . $data);
			case '*' :
				$num = (int) $data;
				if ((string) $num != $data)
					trigger_error("Cannot convert multi-response header '$data' to integer", E_USER_ERROR);
				$result = array ();
				for($i = 0; $i < $num; $i++)
					$result[] = $this->get_response();
				return $result;
			default :
				trigger_error("Invalid reply type byte: '$c'");
		}
	}

	private function get_bulk_reply($data = null)
	{
		if ($data === null)
		{
			$data = trim($this->read());
		}

		if ($data == '$-1')
		{
			return null;
		}

		$c = $data[0];
		$data = substr($data, 1);
		$bulklen = (int) $data;
		if ((string) $bulklen != $data)
		{
			trigger_error("Cannot convert bulk read header '$c$data' to integer", E_USER_ERROR);
		}

		if ($c != '$')
		{
			trigger_error("Unkown response prefix for '$c$data'", E_USER_ERROR);
		}

		$buffer = '';
		while ( $bulklen )
		{
			$data = fread($this->_sock, $bulklen);
			$bulklen -= strlen($data);
			$buffer .= $data;
		}
		$crlf = fread($this->_sock, 2);
		return $buffer;
	}
}