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

	/**
	 * 读取缓存
	 *
	 * @param string $cache_name 缓存名
	 * @param string $extra 参数 / 缓存使用函数名
	 * @param boolean $recache 是否重建缓存, false 时将不重新生成缓存
	 * @return array
	 */
	public static function &get($cache_name, $extra = '', $recache = true)
	{
		if ($extra !== '' && !is_array($extra))
		{
			$extra = array('func' => $cache_name, 'param' => $extra);
			$cache_name .= '-' . $extra['param'];
		}

		if (!isset(self::$value[$cache_name]))
		{
			$value = self::$cache->get($cache_name);
			if ($value !== NULL)
			{
				self::$value[$cache_name] = $value;
			}
			else if ($recache)
			{
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
				self::rebuild($extra);
			}
		}

		return self::$value[$cache_name];
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
	 * 重建缓存
	 *
	 * @param string $name 缓存名字
	 * @param mixed $param 参数
	 */
	public static function rebuild($name, $param = NULL)
	{
		if (empty($name))
		{
			return false;
		}

		if (is_array($name))
		{
			$param = $name['param'];
			$name = $name['func'];
		}

		require_once (ROOT_DIR . 'recaches/' . $name . '.php');
		$value = call_user_func('recache_' . $name, $param);

		self::set($name, $value);
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