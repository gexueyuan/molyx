<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
require ('./global.php');

class filter
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditbans'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch ($_INPUT['do'])
		{
			case 'badword':
				$this->badword_start();
				break;
			case 'badword_add':
				$this->badword_add();
				break;
			case 'badword_remove':
				$this->badword_remove();
				break;
			case 'badword_edit':
				$this->badword_edit();
				break;
			case 'badword_doedit':
				$this->badword_doedit();
				break;
			case 'badword_export':
				$this->badword_export();
				break;
			case 'badword_import':
				$this->badword_import();
				break;
			case 'ban':
				$this->ban_start();
				break;
			case 'ban_add':
				$this->ban_add();
				break;
			case 'ban_delete':
				$this->ban_delete();
				break;
			default:
				$this->badword_start();
				break;
		}
	}

	function ban_delete()
	{
		global $forums, $DB, $_INPUT;
		if (is_array($_INPUT['id']))
		{
			foreach ($_INPUT['id'] AS $value)
			{
				if ($value)
				{
					$ids[] = $value;
				}
			}
		}
		if (count($ids))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "banfilter WHERE id IN(" . implode(",", $ids) . ")");
		}
		$forums->func->recache('banfilter');
		$forums->main_msg = $forums->lang['banfilterdeleted'];
		$this->ban_start();
	}

	function ban_add()
	{
		global $forums, $DB, $_INPUT;
		if (! $_INPUT['content'])
		{
			$forums->main_msg = $forums->lang['requirecontent'];
			$this->ban_start();
		}
		if ($result = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "banfilter WHERE type='" . $_INPUT['type'] . "' AND content='" . $_INPUT['content'] . "'"))
		{
			$forums->main_msg = $forums->lang['filterexist'];
			$this->ban_start();
		}
		$DB->insert(TABLE_PREFIX . 'banfilter', array('type' => $_INPUT['type'], 'content' => $_INPUT['content']));
		$forums->func->recache('banfilter');
		$forums->main_msg = $forums->lang['filteradded'];
		$this->ban_start();
	}

	function ban_start()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managebanfilter'];
		$detail = $forums->lang['managebanfilterdesc'];
		$forums->admin->nav[] = array('filter.php?do=ban' , $forums->lang['managebanfilter']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$ban = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "banfilter");
		while ($r = $DB->fetch_array())
		{
			$ban[ $r['type'] ][ $r['id'] ] = $r;
		}
		$forums->admin->print_form_header(array(1 => array('do', 'ban_delete'),));
		$forums->admin->columns[] = array("", "");
		$forums->admin->columns[] = array("", "100%");
		$forums->admin->print_table_start($forums->lang['managebanfilter']);
		$forums->admin->print_cells_single_row($forums->lang['ipbanned'], "left", "pformstrip");
		if (is_array($ban['ip']) AND count($ban['ip']))
		{
			foreach ($ban['ip'] AS $id => $entry)
			{
				$forums->admin->print_cells_row(array("<input type='checkbox' name='id[]' value='{$entry['id']}' />", $entry['content']));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noipbanned'], "left", "tdrow1");
		}
		$forums->admin->print_cells_single_row($forums->lang['emailbanned'], "left", "pformstrip");
		if (is_array($ban['email']) AND count($ban['email']))
		{
			foreach ($ban['email'] AS $id => $entry)
			{
				$forums->admin->print_cells_row(array("<input type='checkbox' name='id[]' value='{$entry['id']}' />", $entry['content']));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noemailbanned'], "left", "tdrow1");
		}
		$forums->admin->print_cells_single_row($forums->lang['namebanned'], "left", "pformstrip");
		if (is_array($ban['name']) AND count($ban['name']))
		{
			foreach ($ban['name'] AS $id => $entry)
			{
				$forums->admin->print_cells_row(array("<input type='checkbox' name='id[]' value='{$entry['id']}' />", $entry['content']));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['nonamebanned'], "left");
		}
		$forums->admin->print_cells_single_row($forums->lang['titlebanned'], "left", "pformstrip");
		if (is_array($ban['title']) AND count($ban['title']))
		{
			foreach ($ban['title'] AS $id => $entry)
			{
				$forums->admin->print_cells_row(array("<input type='checkbox' name='id[]' value='{$entry['id']}' />", $entry['content']));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['notitlebanned'], "left");
		}
		$end_it_now = "<div align='left' style='float:left;width:auto;'>
		 			   <input type='submit' value='" . $forums->lang['deleteselected'] . "' class='button' />
					   </div>";
		$forums->admin->print_cells_single_row($end_it_now, "center", "pformstrip");
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		echo "
		   <div align='center' class='pformstrip'><form method='post' action='filter.php?{$forums->sessionurl}do=ban_add'><input type='text' size='30' class='textinput' value='' name='content' />
		   <select class='dropdown' name='type'><option value='ip'>" . $forums->lang['ipaddress'] . "</option><option value='email'>" . $forums->lang['email'] . "</option><option value='name'>" . $forums->lang['username'] . "</option><option value='title'>" . $forums->lang['usertitle'] . "</option></select>
		   <input type='submit' value='" . $forums->lang['addnew'] . "' class='button' /></form></div>";
		$forums->admin->print_cp_footer();
	}

	function badword_start()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managebadword'];
		$detail = $forums->lang['managebadworddesc'];
		$forums->admin->nav[] = array('filter.php?do=badword' , $forums->lang['managebadword']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'badword_add'),));
		$forums->admin->columns[] = array($forums->lang['badword'], "30%");
		$forums->admin->columns[] = array($forums->lang['replaceword'], "30%");
		$forums->admin->columns[] = array($forums->lang['replacetype'], "20%");
		$forums->admin->columns[] = array($forums->lang['edit'], "10%");
		$forums->admin->columns[] = array($forums->lang['delete'], "10%");
		$forums->admin->print_table_start($forums->lang['badwordlist']);
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "badword ORDER BY badbefore");
		if ($DB->num_rows())
		{
			while ($r = $DB->fetch_array())
			{
				$words[] = $r;
			}
			foreach($words as $idx => $r)
			{
				$replace = $r['badafter'] ? $r['badafter'] : '******';
				$type = $r['type'] ? $forums->lang['exactmatch'] : $forums->lang['partmatch'];
				$forums->admin->print_cells_row(array($r['badbefore'], $replace, $type,
					"<center><a href='filter.php?{$forums->sessionurl}do=badword_edit&amp;id={$r['id']}'>" . $forums->lang['edit'] . "</a></center>",
					"<center><a href='filter.php?{$forums->sessionurl}do=badword_remove&amp;id={$r['id']}'>" . $forums->lang['delete'] . "</a></center>",
				));
			}
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array($forums->lang['badword'], "40%");
		$forums->admin->columns[] = array($forums->lang['replaceword'], "40%");
		$forums->admin->columns[] = array($forums->lang['replacetype'], "20%");
		$forums->admin->print_table_start($forums->lang['addnewbadword']);
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('badbefore'),
				$forums->admin->print_input_row('badafter'),
				$forums->admin->print_input_select_row('type', array(0 => array(0, $forums->lang['partmatch']), 1 => array(1, $forums->lang['exactmatch'])))
				));
		$forums->admin->print_form_submit($forums->lang['addnewbadword']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function badword_doedit()
	{
		global $forums, $_INPUT;
		if ($_INPUT['badbefore'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['requirebadword']);
		}
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$_INPUT['type'] = $_INPUT['type'] ? 1 : 0;
		strlen($_INPUT['badafter']) > 1 ? $_INPUT['badafter'] : "";
		$DB->update(TABLE_PREFIX . 'badword', array(
			'badbefore' => $_INPUT['badbefore'],
			'badafter' => $_INPUT['badafter'],
			'type' => $_INPUT['type']
		), "id='" . $_INPUT['id'] . "'");
		$forums->func->recache('badword');
		$forums->main_msg = $forums->lang['badwordedited'];
		$this->badword_start();
	}

	function badword_edit()
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['managebadword'];
		$detail = $forums->lang['managebadworddesc'];
		$forums->admin->nav[] = array('filter.php?do=badword' , $forums->lang['managebadword']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (! $r = $DB->query_first("SELECT badbefore,badafter,type FROM " . TABLE_PREFIX . "badword WHERE id='" . $_INPUT['id'] . "'"))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$forums->admin->print_form_header(array(1 => array('do' , 'badword_doedit'), 2 => array('id', $_INPUT['id'])));
		$forums->admin->columns[] = array($forums->lang['badword'], "40%");
		$forums->admin->columns[] = array($forums->lang['replaceword'], "40%");
		$forums->admin->columns[] = array($forums->lang['replacetype'], "20%");
		$forums->admin->print_table_start($forums->lang['editbadword']);
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('badbefore', $r['badbefore']),
				$forums->admin->print_input_row('badafter' , $r['badafter']),
				$forums->admin->print_input_select_row('type', array(0 => array(1, $forums->lang['exactmatch']), 1 => array(0, $forums->lang['partmatch'])), $r['type'])
				));
		$forums->admin->print_form_submit($forums->lang['editbadword']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function badword_remove()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "badword WHERE id='" . intval($_INPUT['id']) . "'");
		$forums->func->recache('badword');
		$forums->main_msg = $forums->lang['badworddeleted'];
		$this->badword_start();
	}

	function badword_add()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['badbefore'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['requirebadword']);
		}
		$_INPUT['type'] = $_INPUT['type'] ? 1 : 0;
		if ($badword = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "badword WHERE badbefore='" . $_INPUT['badbefore'] . "' AND type=" . $_INPUT['type'] . ""))
		{
			$forums->main_msg = $forums->lang['badwordexist'];
			$this->badword_start();
		}
		$DB->insert(TABLE_PREFIX . 'badword', array(
			'badbefore' => $_INPUT['badbefore'],
			'badafter' => $_INPUT['badafter'],
			'type' => $_INPUT['type'])
		);
		$forums->func->recache('badword');
		$forums->main_msg = $forums->lang['badwordadded'];
		$this->badword_start();
	}
}

$output = new filter();
$output->show();

?>