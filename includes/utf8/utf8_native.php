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
 * 返回字符个数而不是字节数
 * utf8_decode() 会把非 ISO-8859-1 字符转换为 '?', 对于计数已经足够了
 * 在 PHP4 中速度比 iconv_strlen 和 mb_strlen 快
 * 说明: 这个函数不会统计字符中错误的 UTF-8 字节
 */
function utf8_strlen($str)
{
	return strlen(utf8_decode($str));
}

/**
 * UTF-8 兼容 substr
 * 说明:
 *   - 不同于 substr, offset 或 length 不是整数时不会报错而是转换为整数
 *   - substr 出错可能会返回 false, 这个函数类似 mb_substr 出错时返回一个空字符串
 *   - Perl 兼容的正则最多仅支持重复 65536 次, 本函数将会在需要的时候按照 65535 对字符进行分组
 *   - utf8_strlen 只在需要的时候进行, offset 为正数或者未定义长度的时候不需要执行
 */
function utf8_substr($str, $offset, $length = null)
{
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
		$strlen = utf8_strlen($str);
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
			$strlen = utf8_strlen($str);
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

/**
 * 将字符转换为小写
 * 仅支持已存在的字母表转换： 拉丁文, 希腊语, 西里尔字母, 亚美尼亚语和格鲁吉亚语, 没有中文字母表
 */
function utf8_strtolower($string)
{
	static $utf8_case_table;
	if (!is_array($utf8_case_table))
	{
		$utf8_case_table = read_serialize_file(ROOT_PATH . 'includes/encoding/upper2lower.data');
	}

	return strtr(strtolower($string), $utf8_case_table);
}
?>