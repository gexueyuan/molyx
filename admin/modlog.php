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

class modlog
{
	function show()
	{
		global $forums, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['canviewmodlogs'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array("modlog.php", $forums->lang['modlog']);
		switch (input::get('do', ''))
		{
			case 'view':
				$this->view();
				break;
			case 'remove':
				$this->remove();
				break;
			default:
				$this->list_current();
				break;
		}
	}

	function view()
	{
		global $forums, $DB;
		$start = input::int('pp');
		$pagetitle = $forums->lang['modlog'];
		$detail = $forums->lang['viewallmodlog'];
		$forums->admin->nav[] = array("modlog.php?do=view", $forums->lang['viewmodlog']);
		if (input::str('search_string') == "")
		{
			if (!input::get('u', ''))
			{
				$forums->main_msg = $forums->lang['inputkeywords'];
				return $this->list_current();
			}
			$row = $DB->queryFirst("SELECT COUNT(moderatorlogid) as count FROM " . TABLE_PREFIX . "moderatorlog WHERE userid='" . input::int('u') . "'");
			$row_count = $row['count'];
			$query = "u=" . input::get('u', '') . "&amp;do=view&amp;pp={$start}";
			$DB->query("SELECT m.*, f.id as forumid, f.name FROM " . TABLE_PREFIX . "moderatorlog m
				 LEFT JOIN " . TABLE_PREFIX . "forum f ON(f.id=m.forumid)
				WHERE m.userid='" . input::get('u', '') . "' ORDER BY m.dateline DESC LIMIT " . $start . ", 20");
		}
		else
		{
			input::set('search_string', trim(rawurldecode(input::str('search_string'))));
			if ((input::str('search_type') == 'threadid') OR (input::str('search_type') == 'forumid'))
			{
				$where = input::get('search_type', '') . "='" . input::get('search_string', '') . "'";
			}
			else
			{
				$where = input::get('search_type', '') . " LIKE '%" . input::get('search_string', '') . "%'";
			}
			$row = $DB->queryFirst("SELECT COUNT(moderatorlogid) as count FROM " . TABLE_PREFIX . "moderatorlog WHERE " . $where . "");
			$row_count = $row['count'];
			$query = "do=view&amp;search_type=" . input::get('search_type', '') . "&amp;pp={$start}&amp;search_string=" . urlencode(input::str('search_string'));
			$DB->query("SELECT m.*, f.id as forumid, f.name FROM " . TABLE_PREFIX . "moderatorlog m
				LEFT JOIN " . TABLE_PREFIX . "forum f ON(f.id=m.forumid)
				WHERE " . $where . " ORDER BY m.dateline DESC LIMIT " . $start . ", 20");
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
				'perpage' => 20,
				'curpage' => $start,
				'pagelink' => "modlog.php?" . $forums->sessionurl . $query,
				)
			);
		$forums->admin->columns[] = array($forums->lang['username'], "15%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "25%");
		$forums->admin->columns[] = array($forums->lang['forum'], "15%");
		$forums->admin->columns[] = array($forums->lang['referthreads'], "20%");
		$forums->admin->columns[] = array($forums->lang['actiontime'], "15%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "10%");
		$forums->admin->print_table_start($forums->lang['savedmodlog']);
		$forums->admin->print_cells_single_row($links, 'center', 'catrow');
		if ($DB->numRows())
		{
			while ($row = $DB->fetch())
			{
				$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
				if ($row['threadid'])
				{
					$threadid = "<br />" . $forums->lang['threadid'] . ": " . $row['threadid'];
				}
				$sessionid = preg_replace("/^.+?s=(\w{32}).+?$/" , "\\1", $row['referer']);
				$row['referer'] = preg_replace("/s=(\w){32}/" , "" , $row['referer']);
				$forums->admin->print_cells_row(array("<strong>{$row['username']}</strong>",
						"{$row['action']}",
						"<strong>{$row['name']}</strong>",
						"{$row['title']}" . $threadid,
						"{$row['dateline']}",
						"{$row['host']}",
						));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_cells_single_row($links, 'center', 'tdtop');
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function remove()
	{
		global $forums, $DB;
		if (input::str('u') == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->queryUnbuffered("DELETE FROM " . TABLE_PREFIX . "moderatorlog WHERE userid=" . input::int('u') . "");
		$forums->admin->save_log($forums->lang['modlogdeleted']);
		$forums->func->standard_redirect("modlog.php?" . $forums->sessionurl);
		exit();
	}

	function list_current()
	{
		global $forums, $DB;
		$form_array = array();
		$pagetitle = $forums->lang['modlog'];
		$detail = $forums->lang['modlogdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$DB->query("SELECT m.*, f.id as forumid, f.name FROM " . TABLE_PREFIX . "moderatorlog m
		            LEFT JOIN " . TABLE_PREFIX . "forum f ON (f.id=m.forumid)
		            ORDER BY m.dateline DESC LIMIT 0, 5");
		$forums->admin->columns[] = array($forums->lang['username'], "15%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "25%");
		$forums->admin->columns[] = array($forums->lang['forum'], "15%");
		$forums->admin->columns[] = array($forums->lang['referthreads'], "20%");
		$forums->admin->columns[] = array($forums->lang['actiontime'], "15%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "10%");
		$forums->admin->print_table_start($forums->lang['recentlyfivelogs']);
		if ($DB->numRows())
		{
			while ($row = $DB->fetch())
			{
				$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
				$threadid = "";
				if ($row['threadid'])
				{
					$threadid = "<br />" . $forums->lang['threadid'] . ": " . $row['threadid'];
				}
				$sessionid = preg_replace("/^.+?s=(\w{32}).+?$/" , "\\1", $row['referer']);
				$row['referer'] = preg_replace("/s=(\w){32}/" , "" , $row['referer']);
				$forums->admin->print_cells_row(array("<strong>{$row['username']}</strong>",
						"{$row['action']}",
						"<strong>{$row['name']}</strong>",
						"{$row['title']}" . $threadid,
						"{$row['dateline']}",
						"{$row['host']}",
						));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array($forums->lang['username'], "30%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "20%");
		$forums->admin->columns[] = array($forums->lang['viewuseralllogs'], "20%");
		$forums->admin->columns[] = array($forums->lang['deleteuseralllogs'], "30%");
		$forums->admin->print_table_start($forums->lang['savedmodlog']);
		$DB->query("SELECT m.*, count(m.moderatorlogid) as acount from " . TABLE_PREFIX . "moderatorlog m GROUP BY m.userid ORDER BY acount DESC");
		if ($DB->numRows())
		{
			while ($r = $DB->fetch())
			{
				$forums->admin->print_cells_row(array("<strong>{$r['username']}</strong>",
						"<center>{$r['acount']}</center>",
						"<center><a href='modlog.php?{$forums->sessionurl}do=view&amp;u={$r['userid']}'>" . $forums->lang['view'] . "</a></center>",
						"<center><a href='modlog.php?{$forums->sessionurl}do=remove&amp;u={$r['userid']}'>" . $forums->lang['delete'] . "</a></center>",
						));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(1 => array('do', 'view')));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['seachmodlog']);
		$form_array = array(0 => array('title', $forums->lang['threadtitle']),
			1 => array('host', $forums->lang['ipaddress']),
			2 => array('username', $forums->lang['username']),
			3 => array('threadid', $forums->lang['threadid']),
			4 => array('forumid', $forums->lang['forumid'])
			);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['keyword'] . "</strong>", $forums->admin->print_input_row("search_string")));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['searchtype'] . "</strong>", $forums->admin->print_input_select_row("search_type", $form_array)));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new modlog();
$output->show();

?>