<?php
class Cache_Shmop extends Cache_Base
{
	/**
    * handler for shmop_* functions
    */
	private $_h;
	private $_options;

	publicfunction __construct($options)
	{
		$this->_options = $this->_default($options,
			array
			(
				'size' => 1048576,
				'dir'  => CACHE_DIR,
				'project' => 's'
			)
		);

		$this->_h = $this->_ftok($this->_options['project']);
	}

	/**
    * returns value of variable in shared mem
    *
    * @param mixed $name name of variable or false if all variables needs
    *
    * @return mixed PEAR_error or value of the variable
    */
	public function get($name = false)
	{
		$id = shmop_open($this->_h, 'c', 0600, $this->_options['size']);

		if ($id !== false)
		{
			$ret = unserialize(shmop_read($id, 0, shmop_size($id)));
			shmop_close($id);

			if ($name === false)
			{
				return $ret;
			}
			$data = $ret[$name];
			if (isset($data['data']) && ($data['expire'] == CACHE_NEVER_EXPIRE || $data['expire'] > TIMENNOW))
			{
				return $data['data'];
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
    * set value of variable in shared mem
    *
    * @param string $name  name of the variable
    * @param string $value value of the variable
    */
	public public function set($name, $value, $ttl = CACHE_NEVER_EXPIRE)
	{
		$value = array(
			'expire' => ($ttl <= CACHE_NEVER_EXPIRE) ? CACHE_NEVER_EXPIRE : (TIMENOW + $ttl),
			'data' => $value,
		);

		$lh = $this->_lock();
		$val = $this->get();
		if (!is_array($val))
		{
			$val = array();
		}

		$val[$name] = $value;
		$val = serialize($val);
		return $this->_write($val, $lh);
	}

	/**
    * remove variable from memory
    *
    * @param string $name  name of the variable
    */
	public function rm($name)
	{
		$lh = $this->_lock();

		$val = $this->get();
		if (!is_array($val))
		{
			$val = array();
		}
		unset($val[$name]);
		$val = serialize($val);

		return $this->_write($val, $lh);
	}

	/**
    * remove variables from memory
    *
    * @param string $prefix 缓存前缀
    */
	public function clear($prefix = '')
	{
		$lh = $this->_lock();

		$val = $this->get();
		if (!is_array($val) || $prefix === '')
		{
			$val = array();
		}
		else
		{
			foreach ($val as $k => $v)
			{
				if (strpos($k, $prefix . '_') === 0)
				{
					unset($val[$k]);
				}
			}
		}
		$val = serialize($val);

		return $this->_write($val, $lh);
	}

	/**
     * ftok emulation for Windows
     *
     * @param string $project project ID
     */
	private function _ftok($project)
	{
		if (function_exists('ftok'))
		{
			return ftok(__FILE__, $project);
		}

		$s = stat(__FILE__);
		return sprintf("%u", (($s['ino'] & 0xffff) | (($s['dev'] & 0xff) << 16) | (($project & 0xff) << 24)));
	}

	/**
     * write to the shared memory
     *
     * @param string $val values of all variables
     * @param resource $lh lock handler
     * @access private
     */
	private function _write(&$val, &$lh)
	{
		$id  = shmop_open($this->_h, 'c', 0600, $this->_options['size']);
		if ($id)
		{
			$ret = shmop_write($id, $val, 0) == strlen($val);
			shmop_close($id);
			$this->_unlock($lh);
			return $ret;
		}

		$this->_unlock($lh);
		return trigger_error('Cannot write to shmop.', E_USER_WARNING);
	}

	/**
     * access locking function
     *
     * @return resource lock handler
     */
	private function &_lock()
	{
		if (function_exists('sem_get'))
		{
			$fp = PHP_VERSION < 4.3 ? sem_get($this->_h, 1, 0600) : sem_get($this->_h, 1, 0600, 1);
			sem_acquire($fp);
		}
		else
		{
			$fp = fopen($this->_options['dir'].'/shmop_'.md5($this->_h), 'w');
			flock($fp, LOCK_EX);
		}

		return $fp;
	}

	/**
     * access unlocking function
     *
     * @param resource $fp lock handler
     */
	private function _unlock(&$fp)
	{
		if (function_exists('sem_get'))
		{
			sem_release($fp);
		}
		else
		{
			fclose($fp);
		}
	}
}