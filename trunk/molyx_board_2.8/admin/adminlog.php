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
# $Id: adminlog.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class adminlog
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['canviewadminlogs'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array("adminlog.php", $forums->lang['adminlog']);
		switch ($_INPUT['do'])
		{
			case 'view':
				$this->view();
				break;
			case 'remove':
				$this->remove();
				break;
			default:
				$this->loglist();
				break;
		}
	}

	function view()
	{
		global $forums, $DB, $_INPUT;
		$pp = $_INPUT['pp'] ? $_INPUT['pp'] : 0;
		$pagetitle = $forums->lang['adminlog'];
		$detail = $forums->lang['viewalladminlog'];
		$forums->admin->nav[] = array("adminlog.php?do=view", $forums->lang['viewadminlog']);
		if ($_INPUT['key'] == "")
		{
			if (!$_INPUT['u'])
			{
				$forums->main_msg = $forums->lang['inputkeywords'];
				return $this->loglist();
			}
			$row = $DB->query_first("SELECT COUNT(adminlogid) as count FROM " . TABLE_PREFIX . "adminlog WHERE userid=" . intval($_INPUT['u']) . "");
			$row_count = $row['count'];
			$query = "u=" . $_INPUT['u'] . "&amp;do=view";
			$DB->query("SELECT a.*, u.id, u.name FROM " . TABLE_PREFIX . "adminlog a, " . TABLE_PREFIX . "user u WHERE a.userid='" . $_INPUT['u'] . "' AND a.userid=u.id ORDER BY a.dateline DESC LIMIT " . $pp . ", 20");
		}
		else
		{
			$_INPUT['key'] = rawurldecode($_INPUT['key']);
			$where = $_INPUT['type'] . " LIKE '%" . $_INPUT['key'] . "%'";
			$row = $DB->query_first("SELECT COUNT(adminlogid) as count FROM " . TABLE_PREFIX . "adminlog WHERE " . $where . "");
			$row_count = $row['count'];
			$query = "do=view&amp;type={$_INPUT['type']}&amp;key=" . urlencode($_INPUT['key']);
			$DB->query("SELECT a.*, u.id, u.name FROM " . TABLE_PREFIX . "adminlog a, " . TABLE_PREFIX . "user u
				 WHERE a.userid=u.id AND " . $where . " ORDER BY a.dateline DESC LIMIT " . $pp . ", 20");
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
				'perpage' => 20,
				'curpage' => $pp,
				'pagelink' => "adminlog.php?" . $forums->sessionurl . $query,
				)
			);
		$forums->admin->columns[] = array($forums->lang['username'], "10%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "55%");
		$forums->admin->columns[] = array($forums->lang['actiontime'], "15%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "20%");
		$forums->admin->print_table_start($forums->lang['savedadminlog']);
		$forums->admin->print_cells_single_row($links, 'center', 'pformstrip');
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
			{
				$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
				$forums->admin->print_cells_row(array("<strong>{$row['name']}</strong>",
						"{$row['note']}",
						"{$row['dateline']}",
						"{$row['host']}",
						));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_cells_single_row($links, 'center', 'pformstrip');
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function remove()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['u'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['nodeleteusers']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "adminlog WHERE userid=" . $_INPUT['u'] . "");
		$forums->func->standard_redirect("adminlog.php?" . $forums->sessionurl);
	}

	function loglist()
	{
		global $forums, $DB;
		$form_array = array();
		$pagetitle = $forums->lang['adminlog'];
		$detail = $forums->lang['adminlogdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$DB->query("SELECT a.*, u.id, u.name FROM " . TABLE_PREFIX . "adminlog a LEFT JOIN " . TABLE_PREFIX . "user u ON (a.userid=u.id) ORDER BY a.dateline DESC LIMIT 0, 5");
		$forums->admin->columns[] = array($forums->lang['username'], "10%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "55%");
		$forums->admin->columns[] = array($forums->lang['actiontime'], "15%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "20%");
		$forums->admin->print_table_start($forums->lang['recentlyfivelogs']);
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
			{
				$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
				$forums->admin->print_cells_row(array("<strong>{$row['name']}</strong>",
						"{$row['note']}",
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
		$forums->admin->columns[] = array($forums->lang['username'], "10%");
		$forums->admin->columns[] = array($forums->lang['actionlog'], "40%");
		$forums->admin->columns[] = array($forums->lang['viewuseralllogs'], "20%");
		$forums->admin->columns[] = array($forums->lang['deleteuseralllogs'], "30%");
		$forums->admin->print_table_start($forums->lang['savedadminlog']);
		$DB->query("SELECT a.*, u.name, count(a.adminlogid) as acount FROM " . TABLE_PREFIX . "adminlog a LEFT JOIN " . TABLE_PREFIX . "user u ON (a.userid=u.id) GROUP BY a.userid ORDER BY acount DESC");
		if ($DB->num_rows())
		{
			while ($r = $DB->fetch_array())
			{
				$forums->admin->print_cells_row(array("<strong>{$r['name']}</strong>",
						"<center>{$r['acount']}</center>",
						"<center><a href='adminlog.php?{$forums->sessionurl}do=view&amp;u={$r['userid']}'>" . $forums->lang['view'] . "</a></center>",
						"<center><a href='adminlog.php?{$forums->sessionurl}do=remove&amp;u={$r['userid']}'>" . $forums->lang['delete'] . "</a></center>",
						));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(1 => array('do', 'view')));
		$forums->admin->columns[] = array("" , "40%");
		$forums->admin->columns[] = array("" , "60%");
		$forums->admin->print_table_start($forums->lang['seachadminlog']);
		$form_array = array(0 => array('note', $forums->lang['actionlog']),
			1 => array('host', $forums->lang['ipaddress']),
			2 => array('userid', $forums->lang['userid']),
			3 => array('script', $forums->lang['actionscript']),
			4 => array('do', $forums->lang['actiontype']),
			);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['keyword'] . "</strong>" ,
				$forums->admin->print_input_row("key")
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['searchtype'] . "</strong>" ,
				$forums->admin->print_input_select_row("type", $form_array)
				));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new adminlog();
$output->show();

?>