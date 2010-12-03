<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
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
		global $forums, $DB, $bbuserinfo, $bboptions;
		$forums->func->load_lang('showthread');
		$forums->func->load_lang('post');
		$t = input::get('t', 0);
		$goto = input::get('goto', '');
		$pid = input::get('p', 0);
		if ($t < 1)
		{
			if ($goto == 'findpost')
			{
				if ($pid > 0)
				{
					$thread = $DB->queryFirst("SELECT threadid FROM " . TABLE_PREFIX . "post WHERE pid = " . $pid);
					if ($thread)
					{
						$t = $thread['threadid'];
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
		$this->thread = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid='" . $t . "'");
		$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		if (!$this->forum['id'] OR !$this->thread['tid'])
		{
			$forums->func->standard_error("erroraddress");
		}
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();

		$forums->forum->check_permissions($this->forum['id'], 1, 'thread');

		switch ($goto)
		{
			case 'new':
				if ($this->thread = $DB->queryFirst("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost > '" . $this->thread['lastpost'] . "' ORDER BY lastpost LIMIT 0, 1"))
				{
					$t = $this->thread['tid'];
				}
				else
				{
					$forums->func->standard_error("nonewthread");
				}
			break;

			case 'old':
				if ($this->thread = $DB->queryFirst("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost < '" . $this->thread['lastpost'] . "' ORDER BY lastpost DESC LIMIT 0, 1"))
				{
					$t = $this->thread['tid'];
				}
				else
				{
					$forums->func->standard_error("nooldthread");
				}
			break;

			case 'lastpost':
				$this->return_lastpost();
			break;

			case 'newpost':
				$page = 0;
				$pid = "";
				$last_time = $threadread[$this->thread['tid']];
				$last_time = $last_time ? $last_time : input::get('lastvisit', 0);
				$post = $DB->queryFirst("SELECT pid, dateline
					FROM " . TABLE_PREFIX . "post
					WHERE threadid='" . $this->thread['tid'] . "'
						AND moderate != 1
						AND dateline > '" . $last_time . "'
					ORDER BY pid");
				if (!empty($post))
				{
					$pid = "#pid" . $post['pid'];
					$cpost = $DB->queryFirst("SELECT COUNT(*) as post
						FROM " . TABLE_PREFIX . "post
						WHERE threadid='" . $this->thread['tid'] . "'
							AND moderate != 1
							AND pid <= '" . $post['pid'] . "'");
					if ((($cpost['post']) % $this->maxposts) == 0)
					{
						$pages = ($cpost['post']) / $this->maxposts;
					}
					else
					{
						$pages = ceil(($cpost['post']) / $this->maxposts);
					}
					$page = ($pages - 1) * $this->maxposts;
					if ($bboptions['rewritestatus'])
					{
						$forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . $pid);
					}
					else
					{
						$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;pp=$page" . $pid);
					}
				}
				else
				{
					$this->return_lastpost();
				}
			break;

			case 'findpost':
				if ($pid > 0)
				{
					$cpost = $DB->queryFirst("SELECT COUNT(*) as post FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND pid <= '" . $pid . "' LIMIT 0, 1");
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
					if ($bboptions['rewritestatus'])
					{
						$forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . "?p=" . $pid . "#pid" . $pid);
					}
					else
					{
						$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;p=$pid&amp;pp=" . $page . "#pid" . $pid);
					}
				}
				else
				{
					$this->return_lastpost();
				}
			break;
		}

		input::set('t', $t);
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
		$post = $DB->queryFirst("SELECT pid FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND moderate != 1 ORDER BY pid DESC LIMIT 0, 1");
		$bboptions['rewritestatus'] ? $forums->func->standard_redirect("thread-" . $this->thread['tid'] . "-" . $page . ".html" . "#pid" . $post['pid']) :$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;pp=" . $page . "#pid" . $post['pid']);
	}
}

$output = new redirect();
$output->show();