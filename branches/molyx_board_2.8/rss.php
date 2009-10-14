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
# $Id: rss.php 189 2007-09-26 16:09:10Z sancho $
# **************************************************************************#
define('THIS_SCRIPT', 'rss');
require_once('./global.php');

class rss
{
	function show()
	{
		global $_INPUT, $forums, $bboptions, $DB;
		$forums->func->load_lang('rss');
		if ($_INPUT['fid'] != '')
		{
			$rssforum = explode(',', $_INPUT['fid']);
			foreach($rssforum as $fid)
			{
				if ($forums->forum->foruminfo[$fid])
				{
					$forum_title = $forums->forum->foruminfo[$fid]['name'];
					$forumids[] = intval($fid);
				}
			}
		}
		if ($_INPUT['fid'] == '' || count($forumids) == 0)
		{
			$forumids = array_keys($forums->forum->foruminfo);
		}
		if (is_array($forumids))
		{
			foreach($forumids as $f)
			{
				if ($this->check_permissions($forums->forum->foruminfo[$f]) == true)
				{
					$final .= ',' . $f;
				}
			}
		}
		$forumlist = "AND f.id IN (-1" . $final . ")";
		$t_forumlist = "AND t.forumid IN (-1" . $final . ")";

		$title = $forum_title ? $bboptions['bbtitle'] . " - " . $forum_title : $bboptions['bbtitle'];
		$limit = intval($_INPUT['limit']);
		if (!$limit OR $limit > 100)
		{
			$limit = 20;
		}
		header("Content-Type: text/xml; charset=UTF-8");
		switch ($_INPUT['version'])
		{
			case "rss2.0":
				echo "<?xml version=\"1.0\" encoding=\"" . $forums->lang['charset'] . "\"?>\n";
				echo "<rss version=\"2.0\">\n";
				echo "\t<channel>\n";
				echo "\t\t<title><![CDATA[" . $title . "]]></title>\n";
				echo "\t\t<description><![CDATA[" . $bboptions['bbtitle'] . " - " . $bboptions['bburl'] . "]]></description>\n";
				echo "\t\t<link>" . $bboptions['bburl'] . "</link>\n";
				echo "\t\t<copyright>Copyright 2004-2006 MolyX Studios</copyright>\n";
				echo "\t\t<generator>MolyX Board v" . $bboptions['version'] . "</generator>\n";
				break;
			default:
				echo "<?xml version=\"1.0\" encoding=\"" . $forums->lang['charset'] . "\"?>\n";
				echo "<rss version=\"0.92\">\n";
				echo "\t<channel>\n";
				echo "\t\t<title><![CDATA[" . $title . "]]></title>\n";
				echo "\t\t<description><![CDATA[" . $bboptions['bbtitle'] . " - " . $bboptions['bburl'] . "]]></description>\n";
				echo "\t\t<link>" . $bboptions['bburl'] . "</link>\n";
				echo "\t\t<copyright>Copyright 2004-2006 MolyX Studios</copyright>\n";
				echo "\t\t<language>zh</language>\n";
				break;
		}

		$query = $DB->query("SELECT t.*, p.pagetext, p.hidepost, f.name AS forumname, u.email
			FROM " . TABLE_PREFIX . "thread t
				LEFT JOIN " . TABLE_PREFIX . "forum f
					ON (f.id=t.forumid)
				LEFT JOIN " . TABLE_PREFIX . "post p
					ON (p.pid=t.firstpostid)
				LEFT JOIN " . TABLE_PREFIX . "user u
					ON (t.postuserid=u.id)
			WHERE 1=1 $t_forumlist ORDER BY t.dateline DESC LIMIT 0, $limit"
			);
		$i = 0;
		while ($thread = $DB->fetch_array($query))
		{
			$thread['title'] = utf8_htmlspecialchars(strip_tags($thread['title']));
			$thread['forumnanme'] = utf8_htmlspecialchars(strip_tags($$thread['forumname']));
			$thread['lastposter'] = utf8_htmlspecialchars($thread['lastposter']);
			$postdate = $forums->func->get_date($thread['dateline'], 2);
			$pubdate = date('r', $thread['dateline']);
			$lastdate = date('r', $thread['lastpost']);
			if ($thread['hidepost'])
			{
				$pagetext = $forums->lang['_posthidden'];
			}
			else
			{
				$pagetext = utf8_htmlspecialchars($this->clean_message($thread['pagetext']));
				$pagetext = preg_replace("(\r\n|\r|\n)", '<br />', $pagetext);
			}
			$thread['title'] = str_replace(array('&amp;amp;', '&amp;'), array('&#38;', '&'), $thread['title']);
			$pagetext = str_replace(array('&amp;amp;', '&amp;'), array('&#38;', '&'), $pagetext);
			switch ($_INPUT['version'])
			{
				case "rss2.0";
					echo ($i > 0) ? '' : "\t\t<pubDate>$pubdate</pubDate>\n";
					echo ($i > 0) ? '' : "\t\t<lastBuildDate>$lastdate</lastBuildDate>\n";
					echo "\t\t<item>\n";
					echo "\t\t\t<guid>" . $bboptions['bburl'] . "/redirect.php{$forums->sessionurl}t=" . $thread['tid'] . "&amp;goto=newpost</guid>\n";
					echo "\t\t\t<title>" . $thread['title'] . "</title>\n";
					echo "\t\t\t<author>" . $thread['postusername'] . "&lt;{$thread['email']}&gt;</author>\n";
					echo "\t\t\t<description><![CDATA[" . $forums->lang['_forum'] . ": " . $thread['forumname'] . "<br />" . $forums->lang['posttime'] . ": " . $postdate . "<br />" . $forums->lang['lastposter'] . ": " . $thread['lastposter'] . "<br />" . $pagetext . "]]></description>\n";
					echo "\t\t\t<link>" . $bboptions['bburl'] . "/redirect.php{$forums->sessionurl}t=" . $thread['tid'] . "&amp;goto=newpost</link>\n";
					echo "\t\t\t<category domain=\"" . $bboptions['bburl'] . "/forumdisplay.php{$forums->sessionurl}f=" . $thread['forumid'] . "\"><![CDATA[" . $thread['forumname'] . "]]></category>\n";
					echo "\t\t\t<pubDate>$pubdate</pubDate>\n";
					echo "\t\t</item>\n";
				break;

				default:
					echo ($i > 0) ? '' : "\t\t<lastBuildDate>$lastdate</lastBuildDate>\n";
					echo "\t\t<item>\n";
					echo "\t\t\t<title>" . $thread['title'] . "</title>\n";
					echo "\t\t\t<author>" . $thread['postusername'] . "&lt;{$thread['email']}&gt;</author>\n";
					echo "\t\t\t<description><![CDATA[" . $forums->lang['_forum'] . ": " . $thread['forumname'] . "<br />" . $forums->lang['posttime'] . ": " . $postdate . "<br />" . $forums->lang['lastposter'] . ": " . $thread['lastposter'] . "<br />" . $pagetext . "]]></description>\n";
					echo "\t\t\t<link>" . $bboptions['bburl'] . "/redirect.php{$forums->sessionurl}t=" . $thread['tid'] . "&amp;goto=newpost</link>\n";
					echo "\t\t</item>\n";
				break;
			}
			$i++;
		}
		switch ($_INPUT['version'])
		{
			case "rss2.0":
				echo "\t</channel>\n";
				echo " </rss>\n";
				break;
			default:
				echo "\t</channel>\n";
				echo " </rss>\n";
				break;
		}
	}

