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

class Cache_Redis extends Cache_Base
{
	/**
    * Redis object instance
    */
	private $_r;

	public function __construct($options)
	{
		extract(
			$this->_default($options,
				array
				(
					'host'  => '127.0.0.1',
					'port'  => 11211,
					'timeout' => false,
				)
			)
		);

		$this->_r  = new Redis();
		$this->_r->connect($host, $port);
	}


	/**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     */
	public function get($name)
	{
		return $this->_r->get($name);
	}

	/**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     * @param int $ttl (optional) time to life of the variable
     */
	public function set($name, $value, $ttl = CACHE_NEVER_EXPIRE)
	{
		if ($ttl < 0)
		{
			$ttl = 0;
		}

		$return = $this->_r->set($name, $value);
		if ($ttl !== CACHE_NEVER_EXPIRE)
		{
			$this->_r->expireAt($name, $ttl);
		}
		return $return;
	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     */
	public function rm($name)
	{
		return $this->_r->delete($name);
	}

	public function clear($prefix = '')
	{
		return $this->_r->flushDB();
	}
}