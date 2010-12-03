<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
require ('./global.php');

class attachment
{
	var $attachfolder = array();

	function show()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditattachments'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('attachment.php', $forums->lang['manageattachments']);
		$pagetitle = $forums->lang['manageattachments'];
		$detail = $forums->lang['maattachmentsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		switch (input::get('do', ''))
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
		global $forums, $DB;
		$show = input::int('show');
		$show = $show > 100 ? 100 : $show;
		$first = input::int('pp');
		$forums->cache['attachmenttype'] = array();
		$result = $DB->query('SELECT extension, mimetype, usepost, useavatar, attachimg
			FROM ' . TABLE_PREFIX . 'attachmenttype
			WHERE usepost = 1');
		while ($r = $DB->fetch($result))
		{
			$forums->cache['attachmenttype'][$r['extension']] = $r;
		}
		$url = '';
		$url_components = array('extension', 'filesize', 'filesize_gt', 'days', 'days_gt', 'hits', 'hits_gt', 'filename', 'authorname', 'onlyimage');
		foreach($url_components AS $u)
		{
			$url .= $u . '=' . input::get($u, '') . '&amp;';
		}
		$url .= 'orderby=' . input::get('orderby', '') . '&amp;sort=' . input::get('sort', '') . '&amp;show=' . input::str('show');
		$queryfinal = "";
		$query = array();
		if (input::str('filename'))
		{
			$query[] = 'LOWER(a.filename) LIKE "%' . strtolower(input::str('filename')) . '%"';
		}
		if (input::str('extension'))
		{
			$query[] = 'a.extension="' . strtolower(str_replace('.', '', input::str('extension'))) . '"';
		}
		if (input::int('filesize'))
		{
			$gt = input::str('filesize_gt') == 'gt' ? '>=' : '<';
			$query[] = "a.filesize $gt " . intval(input::int('filesize') * 1024);
		}
		if (input::int('days'))
		{
			$day_break = TIMENOW - intval(input::int('days') * 86400);
			$gt = input::str('days_gt') == 'lt' ? '>=' : '<';
			$query[] = "a.dateline $gt " . $day_break;
		}
		if (input::int('hits'))
		{
			$gt = input::str('hits_gt') == 'gt' ? '>=' : '<';
			$query[] = "a.counter $gt " . input::int('hits');
		}
		if (input::str('authorname'))
		{
			$user = $DB->queryFirst("SELECT id FROM " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE '%" . strtolower(input::str('authorname')) . "%' OR name LIKE '%" . input::str('authorname') . "%'");
			$query[] = 'a.userid = ' . intval($user['id']);
		}
		if (input::str('onlyimage'))
		{
			$query[] = 'a.image=1';
		}
		if (count($query))
		{
			$queryfinal = 'AND ' . implode(" AND ", $query);
		}
		$count = $DB->queryFirst("SELECT count(*) as cnt FROM " . TABLE_PREFIX . "attachment a WHERE a.postid != 0 " . $queryfinal . "");
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
				ORDER BY a." . input::get('orderby', '') . " " . input::get('sort', '') . "
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
		while ($r = $DB->fetch())
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
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['filename'] . "</strong>", $forums->admin->print_input_row('filename', input::str('filename'), '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['extension'] . "</strong>", $forums->admin->print_input_row('extension', input::str('extension'), '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['filesize'] . " (" . $forums->lang['units'] . ": kb)</strong>", $forums->admin->print_input_select_row('filesize_gt', $gt_array, input::str('filesize_gt')) . ' ' . $forums->admin->print_input_row('filesize', input::int('filesize'), '', '', 10)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploadtime'] . "</strong>", $forums->admin->print_input_select_row('days_gt', $gt_array, input::str('days_gt')) . ' ' . $forums->admin->print_input_row('days', input::int('days'), '', '', 10) . ' ' . $forums->lang['days']));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['downloadtimes'] . "</strong>", $forums->admin->print_input_select_row('hits_gt', $gt_array, input::str('hits_gt')) . ' ' . $forums->admin->print_input_row('hits', input::str('hits'), '', '', 10) . ' ' . $forums->lang['times']));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploaduser'] . "</strong>", $forums->admin->print_input_row('authorname', input::str('authorname'), '', '', 30)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['onlyimages'] . "</strong><div class='description'>" . $forums->lang['onlyimagesdesc'] . "</div>",
				$forums->admin->print_yes_no_row('onlyimage', input::str('onlyimage')),
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['orderby'] . "</strong>",
				$forums->admin->print_input_select_row('orderby', array(0 => array('dateline', $forums->lang['uploadtime']),
						1 => array('counter', $forums->lang['viewtimes']),
						2 => array('filesize', $forums->lang['filesize']),
						3 => array('filename', $forums->lang['filename']),
						), input::str('orderby')) . ' ' . $forums->admin->print_input_select_row('sort', array(0 => array('desc', $forums->lang['descending']),
						1 => array('asc', $forums->lang['ascending']),
						), input::str('sort'))
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pageresults'] . "</strong><div class='description'>" . $forums->lang['nomorethanhundred'] . "</div>",
				$forums->admin->print_input_row('show', input::get('show', 25), '', '', 10),
				));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function massremove()
	{
		global $forums, $DB, $bboptions;
		$tmpattach = input::get('attach', array());
		if (!empty($tmpattach))
		{
			foreach (input::get('attach', array()) AS $value)
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
			while ($attachment = $DB->fetch($attachments))
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
			$DB->queryUnbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN(" . implode(",", $ids) . ")");
			require_once(ROOT_PATH . 'includes/functions_post.php');
			$postlib = new functions_post(0);
			foreach($attach_tid AS $apid => $tid)
			{
				$postlib->recount_attachment($tid);
			}
		}
		$forums->main_msg = $forums->lang['attachdeleted'];
		if (input::str('return') == 'stats')
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
					input::set($k, $v);
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
		while ($r = $DB->fetch())
		{
			$r['extension'] = strtolower($r['extension']);
			$forums->cache['attachmenttype'][ $r['extension'] ] = $r;
		}
		$forums->admin->columns[] = array("", "30%");
		$forums->admin->columns[] = array("", "70%");
		$forums->admin->print_table_start($forums->lang['attachment'] . ": " . $forums->lang['summary']);
		$stats = $DB->queryFirst("SELECT count(*) as count, sum(filesize) as sum FROM " . TABLE_PREFIX . "attachment");
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
		while ($r = $DB->fetch())
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
		while ($r = $DB->fetch($attach))
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
		while ($r = $DB->fetch())
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
		global $forums, $DB;
		$DB->delete(TABLE_PREFIX . 'attachmenttype', 'id=' . input::int('id'));
		$forums->func->recache('attachmenttype');
		$forums->main_msg = $forums->lang['attachtypedeleted'];
		$this->attachtypes_start();
	}

	function attachtypes_save($type = 'add')
	{
		global $forums, $DB;

		if (!input::str('extension') || !input::str('mimetype'))
		{
			$forums->main_msg = $forums->lang['mustinputmime'];
			$this->attachtypes_form($type);
		}
		$save_array = array(
			'extension' => str_replace('.', '', input::str('extension')),
			'mimetype' => input::str('mimetype'),
			'maxsize' => input::int('maxsize'),
			'usepost' => input::int('usepost'),
			'useavatar' => input::int('useavatar'),
			'attachimg' => input::str('attachimg')
		);
		if ($type == 'add')
		{
			$attach = $DB->queryFirst('SELECT *
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
			$DB->update(TABLE_PREFIX . 'attachmenttype', $save_array, 'id=' . input::int('id'));
			$forums->main_msg = $forums->lang['attachtypeedited'];
		}
		$forums->func->recache('attachmenttype');
		$this->attachtypes_start();
	}

	function attachtypes_form($type = 'add')
	{
		global $forums, $DB;

		if ($type == 'add')
		{
			$code = 'doadd';
			$title = $forums->lang['addnewattachtype'];
			$attach = array();
			$types = '';
			$result = $DB->query('SELECT *
				FROM ' . TABLE_PREFIX . 'attachmenttype
				ORDER BY extension');
			while ($r = $DB->fetch($result))
			{
				$selected = '';
				if (input::int('istype') && $r['id'] == input::int('istype'))
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
			$attach = $DB->queryFirst('SELECT *
				FROM ' . TABLE_PREFIX . 'attachmenttype
				WHERE id=' . input::int('id'));

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
			2 => array('id' , input::int('id'))
		);
		$forums->admin->print_table_start($title, '', $extra, $createform);

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['extension'] . '</strong><div class="description">' . $forums->lang['extensiondesc'] . '</div>',
			$forums->admin->print_input_row('extension', input::get('extension', $attach['extension']), '', '', 10),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetype'] . '</strong><div class="description">' . $forums->lang['mimetypedesc'] . '</div>',
			$forums->admin->print_input_row('mimetype', input::get('extension', $attach['mimetype']), 40),
		));

		$upload_max_filesize = function_exists('ini_get') ? ' ' . @ini_get('upload_max_filesize') : '';

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['maxsize'] . '</strong><div class="description">' . sprintf($forums->lang['maxsizedesc'], $upload_max_filesize) . '</div>',
			$forums->admin->print_input_row('maxsize', input::get('extension', $attach['maxsize']), 20),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetypeusepost'] . '</strong>',
			$forums->admin->print_yes_no_row('usepost', input::get('usepost', $attach['usepost'])),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['mimetypeuseavatar'] . '</strong>',
			$forums->admin->print_yes_no_row('useavatar', input::get('useavatar', $attach['useavatar'])),
		));

		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['attachimages'] . '</strong><div class="description">' . $forums->lang['attachimagesdesc'] . '</div>',
			$forums->admin->print_input_row('attachimg', input::get('attachimg', $attach['attachimg']), '', '', 40),
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
		while ($r = $DB->fetch($result))
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