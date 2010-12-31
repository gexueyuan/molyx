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
define('CACHE_FORCE_REBUILD', -1);

class cache
{
	private static $cache;
	private static $value;
	private static $cache_name;

	public static function init($type = 'File', $options = array())
	{
		$class = 'Cache_' . $type;
		self::$cache = new $class($options);
	}

	public static function &all()
	{
		return self::$value;
	}

	private static function _name($cache_name, $extra)
	{
		if ($extra !== '' && !is_array($extra))
		{
			$extra = array('func' => $cache_name, 'param' => $extra);
			$cache_name .= '-' . $extra['param'];
		}

		return array($cache_name, $extra);
	}

	/**
	 * 读取缓存
	 *
	 * @param string $cache_name 缓存名
	 * @param string $extra 参数 / 缓存使用函数名
	 * @param boolean $recache 是否重建缓存, false 时将不重新生成缓存
	 * @return array
	 */
	public static function &get($cache_name, $extra = '', $recache = CACHE_NEVER_EXPIRE)
	{
		list($cache_name, $extra) = self::_name($cache_name, $extra);

		if (!isset(self::$value[$cache_name]))
		{
			$value = self::$cache->get($cache_name);
			if ($value !== NULL)
			{
				self::$value[$cache_name] = $value;
			}
			else if ($recache !== false)
			{
				self::update($cache_name, $extra, $recache);
			}
		}

		return self::$value[$cache_name];
	}

	public static function update($cache_name, $extra = '', $expire = CACHE_NEVER_EXPIRE)
	{
		list($cache_name, $extra) = self::_name($cache_name, $extra);

		$expire = (int) $expire;

		if (empty($extra))
		{
			$pos = strpos($cache_name, '-');
			if ($pos !== false)
			{
				$extra = array(
					'func' => substr($cache_name, 0, $pos),
					'param' => substr($cache_name, $pos + 1)
				);
			}
			else
			{
				$extra = $cache_name;
			}
		}

		self::set($cache_name, self::value($extra), $expire);

		return true;
	}

	/**
	 * 更新缓存文件
	 *
	 * @param array $name 缓存名字
	 * @param array $value 缓存内容, 如果没有 value 则取当前缓存变量对应的值
	 */
	public static function set($name, $value = '', $ttl = CACHE_NEVER_EXPIRE)
	{
		self::$value[$name] = $value;
		self::$cache->set($name, $value, $ttl);
	}

	/**
	 * 读取数据库中的值
	 *
	 * @param string $name 缓存名字
	 */
	public static function value($name)
	{
		if (empty($name))
		{
			return false;
		}

		$param = NULL;
		if (is_array($name))
		{
			$param = $name['param'];
			$name = $name['func'];
		}

		require_once (ROOT_DIR . 'recaches/' . $name . '.php');
		return call_user_func('recache_' . $name, $param);
	}

	/**
	 * 删除缓存
	 *
	 * @param string $cache_name 缓存名字
	 */
	public function rm($cache_name, $param = '', $only_cache = false)
	{
		if (empty($cache_name))
		{
			return false;
		}
		else if ($cache_name === '*')
		{
			if ($only_cache)
			{
				self::$value = array();
				return true;
			}

			$cache_name = array_keys(self::$value);
		}

		if (is_array($cache_name))
		{
			if ($only_cache)
			{
				foreach ($cache_name as $v)
				{
					unset(self::$value[$v]);
				}
				return true;
			}

			return array_map(array('self', 'rm'), $cache_name);
		}
		else
		{
			if ($param !== '' && !is_array($param))
			{
				$cache_name .= '-' . $param;
			}

			unset(self::$value[$cache_name]);
			if ($only_cache)
			{
				return true;
			}

			return $this->rm($cache_name);
		}
	}
}