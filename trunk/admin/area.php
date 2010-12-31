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
class area
{
	function show()
	{
		global $forums, $bbuserinfo;
		$forums->func->load_lang('admin_area');
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditforums'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch (input::str('do'))
		{
			case 'add':
				$this->area_form('add');
				break;
			case 'doadd':
				$this->add_area();
				break;
			case 'list_content':
				$this->area_content_list();
				break;
			case 'add_content':
				$this->content_form('add');
				break;
			case 'doadd_content':
				$this->doadd_content();
				break;
			case 'edit_content':
				$this->content_form('edit');
				break;
			case 'doedit_content':
				$this->doedit_content();
				break;
			case 'del_content':
				$this->del_content();
				break;
			default:
				$this->show_list();
				break;
		}
	}

	function show_list()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['adminarea'];
		$detail = $forums->lang['adminareadesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add')));
		$forums->admin->columns[] = array($forums->lang['areaid'], "10%");
		$forums->admin->columns[] = array($forums->lang['areaname'], "15%");
		$forums->admin->columns[] = array($forums->lang['show_record'], "15%");
		$forums->admin->columns[] = array($forums->lang['manage'], "20%");
		$forums->admin->print_table_start($title);
		$forums->adminforum->moderator = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "area ORDER BY orderid ASC");
		while ($r = $DB->fetch())
		{
			//$manage = "<a href='area.php?{$forums->sessionurl}do=remove&amp;id={$r['moderatorid']}'>" . $forums->lang['delete'] . "</a>&nbsp;<a href='area.php?{$forums->sessionurl}do=edit&amp;u={$r['moderatorid']}'>" . $forums->lang['edit'] . "</a>&nbsp;";
			$r['areaname'] = "<a href='area.php?{$forums->sessionurl}do=list_content&amp;areaid={$r['areaid']}'>" . $r['areaname'] . "</a>";
			$forums->admin->print_cells_row(array($r['areaid'], $r['areaname'], $r['show_record'], $manage));
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function area_content_list()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['area_content_list'];
		$detail = $forums->lang['area_content_list'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add')));
		$forums->admin->columns[] = array($forums->lang['area_contentid'], "5%");
		$forums->admin->columns[] = array($forums->lang['content_title'], "50%");
		$forums->admin->columns[] = array($forums->lang['content_target'], "5%");
		$forums->admin->columns[] = array($forums->lang['bareaname'], "20%");
		$forums->admin->columns[] = array($forums->lang['manage'], "20%");
		$forums->admin->print_table_start($title);
		$forums->adminforum->moderator = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "area_content ac
						LEFT JOIN " . TABLE_PREFIX . "area a
							ON a.areaid=ac.areaid
					WHERE ac.areaid=" . intval(input::int('areaid')) . " ORDER BY ac.orderid ASC, ac.id DESC");
		while ($r = $DB->fetch())
		{
			$manage = "<a href='area.php?{$forums->sessionurl}do=del_content&amp;id={$r['id']}&amp;areaid=".intval(input::int('areaid'))."' onclick=\"return confirm('{$forums->lang['confirmdelete']}');\">" . $forums->lang['delete'] . "</a>&nbsp;<a href='area.php?{$forums->sessionurl}do=edit_content&amp;id={$r['id']}'>" . $forums->lang['edit'] . "</a>&nbsp;";
			if ($r['titlelink'])
			{
				$r['title'] = "<a href='{$r['titlelink']}' target='_blank'>" . $r['title'] . "</a>";
			}
			$forums->admin->print_cells_row(array($r['id'], $r['title'], $r['target'], $r['areaname'], $manage));
		}
		$extra = '<input type="button" class="button" value="' . $forums->lang['add_area_content'] . '" name="setbt" onclick="document.location.href=\'area.php?' . $forums->sessionurl . 'do=add_content&areaid='. intval(input::int('areaid')) .'\'" />';
		$forums->admin->print_cells_single_row($extra, 'right');
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function content_form($type = 'add')
	{
		global $forums, $DB;
		$hiddens = array();
		if ($type == 'edit')
		{
			$pagetitle = $forums->lang['edit_area_content'];
			$action = 'doedit_content';
			$detail = '';
			$table_title = $forums->lang['edit_area_content'];
			$content_info = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "area_content WHERE id=" . intval(input::int('id')));
			$hiddens[] = array('id', input::int('id'));
		}
		else
		{
			$pagetitle = $forums->lang['add_area_content'];
			$action = 'doadd_content';
			$detail = '';
			$table_title = $forums->lang['add_area_content'];
		}
		$hiddens[] = array('do', $action);


		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header($hiddens);
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$areainfo = $this->fetch_area();
		$forums->admin->print_table_start($table_title);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_title'] . "</strong>" ,
				$forums->admin->print_input_row("title", input::str('title'))
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_link'] . "</strong>" ,
				$forums->admin->print_input_row("titlelink", input::str('titlelink'))
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_target'] . "</strong>" ,
				$forums->admin->print_input_row("target", input::str('target'), 'text', '', 8)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_order'] . "</strong>" ,
				$forums->admin->print_input_row("orderid", input::str('orderid'), 'text', '', 8)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bareaname'] . "</strong>" ,
				$forums->admin->print_input_select_row("areaid", $areainfo, input::str('areaid'))
				));
		$forums->admin->print_form_end($table_title);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function doadd_content()
	{
		global $DB, $forums;
		$area_content = array('title' => input::str('title'),
			'titlelink' => input::str('titlelink'),
			'target' => input::str('target'),
			'areaid' => input::str('areaid'),
			'orderid' => input::str('orderid'),
			);
		$DB->insert(TABLE_PREFIX . 'area_content', $area_content);
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid=".intval(input::int('areaid')), $forums->lang['area_content_list'], $forums->lang['add_content_suc']);
	}

	function doedit_content()
	{
		global $DB, $forums;
		$area_content = array('title' => input::str('title'),
			'titlelink' => input::str('titlelink'),
			'target' => input::str('target'),
			'areaid' => input::str('areaid'),
			'orderid' => input::str('orderid'),
			);
		$DB->update(TABLE_PREFIX . 'area_content', $area_content, 'id = ' . intval(input::int('id')));
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid=".intval(input::int('areaid')), $forums->lang['area_content_list'], $forums->lang['edit_content_suc']);
	}
	function del_content()
	{
		global $DB, $forums;
		$DB->delete(TABLE_PREFIX . 'area_content', 'id = ' . intval(input::int('id')));
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid=".intval(input::int('areaid')), $forums->lang['area_content_list'], $forums->lang['del_content_suc']);
	}

	function fetch_area()
	{
		global $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "area ORDER BY orderid ASC");
		$ret = array();
		while ($r = $DB->fetch())
		{
			$ret[$r['areaid']] = array($r['areaid'], $r['areaname']);
		}
		return $ret;
	}

	function recache()
	{
		global $forums;
		cache::get('forum');
		foreach ($forums->cache['forum'] AS $fid => $v)
		{
			input::set('f', $fid);
			cache::update('forum_area');
		}
	}
}

$output = new area();
$output->show();

?>