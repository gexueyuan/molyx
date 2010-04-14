<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
class functions_private
{
	var $posthash = '';
	var $canupload = 0;

	function functions_private()
	{
		global $_INPUT, $bbuserinfo;
		require_once(ROOT_PATH . 'includes/functions_post.php');
		$this->postlib = new functions_post();
		$this->posthash = $_INPUT['posthash'] ? trim($_INPUT['posthash']) : md5(microtime());
		if ($bbuserinfo['attachlimit'] != -1 AND $bbuserinfo['canpmattach'])
		{
			$this->canupload = 1;
			$this->postlib->obj['form_extra'] = " enctype='multipart/form-data'";
			$this->postlib->obj['hidden_field'] = "<input type='hidden' name='MAX_FILE_SIZE' value='" . ($bbuserinfo['attachlimit'] * 1024) . "' />";
		}
		$this->postlib->canupload = $this->canupload;
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
	}

	function sendpm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['removeattachid'])
		{
			if ($_INPUT[ 'removeattach_' . $_INPUT['removeattachid'] ])
			{
				$this->postlib->remove_attachment(intval($_INPUT['removeattachid']), $this->posthash);
				return $this->newpm();
			}
		}
		$_INPUT['title'] = str_replace("　", ' ', $_INPUT['title']);
		$title = trim($_INPUT['title']);
		$title = $this->postlib->parser->censoredwords($title);

		$this->cookie_mxeditor = $this->cookie_mxeditor ? $this->cookie_mxeditor : $forums->func->get_cookie('mxeditor');
		if ($this->cookie_mxeditor)
		{
			$bbuserinfo['usewysiwyg'] = ($this->cookie_mxeditor == 'wysiwyg') ? 1 : 0;
		}
		else if ($bboptions['mxemode'])
		{
			$bbuserinfo['usewysiwyg'] = 1;
		}
		else
		{
			$bbuserinfo['usewysiwyg'] = 0;
		}
		$post = $bbuserinfo['usewysiwyg'] ? $_POST['post'] : utf8_htmlspecialchars($_POST['post']);
		$post = $this->postlib->parser->censoredwords($post);
		$message = $this->postlib->parser->convert(array('text' => $post,
			'allowsmilies' => $_INPUT['allowsmile'],
			'allowcode' => $bboptions['pmallowbbcode'],
		));
		if ($title == '' OR $message == '')
		{
			return $this->newpm($forums->lang['_inputallform']);
		}
		if ($_INPUT['username'] == '')
		{
			return $this->newpm($forums->lang['_selectusername']);
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
		if (!$_INPUT['noredirect'])
		{
			$this->credit->check_credit('sendpm', $bbuserinfo['usergroupid'], '', $usercounts);
		}
		if (($bbuserinfo['pmsendmax'] > 0 && $usercounts > $bbuserinfo['pmsendmax']) || (empty($bbuserinfo['pmsendmax']) AND $usercounts > 2))
		{
			return $this->newpm($forums->lang['_toomanyusers'] . ': ' . $usercounts);
		}
		$total = $DB->query_first("SELECT COUNT(*) as pmtotal FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . "");
		if ($savecopy && ($total['pmtotal'] + 1) > $bbuserinfo['pmquota'])
		{
			return $this->newpm($forums->lang['_pmquotafull']);
		}
		$touserlist = array();
		foreach ($touser AS $username)
		{
			if ($username == '' OR strlen($username) > 60)
			{
				$errors['lengtherror'] .= $username . ',';
				continue;
			}
			if (!$user = $DB->query_first("SELECT u.id, u.name, u.pmtotal, u.options, u.email, u.emailcharset, u.pmfolders, u.membergroupids, g.pmquota, p.id AS banid
				FROM " . TABLE_PREFIX . "user u
					LEFT JOIN " . TABLE_PREFIX . "usergroup g
						ON u.usergroupid=g.usergroupid
					LEFT JOIN " . TABLE_PREFIX . "pmuserlist p
						ON (u.id=p.userid AND p.contactid = {$bbuserinfo['id']} AND p.allowpm=0)
				WHERE LOWER(u.name)='" . strtolower($username) . "'
					OR u.name='" . $username . "'"))
			{
				$errors['user'] .= $username . ',';
				continue;
			}
			if ($user['membergroupids'])
			{
				$result = $DB->query('SELECT pmquota FROM ' . TABLE_PREFIX . 'usergroup WHERE usergroupid IN (' . $user['membergroupids'] . ')');
				while ($row = $DB->fetch_array($result))
				{
					$user['pmquota'] = ($user['pmquota'] > $row['pmquota']) ? $user['pmquota'] : $row['pmquota'];
				}
			}
			$forums->func->convert_bits_to_array($user, $user['options']);
			$deloldpmflag = 0;
			if (! $user['pmquota'] OR $user['pmtotal'] >= $user['pmquota'])
			{
				$getoldpm = $DB->query_first("SELECT pmid, messageid FROM " . TABLE_PREFIX . "pm WHERE userid = {$user['id']} ORDER BY dateline ASC");
				if ($user['pmover'] && !empty($getoldpm))
				{
					$DB->delete(TABLE_PREFIX . "pm", "pmid = {$getoldpm['pmid']}");
					$DB->delete(TABLE_PREFIX . "pmtext", "pmtextid = {$getoldpm['messageid']}");
					$DB->delete(TABLE_PREFIX . "attachment", "pmid = {$getoldpm['messageid']}");
					$this->rebuild_foldercount($user['id'], '', '0', '-2', 'save', ",pmtotal=pmtotal-1");
				}
				else
				{
					$errors['full'] .= $username . ',';
				}
			}
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
		if (is_array($errors) AND !$_INPUT['noredirect'])
		{
			$showerrors = '';
			if ($errors['lengtherror'])
			{
				$showerrors .= $forums->lang['_pmerrors0'] . ': ' . $errors['lengtherror'] . '<br />';
			}
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
		if ($savecopy)
		{
			$usercounts++;
		}
		$DB->insert(TABLE_PREFIX . 'pmtext', array(
			'dateline' => TIMENOW,
			'message' => $message,
			'savedcount' => $usercounts,
			'posthash' => $this->posthash,
			'fromuserid' => $bbuserinfo['id'],
		));
		$pmtextid = $DB->insert_id();
		$no_attachment = $this->postlib->attachment_complete(array($this->posthash), "", "", "", $pmtextid);
		foreach ($touserlist AS $userid => $to_user)
		{
			$DB->insert(TABLE_PREFIX . 'pm', array(
				'messageid' => $pmtextid,
				'dateline' => TIMENOW,
				'title' => $title,
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $to_user['id'],
				'folderid' => '0',
				'tracking' => intval($_INPUT['addtracking']),
				'attach' => intval($no_attachment),
				'userid' => $to_user['id'],
			));
			$pmid = $DB->insert_id();
			$this->rebuild_foldercount($to_user['id'], "", '0', '-1', 'save', ",pmtotal=pmtotal+1, pmunread=pmunread+1");
			if ($to_user['emailonpm'])
			{
				require_once (ROOT_PATH . "includes/functions_email.php");
				$this->email = new functions_email();
				$this->email->char_set = $to_user['emailcharset']?$to_user['emailcharset']:'GBK';
				$message = $this->email->fetch_email_pmnotify(array('username' => $to_user['name'],
						'sender' => $bbuserinfo['name'],
						'title' => $title,
						'link' => $bboptions['bburl'] . "/private.php?do=showpm&amp;folderid=0&amp;pmid=$pmid",
						));
				$this->email->build_message($message);
				$this->email->subject = $forums->lang['_newpm'];
				$this->email->to = $to_user['email'];
				$this->email->send_mail();
			}
		}
		if ($savecopy)
		{
			$this->rebuild_foldercount($bbuserinfo['id'], "", '-1', '-1', 'save', ",pmtotal=pmtotal+1");
			$DB->insert(TABLE_PREFIX . 'pm', array(
				'messageid' => $pmtextid,
				'dateline' => TIMENOW,
				'title' => $title,
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $to_user['id'],
				'folderid' => '-1',
				'tracking' => 0,
				'attach' => intval($no_attachment),
				'userid' => $bbuserinfo['id'],
			));
		}
		if (!$_INPUT['noredirect'])
		{
			if ($savecopy) $usercounts--;
			$this->credit->update_credit('sendpm', $bbuserinfo['id'], $bbuserinfo['usergroupid'], '', $usercounts);
			$forums->func->redirect_screen($forums->lang['_sendpmdone'], "private.php{$forums->sessionurl}do=list");
		}
	}

	function newpm($errors = '')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('post');
		$userid = intval($_INPUT['u']);
		$getpmid = intval($_INPUT['pmid']);
		$posthash = $this->posthash;
		$contact = $this->build_contact_list();
		$sendmax = $bbuserinfo['pmsendmax'] ? intval($bbuserinfo['pmsendmax']) : '1';
		if ($userid)
		{
			$user = $DB->query_first("SELECT name, id FROM " . TABLE_PREFIX . "user WHERE id='" . $userid . "'");
			if ($_INPUT['fwd'] != 1)
			{
				if ($user['id'])
				{
					$username = $user['name'];
				}
			}
		}
		else
		{
			$username = $_INPUT['username'];
		}
		$title = preg_replace("/'/", "&#39;", $_INPUT['title']);
		$content = utf8_htmlspecialchars($_INPUT['post']);
		if ($getpmid)
		{
			$pm = $DB->query_first("SELECT u.id,u.name, p.*, pt.*
				FROM " . TABLE_PREFIX . "pm p
				 LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON (p.messageid=pt.pmtextid)
				 LEFT JOIN " . TABLE_PREFIX . "user u ON (p.touserid=u.id)
				WHERE p.pmid=" . $getpmid . " AND p.userid=" . $bbuserinfo['id'] . "");
			$this->cookie_mxeditor = $forums->func->get_cookie('mxeditor');
			if ($this->cookie_mxeditor)
			{
				$bbuserinfo['usewysiwyg'] = ($this->cookie_mxeditor == 'wysiwyg') ? 1 : 0;
			}
			else if ($bboptions['mxemode'])
			{
				$bbuserinfo['usewysiwyg'] = 1;
			}
			else
			{
				$bbuserinfo['usewysiwyg'] = 0;
			}
			$pm['message'] = $this->postlib->parser->unconvert($pm['message'], 1, 0, $bbuserinfo['usewysiwyg']);
			if ($pm['title'])
			{
				if ($_INPUT['fwd'] == 1)
				{
					$title = $forums->lang['_fw'] . ":" . $pm['title'];
					$title = preg_replace("/^(?:" . $forums->lang['_fw'] . "\:){1,}/i", $forums->lang['_fw'] . ":", $title);
					$content = "[quote=" . $user['name'] . "]\r\n" . $pm['message'] . "\r\n[/quote]\r\n";
					$content = $bbuserinfo['usewysiwyg'] ? $content : br2nl($content);
				}
				else
				{
					$title = $forums->lang['_re'] . ":" . $pm['title'];
					$title = preg_replace("/^(?:" . $forums->lang['_re'] . "\:){1,}/i", $forums->lang['_re'] . ":", $title);
					$content = "[quote]\r\n" . $pm['message'] . "\r\n[/quote]\r\n";
					$content = $bbuserinfo['usewysiwyg'] ? $content : br2nl($content);
				}
			}
		}
		if (!$bbuserinfo['usewysiwyg'])
		{
			$pm['message'] = preg_replace("#<br.*>#siU", "\n", $pm['message']);
		}
		$content = utf8_htmlspecialchars($content);
		$content = preg_replace("#\[code\](.+?)\[/code\]#ies" , "utf8_unhtmlspecialchars('[code]\\1[/code]')", $content);
		if ($this->canupload)
		{
			$show['upload'] = true;
			$upload = $this->postlib->fetch_upload_form($posthash, 'msg');
		}
		$upload['maxnum'] = intval($bbuserinfo['attachnum']);
		$bbuserinfo['attachlimit'] = $bbuserinfo['attachlimit'] * 1024;
		$creditsingle_list = $this->credit->show_credit('sendpm', $bbuserinfo['usergroupid']);
		$smiles = $this->postlib->construct_smiles();
		$smile_count = $smiles['count'];
		$all_smiles = $smiles['all'];
		$smiles = $smiles['smiles'];
		$pagetitle = $forums->lang['sendpm'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['sendpm']);

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		include(ROOT_PATH . 'includes/ajax/ajax.php');

		//加载编辑器js
		load_editor_js();
		include $forums->func->load_template('pm_newpm');
		exit;
	}

	function build_contact_list()
	{
		global $DB, $forums, $bbuserinfo;
		$contact = "";
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "pmuserlist WHERE userid='" . $bbuserinfo['id'] . "' ORDER BY contactname");
		if ($DB->num_rows())
		{
			$contact = "<select class='select_normal' onchange='document.mxbform.username.focus(); document.mxbform.username.value = this.options[this.selectedIndex].value + document.mxbform.username.value;'>";
			$contact .= "<option selected value=''>--------</option>\n";
			while ($entry = $DB->fetch_array())
			{
				$contact .= "<option value='" . $entry['contactname'] . ";'>" . $entry['contactname'] . "</option>\n";
			}
			$contact .= "</select>\n";
		}
		return $contact;
	}

	function rebuild_foldercount($userid, $folders, $curfolderid, $pmcount, $nosave = 'save', $extra = "")
	{
		global $DB, $forums;
		$rebuild = array();
		if (! $folders)
		{
			$user = $DB->query_first("SELECT pmfolders FROM " . TABLE_PREFIX . "user WHERE id=" . $userid);
			$def_folders = array('0' => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']),
				'-1' => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']),
				);
			$folders = $user['pmfolders'] ? unserialize($user['pmfolders']) : $def_folders;
		}
		foreach($folders AS $id => $data)
		{
			if ($id == $curfolderid)
			{
				if ($pmcount == '-2')
				{
					$data['pmcount'] = intval($data['pmcount'] - 1);
				}
				else if ($pmcount == '-1')
				{
					$data['pmcount'] = intval($data['pmcount'] + 1);
				}
				else
				{
					$data['pmcount'] = intval($pmcount);
				}
			}
			$rebuild[$id] = $data;
		}
		$pmfolders = addslashes(serialize($rebuild));
		if ($nosave != 'nosave')
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmfolders='" . $pmfolders . "' " . $extra . " WHERE id=" . $userid);
		}
		return $pmfolders;
	}
}

?>