	function check_permissions($forum)
	{
		global $forums;
		$can_read = false;
		if ($forums->func->fetch_permissions($forum['canread'], 'canread') == true)
		{
			$can_read = true;
		}
		if ($forum['password'] != '' && $can_read == true)
		{
			if ($forums->forum->check_password($forum['id']) != true)
			{
				$can_read = false;
			}
		}
		return $can_read;
	}

	function clean_message($message = '')
	{
		if (empty($message))
		{
			return $message;
		}

		$message = preg_replace("/^(\r|\n)+?(.*)$/", "\\2", $message);
		$message = preg_replace("#<b>(.+?)</b>#" , "\\1", $message);
		$message = preg_replace("#<strong>(.+?)</strong>#" , "\\1", $message);
		$message = preg_replace("#<i>(.+?)</i>#" , "\\1", $message);
		$message = preg_replace("#<s>(.+?)</s>#" , "--\\1--", $message);
		$message = preg_replace("#<u>(.+?)</u>#" , "-\\1-" , $message);
		$message = preg_replace("#<!--quote-->(.+?)<!--quote1-->#", "\n\n------------ QUOTE ----------\n" , $message);
		$message = preg_replace("#<!--quote--(.+?)\+(.+?)-->(.+?)<!--quote1-->#", "\n\n------------ QUOTE ----------\n" , $message);
		$message = preg_replace("#<!--quote--(.+?)\+(.+?)\+(.+?)-->(.+?)<!--quote1-->#", "\n\n------------ QUOTE ----------\n" , $message);
		$message = preg_replace("#<!--quote2-->(.+?)<!--quote3-->#", "\n-----------------------------\n\n" , $message);
		$message = preg_replace("#<!--Flash (.+?)-->.+?<!--End Flash-->#", "(FLASH MOVIE)" , $message);
		$message = preg_replace("#<img[^>]+src=[\"'](\S+?)[\"'].+?" . ">screen??(.*)>#", "(IMAGE: \\1)" , $message);
		$message = preg_replace("#<img[^>]+src=[\"'](\S+?)['\"].+?" . ">#", "(IMAGE: \\1)" , $message);
		$message = preg_replace("#<a href=[\"'](http|news|https|ftp|ed2k|rtsp|mms)://(\S+?)['\"].+?" . ">(.+?)</a>#", "\\1://\\2" , $message);
		$message = preg_replace("#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#", "(EMAIL: \\2)" , $message);
		$message = preg_replace("#<.+?" . ">#" , "" , $message);
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
		return $message;
	}
}

$output = new rss();
$output->show();

?>




