<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'sendmessage');
require_once('./global.php');

class sendmessage
{
	function show()
	{
		global $bbuserinfo, $forums;
		if (! $bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$forums->func->load_lang('sendmessage');
		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();

		$do = input::get('do', '');
		switch ($do)
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
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (! $bbuserinfo['canemail'])
		{
			$forums->func->standard_error("noperms");
		}
		$u = input::get('u', 0);
		if (!$u)
		{
			$forums->func->standard_error("cannotfindmailer");
		}
		if (!$user = $DB->query_first("SELECT id, name, email, emailcharset, options
			FROM " . TABLE_PREFIX . "user
			WHERE id = $u"))
		{
			$forums->func->standard_error("cannotfindmailer");
		}
		$forums->func->convert_bits_to_array($user, $user['options']);
		if ($user['hideemail'])
		{
			$forums->func->standard_error("cannotmailuser", false, $user['name']);
		}
		$forums->lang['sendmailto'] = sprintf($forums->lang['sendmailto'], $user['name']);
		if (!input::get('send', 0) OR $errors)
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
		global $forums, $DB, $bbuserinfo;
		$subject = input::get('subject', '');
		$message = input::get('message', '', false);
		if (!$subject || !$message)
		{
			$forums->func->standard_error("plzinputallform");
		}
		$message = $this->email->fetch_email_mailmember(array(
			'message' => preg_replace("#<br.*>#siU", "\n", str_replace("\r", '', $message)),
			'username' => $user['name'],
			'from' => $bbuserinfo['name']
		));
		$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
		$this->email->build_message($message);
		$this->email->subject = $subject;
		$this->email->to = $user['email'];
		$this->email->from = $bbuserinfo['email'];
		$this->email->send_mail();
		$forums->func->redirect_screen($forums->lang['sendmail'], input::get('url', ''));
	}

	function sendtofriend()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$t = input::get('t', 0);
		if (!$t)
		{
			$forums->func->standard_error("erroraddress");
		}
		if (!$thread = $DB->query_first("SELECT *
			FROM " . TABLE_PREFIX . "thread
			WHERE tid = $t"))
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
		global $forums, $DB, $bbuserinfo;
		$to_name = input::get('to_name', '');
		$to_email = input::get('to_email', '');
		$subject = input::get('subject', '');
		$message = input::get('message', '', false);

		if (!$to_name || !$to_email || !$subject || !$message)
		{
			$forums->func->standard_error("plzinputallform");
		}

		$to_email = clean_email($to_email);
		if (!$to_email)
		{
			$forums->func->standard_error("erroremail");
		}

		$message = $this->email->fetch_email_sendtofriend(array(
			'message' => preg_replace("#<br.*>#siU", "\n", str_replace("\r", "", $message)),
			'username' => $to_name,
			'from' => $bbuserinfo['name'],
		));
		$this->email->char_set = 'GBK';
		$this->email->build_message($message);
		$this->email->subject = $subject;
		$this->email->to = $to_email;
		$this->email->from = $bbuserinfo['email'];
		$this->email->send_mail();
		$forums->func->redirect_screen($forums->lang['sendmail'], "showthread.php{$forums->sessionurl}t=" . input::get('t', 0) . "&amp;pp=" . input::get('pp', 0));
	}
}

$output = new sendmessage();
$output->show();