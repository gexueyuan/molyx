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
require ('./global.php');

class attachment
{
	var $attachfolder = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditattachments'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('attachment.php', $forums->lang['manageattachments']);
		$pagetitle = $forums->lang['manageattachments'];
		$detail = $forums->lang['maattachmentsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		switch ($_INPUT['do'])
		{
			case 'types':
				$this->attachtypes_start();
				break;
			case 'add':
				$this->attachtypes_form('add');
				break;
			case 'doadd':
				$this->attachtypes_save('add');
				break;
			case 'edit':
				$this->attachtypes_form('edit');
				break;
			case 'delete':
				$this->attachtypes_delete();
				break;
			case 'doedit':
				$this->attachtypes_save('edit');
				break;
			case 'stats':
				$this->stats_form();
				break;
			case 'massremove':
				$this->massremove();
				break;
			case 'search':
				$this->search_form();
				break;
			case 'dosearch':
				$this->dosearch();
				break;
			default:
				$this->attachtypes_start();
				break;
		}
	}

	function dosearch()
	{
		global $forums, $DB, $_INPUT;
		$show = intval($_INPUT['show']);
		$show = $show > 100 ? 100 : $show;
		$first = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$forums->cache['attachmenttype'] = array();
		$result = $DB->query('SELECT extension, mimetype, usepost, useavatar, attachimg
			FROM ' . TABLE_PREFIX . 'attachmenttype
			WHERE usepost = 1');
		while ($r = $DB->fetch_array($result))
		{
			$forums->cache['attachmenttype'][$r['extension']] = $r;
		}
		$url = '';
		$url_components = array('extension', 'filesize', 'filesize_gt', 'days', 'days_gt', 'hits', 'hits_gt', 'filename', 'authorname', 'onlyimage');
		foreach($url_components AS $u)
		{
			$url .= $u . '=' . $_INPUT[ $u ] . '&amp;';
		}
		$url .= 'orderby=' . $_INPUT['orderby'] . '&amp;sort=' . $_INPUT['sort'] . '&amp;show=' . $_INPUT['show'];
		$queryfinal = "";
		$query = array();
		if ($_INPUT['filename'])
		{
			$query[] = 'LOWER(a.filename) LIKE "%' . strtolower($_INPUT['filename']) . '%"';
		}
		if ($_INPUT['extension'])
		{
			$query[] = 'a.extension="' . strtolower(str_replace('.', '', $_INPUT['extension'])) . '"';
		}
		if ($_INPUT['filesize'])
		{
			$gt = $_INPUT['filesize_gt'] == 'gt' ? '>=' : '<';
			$query[] = "a.filesize $gt " . intval($_INPUT['filesize'] * 1024);
		}
		if ($_INPUT['days'])
		{
			$day_break = TIMENOW - intval($_INPUT['days'] * 86400);
			$gt = $_INPUT['days_gt'] == 'lt' ? '>=' : '<';
			$query[] = "a.dateline $gt " . $day_break;
		}
		if ($_INPUT['hits'])
		{
			$gt = $_INPUT['hits_gt'] == 'gt' ? '>=' : '<';
			$query[] = "a.counter $gt " . $_INPUT['hits'];
		}
		if ($_INPUT['authorname'])
		{
			$user = $DB->query_first("SELECT id FROM " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE '%" . strtolower($_INPUT['authorname']) . "%' OR name LIKE '%" . $_INPUT['authorname'] . "%'");
			$query[] = 'a.userid = ' . intval($user['id']);
		}
		if ($_INPUT['onlyimage'])
		{
			$query[] = 'a.image=1';
		}
		if (count($query))
		{
			$queryfinal = 'AND ' . implode(" AND ", $query);
		}
		$count = $DB->query_first("SELECT count(*) as cnt FROM " . TABLE_PREFIX . "attachment a WHERE a.postid != 0 " . $queryfinal . "");
		$links = $forums->func->build_pagelinks(array('totalpages' => $count['cnt'],
				'perpage' => $show,
				'curpage' => $first,
				'pagelink' => "attachment.php?{$forums->sessionurl}do=dosearch&amp;{$url}",
				));
		$DB->query("SELECT a.*, t.tid, t.forumid, t.title, p.username, p.dateline
				FROM " . TABLE_PREFIX . "attachment a
				 LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid=a.postid)
				 LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
				WHERE a.postid != 0 " . $queryfinal . "
				ORDER BY a." . $_INPUT['orderby'] . " " . $_INPUT['sort'] . "
				LIMIT $first, $show");
		$forums->admin->print_form_header(array(1 => array('do' , 'massremove'),
				3 => array('return', 'search'),
				4 => array('url' , $url),
				), 'mutliact');
		$forums->admin->columns[] = array("", "1%");
		$forums->admin->columns[] = array($forums->lang['attachment'], "25%");
		$forums->admin->columns[] = array($forums->lang['size'], "10%");
		$forums->admin->columns[] = array($forums->lang['uploaduser'], "15%");
		$forums->admin->columns[] = array($forums->lang['thread'], "25%");
		$forums->admin->columns[] = array($forums->lang['uploadtime'], "20%");
		$forums->admin->columns[] = array("<input name='allbox' type='checkbox' value='" . $forums->lang['selectall'] . "' onClick='CheckAll(document.mutliact);' />", "1%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['searchresult']);
		while ($r = $DB->fetch_array())
		{
			$r['title'] = strip_tags($r['title']);
			$r['extension'] = strtolower($r['extension']);
			$r['stitle'] = $forums->func->fetch_trimmed_title($r['title'], 15);
			$forums->admin->print_cells_row(array("<img src='../images/{$forums->cache['attachmenttype'][$r['extension']]['attachimg']}' border='0' alt='' />" ,
					"<a href='../attachment.php?id={$r['attachmentid']}&amp;u={$r['userid']}&amp;extension={$r['extension']}&amp;attach={$r['location']}&amp;filename={$r['filename']}&amp;attachpath={$r['attachpath']}' target='_blank'>{$r['filename']}</a>",
					fetch_number_format($r['filesize'], true),
					"<a href='../profile.php?u={$r['userid']}' target='_blank'>{$r['username']}</a>",
					"<a href='../showthread.php?t={$r['tid']}&amp;view=findpost&amp;p={$r['postid']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
					$forums->func->get_date($r['dateline'], 1),
					"<div align='center'><input type='checkbox' name='attach[]' value='{$r['attachmentid']}' /></div>",
					));
		}
		$removebutton = "<input type='submit' value='" . $forums->lang['deleteselattachs'] . "' class='button' />";
		$forums->admin->print_cells_single_row($removebutton, "right", "pformstrip");
		$forums->admin->print_cells_single_row($links, "right", "");
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function search_form()
	{
		global $forums, $DB;
		$forums->admin->columns[] = array("" , "40%");
		$forums->admin->columns[] = array("" , "60%");
		$forums->admin->print_form_header(array(1 => array('do' , 'dosearch'),));
		$forums->admin->print_table_start($forums->lang['searchattachment']);
		$gt_array = array(0 => array('gt', $forums->lang['greatthan']), 1 => array('lt', $forums->lang['lessthan']));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['filename'] . "</strong>", $forums->admin->print_input_row('filename', $_INPUT['filename'], '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['extension'] . "</strong>", $forums->admin->print_input_row('extension', $_INPUT['extension'], '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['filesize'] . " (" . $forums->lang['units'] . ": kb)</strong>", $forums->admin->print_input_select_row('filesize_gt', $gt_array, $_INPUT['filesize_gt']) . ' ' . $forums->admin->print_input_row('filesize', $_INPUT['filesize'], '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploadtime'] . "</strong>", $forums->admin->print_input_select_row('days_gt', $gt_array, $_INPUT['days_gt']) . ' ' . $forums->admin->print_input_row('days', $_INPUT['days'], '', '', 10) . ' ' . $forums->lang['days']));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['downloadtimes'] . "</strong>", $forums->admin->print_input_select_row('hits_gt', $gt_array, $_INPUT['hits_gt']) . ' ' . $forums->admin->print_input_row('hits', $_INPUT['hits'], '', '', 10) . ' ' . $forums->lang['times']));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploaduser'] . "</strong>", $forums->admin->print_input_row('authorname', $_INPUT['authorname'], '', '', 30)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['onlyimages'] . "</strong><div class='description'>" . $forums->lang['onlyimagesdesc'] . "</div>",
				$forums->admin->print_yes_no_row('onlyimage', $_INPUT['onlyimage']),
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['orderby'] . "</strong>",
				$forums->admin->print_input_select_row('orderby', array(0 => array('dateline', $forums->lang['uploadtime']),
						1 => array('counter', $forums->lang['viewtimes']),
						2 => array('filesize', $forums->lang['filesize']),
						3 => array('filename', $forums->lang['filename']),
						), $_INPUT['orderby']) . ' ' . $forums->admin->print_input_select_row('sort', array(0 => array('desc', $forums->lang['descending']),
						1 => array('asc', $forums->lang['ascending']),
						), $_INPUT['sort'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pageresults'] . "</strong><div class='description'>" . $forums->lang['nomorethanhundred'] . "</div>",
				$forums->admin->print_input_row('show', $_INPUT['show'] ? $_INPUT['show'] : 25, '', '', 10),
				));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function massremove()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		if (is_array($_INPUT['attach']))
		{
			foreach ($_INPUT['attach'] AS $value)
			{
				if ($value)
				{
					$ids[] = $value;
				}
			}
		}
		$attach_tid = array();
		if (count($ids))
		{
			$attachments = $DB->query("SELECT a.*, p.pid, p.threadid
											FROM " . TABLE_PREFIX . "attachment a
											 LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid=a.postid)
											WHERE a.attachmentid IN(" . implode(",", $ids) . ")");
			while ($attachment = $DB->fetch_array($attachments))
			{
				if ($attachment['location'])
				{
					unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['location']);
				}
				if ($attachment['thumblocation'])
				{
					unlink($bboptions['uploadfolder'] . "/" . $attachment['attachpath'] . "/" . $attachment['thumblocation']);
				}
				$attach_tid[ $attachment['threadid'] ] = $attachment['threadid'];
			}
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN(" . implode(",", $ids) . ")");
			require_once(ROOT_PATH . 'includes/functions_post.php');
			$postlib = new functions_post(0);
			foreach($attach_tid AS $apid => $tid)
			{
				$postlib->recount_attachment($tid);
			}
		}
		$forums->main_msg = $forums->lang['attachdeleted'];
		if ($_INPUT['return'] == 'stats')
		{
			$this->stats_form();
		}
		else
		{
			if ($_POST['url'])
			{
				$_POST['url'] = str_replace("&amp;", "&", $_POST['url']);
				foreach(explode('&', $_POST['url']) AS $u)
				{
					list ($k, $v) = explode('=', $u);
					$_INPUT[ $k ] = $v;
				}
			}
			$this->dosearch();
		}
	}

