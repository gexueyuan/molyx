<?php
class Cache_Memcached extends Cache_Base
{
	/**
    * true if plugin was connected to backend
    */
	private $_connected;

	/**
    * Memcache object instance
    */
	private $_mc;

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

		$this->_mc  = new Memcached('cache');
		$this->_connected = $this->_mc->addServer($host, $port);
	}

	/**
     * returns true if plugin was
     * successfully connected to backend
     */
	public function isConnected()
	{
		return $this->_connected;
	}

	/**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     */
	public function get($name)
	{
		return $this->_mc->get($name);
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

		return $this->_mc->set($name, $value, 0, $ttl);
	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     */
	public function rm($name)
	{
		return $this->_mc->delete($name);
	}

	public function clear($prefix = '')
	{
		return $this->_mc->flush();
	}
}