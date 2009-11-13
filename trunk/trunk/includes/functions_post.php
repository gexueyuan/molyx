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
# $Id: functions_post.php 457 2007-11-30 12:49:23Z develop_tong $
# **************************************************************************#
class functions_post
{
	var $userhash = '';
	var $obj = array();
	var $forum = array();
	var $canupload = 0;
	var $nav = array();
	var $moderator = array();
	var $postattach = array();
	var $totalattachsum = -1;
	var $totalattachsize = 0;
	var $qpids = array();

	function functions_post($convert = '1')
	{
		global $forums, $bbuserinfo;
		require_once(ROOT_PATH . 'includes/functions_showcode.php');
		$this->code = new functions_showcode();
		if ($convert)
		{
			require_once(ROOT_PATH . 'includes/functions_codeparse.php');

			//生成icon smile bbcode badword 缓存
			$this->parser = new functions_codeparse(1);
		}
		$this->userhash = $forums->func->md5_check();
	}

	function dopost($class)
	{
		global $forums, $_INPUT, $DB, $bbuserinfo, $bboptions;
		$this->class = $class;
		if ($_INPUT['t'])
		{
			$_INPUT['t'] = intval($_INPUT['t']);
			if (!$_INPUT['t'])
			{
				$forums->func->standard_error("erroraddress");
			}
		}
		if ($_INPUT['p'])
		{
			$_INPUT['p'] = intval($_INPUT['p']);
			if (!$_INPUT['p'])
			{
				$forums->func->standard_error("erroraddress");
			}
		}
		$_INPUT['f'] = intval($_INPUT['f']);
		if (!$_INPUT['f'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$this->forum = $forums->forum->single_forum($_INPUT['f']);
		$forums->forum->load_forum_style($this->forum['style']);
		if (!$this->forum['allowposting'])
		{
			$forums->func->standard_error("postnewthreaderror");
		}
		$_INPUT['pp'] = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$this->obj['preview'] = $_INPUT['preview'];
		if ($forums->func->fetch_permissions($this->forum['canupload'], 'canupload') == true)
		{
			if ($bbuserinfo['attachlimit'] != -1)
			{
				$this->canupload = 1;
				$this->obj['form_extra'] = ' enctype="multipart/form-data"';
			}
		}
		if (!$this->forum['status'])
		{
			$forums->func->standard_error('readforum');
		}
		$forums->forum->check_permissions($this->forum['id'], 1);
		$this->nav = $forums->forum->forums_nav($this->forum['id']);
		if (!$this->forum['id'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$this->obj['moderate'] = intval($this->forum['moderatepost']);
		if ($bbuserinfo['passmoderate'])
		{
			$this->obj['moderate'] = 0;
		}
		if ($bbuserinfo['moderate'])
		{
			if ($bbuserinfo['moderate'] == 1)
			{
				$this->obj['moderate'] = 1;
			}
			else
			{
				$mod_arr = banned_detect($bbuserinfo['moderate']);
				if (TIMENOW >= $mod_arr['date_end'])
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET moderate=0 WHERE id=" . $bbuserinfo['id'] . "");
					$this->obj['moderate'] = intval($this->forum['moderatepost']);
				}
				else
				{
					$this->obj['moderate'] = 1;
				}
			}
		}
		if ($bbuserinfo['id'])
		{
			if ($bbuserinfo['forbidpost'])
			{
				if ($bbuserinfo['forbidpost'] == 1)
				{
					$forums->func->standard_error("forbidpost");
				}
				$forbidpost = banned_detect($bbuserinfo['forbidpost']);
				if (TIMENOW >= $forbidpost['date_end'])
				{
					$DB->update(TABLE_PREFIX . 'user', array('forbidpost' => 0), 'id = ' . $bbuserinfo['id']);
				}
				else
				{
					$forums->func->standard_error('banuser', false, $forums->func->get_date($post_arr['date_end'], 2));
				}
			}
			if ($bbuserinfo['liftban'])
			{
				$liftban = banned_detect($bbuserinfo['liftban']);
				if ($liftban['forumid'] && $liftban['forumid'] == $this->forum['id'])
				{
					if (TIMENOW >= $liftban['date_end'])
					{
						$DB->update(TABLE_PREFIX . 'user', array('liftban' => ''), 'id = ' . $bbuserinfo['id']);
					}
					else
					{
						$forums->func->standard_error('banpost', false, $forums->func->get_date($liftban['date_end'], 2));
					}
				}
			}
		}
		if ($bbuserinfo['id'] && !$bbuserinfo['supermod'])
		{
			$this->moderator = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . "moderator
				WHERE forumid='{$this->forum['id']}'
					AND (userid='{$bbuserinfo['id']}'
					OR (usergroupid = '{$bbuserinfo['usergroupid']}'
						AND isgroup = 1))");
		}
		if ($_INPUT['do'] == 'update')
		{
			if ($_INPUT['userhash'] != $this->userhash)
			{
				$forums->func->standard_error("erroruserhash");
			}
			if ($bboptions['floodchecktime'] > 0)
			{
				if (!$bbuserinfo['passflood'])
				{
					if (TIMENOW - $bbuserinfo['lastpost'] < $bboptions['floodchecktime'])
					{
						$forums->func->standard_error("floodcheck", false, $bboptions['floodchecktime']);
					}
				}
			}
			if (!$bbuserinfo['id'])
			{
				$_INPUT['username'] = str_replace('|', '&#124;', trim($_INPUT['username']));
				$_INPUT['username'] = $_INPUT['username'] ? $_INPUT['username'] : $forums->lang['_guest'];
				if ($_INPUT['username'] != $forums->lang['_guest'])
				{
					$DB->query('SELECT id, name, email, usergroupid, password, host, options, salt
						FROM ' . TABLE_PREFIX . 'user
						WHERE LOWER(name) = ' . $DB->validate(strtolower($_INPUT['username'])) . '
							OR name = ' . $DB->validate($_INPUT['username']));
					if ($DB->num_rows())
					{
						$bboptions['guesttag'] = $bboptions['guesttag'] ? $bboptions['guesttag'] : '*';
						$_INPUT['username'] = $_INPUT['username'] . $bboptions['guesttag'];
					}
				}
			}
			$this->class->process();
		}
		else
		{
			$this->class->showform();
		}
	}

	function check_multi_quote($split = true)
	{
		global $DB, $forums, $_INPUT, $bboptions, $bbuserinfo;
		$add_tags = 0;
		if ($_INPUT['qpid'])
		{
			$_INPUT['qpid'] = $_INPUT['t'] . '|' . $_INPUT['qpid'] . ',';
		}
		$_INPUT['qpid'] = preg_replace("/[^,\d|]/", '', trim($_INPUT['qpid'] . $forums->func->get_cookie('mqtids')));
		if ($_INPUT['qpid'])
		{
			$forums->func->set_cookie('mqtids', ',', 0);
			$this->qpids = preg_split('/,/', $_INPUT['qpid'], -1, PREG_SPLIT_NO_EMPTY);
			if (count($this->qpids))
			{
				$tidspid = $tablename = $qposts = array();
				foreach ($this->qpids as $tpid)
				{
					if (strpos($tpid, '|'))
					{
						$tpid = explode('|', $tpid);
						$tidspid[$tpid[0]][] = $tpid[1];
					}
				}
				if ($tidspid)
				{
					$tposttable = $DB->query("SELECT tid, posttable
						FROM " . TABLE_PREFIX . "thread
						WHERE tid IN (" . implode(',', array_keys($tidspid)) . ")");

					while ($r = $DB->fetch_array($tposttable))
					{
						$r['posttable'] = $r['posttable'] ? $r['posttable'] : 'post';
						if (is_array($tablename[$r['posttable']]))
						{
							$tablename[$r['posttable']] = array_merge($tablename[$r['posttable']], $tidspid[$r['tid']]);
						}
						else
						{
							$tablename[$r['posttable']] = $tidspid[$r['tid']];
						}
					}
				}
				if (!empty($tablename))
				{
					foreach ($tablename as $table => $ids)
					{
						$result = $DB->query("SELECT p.*,t.forumid
							FROM " . TABLE_PREFIX . "$table p
							LEFT JOIN " . TABLE_PREFIX . "thread t
							ON (t.tid=p.threadid)
							WHERE pid IN (" . implode(",", $ids) . ")");
						while ($r = $DB->fetch_array($result))
						{
							$qposts[$r['pid']] = $r;
						}
					}
				}
				if (!empty($qposts))
				{
					foreach ($qposts as $pid => $qpost)
					{
						if ($_INPUT['t'] != $qpost['threadid']) continue;
						if ($forums->func->fetch_permissions($forums->forum->foruminfo[ $qpost['forumid'] ]['canread'], 'canread') == true)
						{
							$pagetext = $qpost['pagetext'];
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
							$qpost['pagetext'] = $qpost['hidepost'] ? $forums->lang['hiddenpost'] : trim($this->parser->unconvert($qpost['pagetext'], 1, 0, $bbuserinfo['usewysiwyg']));
							if ($bboptions['stripquotes'])
							{
								$qpost['pagetext'] = preg_replace("#\[quote(.+?)?\].*\[/quote\]#is", "", $qpost['pagetext']);
								$qpost['pagetext'] = preg_replace("#(?:\n|\r){3,}#s", "\n", trim($qpost['pagetext']));
							}
							if ($qpost['anonymous'])
							{
								$qpost['username'] = 'anonymous*';
							}
							if ($bbuserinfo['usewysiwyg'])
							{
								$qpost['pagetext'] = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies" , "str_replace('&lt;br /&gt;', '<br />', '[code\\1]\\2[/code]')", $qpost['pagetext']);
							}
							if ($bboptions['quoteslengthlimit'])
							{
								$check = trim(strip_tags($qpost['pagetext']));
								$check = str_replace(array('&amp;', '&lt;', '&gt;'), array('1', '1', '1'), $check);
								if (utf8_strlen($check) > $bboptions['quoteslengthlimit'])
								{
									$qpost['pagetext'] = preg_replace('/<img (.*) \/>/iU', '', $qpost['pagetext']);
									$qpost['pagetext'] = $forums->func->fetch_trimmed_title($qpost['pagetext'], $bboptions['quoteslengthlimit']);
									$qpost['pagetext'] = str_replace("\n", "<br />", $qpost['pagetext']);
								}
							}
							$content .= '[quote=' . $qpost['username'] . ',' . $forums->func->get_date($qpost['dateline'], 2, 1) . ',pid' . $qpost['pid'] . ']' . "\n" . ($qpost['pagetext']) . "\n[/quote]";
							if ($split)
							{
								$content .= "\n\n\n";
							}
						}
					}
				}
				$content = trim($content) . "\n";
			}
			if ($bbuserinfo['usewysiwyg'])
			{
				$content = str_replace("\n", "<br />", $content);
			}
		}
		if (isset($_POST['post']))
		{
			$content .= $_POST['post'];
		}
		if (isset($content))
		{
			$content = $this->init_post($content);
		}
		return $content;
	}

	function compile_title()
	{
		global $forums, $_INPUT, $bbuserinfo, $bboptions;
		if ($this->moderator['caneditthreads'] OR $bbuserinfo['supermod'])
		{
			if ($_INPUT['titlecolor'])
			{
				$_INPUT['title'] = '<font color="' . $_INPUT['titlecolor'] . '">' . $_INPUT['title'] . '</font>';
			}
			if ($_INPUT['titlebold'])
			{
				$_INPUT['title'] = '<strong>' . $_INPUT['title'] . '</strong>';
			}
		}
		return $_INPUT['title'];
	}

	function compile_post()
	{
		global $forums, $_INPUT, $bbuserinfo, $bboptions;
		$bboptions['maxpostchars'] = $bboptions['maxpostchars'] ? $bboptions['maxpostchars'] : 0;
		$_INPUT['showsignature'] = $_INPUT['showsignature'] ? 1 : 0;
		$_INPUT['allowsmile'] = $_INPUT['allowsmile'] ? 1 : 0;
		$useanonymous = 0;
		if ($bbuserinfo['cananonymous'] AND $_INPUT['anonymous'])
		{
			$useanonymous = 1;
		}
		$this->cookie_mxeditor = $forums->func->get_cookie('mxeditor');
		if ($this->cookie_mxeditor)
		{
			$bbuserinfo['usewysiwyg'] = ($this->cookie_mxeditor == 'wysiwyg') ? 1 : 0;
		}
		else
		{
			$bbuserinfo['usewysiwyg'] = ($bboptions['mxemode']) ? 1 : 0;
		}
		if (!$_INPUT['iconid'])
		{
			$_INPUT['iconid'] = 0;
		}
		$pagetext = $bbuserinfo['usewysiwyg'] ? $_POST['post'] : utf8_htmlspecialchars($_POST['post']);
		$post = array(
			'userid' => $bbuserinfo['id'] ? $bbuserinfo['id'] : 0,
			'showsignature' => $_INPUT['showsignature'],
			'allowsmile' => $_INPUT['allowsmile'],
			'host' => IPADDRESS,
			'dateline' => TIMENOW,
			'iconid' => intval($_INPUT['iconid']),
			'pagetext' => $this->parser->convert(array(
				'text' => $pagetext,
				'allowsmilies' => $_INPUT['allowsmile'],
				'allowcode' => $this->forum['allowbbcode'],
			)),
			'username' => $bbuserinfo['id'] ? $bbuserinfo['name'] : $_INPUT['username'],
			'threadid' => '',
			'anonymous' => $useanonymous,
			'moderate' => ($this->obj['moderate'] == 1 || $this->obj['moderate'] == 3) ? 1 : 0,
		);
		$posttext = $post['pagetext'];
		$check = $this->parser->unconvert($posttext, $this->forum['allowbbcode'], $this->forum['allowhtml'], $bbuserinfo['usewysiwyg']);
		$check = trim(strip_tags($check));
		$check = str_replace(array('&amp;', '&lt;', '&gt;'), array('1', '1', '1'), $check);
		if (($bboptions['maxpostchars'] && utf8_strlen($check) > $bboptions['maxpostchars']) || strlen($posttext) > 16777215)
		{
			$this->obj['errors'] = $forums->lang['_posterror1'];
		}
		if ($bboptions['minpostchars'] && utf8_strlen($check) < $bboptions['minpostchars'] && !$_INPUT['preview'])
		{
			$forums->func->standard_error("posttooshort", false, $bboptions['minpostchars']);
		}
		$this->obj['errors'] = $this->parser->error;
		return $post;
	}

	function fetch_post_form($additional_tags = array())
	{
		global $forums, $bbuserinfo;
		$wysiwyg = $bbuserinfo['usewysiwyg'] == 1 ? 1 : 0;
		$form .= '<form action="' . SCRIPT . '" method="post" name="mxbform" id="mxbform" onsubmit="if (typeof(submit_form) != \'undefined\') return submit_form(1);"' . $this->obj['form_extra'] . '>
			<input type="hidden" name="s" value="' . $forums->sessionid . '" />
			<input type="hidden" name="f" value="' . $this->forum['id'] . '" />
			<input type="hidden" name="wysiwyg" value="' . $wysiwyg . '" />
			<input type="hidden" name="userhash" value="' . $this->userhash . '" />
			<input type="hidden" name="preview" value="" />' .
			$this->obj['hidden_field'];
		if (isset($additional_tags))
		{
			foreach($additional_tags AS $k => $v)
			{
				$form .= "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}
		return $form;
	}

	function construct_checkboxes()
	{
		global $_INPUT, $bbuserinfo;
		$checked = array('signature' => 'checked="checked"', 'smiles' => 'checked="checked"', 'parseurl' => 'checked="checked"', 'titlebold' => 'checked="checked"', 'redirect' => 'checked="checked"');
		if (isset($_INPUT['showsignature']) AND (! $_INPUT['showsignature']))
		{
			$checked['signature'] = "";
		}
		if (! $bbuserinfo['redirecttype'])
		{
			$checked['redirect'] = "";
		}
		if (isset($_INPUT['allowsmile']) AND (! $_INPUT['allowsmile']))
		{
			$checked['smiles'] = "";
		}
		if (isset($_INPUT['parseurl']) AND (! $_INPUT['parseurl']))
		{
			$checked['parseurl'] = "";
		}
		if (! $_INPUT['titlebold'])
		{
			$checked['titlebold'] = "";
		}
		return $checked;
	}

	function construct_smiles()
	{
		global $forums, $bboptions;
		$forums->func->check_cache('smile');
		$smile_count = count($forums->cache['smile']);
		$all_smiles = $bboptions['smilenums'];
		$smiles = array();
		for ($i = 0; $i < $all_smiles; $i++)
		{
			if (isset($forums->cache['smile'][$i]))
			{
				$smiles[$forums->cache['smile'][$i]['id']] = $forums->cache['smile'][$i];
			}
			else
			{
				break;
			}
		}
		return array('smiles' => $smiles, 'count' => $smile_count, 'all' => $all_smiles);
	}

	function construct_icons()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forums->func->check_cache('icon');
		foreach($forums->cache['icon'] AS $icon)
		{
			$icons[] = $icon;
		}
		return $icons;
	}

	function modoptions($replythread = 0)
	{
		global $bbuserinfo, $forums;
		$canclose = false;
		$canstick = false;
		$canmove = false;
		$cangstick = false;
		$cangstickclose = false;
		$modoptions = "";
		if ($bbuserinfo['supermod'])
		{
			$canclose = true;
			$cangstick = true;
			$cangstickclose = true;
			$canstick = true;
			$canmove = true;
		}
		else if ($bbuserinfo['id'])
		{
			if ($this->moderator['moderatorid'])
			{
				if ($this->moderator['canopenclose'])
				{
					$canclose = true;
				}
				if ($this->moderator['canstickthread'])
				{
					$canstick = true;
				}
				if ($this->moderator['canremoveposts'])
				{
					$canmove = true;
				}
			}
		}
		if (!$canstick AND !$canclose AND !$canmove AND !$cangstick)
		{
			return $modoptions;
		}
		else
		{
			$modoptions = "<select name='modoptions' class='select_normal'>\n<option value=''>" . $forums->lang['_noaction'] . "</option>\n";
		}
		if ($cangstick)
		{
			$modoptions .= "<option value='gstick'>" . $forums->lang['gstickthread'] . "</option>\n";
		}
		if ($canstick)
		{
			$modoptions .= "<option value='stick'>" . $forums->lang['_stickthread'] . "</option>\n";
		}
		if ($canclose)
		{
			$modoptions .= "<option value='close'>" . $forums->lang['_closethread'] . "</option>\n";
		}
		if ($cangstickclose)
		{
			$modoptions .= "<option value='gstickclose'>" . $forums->lang['gstickclose'] . "</option>\n";
		}
		if ($canclose AND $canstick)
		{
			$modoptions .= "<option value='stickclose'>" . $forums->lang['_stickandclose'] . "</option>\n";
		}
		if ($canmove AND $replythread)
		{
			$modoptions .= "<option value='move'>" . $forums->lang['_movethread'] . "</option>\n";
		}
		return $modoptions;
	}

	function attachmentcount()
	{
		global $forums, $DB, $bbuserinfo;
		if ($this->totalattachsum == -1)
		{
			$stats = $DB->query_first("SELECT SUM(filesize) as sum FROM " . TABLE_PREFIX . "attachment WHERE userid=" . $bbuserinfo['id'] . "");
			$this->totalattachsum = $stats['sum'];
		}
	}

	//有空的时候想办法把这里的html做成模板
	function fetch_upload_form($posthash = "", $type = "", $pid = "")
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$this->attachmentcount();
		$upload['left'] = ($bbuserinfo['attachlimit'] > 0) ? fetch_number_format(intval(($bbuserinfo['attachlimit'] * 1024) - $this->totalattachsum), true) : "<strong>" . $forums->lang['_nolimit'] . "</strong>";
		if ($posthash != "")
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE posthash='" . $posthash . "'");
			while ($r = $DB->fetch_array())
			{
				$this->postattach[] = $r;
			}
			if (is_array($this->postattach) AND count($this->postattach))
			{
				$upload_files = "<table cellpadding='4' cellspacing='0' border='0'><tbody>";
				$upload_size = 0;
				$forums->func->check_cache('attachmenttype');
				$forums->func->check_cache('usergroup');
				$usergrp = $forums->cache['usergroup'];

				$i = 0;
				foreach($this->postattach AS $row)
				{
					$i++;
					$upload_size += $row['filesize'];
					$row['image'] = $forums->cache['attachmenttype'][ $row['extension'] ]['attachimg'];
					$row['size'] = fetch_number_format($row['filesize'], true);
					if (strlen($row['filename']) > 40)
					{
						$row['filename'] = substr($row['filename'], 0, 35) . '...';
					}
					$row['thumb'] = '';
					if (in_array($row['extension'], array('jpg', 'gif', 'png')))
					{
						$thumb_path = $row['userid'] ? $row['userid'] : $bbuserinfo['id'];
						$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $thumb_path, -1, PREG_SPLIT_NO_EMPTY));
						$path = $bboptions['uploadurl'] . '/' . $subpath;
						$row['thumb'] = "<img src='" . $path . "/" . ($row['thumblocation'] ? $row['thumblocation'] : $row['location']) . "' width='100' alt='' />";
					}
					$upload_files .= "<tr><td width='1%'><img src='images/" . $row['image'] . "' border='0' alt='' /></td><td width='100%'>" . $row['thumb'] . " <strong>" . $row['filename'] . "</strong></td><td width='20%' nowrap='nowrap'>(" . $row['size'] . ")</td><td nowrap='nowrap'><input type='button' name='removeattach_" . $row['attachmentid'] . "' id='removeattach_" . $row['attachmentid'] . "' class='button' onclick='removeattach(" . $row['attachmentid'] . ")' value='" . $forums->lang['_delete'] . "' /> <input type='button' name='aid" . $row['attachmentid'] . "' class='button' onclick='insertattach(" . $row['attachmentid'] . ")' value='" . $forums->lang['_insertattach'] . "' /></td></tr>";

					$upload_files.="</td></tr>";
				}
				$space_used = fetch_number_format(intval($upload_size), true);
				if ($bbuserinfo['attachlimit'] > 0)
				{
					$type = $bbuserinfo['perpostattach'] ? $bbuserinfo['perpostattach'] : $bbuserinfo['attachlimit'];
					$upload['left'] = fetch_number_format(intval(($type * 1024) - $upload_size), true);
				}
				else
				{
					$upload['left'] = $bbuserinfo['perpostattach'] ? fetch_number_format(intval(($bbuserinfo['perpostattach'] * 1024) - $upload_size), true) : $forums->lang['_nolimit'];
				}
				$forums->lang['_tempused'] = sprintf($forums->lang['_tempused'], $space_used);
				$upload_files .= "</tbody></table>";
				$upload['tmp'] .= "<fieldset><legend><strong>" . $forums->lang['_tempused'] . ": " . $upload['left'] . "</strong></legend>\n";
				$upload['tmp'] .= $upload_files;
				$upload['tmp'] .= "</fieldset>";
			}
		}

		return $upload;
	}

	function moderate_log($action = 'Unknown', $title)
	{
		global $forums, $bbuserinfo;
		require_once(ROOT_PATH . 'includes/functions_moderate.php');
		$this->modfunc = new modfunctions();
		$this->modfunc->add_moderate_log($_INPUT['f'], $_INPUT['t'], $_INPUT['p'], $title, $action . $title);
	}

	function process_upload($userid = '')
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		$forums->func->check_cache('attachmenttype');

		$attach_data = array(
			'extension' => '',
			'filename' => '',
			'location' => '',
			'thumblocation' => '',
			'counter' => 0,
			'dateline' => TIMENOW,
			'temp' => 0,
			'postid' => 0,
			'posthash' => $_INPUT['posthash'],
			'userid' => $userid ? $userid : $bbuserinfo['id'],
			'filesize' => 0,
			'attachpath' => '',
		);
		if (($this->canupload != 1) || ($bbuserinfo['attachlimit'] == '-1'))
		{
			return $attach_data;
		}
		//获得filesize大小
		$this->attachmentcount();
		//获得相关文件信息
		foreach($_FILES as $key => $val)
		{
			$this->totalattachsize += $val['size'];
		}

		if ($bbuserinfo['attachlimit'] > 0)
		{
			$attachment['left'] = $bbuserinfo['perpostattach'] ? intval(($bbuserinfo['perpostattach'] * 1024) - $this->totalattachsize) : intval(($bbuserinfo['attachlimit'] * 1024) - $this->totalattachsum);
		}
		else
		{
			$attachment['left'] = $bbuserinfo['perpostattach'] ? intval(($bbuserinfo['perpostattach'] * 1024) - $this->totalattachsize) : 1000000000;
		}
		if ($attachment['left'] <= 0)
		{
			$this->obj['errors'] = $forums->lang['_uploaderror1'];
			return $attach_data;
		}
		require_once(ROOT_PATH . 'includes/functions_upload.php');
		$upload = new functions_upload();
		$userid = ($userid != '') ? intval($userid) : $bbuserinfo['id'];
		$upload->filepath = $upload->verify_attachment_path($userid);
		$upload->maxfilesize = $attachment['left'];
		if (is_array($forums->cache['attachmenttype']) && count($forums->cache['attachmenttype']))
		{
			foreach($forums->cache['attachmenttype'] as $idx => $data)
			{
				if ($data['usepost'])
				{
					$upload->allow_extension[] = $data['extension'];
				}
			}
		}
		$upload->upload_process();
		if ($upload->error_no)
		{
			switch ($upload->error_no)
			{
				case 1:
					$this->obj['errors'] = $forums->lang['_uploaderror1'];
					return $attach_data;
				case 2:
					$this->obj['errors'] = $forums->lang['_uploaderror2'];
					return $attach_data;
				case 3:
					$this->obj['errors'] = $forums->lang['_uploaderror3'];
					return $attach_data;
				case 4:
					$this->obj['errors'] = $forums->lang['_uploaderror4'];
					return $attach_data;
			}
		}
		if ($upload->uploadfile && is_array($upload->uploadfile))
		{
			$newid = array();
			foreach ($upload->uploadfile as $key => $uploadfile)
			{
				if (@file_exists($uploadfile))
				{
					unset($attach_data['attachmentid']);
					$attach_data['filesize'] = @filesize($uploadfile);
					$attach_data['location'] = $upload->parsed_file_name[$key];
					$attach_data['filename'] = $upload->original_file_name[$key];
					$attach_data['image'] = intval($upload->is_image[$key]);
					$attach_data['extension'] = $upload->real_file_extension[$key];
					$attach_data['attachpath'] = SAFE_MODE ? '' : implode('/', preg_split('//', $userid, -1, PREG_SPLIT_NO_EMPTY));
					if ($attach_data['image'] == 1 && $bboptions['sigimgdimension'])
					{
						$thumb_data = $upload->create_thumbnail($attach_data);
						$attach_data['thumbwidth'] = $thumb_data['thumbwidth'];
						$attach_data['thumbheight'] = $thumb_data['thumbheight'];
						$attach_data['thumblocation'] = $thumb_data['thumblocation'];
					}
					$DB->insert(TABLE_PREFIX . 'attachment', $attach_data);
					$insertid = $DB->insert_id();
					$attach_data['attachmentid'] = $insertid;
					$this->totalattachsize += $attach_data['filesize'];
					$newid[] = $insertid;
				}
			}
			return $newid;
		}
	}

	function posts_recount()
	{
		global $forums, $DB, $bbuserinfo;
		if ($bbuserinfo['id'])
		{
			$sql_array = array();
			if ($this->forum['countposts'])
			{
				$sql_array['posts'] = array(1, '+');
			}
			$sql_array['lastpost'] = $bbuserinfo['lastpost'] = TIMENOW;
			$DB->update(TABLE_PREFIX . 'user', $sql_array, 'id = ' . $bbuserinfo['id']);
		}
	}

	function stats_recount($tid = 0, $type = 'new')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$moderated = false;
		if ($this->obj['moderate'])
		{
			if (($type == 'new' && ($this->obj['moderate'] == 1 || $this->obj['moderate'] == 2)) || ($type == 'reply' && ($this->obj['moderate'] == 1 || $this->obj['moderate'] == 3)))
			{
				$moderate = true;
			}
		}
		$this_stats = array();
		$forum_ids = explode(',', $this->forum['parentlist']);
		unset($forum_ids[count($forum_ids) - 1]);
		$stats = array();
		if (!$moderate)
		{
			$stats['lastthreadid'] = $tid;
			if ($type == 'new')
			{
				$stats['thread'] = array(1, '+');
				$this_stats['this_thread'] = array(1, '+');
			}
			$stats['post'] = $stats['todaypost'] = array(1, '+');
		}
		else
		{
			if ($type == 'new')
			{
				$stats['unmodthreads'] = array(1, '+');
			}
			else
			{
				$stats['unmodposts'] = array(1, '+');
			}
		}

		$DB->update(TABLE_PREFIX . 'forum', $stats, $DB->sql_in('id', $forum_ids));
		if ($this_stats)
		{
			$DB->update(TABLE_PREFIX . 'forum', $this_stats, 'id = ' . $this->forum['id']);
		}
	}

	function remove_attachment($attachmentid, $posthash)
	{
		global $forums, $DB, $bboptions;
		$attachment = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE posthash='" . $posthash . "' AND attachmentid=" . $attachmentid . " LIMIT 0, 1");
		if ($attachment['attachmentid'])
		{
			if ($attachment['location'])
			{
				@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['location']);
			}
			if ($attachment['thumblocation'])
			{
				@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['thumblocation']);
			}
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid=" . $attachment['attachmentid'] . "");

			$forums->func->check_cache('splittable');
			$deftable = $forums->cache['splittable']['default'];
			$deftable = $deftable ? $deftable : 'post';
			$posttable = $attachment['posttable'] ? $attachment['posttable'] : $deftable;
			$t = $DB->query_first("SELECT threadid FROM " . TABLE_PREFIX . "$posttable WHERE posthash='" . $posthash . "' LIMIT 0, 1");
			$this->recount_attachment($t['threadid']);
		}
	}

	function attachment_complete($posthash = array(), $tid = "", $pid = "", $posttable = '', $pmid = "")
	{
		global $forums, $DB, $bboptions;
		$cutoff = TIMENOW - 7200;
		$deadid = array();
		$attachments = $DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE postid=0 AND pmid=0 AND dateline < " . $cutoff . "");
		while ($attachment = $DB->fetch_array($attachments))
		{
			if ($attachment['location'])
			{
				@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['location']);
			}
			if ($attachment['thumblocation'])
			{
				@unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['thumblocation']);
			}
			$deadid[] = $attachment['attachmentid'];
		}
		if (count($deadid))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN(" . implode(",", $deadid) . ")");
		}
		if ($posthash[1])
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "attachment SET posthash='" . $posthash[0] . "' WHERE posthash='" . $posthash[1] . "'");
		}
		if ($posthash[0] AND ($pid OR $pmid))
		{
			$cnt = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "attachment WHERE posthash='" . $posthash[0] . "'");
			if ($cnt['count'])
			{

				if ($pmid != "")
				{
					$query[] = "pmid=" . $pmid;
				}
				if ($pid != "")
				{
					$query[] = "postid=" . $pid;
					$query[] = "posttable='" . $posttable . "'";
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET attach=" . $cnt['count'] . " WHERE tid=" . $tid . "");
				}
				if ($tid)
				{
					$query[] = "threadid=" . $tid;
				}
				if (is_array($query))
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "attachment SET " . implode(", ", $query) . " WHERE posthash='" . $posthash[0] . "'");
					$this->clean_attachment($posthash[0]);
				}
			}
		}
		return $cnt['count'];
	}

	function clean_attachment($posthash = "")
	{
		global $forums, $DB;
		$update_notinpost = false;
		$update_inpost = false;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE posthash='{$posthash}'");
		while ($attach = $DB->fetch_array())
		{
			if ($attach['inpost'])
			{
				if (!in_array($attach['attachmentid'], $this->parser->attachinpost))
				{
					$update_notinpost = true;
					$notinpost[] = $attach['attachmentid'];
				}
			}
			else
			{
				if (in_array($attach['attachmentid'], $this->parser->attachinpost))
				{
					$update_inpost = true;
					$inpost[] = $attach['attachmentid'];
				}
			}
		}
		if ($update_notinpost)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "attachment SET inpost = 0 WHERE attachmentid IN (" . implode(",", $notinpost) . ")");
		}
		if ($update_inpost)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "attachment SET inpost = 1 WHERE attachmentid IN (" . implode(",", $inpost) . ")");
		}
	}

	function recount_attachment($tid = '')
	{
		global $forums, $DB;
		if (empty($tid))
		{
			return;
		}

		$pids = array();
		$count = 0;
		$getthread = $result = $DB->query('SELECT posttable
			FROM ' . TABLE_PREFIX . "thread
			WHERE tid = $tid");
		$posttable = $getthread['posttable']?$getthread['posttable']:'post';
		$result = $DB->query('SELECT pid
			FROM ' . TABLE_PREFIX . "$posttable
			WHERE threadid = $tid");
		if ($DB->num_rows())
		{
			while ($p = $DB->fetch_array($result))
			{
				$pids[] = $p['pid'];
			}
			$count = $DB->query_first('SELECT count(*) as count
				FROM ' . TABLE_PREFIX . 'attachment
				WHERE ' . $DB->sql_in('postid', $pids) ."
				AND posttable='" . $posttable . "'");
			$count = intval($count['count']);
		}
		$DB->update(TABLE_PREFIX . 'thread', array('attach'=> $count), "tid = $tid");
	}

	function validate_antispam()
	{
		global $forums, $DB, $bboptions, $_INPUT;

		if ($bboptions['useantispam'])
		{
			if ($_INPUT['imagehash'] == "" OR $_INPUT['antispam'] == "")
			{
				return false;
			}
			if (!$row = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "antispam WHERE regimagehash='" . addslashes(trim($_INPUT['imagehash'])) . "'"))
			{
				return false;
			}
			if (intval($_INPUT['antispam']) != $row['imagestamp'])
			{
				return false;
			}
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "antispam WHERE regimagehash='" . addslashes(trim($_INPUT['imagehash'])) . "'");
			return true;
		}
		return true;
	}

	function init_post($text = '')
	{
		$text = str_replace('$', '&#036;', $text);
		return preg_replace("/\\\(&amp;#|\?#)/", '&#092;', $text);
	}
}

?>