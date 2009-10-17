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
# $Id: pm.php 189 2007-09-26 16:09:10Z sancho $
# **************************************************************************#
define('THIS_SCRIPT', 'private');
require_once('./global.php');

class newprivate
{
	var $folderid = '';
	var $canupload = 0;
	var $user = array();
	var $pmselect = '';
	var $userid = 0;
	var $getpmid = 0;
	var $message = '';

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('usercp');
		$forums->func->load_lang('private');
		if (! $bbuserinfo['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['notlogin']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$this->folderid = intval($_INPUT['folderid']);
		if (! $bbuserinfo['pmquota'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotusepm']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		if (! $bbuserinfo['usepm'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['pmclosed']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$this->lib = new functions_private();

		$bbuserinfo['pmfolders'] = unserialize($bbuserinfo['pmfolders']);
		if (count($bbuserinfo['pmfolders']) < 2)
		{
			$bbuserinfo['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['inbox']));
		}
		$forums->lang['pmcenter'] = convert($forums->lang['pmcenter']);
		$forums->lang['sendpm'] = convert($forums->lang['sendpm']);
		$this->posthash = $_INPUT['posthash'] ? trim($_INPUT['posthash']) : md5(microtime());

		switch ($_INPUT['do'])
		{
			case 'editfolders':
				$this->editfolders();
				break;
			case 'savefolders':
				$this->savefolders();
				break;
			case 'empty':
				$this->emptyfolders();
				break;
			case 'doempty':
				$this->doempty();
				break;
			case 'buddy':
				$this->buddylist();
				break;
			case 'adduser':
				$this->adduser();
				break;
			case 'deleteuser':
				$this->deleteuser();
				break;
			case 'edituser':
				$this->edituser();
				break;
			case 'douseredit':
				$this->douseredit();
				break;
			case 'showtrack':
				$this->showtracking();
				break;
			case 'endtracking':
				$this->endtracking();
				break;
			case 'deltracked':
				$this->deltracked();
				break;
			case 'managepm':
				$this->managepm();
				break;

			case 'ignorepm':
				$this->ignorepm();
				break;
			case 'del':
				$this->pmdelete();
				break;
			case 'newpm':
			case 'reply':
				$this->newpm();
				break;
			case 'send':
				$this->sendpm();
				break;
			case 'show':
				$this->showpm();
				break;
			case 'list':
				$this->pmlist();
				break;
			default:
				$this->pmhome();
				break;
		}
	}

	function ignorepm()
	{
		global $forums, $DB, $bbuserinfo;
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		redirect("index.php{$forums->sessionurl}");
	}

	function pmhome()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$folder_links = "";
		foreach($bbuserinfo['pmfolders'] AS $id => $data)
		{
			if ($data['pmcount'])
			{
				$data['foldername'] .= " ({$data['pmcount']})";
			}
			$folder_links .= "<img src='images/dot.gif' alt='-' /><a href='pm.php{$forums->sessionurl}do=list&amp;folderid=" . $id . "'>" . $data['foldername'] . "</a><br />";
		}
		$folder_links = convert($folder_links);
		include $forums->func->load_template('wap_pm_home');
		exit;
	}

	function pmlist()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$sortby = 'dateline DESC';
		$folderid = $this->folderid;

		$firstpost = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$foldername = "<a href='pm.php{$forums->sessionurl}do=list&amp;folderid={$folderid}'>" . convert($bbuserinfo['pmfolders'][$this->folderid]['foldername']) . "</a>";
		if ($this->folderid == '-1')
		{
			$pms = $DB->query("SELECT u.name as fromusername, p.title, p.pmid
						 FROM " . TABLE_PREFIX . "pm p
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( p.touserid=u.id )
						WHERE p.userid=" . $bbuserinfo['id'] . " AND p.folderid='-1'
						ORDER BY dateline DESC LIMIT " . $firstpost . ", 8");
		}
		else
		{
			$pms = $DB->query("SELECT p.title, p.pmid,p.dateline,u.name as fromusername
						 FROM " . TABLE_PREFIX . "pm p
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( p.fromuserid=u.id )
						WHERE p.userid='" . $bbuserinfo['id'] . "' AND p.folderid='" . $this->folderid . "' AND p.touserid='" . $bbuserinfo['id'] . "'
						ORDER BY dateline DESC LIMIT " . $firstpost . ", 8");
		}
		$contents = "";
		$i = 0;
		if ($DB->num_rows($pms))
		{
			while ($pm = $DB->fetch_array($pms))
			{
				++$i;
				$contents .= "<p>";
				$contents .= "<a href='pm.php{$forums->sessionurl}do=show&amp;id={$pm['pmid']}&amp;folderid={$folderid}'>" . strip_tags($pm['title']) . "</a><br />";
				$contents .= $pm['fromusername'] . "<br />";
				$contents .= $forums->func->get_date($pm['dateline'] , 2);
				$contents .= "</p>\n";
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
			$contents .= "<p>{$forums->lang['nonewpm']}</p>\n";
		}
		$contents = convert($contents);
		$forums->lang['prevlink'] = $prevpage ? convert($forums->lang['prevlink']) : '';
		$forums->lang['nextlink'] = $nextpage ? convert($forums->lang['nextlink']) : '';
		if ($this->folderid == 0 AND $bbuserinfo['pmunread'] > 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		}
		if ($prevpage OR $nextpage)
		{
			$show['p1'] = true;
		}
		include $forums->func->load_template('wap_pm_list');
		exit;
	}

	function showpm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$_INPUT['id'] = intval($_INPUT['id']);
		$this->offset = intval($_INPUT['offset']);
		if (! $_INPUT['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotfindpm']);
			include $forums->func->load_template('wap_info');
			exit;
		}

		require_once(ROOT_PATH . 'includes/class_textparse.php');
		require_once(ROOT_PATH . "wap/convert.php");
		$this->con = new convert();

		$DB->query("SELECT u.name AS username,
				p.*, pt.*
				FROM " . TABLE_PREFIX . "pm p
				 LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON (p.messageid=pt.pmtextid)
				 LEFT JOIN " . TABLE_PREFIX . "user u ON (p.fromuserid=u.id)
				WHERE p.pmid='" . $_INPUT['id'] . "'");
		if ($pm = $DB->fetch_array())
		{
			if ($pm['userid'] != $bbuserinfo['id'] && $pm['usergroupid'] != -1 && !preg_match("/," . $bbuserinfo['usergroupid'] . ",/i", "," . $pm['usergroupid'] . ","))
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['cannotfindpm']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		else
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotfindpm']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		if ($bbuserinfo['pmunread'] > 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		}
		if ($pm['pmread'] < 1)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "pm SET pmread=1, pmreadtime=" . TIMENOW . " WHERE pmid=" . $_INPUT['id'] . "");
		}
		$dateline = $forums->func->get_date($pm['dateline'], 2);
		$username = convert($pm['username']);
		$pm_title = convert($pm['title']);

		$pm['message'] = utf8_unhtmlspecialchars($pm['message']);
		$pm['message'] = textparse::convert_text($pm['message'], $bboptions['pmallowhtml']);
		$pm['message'] = $this->con->convert_text($pm['message']);

		if ($this->offset)
		{
			$pm['message'] = substr($pm['message'], $this->offset);
		}

		$postlen = strlen($pm['message']);
		$leftlen = 400 - $this->contents_len;
		$this->contents_len = $this->contents_len + $postlen;
		if ($this->contents_len > 400)
		{
			$this->endoutput = true;
			$pm['message'] = $this->con->fetch_trimmed_title($pm['message'], $leftlen);
			$offset = $this->offset + $this->con->post_set;
			$urllink = "pm.php{$forums->sessionurl}do=show&amp;id={$_INPUT['id']}&amp;folderid={$this->folderid}&amp;offset={$offset}";
		}
		$message = convert($pm['message']) . "\n";

		$showpage = ($this->endoutput) ? true : false;
		if ($showpage)
		{
			$nextpage = "\n<p><a href='{$urllink}'>" . convert($forums->lang['nextlink']) . "</a></p>";
		}

		$otherlink = "<a href='pm.php{$forums->sessionurl}do=reply&amp;id={$_INPUT['id']}&amp;u={$pm['fromuserid']}' title='{$forums->lang['re']}'>{$forums->lang['re']}</a><br />";
		$otherlink .= "<a href='pm.php{$forums->sessionurl}do=del&amp;id={$_INPUT['id']}&amp;folderid={$this->folderid}' title='{$forums->lang['delete']}'>{$forums->lang['delete']}</a><br />";
		$otherlink = convert($otherlink);
		$otherlink .= "<a href='pm.php{$forums->sessionurl}' title='{$forums->lang['pmcenter']}'>{$forums->lang['pmcenter']}</a>\n";

		include $forums->func->load_template('wap_pm_show');
		exit;
	}

	function pmdelete()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$_INPUT['id'] = intval($_INPUT['id']);
		if (! $_INPUT['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['nodelpms']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$this->delete_messages($_INPUT['id'], $bbuserinfo['id']);
		$this->lib->rebuild_foldercount($bbuserinfo['id'],
			$bbuserinfo['pmfolders'],
			$this->folderid,
			$bbuserinfo['folders'][ $this->folderid ]['count'] - 1,
			'save',
			",pmtotal=pmtotal-1"
			);
		redirect("pm.php{$forums->sessionurl}do=list&amp;folderid=" . $this->folderid . "");
	}

	function delete_messages($ids, $userid, $query = "")
	{
		global $DB, $forums, $bboptions;
		if (! $query)
		{
			$query = "p.userid=$userid";
		}
		$id_string = "";
		if (is_array($ids))
		{
			if (! count($ids))
			{
				return;
			}
			$id_string = 'IN (' . implode(",", $ids) . ')';
		}
		else
		{
			if (! $ids)
			{
				return;
			}
			$id_string = '=' . $ids;
		}
		$pms = $DB->query("SELECT p.pmid, p.touserid, p.messageid, p.usergroupid, u.pmtotal, u.pmunread
										FROM " . TABLE_PREFIX . "pm p
										LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id = p.touserid)
										WHERE " . $query . " AND p.pmid " . $id_string . "");
		$final_ids = array();
		$final_pms = array();
		while ($i = $DB->fetch_array($pms))
		{
			if ($i['usergroupid'] != 0) continue;
			$extra = "";
			if ($i['pmtotal'] > 0)
			{
				$extra .= ",pmtotal=pmtotal-1";
			}
			if ($i['pmunread'] > 0)
			{
				$extra .= ",pmunread=pmunread-1";
			}
			$this->lib->rebuild_foldercount($i['touserid'], "", '0', '-1', 'save', $extra);
			$final_ids[ $i['pmid'] ] = $i['messageid'];
			$final_pms[] = $i['pmid'];
		}
		if (count($final_pms))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid IN (" . implode(',', $final_pms) . ")");
		}
		if (count($final_ids))
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "pmtext SET deletedcount=deletedcount+1 WHERE pmtextid IN (" . implode(',', $final_ids) . ")");
		}
		$deleted_ids = array();
		$attachmentids = array();
		$DB->query("SELECT pmtextid FROM " . TABLE_PREFIX . "pmtext WHERE deletedcount >= savedcount");
		while ($r = $DB->fetch_array())
		{
			$deleted_ids[] = $r['pmtextid'];
		}
		if (count($deleted_ids))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pmtext WHERE pmtextid IN (" . implode(',', $deleted_ids) . ")");
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE pmid IN (" . implode(',', $deleted_ids) . ")");
			while ($a = $DB->fetch_array())
			{
				$attachmentids[] = $a['attachmentid'];
				if ($a['location'])
				{
					@unlink($bboptions['uploadfolder'] . "/" . $a['attachpath'] . "/" . $a['location']);
				}
				if ($a['thumblocation'])
				{
					@unlink($bboptions['uploadfolder'] . "/" . $a['attachpath'] . "/" . $a['thumblocation']);
				}
			}
			if (count($attachmentids))
			{
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN (" . implode(',', $attachmentids) . ")");
			}
		}
	}

