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

class creditrule
{
	function show()
	{
		global $forums, $DB, $bbuserinfo;
		$forums->func->load_lang('admin_credit');
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}

		switch (input::get('do', ''))
		{
			case 'add':
				$this->creditrule_form('add');
			break;
			case 'changetype':
				$this->creditrule_form(input::str('grouptype'));
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
		global $forums, $DB;

		$pp = input::int('pp');
		$pagetitle = $forums->lang['managecredit'];
		$detail = $forums->lang['managecreditruledesc'];
		$forums->admin->nav[] = array('creditrule.php' , $forums->lang['creditrulelist']);

		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add')));

		$row = $DB->queryFirst('SELECT count(ruleid) as total FROM ' . TABLE_PREFIX . 'creditrule');
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
		if ($DB->numRows($result))
		{
			while ($rule = $DB->fetch($result))
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
		global $forums, $DB;
		if ($type == "edit")
		{
			$id = input::int('id');
			if (!$id)
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$rule = $DB->queryFirst("SELECT *
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
			3 => array('grouptype', $rule['grouptype']?$rule['grouptype']:input::get('grouptype', '')),
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
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_group']}</strong>", $forums->admin->print_input_select_row('grouptype', $rulegroup, input::get('grouptype', $rule['grouptype']), $rulejs)));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_name']}</strong>", $forums->admin->print_input_row('rule_name', input::get('rule_name', $rule['rule_name']))));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_tag_name']}</strong><div class='description'>{$forums->lang['credit_tag_name_desc']}</div>", $rule['rule_tag'] ? $rule['rule_tag'] : $forums->admin->print_input_row('rule_tag', input::get('rule_tag', ''))));

		if ($type == 'revise' || $rule['grouptype'] == 'revise')
		{
			$normalrule = $existrule = array();
			$DB->query("SELECT action_tag FROM " . TABLE_PREFIX . "creditrule WHERE grouptype = 'revise'");
			while($row = $DB->fetch())
			{
				$existrule[] = $row['action_tag'];
			}
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "creditrule
			  WHERE texttype = 'fixvalue' and grouptype != 'revise' and grouptype != 'global'");
			while($row = $DB->fetch())
			{
				if (!in_array($row['rule_tag'], $existrule)) $normalrule[] = array($row['rule_tag'],$row['rule_name']);
			}
			$forums->admin->print_cells_row(array("<strong>{$forums->lang['selreviseactevent']}</strong><div class='description'>{$forums->lang['selreviseactevent_desc']}</div>", $forums->admin->print_input_select_row('action_tag', $normalrule, input::get('action_tag', $rule['action_tag']))));
		}
		else
		{
			$ruletext = array(0=>array('fixvalue',$forums->lang['credit_fixvalue']),
		    	1=>array('rangevalue',$forums->lang['credit_rangevalue']),
			);
			$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_text']}</strong>", $forums->admin->print_input_select_row('texttype', $ruletext, input::get('texttype', $rule['texttype']))));

			$forums->admin->print_cells_row(array("<strong>{$forums->lang['credit_rule_desction']}</strong>", $forums->admin->print_textarea_row('description', $rule['description'])));
		}
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doedit($redirect = true)
	{
		global $forums, $DB;
		$id = input::int('id');
		input::set('rule_name', trim(input::str('rule_name')));
		input::set('rule_tag', strtolower(trim(input::str('rule_tag'))));
		if (!input::get('rule_name', ''))
		{
			$forums->admin->print_cp_error($forums->lang['require_creditrule_name']);
		}
		if ($id)
		{
			$rule = $DB->queryFirst('SELECT *
				FROM ' . TABLE_PREFIX . "creditrule
				WHERE ruleid = $id");
			if (!$rule['ruleid'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
		}
		else
		{
			if (!preg_match('#^(\w+)$#i', input::get('rule_tag', '')))
			{
				$forums->admin->print_cp_error($forums->lang['only_letter_num']);
			}
			$rule = $DB->queryFirst('SELECT ruleid
				FROM ' . TABLE_PREFIX . "creditrule
				WHERE rule_tag = '". input::get('rule_tag', '') . "'");
			if ($rule['ruleid'] > 0)
			{
				$forums->admin->print_cp_error($forums->lang['key_already_used']);
			}
		}

		$sql_array = array(
			'rule_name' => input::get('rule_name', ''),
			'description' => convert_andstr(trim(input::str('description'))),
			'grouptype' => trim(input::str('grouptype')),
			'texttype' => $texttype=trim(input::str('texttype'))?trim(input::str('texttype')):'fixvalue',
			'action_tag' => input::get('action_tag', ''),
			'isdefault' => 2,
		);
		if ($rule['ruleid'])
		{
			$DB->update(TABLE_PREFIX . 'creditrule', $sql_array, 'ruleid = ' . $rule['ruleid']);
			$type = 'edited';
		}
		else
		{
			$sql_array['rule_tag'] = input::get('rule_tag', '');
			$DB->insert(TABLE_PREFIX . 'creditrule', $sql_array);
			$id = $DB->insertId();
			$type = 'added';
		}
		cache::update('creditrule');
		$forums->admin->redirect("creditrule.php", $forums->lang['creditrule_' . $type], $forums->lang['creditrule_' . $type]);
	}

	function delete_creditrule()
	{
		global $forums, $DB;

		$forums->admin->nav[] = array('creditrule.php' , $forums->lang['creditlist']);

		$id = input::int('id');
		if (!$id)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$rule = $DB->queryFirst('SELECT *
			FROM ' . TABLE_PREFIX . "creditrule
			WHERE ruleid = $id");
		if (!$rule['ruleid'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (input::str('update'))
		{
			$DB->delete(TABLE_PREFIX . 'creditrule', 'ruleid = ' . $rule['ruleid']);
			cache::update('creditrule');
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