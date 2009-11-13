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
# $Id: area.php 330 2007-10-23 10:26:53Z develop_tong $
# **************************************************************************#
require ('./global.php');
$forums->lang['adminarea'] = '版面自定义区块管理';
$forums->lang['adminareadesc'] = '在这里可以管理版面内的自定义区块和区块内容';
$forums->lang['area_content_list'] = '区块内容列表';
$forums->lang['add_content_suc'] = '区块内容添加成功';
$forums->lang['edit_content_suc'] = '区块内容编辑成功';
$forums->lang['del_content_suc'] = '区块内容删除成功';
$forums->lang['add_area_content'] = '添加区块内容';
$forums->lang['edit_area_content'] = '编辑区块内容';
$forums->lang['areaid'] = '区块ID';
$forums->lang['areaname'] = '区块名称';
$forums->lang['show_record'] = '显示记录数';
$forums->lang['area_contentid'] = '区块内容ID';
$forums->lang['bareaname'] = '所属区块';
$forums->lang['content_title'] = '内容标题';
$forums->lang['content_target'] = '链接窗口';
$forums->lang['bareaname'] = '所属区块';
$forums->lang['manage'] = '管理';
$forums->lang['content_link'] = '区块链接';
$forums->lang['content_order'] = '内容排序';
class area
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditforums'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch ($_INPUT['do'])
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
		global $forums, $DB, $_INPUT;
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
		while ($r = $DB->fetch_array())
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
		global $forums, $DB, $_INPUT;
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
					WHERE ac.areaid=" . intval($_INPUT['areaid']) . " ORDER BY ac.orderid ASC, ac.id DESC");
		while ($r = $DB->fetch_array())
		{
			$manage = "<a href='area.php?{$forums->sessionurl}do=del_content&amp;id={$r['id']}&amp;areaid={$_INPUT['areaid']}' onclick=\"return confirm('{$forums->lang['confirmdelete']}');\">" . $forums->lang['delete'] . "</a>&nbsp;<a href='area.php?{$forums->sessionurl}do=edit_content&amp;id={$r['id']}'>" . $forums->lang['edit'] . "</a>&nbsp;";
			if ($r['titlelink']) 
			{
				$r['title'] = "<a href='{$r['titlelink']}' target='_blank'>" . $r['title'] . "</a>";
			}
			$forums->admin->print_cells_row(array($r['id'], $r['title'], $r['target'], $r['areaname'], $manage));
		}
		$extra = '<input type="button" class="button" value="' . $forums->lang['add_area_content'] . '" name="setbt" onclick="document.location.href=\'area.php?' . $forums->sessionurl . 'do=add_content&areaid='. $_INPUT['areaid'] .'\'" />';
		$forums->admin->print_cells_single_row($extra, 'right');
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
	
	function content_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		$hiddens = array();
		if ($type == 'edit') 
		{
			$pagetitle = $forums->lang['edit_area_content'];
			$action = 'doedit_content';
			$detail = '';
			$table_title = $forums->lang['edit_area_content'];
			$content_info = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "area_content WHERE id=" . intval($_INPUT['id']));
			$hiddens[] = array('id', $_INPUT['id']);
		}
		else 
		{
			$pagetitle = $forums->lang['add_area_content'];
			$action = 'doadd_content';
			$detail = '';
			$table_title = $forums->lang['add_area_content'];
			$content_info = $_INPUT;
		}
		$hiddens[] = array('do', $action);
		
		
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header($hiddens);
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$areainfo = $this->fetch_area();
		$forums->admin->print_table_start($table_title);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_title'] . "</strong>" ,
				$forums->admin->print_input_row("title", $content_info['title'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_link'] . "</strong>" ,
				$forums->admin->print_input_row("titlelink", $content_info['titlelink'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_target'] . "</strong>" ,
				$forums->admin->print_input_row("target", $content_info['target'], 'text', '', 8)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['content_order'] . "</strong>" ,
				$forums->admin->print_input_row("orderid", $content_info['orderid'], 'text', '', 8)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bareaname'] . "</strong>" ,
				$forums->admin->print_input_select_row("areaid", $areainfo, $content_info['areaid'])
				));
		$forums->admin->print_form_end($table_title);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}
	
	function doadd_content()
	{
		global $DB, $forums, $_INPUT;
		$area_content = array('title' => $_INPUT['title'],
			'titlelink' => $_INPUT['titlelink'],
			'target' => $_INPUT['target'],
			'areaid' => $_INPUT['areaid'],
			'orderid' => $_INPUT['orderid'],
			);
		$DB->insert(TABLE_PREFIX . 'area_content', $area_content);
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid={$_INPUT['areaid']}", $forums->lang['area_content_list'], $forums->lang['add_content_suc']);
	}
	
	function doedit_content()
	{
		global $DB, $forums, $_INPUT;
		$area_content = array('title' => $_INPUT['title'],
			'titlelink' => $_INPUT['titlelink'],
			'target' => $_INPUT['target'],
			'areaid' => $_INPUT['areaid'],
			'orderid' => $_INPUT['orderid'],
			);
		$DB->update(TABLE_PREFIX . 'area_content', $area_content, 'id = ' . intval($_INPUT['id']));
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid={$_INPUT['areaid']}", $forums->lang['area_content_list'], $forums->lang['edit_content_suc']);
	}
	function del_content()
	{
		global $DB, $forums, $_INPUT;
		$DB->delete(TABLE_PREFIX . 'area_content', 'id = ' . intval($_INPUT['id']));
		$this->recache();
		$forums->admin->redirect("area.php?do=list_content&amp;areaid={$_INPUT['areaid']}", $forums->lang['area_content_list'], $forums->lang['del_content_suc']);
	}
	
	function fetch_area()
	{
		global $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "area ORDER BY orderid ASC");
		$ret = array();
		while ($r = $DB->fetch_array())
		{
			$ret[$r['areaid']] = array($r['areaid'], $r['areaname']);
		}
		return $ret;
	}
	
	function recache()
	{
		global $forums, $_INPUT;
		$forums->func->check_cache('forum');
		foreach ($forums->cache['forum'] AS $fid => $v)
		{
			$_INPUT['f'] = $fid;
			$forums->func->recache('forum_area');
		}
	}
}

$output = new area();
$output->show();

?>