	function newpm($errors = '')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('post');
		$posthash = $this->posthash;
		$userid = intval($_INPUT['u']);
		$getpmid = intval($_INPUT['id']);
		if ($errors)
		{
			$errors = convert($errors);
		}
		if ($userid)
		{
			$user = $DB->query_first("SELECT name, id FROM " . TABLE_PREFIX . "user WHERE id='" . $userid . "'");
			if ($user['id'])
			{
				$username = $user['name'];
			}
		}
		else
		{
			$username = $_INPUT['username'];
		}
		$showuser = true;
		if ($getpmid)
		{
			$pm = $DB->query_first("SELECT u.id,u.name, p.*
				FROM " . TABLE_PREFIX . "pm p
				 LEFT JOIN " . TABLE_PREFIX . "user u ON (p.touserid=u.id)
				WHERE p.pmid=" . $getpmid . " AND p.userid=" . $bbuserinfo['id'] . "");
			$showuser = false;
			if ($pm['title'])
			{
				$title = $forums->lang['re'] . ":" . $pm['title'];
				$title = preg_replace("/^(?:" . $forums->lang['re'] . "\:){1,}/i", $forums->lang['re'] . ":", $title);
				$_POST['title'] = convert($title);
			}
		}
		$forums->lang['username'] = convert($forums->lang['username']);
		$forums->lang['title'] = convert($forums->lang['title']);
		$forums->lang['content'] = convert($forums->lang['content']);
		$forums->lang['savecopy'] = convert($forums->lang['savecopy']);
		$forums->lang['yes'] = convert($forums->lang['yes']);
		$forums->lang['no'] = convert($forums->lang['no']);
		include $forums->func->load_template('wap_pm_post');
		exit;
	}

