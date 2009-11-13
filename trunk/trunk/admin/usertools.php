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
# $Id: usertools.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');
class usertools
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('user.php', $forums->lang['manageuser']);
		switch ($_INPUT['do'])
		{
			case 'pmstats':
				$this->pmstats();
				break;
			case 'pmfolderstats':
				$this->pmfolderstats();
				break;
			case 'removepms':
				$this->removepms();
				break;
			case 'pmuserstats':
				$this->pmuserstats();
				break;
			case 'sendmail':
				$this->sendmail();
				break;
			default:
				$this->email_form();
				break;
		}
	}

	function pmstats()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['pmtools'];
		$detail = $forums->lang['pmtoolsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);

		$groups = array();
		$pms = $DB->query("SELECT COUNT(*) AS total, userid FROM " . TABLE_PREFIX . "pm GROUP BY userid ORDER BY total DESC");
		while ($pm = $DB->fetch_array($pms))
		{
			$groups[$pm['total']]++;
		}

		$forums->admin->columns[] = array($forums->lang['pmnums'], "30%");
		$forums->admin->columns[] = array($forums->lang['groupusers'] , "30%");
		$forums->admin->columns[] = array($forums->lang['action'], "40%");

		$forums->admin->print_table_start($forums->lang['pmstats']);
		if (count($groups))
		{
			foreach ($groups AS $key => $total)
			{
				$showpmlist = sprintf($forums->lang['showpmlist'], $key);
				$forums->admin->print_cells_row(array($key, $total, "<a href='usertools.php?{$forums->sessionurl}do=pmuserstats&amp;total={$key}'>{$showpmlist}</a>"));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['nuanypms']);
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function pmuserstats()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['pmtools'];
		$detail = $forums->lang['pmtoolsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header();
		echo "<script type='text/javascript'>\n";
		echo "function js_jump(userid)\n";
		echo "{\n";
		echo "value = eval('document.cpform.id' + userid + '.options[document.cpform.id' + userid + '.selectedIndex].value');\n";
		echo "var page = '';\n";
		echo "switch (value) {\n";
		echo "case 'pmstats': page = 'usertools.php?{$forums->js_sessionurl}do=pmfolderstats&u=' + userid; break;\n";
		echo "case 'profile': page = 'user.php?{$forums->js_sessionurl}do=doform&u=' + userid; break;\n";
		echo "case 'pmuser': page = '../private.php?do=newpm&u=' + userid; break;\n";
		echo "case 'delete': page = 'usertools.php?{$forums->js_sessionurl}do=removepms&u=' + userid; break;\n";
		echo "}\n";
		echo "if (page != '') {\n";
		echo "window.location = page;\n";
		echo "} else {\n";
		echo "window.location = 'mailto:' + value;\n";
		echo "}\n";
		echo "}\n";
		echo "</script>\n";
		$forums->admin->print_table_start($forums->lang['pmtools']);

		$total = intval($_INPUT['total']);
		if (!$total)
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems']);
		}
		$users = $DB->query("SELECT COUNT( * ) AS total, p.userid, u.name, u.lastactivity,u.email FROM " . TABLE_PREFIX . "pm p LEFT JOIN " . TABLE_PREFIX . "user u ON (p.userid = u.id) GROUP BY p.userid HAVING total = {$total} ORDER BY u.name DESC");
		if ($DB->num_rows($users))
		{
			$forums->admin->columns[] = array($forums->lang['username'], "30%");
			$forums->admin->columns[] = array($forums->lang['lastactivity'], "30%");
			$forums->admin->columns[] = array($forums->lang['action'], "40%");
			while ($user = $DB->fetch_array($users))
			{
				$forums->admin->print_cells_row(array($user['name'], $forums->func->get_date($user['lastactivity'], 2), $forums->admin->print_input_select_row('id' . $user['userid'],
							array(0 => array('pmstats', $forums->lang['viewuserpms']),
								1 => array('profile', $forums->lang['edituserprofile']),
								2 => array('pmuser', $forums->lang['usepmcontact']),
								3 => array($user['email'], $forums->lang['useemailcontact']),
								4 => array('delete', $forums->lang['deleteallpms'])
								), '', "onchange='js_jump({$user['userid']});'") . "<input type='button' class='button' value='{$forums->lang['ok']}' onclick='js_jump({$user['userid']});' />"));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems']);
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function pmfolderstats()
	{
		global $forums, $DB, $_INPUT;
		$userid = intval($_INPUT['u']);
		$user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE id = {$userid}");
		if (!$user['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$user['pmfolders'] = unserialize($user['pmfolders']);
		if (count($user['pmfolders']) < 2)
		{
			$user['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['inbox']));
		}
		$folders = array();
		$pms = $DB->query("SELECT COUNT(*) AS messages, folderid FROM " . TABLE_PREFIX . "pm WHERE userid = {$user['id']} GROUP BY folderid");
		if (!$DB->num_rows($pms))
		{
			$forums->admin->print_cp_error($forums->lang['nomatchresult']);
		}
		while ($pm = $DB->fetch_array($pms))
		{
			$pmtotal += $pm['messages'];
			$folders[$user['pmfolders'][$pm['folderid']]['foldername']] = $pm['messages'];
		}
		$pagetitle = $forums->lang['pmtools'];
		$detail = $forums->lang['pmtoolsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'doform'), 2 => array('u', $user['id'])), "", "", 'user.php');

		$forums->lang['userpmstats'] = sprintf($forums->lang['userpmstats'], $user['name']);
		$forums->admin->print_table_start($forums->lang['userpmstats']);

		$forums->admin->columns[] = array("&nbsp;", "40%");
		$forums->admin->columns[] = array("&nbsp;", "60%");

		foreach($folders AS $foldername => $messages)
		{
			$forums->admin->print_cells_row(array($foldername, $messages));
		}

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['userallpms']}</strong>", "<strong>{$pmtotal}</strong>"));

		$forums->admin->print_form_end($forums->lang['edituserprofile'], '', $forums->admin->print_button($forums->lang['deleteallpms'], "usertools.php?{$forums->js_sessionurl}do=removepms&u={$user['id']}"));

		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function removepms()
	{
		global $DB, $forums, $bbuserinfo, $_INPUT, $bboptions;
		$userid = intval($_INPUT['u']);
		$user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE id = {$userid}");
		if (!$user['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($_INPUT['update'])
		{
			$pmtextids = array();
			$pmtexts = $DB->query("SELECT pmtextid FROM " . TABLE_PREFIX . "pmtext WHERE fromuserid = {$user['id']}");
			if ($DB->num_rows($pmtexts))
			{
				while ($pmtext = $DB->fetch_array($pmtexts))
				{
					$pmtextids[] = $pmtext['pmtextid'];
				}
			}
			else
			{
				$forums->admin->print_cp_error($forums->lang['nomatchresult']);
			}

			$pmids = array();
			$pmarray = array();
			$pms = $DB->query("SELECT p.*, u.name FROM " . TABLE_PREFIX . "pm p LEFT JOIN " . TABLE_PREFIX . "user u ON (p.userid=u.id) WHERE p.messageid IN (" . implode(",", $pmtextids) . ")");
			while ($pm = $DB->fetch_array($pms))
			{
				$pmids[] = $pm['pmid'];
				$pmarray[ $pm['username'] ][] = $pm;
			}
			$DB->free_result($pms);
			$users = array();
			foreach($pmarray AS $username => $pms)
			{
				$pmunread = 0;
				foreach($pms AS $pm)
				{
					if ($pm['messageread'] == 0)
					{
						$pmunread ++;
					}
				}
				$pmtotal = count($pms);
				$users[ $pm['userid'] ] = array('pmtotal' => $pmtotal, 'pmunread' => $pmunread);
			}
			if (count($pmids))
			{
				$attachments = $DB->query("SELECT location, thumblocation, attachpath FROM " . TABLE_PREFIX . "attachment WHERE pmid IN (" . implode(",", $pmids) . ")");
				if ($attachment = $DB->fetch_array($attachments))
				{
					if ($attachment['location'])
					{
						@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['location']);
					}
					if ($attachment['thumblocation'])
					{
						@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['thumblocation']);
					}
				}
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid IN (" . implode(",", $pmids) . ")");
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE pmid IN (" . implode(",", $pmids) . ")");
			}

			if (!empty($users))
			{
				$userids = implode(', ', array_keys($users));
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmtotal = 0, pmunread = 0 WHERE id IN ($userids)");
			}
			$forums->admin->redirect("usertools.php?do=pmstats", $forums->lang['manageuser'], $forums->lang['pmdeleted']);
		}
		else
		{
			$pagetitle = $forums->lang['deleteallpms'] . " - " . $user['name'];
			$forums->admin->nav[] = array('', $forums->lang['deleteallpms']);
			$forums->admin->print_cp_header($pagetitle);
			$forums->admin->columns[] = array("&nbsp;" , "60%");
			$forums->admin->columns[] = array("&nbsp;" , "40%");
			$forums->admin->print_form_header(array(1 => array('do', 'removepms'), 2 => array('u', $user['id']), 3 => array('update', 1)));
			$forums->admin->print_table_start($forums->lang['deleteallpms'] . " - " . $user['name']);
			$forums->lang['areyousuredeletepms'] = sprintf($forums->lang['areyousuredeletepms'], $user['name']);
			$forums->admin->print_cells_single_row($forums->lang['areyousuredeletepms'], "center");
			$forums->admin->print_form_submit($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}

	function email_form()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['mailtools'];
		$detail = $forums->lang['mailtoolsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$user_group = array(0 => array('', $forums->lang['anyusergroup']));
		$DB->query("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup ORDER BY grouptitle");
		while ($r = $DB->fetch_array())
		{
			$user_group[] = array($r['usergroupid'] , $forums->lang[ $r['grouptitle'] ]);
		}

		$maillist = "none";
		$sendmail = "";
		$check1 = "";
		$check2 = " checked='checked'";

		if ($_INPUT['glist'])
		{
			$maillist = "";
			$sendmail = "none";
			$check1 = " checked='checked'";
			$check2 = "";
		}

		$forums->admin->print_form_header(array(1 => array('do' , 'sendmail'),));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");

		$forums->admin->print_table_start($forums->lang['createmaillist']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['docreatemaillist']}</strong>", "<input name='glist' type='radio' value='1' onClick=\"maillist.style.display='';sendmail.style.display='none';\"{$check1} /> {$forums->lang['yes']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name='glist' type='radio' value='0' onClick=\"maillist.style.display='none';sendmail.style.display='';\"{$check2} /> {$forums->lang['no']}"));
		$forums->admin->print_table_footer();

		echo "<div  id=\"maillist\" style=\"display:{$maillist}\">\n";
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['listseparator']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['listseparator']}</strong><div class='description'>{$forums->lang['listseparatordesc']}</div>", $forums->admin->print_input_row("separate", isset($_INPUT['separate']) ? $_INPUT['separate'] : " ")));
		$forums->admin->print_table_footer();
		echo "</div>\n";

		echo "<div  id=\"sendmail\" style=\"display:{$sendmail}\">\n";
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['mailtools']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['onlytestmail']}</strong>" ,
				$forums->admin->print_yes_no_row("onlytest", 0)));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['operateuser']}</strong>", $forums->admin->print_input_row("operateuser", $_INPUT['operateuser'] ? intval($_INPUT['operateuser']) : 500)));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['mailtitle']}</strong>", $forums->admin->print_input_row("email_title", $_INPUT['email_title'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['mailcontents']}</strong><div class='description'>{$forums->lang['mailcontentsdesc']}</div>",
				$forums->admin->print_textarea_row("email_contents", $_INPUT['email_contents'], "", 10)
				), "", 'top');

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['pausesend']}</strong>" ,
				$forums->admin->print_yes_no_row("pause", 0)));

		$forums->admin->print_table_footer();
		echo "</div>\n";

		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['searchmatch']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['username'] . "</strong><div class='description'>" . $forums->lang['usernamedesc'] . "</div>",
				$forums->admin->print_input_select_row('namewhere', array(0 => array('begin', $forums->lang['namebegin']),
						1 => array('is', $forums->lang['exactmatch']),
						2 => array('contains', $forums->lang['nameinclude']),
						3 => array('end', $forums->lang['nameend'])
						), $_INPUT['namewhere']
					)
				 . '&nbsp;' . $forums->admin->print_input_row("name", $_INPUT['name'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergroup'] . "</strong>" ,
				$forums->admin->print_input_select_row("usergroupid", $user_group, $_INPUT['usergroupid'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['membergroup'] . "</strong>" ,
				$forums->admin->print_input_select_row("membergroupid", $user_group, $_INPUT['membergroupid'])
				));
		$forums->admin->print_cells_single_row($forums->lang['optionalsearchparts'], "left", "pformstrip");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['emailinclude'] . "</strong>", $forums->admin->print_input_row("email", $_INPUT['email'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['tempbanuser'] . "</strong>",
				$forums->admin->print_input_select_row("suspended", array(0 => array('0', $forums->lang['any']), 1 => array('yes', $forums->lang['yes']), 2 => array('no', $forums->lang['no'])), $_INPUT['suspended'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['ipaddressinclude'] . "</strong>", $forums->admin->print_input_row("host", $_INPUT['host'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['wsinclude'] . "</strong>", $forums->admin->print_input_row("website", $_INPUT['website'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['qqinclude'] . "</strong>", $forums->admin->print_input_row("qq", $_INPUT['qq'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['ucinclude'] . "</strong>", $forums->admin->print_input_row("uc", $_INPUT['uc'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['popoinclude'] . "</strong>", $forums->admin->print_input_row("popo", $_INPUT['popo'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['aiminclude'] . "</strong>", $forums->admin->print_input_row("aim", $_INPUT['aim'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['icqinclude'] . "</strong>", $forums->admin->print_input_row("icq", $_INPUT['icq'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['msninclude'] . "</strong>", $forums->admin->print_input_row("msn", $_INPUT['msn'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['yahooinclude'] . "</strong>", $forums->admin->print_input_row("yahoo", $_INPUT['yahoo'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['signatureinclude'] . "</strong>", $forums->admin->print_input_row("signature", $_INPUT['signature'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['postslessthan'] . "</strong>", $forums->admin->print_input_row("posts", $_INPUT['posts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['regdateinter'] . " (YYYY-MM-DD)</strong><div class='description'>" . $forums->lang['regdateinterdesc'] . "</div>",
				$forums->lang['from'] . ' ' . $forums->admin->print_input_row("registered_first", $_INPUT['registered_first'], '', '', 10) . ' ' . $forums->lang['to'] . ' ' . $forums->admin->print_input_row("registered_last", $_INPUT['registered_last'], '', '', 10)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['lastpostinter'] . " (YYYY-MM-DD)</strong><div class='description'>" . $forums->lang['lastpostinterdesc'] . "</div>" ,
				$forums->lang['from'] . ' ' . $forums->admin->print_input_row("lastpost_first", $_INPUT['lastpost_first'], '', '', 10) . ' ' . $forums->lang['to'] . ' ' . $forums->admin->print_input_row("lastpost_last", $_INPUT['lastpost_last'], '', '', 10)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['lastactivityinter'] . " (YYYY-MM-DD)</strong><div class='description'>" . $forums->lang['lastactivityinterdesc'] . "</div>" ,
				$forums->lang['from'] . ' ' . $forums->admin->print_input_row("lastactivity_first", $_INPUT['lastactivity_first'], '', '', 10) . ' ' . $forums->lang['to'] . ' ' . $forums->admin->print_input_row("lastactivity_last", $_INPUT['lastactivity_last'], '', '', 10)
				));
		$forums->admin->print_form_submit($forums->lang['dosendmail']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function sendmail()
	{
		global $forums, $DB, $_INPUT;
		$first = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$_INPUT['operateuser'] = intval($_INPUT['operateuser']);

		if ($_INPUT['glist'])
		{
			if (!$_POST['separate'])
			{
				$forums->main_msg = $forums->lang['inputseparator'];
				$this->email_form();
			}
			$separate = str_replace("\n", "<br />", convert_andstr($_POST['separate']));
		}
		else
		{
			if ($_POST['email_title'] || $_POST['email_contents'])
			{
				$email_title = trim(convert_andstr($_POST['email_title']));
				$email_contents = trim($_POST['email_contents']);
				if ($email_title == '' OR $email_contents == '')
				{
					$forums->main_msg = $forums->lang['inputwholecontent'];
					$this->email_form();
				}
				if (is_writeable(ROOT_PATH . 'cache'))
				{
					if (file_exists(ROOT_PATH . "cache/send_mail.txt"))
					{
						if (! is_writeable(ROOT_PATH . "cache/send_mail.txt"))
						{
							$forums->main_msg = $forums->lang['cannotwritesendmail'];
							$this->email_form();
						}
					}
				}
				else
				{
					$forums->main_msg = $forums->lang['cannotwritesendmail'];
					$this->email_form();
				}
				require_once(ROOT_PATH . "includes/functions_codeparse.php");
				$parse = new functions_codeparse();
				$email_contents = $parse->convert(array(
					'text' => utf8_htmlspecialchars($email_contents),
					'allowsmilies' => 1,
					'allowcode' => 1,
				));
				require_once(ROOT_PATH . 'includes/class_textparse.php');
				$email_contents = textparse::convert_text($email_contents, true);
				if ($fp = fopen(ROOT_PATH . "cache/send_mail.txt", 'wb'))
				{
					$content = $email_title . "\n";
					$content .= $email_contents;
					@flock($fp, LOCK_EX);
					fwrite($fp, $content);
					fclose($fp);
					@chmod(ROOT_PATH . "cache/send_mail.txt", 0777);
				}
			}
			else
			{
				$sendemail = @file_get_contents(ROOT_PATH . "cache/send_mail.txt");
				if (!$sendemail)
				{
					$forums->main_msg = $forums->lang['cannotfindsendmail'];
					$this->email_form();
				}
				else
				{
					list($email_title, $email_contents) = explode("\n", $sendemail);
				}
			}
		}
		require_once(ROOT_PATH . 'includes/adminfunctions_importers.php');
		$lib = new adminfunctions_importers();

		$page_query = "";
		$un_all = "";
		$query = array();
		$date_keys = array('registered_first', 'registered_last', 'lastpost_first', 'lastpost_last', 'lastactivity_first', 'lastactivity_last');
		foreach(array('name', 'email', 'host', 'website', 'qq', 'uc', 'popo', 'aim', 'icq', 'yahoo', 'msn', 'signature', 'posts', 'suspended', 'registered_first', 'registered_last', 'lastpost_first', 'lastpost_last', 'lastactivity_first', 'lastactivity_last', 'usergroupid', 'membergroupid') AS $bit)
		{
			$_INPUT[ $bit ] = rawurldecode(trim($_INPUT[ $bit ]));
			$page_query .= '&amp;' . $bit . '=' . urlencode($_INPUT[ $bit ]);
			if ($_INPUT[ $bit ])
			{
				if (in_array($bit, $date_keys))
				{
					list($year, $month, $day) = explode('-', $_INPUT[ $bit ]);
					if (! checkdate($month, $day, $year))
					{
						$forums->lang['inputdateerror'] = sprintf($forums->lang['inputdateerror'], $year, $month, $day);
						$forums->main_msg = $forums->lang['inputdateerror'];
						$this->email_form();
					}
					$time_int = $forums->func->mk_time(0, 0 , 0, $month, $day, $year);
					$tmp_bit = str_replace('_first', '', $bit);
					$tmp_bit = str_replace('_last', '', $tmp_bit);
					$tmp_bit = str_replace('registered', 'joindate', $tmp_bit);
					if (strstr($bit, '_first'))
					{
						$query[] = 'u.' . $tmp_bit . ' > ' . $time_int;
					}
					else
					{
						$query[] = 'u.' . $tmp_bit . ' < ' . $time_int;
					}
				}
				else if ($bit == 'usergroupid')
				{
					if ($_INPUT['usergroupid'] != '')
					{
						$query[] = "u.usergroupid=" . $_INPUT['usergroupid'];
					}
				}
				else if ($bit == 'membergroupid')
				{
					if ($_INPUT['membergroupid'] != '')
					{
						$query[] = "(u.membergroupids LIKE ('" . $_INPUT['membergroupid'] . ",%') OR u.membergroupids LIKE ('%," . $_INPUT['membergroupid'] . "') OR u.membergroupids LIKE ('%," . $_INPUT['membergroupid'] . ",%') OR u.membergroupids =" . $_INPUT['membergroupid'] . ")";
					}
				}
				else if ($bit == 'posts')
				{
					$query[] = "u.posts <=" . $_INPUT[$bit];
				}
				else if ($bit == 'suspended')
				{
					if ($_INPUT[$bit] == 'yes')
					{
						$query[] = "u.liftban IS NOT NULL OR u.liftban != ''";
					}
					else if ($_INPUT[$bit] == 'no')
					{
						$query[] = "u.liftban IS NULL OR u.liftban = ''";
					}
				}
				else if ($bit == 'name')
				{
					$start_bit = '%';
					$end_bit = '%';
					if ($_INPUT['namewhere'] == 'begin')
					{
						$start_bit = '';
					}
					else if ($_INPUT['namewhere'] == 'end')
					{
						$end_bit = '';
					}
					else if ($_INPUT['namewhere'] == 'is')
					{
						$end_bit = '';
						$start_bit = '';
					}
					$name = "LOWER(u.name) LIKE concat('" . $start_bit . "','" . strtolower($_INPUT[$bit]) . "','" . $end_bit . "') OR u.name LIKE concat('" . $start_bit . "','" . $_INPUT[$bit] . "','" . $end_bit . "')";
					$query[] = $name;
				}
				else
				{
					$query[] = "u." . $bit . " LIKE '%" . $_INPUT[$bit] . "%'";
				}
			}
		}
		if (count($query))
		{
			$where = ' WHERE ' . implode(" AND ", $query);
		}
		if ($_INPUT['operateuser'])
		{
			$limit = " LIMIT {$first},{$_INPUT['operateuser']}";
		}
		$DB->query("SELECT COUNT(*) as count FROM " . TABLE_PREFIX . "user u " . $where . "");
		$count = $DB->fetch_array();
		if ($count['count'] < 1)
		{
			$forums->main_msg = $forums->lang['nomatchresult'];
			$this->email_form();
		}
		$page_query .= '&amp;namewhere=' . $_INPUT['namewhere'] . '&amp;gotcount=' . $count['count'];
		$pagetitle = $forums->lang['usersearchresult'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$pages = $forums->func->build_pagelinks(array('totalpages' => $count['count'],
				'perpage' => 25,
				'curpage' => $first,
				'pagelink' => "user.php?{$forums->sessionurl}do={$_INPUT['do']}" . $page_query,
				));
		$mailcount = 0;
		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();
		$useactivation = false;
		if (preg_match("/{(activateid|activatelink)}/i", $email_contents))
		{
			$useactivation = true;
		}
		$users = $DB->query("SELECT u.email, u.emailcharset, u.name, u.id, u.usergroupid, ua.useractivationid FROM " . TABLE_PREFIX . "user u LEFT JOIN " . TABLE_PREFIX . "useractivation ua ON (ua.userid = u.id AND ua.type = 2) " . $where . " ORDER BY name$limit");
		while ($user = $DB->fetch_array($users))
		{
			if ($_INPUT['glist'])
			{
				$lib->echo_flush($user['email'] . $separate . "\n");
			}
			else
			{
				$mailcount++;
				$sendingmaillist = sprintf($forums->lang['sendingmaillist'], $user['name']);
				$lib->echo_flush("<p>&raquo; $sendingmaillist {$user['email']} ....\n");
				if (!$_INPUT['onlytest'])
				{
					$msg = trim($email_contents);
					if ($useactivation == true AND $user['usergroupid'] == 1)
					{
						$activationid = md5($forums->func->make_password() . TIMENOW);
						if (empty($user['useractivationid']))
						{
							$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "useractivation
								(useractivationid, userid, usergroupid, tempgroup, dateline, type)
								VALUES
								('" . $activationid . "', " . $user['id'] . ", 3, 1, " . TIMENOW . ", 2)"
								);
						}
						else
						{
							$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "useractivation SET dateline = " . TIMENOW . ", useractivationid ='" . $activationid . "' WHERE useractivationid = '" . $user['useractivationid'] . "'");
						}
						$activatelink = $bboptions['bburl'] . "/register.php?do=validate&amp;u=" . urlencode($user['id']) . "&amp;a=" . urlencode($activationid);
						$msg = str_replace(array("{activateid}", "{activatelink}"), array($activationid, $activatelink), $msg);
					}
					$msg = str_replace(array("{username}", "{userid}", "{email}"), array($user['name'], $user['id'], $user['email']), $msg);
					$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
					$this->email->build_message($msg);
					$this->email->subject = $email_title;
					$this->email->to = $user['email'];
					$this->email->send_mail();
				}
				$lib->echo_flush("{$forums->lang['done']}</p>\n");
			}
		}
		if (($mailcount < $_INPUT['operateuser'] AND $_INPUT['operateuser'] != 0) OR ($_INPUT['operateuser'] == 0))
		{
			@unlink(ROOT_PATH . "cache/send_mail.txt");
			$lib->echo_flush("<hr><strong><a href='usertools.php?" . $forums->sessionurl . "'>{$forums->lang['finishsending']}</a></strong>\n");
		}
		else
		{
			$pp = $first + $mailcount;
			$js_query = str_replace("&amp;", "&", $page_query);
			echo (!$_INPUT['pause'] ? "<script language='javascript'>window.location='usertools.php?" . $forums->js_sessionurl . "do=sendmail&pp=$pp" . $js_query . "';</script>\b" : "");
			$lib->echo_flush("<hr><strong>&raquo; <a href='usertools.php?" . $forums->sessionurl . "do=sendmail&amp;pp=$pp" . $page_query . "'>{$forums->lang['redirectsending']}</a> &laquo;</strong>\n");
		}
	}
}

$output = new usertools();
$output->show();

?>