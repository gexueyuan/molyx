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

class cronlog
{
	function show()
	{
		global $forums;
		$forums->admin->nav[] = array('cronlog.php', $forums->lang['runcronlog']);
		switch (input::get('do', ''))
		{
			case 'view':
				$this->view();
				break;
			case 'delete':
				$this->remove();
				break;
			default:
				$this->listlog();
				break;
		}
	}

	function remove()
	{
		global $forums, $DB;
		$prune = is_numeric(input::str('cron_prune')) ? input::int('cron_prune') : 30;
		$prune = TIMENOW - ($prune * 86400);
		if (input::get('cronid', '') != -1)
		{
			$where = "title='" . input::get('cronid', '') . "' AND dateline < $prune";
		}
		else
		{
			$where = "dateline < $prune";
		}
		$DB->queryUnbuffered("DELETE FROM " . TABLE_PREFIX . "cronlog WHERE " . $where . "");
		$forums->main_msg = $forums->lang['cronlogdeleted'];
		$this->listlog();
	}

	function view()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['runcronlog'];
		$detail = $forums->lang['runcronlogdesc'];
		$forums->admin->nav[] = array('', $forums->lang['cronloglist']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$limit = input::get('cron_count', 30);
		$limit = $limit > 150 ? 150 : $limit;
		if (input::get('cronid', '') != -1)
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog WHERE title='" . input::get('cronid', '') . "' ORDER BY dateline DESC LIMIT 0, " . $limit . "");
		}
		else
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog ORDER BY dateline DESC LIMIT 0, " . $limit . "");
		}
		$forums->admin->columns[] = array($forums->lang['cronexecute'], "20%");
		$forums->admin->columns[] = array($forums->lang['crontime'], "35%");
		$forums->admin->columns[] = array($forums->lang['cronloginfo'], "45%");
		$forums->admin->print_table_start($forums->lang['selectedcronlog']);
		if ($DB->numRows())
		{
			while ($row = $DB->fetch())
			{
				$forums->admin->print_cells_row(array("<strong>{$row['title']}</strong>", $forums->func->get_date($row['dateline'], 1), "{$row['description']}"));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function listlog()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['runcronlog'];
		$detail = $forums->lang['runcronlogdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$crons = array(0 => array(-1, $forums->lang['allcronlist']));
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "cron");
		while ($pee = $DB->fetch())
		{
			$crons[] = array($pee['cronid'], $pee['title']);
		}
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog ORDER BY dateline DESC LIMIT 0, 5");
		$forums->admin->columns[] = array($forums->lang['cronexecute'], "20%");
		$forums->admin->columns[] = array($forums->lang['crontime'], "35%");
		$forums->admin->columns[] = array($forums->lang['cronloginfo'], "45%");
		$forums->admin->print_table_start($forums->lang['lastfivecron']);
		if ($DB->numRows())
		{
			while ($row = $DB->fetch())
			{
				$forums->admin->print_cells_row(array("<strong>{$row['title']}</strong>", $forums->func->get_date($row['dateline'], 1), "{$row['description']}"));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(1 => array('do' , 'view')), 'viewform');
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->print_table_start($forums->lang['viewcronlog']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['viewwhichcronlog'] . "</strong>", $forums->admin->print_input_select_row('cronid', $crons)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['showcronlogs'] . "</strong>", $forums->admin->print_input_row('cron_count', '30')));
		$forums->admin->print_form_submit($forums->lang['viewcronlog']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do' , 'delete')), 'delform');
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->print_table_start($forums->lang['deletecronlog']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['deletewhichcronlog'] . "</strong>", $forums->admin->print_input_select_row('cronid', $crons)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['deletecronlogdays'] . "</strong>", $forums->admin->print_input_row('cron_prune', '30')));
		$forums->admin->print_form_submit($forums->lang['deletecronlog']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new cronlog();
$output->show();

?>