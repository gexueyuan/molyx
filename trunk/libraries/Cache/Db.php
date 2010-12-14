<?php
class Cache_Db extends Cache_Base
{
	private $_db;
	private $_options;

	public function __construct($options = array())
	{
		$this->_options = $this->_default($options,
			array
			(
				'db' => 'base',
				'config' => NULL,
				'table' => CACHE_TABLE,
				'key' => 'cache_key',
				'value' => 'cache_value',
				'expire' => 'cache_expire',
			)
		);

		$this->_db = db::get($this->_options['db'], $this->_options['config']);

		parent::__construct();
	}

	/**
     * returns true if plugin was
     * successfully connected to backend
     */
	public function isConnected()
	{
		return ($this->_db instanceof Db_Base);
	}

	/**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     */
	public function get($name)
	{
		$sql = "SELECT {$this->_options['expire']}, {$this->_options['value']}
			FROM {$this->_options['table']}
			WHERE {$this->_options['key']} = " . $this->_db->validate($name);

		$data = $this->_db->queryFirst($sql);
		if (!empty($data) &&
			$data[$this->_options['expire']] != CACHE_NEVER_EXPIRE &&
			$data[$this->_options['expire']] > TIMENNOW
		)
		{
			$data = @unserialize($data[$this->_options['value']]);
			if ($data !== false)
			{
				return $data;
			}
		}
		return false;
	}

	/**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     */
	public function set($name, $value, $ttl = CACHE_NEVER_EXPIRE)
	{
		$sql_array = array(
			$this->_options['key'] => $name,
			$this->_options['value'] => serialize($value),
			$this->_options['expire'] => ($ttl <= CACHE_NEVER_EXPIRE) ? CACHE_NEVER_EXPIRE : (TIMENOW + $ttl),
		);

		return $this->_db->replace($this->_options['table'], $sql_array);

	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     */
	public function rm($name)
	{
		return $this->_db->delete($this->_options['table'], $this->_options['key'] . ' = ' . $this->_db->validate($name));
	}

	public function clear($prefix = '')
	{
		$where = '';
		if ($prefix)
		{
			$where = $this->_options['key'] . ' ' . $this->like($prefix . '*');
		}

		return $this->_db->delete($this->_options['table'], $where);
	}
}