	function stats_form()
	{
		global $forums, $DB, $bboptions;
		$forums->cache['attachmenttype'] = array();
		$DB->query("SELECT extension,mimetype,usepost,useavatar,attachimg FROM " . TABLE_PREFIX . "attachmenttype WHERE usepost=1");
		while ($r = $DB->fetch_array())
		{
			$r['extension'] = strtolower($r['extension']);
			$forums->cache['attachmenttype'][ $r['extension'] ] = $r;
		}
		$forums->admin->columns[] = array("", "30%");
		$forums->admin->columns[] = array("", "70%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['summary']);
		$stats = $DB->query_first("SELECT count(*) as count, sum(filesize) as sum FROM " . TABLE_PREFIX . "attachment");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['attachnums'] . "</strong>" , fetch_number_format($stats['count'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['diskused'] . "</strong>", fetch_number_format($stats['sum'], true)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['attachaverage'] . "</strong>", $stats['count'] ? fetch_number_format(($stats['sum'] / $stats['count']), true) : '0 ' . $forums->lang['bytes']));
		if (!@is_dir($bboptions['uploadfolder']))
		{
			$warning = " <font color='red'><strong>( " . $forums->lang['patherrors'] . " )</strong></font>";
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['storagepath'] . "</strong>", $bboptions['uploadfolder'] . $warning));
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(1 => array('do' , 'massremove'),
				2 => array('return', 'stats'),
				));
		$forums->admin->columns[] = array("", "1%");
		$forums->admin->columns[] = array($forums->lang['attachment'], "25%");
		$forums->admin->columns[] = array($forums->lang['size'], "10%");
		$forums->admin->columns[] = array($forums->lang['uploaduser'], "15%");
		$forums->admin->columns[] = array($forums->lang['thread'], "25%");
		$forums->admin->columns[] = array($forums->lang['uploadtime'], "20%");
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['fivenewattachs']);
		$DB->query("SELECT a.*, t.tid, t.forumid, t.title, p.username, p.dateline
				FROM " . TABLE_PREFIX . "attachment a
				 LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid=a.postid)
				 LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
				WHERE a.postid != 0
				ORDER BY a.dateline DESC
				LIMIT 0, 5");
		while ($r = $DB->fetch_array())
		{
			$r['title'] = strip_tags($r['title']);
			$r['stitle'] = $forums->func->fetch_trimmed_title($r['title'], 15);
			$forums->admin->print_cells_row(array("<img src='../images/{$forums->cache['attachmenttype'][ $r['extension'] ]['attachimg']}' border='0' alt='' />" ,
					"<a href='../attachment.php?id={$r['attachmentid']}&amp;u={$r['userid']}&amp;extension={$r['extension']}&amp;attach={$r['location']}&amp;filename={$r['filename']}&attachpath={$r['attachpath']}' target='_blank'>{$r['filename']}</a>",
					fetch_number_format($r['filesize'], true),
					"<a href='../profile.php?u={$r['userid']}' target='_blank'>{$r['username']}</a>",
					"<a href='../redirect.php?t={$r['tid']}&amp;goto=findpost&amp;p={$r['postid']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
					$forums->func->get_date($r['dateline'], 1),
					"<div align='center'><input type='checkbox' name='attach[]' value='{$r['attachmentid']}' /></div>",
					));
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->columns[] = array($forums->lang['attachment'], "20%");
		$forums->admin->columns[] = array($forums->lang['size'], "10%");
		$forums->admin->columns[] = array($forums->lang['uploaduser'], "15%");
		$forums->admin->columns[] = array($forums->lang['thread'], "25%");
		$forums->admin->columns[] = array($forums->lang['uploadtime'], "25%");
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['fivebiggerattachs']);
		$attach = $DB->query("SELECT a.*, t.tid, t.forumid, t.title, p.username, p.dateline
				FROM " . TABLE_PREFIX . "attachment a
				 LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid=a.postid)
				 LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
				WHERE a.postid != 0
				ORDER BY a.filesize DESC
				LIMIT 0, 5");
		while ($r = $DB->fetch_array($attach))
		{
			$r['stitle'] = $forums->func->fetch_trimmed_title($r['title'], 15);
			$forums->admin->print_cells_row(array("<img src='../images/{$forums->cache['attachmenttype'][ $r['extension'] ]['attachimg']}' border='0' alt='' />" ,
					"<a href='../attachment.php?id={$r['attachmentid']}&amp;u={$r['userid']}&amp;extension={$r['extension']}&amp;attach={$r['location']}&amp;filename={$r['filename']}&amp;attachpath={$r['attachpath']}' target='_blank'>{$r['filename']}</a>",
					fetch_number_format($r['filesize'], true),
					"<a href='../profile.php?u={$r['userid']}' target='_blank'>{$r['username']}</a>",
					"<a href='../showthread.php?t={$r['tid']}&amp;view=findpost&amp;p={$r['postid']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
					$forums->func->get_date($r['dateline'], 1),
					"<div align='center'><input type='checkbox' name='attach[]' value='{$r['attachmentid']}' /></div>",
					));
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->columns[] = array($forums->lang['attachment'], "20%");
		$forums->admin->columns[] = array($forums->lang['viewtimes'], "10%");
		$forums->admin->columns[] = array($forums->lang['uploaduser'], "15%");
		$forums->admin->columns[] = array($forums->lang['thread'], "25%");
		$forums->admin->columns[] = array($forums->lang['uploadtime'], "25%");
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['fiveviewattachs']);
		$DB->query("SELECT a.*, t.tid, t.forumid, t.title, p.username, p.dateline
				FROM " . TABLE_PREFIX . "attachment a
				 LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid=a.postid)
				 LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
				WHERE a.postid != 0
				ORDER BY a.counter DESC
				LIMIT 0, 5");
		while ($r = $DB->fetch_array())
		{
			$r['title'] = strip_tags($r['title']);
			$r['stitle'] = $forums->func->fetch_trimmed_title($r['title'], 15);
			$size = fetch_number_format($r['filesize'], true);
			$forums->admin->print_cells_row(array("<img src='../images/{$forums->cache['attachmenttype'][ $r['extension'] ]['attachimg']}' border='0' alt='' />" ,
					"<a href='../attachment.php?id={$r['attachmentid']}&amp;u={$r['userid']}&amp;extension={$r['extension']}&amp;attach={$r['location']}&amp;filename={$r['filename']}&amp;attachpath={$r['attachpath']}' target='_blank'>{$r['filename']}</a>",
					$r['counter'],
					"<a href='../profile.php?u={$r['userid']}' target='_blank'>{$r['username']}</a>",
					"<a href='../showthread.php?t={$r['tid']}&amp;view=findpost&amp;p={$r['postid']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
					$forums->func->get_date($r['dateline'], 1),
					"<div align='center'><input type='checkbox' name='attach[]' value='{$r['attachmentid']}' /></div>",
					));
		}
		$removebutton = "<input type='submit' value='" . $forums->lang['delselectedattachs'] . "' class='button' />";
		$forums->admin->print_cells_single_row($removebutton, "right", "pformstrip");
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function attachtypes_delete()
	{
		global $forums, $DB, $_INPUT;
		$DB->delete(TABLE_PREFIX . 'attachmenttype', 'id=' . intval($_INPUT['id']));
		$forums->func->recache('attachmenttype');
		$forums->main_msg = $forums->lang['attachtypedeleted'];
		$this->attachtypes_start();
	}

	function attachtypes_save($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		if (!$_INPUT['extension'] || !$_INPUT['mimetype'])
		{
			$forums->main_msg = $forums->lang['mustinputmime'];
			$this->attachtypes_form($type);
		}
		$save_array = array(
			'extension' => str_replace('.', '', $_INPUT['extension']),
			'mimetype' => $_INPUT['mimetype'],
			'maxsize' => intval($_INPUT['maxsize']),
			'usepost' => intval($_INPUT['usepost']),
			'useavatar' => intval($_INPUT['useavatar']),
			'attachimg' => $_INPUT['attachimg']
		);
		if ($type == 'add')
		{
			$attach = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . "attachmenttype
				WHERE extension = '{$save_array['extension']}'");
			if ($attach['id'])
			{
				$forums->lang['mimeexist'] = sprintf($forums->lang['mimeexist'], $save_array['extension']);
				$forums->main_msg = $forums->lang['mimeexist'];
				$this->attachtypes_form($type);
			}
			$DB->insert(TABLE_PREFIX . 'attachmenttype', $save_array);
			$forums->main_msg = $forums->lang['attachtypeadded'];
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'attachmenttype', $save_array, 'id=' . $_INPUT['id']);
			$forums->main_msg = $forums->lang['attachtypeedited'];
		}
		$forums->func->recache('attachmenttype');
		$this->attachtypes_start();
	}

	function attachtypes_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		$_INPUT['istype'] = intval($_INPUT['istype']);
		if ($type == 'add')
		{
			$code = 'doadd';
			$title = $forums->lang['addnewattachtype'];
			$attach = array();
			$types = '';
			$result = $DB->query('SELECT *
				FROM ' . TABLE_PREFIX . 'attachmenttype
				ORDER BY extension');
			while ($r = $DB->fetch_array($result))
			{
				$selected = '';
				if ($_INPUT['istype'] && $r['id'] == $_INPUT['istype'])
				{
					$attach = $r;
					$selected = ' selected="selected"';
				}
				$types .= '<option value="' . $r['id'] . '"' . $selected . '>' . $forums->lang['baseon'] . ': ' . $r['extension'] . '</option>';
			}
			$extra = '<div style="float:right;width:auto;padding-right:3px;"><form method="post" action="attachment.php?' . $forums->sessionurl . 'do=add"><select name="istype" class="button">' . $types . '</select> &nbsp;<input type="submit" value="' . $forums->lang["ok"] . '" class="button" /></form></div>';
		}
		else
		{
			$code = 'doedit';
			$title = $forums->lang['editattachtype'];
			$attach = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . 'attachmenttype
				WHERE id=' . $_INPUT['id']);

			if (!$attach['id'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->attachtypes_start();
			}
		}
		$forums->admin->columns[] = array('&nbsp;' , '40%');
		$forums->admin->columns[] = array('&nbsp;' , '60%');
		$createform = array(
			1 => array('do' , $code),
			2 => array('id' , $_INPUT['id'])
		);
		$forums->admin->print_table_start($title, '', $extra, $createform);

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['extension'] . '</strong><div class="description">' . $forums->lang['extensiondesc'] . '</div>',
			$forums->admin->print_input_row('extension', isset($_INPUT['extension']) ? $_INPUT['extension'] : $attach['extension'], '', '', 10),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetype'] . '</strong><div class="description">' . $forums->lang['mimetypedesc'] . '</div>',
			$forums->admin->print_input_row('mimetype', isset($_INPUT['mimetype']) ? $_INPUT['mimetype'] : $attach['mimetype'], 40),
		));

		$upload_max_filesize = function_exists('ini_get') ? ' ' . @ini_get('upload_max_filesize') : '';

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['maxsize'] . '</strong><div class="description">' . sprintf($forums->lang['maxsizedesc'], $upload_max_filesize) . '</div>',
			$forums->admin->print_input_row('maxsize', isset($_INPUT['maxsize']) ? $_INPUT['maxsize'] : $attach['maxsize'], 20),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetypeusepost'] . '</strong>',
			$forums->admin->print_yes_no_row('usepost', isset($_INPUT['usepost']) ? $_INPUT['usepost'] : $attach['usepost']),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetypeuseavatar'] . '</strong>',
			$forums->admin->print_yes_no_row('useavatar', isset($_INPUT['useavatar']) ? $_INPUT['useavatar'] : $attach['useavatar']),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['attachimages'] . '</strong><div class="description">' . $forums->lang['attachimagesdesc'] . '</div>',
			$forums->admin->print_input_row('attachimg', $_INPUT['attachimg'] ? $_INPUT['attachimg'] : $attach['attachimg'], '', '', 40),
		));
		$forums->admin->print_form_submit($title);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function attachtypes_start()
	{
		global $forums, $DB;
		$forums->admin->columns[] = array("&nbsp;", "1%");
		$forums->admin->columns[] = array($forums->lang['extension'], "20%");
		$forums->admin->columns[] = array($forums->lang['mimetype'], "30%");
		$forums->admin->columns[] = array($forums->lang['maxsize'], "10%");
		$forums->admin->columns[] = array($forums->lang['usepost'], "10%");
		$forums->admin->columns[] = array($forums->lang['useavatar'], "10%");
		$forums->admin->columns[] = array($forums->lang['option'], "20%");
		$forums->admin->print_table_start($forums->lang['attachtype']);

		$checked_img = '<img src="' . $forums->imageurl . '/check.gif" border="0" alt="X" />';

		$result = $DB->query('SELECT *
			FROM ' . TABLE_PREFIX . 'attachmenttype
			ORDER BY extension');
		while ($r = $DB->fetch_array($result))
		{
			$apost_checked = $r['usepost'] ? $checked_img : '&nbsp;';
			$aphoto_checked = $r['useavatar'] ? $checked_img : '&nbsp;';
			$edit = $forums->admin->print_button($forums->lang['edit'], "attachment.php?{$forums->sessionurl}do=edit&amp;id={$r['id']}");
			$delete = $forums->admin->print_button($forums->lang['delete'], "attachment.php?{$forums->sessionurl}do=delete&amp;id={$r['id']}", 'button');
			$forums->admin->print_cells_row(array(
				'<img src="../images/' . $r['attachimg'] . '" border="0" alt="' . $r['extension'] . '" />',
				'.<strong>' . $r['extension'] . '</strong>',
				$r['mimetype'],
				fetch_number_format($r['maxsize'] * 1024, true),
				'<div align="center">' . $apost_checked . '</div>',
				'<div align="center">' . $aphoto_checked . '</div>',
				'<div align="center">' . $edit . ' &nbsp; &nbsp; '. $delete . '</div>',
			));
		}
		$add_new = $forums->admin->print_button($forums->lang['addnewattachtype'], "attachment.php?{$forums->sessionurl}do=add");
		$forums->admin->print_cells_single_row($add_new, "center", "pformstrip");
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}
}

$output = new attachment();
$output->show();

?>