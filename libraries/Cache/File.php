<?php
class Cache_File extends Cache_Base
{
	private $_options;
	private $_connected;
	private static $_path = array();

	/**
     * Constructor. Init all variables.
     *
     * @param array $options
     */
	function __construct($options)
	{
		$this->_options = $this->_default($options,
			array
			(
				'dir'  => CACHE_DIR,
				'include' => false,
			)
		);

		$this->_connected = checkdir($this->_options['dir']);
	}

	/**
     * returns true if plugin was
     * successfully connected to backend
     */
	function is_connected()
	{
		return $this->_connected;
	}

	/**
     * returns value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     * @return mixed true on success or PEAR_error on fail
     */
	function get($name)
	{
		$name = $this->getPath($name);

		if ($this->_options['include'])
		{
			@include($name);
			if (!isset($data))
			{
				return NULL;
			}
		}
		else
		{
			$data = read_serialize_file($name);

			if (empty($data) || ($data['time'] != CACHE_NEVER_EXPIRE && $data['time'] < TIMENNOW))
			{
				return NULL;
			}

			$data = $data['data'];
		}

		return $data;
	}

	/**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     * @return mixed true on success or PEAR_error on fail
     */
	function set($name, $value, $ttl = CACHE_NEVER_EXPIRE)
	{
		$name = $this->getPath($name);

		$expire = ($ttl <= CACHE_NEVER_EXPIRE) ? CACHE_NEVER_EXPIRE : (TIMENOW + $ttl);
		if (!$this->_options['include'])
		{
			$content = '<?php exit; ?' . '>' . serialize(array(
				'expire' => $expire,
				'data' => $value,
			));
		}
		else
		{
			$content = '<?php ';
			if ($expire != CACHE_NEVER_EXPIRE)
			{
				$content .= 'if (TIMENOW > ' . $expire . ') {return;} '
			}
			$content .= '$data = ' . var_export($value, true) . '; ?>';
		}

		return file_write($name, $content);
	}

	/**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
	function rm($name)
	{
		$name = $this->getPath($name);
		@unlink($name);
	}

	function clear($dir)
	{
		$dir = $this->_options['dir'] . str_replace('_', '/', $dir);
		$dh = opendir($dir);
		while (($entry = readdir($dh)) !== false)
		{
			if ($entry == '.' || $entry == '..')
			{
				continue;
			}
			$name = $dir . $entry;
			if (is_dir($name))
			{
				$this->clear($name);
			}
			else if (is_file($name))
			{
				@unlink($name);
			}
		}
		@closedir($dir);
	}

	/**
	 * 根据名字获得路径
	 */
	private function getPath($name)
	{
		if (!isset(self::$_path[$name]))
		{
			if (strpos($name, '-') !== false)
			{
				$file = implode('/', array_map(function ($c) {
					return is_numeric($c) ? number_hash($c) : $c;
				}, explode('-', $name)));

				if (strpos($file, '.') === false)
				{
					$file .= '/' . $name;
				}
			}

			$file = $this->_options['dir'] . $file . '.php' ;
			if (!checkdir($file, true, $this->_options['dir']))
			{
				trigger_error('Path Error: ' . $file, E_USER_WARNING);
			}

			self::$_path[$name] = $file;
		}

		return self::$_path[$name];
	}
}