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
# $Id: cronlog.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class cronlog
{
	function show()
	{
		global $forums, $_INPUT;
		$forums->admin->nav[] = array('cronlog.php', $forums->lang['runcronlog']);
		switch ($_INPUT['do'])
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
		global $forums, $DB, $_INPUT;
		$prune = is_numeric($_INPUT['cron_prune']) ? intval($_INPUT['cron_prune']) : 30;
		$prune = TIMENOW - ($prune * 86400);
		if ($_INPUT['cronid'] != -1)
		{
			$where = "title='" . $_INPUT['cronid'] . "' AND dateline < $prune";
		}
		else
		{
			$where = "dateline < $prune";
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "cronlog WHERE " . $where . "");
		$forums->main_msg = $forums->lang['cronlogdeleted'];
		$this->listlog();
	}

	function view()
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['runcronlog'];
		$detail = $forums->lang['runcronlogdesc'];
		$forums->admin->nav[] = array('', $forums->lang['cronloglist']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$limit = $_INPUT['cron_count'] ? $_INPUT['cron_count'] : 30;
		$limit = $limit > 150 ? 150 : $limit;
		if ($_INPUT['cronid'] != -1)
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog WHERE title='" . $_INPUT['cronid'] . "' ORDER BY dateline DESC LIMIT 0, " . $limit . "");
		}
		else
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog ORDER BY dateline DESC LIMIT 0, " . $limit . "");
		}
		$forums->admin->columns[] = array($forums->lang['cronexecute'], "20%");
		$forums->admin->columns[] = array($forums->lang['crontime'], "35%");
		$forums->admin->columns[] = array($forums->lang['cronloginfo'], "45%");
		$forums->admin->print_table_start($forums->lang['selectedcronlog']);
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
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
		while ($pee = $DB->fetch_array())
		{
			$crons[] = array($pee['cronid'], $pee['title']);
		}
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "cronlog ORDER BY dateline DESC LIMIT 0, 5");
		$forums->admin->columns[] = array($forums->lang['cronexecute'], "20%");
		$forums->admin->columns[] = array($forums->lang['crontime'], "35%");
		$forums->admin->columns[] = array($forums->lang['cronloginfo'], "45%");
		$forums->admin->print_table_start($forums->lang['lastfivecron']);
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
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