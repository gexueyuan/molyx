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
# $Id: sendmessage.php 189 2007-09-26 16:09:10Z sancho $
# **************************************************************************#
define('THIS_SCRIPT', 'sendmessage');
require_once('./global.php');

class sendmessage
{
	function show()
	{
		global $_INPUT, $bbuserinfo, $forums;
		if (! $bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$forums->func->load_lang('sendmessage');
		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();
		switch ($_INPUT['do'])
		{
			case 'mailmember':
				$this->mailmember();
				break;
			case 'sendtofriend':
				$this->sendtofriend();
				break;
			case 'dosend':
				$this->dosend();
				break;
			default:
				$forums->func->standard_error("erroroperation");
				break;
		}
	}

	function mailmember($errors = '')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions;
		if (! $bbuserinfo['canemail'])
		{
			$forums->func->standard_error("noperms");
		}
		$_INPUT['u'] = intval($_INPUT['u']);
		if (!$_INPUT['u'])
		{
			$forums->func->standard_error("cannotfindmailer");
		}
		if (!$user = $DB->query_first("SELECT id, name, email, emailcharset, options FROM " . TABLE_PREFIX . "user WHERE id=" . $_INPUT['u'] . ""))
		{
			$forums->func->standard_error("cannotfindmailer");
		}
		$forums->func->convert_bits_to_array($user, $user['options']);
		if ($user['hideemail'])
		{
			$forums->func->standard_error("cannotmailuser", false, $user['name']);
		}
		$forums->lang['sendmailto'] = sprintf($forums->lang['sendmailto'], $user['name']);
		if (! $_INPUT['send'] OR $errors)
		{
			$pagetitle = $forums->lang['sendmail'] . " - " . $bboptions['bbtitle'];
			$nav = array($forums->lang['sendmailto']);
			include $forums->func->load_template('sendmail_mailmember');
			exit;
		}
		else
		{
			$this->domailmember($user);
		}
	}

	function domailmember($user)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (! $_INPUT['subject'] OR ! $_INPUT['message'])
		{
			$forums->func->standard_error("plzinputallform");
		}
		$message = $this->email->fetch_email_mailmember(array(
			'message' => preg_replace("#<br.*>#siU", "\n", str_replace("\r", '', $_POST['message'])),
			'username' => $user['name'],
			'from' => $bbuserinfo['name']
		));
		$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
		$this->email->build_message($message);
		$this->email->subject = $_INPUT['subject'];
		$this->email->to = $user['email'];
		$this->email->from = $bbuserinfo['email'];
		$this->email->send_mail();
		$forums->func->redirect_screen($forums->lang['sendmail'], $_INPUT['url']);
	}

	function sendtofriend()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$_INPUT['t'] = intval($_INPUT['t']);
		if (!$_INPUT['t'])
		{
			$forums->func->standard_error("erroraddress");
		}
		if (!$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid=" . $_INPUT['t'] . ""))
		{
			$forums->func->standard_error("erroraddress");
		}
		$subject = strip_tags($thread['title']);
		$forum = $forums->forum->single_forum($thread['forumid']);
		if (! $forum['id'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$forums->func->fetch_permissions($forum['id']['canread'], 'canread');
		$threadurl = preg_replace('/\?s=\w{32}(&)?/', '?', $forums->url);
		$forums->lang['sendfriendcontent'] = sprintf($forums->lang['sendfriendcontent'], $threadurl, $bboptions['bbtitle'], $bbuserinfo['name']);
		$pagetitle = $forums->lang['sendfriend'] . " - " . $bboptions['bbtitle'];
		$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f=" . $forum['id'] . "'>" . $forum['name'] . "</a>", "<a href='showthread.php{$forums->sessionurl}f=" . $forum['id'] . "&amp;t=" . $thread['tid'] . "'>" . $thread['title'] . "</a>", $forums->lang['sendfriend']);
		include $forums->func->load_template('sendmail_sendtofriend');
		exit;
	}

	function dosend()
	{
		global $_INPUT, $forums, $DB, $bbuserinfo;
		if (!$_INPUT['to_name'] OR !$_INPUT['to_email'] OR !$_INPUT['message'] OR !$_INPUT['subject'])
		{
			$forums->func->standard_error("plzinputallform");
		}
		$to_email = clean_email($_INPUT['to_email']);
		if (!$to_email)
		{
			$forums->func->standard_error("erroremail");
		}
		$message = $this->email->fetch_email_sendtofriend(array(
			'message' => preg_replace("#<br.*>#siU", "\n", str_replace("\r", "", $_POST['message'])),
			'username' => $_INPUT['to_name'],
			'from' => $bbuserinfo['name'],
		));
		$this->email->char_set = 'GBK';
		$this->email->build_message($message);
		$this->email->subject = $_INPUT['subject'];
		$this->email->to = $_INPUT['to_email'];
		$this->email->from = $bbuserinfo['email'];
		$this->email->send_mail();
		$forums->func->redirect_screen($forums->lang['sendmail'], "showthread.php{$forums->sessionurl}t=" . $_INPUT['t'] . "&amp;pp=" . $_INPUT['pp']);
	}
}

$output = new sendmessage();
$output->show();

?>




