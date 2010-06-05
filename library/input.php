<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
/**
 * 获得 input 值 (POST GET)
 */
class input
{
	static private $type = array(
		'integer' => 'intval',
		'double' => 'floatval',
		'string' => array('self', 'clean_value'),
	);
	static private $clear = true;
	static private $empty = array();
	static private $preg_find = array('/&(?!#[0-9]+|shy;)/si', '/<script/i');
	static private $preg_replace = array('&amp;', '&#60;script');
	static private $find = array('&#032;', '<!--', '-->',  '../', '&#032;', '>', '<', '"', '!', "'", '$', "\r\n", "\r", "\n");
	static private $replace = array(' ', '&#60;&#33;--', '--&#62;', '&#46;&#46;/', ' ', '&gt;', '&lt;', '&quot;', '&#33;', '&#39;', '&#036;', '<br />', '<br />', '<br />');

	static public function init()
	{
		self::$empty = array(utf8_chr(173), utf8_chr(127), chr(0xCA), '%00', '/\\\0/', '/\\x00/');
	}

	/**
	 * 获得整数
	 * @param string $name
	 */
	static public function int($name)
	{
		return self::get($name, 0);
	}

	/**
	 * 获得字符串
	 * @param string $name
	 * @param boolean $clear
	 */
	static public function str($name, $clear = true)
	{
		return self::get($name, '', $clear);
	}

	/**
	 * 获得数组
	 * @param string $name
	 */
	static public function arr($name)
	{
		return self::get($name, array(0));
	}

	/**
	 * 获得某个 input 的值
	 *
	 * @param string $name input key
	 * @param mixed $default 默认值， input 值会被设置成 default 变量的类型
	 * @param boolean $clear 是否清理数据
	 * @return mixed
	 */
	static public function get($name, $default = 0, $clear = true)
	{
		// 防止获取到 cookie 中的内容
		if (isset($_COOKIE[$name]))
		{
			if (isset($_POST[$name]))
			{
				$_REQUEST[$name] = $_POST[$name];
			}
			else if (isset($_GET[$name]))
			{
				$_REQUEST[$name] = $_GET[$name];
			}
			else if (is_array($default))
			{
				return array();
			}
			else
			{
				return $default;
			}
		}

		if ($clear !== self::$clear)
		{
			self::$clear = $clear;
		}

		if (isset($_REQUEST[$name]))
		{
			return self::get_value($_REQUEST[$name], $default);
		}
		else if (is_array($default))
		{
			return array();
		}
		else
		{
			return $default;
		}
	}

	/**
	 * 格式化变量
	 *
	 * @param string $value
	 * @param mixed $default 同 get
	 * @return mixed
	 */
	static private function get_value($value, $default)
	{
		$type = gettype($default);

		if ($type === 'array')
		{
			if (empty($default))
			{
				settype($value, $type);
			}
			else
			{
				$type = gettype(current($default));
				if (!is_array($value))
				{
					$value =  strpos($value, ',') ? explode(',', $value) : array($value);
				}

				// array_map 大概比 foreach 快 2 倍
				if (isset(self::$type[$type]))
				{
					$value = array_map(self::$type[$type], $value);
				}
				else
				{
					foreach ($value as $k => $v)
					{
						settype($value[$k], $type);
					}
				}
			}
		}
		else
		{
			settype($value, $type);
			if ($type === 'string')
			{
				$value = self::clean_value($value);
			}
		}

		return $value;
	}

	/**
	 * 过滤输入的数据
	 *
	 * @param mixed $val
	 */
	static public function clean_value($val)
	{
		if (empty($val))
		{
			return '';
		}

		$val = trim($val);

		if (!is_numeric($val) && self::$clear)
		{
			$val = str_replace(self::$empty, '', $val);
			$val = preg_replace(self::$preg_find, self::$preg_replace, $val);
			$val = str_replace(self::$find, self::$replace, $val);
			$val = preg_replace('/\\\(&amp;#|\?#)/', '&#092;', $val);
		}

		return $val;
	}

	/**
	 * 用在 textarea 中编辑以前提交保存的数据
	 *
	 * @param string $val
	 */
	static public function for_textarea($val)
	{
		if (empty($val))
		{
			return '';
		}

		$val = str_replace(self::$replace, self::$find, $val);
		$val = str_replace(array('&#092;', '&amp;'), array('\\', '&'), $val);
		return trim($val);
	}

	/**
	 * 设置 input
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	static public function set($key, $value)
	{
		$_REQUEST[$key] = $_POST[$key] = $value;
	}

	static public function is_set($name)
	{
		return (isset($_POST[$name]) || isset($_GET[$name]));
	}
}

input::init();