	function sendpm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;

		require_once(ROOT_PATH . 'includes/functions_post.php');

		$title = trim(convert($_INPUT['title']));
		$title = $this->lib->postlib->parser->censoredwords($title);
		$_POST['post'] = $this->lib->postlib->parser->censoredwords($_POST['post']);
		$message = $this->lib->postlib->parser->convert(array(
			'text' => utf8_htmlspecialchars(convert($_POST['post'])),
			'allowsmilies' => $_INPUT['allowsmile'],
			'allowcode' => $bboptions['pmallowbbcode'],
		));
		if ($title == '' OR $message == '')
		{
			return $this->newpm($forums->lang['inputallform']);
		}
		if ($_INPUT['u'])
		{
			$user = $DB->query_first("SELECT name FROM " . TABLE_PREFIX . "user WHERE id=" . intval($_INPUT['u']) . "");
			$_INPUT['username'] = $user['name'];
		}
		else
		{
			$_INPUT['username'] = convert($_INPUT['username']);
		}
		if ($_INPUT['username'] == "")
		{
			return $this->newpm($forums->lang['selectusername']);
		}
		$savecopy = intval($_INPUT['savecopy']);
		$_INPUT['username'] = unclean_value($_INPUT['username']);
		$users = explode(';', $_INPUT['username']);
		$touser = array();
		foreach ($users as $val)
		{
			$val = trim($val);
			if ($val)
			{
				$touser[] = strtolower(clean_value($val));
			}
		}
		$touser = array_unique($touser);
		$usercounts = count($touser);
		if (($bbuserinfo['pmsendmax'] > 0 AND $usercounts > $bbuserinfo['pmsendmax']) OR (empty($bbuserinfo['pmsendmax']) AND $usercounts > 2))
		{
			return $this->newpm($forums->lang['toomanyusers'] . ': ' . $usercounts);
		}
		$total = $DB->query_first("SELECT COUNT(*) as pmtotal FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . "");
		if ($savecopy AND ($total['pmtotal'] + 1) > $bbuserinfo['pmquota'])
		{
			return $this->newpm($forums->lang['pmquotafull']);
		}
		$touserlist = array();
		foreach ($touser AS $username)
		{
			if ($username == '' OR strlen($username) > 60) continue;
			if (! $user = $DB->query_first("
						SELECT u.id, u.name, u.pmtotal, u.options, u.email, u.emailcharset, u.pmfolders, g.pmquota, p.id AS banid
						FROM " . TABLE_PREFIX . "user u
						LEFT JOIN " . TABLE_PREFIX . "usergroup g ON (u.usergroupid=g.usergroupid)
						LEFT JOIN " . TABLE_PREFIX . "pmuserlist p ON (u.id=p.userid AND allowpm=0)
						WHERE LOWER(u.name)='" . strtolower($username) . "'"))
			{
				$errors['user'] .= $username . ',';
				continue;
			}
			if (! $user['pmquota'] OR $user['pmtotal'] >= $user['pmquota'])
			{
				$errors['full'] .= $username . ',';
			}
			$forums->func->convert_bits_to_array($user, $user['options']);
			if ($user['banid'])
			{
				$errors['disable'] .= $username . ',';
			}
			if (!$user['usepm'])
			{
				$errors['nousepm'] .= $username . ',';
			}
			$touserlist[ $user['id'] ] = $user;
		}
		if (is_array($errors))
		{
			$showerrors = '';
			$forums->func->load_lang('global');
			if ($errors['user'])
			{
				$showerrors .= $forums->lang['_pmerrors1'] . ': ' . $errors['user'] . '<br />';
			}
			if ($errors['full'])
			{
				$showerrors .= $forums->lang['_pmerrors2'] . ': ' . $errors['full'] . '<br />';
			}
			if ($errors['disable'])
			{
				$showerrors .= $forums->lang['_pmerrors3'] . ': ' . $errors['disable'] . '<br />';
			}
			if ($errors['nousepm'])
			{
				$showerrors .= $forums->lang['_pmerrors4'] . ': ' . $errors['nousepm'] . '<br />';
			}
			return $this->newpm($showerrors);
		}
		$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pmtext
								(dateline, message, savedcount, posthash, fromuserid)
							VALUES
								(" . TIMENOW . ", '" . addslashes($message) . "', " . $usercounts . ", '" . $this->posthash . "', " . $bbuserinfo['id'] . ")"
			);
		$pmtextid = $DB->insert_id();
		foreach ($touserlist AS $userid => $to_user)
		{
			$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pm
									(messageid, dateline, title, fromuserid, touserid, folderid, tracking, attach, userid)
								VALUES
									(" . $pmtextid . ", " . TIMENOW . ", '" . addslashes($title) . "', " . $bbuserinfo['id'] . ", " . $to_user['id'] . ", 0, 0, 0, " . $to_user['id'] . ")"
				);
			$pmid = $DB->insert_id();
			$this->lib->rebuild_foldercount($to_user['id'], "", '0', '-1', 'save', ",pmtotal=pmtotal+1, pmunread=pmunread+1");
			if ($to_user['emailonpm'])
			{
				require_once (ROOT_PATH . "includes/functions_email.php");
				$this->email = new functions_email();
				$this->email->char_set = $to_user['emailcharset']?$to_user['emailcharset']:'GBK';
				$message = $this->email->fetch_email_pmnotify(array('username' => $to_user['name'],
						'sender' => $bbuserinfo['name'],
						'title' => $title,
						'link' => $bboptions['bburl'] . "/private.php?do=showpm&folderid=0&pmid=$pmid",
						));
				$this->email->build_message($message);
				$this->email->subject = $forums->lang['_newpm'];
				$this->email->to = $to_user['email'];
				$this->email->send_mail();
			}
		}
		if ($savecopy)
		{
			$this->lib->rebuild_foldercount($bbuserinfo['id'], "", '-1', '-1', 'save', ",pmtotal=pmtotal+1");
			$DB->insert(TABLE_PREFIX . 'pm', array(
				'messageid' => $pmtextid,
				'dateline' => TIMENOW,
				'title' => $title,
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $to_user['id'],
				'folderid' => '-1',
				'tracking' => 0,
				'attach' => 0,
				'userid' => $bbuserinfo['id'],
			));
		}
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		$this->credit->update_credit('sendpm', $bbuserinfo['id'], $bbuserinfo['usergroupid']);
		redirect("pm.php" . $forums->sessionurl);
	}
}

$output = new newprivate();
$output->show();

?>