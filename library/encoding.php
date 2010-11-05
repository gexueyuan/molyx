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
 * 编码转换类
 * 支持 UTF-8, UTF-16(BE and LE), GBK(GB2312), BIG5, HTML-ENTITIES(NCR)之间的相互转换
 * 支持 UTF-8 下的 简(simplified) 繁(traditional) 文字互换
 * 支持将上述编码的中文转换成拼音
 */
class encoding
{
	private static $table_path = '';
	private static $from_encoding = '';
	private static $to_encoding = '';
	private static $type = 'table';
	private static $pinyin = true;

	private static $support = array(
		'utf-8',
		'gbk',
		'big5',
		'gb',
		'gb2312',
		'big-5',
		'utf-16be',
		'utf-16le',
		'html-entities',
		'ncr',
		'utf8',
		'pinyin',
		'traditional',
		'simplified'
	);

	/**
	 * 构造函数
	 *
	 * 说明:
	 *   - 优先级 mbstring > iconv > 查表
	 *
	 * @param string $from 初始化源编码, 可以为空
	 * @param string $to 初始化目的编码, 可以为空
	 */
	public static function init($from = '', $to = '')
	{
		if (!empty($from) || !empty($to))
		{
			self::set($from, $to);
		}
		self::$table_path = format_path(dirname(__FILE__) . '/encoding/');

		switch (true)
		{
			case function_exists('mb_convert_encoding'):
				self::$type = 'mbstring';
				break;

			case function_exists('iconv'):
				self::$type = 'iconv';
				break;

			default:
				self::$type = 'table';
		}
	}

	/**
	 * 设置转换编码
	 */
	public static function set($from = '', $to = '')
	{
		self::$from_encoding = self::check_encoding($from, 'from');
		self::$to_encoding = self::check_encoding($to, 'to');
		return (self::$from_encoding && self::$to_encoding);
	}

	/**
	 * 进行转换
	 *
	 * 说明:
	 *   - iconv 下会忽略在目标字符集中不存在的字符
	 *   - 查表方法使用 UTF-16BE 作为中间编码进行转换
	 *   - 非 mbstring 方式由 HTML-ENTITIES(NCR) 转换到非 UTF-8 编码使用 UTF-8 作中间编码
	 *   - 转换到 pinyin 使用 GBK 作中间编码
	 *
	 *
	 * @param string $str 要转换的字符串
	 * @param string $from 不填写将直接使用属性 from_encoding
	 * @param string $to 不填写将直接使用属性 to_encoding
	 * @return string 转换后的字符串
	 */
	public static function convert($str, $from = '', $to = '')
	{
		if(self::set($from, $to) && !empty($str) && self::$from_encoding != self::$to_encoding)
		{
			if (in_array(self::$from_encoding, array('traditional', 'simplified')) || in_array(self::$to_encoding, array('traditional', 'simplified')))
			{
				if (utf8::check($str))
				{
					if (self::$from_encoding == 'simplified' && self::$to_encoding == 'traditional')
					{
						return self::simp2trad($str);
					}
					else if (self::$from_encoding == 'traditional' && self::$to_encoding == 'simplified')
					{
						return self::trad2simp($str);
					}
				}

				return $str;
			}

			if (self::$to_encoding == 'pinyin')
			{
				if (self::$from_encoding != 'gbk')
				{
					$str = self::convert($str, self::$from_encoding, 'gbk');
					self::$to_encoding = 'pinyin';
				}
				return self::gbk2pin($str);
			}

			switch (self::type)
			{
				case 'mbstring':
					return mb_convert_encoding($str, self::$to_encoding, self::$from_encoding);

				case 'iconv':
					if (self::$to_encoding == 'html-entities')
					{
						$str = iconv(self::$from_encoding, 'utf-16be', $str);
						return self::unicode2htm($str);
					}
					else if (self::$from_encoding != 'html-entities')
					{
						return iconv(self::$from_encoding, self::$to_encoding . '//IGNORE', $str);
					}

				default:
					if (self::$from_encoding == 'utf-8' && self::$to_encoding == 'html-entities')
					{
						return self::utf2ncr($str);
					}
					else if (self::$from_encoding == 'html-entities')
					{
						$str = self::ncr2utf($str);
						if (self::$to_encoding != 'utf-8')
						{
							$str = self::convert($str, 'utf-8', self::$to_encoding);
							self::$from_encoding = 'html-entities';
						}
						return $str;
					}
					else
					{
						$space = array("\n", "\r", "\t");
						$tag = array('<|n|>', '<|r|>', '<|t|>');
						$str = str_replace($space, $tag, $str);

						$method_name = substr(self::$from_encoding, 0, 3);
						$to_unicode = $method_name . '2unicode';
						if (self::$from_encoding == 'utf-16le')
						{
							$str = self::change_byte($str);
						}
						else if (self::$from_encoding != 'utf-16be' && method_exists(__CLASS__, $to_unicode))
						{
							$str = self::$to_unicode($str);
						}

						$from_unicode = 'unicode2' . $method_name;
						if (self::$to_encoding == 'utf-16le')
						{
							$str = self::change_byte($str);
						}
						else if (self::$to_encoding != 'utf-16be' && method_exists(__CLASS__, $from_unicode))
						{
							$str = self::$from_unicode($str);
						}

						return str_replace($tag, $space, $str);
					}
			}
		}
		else
		{
			return $str;
		}
	}

