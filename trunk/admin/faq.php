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

class faq
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditothers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('faq.php' , $forums->lang['managefaq']);
		switch ($_INPUT['do'])
		{
			case 'edit':
				$this->showform('edit');
				break;
			case 'new':
				$this->showform('new');
				break;
			case 'doedit':
				$this->doedit('edit');
				break;
			case 'donew':
				$this->doedit('add');
				break;
			case 'remove':
				$this->remove();
				break;
			default:
				$this->list_files();
				break;
		}
	}

	function doedit($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($_POST['title'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['inputfaqtitle']);
		}
		$title = convert_andstr($_POST['title']);
		if ($_INPUT['parentid'])
		{
			$text = preg_replace(array("/\n/", "/\\\/"), array("<br />", "&#092;"), convert_andstr($_POST['text']));
		}
		$desc = preg_replace("/\n/", "<br />", convert_andstr($_POST['description']));
		$sql_array = array(
			'title' => $title,
			'text' => $text,
			'description' => $description,
			'parentid' => $_INPUT['parentid'],
			'displayorder' => $_INPUT['displayorder']
		);
		if ($type == 'add')
		{
			$DB->insert(TABLE_PREFIX . 'faq', $sql_array);
			$forums->admin->save_log($forums->lang['addnewfaq']);
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'faq', $sql_array, 'id = ' . intval($_INPUT['id']));
			$forums->admin->save_log($forums->lang['editnewfaq']);
		}
		$forums->func->standard_redirect("faq.php?" . $forums->sessionurl);
		exit();
	}

	function showform($type = 'new')
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['managefaq'];
		$detail = $forums->lang['managefaqdesc'];
		if ($type != 'new')
		{
			if ($_INPUT['id'] == "")
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			if (! $r = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "faq WHERE id='" . intval($_INPUT['id']) . "'"))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$button = $forums->lang['editfaq'];
			$forums->admin->nav[] = array('' , $forums->lang['editfaq']);
			$code = 'doedit';
		}
		else
		{
			$r = array();
			$button = $forums->lang['addnewfaq'];
			$forums->admin->nav[] = array('' , $forums->lang['addnewfaq']);
			$code = 'donew';
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$p_array[] = array(0, $forums->lang['tobecategory']);
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "faq WHERE parentid=0 ORDER BY displayorder");
		if ($DB->num_rows())
		{
			while ($parent = $DB->fetch_array())
			{
				$p_array[] = array($parent['id'], $parent['title']);
			}
		}
		$forums->admin->print_form_header(array(1 => array('do' , $code), 2 => array('id' , $_INPUT['id'])));
		$forums->admin->columns[] = array("&nbsp;" , "30%");
		$forums->admin->columns[] = array("&nbsp;" , "70%");
		$r['text'] = preg_replace("#<br.*>#siU", "\n", $r['text']);
		$forums->admin->print_table_start($button);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['faqtitle'] . "</strong>", $forums->admin->print_input_row('title', $r['title'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['faqdesc'] . "</strong>", $forums->admin->print_textarea_row('description', $r['description'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['faqtext'] . "</strong>", $forums->admin->print_textarea_row('text', utf8_htmlspecialchars($r['text']), "60", "10")));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['faqparent'] . "</strong>", $forums->admin->print_input_select_row("parentid", $p_array, $r['parentid'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['displayorder'] . "</strong>", $forums->admin->print_input_row('displayorder', $r['displayorder'] ? intval($r['displayorder']) : 0)));
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function remove()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($_INPUT['update'])
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "faq WHERE id=" . intval($_INPUT['id']) . " OR parentid=" . intval($_INPUT['id']) . "");
			$forums->admin->save_log($forums->lang['deletefaq']);
			$forums->func->standard_redirect("faq.php?" . $forums->sessionurl);
			exit();
		}
		else
		{
			$pagetitle = $forums->lang['deletefaq'];
			$detail = $forums->lang['confirmdeleteaction'];
			$forums->admin->nav[] = array('', $forums->lang['deletefaq']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'remove'), 2 => array('id', $_INPUT['id']), 3 => array('update', 1)));
			$forums->admin->print_table_start($forums->lang['affdeletefaq']);
			$forums->admin->print_cells_single_row($forums->lang['affdeletefaqdesc'], "center");
			$forums->admin->print_form_submit($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}

	function list_files()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managefaq'];
		$detail = $forums->lang['managefaqdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$newfaq = $forums->admin->print_button($forums->lang['addnewfaq'], "faq.php?{$forums->sessionurl}do=new");
		$forums->admin->print_table_start($forums->lang['currentlyfaq'], "", "<div style='float:right'>$newfaq&nbsp;</div>");
		$pfaqs = $DB->query("SELECT * FROM " . TABLE_PREFIX . "faq WHERE parentid=0 ORDER BY displayorder");
		if ($DB->num_rows($pfaqs))
		{
			while ($pfaq = $DB->fetch_array($pfaqs))
			{
				$faqlist .= "<ul><li><a href='faq.php?{$forums->sessionurl}do=edit&amp;id=" . $pfaq['id'] . "'><strong>" . $pfaq['title'] . "</strong></a> <a href='faq.php?{$forums->sessionurl}do=edit&amp;id=" . $pfaq['id'] . "'>[" . $forums->lang['edit'] . "]</a> <a href='faq.php?{$forums->sessionurl}do=remove&amp;id=" . $pfaq['id'] . "'>[" . $forums->lang['delete'] . "]</a></li></ul>\n";
				$subfaqs = $DB->query("SELECT * FROM " . TABLE_PREFIX . "faq WHERE parentid=" . $pfaq['id'] . " ORDER BY displayorder");
				if ($DB->num_rows($subfaqs))
				{
					$faqlist .= "<ul>\n";
					while ($subfaq = $DB->fetch_array($subfaqs))
					{
						$faqlist .= "<li><a href='faq.php?{$forums->sessionurl}do=edit&amp;id=" . $subfaq['id'] . "'><strong>" . $subfaq['title'] . "</strong></a> <a href='faq.php?{$forums->sessionurl}do=edit&amp;id=" . $subfaq['id'] . "'>[" . $forums->lang['edit'] . "]</a> <a href='faq.php?{$forums->sessionurl}do=remove&amp;id=" . $subfaq['id'] . "'>[" . $forums->lang['delete'] . "]</a></li>";
					}
					$faqlist .= "</ul>\n";
				}
			}
		}
		else
		{
			$faqlist .= $forums->lang['nofaq'];
		}
		$forums->admin->print_cells_single_row($faqlist);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}
}

$output = new faq();
$output->show();

?>