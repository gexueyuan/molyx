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
define('THIS_SCRIPT', 'subscribe');
require_once('./global.php');

class subscribe
{
	function show()
	{
		global $forums, $DB, $bbuserinfo;
		$forums->func->load_lang('subscribe');
		$threadid = input::get('t', 0);
		$forumid = input::get('f', 0);
		$type = input::get('type', '');
		$this->thread = $forums->forum->single_forum($forumid);
		if ($type != 'forum')
		{
			$row = $DB->queryFirst("SELECT tid, forumid FROM " . TABLE_PREFIX . "thread WHERE tid='" . $threadid . "'");
			$this->thread = array_merge($row, $this->thread);
		}
		if (! $this->thread['id'])
		{
			$forums->func->standard_error("nosubscribe");
		}
		if ($type != 'forum' AND ! $this->thread['tid'])
		{
			$forums->func->standard_error("nosubscribe");
		}
		if (! $bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		if ($forums->func->fetch_permissions($this->thread['canread'], 'canread') != true)
		{
			$forums->func->standard_error("cannotviewboard");
		}
		if ($this->thread['password'] != "")
		{
			if ($this->thread['password'] != $forums->func->get_cookie('forum_' . $this->thread['fid']))
			{
				$forums->func->standard_error("cannotviewboard");
			}
		}
		if ($type == 'forum')
		{
			$DB->query("SELECT subscribeforumid FROM " . TABLE_PREFIX . "subscribeforum WHERE forumid='" . $this->thread['id'] . "' AND userid='" . $bbuserinfo['id'] . "'");
		}
		else
		{
			$DB->query("SELECT subscribethreadid FROM " . TABLE_PREFIX . "subscribethread WHERE threadid='" . $this->thread['tid'] . "' AND userid='" . $bbuserinfo['id'] . "'");
		}
		if ($DB->numRows())
		{
			$forums->func->standard_error("alreadysubscribe");
		}
		if ($type == 'forum')
		{
			$DB->insert(TABLE_PREFIX . "subscribeforum", array(
				'userid' => $bbuserinfo['id'],
				'forumid' => $forumid,
				'dateline' => TIMENOW
			), SHUTDOWN_QUERY);
			$forums->func->redirect_screen($forums->lang['subscribeforum'], "forumdisplay.php{$forums->sessionurl}f=" . $forumid . "");
		}
		else
		{
			$DB->insert(TABLE_PREFIX . "subscribethread", array(
				'userid' => $bbuserinfo['id'],
				'threadid' => $this->thread['tid'],
				'dateline' => TIMENOW
			), SHUTDOWN_QUERY);
			$forums->func->redirect_screen($forums->lang['subscribethread'], "showthread.php{$forums->sessionurl}f=" . $this->thread['id'] . "&amp;t=" . $this->thread['tid'] . "&amp;pp=" . input::get('pp', 0) . "");
		}
	}
}

$output = new subscribe();
$output->show();

?>