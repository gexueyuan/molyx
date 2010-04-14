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
define('THIS_SCRIPT', 'redirect');
require_once('./global.php');

class redirect
{
	var $posthash = '';
	var $thread = array();
	var $forum = array();
	var $page = 0;
	var $maxposts = 10;
	var $moderator = array();
	var $cached_users = array();
	var $postcount = 0;
	var $already_replied = 0;
	var $canview_hideattach = 0;
	var $canview_hidecontent = 0;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('showthread');
		$forums->func->load_lang('post');
		$_INPUT['t'] = intval($_INPUT['t']);
		if ($_INPUT['t'] < 1)
		{
			if ($_INPUT['goto'] == 'findpost')
			{
				$pid = intval($_INPUT['p']);
				if ($pid > 0)
				{
					$thread = $DB->query_first("SELECT threadid FROM " . TABLE_PREFIX . "post WHERE pid = " . intval($_INPUT['p']));
					if ($thread)
					{
						$_INPUT['t'] = $thread['threadid'];
					}
					else
					{
						$forums->func->standard_error("errorthreadlink");
					}
				}
				else
				{
					$forums->func->standard_error("errorthreadlink");
				}
			}
			else
			{
				$forums->func->standard_error("errorthreadlink");
			}
		}
		$this->thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid='" . $_INPUT['t'] . "'");
		$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		if (!$this->forum['id'] OR !$this->thread['tid'])
		{
			$forums->func->standard_error("erroraddress");
		}
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();

		$forums->forum->check_permissions($this->forum['id'], 1, 'thread');

		if (isset($_INPUT['goto']))
		{
			if ($_INPUT['goto'] == 'new')
			{
				if ($this->thread = $DB->query_first("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost > '" . $this->thread['lastpost'] . "' ORDER BY lastpost LIMIT 0, 1"))
				{
					$_INPUT['t'] = $this->thread['tid'];
					require (ROOT_PATH . 'showthread.php');
					exit;
				}
				else
				{
					$forums->func->standard_error("nonewthread");
				}
			}
			else if ($_INPUT['goto'] == 'old')
			{
				if ($this->thread = $DB->query_first("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost < '" . $this->thread['lastpost'] . "' ORDER BY lastpost DESC LIMIT 0, 1"))
				{
					$_INPUT['t'] = $this->thread['tid'];
					require (ROOT_PATH . 'showthread.php');
					exit;
				}
				else
				{
					$forums->func->standard_error("nooldthread");
				}
			}
			else if ($_INPUT['goto'] == 'lastpost')
			{
				$this->return_lastpost();
			}
			else if ($_INPUT['goto'] == 'newpost')
			{
				$page = 0;
				$pid = "";
				$last_time = $threadread[$this->thread['tid']];
				$last_time = $last_time ? $last_time : $_INPUT['lastvisit'];
				if ($post = $DB->query_first("SELECT pid, dateline FROM " . TABLE_PREFIX . "post WHERE  threadid='" . $this->thread['tid'] . "' AND moderate != 1 AND dateline > '" . $last_time . "' ORDER BY pid LIMIT 0, 1"))
				{
					$pid = "#pid" . $post['pid'];
					$cpost = $DB->query_first("SELECT COUNT(*) as post FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND moderate != 1 AND pid <= '" . $post['pid'] . "' LIMIT 0, 1");
					if ((($cpost['post']) % $this->maxposts) == 0)
					{
						$pages = ($cpost['post']) / $this->maxposts;
					}
					else
					{
						$pages = ceil(($cpost['post']) / $this->maxposts);
					}
					$page = ($pages - 1) * $this->maxposts;
					$bboptions['rewritestatus'] ? $forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . $pid) : $forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;pp=$page" . $pid);
				}
				else
				{
					$this->return_lastpost();
				}
			}
			else if ($_INPUT['goto'] == 'findpost')
			{
				$pid = intval($_INPUT['p']);
				if ($pid > 0)
				{
					$cpost = $DB->query_first("SELECT COUNT(*) as post FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND pid <= '" . $pid . "' LIMIT 0, 1");
					if ((($cpost['post']) % $this->maxposts) == 0)
					{
						$pages = ($cpost['post']) / $this->maxposts;
					}
					else
					{
						$number = (($cpost['post']) / $this->maxposts);
						$pages = ceil($number);
					}
					$page = ($pages - 1) * $this->maxposts;
					$bboptions['rewritestatus'] ? $forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . "?p=" . $pid . "#pid" . $pid) : $forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;p=$pid&amp;pp=" . $page . "#pid" . $pid);
				}
				else
				{
					$this->return_lastpost();
				}
			}
		}
		require (ROOT_PATH . 'showthread.php');
		exit;
	}

	function return_lastpost()
	{
		global $forums, $DB , $bboptions;
		$page = 0;
		if ($this->thread['post'])
		{
			if ((($this->thread['post'] + 1) % $this->maxposts) == 0)
			{
				$pages = ($this->thread['post'] + 1) / $this->maxposts;
			}
			else
			{
				$number = (($this->thread['post'] + 1) / $this->maxposts);
				$pages = ceil($number);
			}
			$page = ($pages - 1) * $this->maxposts;
		}
		$post = $DB->query_first("SELECT pid FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND moderate != 1 ORDER BY pid DESC LIMIT 0, 1");
		$bboptions['rewritestatus'] ? $forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . "#pid" . $post['pid']) :$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;pp=" . $page . "#pid" . $post['pid']);
	}
}

$output = new redirect();
$output->show();

?>