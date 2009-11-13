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
# $Id: class_textparse.php 310 2007-10-19 07:43:30Z develop_tong $
# **************************************************************************#
/**
 * 对文本内容进行解析, 用于查看时
 */
class textparse
{
	/**
	 * 转换文本内容
	 *
	 * @var $show_html boolean 是否允许 HTML
	 */
	function convert_text($text = '', $show_html = false)
	{
		if ($show_html)
		{
			$text = textparse::parse_html($text);
		}
		if (strpos($text, '[/') !== false)
		{
			$text = textparse::parse_bbcode($text);
		}
		return $text;
	}

	/**
	 * 解析 HTML
	 */
	function parse_html($text = '')
	{
		if ($text == '')
		{
			return $text;
		}
		//$text = preg_replace('#<br.*>#siU', "\n", $text);
		$strfind = array('&#39;', '&#33;', '&#036;', '&#124;', '&gt;', '&lt;', '&quot;', '&amp;');
		$strreplace = array("'", '!', '$', '|', '>', '<', '"', '&');
		$text = str_replace($strfind, $strreplace, $text);
		return $text;
	}

	/**
	 * 解析自定义 BBCode
	 */
	function parse_bbcode($text = '')
	{
		global $forums;
		$forums->func->check_cache('bbcode');
		if (is_array($forums->cache['bbcode']) && count($forums->cache['bbcode']))
		{
			foreach($forums->cache['bbcode'] as $row)
			{
				if (substr_count($row['bbcodereplacement'], '{content}') > 1)
				{
					if ($row['twoparams'])
					{
						preg_match_all("#(\[" . $row['bbcodetag'] . "=(?:&quot;|&\#39;)?(.+?)(?:&quot;|&\#39;)?\])(.+?)(\[/" . $row['bbcodetag'] . "\])#si", $text, $match);
						for ($i = 0, $n = count($match[0]); $i < $n; $i++)
						{
							$row['bbcodereplacement'] = str_replace(array('{option}', '{content}'), array($match[2][$i], $match[3][$i]), $row['bbcodereplacement']);
							$text = str_replace($match[0][$i], $row['bbcodereplacement'], $text);
						}
					}
					else
					{
						preg_match_all("#(\[" . $row['bbcodetag'] . "\])(.+?)(\[/" . $row['bbcodetag'] . "\])#si", $text, $match);
						for ($i = 0, $n = count($match[0]); $i < $n; $i++)
						{
							$bbcodereplacement = str_replace('{content}', $match[2][$i], $row['bbcodereplacement']);
							$text = str_replace($match[0][$i], $bbcodereplacement, $text);
						}
					}
				}
				else
				{
					$replacement = explode('{content}', $row['bbcodereplacement']);
					if ($row['twoparams'])
					{
						$text = preg_replace("#\[" . $row['bbcodetag'] . "=(?:&quot;|&\#39;)?(.+?)(?:&quot;|&\#39;)?\]#si", str_replace('{option}', "\\1", $replacement[0]), $text);
					}
					else
					{
						$text = str_replace('[' . $row['bbcodetag'] . ']' , $replacement[0], $text);
					}
					$text = str_replace('[/' . $row['bbcodetag'] . ']', $replacement[1], $text);
				}
			}
		}
		return $text;
	}
}