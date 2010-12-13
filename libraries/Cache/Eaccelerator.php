<?php
class Cache_Eaccelerator extends Cache_Base
{
	/**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     */
	public function get($name)
	{
		return eaccelerator_get($name);
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

		return eaccelerator_put ($name, $value, $ttl);
	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     */
	public function rm($name)
	{
		return eaccelerator_rm($name);
	}

	public function clear($prefix = '')
	{
		 return eaccelerator_clear();
	}
}