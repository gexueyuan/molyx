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
define('THIS_SCRIPT', 'report');
require_once('./global.php');

class report
{
	var $postid = 0;
	var $threadid = 0;
	var $thread = array();
	var $forum = array();
	var $forumid = 0;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$forums->func->load_lang('report');
		if (!$bbuserinfo['id'])
		{
			$forums->func->standard_error("noperms");
		}
		$this->postid = intval($_INPUT['p']);
		$this->threadid = intval($_INPUT['t']);
		$this->thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid=" . $this->threadid . "");
		$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		$this->forumid = $this->forum['id'];
		if ((!$this->threadid) OR (!$this->postid) OR (!$this->forumid))
		{
			$forums->func->standard_error("cannotfindreport");
		}
		switch ($_INPUT['do'])
		{
			case 'report':
				$this->report_form();
				break;
			case 'send':
				$this->send();
				break;
			default:
				$this->report_form();
				break;
		}
	}

	function check_permissions()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$return = false;
		if ($forums->func->fetch_permissions($this->forum['canread'], 'canread') == true)
		{
			$return = true;
		}
		if ($this->forum['password'])
		{
			$this->forum_password = $forums->func->get_cookie('forum_' . $this->forum['id']);
			if ($this->forum_password == $this->forum['password'])
			{
				$return = true;
			}
		}
		if (!$return)
		{
			$forums->func->standard_error("cannotreport");
		}
	}

	function report_form()
	{
		global $forums, $bbuserinfo, $_INPUT, $bboptions;
		$this->check_permissions();
		$title = $this->thread['title'];
		$pagetitle = $this->thread['title'] . " - " . $forums->lang['reportbadpost'] . " - " . $bboptions['bbtitle'];
		$nav = array_merge($forums->forum->forums_nav($this->forum['id']), array($forums->lang['reportbadpost']));
		include $forums->func->load_template('sendmail_report');
		exit;
	}

	function send()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		if ($_INPUT['message'] == "")
		{
			$forums->func->standard_error("plzinputallform");
		}
		$this->check_permissions();
		$mods = array();
		$nmods = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset, m.moderatorid FROM " . TABLE_PREFIX . "moderator m, " . TABLE_PREFIX . "user u WHERE m.forumid=" . $this->forumid . " and m.userid=u.id");
		if ($DB->num_rows($nmods))
		{
			while ($r = $DB->fetch_array($nmods))
			{
				$mods[] = $r;
			}
		}
		else
		{
			$smods = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset FROM " . TABLE_PREFIX . "user u, " . TABLE_PREFIX . "usergroup g WHERE g.supermod=1 AND u.usergroupid=g.usergroupid");
			if ($DB->num_rows($smods))
			{
				while ($r = $DB->fetch_array($smods))
				{
					$mods[] = $r;
				}
			}
			else
			{
				$admin = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset FROM " . TABLE_PREFIX . "user u, " . TABLE_PREFIX . "usergroup g WHERE g.cancontrolpanel=1 AND u.usergroupid=g.usergroupid");
				while ($r = $DB->fetch_array($admin))
				{
					$mods[] = $r;
				}
			}
		}
		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();
		$report = trim($_INPUT['message']);
		foreach($mods as $ids => $data)
		{
			$message = $this->email->fetch_email_reportpost(array('moderator' => $data['name'],
					'username' => $bbuserinfo['name'],
					'thread' => $this->thread['title'],
					'link' => $bboptions[bburl] . "/showthread.php?f=" . $this->forumid . "&amp;t=" . $this->threadid . "&amp;pp=" . $_INPUT['pp'] . "#pid" . $this->postid,
					'report' => $report,
					)
				);
			$this->email->build_message($message);
			if ($bboptions['reporttype'] == 'email')
			{
				$this->email->char_set = $data['emailcharset']?$data['emailcharset']:'GBK';
				$this->email->subject = $forums->lang['reportbadpost'] . ' - ' . $bboptions['bbtitle'];
				$this->email->to = $data['email'];
				$this->email->send_mail();
			}
			else
			{
				$_INPUT['title'] = $forums->lang['reportthread'] . ': ' . $this->thread['title'];
				$_POST['post'] = $message;
				$_INPUT['username'] = $data['name'];
				require_once(ROOT_PATH . 'includes/functions_private.php');
				$pm = new functions_private();
				$bbuserinfo['pmfolders'] = unserialize($bbuserinfo['pmfolders']);
				if (count($bbuserinfo['pmfolders']) < 2)
				{
					$bbuserinfo['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']));
				}
				$_INPUT['noredirect'] = 1;
				$pm->sendpm();
			}
		}
		$forums->lang['hasreport'] = sprintf($forums->lang['hasreport'], $bbuserinfo['name']);
		$forums->func->redirect_screen($forums->lang['hasreport'], "showthread.php{$forums->sessionurl}f=" . $this->forumid . "&amp;t=" . $this->threadid . "&amp;pp=" . $_INPUT['pp'] . "#pid" . $this->postid);
	}
}

$output = new report();
$output->show();

?>