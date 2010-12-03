<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
# **************************************************************************#
require ('./global.php');

class userregfield
{
	function show()
	{
		global $forums, $DB, $bbuserinfo;
		$forums->func->load_lang('admin_makereg');
		$forums->func->load_lang('admin_makeform');
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
		$forums->admin->nav[] = array('regfield.php' , '用户资料自定义列表');
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(1 => array('do' , 'addfield')));
		
		$row = $DB->queryFirst('SELECT count(*) as total FROM ' . TABLE_PREFIX . 'regextrafield');
		$row_count = $row['total'];
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
			'perpage' => 10,
			'curpage' => $pp,
			'pagelink' => "regfield.php?" . $forums->sessionurl,
			)
		);
		$forums->admin->print_cells_single_row($links, 'right', 'pformstrip');
		
		$forums->admin->columns[] = array($forums->lang['column_title'], "20%",);
		$forums->admin->columns[] = array($forums->lang['column_name'], "10%");
		$forums->admin->columns[] = array($forums->lang['tablename'], "10%");
		$forums->admin->columns[] = array($forums->lang['type'], "10%");
		$forums->admin->columns[] = array($forums->lang['control'], "10%");
		
		$forums->admin->print_table_start($forums->lang['userextrafieldlist']);
		$result = $DB->query('SELECT * FROM ' . TABLE_PREFIX . 'regextrafield');
        if ($DB->numRows($result))
		{
			while ($field = $DB->fetch($result))
			{
                $action = $field["type"]? $forums->lang['cannotmake']:"<a href='regfield.php?{$forums->sessionurl}do=editfield&amp;fieldid={$field['fieldid']}'>{$forums->lang['edit']}</a> | 
                	<a href='regfield.php?{$forums->sessionurl}do=delete&amp;fieldid={$field['fieldid']}&amp;fieldtag={$field['fieldtag']}&amp;tablename={$field['tablename']}' onclick=\"if (!confirm('".$forums->lang['confirmdelregfield']."')) {return false;}\">{$forums->lang['delete']}</a>";
                
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
		$forums->admin->print_form_submit($forums->lang['userextrafieldlist']);
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
			$field = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "regextrafield WHERE fieldid = $fieldid");
			if (!$field['fieldid'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$langtitle = $forums->lang['edituserregfield'];
			$listcontent = implode("<br />", unserialize($field["listcontent"]));
		}
		$showtypes = array('text', 'textarea', 'radio', 'checkbox', 'select', 'password');
		$tablelist = array('user' => 'user', 'userexpand' => 'userexpand');
		$datatype = array('VARCHAR' => 'VARCHAR',
			              'TINYINT' => 'TINYINT',
			              'TEXT' => 'TEXT',
			              'DATE' => 'DATE',
			              'SMALLINT' => 'SMALLINT',
			              'MEDIUMINT' => 'MEDIUMINT',
			              'INT' => 'INT',
			              'BIGINT' => 'BIGINT',
			              'FLOAT' => 'FLOAT',
			              'DOUBLE' => 'DOUBLE',
			              'DATETIME' => 'DATETIME',
			              'TIMESTAMP' => 'TIMESTAMP',
			              'TIME' => 'TIME',
			              'YEAR' => 'YEAR',
			              'CHAR' => 'CHAR',
			              'TINYBLOB' => 'TINYBLOB',
			              'TINYTEXT' => 'TINYTEXT',
			              'BLOB' => 'BLOB',
			              'LONGBLOB' => 'LONGBLOB',
			              'LONGTEXT' => 'LONGTEXT',
      	);
      	$forums->admin->nav[] = array('regfield.php', $forums->lang['userextrafieldlist']);
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
		
		$forums->admin->print_cells_row(array("<strong>".$forums->lang['is_only']."</strong>", $forums->admin->print_yes_no_row("isonly", input::get('isonly', '') ? input::get('isonly', '') : $field['isonly']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang["select_type"]."</strong>", $forums->admin->print_input_select_row("showtype", $showtypes, input::get('showtype', '') ? input::get('showtype', '') : $field['showtype']), ''));
		
		$forums->admin->print_cells_row(array("<strong>".$forums->lang['regular']."</strong>", $forums->admin->print_input_row("checkregular", input::get('checkregular', '') ? input::get('checkregular', '') : $field['checkregular']), ''));
        
		$forums->admin->print_cells_row(array("<strong>".$forums->lang['mustfillin']."</strong>", $forums->admin->print_yes_no_row("ismustfill", input::get('ismustfill', '') ? input::get('ismustfill', '') : $field['ismustfill']), ''));
        
        $forums->admin->print_cells_row(array("<strong>".$forums->lang['confirmation']."</strong>", $forums->admin->print_yes_no_row("isconfirm", input::get('isconfirm', '') ? input::get('isconfirm', '') : $field['isconfirm']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['column_value']."</strong>", $forums->admin->print_textarea_row("defaultvalue", input::get('defaultvalue', '') ? input::get('defaultvalue', '') : $field['defaultvalue']), $forums->lang['column_value_des']));

        $forums->admin->print_cells_row(array("<strong>".$forums->lang['rows_cols_des']."</strong>", $forums->admin->print_textarea_row("listcontent", input::get('listcontent', '') ? input::get('listcontent', '') : $listcontent), $forums->lang['column_list_content_des']));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['rows_cols']."</strong>", $forums->admin->print_input_row("rows", input::get('rows', '') ? input::get('rows', '') : $field['rows'], '', '', 10)." / ".$forums->admin->print_input_row("cols", input::get('cols', '') ? input::get('cols', '') : $field['cols'], '', '', 10), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang["datatype"]."</strong>", $forums->admin->print_input_select_row("datatype", $datatype, input::get('datatype', '') ? input::get('datatype', '') : $field['datatype']), ''));

		$forums->admin->print_cells_row(array("<strong>".$forums->lang['maxlength']."</strong>", $forums->admin->print_input_row("length", input::get('length', '') ? input::get('length', '') : $field['length'], '', '', 15), $forums->lang['column_length_des']));
		
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
		$fieldtag = input::get('fieldtag', '')?trim(input::str('fieldtag')):trim(input::str('edittag'));
		$tablename = input::get('tablename', '')?trim(input::str('tablename')):trim(input::str('edittblname'));
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
			while ($r = $DB->fetch($result))
			{
				if ($r['Field'] == $fieldtag)
				{
					$forums->admin->print_cp_error($forums->lang['error_exists_column']);
				}
			}
			$result = $DB->query('SHOW FIELDS FROM ' . TABLE_PREFIX . 'userexpand');
			while ($r = $DB->fetch($result))
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
		$forums->func->recache('regextrafield');
		$forums->admin->redirect('regfield.php', $forums->lang['userextrafieldlist'], $msg);
	}
	
	function deletefield()
	{
		global $forums, $DB;
		if (!input::get('fieldid', ''))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$this->processdata('drop');
		$forums->func->recache('regextrafield');
		$forums->admin->redirect('regfield.php', $forums->lang['userextrafieldlist'], $forums->lang['delregfieldsuccess']);
	}
	
	function processdata($type='add')
	{
		global $forums, $DB;
		$tablename = input::get('tablename', '')?trim(input::str('tablename')):trim(input::str('edittblname'));
		$fieldname = trim(input::str('fieldname'));
		$fieldtag = input::get('fieldtag', '')?trim(input::str('fieldtag')):trim(input::str('edittag'));
		$datanull = input::int('ismustfill')?'NOT NULL':'NULL';
		$datatype = trim(input::str('datatype'));
		$length = input::int('length');
		$listcontent = serialize(explode("<br />", trim(input::get('listcontent', ''))));
		if (!$tablename || !$fieldtag)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		switch($type)
		{
			case 'drop':
				$DB->query("ALTER TABLE `" . TABLE_PREFIX . $tablename . "` DROP `". $fieldtag . "`");
				$DB->delete(TABLE_PREFIX . "regextrafield", "fieldid=" . input::int('fieldid'));
		    break;

			case 'change':
				$DB->query("ALTER TABLE `" . TABLE_PREFIX . $tablename ."` CHANGE `" . $fieldtag . "` `" . $fieldtag . "` ". $datatype . ' ( ' . $length . ' ) ' . $datanull);
				$data = array('fieldname' => $fieldname,
					'fieldtag' => $fieldtag, 
					'showtype' =>  trim(input::str('showtype')), 
					'ismustfill' =>  input::int('ismustfill'), 
					'isconfirm' =>  input::int('isconfirm'), 
					'length' => $length,
					'rows' => input::int('rows'),
					'cols' =>  input::int('cols'),
					'tablename' =>  $tablename,
					'datatype' =>  $datatype,
					'checkregular' =>  trim(input::str('checkregular')), 
					'listcontent' =>  $listcontent, 
					'isonly' => input::int('isonly'),
					'defaultvalue' =>  trim(input::str('defaultvalue'))
				);
				$DB->update(TABLE_PREFIX . "regextrafield" , $data, "fieldid=" . input::int('fieldid'));
			break;
			
			default:
				$data = array('fieldname' => $fieldname, 
					'fieldtag' => $fieldtag, 
					'showtype' =>  trim(input::str('showtype')), 
					'ismustfill' =>  input::int('ismustfill'), 
					'isconfirm' =>  input::int('isconfirm'), 
					'length' => $length,
					'rows' => input::int('rows'),
					'cols' =>  input::int('cols'),
					'tablename' =>  $tablename,
					'datatype' =>  $datatype,
					'checkregular' =>  trim(input::str('checkregular')), 
					'listcontent' =>  $listcontent, 
					'isonly' => input::int('isonly'), 
					'defaultvalue' =>  trim(input::str('defaultvalue'))
				);
				$DB->insert(TABLE_PREFIX . "regextrafield" , $data);
			    $DB->query("ALTER TABLE `" . TABLE_PREFIX . $tablename ."` ADD `" . $fieldtag . "` ". $datatype . ' ( ' . $length . ' ) ' . $datanull);
		}
	}
}

$output = new userregfield();
$output->show();
?>