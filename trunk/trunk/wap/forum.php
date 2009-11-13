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
# $Id: forum.php 189 2007-09-26 16:09:10Z sancho $
# **************************************************************************#
define('THIS_SCRIPT', 'forum');

require_once('./global.php');

class forum
{
	var $posthash = '';
	var $threadread = array();
	var $forum = array();
	var $newpost = 0;

	function show()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$forums->func->load_lang('forumdisplay');
		$this->posthash = $forums->func->md5_check();
		$_INPUT['f'] = intval($_INPUT['f']);

		$this->forum = $forums->forum->single_forum($_INPUT['f']);
		if (! $this->forum['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotfindforum']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$this->forum['name'] = strip_tags($this->forum['name']);
		if ($_INPUT['pwd'])
		{
			$this->check_permissions();
		}
		else
		{
			check_password($this->forum['id'], 1);
		}
		if ($this->forum['allowposting'])
		{
			$this->render_forum($_INPUT['f']);
		}
		else
		{
			$this->show_subforums($_INPUT['f']);
		}
	}

	function show_subforums($fid)
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$forumname = convert($this->forum['name']);
		$showforum = true;
		$canpost = false;

		if (is_array($forums->forum->forum_cache[ $fid ]))
		{
			$foruminfo[] = $forums->lang['forum_list'];
			foreach ($forums->forum->forum_cache[$fid] AS $id => $cat_data)
			{
				$cat_data['name'] = strip_tags($cat_data['name']);
				$foruminfo[] = "<a href='forum.php{$forums->sessionurl}f={$cat_data['id']}'>{$cat_data['name']}</a>\n";
			}
			$subforum = implode("<br />", convert($foruminfo));
		}
		if ($this->forum['parentid'] != '-1')
		{
			$showforum = false;
			$pforum = $forums->forum->single_forum($this->forum['parentid']);
			$pforum['name'] = convert($pforum['name']);
			$forums->lang['parentforum'] = convert($forums->lang['parentforum']);
		}
		if ($pforum['name'])
		{
			$show['p2'] = true;
		}
		include $forums->func->load_template('wap_forumdisplay');
	}

	function check_permissions()
	{
		global $forums, $_INPUT;
		if ($_INPUT['pwd'] == "")
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['requiredpassword']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		if ($_INPUT['pwd'] != $this->forum['password'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['errorforumpassword']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		if ($_POST['pwd'])
		{
			redirect("forum.php{$forums->sessionurl}pwd={$_INPUT['pwd']}&amp;f=" . $this->forum['id']);
		}
	}

	function render_forum($fid)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		$forum = $this->forum;
		$canpost = true;
		$forumname = convert($this->forum['name']);

		if ($_INPUT['showsub'])
		{
			$this->show_subforums($fid);
			exit;
		}
		$showforum = true;

		if ($this->forum['parentid'] != '-1')
		{
			$pforum = $forums->forum->single_forum($this->forum['parentid']);
			$pforum['name'] = convert(strip_tags($pforum['name']));
			$forums->lang['parentforum'] = convert($forums->lang['parentforum']);
		}

		if (is_array($forums->forum->forum_cache[ $fid ]))
		{
			$forumcount = count($forums->forum->forum_cache[ $fid ]);
			$subforum = "+ [<a href='forum.php{$forums->sessionurl}f={$fid}&amp;showsub=1'>{$forumcount} " . convert($forums->lang['subforum']) . "</a>]";
		}

		$firstpost = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$queryarray = array();
		$addquery = "";

		if (! $bbuserinfo['canviewothers'] OR $threadfilter == 'started')
		{
			$queryarray[] = "postuserid='" . $bbuserinfo['id'] . "'";
		}
		if (count($queryarray))
		{
			$addquery = ' AND ' . implode(' AND ', $queryarray);
		}
		if (! $bbuserinfo['is_mod'])
		{
			$visible = ' AND visible=1 ';
		}
		else
		{
			$visible = '';
		}

		$threads = $DB->query("SELECT tid, title, lastpost, lastposter FROM " . TABLE_PREFIX . "thread WHERE forumid=" . $this->forum['id'] . " {$visible}{$addquery} ORDER BY sticky DESC, lastpost DESC LIMIT " . $firstpost . ", 8");

		$threadlist = array();
		$i = 0;
		if ($DB->num_rows())
		{
			if ($firstpost)
			{
				$extra = "&amp;extra=" . $firstpost;
				$reffer = urlencode("forum.php{$forums->sessionurl}f={$this->forum['id']}{$extra}");
			}
			while ($t = $DB->fetch_array($threads))
			{
				++$i;
				$thread_array[ $t['tid'] ] = $t;
				$threadids[ $t['tid'] ] = $t['tid'];
				$t['title'] = str_replace(array("&amp;", "&"), array("&", "&amp;"), $t['title']);
				$showthread .= "<p>\n<img src='images/dot.gif' alt='-' /><a href='thread.php{$forums->sessionurl}t={$t['tid']}{$extra}'>" . strip_tags($t['title']) . "</a><br />\n";
				$showthread .= "<small>" . $forums->lang['lastpost'] . ": {$t['lastposter']}</small><br />\n";
				$showthread .= "<small>" . $forums->func->get_date($t['lastpost'], 2) . "</small>\n</p>\n\n";
			}
			$prevlink = $firstpost - 8;
			$nextlink = $firstpost + 8;
			$prevpage = ($prevlink < 0) ? false : true;
			$nextpage = ($i < 8) ? false : true;
		}
		else
		{
			$prevpage = false;
			$nextpage = false;
			$showthread = $forums->lang['nonewpost'];
		}
		$showthread = convert($showthread);

		$forums->lang['prevlink'] = $prevpage ? convert($forums->lang['prevlink']) : '';
		$forums->lang['nextlink'] = $nextpage ? convert($forums->lang['nextlink']) : '';
		if ($bbuserinfo['id'])
		{
			$forums->lang['postthread'] = convert($forums->lang['postthread']);
		}
		else
		{
			$forums->lang['register'] = convert($forums->lang['register']);
			$forums->lang['login'] = convert($forums->lang['login']);
		}
		$forums->lang['forum'] = convert($forums->lang['forum']);

		if ($prevpage OR $nextpage OR ($bbuserinfo['id'] AND $canpost))
		{
			$show['p1'] = true;
		}
		if ($pforum['name'])
		{
			$show['p2'] = true;
		}
		include $forums->func->load_template('wap_forumdisplay');
		exit;
	}
}

$output = new forum();
$output->show();
?>