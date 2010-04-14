<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'announcement');
require_once('./global.php');

class announce
{
	var $pp = 0;
	var $offset = 0;
	var $parser;
	var $lib;

	function show()
	{
		global $_INPUT, $DB, $bbuserinfo, $bboptions, $forums;
		$forums->func->load_lang('showthread');
		$this->pp = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$this->offset = $_INPUT['offset'] ? intval($_INPUT['offset']) : 0;
		$pages = '';
		$forums->lang['announcement'] = convert($forums->lang['announcement']);

		require_once(ROOT_PATH . 'includes/class_textparse.php');
		require_once(ROOT_PATH . "wap/convert.php");
		$this->lib = new convert();

		$i = 0;

		$DB->query("SELECT a.pagetext, a.forumid, a.userid, a.allowhtml, a.views, a.startdate, a.enddate, a.id AS announceid, a.title AS announcetitle, u.name FROM " . TABLE_PREFIX . "announcement a LEFT JOIN " . TABLE_PREFIX . "user u on (a.userid=u.id)  WHERE active = 1 AND (startdate=0 OR startdate < " . TIMENOW . ") AND (enddate=0 OR enddate > " . TIMENOW . ") ORDER BY enddate DESC LIMIT " . $this->pp . ", 5");
		if ($DB->num_rows())
		{
			$forums->lang['from'] = convert($forums->lang['from']);
			$forums->lang['to'] = convert($forums->lang['to']);
			while ($announce = $DB->fetch_array())
			{
				if ($this->endoutput)
				{
					continue;
				}
				++$i;
				$announce = $this->parse_row($announce);
				$this->offset = 0;
				$postcount = $announce['row']['postcount'];
				$announcement[] = $announce;
			}
			foreach ($announcement AS $announce)
			{
				$showannounce .= "<p># {$announce['row']['postcount']}{$announce['row']['announcetitle']}<br />";
				$showannounce .= "{$announce['row']['pagetext']}<br />";
				$showannounce .= "<small>{$announce['poster']['name']}</small><br />";
				$showannounce .= "<small>{$announce['row']['dateline']}</small></p>";
			}
			if ($this->urllink)
			{
				$urllink = $this->urllink;
			}
			else
			{
				$urllink = "announce.php{$forums->sessionurl}pp={$postcount}";
			}
			$showpage = ($i >= $perpage OR ($i < $perpage AND $this->endoutput)) ? true : false;
			if ($showpage)
			{
				$nextpage = "\n<p><a href='{$urllink}'>" . convert($forums->lang['nextlink']) . "</a></p>";
			}
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "announcement SET views=views+1 WHERE id  IN (0$ids)");
		}
		else
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotviewannounce']);
			include $forums->func->load_template('wap_info');
			exit;
		}

		include $forums->func->load_template('wap_announcement');
		exit;
	}

	function parse_row($row = array())
	{
		global $forums, $bbuserinfo;
		$poster = array();
		if (isset($this->cached_users[ $row['userid'] ]))
		{
			$poster['name'] = $this->cached_users[ $row['userid'] ];
		}
		else
		{
			$poster['name'] = convert($row['name']);
			$this->cached_users[ $row['userid'] ] = $poster['name'];
		}
		if ($row['startdate'] and $row['enddate'])
		{
			$row['dateline'] = $forums->lang['from'] . " " . $forums->func->get_time($row['startdate'], 'Y-m-d') . "<br />" . $forums->lang['to'] . " " . $forums->func->get_time($row['enddate'], 'Y-m-d');
		}
		else if ($row['startdate'] and ! $row['enddate'])
		{
			$row['dateline'] = $forums->lang['from'] . " " . $forums->func->get_time($row['startdate'], 'Y-m-d');
			$row['dateline'] = convert($row['dateline']);
		}
		else
		{
			$row['dateline'] = '';
		}
		$row['announcetitle'] = convert(strip_tags($row['announcetitle']));
		$row['pagetext'] = utf8_unhtmlspecialchars($row['pagetext']);

		$row['pagetext'] = textparse::convert_text($row['pagetext']);
		$row['pagetext'] = $this->lib->convert_text($row['pagetext']);

		if ($this->offset)
		{
			$row['pagetext'] = substr($row['pagetext'], $this->offset);
		}

		$this->postcount++;
		$row['postcount'] = $this->pp + $this->postcount;

		$postlen = strlen($row['pagetext']);
		$leftlen = 400 - $this->contents_len - $this->pollslen;
		$this->contents_len = $this->contents_len + $this->pollslen + $postlen;
		if ($this->contents_len > 400)
		{
			$this->endoutput = true;
			$row['pagetext'] = $this->lib->fetch_trimmed_title($row['pagetext'], $leftlen);
			$offset = $this->offset + $this->lib->post_set;
			$pp = $row['postcount'] -1;
			$this->urllink = "announce.php{$forums->sessionurl}pp={$pp}&amp;offset={$offset}";
		}
		$row['pagetext'] = convert($row['pagetext']);
		return array('row' => $row, 'poster' => $poster);
	}
}

$output = new announce();
$output->show();

?>