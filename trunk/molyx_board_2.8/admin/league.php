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
# $Id: league.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class league
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditleagues'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('league.php', $forums->lang['manageleague']);
		switch ($_INPUT['do'])
		{
			case 'addleague':
				$this->league_form('add');
				break;
			case 'editleague':
				$this->league_form('edit');
				break;
			case 'updateleague':
				$this->updateleague();
				break;
			case 'removeleague':
				$this->removeleague();
				break;
			case 'reorder':
				$this->reorder();
				break;
			default:
				$this->leaguelist();
				break;
		}
	}

	function leaguelist()
	{
		global $forums, $_INPUT, $DB;
		$pagetitle = $forums->lang['manageleague'];
		$detail = $forums->lang['manageleaguedesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'reorder')));
		echo "<script type='text/javascript'>\n";
		echo "function js_league_jump(leagueinfo)\n";
		echo "{\n";
		echo "value = eval('document.cpform.id' + leagueinfo + '.options[document.cpform.id' + leagueinfo + '.selectedIndex].value');\n";
		echo "if (value=='removeleague') {\n";
		echo "okdelete = confirm('" . $forums->lang['wantdeleteleague'] . "');\n";
		echo "if ( okdelete == false ) {\n";
		echo "return false;\n";
		echo "}\n";
		echo "}\n";
		echo "window.location = 'league.php?{$forums->js_sessionurl}do=' + value + '&id=' + leagueinfo;\n";
		echo "}\n";
		echo "</script>\n";
		$forums->admin->columns[] = array($forums->lang['leaguesite'], "30%");
		$forums->admin->columns[] = array($forums->lang['leagueicon'], "35%");
		$forums->admin->columns[] = array($forums->lang['action'] , "25%");
		$forums->admin->columns[] = array($forums->lang['displayorder'], "10%");
		$forums->admin->print_table_start($forums->lang['league']);
		$nodisplay = true;
		$imgsite = true;
		$textsite = true;
		$linesite = true;

		$leagues = $DB->query("SELECT * FROM " . TABLE_PREFIX . "league ORDER BY type, displayorder");
		if ($DB->num_rows($leagues))
		{
			while ($league = $DB->fetch_array($leagues))
			{
				if ($linesite AND $league['type'] == 0)
				{
					$forums->admin->print_cells_single_row($forums->lang['flatrange'], "left", "pformstrip");
					$linesite = false;
				}
				if ($imgsite AND $league['type'] == 1)
				{
					$forums->admin->print_cells_single_row($forums->lang['imageleague'], "left", "pformstrip");
					$imgsite = false;
				}
				if ($textsite AND $league['type'] == 2)
				{
					$forums->admin->print_cells_single_row($forums->lang['textleague'], "left", "pformstrip");
					$textsite = false;
				}
				if ($nodisplay AND $league['type'] == 3)
				{
					$forums->admin->print_cells_single_row($forums->lang['nodisplayleague'], "left", "pformstrip");
					$nodisplay = false;
				}
				$forums->admin->print_cells_row(array("<a href='" . $league['siteurl'] . "' target='_blank' title='" . $league['siteinfo'] . "'><strong>" . $league['sitename'] . "</strong></a>",
						$league['siteimage'] ? "<img src='" . ((substr($league['siteimage'], 0, 7) != 'http://' AND substr($league['siteimage'], 0, 1) != '/') ? '../' : '') . $league['siteimage'] . "' alt='" . $league['siteimage'] . "' align='middle' />" : '&nbsp;',
						$forums->admin->print_input_select_row('id' . $league['leagueid'],
							array(0 => array('editleague', $forums->lang['editlague']),
								1 => array('removeleague', $forums->lang['deletelague'])
								), '', "onchange='js_league_jump(" . $league['leagueid'] . ");'") . "<input type='button' class='button' value='" . $forums->lang['ok'] . "' onclick='js_league_jump(" . $league['leagueid'] . ");' />",
						$forums->admin->print_input_row("order[" . $league['leagueid'] . "]", $league['displayorder'], "", "", 5)
						));
			}
		}

		$forums->admin->print_form_submit($forums->lang['reorder'], '', " " . $forums->admin->print_button($forums->lang['addlague'], "league.php?{$forums->sessionurl}do=addleague"));
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function league_form($type = 'edit')
	{
		global $forums, $_INPUT, $DB;
		$leagueid = intval($_INPUT['id']);
		if ($type == 'edit')
		{
			$pagetitle = $forums->lang['editlague'];
			$detail = $forums->lang['editlaguedesc'];
			if (!$_INPUT['id'] OR !$league = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "league WHERE leagueid=" . $leagueid . ""))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
		}
		else
		{
			$pagetitle = $forums->lang['addlague'];
			$detail = $forums->lang['addlaguedesc'];
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_form_header(array(1 => array('do' , 'updateleague'), 2 => array('id' , $leagueid)));
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sitename'] . "</strong>", $forums->admin->print_input_row("sitename", utf8_htmlspecialchars($league['sitename']))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['siteurl'] . "</strong>", $forums->admin->print_input_row("siteurl", $league['siteurl'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['siteimage'] . "</strong>", $forums->admin->print_input_row("siteimage", $league['siteimage'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['siteinfo'] . "</strong>", $forums->admin->print_textarea_row("siteinfo", $league['siteinfo'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sitedisplaytype'] . "</strong>" ,
				$forums->admin->print_input_select_row('type', array(0 => array('0', $forums->lang['flatleague']),
						1 => array('1', $forums->lang['imageleague']),
						2 => array('2', $forums->lang['textleague']),
						3 => array('3', $forums->lang['nodisplay'])
						), $league['type']
					),
				));
		$forums->admin->print_form_submit($pagetitle);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function updateleague()
	{
		global $forums, $_INPUT, $DB;
		if (!$_POST['sitename'] OR !$_POST['siteurl'])
		{
			$forums->admin->print_cp_error($forums->lang['inputallforms']);
		}
		$leagueid = intval($_INPUT['id']);
		$league = array(
			'sitename' => $_INPUT['sitename'],
			'siteurl' => $_INPUT['siteurl'],
			'siteimage' => $_INPUT['siteimage'],
			'siteinfo' => $_INPUT['siteinfo'],
			'type' => $_INPUT['type']
		);
		if ($leagueid)
		{
			$DB->update(TABLE_PREFIX . 'league', $league, "leagueid = $leagueid");
		}
		else
		{
			$DB->insert(TABLE_PREFIX . 'league', $league);
		}
		$forums->func->recache('league');
		$forums->admin->save_log($forums->lang['addlague'] . " - {$_INPUT['sitename']}");
		$forums->admin->redirect("league.php", $forums->lang['manageleague'], $forums->lang['leaguechanged']);
	}

	function removeleague()
	{
		global $forums, $_INPUT, $DB;
		if (!$_INPUT['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "league WHERE leagueid=" . intval($_INPUT['id']) . "");
		$forums->func->recache('league');
		$forums->admin->save_log($forums->lang['deletelague']);
		$forums->admin->redirect("league.php", $forums->lang['manageleague'], $forums->lang['leaguedeleted']);
	}

	function reorder()
	{
		global $forums, $_INPUT, $DB;
		if (is_array($_INPUT['order']))
		{
			$leagues = $DB->query("SELECT leagueid,displayorder FROM " . TABLE_PREFIX . "league");
			while ($league = $DB->fetch_array($leagues))
			{
				if (!isset($_INPUT['order'][$league['leagueid']]))
				{
					continue;
				}
				$displayorder = intval($_INPUT['order'][$league['leagueid']]);
				if ($league['displayorder'] != $displayorder)
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "league SET displayorder = $displayorder WHERE leagueid = " . $league['leagueid'] . "");
				}
			}
		}
		$this->cache->league_recache();
		$forums->admin->save_log($forums->lang['changeleagueorder']);
		$forums->admin->redirect("league.php", $forums->lang['manageleague'], $forums->lang['leaguereordered']);
	}
}

$output = new league();
$output->show();

?>