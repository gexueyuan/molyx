<?php
class Cache_Apc extends Cache_Base
{
	/**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     */
	public function get($name)
	{
		return apc_fetch($name);
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

		return apc_store($name, $value, $ttl);
	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     */
	public function rm($name)
	{
		return apc_delete($name);
	}

	public function clear($prefix = '')
	{
		apc_clear_cache('user');
	}
}