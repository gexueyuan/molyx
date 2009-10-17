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
# $Id: functions_email.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class functions_email
{
	var $from = "";
	var $to = "";
	var $subject = "";
	var $message = "";
	var $header = "";
	var $footer = "";
	var $error = "";
	var $parts = array();
	var $mail_headers = "";
	var $multipart = "";
	var $boundry = "";
	var $char_set = 'GBK';
	var $smtp_fp = false;
	var $smtp_msg = "";
	var $smtpport = "";
	var $smtphost = "localhost";
	var $smtpuser = "";
	var $smtppassword = "";
	var $smtp_code = "";
	var $emailwrapbracket = 0;
	var $emailtype = 'mail';

	function functions_email()
	{
		global $forums, $DB, $bboptions;
		$forums->func->load_lang('email');
		$this->from = $bboptions['emailsend'];
		$this->emailwrapbracket = $bboptions['emailwrapbracket'];
		if ($bboptions['emailtype'] == 'smtp')
		{
			$this->emailtype = 'smtp';
			$this->smtpport = empty($bboptions['smtpport']) ? 25 : intval($bboptions['smtpport']);
			$this->smtphost = empty($bboptions['smtphost']) ? 'localhost' : $bboptions['smtphost'];
			$this->smtpuser = $bboptions['smtpuser'];
			$this->smtppassword = $bboptions['smtppassword'];
		}
		$this->header = $bboptions['email_header'];
		$this->footer = $bboptions['email_footer'];
		$this->boundry = "----=_NextPart_000_0022_01C1BD6C.D0C0F9F0";
		$bboptions['bbtitle'] = $this->clean_message($bboptions['bbtitle']);
	}

	function build_headers()
	{
		global $forums, $bboptions;
		$this->mail_headers .= "From: \"" . $bboptions['bbtitle'] . "\" <" . $this->from . ">\n";
		if ($this->emailtype == 'smtp')
		{
			if ($this->to)
			{
				$this->mail_headers .= "To: " . $this->to . "\n";
			}
			$this->mail_headers .= "Subject: " . $this->subject . "\n";
		}
		$this->mail_headers .= "Return-Path: " . $this->from . "\n";
		$this->mail_headers .= "X-Priority: 3\n";
		$this->mail_headers .= "X-Mailer: PHP Mailer\n";
		if (count ($this->parts) > 0)
		{
			$this->mail_headers .= "MIME-Version: 1.0\n";
			$this->mail_headers .= "Content-Type: multipart/mixed;\n\tboundary=\"" . $this->boundry . "\"\n\nThis is a MIME encoded message.\n\n--" . $this->boundry;
			$this->mail_headers .= "\nContent-Type: text/plain;\n\tcharset=\"" . $this->char_set . "\"\nContent-Transfer-Encoding: quoted-printable\n\n" . $this->message . "\n\n--" . $this->boundry;
			$this->message = "";
		}
	}

	function send_mail()
	{
		global $forums, $DB, $bboptions;
		$this->to = preg_replace(array("/[ \t]+/", "/,,/", "#\#\[\]'\"\(\):;/\$!?\^&\*\{\}#"), array('', ',', ''), $this->to);
		$this->from = preg_replace(array("/[ \t]+/", "/,,/", "#\#\[\]'\"\(\):;/\$!?\^&\*\{\}#"), array('', ',', ''), $this->from);
		$this->subject = $this->clean_message($this->subject);
		$this->build_headers();
		if (($this->from) AND ($this->subject))
		{
			$this->subject .= " ( From " . $bboptions['bbtitle'] . " )";
			$this->subject = convert_encoding($this->subject, 'UTF-8', $this->char_set);
			$this->message = convert_encoding($this->message);
			$this->message = str_replace(
				array("\r\n", "\r", "\n", '<br />'),
				array("\n", "\n", "\r\n", "\r\n"),
				$this->message
			);
			$this->mail_headers = convert_encoding($this->mail_headers);
			$this->mail_headers = str_replace(
				array("\r\n", "\r", "\n", '<br />'),
				array("\n", "\n", "\r\n", "\r\n"),
				$this->mail_headers
			);
			if ($this->emailtype != 'smtp')
			{
				@mail($this->to, $this->subject, $this->message, $this->mail_headers);
			}
			else
			{
				$this->smtp_send_mail();
			}
		}
		else
		{
			return false;
		}
	}

	function build_message($message = '')
	{
		global $forums, $bboptions;
		$message .= $this->fetch_email_footer();
		$message = str_replace("\t", '', $message);
		$this->message = $this->clean_message($message);
	}

	function clean_message($message = "")
	{
		$pregfind = array
		("/^(\r|\n)+?(.*)$/",
			"#<b>(.+?)</b>#",
			"#<strong>(.+?)</strong>#",
			"#<i>(.+?)</i>#",
			"#<s>(.+?)</s>#",
			"#<u>(.+?)</u>#",
			"#<!--quote-->(.+?)<!--quote1-->#",
			"#<!--quote--(.+?)\+(.+?)-->(.+?)<!--quote1-->#",
			"#<!--quote--(.+?)\+(.+?)\+(.+?)-->(.+?)<!--quote1-->#",
			"#<!--quote2-->(.+?)<!--quote3-->#",
			"#<!--Flash (.+?)-->.+?<!--End Flash-->#e",
			"#<img[^>]+src=[\"'](\S+?)[\"'].+?" . ">screen??(.*)>#",
			"#<img[^>]+src=[\"'](\S+?)['\"].+?" . ">#",
			"#<a href=[\"'](http|news|https|ftp|ed2k|rtsp|mms)://(\S+?)['\"].+?" . ">(.+?)</a>#",
			"#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#",
			);
		$pregreplace = array
		("\\2",
			"\\1",
			"\\1",
			"\\1",
			"--\\1--",
			"-\\1-",
			"\n\n------------ QUOTE ----------\n",
			"\n\n------------ QUOTE ----------\n",
			"\n\n------------ QUOTE ----------\n",
			"\n-----------------------------\n\n",
			"(FLASH MOVIE)",
			"(IMAGE: \\1)",
			"(IMAGE: \\1)",
			"\\1://\\2",
			"(EMAIL: \\2)",
			);
		$message = preg_replace($pregfind, $pregreplace, $message);
		$message = str_replace("#<br.*>#siU", "\r\n", $message);
		$message = preg_replace("#<.+?>#", '', $message);
		$pregfind = array
		("&quot;",
			"&#092;",
			"&#160;",
			"&#036;",
			"&#33;",
			"&#39;",
			"&lt;",
			"&gt;",
			"&#124;",
			"&amp;",
			"&#58;",
			"&#91;",
			"&#93;",
			"&#064;",
			"&#60;",
			"&#62;",
			);
		$pregreplace = array
		("\"",
			"\\",
			"\r\n",
			"\$",
			"!",
			"'",
			"<",
			">",
			'|',
			"&",
			":",
			"[",
			"]",
			'@',
			'<',
			'>',
			);
		return str_replace($pregfind, $pregreplace, $message);
	}

	function smtp_get_line()
	{
		$this->smtp_msg = "";
		while ($line = fgets($this->smtp_fp, 515))
		{
			$this->smtp_msg .= $line;
			if (substr($line, 3, 1) == " ")
			{
				break;
			}
		}
	}

	function smtp_send_cmd($cmd)
	{
		$this->smtp_msg = "";
		$this->smtp_code = "";
		fputs($this->smtp_fp, $cmd . "\r\n");
		$this->smtp_get_line();
		$this->smtp_code = substr($this->smtp_msg, 0, 3);
		return $this->smtp_code == "" ? false : true;
	}

	function smtp_crlf_encode($data)
	{
		$data .= "\n";
		return str_replace(array("\r", "\n", "\n.\r\n"), array("", "\r\n", "\n. \r\n"), $data);
	}

	function smtp_send_mail()
	{
		$this->smtp_fp = @fsockopen($this->smtphost, intval($this->smtpport), $errno, $errstr, 30);
		if (! $this->smtp_fp)
		{
			return;
		}
		$this->smtp_get_line();
		$this->smtp_code = substr($this->smtp_msg, 0, 3);
		if ($this->smtp_code == 220)
		{
			$data = $this->smtp_crlf_encode($this->mail_headers . "\n" . $this->message);
			$this->smtp_send_cmd("HELO " . $this->smtphost);
			if ($this->smtp_code != 250)
			{
				return;
			}
			if ($this->smtpuser AND $this->smtppassword)
			{
				$this->smtp_send_cmd("AUTH LOGIN");
				if ($this->smtp_code == 334)
				{
					$this->smtp_send_cmd(base64_encode($this->smtpuser));
					if ($this->smtp_code != 334)
					{
						return;
					}
					$this->smtp_send_cmd(base64_encode($this->smtppassword));
					if ($this->smtp_code != 235)
					{
						return;
					}
				}
				else
				{
					return;
				}
			}
			if ($this->emailwrapbracket)
			{
				if (! preg_match("/^</", $this->from))
				{
					$this->from = "<" . $this->from . ">";
				}
			}
			$this->smtp_send_cmd("MAIL FROM:" . $this->from);
			if ($this->smtp_code != 250)
			{
				return;
			}
			$to_arry = array($this->to);
			foreach($to_arry AS $to_email)
			{
				if ($this->emailwrapbracket)
				{
					$this->smtp_send_cmd("RCPT TO:<" . $to_email . ">");
				}
				else
				{
					$this->smtp_send_cmd("RCPT TO:" . $to_email);
				}
				if ($this->smtp_code != 250)
				{
					return;
				}
			}
			$this->smtp_send_cmd("DATA");
			if ($this->smtp_code == 354)
			{
				fputs($this->smtp_fp, $data . "\r\n");
			}
			else
			{
				return;
			}
			$this->smtp_send_cmd(".");
			if ($this->smtp_code != 250)
			{
				return;
			}
			$this->smtp_send_cmd("quit");
			if ($this->smtp_code != 221)
			{
				return;
			}
			@fclose($this->smtp_fp);
		}
		else
		{
			return;
		}
	}

	function fetch_email_lostpassword($data = array())
	{
		global $bboptions, $forums;
		$lostpassword = sprintf($forums->lang['mail_lostpassword'], $data['name'], $bboptions['bbtitle'], $bboptions['bbtitle'], $data['link'], $data['linkpage'], $data['name'], $data['code'], $data['host']);
		return $lostpassword;
	}

	function fetch_email_activationaccount($data = array())
	{
		global $bboptions, $forums;
		$activationaccount = sprintf($forums->lang['activationaccount'], $data['name'], $bboptions['bbtitle'], $bboptions['bbtitle'], $bboptions['bbtitle'], $data['link'], $data['linkpage'], $data['name'], $data['code']);
		return $activationaccount;
	}

	function fetch_email_changeemail($data = array())
	{
		global $bboptions, $forums;
		$changeemail = sprintf($forums->lang['changeemail'], $data['name'], $bboptions['bbtitle'], $bboptions['bbtitle'], $bboptions['bbtitle'], $data['link'], $data['linkpage'], $data['name'], $data['code']);
		return $changeemail;
	}

	function fetch_email_reportpost($data = array())
	{
		global $bboptions, $forums;
		$reportpost = sprintf($forums->lang['reportpost'], $data['moderator'], $data['username'], $data['thread'], $data['link'], $data['report']);
		return $reportpost;
	}

	function fetch_email_mailmember($data = array())
	{
		global $bboptions, $forums;
		$mailmember = sprintf($forums->lang['mailmember'], $data['username'], $data['from'], $bboptions['bbtitle'], $data['message'], $bboptions['bbtitle'], $bboptions['bbtitle']);
		return $mailmember;
	}

	function fetch_email_sendtofriend($data = array())
	{
		global $bboptions, $forums;
		$sendtofriend = sprintf($forums->lang['sendtofriend'], $data['username'], $data['from'], $bboptions['bbtitle'], $data['message'], $bboptions['bbtitle'], $bboptions['bbtitle']);
		return $sendtofriend;
	}

	function fetch_email_pmnotify($data = array())
	{
		global $bboptions, $forums;
		$pmnotify = sprintf($forums->lang['pmnotify'], $data['username'], $data['sender'], $data['title'], $data['link']);
		return $pmnotify;
	}

	function fetch_accept_account()
	{
		global $bboptions, $forums;
		$accept_account = sprintf($forums->lang['accept_account'], $bboptions['bbtitle'], $bboptions['bbtitle'], $bboptions['bburl']);
		return $accept_account;
	}

	function fetch_email_footer ()
	{
		global $bboptions, $forums;
		$bburl = $bboptions['bburl'] . '/' . $bboptions['forumindex'];
		$email_footer = sprintf($forums->lang['email_footer'], $bboptions['bbtitle'], $bboptions['bburl']);
		return $email_footer;
	}
}

?>