	/**
	 * GBK to UTF-16BE
	 */
	private static function gbk2unicode(&$str)
	{
		$table = self::read_table('gbkunicode');

		$return = $p = $q = '';
		$str_len = strlen($str);
		for ($i = 0; $i < $str_len; $i++)
		{
			if (128 < ($p = ord($str[$i])))
			{
				$q = ord($str[++$i]);
				if ($p > 254)
				{
					$return .= '003f';
				}
				else if ($q < 64 || $q > 254)
				{
					$return .= '003f';
				}
				else
				{
					$q = ($q - 64) * 4;
					$return .= substr($table[$p - 128], $q, 4);
				}
			}
			else
			{
				if ($p == 128)
				{
					$return .= '20ac';
				}
				else
				{
					$return .= '00';
					$return .= dechex($p);
				}
			}
		}
		return self::hex2bin($return);
	}

	/**
	 * BIG-5 to UTF-16BE
	 */
	private static function big2unicode(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = self::read_table('bigunicode');
		}

		$return = $p = $q = '';
		$str_len = strlen($str);
		for ($i = 0; $i < $str_len; $i++)
		{
			if (128 < ($p = ord($str[$i])))
			{
				$q = ord($str[++$i]);
				if ($p > 249)
				{
					$return .= '003f';
				}
				else if ($q < 64 || $q > 254)
				{
					$return .= '003f';
				}
				else
				{
					$q = ($q - 64) * 4;
					$return .= substr($table[$p - 160], $q, 4);
				}
			}
			else
			{
				$return .= '00';
				$return .= dechex($p);
			}
		}
		return self::hex2bin($return);
	}

	/**
	 * UTF-16BE to GBK
	 */
	private static function unicode2gbk(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = self::read_table('unicodegbk');
		}

		$return = $p = $q = $temp = '';
		$str_len = strlen($str);
		for ($i = 0; $i < $str_len; $i++)
		{
			$p = ord($str[$i++]);
			if ($i == $str_len)
			{
				$temp = dechex($p);
				if (strlen($temp) < 2)
				{
					$temp = '0' . $temp;
				}
				$return .= $temp;
				continue;
			}

			$q = ord($str[$i]);
			if ($p == 0 && $q < 127)
			{
				$temp = dechex($q);
				if (strlen($temp) < 2)
				{
					$temp = '0' . $temp;
				}
				$return .= $temp;
				continue;
			}
			$p++;
			$begin = hexdec(substr($table[$p], 0, 2));
			if (strlen($table[$p]) < 3 || $q < $begin || $q > hexdec(substr($table[$p], 2, 2)))
			{
				$return .= '3f';
				continue;
			}
			$q *= 4;
			$q -= $begin * 4;
			$temp = substr($table[$p], $q + 4, 2);
			if ($temp == '00')
			{
				$return .= substr($table[$p], $q + 6, 2);
			}
			else
			{
				$return .= $temp . substr($table[$p], $q + 6, 2);
			}
		}
		return self::hex2bin($return);
	}

	/**
	 * UTF-16BE to BIG-5
	 */
	private static function unicode2big(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = self::read_table('unicodebig');
		}

		$return = $p = $q = $temp = '';
		$str_len = strlen($str);
		for ($i = 0;$i < $str_len; $i++)
		{
			$p = ord($str[$i++]);
			if ($i == $str_len)
			{
				$temp = dechex($p);
				if (strlen($temp) < 2)
				{
					$temp = '0' . $temp;
				}
				$return .= $temp;
				continue;
			}
			$q = ord($str[$i]);
			if ($p == 0 && $q < 127)
			{
				$temp = dechex($q);
				if (strlen($temp) < 2)
				{
					$temp = '0' . $temp;
				}
				$return .= $temp;
				continue;
			}
			$p++;
			$begin = hexdec(substr($table[$p], 0, 2));
			if (strlen($table[$p]) < 3 || $q < $begin || $q > hexdec(substr($table[$p], 2, 2)))
			{
				$return .= '3f';
				continue;
			}
			$q *= 4;
			$q -= $begin * 4;
			$temp = substr($table[$p], $q + 4, 2);
			if ($temp == '00')
			{
				$return .= substr($table[$p], $q + 6, 2);
			}
			else
			{
				$return .= $temp . substr($table[$p], $q + 6, 2);
			}
		}
		return self::hex2bin($return);
	}

	/**
	 * UTF-16BE to UTF-8
	 */
	private static function unicode2utf(&$str)
	{
		$str_len = strlen($str);
		$return = '';
		for ($i = 0; $i < $str_len; $i++)
		{
			$char = $str[$i++];
			if ($i == $str_len)
			{
				$return .= bin2hex($char);
				continue;
			}
			$char .= substr($str, $i, 1);
			$hex = bin2hex($char);
			$dec = hexdec($hex);
			$bin = decbin($dec);
			$temp = '';
			if($dec > 0x7f)
			{
				$binlen = strlen($bin);
				for ($j = 0, $n = 16 - $binlen; $j < $n; $j++)
				{
					$bin = '0' . $bin;
				}
				$temp .= '1110' . substr($bin,0,4);
				$temp .= '10' . substr($bin,4,6);
				$temp .= '10' . substr($bin,10,6);
				$temp = dechex(bindec($temp));
			}
			else
			{
				$temp = substr($hex,2,2);
			}
			$return .= $temp;
		}
		return self::hex2bin($return);
	}

	/**
	 * UTF-8 to UTF-16BE
	 *
	 * @param string $str
	 * @return string
	 */
	private static function utf2unicode(&$str)
	{
		$str_len = strlen($str);
		$x = $y = $z = $return = '';
		for ($i = 0; $i < $str_len; $i++)
		{
			if (128 < ($x = ord($str[$i])))
			{
				if (($i + 1) == $str_len)
				{
					$return .= dechex($x);
					continue;
				}
				$y = ord($str[++$i]);
				if (($i + 1) == $str_len)
				{
					$return .= dechex($x) . dechex($y);
					continue;
				}
				$x = decbin($x);
				$y = decbin($y);
				$z = decbin(ord($str[++$i]));
				$temp = dechex(bindec(substr($x, 4, 4) . substr($y, 2, 4) . substr($y, 6, 2) . substr($z, 2, 6)));
				$str_len = strlen($temp);
				for ($j = 0, $n = 4 - $str_len; $j < $n; $j++)
				{
					$temp = '0' . $temp;
				}
				$return .= $temp;
			}
			else
			{
				$return .= '00';
				$return .= dechex($x);
			}
		}
		return self::hex2bin($return);
	}

	/**
	 * UTF-16LE 和 BE 相互转换, 字符两个字节交换位置
	 */
	private static function change_byte(&$str)
	{
		$str_len = strlen($str);
		$return = '';
		for ($i = 0; $i < $str_len; $i++)
		{
			if (($i + 1) != $str_len)
			{
				$return .= $str[$i + 1] . $str[$i++];
			}
			else
			{
				$return .= $str[$i];
			}
		}
		return $return;
	}

	/**
	 * UTF-16BE to NCR
	 */
	private static function unicode2htm(&$str)
	{
		$return = '';
		for ($i = 0, $n = strlen($str); $i < $n; $i += 2)
		{
			$c = ord($str[$i]) * 256 + ord($str[$i + 1]);
			if ($c < 128)
			{
				$return .= chr($c);
			}
			else if ($c != 65279) // Unicode BOM
			{
				$return .= '&#' . $c . ';';
			}
		}
		return $return;
	}

	private static function gbk2pin(&$str)
	{
		$table = self::table_path . 'gbkpinyin.data';
		$len = strlen($str);
		$return = '';
		$fp = @fopen($table, 'rb');
		if (!$fp)
		{
			return $str;
		}

		for ($i = 0; $i < $len; $i++)
		{
			if (ord($str[$i]) > 0x80)
			{
				$c = substr($str, $i, 2);

				$high = ord($c[0]) - 0x81;
				$low  = ord($c[1]) - 0x40;
				$off = ($high << 8) + $low - ($high * 0x40);

				// 判断 off 值
				if ($off < 0)
				{
					return $str;
				}

				fseek($fp, $off * 8, SEEK_SET);
				$c = fread($fp, 8);
				$c = unpack('a8py', $c);
				$c = (self::$pinyin) ? substr($c['py'], 0, -1) : $c['py'];

				$return .= ($c ? $c . ' ' : substr($str, $i, 2));
				$i++;
			}
			else
			{
				$return .= $str[$i];
			}
		}
		@fclose($fp);
		return $return;
	}

	/**
	 * UTF-8 下中文简体转换到繁体
	 */
	private static function simp2trad($str)
	{
		$table = self::read_table('simp2trad');
		return strtr($str, $table);
	}

	/**
	 * UTF-8 下中文繁体转换到简体
	 */
	private static function trad2simp($str)
	{
		$table = self::read_table('trad2simp');
		return strtr($str, $table);
	}

	/**
	 * 将所有的非 ASCII UTF-8 字符转换为 NCR
	 */
	private static function utf2ncr($text)
	{
		return preg_replace_callback('#[\\xC2-\\xF4][\\x80-\\xBF]{1,3}#', array(__CLASS__, 'utf2ncr_callback'), $text);
	}

	/**
	 * 用于 encode_ncr() 的回调函数
	 */
	public static function utf2ncr_callback($m)
	{
		return '&#' . utf8::ord($m[0]) . ';';
	}

	/**
	 * 转换 NCR 到 UTF-8 字符
	 *
	 * 说明:
	 *	- 函数不会进行递归的转换, 如果你传入 &#38;#38; 将返回 &#38;
	 *	- 函数不检查 Unicode 字符的正确性, 因此实体可能会被转换为不存在的字符
	 */
	private static function ncr2utf($text)
	{
		return preg_replace_callback('/&#([0-9]{1,6}|x[0-9A-F]{1,5});/i', array(__CLASS__, 'ncr2utf_callback'), $text);
	}

	/**
	 * decode_ncr() 回调函数
	 * 函数会忽略大部分 (不是全部) 错误的 NCR
	 */
	public static function ncr2utf_callback($m)
	{
		$cp = (strncasecmp($m[1], 'x', 1)) ? $m[1] : hexdec(substr($m[1], 1));
		return utf8::chr($cp);
	}

	/**
	 * 检查编码是否支持
	 *
	 * @return mixed 有初始化编码或者支持该编码返回编码名称, 否则为 false
	 */
	private static function check_encoding($encoding, $type = '')
	{
		$encoding = strtolower($encoding);
		if ($encoding == 'pinyin' && $type == 'from')
		{
			return false;
		}
		else if (in_array($encoding, self::$support))
		{
			switch ($encoding)
			{
				case 'utf8':
					return 'utf-8';

				case 'gb':
				case 'gb2312':
					return 'gbk';

				case 'big-5':
					return 'big5';

				case 'ncr':
					return 'html-entities';

				default:
					return $encoding;
			}
		}

		if (self::$from_encoding && $type == 'from')
		{
			return self::$from_encoding;
		}
		else if (self::$to_encoding && $type == 'to')
		{
			return self::$to_encoding;
		}
		else
		{
			return false;
		}
	}

	private static function hex2bin(&$str)
	{
		$return = '';
		$str_len = strlen($str);
		for ($i = 0; $i < $str_len; $i+=2)
		{
			$return .= pack('C', hexdec(substr($str, $i, 2)));
		}
		return $return;
	}

	private static function read_table($name)
	{
		return read_serialize_file(self::table_path . $name . '.data');
	}
}

encoding::init();