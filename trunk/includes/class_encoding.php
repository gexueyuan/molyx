<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# copyright (c) 2004-2006 HOGE Software.
# official forum : http://molyx.com
# license : MolyX License, http://molyx.com/license
# MolyX2 is free software. You can redistribute this file and/or modify
# it under the terms of MolyX License. If you do not accept the Terms
# and Conditions stated in MolyX License, please do not redistribute
# this file.Please visit http://molyx.com/license periodically to review
# the Terms and Conditions, or contact HOGE Software.
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
	var $table_path = '';
	var $from_encoding = '';
	var $to_encoding = '';
	var $type = 'table';
	var $ncr = false;
	var $pinyin = true;

	/**
	 * 构造函数
	 *
	 * 说明:
	 *   - 优先级 mbstring > iconv > 查表
	 *
	 * @param string $from 初始化源编码, 可以为空
	 * @param string $to 初始化目的编码, 可以为空
	 */
	function encoding($from = '', $to = '')
	{
		$this->set($from, $to);
		$this->table_path = ROOT_PATH . 'includes/encoding/';
		switch (true)
		{
			case function_exists('mb_convert_encoding'):
				$this->type = 'mbstring';
				break;

			case function_exists('iconv'):
				$this->type = 'iconv';
				break;

			default:
				$this->type = 'table';
		}
	}

	/**
	 * 设置转换编码
	 */
	function set($from = '', $to = '')
	{
		$this->from_encoding = $this->check_encoding($from, 'from');
		$this->to_encoding = $this->check_encoding($to, 'to');
		return ($this->from_encoding && $this->to_encoding);
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
	function convert($str, $from = '', $to = '')
	{
		if($this->set($from, $to) && !empty($str) && $this->from_encoding != $this->to_encoding)
		{
			if (in_array($this->from_encoding, array('traditional', 'simplified')) || in_array($this->to_encoding, array('traditional', 'simplified')))
			{
				if (utf8_check($str))
				{
					if ($this->from_encoding == 'simplified' && $this->to_encoding == 'traditional')
					{
						return utf8_traditional_zh($str);
					}
					else if ($this->from_encoding == 'traditional' && $this->to_encoding == 'simplified')
					{
						return utf8_simplified_zh($str);
					}
				}
				return $str;
			}

			if ($this->to_encoding == 'pinyin')
			{
				if ($this->from_encoding != 'gbk')
				{
					$from_encoding = $this->from_encoding;
					$str = $this->convert($str, $this->from_encoding, 'gbk');
					$this->from_encoding = $from_encoding;
					$this->to_encoding = 'pinyin';
				}
				return $this->gbk2pin($str);
			}

			switch ($this->type)
			{
				case 'mbstring':
					return mb_convert_encoding($str, $this->to_encoding, $this->from_encoding);

				case 'iconv':
					if ($this->to_encoding == 'html-entities')
					{
						$str = iconv($this->from_encoding, 'utf-16be', $str);
						return $this->unicode2htm($str);
					}
					else if ($this->from_encoding != 'html-entities')
					{
						return iconv($this->from_encoding, $this->to_encoding . '//IGNORE', $str);
					}

				default:
					if ($this->from_encoding == 'utf-8' && $this->to_encoding == 'html-entities')
					{
						return utf8_to_ncr($str);
					}
					else if ($this->from_encoding == 'html-entities')
					{
						$str = utf8_from_ncr($str);
						if ($this->to_encoding != 'utf-8')
						{
							$str = $this->convert($str, 'utf-8', $this->to_encoding);
							$this->from_encoding = 'html-entities';
						}
						return $str;
					}
					else
					{
						$space = array("\n", "\r", "\t");
						$tag = array('<|n|>', '<|r|>', '<|t|>');
						$str = str_replace($space, $tag, $str);

						$method_name = substr($this->from_encoding, 0, 3);
						$to_unicode = $method_name . '2unicode';
						if ($this->from_encoding == 'utf-16le')
						{
							$str = $this->change_byte($str);
						}
						else if ($this->from_encoding != 'utf-16be' && method_exists($this, $to_unicode))
						{
							$str = $this->$to_unicode($str);
						}

						$from_unicode = 'unicode2' . $method_name;
						if ($this->to_encoding == 'utf-16le')
						{
							$str = $this->change_byte($str);
						}
						else if ($this->to_encoding != 'utf-16be' && method_exists($this, $from_unicode))
						{
							$str = $this->$from_unicode($str);
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
	function gbk2unicode(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = $this->read_table('gbkunicode');
		}

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
		return $this->hex2bin($return);
	}

	/**
	 * BIG-5 to UTF-16BE
	 */
	function big2unicode(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = $this->read_table('bigunicode');
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
		return $this->hex2bin($return);
	}

	/**
	 * UTF-16BE to GBK
	 */
	function unicode2gbk(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = $this->read_table('unicodegbk');
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
		return $this->hex2bin($return);
	}

	/**
	 * UTF-16BE to BIG-5
	 */
	function unicode2big(&$str)
	{
		static $table = array();
		if (empty($table))
		{
			$table = $this->read_table('unicodebig');
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
		return $this->hex2bin($return);
	}

	/**
	 * UTF-16BE to UTF-8
	 */
	function unicode2utf(&$str)
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
		return $this->hex2bin($return);
	}

	/**
	 * UTF-8 to UTF-16BE
	 *
	 * @param string $str
	 * @return string
	 */
	function utf2unicode(&$str)
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
		return $this->hex2bin($return);
	}

	/**
	 * UTF-16LE 和 BE 相互转换, 字符两个字节交换位置
	 */
	function change_byte(&$str)
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
	function unicode2htm(&$str)
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

	function gbk2pin(&$str)
	{
		$table = $this->table_path . 'gbkpinyin.data';
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
				$c = ($this->pinyin) ? substr($c['py'], 0, -1) : $c['py'];

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
	 * 检查编码是否支持
	 *
	 * @return mixed 有初始化编码或者支持该编码返回编码名称, 否则为 false
	 */
	function check_encoding($encoding, $type = '')
	{
		$encoding = strtolower($encoding);
		if ($encoding == 'pinyin' && $type == 'from')
		{
			return false;
		}
		else if (in_array($encoding, array('utf-8', 'gbk', 'big5', 'gb2312', 'big-5', 'utf-16be', 'utf-16le', 'html-entities', 'ncr', 'utf8', 'pinyin', 'traditional', 'simplified')))
		{
			switch ($encoding)
			{
				case 'utf8':
					return 'utf-8';

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
		if ($this->from_encoding && $type == 'from')
		{
			return $this->from_encoding;
		}
		else if ($this->to_encoding && $type == 'to')
		{
			return $this->to_encoding;
		}
		else
		{
			return false;
		}
	}

	function hex2bin(&$str)
	{
		$return = '';
		$str_len = strlen($str);
		for ($i = 0; $i < $str_len; $i+=2)
		{
			$return .= pack('C', hexdec(substr($str, $i, 2)));
		}
		return $return;
	}

	function read_table($name)
	{
		return read_serialize_file($this->table_path . $name . '.data');
	}
}

/**
 * 将 UTF-8 转换成 UNICODE 码点
 */
function utf8_ord($chr)
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

/**
 * 将所有的非 ASCII UTF-8 字符转换为 NCR
 */
function utf8_to_ncr($text)
{
	return preg_replace_callback('#[\\xC2-\\xF4][\\x80-\\xBF]{1,3}#', 'utf8_to_ncr_callback', $text);
}

/**
 * 用于 encode_ncr() 的回调函数
 */
function utf8_to_ncr_callback($m)
{
	return '&#' . utf8_ord($m[0]) . ';';
}

/**
* 转换 NCR 到 UTF-8 字符
*
* 说明:
*	- 函数不会进行递归的转换, 如果你传入 &#38;#38; 将返回 &#38;
*	- 函数不检查 Unicode 字符的正确性, 因此实体可能会被转换为不存在的字符
*/
function utf8_from_ncr($text)
{
	return preg_replace_callback('/&#([0-9]{1,6}|x[0-9A-F]{1,5});/i', 'utf8_from_ncr_callback', $text);
}

/**
 * decode_ncr() 回调函数
 * 函数会忽略大部分 (不是全部) 错误的 NCR
 */
function utf8_from_ncr_callback($m)
{
	$cp = (strncasecmp($m[1], 'x', 1)) ? $m[1] : hexdec(substr($m[1], 1));

	return utf8_chr($cp);
}

/**
 * UTF-8 下中文简体转换到繁体
 */
function utf8_traditional_zh($str)
{
	static $table = null;
	if (is_null($table))
	{
		$table = read_serialize_file(ROOT_PATH . 'includes/encoding/simp2trad.data');
	}
	return strtr($str, $table);
}

/**
 * UTF-8 下中文繁体转换到简体
 */
function utf8_simplified_zh($str)
{
	static $table = null;
	if (is_null($table))
	{
		$table = read_serialize_file(ROOT_PATH . 'includes/encoding/trad2simp.data');
	}
	return strtr($str, $table);
}

/**
 * 检查字符串是否兼容 UTF-8, 并不是严格的 UTF-8 编码检查, 会忽略 5/6 字节的字符
 */
function utf8_check($str)
{
    if (empty($str))
    {
        return true;
    }
    return (preg_match('/^.{1}/us', $str, $ar) == 1);
}
?>