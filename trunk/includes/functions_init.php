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
 * 清除跨站脚本
 */
function xss_clean($var)
{
	return preg_replace('/(java|vb)script/i', '\\1 script', utf8_htmlspecialchars($var));
}

/**
 * 格式化路径，将 \ 和 // 转换为 /
 */
function format_path($path)
{
	if (strpos($path, '\\') !== false)
	{
		$path = str_replace('\\', '/', $path);
	}
	if (strpos($path, '//') !== false)
	{
		$path = str_replace('//', '/', $path);
	}
	return $path;
}

/**
 * 读取保存串行化字符串的文件
 * .php 文件去除开头的结束语句
 */
function read_serialize_file($filename)
{
	if ($return = @file_get_contents($filename))
	{
		if (strrchr($filename, '.') == '.php')
		{
			$return = substr($return, 14);
		}
		if ($return === 'a:0:{}')
		{
			return array();
		}
		else
		{
			return @unserialize($return);
		}
	}
	return false;
}

/**
 * 将 UNICODE 码点转换成 UTF-8 字符串
 */
function utf8_chr($cp)
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
 * 用于 UTF-8 的 htmlspecialchars()
 */
function utf8_htmlspecialchars($string)
{
	return str_replace(
		array('<', '>', '"', "'"),
		array('&lt;', '&gt;', '&quot;', '&#039;'),
		preg_replace('/&(?!#[0-9]+;)/si', '&amp;', $string)
	);
}

/**
 * htmlspecialchars() 逆操作
 */
function utf8_unhtmlspecialchars($string)
{
	return str_replace(
		array('&lt;', '&gt;', '&quot;', '&#039;', '&amp;'),
		array('<', '>', '"', "'", '&'),
		$string
	);
}

if (function_exists('mb_internal_encoding'))
{
	// 使用 mbstring 的函数
	mb_internal_encoding('UTF-8');

	/**
	 * mb_strlen
	 * 说明: 字符串中错误的字节在这个函数中将直接忽略, 不会被计算
	 */
	function utf8_strlen($str)
	{
		return mb_strlen($str);
	}

	/**
	 * mb_substr
	 * 根据给定的位置和长度截取字符串
	 */
	function utf8_substr($str, $offset, $length = NULL)
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

	/**
	 * mb_strtolower
	 * 仅支持已存在的字母表转换： 拉丁文, 希腊语, 西里尔字母, 亚美尼亚语和格鲁吉亚语, 没有中文字母表
	 */
	function utf8_strtolower($str)
	{
		return mb_strtolower($str);
	}
}
else
{
	require_once(ROOT_PATH . 'includes/utf8/utf8_native.php');
}
?>