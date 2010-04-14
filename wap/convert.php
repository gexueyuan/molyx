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
class convert
{
	function convert_text($message = '')
	{
		$message = preg_replace('#<img[^>]+smilietext=(\'|"|\\\")(.*)(\\1).*>#siU', " \\2 ", $message);
		$message = preg_replace("/^(\r|\n)+?(.*)$/", "\\2", $message);
		$message = preg_replace("#<!--quote-(.+?)<!--quote3-->#", "" , $message);
		$message = preg_replace("#<!--Flash (.+?)-->.+?<!--End Flash-->#e", "(FLASH MOVIE)" , $message);
		$message = preg_replace("#<!--attachid::(\d+)-->(.+?)<!--attachid-->#", "(Attachment:\\1)" , $message);
		$message = preg_replace("#<!--editpost-->(.+?)<!--editpost1-->#", "" , $message);
		$message = preg_replace("#<img src=[\"'](\S+?)['\"].+?" . ">screen.+?" . ">#", "(IMAGE)" , $message);
		$message = preg_replace("#<img src=[\"'](\S+?)['\"].+?" . ">#", "(IMAGE)" , $message);
		$message = preg_replace("#<a href=[\"'](http|https|ftp|news)://(\S+?)['\"].+?" . ">(.+?)</a>#", "\\1://\\2" , $message);
		$message = preg_replace("#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#", "(EMAIL: \\2)" , $message);
		$message = str_replace("&amp;" , "&", $message);
		$message = str_replace("&quot;", "\"", $message);
		$message = str_replace("&#092;", "\\", $message);
		$message = str_replace("&#160;", "\r\n", $message);
		$message = str_replace("&#036;", "\$", $message);
		$message = str_replace("&#33;" , "!", $message);
		$message = str_replace("&#39;" , "'", $message);
		$message = str_replace("&lt;" , "<", $message);
		$message = str_replace("&gt;" , ">", $message);
		$message = str_replace("&#124;", '|', $message);
		$message = str_replace("&#58;" , ":", $message);
		$message = str_replace("&#91;" , "[", $message);
		$message = str_replace("&#93;" , "]", $message);
		$message = str_replace("&#064;", '@', $message);
		$message = str_replace("&#60;", '<', $message);
		$message = str_replace("&#62;", '>', $message);
		$message = str_replace("&nbsp;", ' ', $message);
		$message = str_replace("&" , "&amp;", $message);
		$message = strip_tags($message, "<a>");
		return $message;
	}

	function fetch_trimmed_title($text, $limit = 200, $post_set = 0)
	{
		$more = (utf8_strlen($text) > $limit) ? true : false;
		$text = $more ? utf8_substr($text, $post_set, $limit - 1) . '...' : $text;
		return $text;
	}
}
