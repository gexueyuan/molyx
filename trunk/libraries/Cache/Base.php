<?php
define(CACHE_NEVER_EXPIRE, 0);

class Cache_Base
{
	private $type;

	public function __construct()
	{
		$this->type = strtolower(substr(get_class($this), 6));
	}

	/**
	 * 如果引擎已经成功连接到服务器返回 true
	 *
	 * @return bool 连接上为 true
	 */
	public function isConnected()
	{
		return true;
	}


	/**
	 * 将没有设置的参数中设为默认值
	 *
	 * @param array $options 参数数组
	 * @param array $default 参数默认值数组
	 * @return array 填充过的数组
	 */
	protected function _default($options, $default)
	{
		$options  = array_merge($default, (array) $options);
		return $options;
	}

	protected function clear($prefix = '')
	{
		return false;
	}
}