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
define('THIS_SCRIPT', 'usercp');
require ('./global.php');

class usercp
{
	var $posthash = '';
	var $pmselect = '';
	var $threadread = '';
	var $read_array = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$this->posthash = $forums->func->md5_check();
		if (! $bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$forums->func->load_lang('usercp');
		if ($bbuserinfo['pmquota'])
		{
			$bbuserinfo['folder_links'] = "";
			$bbuserinfo['pmfolders'] = unserialize($bbuserinfo['pmfolders']);
			if (count($bbuserinfo['pmfolders']) < 2)
			{
				$bbuserinfo['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']));
			}
			foreach($bbuserinfo['pmfolders'] AS $id => $data)
			{
				$this->pmselect .= "<option value='" . $id . "'>" . $data['foldername'] . "</option>\n";
			}
		}
		switch ($_INPUT['do'])
		{
			case 'editprofile':
				$this->edit_profile();
				break;
			case 'setting':
				$this->forum_setting();
				break;
			case 'dosetting':
				$this->do_forum_setting();
				break;
			case 'doprofile':
				$this->do_profile();
				break;
			case 'editsignature':
				$this->edit_signature();
				break;
			case 'dosignature':
				$this->do_signature();
				break;
			case 'editavatar':
				$this->edit_avatar();
				break;
			case 'doavatar':
				$this->do_avatar();
				break;
			case 'subscribethread':
				$this->subscribe_thread();
				break;
			case 'dounsubscribe':
				$this->do_unsubscribe();
				break;
			case 'userchange':
				$this->userchange();
				break;
			case 'dochange':
				$this->do_change();
				break;
			case 'subscribeforum':
				$this->subscribe_forum();
				break;
			case 'getgallery':
				$this->avatar_gallery();
				break;
			case 'setinternalavatar':
				$this->set_internal_avatar();
				break;
			case 'attach':
				$this->attachment();
				break;
			default:
				$this->usercp_main();
				break;
		}
	}

	function usercp_main()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$info['email'] = $bbuserinfo['email'];
		$info['joindate'] = $forums->func->get_date($bbuserinfo['joindate'], 3);
		$info['posts'] = $bbuserinfo['posts'];
		$info['daily_average'] = $forums->lang['dailyaverage'];
		if ($bbuserinfo['posts'] > 0)
		{
			$diff = TIMENOW - $bbuserinfo['joindate'];
			$days = ($diff / 3600) / 24;
			$days = $days < 1 ? 1 : $days;
			$info['daily_average'] = sprintf('%.2f', ($bbuserinfo['posts'] / $days));
		}
		$safe = $DB->query_first("SELECT answer, question FROM " . TABLE_PREFIX . "userextra WHERE id = {$bbuserinfo['id']}");
		if (!$safe['answer'])
		{
			$forums->lang['nosafedesc'] = preg_replace("#(.*)(^|\.php)#", '\\1\\2' . $forums->sessionurl, str_replace("?", '', $forums->lang['nosafedesc']));
			$show_safe = true;
		}
		$pms = $DB->query("SELECT p.*,u.name as fromusername, u.id as from_id
						 FROM " . TABLE_PREFIX . "pm p
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( p.fromuserid=u.id )
						WHERE p.userid='" . $bbuserinfo['id'] . "' AND p.folderid=0 AND p.touserid='" . $bbuserinfo['id'] . "'  AND p.pmread=0
						ORDER BY dateline DESC LIMIT 0, 5");
		if ($DB->num_rows($pms))
		{
			$show['message'] = true;
			while ($row = $DB->fetch_array($pms))
			{
				if ($row['attach'])
				{
					$row['attach_img'] = 1;
				}
				$row['date'] = $forums->func->get_date($row['dateline'] , 2);
				$unread_pmlist[] = $row;
			}
		}
		$threadread = array();
		$final_array = array();
		$this->threadread = $forums->func->get_cookie('threadread');
		$this->read_array = unserialize($this->threadread);
		if (is_array($this->read_array) AND count($this->read_array))
		{
			arsort($this->read_array);
			$thread_array = array_slice(array_keys($this->read_array), 0, 5);
			if (count($thread_array))
			{
				$show['thread'] = true;
				$DB->query("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid IN (" . implode(",", $thread_array) . ") LIMIT 0, 5");
				$thread = array();
				while ($row = $DB->fetch_array())
				{
					if ($forums->forum->foruminfo[$row['forumid']])
					{
						$row = $this->parse_data($row);
						$thread[] = $row;
					}
				}
			}
		}
		$forums->func->check_cache('attachmenttype');
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE userid='" . $bbuserinfo['id'] . "' ORDER BY dateline DESC LIMIT 0, 5");
		if ($DB->num_rows())
		{
			$show['attachment'] = true;
			while ($row = $DB->fetch_array())
			{
				$row['method'] = $row['pmid'] ? 'msg' : 'post';
				$row['image'] = $forums->cache['attachmenttype'][ strtolower($row['extension']) ]['attachimg'];
				$attachment[] = $row;
			}
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['usercp'] . " - " . $bboptions['bbtitle'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>");
		include $forums->func->load_template('usercp_main');
		exit;
	}

	function subscribe_thread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$datecut = intval($_INPUT['datecut']) != "" ? intval($_INPUT['datecut']) : 1000;
		$date_query = $datecut != 1000 ? " AND s.dateline > '" . (TIMENOW - ($datecut * 86400)) . "' " : "";
		$DB->query("SELECT s.subscribethreadid, s.userid, s.threadid, s.dateline as track_started, t.*
									FROM " . TABLE_PREFIX . "subscribethread s
										LEFT JOIN " . TABLE_PREFIX . "thread t ON (t.tid=s.threadid)
									WHERE s.userid='" . $bbuserinfo['id'] . "' " . $date_query . " ORDER BY s.subscribethreadid DESC
								");
		if ($DB->num_rows())
		{
			$show['subscribe'] = true;
			$last_forumid = -1;
			$this->threadread = $forums->func->get_cookie('threadread');
			$this->read_array = unserialize($this->threadread);
			while ($thread = $DB->fetch_array())
			{
				$thread = $this->parse_data($thread);
				$thread['description'] = empty($thread['description']) ? '' : $thread['description'] . ' | ';
				$subscribe[] = $thread;
			}
		}
		$datelist = "";
		foreach(array(1, 7, 30, 60, 90, 365) AS $day)
		{
			$selected = $day == $datecut ? " selected='selected'" : '';
			$datelist .= "<option value='$day'$selected>" . $day . " {$forums->lang['_days']}</option>\n";
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['subscibethread'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['subscibethread']);
		include $forums->func->load_template('usercp_subscribe_thread');
		exit;
	}

	function subscribe_forum()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$DB->query("SELECT s.*, f.*
				FROM " . TABLE_PREFIX . "subscribeforum s
				 LEFT JOIN " . TABLE_PREFIX . "forum f ON (s.forumid=f.id)
				WHERE s.userid='" . $bbuserinfo['id'] . "'");
		if ($DB->num_rows())
		{
			$show['subscribe'] = true;
			while ($forum = $DB->fetch_array())
			{
				$forum['foldericon'] = $forums->forum->forums_new_post($forum);
				$forum['lastpost'] = $forums->func->get_date($forum['lastpost'], 2);
				$forum['lastthread'] = str_replace("&#33;" , "!", $forum['lastthread']);
				$forum['lastthread'] = str_replace("&quot;", "\"", $forum['lastthread']);
				if (strlen($forum['lastthread']) > 30)
				{
					$forum['lastthread'] = $forums->func->fetch_trimmed_title(strip_tags($forum['lastthread']), 14);
				}
				if ($forum['lastthread'] == "")
				{
					$forum['lastthread'] = "----";
				}
				else if ($forum['password'] != "")
				{
					$forum['lastthread'] = $forums->lang['hiddenforum'];
				}
				else
				{
					$forum['lastthread'] = "<a href='redirect.php{$forums->sessionurl}t=" . $forum['lastthreadid'] . "&amp;goto=lastpost'>" . $forum['lastthread'] . "</a>";
				}
				if (isset($forum['lastposter']))
				{
					$forum['lastposter'] = $forum['lastposterid'] ? "<a href='profile.php{$forums->sessionurl}u={$forum['lastposterid']}'>{$forum['lastposter']}</a>" : $forum['lastposter'];
				}
				else
				{
					$forum['lastposter'] = "----";
				}
				$subscribe[] = $forum;
			}
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['subscibeforum'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['subscibeforum']);
		include $forums->func->load_template('usercp_subscribe_forum');
		exit;
	}

	function edit_profile()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['caneditprofile'])
		{
			$forums->func->standard_error("noperms");
		}
		$safe = $DB->query_first("SELECT answer, question FROM " . TABLE_PREFIX . "userextra WHERE id = {$bbuserinfo['id']}");
		if (is_array($safe))
		{
			$bbuserinfo = @array_merge($safe, $bbuserinfo);
		}
		$date = getdate();
		$year = "<option value='0'>--</option>\n";
		$mon = "<option value='0'>--</option>\n";
		$day = "<option value='0'>--</option>\n";
		$birthday = explode('-', $bbuserinfo['birthday']);
		$i = $date['year'] - 1;
		$j = $date['year'] - 50;
		for ($i ; $j < $i ; $i--)
		{
			$selected = ($i == $birthday[0]) ? " selected='selected'" : "";
			$year .= "<option value='$i'{$selected}>$i</option>\n";
		}
		for ($i = 1 ; $i < 13 ; $i++)
		{
			$selected = ($i == $birthday[1]) ? " selected='selected'" : "";
			$mon .= "<option value='$i'{$selected}>$i " . $forums->lang['month'] . "</option>\n";
		}
		for ($i = 1 ; $i < 32 ; $i++)
		{
			$selected = ($i == $birthday[2]) ? " selected='selected'" : "";
			$day .= "<option value='$i'{$selected}>$i</option>\n";
		}
		$posthash = $this->posthash;
		if ($bbuserinfo['gender'] == 1)
		{
			$male_check = 'checked="checked"';
		}
		else if ($bbuserinfo['gender'] == 2)
		{
			$female_check = 'checked="checked"';
		}
		else
		{
			$default_check = 'checked="checked"';
		}

		// 自定义字段
		$usrext_field = $this->get_usrext_form();

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['editprofile'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['editprofile']);
		include $forums->func->load_template('usercp_profile');
		exit;
	}

	function get_usrext_form()
	{
		global $forums, $bbuserinfo;
		$forums->func->check_cache('userextrafield');

		$return = array('must' => array(), 'other' => array());
		foreach ($forums->cache['userextrafield']['a'] as $k => $v)
		{
			$form = '';
			$type = isset($forums->cache['userextrafield']['f'][$k]) ? 'must' : 'other';
			switch ($v['showtype'])
			{
				case 'text':
					$form = '<input title="' . $v['fielddesc'] . '" size="40" type="text" name="' . $k . '" value="' . $bbuserinfo[$k] . '" class="input_normal" />';
				break;

				case 'select':
					$form = '<select title="' . $v['fielddesc'] . '" name="' . $k . '" class="select_normal">';
					foreach ($v['listcontent'] as $list)
					{
						$form .= '<option value="' . $list[0] . '"';
						$form .= ($bbuserinfo[$k] == $list[0]) ? ' selected="selected"' : '';
						$form .= '>' . $list[1] . '</option>';
					}
					$form .= '</select>';
				break;

				case 'textarea':
					$form = '<textarea cols="80" rows="5" name="' . $k . '" title="' . $v['fielddesc'] . '">' . $bbuserinfo[$k] . '</textarea>';
				break;
			}
			$return[$type][] = array(
				'name' => $v['fieldname'],
				'desc' => $v['fielddesc'],
				'html' => $form
			);
		}
		return $return;
	}

	function edit_signature()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['cansignature'])
		{
			$forums->func->standard_error("noperms");
		}
		$forums->func->load_lang('post');
		require_once(ROOT_PATH . "includes/functions_post.php");
		$this->lib = new functions_post();
		$max_length = $bboptions['signaturemaxlength'] ? $bboptions['signaturemaxlength'] : 0;
		$posthash = $this->posthash;
		$smiles = $this->lib->construct_smiles();
		$smile_count = $smiles['count'];
		$all_smiles = $smiles['all'];
		$smiles = $smiles['smiles'];

		require_once(ROOT_PATH . "includes/class_textparse.php");
		$signature1 = textparse::convert_text($bbuserinfo['signature'], $bboptions['signatureallowhtml']);
		$signature = $signature1;
		$signature_path = split_todir($bbuserinfo['id'], $bboptions['uploadurl'] . '/user');
		$signature_path = $signature_path[0] . '/';
		$signature = str_replace('{$signature_path}', $signature_path, $signature);
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
		$signature1 = preg_replace("#<!--sig_img-->(.+?)<!--sig_img1-->#", "", $bbuserinfo['signature']);
		$signature1 = $this->lib->parser->unconvert($signature1, $bboptions['signatureallowbbcode'], $bboptions['signatureallowhtml'], $bbuserinfo['usewysiwyg']);
		if (!$bbuserinfo['usewysiwyg'])
		{
			$signature1 = preg_replace("#<br.*>#siU", "\n", $signature1);
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['editsignature'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['editsignature']);

		//加载编辑器js
		load_editor_js();
		include $forums->func->load_template('usercp_signature');
		exit;
	}

	function edit_avatar()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		list($bbuserinfo['avatar_width'] , $bbuserinfo['avatar_height']) = explode ("x", $bbuserinfo['avatarsize']);
		$avatarsizeset = explode('|', $bboptions['avatardimension']);
		list($bboptions['av_width'], $bboptions['av_height']) = explode ("x", $avatarsizeset[0]);
		$my_avatar = $forums->func->get_avatar($bbuserinfo['id'], $bbuserinfo['avatar']);
		$my_avatar = $my_avatar ? $my_avatar : $forums->lang['noavatar'];
		$avatar_gallery = array();
		$dh = opendir(ROOT_PATH . 'images/avatars');
		while ($file = readdir($dh))
		{
			if (is_dir(ROOT_PATH . 'images/avatars/' . $file))
			{
				if ($file != "." && $file != "..")
				{
					$categories[] = array($file, str_replace("_", " ", $file));
				}
			}
		}
		closedir($dh);
		if (is_array($categories))
		{
			$show['gallerylist'] = true;
			reset($categories);
			$gallerylist = "<select name='av_cat' class='select_normal' >\n";
			foreach($categories AS $cat)
			{
				$gallerylist .= "<option value='" . $cat[0] . "'>" . $cat[1] . "</option>\n";
			}
			$gallerylist .= "</select>\n";
		}
		$formextra = "";
		$hidden_field = "";
		if ($bbuserinfo['canuseavatar'] == 1)
		{
			$formextra = " enctype='multipart/form-data'";
			$hidden_field = "<input type='hidden' name='MAX_FILE_SIZE' value='9000000' />";
		}
		if ($bboptions['avatarurl'])
		{
			$show['avatarurl'] = true;
			$avatarurl = "http://";
		}
		if ($bbuserinfo['canuseavatar'] == 1)
		{
			$show['avatar_upload'] = true;
		}
		$forums->lang['changeavatar'] = sprintf($forums->lang['changeavatar'], $bboptions['av_width'], $bboptions['av_height']);
		$upload_size = $forums->lang['changeavatar'];

		//加载ajax
		$mxajax_register_functions = array('delete_user_avatar'); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['editavatar'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['changeavatar']);
		include $forums->func->load_template('usercp_avatar');
		exit;
	}

	function attachment()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($bbuserinfo['attachlimit'] == -1)
		{
			$forums->func->standard_error("noperms");
		}
		$info = array();
		$start = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$perpage = 15;
		$sortby = "";
		switch ($_INPUT['sort'])
		{
			case 'date':
				$sortby = 'a.dateline ASC';
				$info['date_order'] = 'rdate';
				$info['size_order'] = 'size';
				break;
			case 'rdate':
				$sortby = 'a.dateline DESC';
				$info['date_order'] = 'date';
				$info['size_order'] = 'size';
				break;
			case 'size':
				$sortby = 'a.filesize DESC';
				$info['date_order'] = 'date';
				$info['size_order'] = 'rsize';
				break;
			case 'rsize':
				$sortby = 'a.filesize ASC';
				$info['date_order'] = 'date';
				$info['size_order'] = 'size';
				break;
			default:
				$sortby = 'a.dateline DESC';
				$info['date_order'] = 'date';
				$info['size_order'] = 'size';
				break;
		}
		if (is_array($_INPUT['attachid']))
		{
			foreach ($_INPUT['attachid'] AS $k)
			{
				$k = intval($k);
				if (empty($k)) continue;
				$ids[] = $k;
			}
		}
		$affected_ids = count($ids);
		if ($affected_ids > 0)
		{
			$attachments = $DB->query("SELECT a.*, p.threadid, p.pid
										 FROM " . TABLE_PREFIX . "attachment a
										  LEFT JOIN " . TABLE_PREFIX . "post p ON ( a.postid=p.pid )
										 WHERE a.attachmentid IN (" . implode(",", $ids) . ")
										 AND a.userid='" . $bbuserinfo['id'] . "'");
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
				if ($attachment['threadid'])
				{
					$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "thread SET attach=attach-1 WHERE tid='" . $attachment['threadid'] . "'");
				}
			}
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN (" . implode(",", $ids) . ") AND userid='" . $bbuserinfo['id'] . "'");
		}
		$maxspace = intval($bbuserinfo['attachlimit']);
		$stats = $DB->query_first("SELECT count(*) as count, sum(filesize) as sum FROM " . TABLE_PREFIX . "attachment WHERE userid='" . $bbuserinfo['id'] . "'");
		$info['used_space'] = fetch_number_format(intval($stats['sum']), true);
		if ($maxspace > 0)
		{
			$show['limit'] = true;
			$info['totalpercent'] = $stats['sum'] ? sprintf("%.0f", (($stats['sum'] / ($maxspace * 1024)) * 100)) : 0;
			$info['img_width'] = $info['totalpercent'] > 0 ? intval($info['totalpercent']) * 2.4 : 1;
			$info['total_space'] = fetch_number_format($maxspace * 1024, true);
			if ($info['img_width'] > 250)
			{
				$info['img_width'] = 250;
			}
		}
		$pages = $forums->func->build_pagelinks(array('totalpages' => $stats['count'],
				'perpage' => $perpage,
				'curpage' => $start,
				'pagelink' => "usercp.php{$forums->sessionurl}do=attach&amp;sort={$_INPUT['sort']}",
				));
		$forums->func->check_cache('attachmenttype');
		$posts = $DB->query("SELECT a.*, t.*, p.pid
										 FROM " . TABLE_PREFIX . "attachment a
										  LEFT JOIN " . TABLE_PREFIX . "post p ON ( a.postid=p.pid )
										  LEFT JOIN " . TABLE_PREFIX . "thread t ON ( t.tid=p.threadid )
										 WHERE a.userid='" . $bbuserinfo['id'] . "'
										 ORDER BY " . $sortby . "
										 LIMIT " . $start . ", " . $perpage . "");
		while ($row = $DB->fetch_array($posts))
		{
			if ($forums->func->fetch_permissions($forums->forum->foruminfo[$row['forumid']]['canread'], 'canread') != true)
			{
				$row['title'] = $forums->lang['cannotview'];
			}
			if ($row['postid'])
			{
				$row['type'] = 'post';
			}
			else if ($row['blogid'])
			{
				$row['type'] = 'blog';
				$row['title'] = "BLOG";
			}
			else if ($row['pmid'])
			{
				$row['type'] = 'msg';
				$row['title'] = $forums->lang['pm'];
			}
			else
			{
				$row['type'] = 'noattribute';
				$row['title'] = $forums->lang['noattribute'];
			}
			$row['image'] = $forums->cache['attachmenttype'][ $row['extension'] ]['attachimg'];
			$row['shortname'] = $forums->func->fetch_trimmed_title($row['filename'], 15);
			$row['dateline'] = $forums->func->get_date($row['dateline'], 1);
			$row['filesize'] = fetch_number_format($row['filesize'], true);
			$attach[] = $row;
		}
		$forums->lang['usedspace'] = sprintf($forums->lang['usedspace'], $info['used_space']);
		$forums->lang['leftspace'] = sprintf($forums->lang['leftspace'], $info['total_space']);
		$forums->lang['totalattachs'] = sprintf($forums->lang['totalattachs'], $stats['count'], $info['totalpercent']);

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['attachmanage'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['attachmanage']);
		include $forums->func->load_template('usercp_attachment');
		exit;
	}

	function forum_setting()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		require_once(ROOT_PATH . "includes/functions_user.php");
		$this->fu = new functions_user();
		if ($bboptions['allowselectstyles'] AND count($forums->cache['style']) > 1)
		{
			$show['style'] = true;
			$select_style = "<select name='style' class='select_normal' >\n";
			foreach ($forums->cache['style'] AS $id => $style)
			{
				$selected = ($id == $bbuserinfo['style']) ? " selected='selected'" : "";
				$select_style .= "<option value='$id'{$selected}>";
				$select_style .= depth_mark($style['depth'], '--') . $style['title'];
				$select_style .= "</option>\n";
			}
			$select_style .= "</select>\n";
		}
		$offset = ($bbuserinfo['timezoneoffset'] != "") ? $bbuserinfo['timezoneoffset'] : 8;
		$time_select = "<select name='u_timezone' class='select_normal' >\n";
		foreach ($this->fu->fetch_timezone() AS $off => $words)
		{
			$selected = ($off == $offset) ? " selected='selected'" : "";
			$time_select .= "<option value='$off'{$selected}>$words</option>\n";
		}
		$time_select .= "</select>\n";
		if ($bboptions['perpagepost'] == "")
		{
			$bboptions['perpagepost'] = '5,10,15,20,25,30,35,40';
		}
		if ($bboptions['perpagethread'] == "")
		{
			$bboptions['perpagethread'] = '5,10,15,20,25,30,35,40';
		}
		list($thread_page, $post_page) = explode("&", $bbuserinfo['viewprefs']);
		if ($post_page == "")
		{
			$post_page = -1;
		}
		if ($thread_page == "")
		{
			$thread_page = -1;
		}
		$pp_a = array();
		$tp_a = array();
		$post_select = "";
		$thread_select = "";
		$pp_a[] = array('-1', $forums->lang['usedefault']);
		$tp_a[] = array('-1', $forums->lang['usedefault']);
		foreach(explode(',', $bboptions['perpagepost']) AS $n)
		{
			$n = intval(trim($n));
			$pp_a[] = array($n, $n);
		}
		foreach(explode(',', $bboptions['perpagethread']) AS $n)
		{
			$n = intval(trim($n));
			$tp_a[] = array($n, $n);
		}
		foreach($pp_a AS $id => $data)
		{
			$selected = ($data[0] == $post_page) ? " selected='selected'" : "";
			$post_select .= "<option value='{$data[0]}'{$selected}>{$data[1]}</option>\n";
		}
		foreach($tp_a AS $id => $data)
		{
			$selected = ($data[0] == $thread_page) ? " selected='selected'" : "";
			$thread_select .= "<option value='{$data[0]}'{$selected}>{$data[1]}</option>\n";
		}
		$userinfo = array();
		foreach (array('dstonoff', 'pmpop', 'hideemail', 'adminemail', 'usepm', 'emailonpm', 'usewysiwyg', 'redirecttype', 'pmover', 'pmwarnmode', 'pmwarn') AS $k)
		{
			if (!empty($bbuserinfo[ $k ]) OR $bbuserinfo[ $k ] != 0)
			{
				$userinfo[$k] = " checked='checked'";
			}
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['forumsetting'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['forumsetting']);
		include $forums->func->load_template('usercp_forum_setting');
		exit;
	}

	function userchange()
	{
		global $forums, $bboptions, $bbuserinfo;

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['changeinfo'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['changeinfo']);
		include $forums->func->load_template('usercp_change_password');
		exit;
	}

	function do_unsubscribe()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$ids = array();
		if (is_array($_INPUT['sid']))
		{
			foreach ($_INPUT['sid'] AS $id)
			{
				$id = intval($id);
				if (!$id) continue;
				$ids[] = $id;
			}
		}
		if (count($ids) > 0)
		{
			if ($_INPUT['type'] == 'thread')
			{
				$DB->shutdown_query("DELETE FROM " . TABLE_PREFIX . "subscribethread WHERE userid='" . $bbuserinfo['id'] . "' AND subscribethreadid IN (" . implode(",", $ids) . ")");
				$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=subscribethread");
			}
			if ($_INPUT['type'] == 'forum')
			{
				$DB->shutdown_query("DELETE FROM " . TABLE_PREFIX . "subscribeforum WHERE userid='" . $bbuserinfo['id'] . "' AND forumid IN (" . implode(",", $ids) . ")");
				$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=subscribeforum");
			}
		}
		else
		{
			$forums->func->standard_error("erroridinfo");
		}
		$forums->func->standard_redirect("usercp.php" . $forums->si_sessionurl);
	}

	function do_profile()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['posthash'] != $this->posthash)
		{
			$forums->func->standard_error("badposthash");
		}
		$banfilter = array();
		$day = intval($_INPUT['day']);
		$month = intval($_INPUT['month']);
		$year = intval($_INPUT['year']);

		require (ROOT_PATH . "includes/functions_codeparse.php");
		$this->parser = new functions_codeparse();
		if (($day == 0) AND ($month == 0))
		{
			$birthday = '';
		}
		else
		{
			if ($month < 10 AND $month > 0)
			{
				$month = '0' . $month;
			}
			if ($day < 10 AND $day > 0)
			{
				$day = '0' . $day;
			}
			if (($year > 1901) AND ($year < $forums->func->get_time(TIMENOW, 'Y')))
			{
				if (checkdate($month, $day, $year))
				{
					$userinfo['birthday'] = "$year-$month-$day";
				}
				else
				{
					$forums->func->standard_error("errorbirthday");
				}
			}
			else if ($year >= date('Y'))
			{
				$forums->func->standard_error("errorbirthday");
			}
			else
			{
				if (checkdate($month, $day, 1996))
				{
					$userinfo['birthday'] = "0000-$month-$day";
				}
				else
				{
					$forums->func->standard_error("errorbirthday");
				}
			}
		}

		//开始检测扩展字段
		$user_data = $forums->func->check_usrext_field();
		if ($user_data['err'])
		{
			$this->start_register($user_data['err']);
		}
		//检测结束
		$userinfo['gender'] = intval($_INPUT['gender']);
		if (is_array($user_data['user']))
		{
			$userinfo = $userinfo + $user_data['user'];
		}

		$DB->update(TABLE_PREFIX . 'user', $userinfo, "id={$bbuserinfo['id']}");
		if (is_array($user_data['userexpand']))
		{
			$DB->update(TABLE_PREFIX . 'userexpand', $user_data['userexpand'], "id={$bbuserinfo['id']}");
		}

		$safes = $DB->query("SELECT answer, question FROM " . TABLE_PREFIX . "userextra WHERE id = {$bbuserinfo['id']}");
		if ($DB->num_rows())
		{
			$has_update = true;
			$safe = $DB->fetch_array($safes);
			$bbuserinfo = array_merge($safe, $bbuserinfo);
		}

		$userinfo['question'] = trim($_INPUT['question']);
		$userinfo['answer'] = trim($_INPUT['answer']);
		if ($bbuserinfo['question'] AND $bbuserinfo['answer'])
		{
			$userinfo['newquestion'] = trim($_INPUT['newquestion']);
			$userinfo['newanswer'] = trim($_INPUT['newanswer']);

			if ($userinfo['answer'] OR $userinfo['newquestion'] OR $userinfo['newanswer'])
			{
				if ($userinfo['newquestion'] AND $userinfo['newanswer'] AND !$userinfo['answer'])
				{
					$forums->func->standard_error("originanswer");
				}
				if ($userinfo['answer'] != $bbuserinfo['answer'])
				{
					$forums->func->standard_error("originanswererror");
				}
				if ((!$userinfo['newquestion'] AND $userinfo['newanswer']) OR ($userinfo['newquestion'] AND !$userinfo['newanswer']) OR (!$userinfo['newquestion'] AND !$userinfo['newanswer']))
				{
					$forums->func->standard_error("requireanswerquestion");
				}
				if ($userinfo['newquestion'] == $userinfo['newanswer'])
				{
					$forums->func->standard_error("cannotsame");
				}
				$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "userextra SET question= '" . addslashes($userinfo['newquestion']) . "', answer= '" . addslashes($userinfo['newanswer']) . "' WHERE id='" . $bbuserinfo['id'] . "'");
			}
		}
		else
		{
			if ((!$userinfo['question'] AND $userinfo['answer']) OR ($userinfo['question'] AND !$userinfo['answer']))
			{
				$forums->func->standard_error("requireanswerquestion");
			}
			if ($userinfo['question'] AND $userinfo['answer'])
			{
				if ($userinfo['question'] == $userinfo['answer'])
				{
					$forums->func->standard_error("cannotsame");
				}
				if ($has_update)
				{
					$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "userextra SET question= '" . addslashes($userinfo['question']) . "', answer= '" . addslashes($userinfo['answer']) . "' WHERE id='" . $bbuserinfo['id'] . "'");
				}
				else
				{
					$DB->shutdown_query("INSERT INTO " . TABLE_PREFIX . "userextra (id, question, answer) VALUES (" . $bbuserinfo['id'] . ", '" . addslashes($userinfo['question']) . "', '" . addslashes($userinfo['answer']) . "')");
				}
			}
		}
		if ($bboptions['updateuserview']) //同步更新用户表
		{
			update_user_view($userinfo);
		}

		$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=editprofile");
	}

	function do_signature()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['cansignature'])
		{
			$forums->func->standard_error("noperms");
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$this->parser = new functions_codeparse();
		if (($bboptions['signaturemaxlength'] && utf8_strlen(strip_tags($_POST['post'])) > $bboptions['signaturemaxlength']) || strlen($_POST['post']) > 16777215)
		{
			$forums->func->standard_error("errorsignature");
		}
		if ($_INPUT['posthash'] != $this->posthash)
		{
			$forums->func->standard_error("badposthash");
		}
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
		$post = $bbuserinfo['usewysiwyg'] ? $_POST['post'] : utf8_htmlspecialchars($_POST['post']);
		$post = $this->parser->censoredwords($post);
		$signature = $this->parser->convert(array(
			'text' => $post,
			'allowsmilies' => 1,
			'allowcode' => intval($bboptions['signatureallowbbcode']),
		));
		if ($this->parser->error != "")
		{
			$forums->func->standard_error($this->parser->error);
		}
		if ($_INPUT['delete'])
		{
			$this->clean_signature($bbuserinfo['id']);
			$has_cleaned = true;
		}

		if ($bboptions['allowuploadsigimg'] AND $bbuserinfo['cansigimg'])
		{
			if ($_FILES['sig_img']['name'] != "" AND ($_FILES['sig_img']['name'] != "none"))
			{
				list($p_width, $p_height) = explode("x", $bboptions['sigimgdimension']);
				$path = split_todir($bbuserinfo['id'], $bboptions['uploadfolder'] . '/user');
				checkdir($path[0], $path[1] + 1);
				$path = $path[0];
				if (!$has_cleaned)
				{
					$this->clean_signature($bbuserinfo['id']);
				}
				$real_name = 's-' . $bbuserinfo['id'];
				$real_type = 2;
				require_once(ROOT_PATH . 'includes/functions_upload.php');
				$upload = new functions_upload();
				$upload->filepath = $path;
				$upload->allow_extension = array('jpg', 'jpeg', 'gif', 'png');
				if ($bbuserinfo['canuseflash'])
				{
					$upload->allow_extension[] = 'swf';
				}
				$fileinfo['name'] = $_FILES['sig_img']['name'];
				$fileinfo['size'] = $_FILES['sig_img']['size'];
				$fileinfo['type'] = $_FILES['sig_img']['type'];
				$fileinfo['tmp_name'] = $_FILES['sig_img']['tmp_name'];
				$fileinfo['filename'] = 's-' . $bbuserinfo['id'];
				$fileinfo['num'] = 0;
				$upload->upload_file($fileinfo);
				if ($upload->error_no)
				{
					switch ($upload->error_no)
					{
						case 1:
							$forums->func->standard_error("errorupload1");
						case 2:
							$forums->func->standard_error("errorupload2");
						case 3:
							$forums->func->standard_error("errorupload3");
						case 4:
							$forums->func->standard_error("errorupload4");
					}
				}
				$real_name = $upload->parsed_file_name[0];
				if ($p_width AND $p_height AND $upload->file_extension != '.swf')
				{
					require_once(ROOT_PATH . 'includes/functions_image.php');
					$image = new functions_image();
					$image->filepath = $path;
					$image->filename = $real_name;
					$image->thumb_filename = 'thumb_' . $bbuserinfo['id'];
					$image->thumbswidth = $p_width;
					$image->thumbsheight = $p_height;
					$return = $image->generate_thumbnail();
					$im['img_width'] = $return['thumbwidth'];
					$im['img_height'] = $return['thumbheight'];
					if (strstr($return['thumblocation'], 'thumb_'))
					{
						@unlink($path . "/" . $real_name);
						$real_name = 's-' . $bbuserinfo['id'] . '.' . $image->file_extension;
						@rename($path . "/" . $return['thumblocation'], $path . "/" . $real_name);
						@chmod($path . "/" . $real_name, 0777);
					}
				}
				else
				{
					if (! $img_size = @GetImageSize($path . '/' . $real_name))
					{
						$img_size[0] = $p_width;
						$img_size[1] = $p_height;
					}
					$w = $img_size[0] ? intval($img_size[0]) : $p_width;
					$h = $img_size[1] ? intval($img_size[1]) : $p_height;
					$im['img_width'] = $w > $p_width ? $p_width : $w;
					$im['img_height'] = $h > $p_height ? $p_height : $h;
				}
				$real_choice = $real_name;
				if (!preg_match("#<!--sig_img-->(.+?)<!--sig_img1-->#", $bbuserinfo['signature']))
				{
					$signature .= "<!--sig_img--><div><img src='{\$signature_path}" . $real_choice . "' width='" . $im['img_width'] . "' height='" . $im['img_height'] . "' /></div><!--sig_img1-->";
				}
			}
			if (preg_match("#<!--sig_img-->(.+?)<!--sig_img1-->#", $bbuserinfo['signature'], $match) AND !$_INPUT['delete'])
			{
				$signature .= $match[0];
			}
		}

		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET signature = '" . addslashes($signature) . "' WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=editsignature");
	}

	function avatar_gallery()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$avatar_gallery = array();
		$selectedavatar = preg_replace("/[^\w\s_\-]/", "", $_INPUT['av_cat']);
		$thiscategories = false;
		$currentcategories = "";
		$dh = opendir(ROOT_PATH . 'images/avatars');
		while ($file = readdir($dh))
		{
			if (is_dir(ROOT_PATH . 'images/avatars' . "/" . $file))
			{
				if ($file != "." && $file != "..")
				{
					if ($file == $selectedavatar)
					{
						$thiscategories = true;
						$currentcategories = str_replace("_", " ", $file);
					}
					$categories[] = array($file, str_replace("_", " ", $file));
				}
			}
		}
		closedir($dh);
		reset($categories);
		if ($selectedavatar)
		{
			if ($thiscategories != true)
			{
				$forums->func->standard_error("erroravatarcategories");
			}
			$currentavatar = "/" . $selectedavatar;
		}
		$dh = opendir(ROOT_PATH . 'images/avatars' . $currentavatar);
		while ($file = readdir($dh))
		{
			if (! preg_match("/^..?$|^index|^\.ds_store|^\.htaccess/i", $file))
			{
				if (is_file(ROOT_PATH . "images/avatars" . $currentavatar . "/" . $file))
				{
					if (preg_match("/\.(gif|jpg|jpeg|png)$/i", $file))
					{
						$galleryimages[] = array ('file' => $file,
							'encode' => urlencode($file),
							'name' => str_replace('_', ' ', preg_replace('/^(.*)\.\w+$/', '\\1', $file)),
							);
					}
				}
			}
		}
		if (is_array($galleryimages) AND count($galleryimages))
		{
			natcasesort($galleryimages);
			reset($galleryimages);
		}
		closedir($dh);
		$colspan = 5;
		$gal_found = count($galleryimages);
		$posthash = $this->posthash;
		$current_folder = urlencode($selectedavatar);
		$c = 0;
		$avatar_list = '';
		if (is_array($galleryimages) AND count($galleryimages))
		{
			$avatar_list_show = 1;
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['avatargallery'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['avatargallery']);
		include $forums->func->load_template('usercp_avatar_category');
		exit;
	}

	function set_internal_avatar()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['posthash'] != $this->posthash)
		{
			$forums->func->standard_error("badposthash");
		}
		$real_choice = 'noavatar';
		$real_dims = '';
		$real_dir = "";
		$save_dir = "";
		$current_folder = preg_replace("/[^\s\w_-]/", "", rawurldecode($_INPUT['current_folder']));
		$selected_avatar = preg_replace("/[^\s\w\._\-\[\]\(\)]/" , "", rawurldecode($_INPUT['avatar']));
		if ($current_folder != "")
		{
			$real_dir = "/" . $current_folder;
			$save_dir = $current_folder . "/";
		}
		$avatar_gallery = array();
		$avatar_dir = ROOT_PATH . 'images/avatars';
		$dh = opendir($avatar_dir . $real_dir);
		while ($file = readdir($dh))
		{
			if (!preg_match("/^..?$|^index/i", $file))
			{
				$avatar_gallery[] = $file;
			}
		}
		closedir($dh);
		$final_string = $save_dir . $selected_avatar;
		if (!in_array($selected_avatar, $avatar_gallery))
		{
			$forums->func->standard_error("erroravatar");
		}

		//头像生成小图

		$path = split_todir($bbuserinfo['id'], $bboptions['uploadfolder'] . '/user');
		checkdir($path[0], $path[1] + 1);
		@copy($avatar_dir . '/' . $final_string, $path[0] . '/' . $selected_avatar);
		$forums->func->bulid_avatars($selected_avatar, $bbuserinfo['id']);
		@unlink($path[0] . '/' . $selected_avatar);
		$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=editavatar");
	}
	function do_avatar()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$real_type = "";
		if ($_INPUT['remove'])
		{
			$this->clean_avatars($bbuserinfo['id']);
			$DB->update(TABLE_PREFIX . 'user', array('avatar' => 0), 'id=' . $bbuserinfo['id']);
			if ($forums->sessionid)
			{
				$DB->update(TABLE_PREFIX . 'session', array('avatar' => 1), "userid = {$bbuserinfo['id']} AND id = '" . $forums->sessionid . "'");
			}
			$forums->func->standard_redirect("usercp.php?{$forums->sessionurl}do=editavatar");
		}
		if ($_INPUT['posthash'] != $this->posthash)
		{
			$forums->func->standard_error("badposthash");
		}
		//检查允许上传的头像附件类型
		$forums->func->check_cache('attachmenttype');
		$allow_extension = array();
		if (is_array($forums->cache['attachmenttype']) AND count($forums->cache['attachmenttype']))
		{
			foreach($forums->cache['attachmenttype'] AS $idx => $data)
			{
				if ($data['useavatar'])
				{

					$allow_extension[] = strtolower($data['extension']);
				}
			}
		}
		if (!$allow_extension)
		{
			$forums->func->standard_error("cannotupavatar");
		}
		if (preg_match("/^http:\/\/$/i", $_INPUT['avatarurl']))
		{
			$_INPUT['avatarurl'] = "";
		}
		$path = split_todir($bbuserinfo['id'], $bboptions['uploadfolder'] . '/user');
		checkdir($path[0], $path[1] + 1);
		$path = $path[0];
		$thumb_suffix = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
		if (empty($_INPUT['avatarurl']))
		{
			if ($_FILES['upload_avatar']['name'] != "" && ($_FILES['upload_avatar']['name'] != "none"))
			{
				if (($bbuserinfo['canuseavatar'] != 1) || ($bboptions['avatamaxsize'] < 1))
				{
					$forums->func->standard_error("cannotupavatar");
				}
				//文件真实类型的检查，这里是头像，只需检测图片的类型
				$file_suffix = strrchr($_FILES['upload_avatar']['name'], '.');
				$file_true_type = @GetImageSize($_FILES['upload_avatar']['tmp_name']);
				$file_true_type = $thumb_suffix[$file_true_type[2]];
				if (substr($file_suffix, 1) != $file_true_type && !in_array($file_true_type, $thumb_suffix))
				{
					$forums->lang['filetypeerror'] = sprintf($forums->lang['filetypeerror'], $file_true_type);
					$forums->func->standard_error("filetypeerror");
				}
				//上传有系统大小限制
				if ($_FILES['upload_avatar']['size'] > ($bboptions['avatamaxsize'] * 1024 * 8))
				{
					$forums->func->standard_error("uploadpasslimit");
				}
				$real_name = 'a-' . $bbuserinfo['id'] . '-0.jpg';
				require_once(ROOT_PATH . 'includes/functions_upload.php');
				$upload = new functions_upload();
				$upload->filepath = $path;
				$upload->maxfilesize = ($bboptions['avatamaxsize'] * 1024) * 8;
				$upload->allow_extension = $allow_extension;
				$fileinfo['name'] = $real_name;
				$fileinfo['size'] = $_FILES['upload_avatar']['size'];
				$fileinfo['type'] = $_FILES['upload_avatar']['type'];
				$fileinfo['tmp_name'] = $_FILES['upload_avatar']['tmp_name'];
				$fileinfo['filename'] = '';
				$fileinfo['num'] = 0;
				$upload->upload_file($fileinfo);

				if ($upload->error_no)
				{
					switch ($upload->error_no)
					{
						case 1:
							$forums->func->standard_error("errorupload1");
						case 2:
							$forums->func->standard_error("errorupload2");
						case 3:
							$forums->func->standard_error("errorupload3");
						case 4:
							$forums->func->standard_error("errorupload4");
					}
				}
				$real_type =  1; //自定义上传图像
				$real_choice = substr($real_name, 1);
			}
			else
			{
				$forums->func->standard_error("selectupavatar");
			}
		}
		else
		{
			$_INPUT['avatarurl'] = trim($_INPUT['avatarurl']);
			if (preg_match("/[?&;]/", $_INPUT['avatarurl']))
			{
				$forums->func->standard_error("errorurl");
			}
			$ext = explode (',', $bboptions['avatarextension']);
			$av_ext = preg_replace("/^.*\.(\S+)$/", "\\1", $_INPUT['avatarurl']);
			$av_ext = strtolower($av_ext);
			if (!in_array($av_ext, $allow_extension))
			{
				$forums->func->standard_error("errorextension");
			}

			$real_name = 'a-' . $bbuserinfo['id'] . '-0.jpg';

			$content = @file_get_contents($_INPUT['avatarurl']);

			if ($content)
			{
				//大小超过系统设置
				if(strlen($content) > ($bboptions['avatamaxsize'] * 1024 * 8))
				{
					$forums->func->standard_error("uploadpasslimit");
				}
				file_write($path . '/' . $real_name, $content, 'wb');
			}
			else
			{
				$forums->func->standard_error("remotegeterror");
			}
		}

		$DB->update(TABLE_PREFIX . 'user', array('avatar' => 1), 'id=' . $bbuserinfo['id']);
		if ($forums->sessionid)
		{
			$DB->update(TABLE_PREFIX . 'session', array('avatar' => 1), "userid = {$bbuserinfo['id']} AND id = '" . $forums->sessionid . "'");
		}
		//头像生成小图
		$forums->func->bulid_avatars($real_name, $bbuserinfo['id']);
		$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=editavatar");
	}

	function do_forum_setting()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['posthash'] != $this->posthash)
		{
			$forums->func->standard_error("badposthash");
		}
		$upstyle = '';
		$timezone = intval($_INPUT['u_timezone']);
		if ($bboptions['allowselectstyles'])
		{
			$styleid = intval($_INPUT['style']);
			$style = $forums->cache['style'][$styleid]['userselect'] ? $styleid : $bbuserinfo['style'];
			$upstyle = "style='" . $style . "', ";
		}
		$bbuserinfo['options'] = $forums->func->convert_array_to_bits($_INPUT['options']);
		if ($bboptions['perpagepost'] == "")
		{
			$bboptions['perpagepost'] = '5,10,15,20,25,30,35,40';
		}
		if ($bboptions['perpagethread'] == "")
		{
			$bboptions['perpagethread'] = '5,10,15,20,25,30,35,40';
		}
		$bboptions['perpagepost'] .= ",-1,";
		$bboptions['perpagethread'] .= ",-1,";
		if (! preg_match("/(^|,)" . $_INPUT['postpage'] . ",/", $bboptions['perpagepost']))
		{
			$_INPUT['postpage'] = '-1';
		}
		if (! preg_match("/(^|,)" . $_INPUT['threadpage'] . ",/", $bboptions['perpagethread']))
		{
			$_INPUT['threadpage'] = '-1';
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET {$upstyle}timezoneoffset=" . $_INPUT['u_timezone'] . ", options=" . $bbuserinfo['options'] . ", viewprefs='" . $_INPUT['threadpage'] . "&" . $_INPUT['postpage'] . "' WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect("usercp.php{$forums->sessionurl}do=setting");
	}

	function do_change()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$currentpassword = trim($_INPUT['currentpassword']);
		$newpassword = trim($_INPUT['newpassword']);
		$newpasswordconfirm = trim($_INPUT['newpasswordconfirm']);
		$email = strtolower(trim($_INPUT['email']));
		$emailcharset = strtolower(trim($_INPUT['emailcharset']));
		$emailconfirm = strtolower(trim($_INPUT['emailconfirm']));
		if ($_INPUT['currentpassword'] == "" OR (empty($newpassword) AND empty($email)))
		{
			$forums->func->standard_error("plzinputallform");
		}
		$salt = $bbuserinfo['salt'];
		if ($bbuserinfo['password'] != md5(md5($currentpassword) . $salt))
		{
			$forums->func->standard_error("errorcurrentpassword");
		}
		if (($email OR $emailconfirm) AND $email != $emailconfirm)
		{
			$forums->func->standard_error("erroremailconfirm");
		}
		if (($newpasswordconfirm OR $newpasswordconfirm) AND $newpassword != $newpasswordconfirm)
		{
			$forums->func->standard_error("errorpassword");
		}
		$userinfo = $bbuserinfo;
		if ($newpassword)
		{
			$userinfo['password'] = $newpassword;
			$newpassword = md5(md5($newpassword) . $salt);
			$DB->update(TABLE_PREFIX . 'user', array('password' => $newpassword), "id = {$bbuserinfo['id']}");
		}
		if ($emailcharset)
		{
			$emailcharset = str_replace(' ', '', $emailcharset);
			$DB->update(TABLE_PREFIX . 'user', array('emailcharset' => $emailcharset), "id = {$bbuserinfo['id']}");
		}
		if ($email)
		{
			if (!$bbuserinfo['caneditprofile'])
			{
				$forums->func->standard_error("noperms");
			}
			if (strlen($email) < 6)
			{
				$forums->func->standard_error('erroremail');
			}
			$email = clean_email($email);
			if ($email == "")
			{
				$forums->func->standard_error("erroremail");
			}
			if ($DB->query_first("SELECT id FROM " . TABLE_PREFIX . "user WHERE email='" . $email . "'"))
			{
				$forums->func->standard_error("mailalreadyexist");
			}
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "banfilter WHERE type='email'");
			while ($r = $DB->fetch_array())
			{
				$banemail = $r['content'];
				$banemail = preg_replace("/\*/", '.*' , $banemail);
				if (preg_match("/$banemail/", $email))
				{
					$forums->func->standard_error("bademail");
				}
			}
			require_once(ROOT_PATH . "includes/functions_email.php");
			$this->email = new functions_email();
			if ($bboptions['moderatememberstype'] AND !$bbuserinfo['cancontrolpanel'])
			{
				$activationkey = md5($forums->func->make_password() . TIMENOW);
				$DB->insert(TABLE_PREFIX . 'useractivation', array(
					'useractivationid' => $activationkey,
					'userid' => $bbuserinfo['id'],
					'usergroupid' => $bbuserinfo['usergroupid'],
					'tempgroup' => 3,
					'dateline' => TIMENOW,
					'type' => 3,
					'host' => IPADDRESS
				));
				$DB->update(TABLE_PREFIX . 'user', array('usergroupid' => 1, 'email' => $email), "id = {$bbuserinfo['id']}");
				if ($forums->sessionid)
				{
					$DB->update(TABLE_PREFIX . 'session', array('username' => '', 'userid' => 0, 'usergroupid' => 1, 'avatar' => 0), "userid = {$bbuserinfo['id']} AND id = '" . $forums->sessionid . "'");
				}
				$forums->func->set_cookie('password' , '-1', 0);
				$forums->func->set_cookie('userid' , '-1', 0);
				$forums->func->set_cookie('sessionid' , '-1', 0);
				if ($emailcharset)
				{
					$emailcharset = str_replace(' ', '', $emailcharset);
				}
				else
				{
					$emailcharset = $bbuserinfo['emailcharset'];
				}
				$this->email->char_set = $emailcharset?$emailcharset:'GBK';
				$message = $this->email->fetch_email_changeemail(array('link' => $bboptions['bburl'] . "/register.php?do=validate&type=newemail&u=" . urlencode($bbuserinfo['id']) . "&a=" . urlencode($activationkey),
						'name' => $bbuserinfo['name'],
						'linkpage' => $bboptions['bburl'] . "/register.php?do=changeemail",
						'id' => $bbuserinfo['id'],
						'code' => $activationkey,
						)
					);
				$this->email->build_message($message);
				$forums->lang['emailchangetitle'] = sprintf($forums->lang['emailchangetitle'], $bboptions['bbtitle']);
				$this->email->subject = $forums->lang['emailchangetitle'];
				$this->email->to = $email;
				$this->email->send_mail();
				$forums->func->redirect_screen($forums->lang['redirectinfo'], "register.php{$forums->sessionurl}do=changeemail");
			}
			else
			{
				$DB->update(TABLE_PREFIX . 'user', array('email' => $email), "id = {$bbuserinfo['id']}");
			}
			$userinfo['email'] = $email;
		}

		if ($bboptions['updateuserview'] && $userinfo) //同步更新用户表
		{
			update_user_view($userinfo);
		}
		$forums->func->standard_redirect("usercp.php" . $forums->si_sessionurl);
	}

	function parse_data($thread)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$last_time = $this->threadread[$thread['tid']] > $_INPUT['lastvisit'] ? $this->read_array[$thread['tid']] : $_INPUT['lastvisit'];
		$maxposts = $bboptions['maxposts'] ? $bboptions['maxposts'] : '10';
		if ($thread['attach'])
		{
			$thread['attach_img'] = 1;
			$thread['alt_attach'] = $forums->lang['attachs'];
		}
		$thread['lastposter'] = $thread['lastposterid'] ? $forums->func->fetch_user_link($thread['lastposter'], $thread['lastposterid']) : "-" . $thread['lastposter'] . "-";
		$thread['postusername'] = $thread['postuserid'] ? $forums->func->fetch_user_link($thread['postusername'], $thread['postuserid']) : "-" . $thread['postusername'] . "-";
		if ($thread['pollstate'])
		{
			$thread['prefix'] = $bboptions['pollprefix'] . ' ';
		}
		$thread['foldericon'] = $forums->func->folder_icon($thread, $last_time);
		$thread['dateline'] = $forums->func->get_date($thread['dateline'], 2);
		if ($bbuserinfo['is_mod'])
		{
			$thread['post'] += intval($thread['modposts']);
		}
		$thread['showpages'] = $forums->func->build_threadpages(
			array('id' => $thread['tid'],
				'totalpost' => $thread['post'],
				'perpage' => $maxposts,
				)
			);
		$thread['post'] = fetch_number_format($thread['post']);
		$thread['views'] = fetch_number_format($thread['views']);
		$thread['lastpost'] = $forums->func->get_date($thread['lastpost'], 1);
		if ($thread['open'] == 2)
		{
			$t_array = explode("&", $thread['moved']);
			$thread['tid'] = $t_array[0];
			$thread['forumid'] = $t_array[1];
			$thread['title'] = $thread['title'];
			$thread['views'] = '--';
			$thread['post'] = '--';
			$thread['prefix'] = $bboptions['movedprefix'] . " ";
			$thread['gotonewpost'] = "";
		}
		return $thread;
	}

	function clean_avatars($id)
	{
		global $bboptions;
		$userdir = split_todir($id, $bboptions['uploadfolder'] . '/user');
		@unlink($userdir[0] . '/a-' . $id . '-0.jpg');
		@unlink($userdir[0] . '/a-' . $id . '-1.jpg');
		@unlink($userdir[0] . '/a-' . $id . '-2.jpg');
	}

	function clean_signature($id)
	{
		global $bboptions;
		$path = split_todir($id, $bboptions['uploadfolder'] . '/user');
		foreach(array('swf', 'jpg', 'jpeg', 'gif', 'png') as $extension)
		{
			if (@file_exists($path[0] . "/s-" . $id . "." . $extension))
			{
				@unlink($path[0] . "/s-" . $id . "." . $extension);
			}
		}
	}
}

$output = new usercp();
$output->show();

?>