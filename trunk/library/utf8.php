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

class utf8
{
	public static $mbstring = false;

	public function init()
	{
		self::$mbstring = function_exists('mb_internal_encoding');
		if (self::$mbstring)
		{
			mb_internal_encoding('UTF-8');
		}
	}

	public static function htmlspecialchars($string)
	{
		return str_replace(
			array('<', '>', '"', "'"),
			array('&lt;', '&gt;', '&quot;', '&#039;'),
			preg_replace('/&(?!#[0-9]+;)/si', '&amp;', $string)
		);
	}

	public static function unhtmlspecialchars($string)
	{
		return str_replace(
			array('&lt;', '&gt;', '&quot;', '&#039;', '&amp;'),
			array('<', '>', '"', "'", '&'),
			$string
		);
	}

	/**
	 * 字符串中错误的字节在这个函数中将直接忽略, 不会被计算
	 * @param string $str
	 */
	public static function strlen($str)
	{
		if (self::$mbstring)
		{
			return mb_strlen($str);
		}
		else
		{
			// utf8_decode() 会把非 ISO-8859-1 字符转换为 '?', 对于计数已经足够了
			return strlen(utf8_decode($str));
		}
	}

	/**
	 * 仅支持已存在的字母表转换： 拉丁文, 希腊语, 西里尔字母, 亚美尼亚语和格鲁吉亚语, 没有中文的字母表
	 */
	public function strtolower($str)
	{
		if (self::$mbstring)
		{
			return mb_strtolower($str);
		}
		else
		{
			static $utf8_case_table;
			if (!is_array($utf8_case_table))
			{
				$utf8_case_table = read_serialize_file(format_path(dirname(__FILE__) . '/encoding/') . 'upper2lower.data');
			}

			return strtr(strtolower($string), $utf8_case_table);
		}
	}

	/**
	 * 根据给定的位置和长度截取字符串
	 */
	public static function substr($str, $offset, $length = NULL)
	{
		if (self::$mbstring)
		{
			if (is_null($length))
			{
				return mb_substr($str, $offset);
			}
			else
			{
				return mb_substr($str, $offset, $length);
			}
		}
		else
		{
			/**
			 * 说明:
			 *   - 不同于 substr, offset 或 length 不是整数时不会报错而是转换为整数
			 *   - substr 出错会返回 false, 这里类似 mb_substr 出错时返回一个空字符串
			 *   - Perl 兼容的正则最多仅支持重复 65536 次, 本函数将会在需要的时候按照 65535 对字符进行分组
			 *   - strlen 只在需要的时候进行, offset 为正数或者未定义长度的时候不需要执行
			 */
			$str = (string) $str;
			$offset = (int) $offset;
			if (!is_null($length))
			{
				$length = (int) $length;
			}

			if ($length === 0 || ($offset < 0 && $length < 0 && $length < $offset))
			{
				return '';
			}

			if ($offset < 0)
			{
				$strlen = self::strlen($str);
				$offset = $strlen + $offset;
				if ($offset < 0)
				{
					$offset = 0;
				}
			}

			$op = '';
			$lp = '';
			if ($offset > 0)
			{
				$ox = (int) ($offset / 65535);
				$oy = $offset % 65535;
				if ($ox)
				{
					$op = '(?:.{65535}){' . $ox . '}';
				}
				$op = '^(?:' . $op . '.{' . $oy . '})';
			}
			else
			{
				$op = '^';
			}

			if (is_null($length))
			{
				$lp = '(.*)$';
			}
			else
			{
				if (!isset($strlen))
				{
					$strlen = self::strlen($str);
				}

				if ($offset > $strlen)
				{
					return '';
				}

				if ($length > 0)
				{
					$length = min($strlen-$offset, $length);

					$lx = (int) ($length / 65535);
					$ly = $length % 65535;
					if ($lx)
					{
						$lp = '(?:.{65535}){' . $lx . '}';
					}
					$lp = '(' . $lp . '.{' . $ly . '})';
				}
				else if ($length < 0)
				{
					if ($length < ($offset - $strlen))
					{
						return '';
					}

					$lx = (int) ((-$length) / 65535);
					$ly = (-$length) % 65535;

					if ($lx)
					{
						$lp = '(?:.{65535}){' . $lx . '}';
					}
					$lp = '(.*)(?:' . $lp . '.{' . $ly . '})$';
				}
			}

			if (!preg_match('#'.$op.$lp.'#us',$str, $match))
			{
				return '';
			}

			return $match[1];
		}
	}

	/**
	 * 检查字符串是否兼容 UTF-8,
	 * 并不是严格的 UTF-8 编码检查, 会忽略 5/6 字节的字符
	 */
	public static function check($str, $strict = false)
	{
	    if (empty($str))
	    {
	        return true;
	    }

	    if (!$strict)
	    {
	    	return (preg_match('/^.{1}/us', $str) == 1);
	    }
	    else
	    {
	    	;
	    }
	}

	/**
	 * 将 UNICODE 码点转换成 UTF-8 字符串
	 */
	public static function chr($cp)
	{
		if ($cp > 0xFFFF)
		{
			return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
		}
		else if ($cp > 0x7FF)
		{
			return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
		}
		else if ($cp > 0x7F)
		{
			return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
		}
		else
		{
			return chr($cp);
		}
	}

	/**
	 * 将 UTF-8 转换成 UNICODE 码点
	 */
	public static function ord($chr)
	{
		switch (strlen($chr))
		{
			case 1:
				return ord($chr);
			break;

			case 2:
				return ((ord($chr[0]) & 0x1F) << 6) | (ord($chr[1]) & 0x3F);
			break;

			case 3:
				return ((ord($chr[0]) & 0x0F) << 12) | ((ord($chr[1]) & 0x3F) << 6) | (ord($chr[2]) & 0x3F);
			break;

			case 4:
				return ((ord($chr[0]) & 0x07) << 18) | ((ord($chr[1]) & 0x3F) << 12) | ((ord($chr[2]) & 0x3F) << 6) | (ord($chr[3]) & 0x3F);
			break;

			default:
				return $chr;
		}
	}
}

utf8::init();