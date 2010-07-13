<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
# **************************************************************************#
require ('./global.php');

class userregfield
{
	function show()
	{
		global $forums, $DB, $bbuserinfo;
		$forums->func->load_lang('admin_userfield');
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch (input::get('do', ''))
		{
			case 'addfield':
				$this->field_form('add');
			break;
			case 'editfield':
				$this->field_form('edit');
			break;
			case 'doedit':
				$this->doedit();
			break;
			case 'delete':
				$this->deletefield();
			break;
			default:
				$this->filedlist();
			break;
		}
	}

	function filedlist()
	{
		global $forums, $DB;

		$pp = input::get('pp', '')?input::int('pp'):0;

		$pagetitle = $forums->lang['manageuser'];
		$forums->admin->nav[] = array('userfield.php' , $forums->lang['userextrafieldlist']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(1 => array('do' , 'addfield')));

		$row = $DB->query_first('SELECT count(*) as total FROM ' . TABLE_PREFIX . 'userextrafield');
		$row_count = $row['total'];
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
			'perpage' => 10,
			'curpage' => $pp,
			'pagelink' => "userfield.php?" . $forums->sessionurl,
			)
		);
		$forums->admin->print_cells_single_row($links, 'right', 'pformstrip');

		$forums->admin->columns[] = array($forums->lang['column_title'], "20%",);
		$forums->admin->columns[] = array($forums->lang['column_name'], "10%");
		$forums->admin->columns[] = array($forums->lang['tablename'], "10%");
		$forums->admin->columns[] = array($forums->lang['type'], "10%");
		$forums->admin->columns[] = array($forums->lang['control'], "10%");

		$forums->admin->print_table_start($forums->lang['userextrafieldlist']);
		$result = $DB->query('SELECT * FROM ' . TABLE_PREFIX . 'userextrafield');
        if ($DB->num_rows($result))
		{
			while ($field = $DB->fetch_array($result))
			{
                $action = $field["type"] ? $forums->lang['cannotmake']:"<a href='userfield.php?{$forums->sessionurl}do=editfield&amp;fieldid={$field['fieldid']}'>{$forums->lang['edit']}</a> |
                	<a href='userfield.php?{$forums->sessionurl}do=delete&amp;fieldid={$field['fieldid']}&amp;fieldtag={$field['fieldtag']}&amp;tablename={$field['tablename']}' onclick=\"if (!confirm('".$forums->lang['confirmdelregfield']."')) {return false;}\">{$forums->lang['delete']}</a>";

                $forums->admin->print_cells_row(array(
                	"<center>" . $field['fieldname'] . "</center>",
					"<center>" . $field['fieldtag'] . "</center>",
					"<center>" . $field['tablename'] . "</center>",
					"<center>" . $type = $field["type"] ? $forums->lang["type_1"] : $forums->lang["type_0"] . "</center>",
					"<center>" . $action . "</center>",
				));
			 }
		 }
		else
		{
			$forums->admin->print_cells_single_row("<strong>".$forums->lang['nouserregfield']."</strong>", 'center');
		}
		$forums->admin->print_form_submit($forums->lang['adduserextrafield']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function field_form($type='add')
	{
		global $forums, $DB;

		$langtitle = $forums->lang['adduserextrafield'];
		if ($type=='edit')
		{
			$fieldid = input::int('fieldid');
			$field = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "userextrafield WHERE fieldid = $fieldid");
			if (!$field['fieldid'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$langtitle = $forums->lang['edituserregfield'];
			$listcontent = '';
			if (!empty($field['listcontent']))
			{
				$list_array = unserialize($field['listcontent']);
				foreach ($list_array as $k => $v)
				{
					if (is_array($v))
					{
						$sign = '';
						if (!empty($v[0]) && !empty($v[1]))
						{
							$sign = '=';
						}
						$list_array[$k] = implode($sign, $v);
					}
				}
				$listcontent = implode("\n", $list_array);
			}
		}
		else
		{
			$field['length'] = 100;
		}
		$showtypes = array('text', 'textarea', 'select');
		$tablelist = array('userexpand', 'user');
		$datatype = array('VARCHAR', 'INT', 'TEXT', 'CHAR');
      	$forums->admin->nav[] = array('userfield.php', $forums->lang['userextrafieldlist']);
		$forums->admin->print_cp_header($langtitle);
		$forums->admin->print_form_header(array(1 => array('do', 'doedit'),
												2 => array('fieldid', $fieldid),
												3 => array('edittblname', $field['tablename']),
	  											4 => array('edittag', $field['fieldtag'])
	  									 ));
		$forums->admin->columns[] = array('&nbsp;', "20%",);
		$forums->admin->columns[] = array('&nbsp;', "50%",);

		$forums->admin->print_table_start($langtitle);

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['column_title']."</strong>", $forums->admin->print_input_row("fieldname", input::get('fieldname', '') ? input::get('fieldname', '') : $field['fieldname']), $forums->lang['text_des']));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['column_name']."</strong>", $field['fieldtag']?$field['fieldtag']:$forums->admin->print_input_row("fieldtag", input::get('fieldtag', '') ? input::get('fieldtag', '') : ''), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['column_desc']."</strong>", $forums->admin->print_textarea_row("fielddesc", input::get('fielddesc', '') ? input::get('fielddesc', '') : $field['fielddesc']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['is_only']."</strong>", $forums->admin->print_yes_no_row("isonly", input::get('isonly', '') ? input::get('isonly', '') : $field['isonly']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['regular']."</strong>", $forums->admin->print_input_row("checkregular", input::get('checkregular', '') ? input::get('checkregular', '') : $field['checkregular']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['mustfillin']."</strong>", $forums->admin->print_yes_no_row("ismustfill", input::get('ismustfill', '') ? input::get('ismustfill', '') : $field['ismustfill']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang["select_type"]."</strong>", $forums->admin->print_input_select_row("showtype", $showtypes, input::get('showtype', '') ? input::get('showtype', '') : $field['showtype']), ''));

        $forums->admin->print_cells_row(array("<strong>".$forums->lang['rows_cols_des']."</strong>", $forums->admin->print_textarea_row("listcontent", input::get('listcontent', '') ? input::get('listcontent', '') : $listcontent), $forums->lang['column_list_content_des']));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang["datatype"]."</strong>", $forums->admin->print_input_select_row("datatype", $datatype, input::get('datatype', '') ? input::get('datatype', '') : $field['datatype']) . $forums->admin->print_input_row("length", input::get('length', '') ? input::get('length', '') : $field['length'], '', '', 10), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['maxlength']."</strong>", $forums->admin->print_input_row("minlength", input::get('minlength', '') ? input::get('minlength', '') : $field['minlength'], '', '', 10) . ' / ' . $forums->admin->print_input_row("maxlength", input::get('maxlength', '') ? input::get('maxlength', '') : $field['maxlength'], '', '', 10), $forums->lang['column_length_des']));

		$extra = $type=='edit'?'disabled="disabled"':'';
		$forums->admin->print_cells_row(array("<strong>".$forums->lang["tablename"]."</strong>", $forums->admin->print_input_select_row("tablename", $tablelist, input::get('tablename', '') ? input::get('tablename', '') : $field['tablename'], $extra), ''));

		$forums->admin->print_form_submit($langtitle);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doedit()
	{
		global $forums, $DB;

		$fieldid = input::int('fieldid');
		$fieldtag = input::get('fieldtag', '') ? trim(input::str('fieldtag')) : trim(input::str('edittag'));
		$tablename = input::get('tablename', '') ? trim(input::str('tablename')) : trim(input::str('edittblname'));
		if(!$fieldtag)
		{
			$forums->admin->print_cp_error($forums->lang['error_dataname']);
		}
		if(!$tablename)
		{
			$forums->admin->print_cp_error($forums->lang['error_tablename']);
		}
		if(!trim(input::str('fieldname')))
		{
			$forums->admin->print_cp_error($forums->lang['error_title']);
		}
        //需要填写长度的数据类型
		if(!input::int('length') && in_array(input::get('datatype', ''), array("VARCHAR", "CHAR")))
		{
			$forums->admin->print_cp_error($forums->lang['error_datalength']);
		}
        //非需要长度字段去掉其长度，输入长度则只为验证该项的输入长短
		if(!in_array(input::get('datatype', ''), array("VARCHAR", "CHAR")))
		{
             input::set('length', '');
		}

		//判断用户自定义字段的唯一标签是否存在于用户或用户扩展表中
		if (!input::get('fieldid', ''))
		{
			$result = $DB->query('SHOW FIELDS FROM ' . TABLE_PREFIX . 'user');
			while ($r = $DB->fetch_array($result))
			{
				if ($r['Field'] == $fieldtag)
				{
					$forums->admin->print_cp_error($forums->lang['error_exists_column']);
				}
			}
			$result = $DB->query('SHOW FIELDS FROM ' . TABLE_PREFIX . 'userexpand');
			while ($r = $DB->fetch_array($result))
			{
				if ($r['Field'] == $fieldtag)
				{
					$forums->admin->print_cp_error($forums->lang['error_exists_column']);
				}
			}
			$type = 'add';
			$msg = $forums->lang['addregfieldsuccess'];
		}
		else
		{
			$type = 'change';
			$msg = $forums->lang['editregfieldsuccess'];
		}
		$this->processdata($type);
		$forums->func->recache('userextrafield');
		$forums->admin->redirect('userfield.php', $forums->lang['userextrafieldlist'], $msg);
	}

	function deletefield()
	{
		global $forums, $DB;
		if (!input::get('fieldid', ''))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$this->processdata('drop');
		$forums->func->recache('userextrafield');
		$forums->admin->redirect('userfield.php', $forums->lang['userextrafieldlist'], $forums->lang['delregfieldsuccess']);
	}

	function processdata($type = 'add')
	{
		global $forums, $DB;
		$tablename = input::get('tablename', '') ? trim(input::str('tablename')) : trim(input::str('edittblname'));
		$fieldname = trim(input::str('fieldname'));
		$fieldtag = input::get('fieldtag', '') ? trim(input::str('fieldtag')) : trim(input::str('edittag'));
		$datanull = input::int('ismustfill') ? 'NOT NULL DEFAULT \'\'' : 'NULL';
		$datatype = trim(input::str('datatype'));
		$maxlength = input::int('maxlength');
		$minlength = input::int('minlength');
		$length = input::int('length');
		$fielddesc = trim(input::str('fielddesc'));

		$listcontent = explode('<br />', trim(input::get('listcontent', '')));
		$list_array = array();
		foreach ($listcontent as $v)
		{
			$array = explode('=', $v);
			$k = array_shift($array);
			$v = empty($array) ? $k : implode('=', $array);

			$list_array[] = array($k, $v);
		}
		$listcontent = serialize($list_array);


		if (!$tablename || !$fieldtag)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		switch($type)
		{
			case 'drop':
				$DB->query_unbuffered("ALTER TABLE `" . TABLE_PREFIX . $tablename . "` DROP `". $fieldtag . "`");
				$DB->delete(TABLE_PREFIX . "userextrafield", "fieldid=" . input::int('fieldid'));
		    break;

			case 'change':
				if ($datanull !== 'NULL')
				{
					$DB->update(TABLE_PREFIX . $tablename, array(
						$fieldtag => ''
					), $fieldtag . ' IS NULL');
				}
				$DB->query_unbuffered("ALTER TABLE `" . TABLE_PREFIX . $tablename ."` CHANGE `" . $fieldtag . "` `" . $fieldtag . "` ". $datatype . ' ( ' . $length . ' ) ' . $datanull);
				$data = array(
					'fieldname' => $fieldname,
					'fieldtag' => $fieldtag,
					'fielddesc' => $fielddesc,
					'showtype' =>  trim(input::str('showtype')),
					'ismustfill' =>  input::int('ismustfill'),
					'length' => $length,
					'maxlength' => $maxlength,
					'minlength' => $minlength,
					'tablename' =>  $tablename,
					'datatype' =>  $datatype,
					'checkregular' =>  trim(input::str('checkregular')),
					'listcontent' =>  $listcontent,
					'isonly' => input::int('isonly'),
				);
				$DB->update(TABLE_PREFIX . "userextrafield" , $data, "fieldid=" . input::int('fieldid'));
			break;

			default:
				$data = array(
					'fieldname' => $fieldname,
					'fieldtag' => $fieldtag,
					'fielddesc' => $fielddesc,
					'showtype' =>  trim(input::str('showtype')),
					'ismustfill' =>  input::int('ismustfill'),
					'length' => $length,
					'maxlength' => $maxlength,
					'minlength' => $minlength,
					'tablename' =>  $tablename,
					'datatype' =>  $datatype,
					'checkregular' =>  trim(input::str('checkregular')),
					'listcontent' =>  $listcontent,
					'isonly' => input::int('isonly'),
				);
				$DB->insert(TABLE_PREFIX . "userextrafield" , $data);
			    $DB->query("ALTER TABLE `" . TABLE_PREFIX . $tablename ."` ADD `" . $fieldtag . "` ". $datatype . ' ( ' . $length . ' ) ' . $datanull);
		}
	}
}

$output = new userregfield();
$output->show();
?>