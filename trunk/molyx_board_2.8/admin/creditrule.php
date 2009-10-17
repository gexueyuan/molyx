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
# $Id: creditrule.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class creditrule
{
	function show()
	{
		global $forums, $_INPUT, $DB, $bbuserinfo;
		$forums->func->load_lang('admin_credit');
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}

		switch ($_INPUT['do'])
		{
			case 'add':
				$this->creditrule_form('add');
			break;
			case 'changetype':
				$this->creditrule_form($_INPUT['grouptype']);
			break;
			case 'edit':
				$this->creditrule_form('edit');
			break;	
			case 'delete':
				$this->delete_creditrule();
			break;
			case 'doedit':
				$this->doedit();
			break;
			default:
				$this->creditrulelist();
			break;
		}
	}

	function creditrulelist()
	{
		global $forums, $DB, $_INPUT;
		
		$pp = $_INPUT['pp']?intval($_INPUT['pp']):0;
		$pagetitle = $forums->lang['managecredit'];
		$detail = $forums->lang['managecreditruledesc'];
		$forums->admin->nav[] = array('creditrule.php' , $forums->lang['creditrulelist']);

		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add')));

		$row = $DB->query_first('SELECT count(ruleid) as total FROM ' . TABLE_PREFIX . 'creditrule');
		$row_count = $row['total'];
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
			'perpage' => 10,
			'curpage' => $pp,
			'pagelink' => "creditrule.php?" . $forums->sessionurl,
			)
		);
		$forums->admin->print_cells_single_row($links, 'right', 'pformstrip');
		$forums->admin->columns[] = array($forums->lang['credit_rule_name'], '23%');
		$forums->admin->columns[] = array($forums->lang['credit_tag_name'], '18%');
		$forums->admin->columns[] = array($forums->lang['credit_rule_group'], '14%');
		$forums->admin->columns[] = array($forums->lang['credit_rule_text'], '15%');
		$forums->admin->columns[] = array($forums->lang['action'], '25%');
		$forums->admin->print_table_start($pagetitle);
		
		$result = $DB->query('SELECT * FROM ' . TABLE_PREFIX . "creditrule order by grouptype Limit $pp, 10");
		if ($DB->num_rows($result))
		{
			while ($rule = $DB->fetch_array($result))
			{
				switch ($rule['grouptype'])
				{
					case 'usergroup':
						$ruletype = $forums->lang['credit_usergroup'];
						break;
					case 'forum':
						$ruletype = $forums->lang['credit_forum'];
						break;
					case 'revise':
						$ruletype = $forums->lang['credit_revise'];
						break;
					default:
						$ruletype = $forums->lang['credit_global'];
				}
				switch ($rule['texttype'])
				{
					case 'rangevalue':
						$texttype = $forums->lang['credit_rangevalue'];
						break;
					case 'fixvalue':
						$texttype = $forums->lang['credit_fixvalue'];
						break;
				}
				$forums->admin->print_cells_row(array(
					"<center>" . $rule['rule_name'] . "</center>",
					"<center>" . $rule['rule_tag'] . "</center>",
					"<center>" . $ruletype . "</center>",
					"<center>" . $texttype . "</center>",
					$rule['isdefault'] != 1?"<center><a href='creditrule.php?{$forums->sessionurl}do=edit&amp;id={$rule['ruleid']}'>{$forums->lang['edit']}</a> | 
					<a href='creditrule.php?{$forums->sessionurl}do=delete&amp;id={$rule['ruleid']}'>{$forums->lang['delete']}</a></center>":'&nbsp;',
				));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row("<strong>{$forums->lang['no_any_creditrules']}</strong>", 'center');
		}
		
		$forums->admin->print_form_submit($forums->lang['add_new_creditrule']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();

		$forums->admin->print_cp_footer();
	}

	function creditrule_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == "edit")
		{
			$id = intval($_INPUT['id']);
			if (!$id)
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$rule = $DB->query_first("SELECT *
				FROM " . TABLE_PREFIX . "creditrule
				WHERE ruleid = $id");
			if (!$rule['ruleid'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$pagetitle = $forums->lang['edit_creditrule'];
			$button = $forums->lang['edit_creditrule'];
		}
		else
		{
			$pagetitle = $forums->lang['add_creditrule'];
			$button = $forums->lang['add_creditrule'];
		}
		$forums->admin->nav[] = array('creditrule.php' , $forums->lang['creditrulelist']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(
			1 => array('do' , 'doedit'), 
			2 => array('id', $rule['ruleid']),
			3 => array('grouptype', $rule['grouptype']?$rule['grouptype']:$_INPUT['grouptype']),
		));
		$forums->admin->columns[] = array('&nbsp;', '40%');
		$forums->admin->columns[] = array('&nbsp;', '60%');
		$forums->admin->print_table_start($button);
		$rulegroup = array(
		   0=>array('global',$forums->lang['credit_global']),
		   1=>array('usergroup',$forums->lang['credit_usergroup']),
	       2=>array('forum',$forums->lang['credit_forum']),
	       3=>array('revise',$forums->lang['credit_revise']));
	    $extra = $type == "edit"?'disabled="disabled"':'';
		$rulejs = "$extra onchange=\"document.cpform['do'].value ='changetype';this.form.submit();\"";
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_group']}</strong>", $forums->admin->print_input_select_row('grouptype', $rulegroup, $_INPUT['grouptype'] ? $_INPUT['grouptype'] : $rule['grouptype'], $rulejs)));
		
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_name']}</strong>", $forums->admin->print_input_row('rule_name', $_INPUT['rule_name'] ? $_INPUT['rule_name'] : $rule['rule_name'])));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_tag_name']}</strong><div class='description'>{$forums->lang['credit_tag_name_desc']}</div>", $rule['rule_tag'] ? $rule['rule_tag'] : $forums->admin->print_input_row('rule_tag', $_INPUT['rule_tag'] ? $_INPUT['rule_tag'] : "")));

		if ($type == 'revise' || $rule['grouptype'] == 'revise')
		{
			$normalrule = $existrule = array();
			$DB->query("SELECT action_tag FROM " . TABLE_PREFIX . "creditrule WHERE grouptype = 'revise'");
			while($row = $DB->fetch_array())
			{
				$existrule[] = $row['action_tag'];
			}
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "creditrule 
			  WHERE texttype = 'fixvalue' and grouptype != 'revise' and grouptype != 'global'");
			while($row = $DB->fetch_array())
			{
				if (!in_array($row['rule_tag'], $existrule)) $normalrule[] = array($row['rule_tag'],$row['rule_name']);
			}
			$forums->admin->print_cells_row(array("<strong>{$forums->lang['selreviseactevent']}</strong><div class='description'>{$forums->lang['selreviseactevent_desc']}</div>", $forums->admin->print_input_select_row('action_tag', $normalrule, $_INPUT['action_tag'] ? $_INPUT['action_tag'] : $rule['action_tag'])));
		}
		else
		{
			$ruletext = array(0=>array('fixvalue',$forums->lang['credit_fixvalue']),
		    	1=>array('rangevalue',$forums->lang['credit_rangevalue']),
			);
			$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_text']}</strong>", $forums->admin->print_input_select_row('texttype', $ruletext, $_INPUT['texttype'] ? $_INPUT['texttype'] : $rule['texttype'])));
			
			$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_desction']}</strong>", $forums->admin->print_textarea_row('description', $rule['description'])));
		}
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doedit($redirect = true)
	{
		global $forums, $DB, $_INPUT;
		$id = intval($_INPUT['id']);
		$_INPUT['rule_name'] = trim($_INPUT['rule_name']);
		$_INPUT['rule_tag'] = strtolower(trim($_INPUT['rule_tag']));
		if (!$_INPUT['rule_name'])
		{
			$forums->admin->print_cp_error($forums->lang['require_creditrule_name']);
		}
		if ($id)
		{
			$rule = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . "creditrule
				WHERE ruleid = $id");
			if (!$rule['ruleid'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
		}
		else
		{
			if (!preg_match('#^(\w+)$#i', $_INPUT['rule_tag']))
			{
				$forums->admin->print_cp_error($forums->lang['only_letter_num']);
			}
			$rule = $DB->query_first('SELECT ruleid
				FROM ' . TABLE_PREFIX . "creditrule
				WHERE rule_tag = '". $_INPUT['rule_tag'] . "'");
			if ($rule['ruleid'] > 0)
			{
				$forums->admin->print_cp_error($forums->lang['key_already_used']);
			}
		}
		
		$sql_array = array(
			'rule_name' => $_INPUT['rule_name'],
			'description' => convert_andstr(trim($_INPUT['description'])),
			'grouptype' => trim($_INPUT['grouptype']),
			'texttype' => $texttype=trim($_INPUT['texttype'])?trim($_INPUT['texttype']):'fixvalue',
			'action_tag' => $_INPUT['action_tag'],
			'isdefault' => 2,
		);
		if ($rule['ruleid'])
		{
			$DB->update(TABLE_PREFIX . 'creditrule', $sql_array, 'ruleid = ' . $rule['ruleid']);
			$type = 'edited';
		}
		else
		{
			$sql_array['rule_tag'] = $_INPUT['rule_tag'];
			$DB->insert(TABLE_PREFIX . 'creditrule', $sql_array);
			$id = $DB->insert_id();
			$type = 'added';
		}
		$forums->func->recache('creditrule');
		$forums->admin->redirect("creditrule.php", $forums->lang['creditrule_' . $type], $forums->lang['creditrule_' . $type]);
	}
	
	function delete_creditrule()
	{
		global $forums, $DB, $_INPUT;

		$forums->admin->nav[] = array('creditrule.php' , $forums->lang['creditlist']);

		$id = intval($_INPUT['id']);
		if (!$id)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$rule = $DB->query_first('SELECT *
			FROM ' . TABLE_PREFIX . "creditrule
			WHERE ruleid = $id");
		if (!$rule['ruleid'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($_INPUT['update'])
		{
			$DB->delete(TABLE_PREFIX . 'creditrule', 'ruleid = ' . $rule['ruleid']);
			$forums->func->recache('creditrule');
			$forums->admin->redirect('creditrule.php', $forums->lang['credit_deleted'], $forums->lang['credit_deleted']);
		}
		else
		{
			$pagetitle = $forums->lang['creditrule_confirm_deleted'];
			$detail = $forums->lang['creditrule_confirm_deleted'];

			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do' , 'delete'), 2 => array('id', $rule['ruleid']), 3 => array('update', 1)));
			$forums->admin->print_table_start($pagetitle);

			$forums->admin->print_cells_single_row($forums->lang['confirm_deleted_rule_desc'], "center");

			$forums->admin->print_form_submit($forums->lang['confirm_deleted']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}
}

$output = new creditrule();
$output->show();
?>