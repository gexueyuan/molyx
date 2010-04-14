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
define('THIS_SCRIPT', 'announcement');
require_once('./global.php');

class announce
{
	var $ids = array();
	function show()
	{
		global $_INPUT, $forums;
		$forums->func->load_lang('moderate');
		switch ($_INPUT['do'])
		{
			case 'doannouncement':
				$this->announcementform();
				break;
			case 'updateannouncement':
				$this->updateannouncement();
				break;
			case 'deleteannouncement':
				$this->deleteannouncement();
				break;
			case 'showall':
				$this->all_announcement();
				break;
			default:
				$this->announcement();
				break;
		}
	}

	function all_announcement()
	{
		global $_INPUT, $DB, $bbuserinfo, $bboptions, $forums;
		$forums->func->load_lang('showthread');
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		require ROOT_PATH . 'includes/functions_showthread.php';
		$show = new functions_showthread();
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		$codeparse = new functions_codeparse();
		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$_INPUT['id'] = intval($_INPUT['id']);
		$start = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$where = '';
		$perpage = 5;
		$pages = '';
		if (!$bbuserinfo['supermod'])
		{
			$where = ' WHERE a.userid = ' . intval($bbuserinfo['id']);
		}
		$acount = $DB->query_first('SELECT COUNT(*) AS count
			FROM ' . TABLE_PREFIX . 'announcement a' . $where);
		$pages = $forums->func->build_pagelinks(array(
			'totalpages' => $acount['count'],
			'perpage' => $perpage,
			'curpage' => $start,
			'pagelink' => "announcement.php{$forums->sessionurl}do=showall",
		));
		$result = $DB->query('SELECT a.pagetext, a.forumid, a.userid, a.allowhtml, a.views, a.startdate, a.enddate, a.id AS announceid, a.title AS announcetitle, a.active, u.*
			FROM ' . TABLE_PREFIX . 'announcement a
				LEFT JOIN ' . TABLE_PREFIX . "user u
					ON a.userid = u.id
					{$where}
			ORDER BY enddate DESC
			LIMIT $start, $perpage");
		if ($DB->num_rows())
		{
			while ($announce = $DB->fetch_array($result))
			{
				$announce['title'] = strip_tags($announce['title']);
				$announce['pagetext'] = preg_replace("/<!--emule1-->(.+?)<!--emule2-->/ie", "\$show->paste_emule('\\1')", $announce['pagetext']);
				$announce['pagetext'] = preg_replace("/<!--emule1-->(.+?)<!--emule2-->/ie", "\$show->paste_emule('\\1')", $announce['pagetext']);
			//处理code
				if (strpos($announce['pagetext'], '[code') !== false)
				{
					$announce['pagetext'] = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies" , "\$codeparse->paste_code('\\1', '\\2')" , $announce['pagetext']);
				}
				//处理引用
				if (strpos($announce['pagetext'], '[quote') !== false)
				{
					$announce['pagetext'] = preg_replace("#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$codeparse->parse_quotes('\\1')" , $announce['pagetext']);
				}

				//处理flash
				if (strpos($announce['pagetext'], '[FLASH') !== false)
				{
					$pregfind = array("#(\[flash\])(.+?)(\[\/flash\])#ie", "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#ie");
					$pregreplace = array("\$codeparse->parse_flash('','','\\2')", "\$codeparse->parse_flash('\\2','\\4','\\6')");
					$announce['pagetext'] = preg_replace($pregfind, $pregreplace, $announce['pagetext']);
				}

				$announce['pagetext'] = textparse::convert_text($announce['pagetext'], ($announce['allowhtml']));
				$announce['avatartype'] = 1;
				$announce['user'] = $forums->func->fetch_user($announce);
				if ($announce['startdate'] and $announce['enddate'])
				{
					$announce['dateline'] = " ( " . $forums->lang['from'] . " " . $forums->func->get_time($announce['startdate'], 'Y-m-d') . " " . $forums->lang['to'] . " " . gmdate('Y-m-d', $announce['enddate']) . " ) ";
				}
				else if ($announce['startdate'] and ! $announce['enddate'])
				{
					$announce['dateline'] = " ( " . $forums->lang['from'] . " " . $forums->func->get_time($announce['startdate'], 'Y-m-d') . " ) ";
				}
				else
				{
					$announce['dateline'] = '';
				}
				if ($announce['enddate'] && $announce['enddate'] < TIMENOW)
				{
					$announce['dateline'] .= $forums->lang['overdue'];
				}
				elseif ($announce['startdate'] && $announce['startdate'] > TIMENOW)
				{
					$announce['dateline'] .= $forums->lang['notyetstart'];
				}
				elseif(!$announce['active'])
				{
					$announce['dateline'] .= $forums->lang['notyetuse'];
				}
				$announce['user']['grouptitle'] = $forums->lang[$announce['user']['grouptitle']];
				$announce['user']['title'] = $forums->lang[$announce['user']['title']];
				$this->ids[] = $announce['announceid'];
				$announcement[] = $announce;
			}
		}
		else
		{
			$forums->func->standard_error("cannotviewannounce");
		}
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		$pagetitle = $forums->lang['announcement'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['announcement']);
		unset($show);
		$show['announce'] = false;
		include $forums->func->load_template('announcement');
		exit;
	}

	function announcement()
	{
		global $_INPUT, $DB, $bbuserinfo, $bboptions, $forums;
		$forums->func->load_lang('showthread');
		require ROOT_PATH . 'includes/functions_showthread.php';
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$codeparse = new functions_codeparse();
		$show = new functions_showthread();
		$_INPUT['id'] = intval($_INPUT['id']);
		$start = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$where = '';
		$perpage = 5;
		$pages = '';
		if ($_INPUT['id'])
		{
			$where = ' AND a.id = ' . intval($_INPUT['id']);
		}
		else
		{
			$acount = $DB->query_first('SELECT COUNT(*) AS count
				FROM ' . TABLE_PREFIX . 'announcement a
				WHERE active = 1
					AND (startdate = 0
						OR startdate < ' . TIMENOW . ')
					AND (enddate = 0
						OR enddate > ' . TIMENOW . ")
					$where");
			$pages = $forums->func->build_pagelinks(array(
				'totalpages' => $acount['count'],
				'perpage' => $perpage,
				'curpage' => $start,
				'pagelink' => "announcement.php{$forums->sessionurl}",
			));
		}
		$result = $DB->query('SELECT a.pagetext, a.forumid, a.userid, a.allowhtml, a.views, a.startdate, a.enddate, a.id AS announceid, a.title AS announcetitle, u.*
			FROM ' . TABLE_PREFIX . 'announcement a
				LEFT JOIN ' . TABLE_PREFIX . 'user u
					ON a.userid = u.id
			WHERE active = 1
				AND (startdate = 0
					OR startdate < ' . TIMENOW . ')
				AND (enddate = 0
					OR enddate > ' . TIMENOW . ")
				$where
			ORDER BY enddate DESC
			LIMIT $start, $perpage");
		$announcement = array();
		while ($announce = $DB->fetch_array($result))
		{
			$announce['title'] = strip_tags($announce['title']);
			$pass = false;
			if ($announce['forumid'] == -1)
			{
				$pass = true;
			}
			else
			{
				$tmp = explode(',', $announce['forumid']);
				if (!is_array($tmp) && !count($tmp))
				{
					$pass = false;
				}
				else
				{
					foreach($tmp as $id)
					{
						if ($forums->forum->foruminfo[$id]['id'])
						{
							$pass = true;
							break;
						}
					}
				}
			}
			$announce['pagetext'] = preg_replace("/<!--emule1-->(.+?)<!--emule2-->/ie", "\$show->paste_emule('\\1')", $announce['pagetext']);
		//处理code
			if (strpos($announce['pagetext'], '[code') !== false)
			{
				$announce['pagetext'] = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies" , "\$codeparse->paste_code('\\1', '\\2')" , $announce['pagetext']);
			}
			//处理引用
			if (strpos($announce['pagetext'], '[quote') !== false)
			{
				$announce['pagetext'] = preg_replace("#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$codeparse->parse_quotes('\\1')" , $announce['pagetext']);
			}

			//处理flash
			if (strpos($announce['pagetext'], '[FLASH') !== false)
			{
				$pregfind = array("#(\[flash\])(.+?)(\[\/flash\])#ie", "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#ie");
				$pregreplace = array("\$codeparse->parse_flash('','','\\2')", "\$codeparse->parse_flash('\\2','\\4','\\6')");
				$announce['pagetext'] = preg_replace($pregfind, $pregreplace, $announce['pagetext']);
			}

			$announce['pagetext'] = textparse::convert_text($announce['pagetext'], ($announce['allowhtml']));
			if ($pass != 1)
			{
				$forums->func->standard_error("cannotviewannounce");
			}
			$announce['avatartype'] = 1;
			$announce['user'] = $forums->func->fetch_user($announce);
			if ($announce['startdate'] and $announce['enddate'])
			{
				$announce['dateline'] = " ( " . $forums->lang['from'] . " " . $forums->func->get_time($announce['startdate'], 'Y-m-d') . " " . $forums->lang['to'] . " " . gmdate('Y-m-d', $announce['enddate']) . " ) ";
			}
			else if ($announce['startdate'] and ! $announce['enddate'])
			{
				$announce['dateline'] = " ( " . $forums->lang['from'] . " " . $forums->func->get_time($announce['startdate'], 'Y-m-d') . " ) ";
			}
			else
			{
				$announce['dateline'] = '';
			}
			$announce['user']['grouptitle'] = $forums->lang[$announce['user']['grouptitle']];
			$announce['user']['title'] = $forums->lang[$announce['user']['title']];
			$this->ids[] = $announce['announceid'];
			$announcement[] = $announce;
		}
		if (count($this->ids))
		{
			$DB->shutdown_update(TABLE_PREFIX . 'announcement', array('views' => array(1, '+')), $DB->sql_in('id', $this->ids));
		}
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		$pagetitle = $forums->lang['announcement'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['announcement']);
		unset($show);
		$show['announce'] = false;
		include $forums->func->load_template('announcement');
		exit;
	}


	function announcementform($errors = '')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$_INPUT['id'] = intval($_INPUT['id']);
		$forum_html = '';
		if ($_INPUT['id'])
		{
			$button = $forums->lang['editannouncement'];
			if (!$announce = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE id=" . $_INPUT['id'] . ""))
			{
				$forums->func->standard_error("cannotfindannounce");
			}
			if ($bbuserinfo['id'] != $announce['userid'] && !$bbuserinfo['supermod'])
			{
				$forums->func->standard_error("noperms");
			}
			$pagetitle = $forums->lang['editannouncement'] . " - " . $bboptions['bbtitle'];
			$nav = array("<a href='announcement.php{$forums->sessionurl}do=announcement'>" . $forums->lang['announcement'] . "</a>", $forums->lang['editannouncement']);
		}
		else
		{
			$button = $forums->lang['addannouncement'];
			$announce = array('active' => 1);
			$pagetitle = $forums->lang['addannouncement'] . " - " . $bboptions['bbtitle'];
			$nav = array("<a href='announcement.php{$forums->sessionurl}do=announcement'>" . $forums->lang['announcement'] . "</a>", $forums->lang['addannouncement']);
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$parser = new functions_codeparse();
		$announce['title'] = $announce['title'] ? $parser->unconvert($announce['title']) : $_POST['title'];
		$announce['pagetext'] = $announce['pagetext'] ? $parser->unconvert($announce['pagetext'], 1, 1, 0) : $_POST['post'];
		$announce['pagetext'] = preg_replace("#<br.*>#siU", "\n", $announce['pagetext']);
		$announce['forumids'] = $announce['forumid'] ? explode(",", $announce['forumid']) : $_POST['announceforum'];
		$announce['startdate'] = $announce['startdate'] ? $forums->func->get_time($announce['startdate'], 'Y-m-d') : ($_POST['startdate'] ? $_POST['startdate'] : $forums->func->get_time(TIMENOW, 'Y-m-d'));
		$announce['enddate'] = $announce['enddate'] ? $forums->func->get_time($announce['enddate'], 'Y-m-d') : ($_POST['enddate'] ? $_POST['enddate'] : $forums->func->get_time(TIMENOW + 2592000, 'Y-m-d'));
		if ($bbuserinfo['supermod'])
		{
			$forum_html .= "<option value='-1'>" . $forums->lang['allforum'] . "</option><optgroup label='-----------------------'>" . $forums->forum->forum_jump();
		}
		if (is_array($bbuserinfo['_moderator']))
		{
			foreach ($bbuserinfo['_moderator'] AS $id => $value)
			{
				$forum_html .= "<option value='" . $id . "'>" . $forums->forum->foruminfo[$id]['name'] . "</option>";
			}
		}
		if (is_array($announce['forumids']) AND count($announce['forumids']))
		{
			foreach($announce['forumids'] AS $f)
			{
				$forum_html = preg_replace('#<option[^>]+value=(\'|")(' . $f . ')(\\1)>#siU', "<option value='\\2' selected='selected'>", $forum_html);
			}
		}
		$bboptions['quickeditorloadmode'] = 1;
		load_editor_js('', 'quick');
		$forum_html .= "</optgroup>";
		$announce['active_checked'] = $announce['active'] ? 'checked="checked"' : '';
		$announce['allowhtml'] = $announce['allowhtml'] ? 'checked="checked"' : '';
		$announce['pagetext'] = br2nl($announce['pagetext']);

		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		include $forums->func->load_template('add_announcement');
		exit;
	}

	function updateannouncement()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		if (! $_INPUT['title'] OR ! $_INPUT['post'])
		{
			return $this->announcementform($forums->lang['requireannouncement']);
		}
		$forumids = '';
		if (is_array($_INPUT['announceforum']) AND count($_INPUT['announceforum']))
		{
			if (in_array('-1', $_INPUT['announceforum']) AND $bbuserinfo['supermod'])
			{
				$forumids = '-1';
			}
			else
			{
				if ($bbuserinfo['supermod'])
				{
					$forumids = implode(",", $_INPUT['announceforum']);
				}
				else
				{
					foreach ($bbuserinfo['_moderator'] AS $id => $value)
					{
						if (in_array($id, $_INPUT['announceforum']))
						{
							$ids[] = $id;
						}
					}
					$forumids = implode(",", $ids);
				}
			}
		}
		if (empty($forumids))
		{
			return $this->announcementform($forums->lang['selectforum']);
		}
		$startdate = 0;
		$enddate = 0;
		if (strstr($_INPUT['startdate'], '-'))
		{
			$start_array = explode('-', $_INPUT['startdate']);
			if ($start_array[0] AND $start_array[1] AND $start_array[2])
			{
				if (!checkdate($start_array[1], $start_array[2], $start_array[0]))
				{
					return $this->announcementform($forums->lang['errorstartdate']);
				}
			}
			else
			{
				return $this->announcementform($forums->lang['errorenddate']);
			}
			$startdate = $forums->func->mk_time(0, 0, 1, $start_array[1], $start_array[2], $start_array[0]);
		}
		if (strstr($_INPUT['enddate'], '-'))
		{
			$end_array = explode('-', $_INPUT['enddate']);
			if ($end_array[0] AND $end_array[1] AND $end_array[2])
			{
				if (!checkdate($end_array[1], $end_array[2], $end_array[0]))
				{
					return $this->announcementform($forums->lang['errorenddate']);
				}
			}
			else
			{
				return $this->announcementform($forums->lang['errorenddate']);
			}
			$enddate = $forums->func->mk_time(23, 59, 59, $end_array[1], $end_array[2], $end_array[0]);
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$parser = new functions_codeparse();
		$cookie_mxeditor = $cookie_mxeditor ? $cookie_mxeditor : $forums->func->get_cookie('mxeditor');
		if ($cookie_mxeditor)
		{
			$bbuserinfo['usewysiwyg'] = ($cookie_mxeditor == 'wysiwyg') ? 1 : 0;
		}
		else if ($bboptions['mxemode'])
		{
			$bbuserinfo['usewysiwyg'] = 1;
		}
		else
		{
			$bbuserinfo['usewysiwyg'] = 0;
		}
		$save_array = array(
			'title' => $parser->convert(array(
				'text' => utf8_htmlspecialchars($_POST['title']),
				'allowsmilies' => 0,
				'allowcode' => 1
			)),
			'pagetext' => $parser->convert(array(
				'text' => $bbuserinfo['usewysiwyg'] ? $_POST['post'] : utf8_htmlspecialchars($_POST['post']),
				'allowsmilies' => $_INPUT['allowsmile'],
				'allowcode' => $_INPUT['allowbbcode'],
			)),
			'active' => $_INPUT['active'],
			'forumid' => $forumids,
			'allowhtml' => intval($_INPUT['allowhtml']),
			'startdate' => $startdate,
			'enddate' => $enddate
		);
		if (!$_INPUT['id'])
		{
			$save_array['userid'] = $bbuserinfo['id'];
			$DB->insert(TABLE_PREFIX . 'announcement', $save_array);
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'announcement', $save_array, 'id=' . intval($_INPUT['id']));
		}
		$forums->func->recache('announcement');
		if ($_INPUT['ref'] == 'showall')
		{
			$this->all_announcement();
		}
		else
		{
			$this->announcement();
		}
	}

	function deleteannouncement()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$id = intval($_INPUT['id']);
		if (!$announce = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE id=" . $id . ""))
		{
			$forums->func->standard_error("cannotfindannounce");
		}
		if ($bbuserinfo['id'] != $announce['userid'] && !$bbuserinfo['supermod'])
		{
			$forums->func->standard_error("cannotdelannounce");
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "announcement WHERE id=" . $id . "");
		$forums->func->recache('announcement');
		if ($_INPUT['ref'] == 'showall')
		{
			$this->all_announcement();
		}
		else
		{
			$this->announcement();
		}
	}
}

$output = new announce();
$output->show();
?>