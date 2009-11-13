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
# $Id: settings.php 196 2007-09-27 08:47:13Z develop_tong $
# **************************************************************************#
require ('./global.php');

class settings
{
	var $key_array = array();

	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditsettings'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch ($_INPUT['do'])
		{
			case 'deletegroup':
				$this->deletegroup();
				break;
			case 'settinggroup_new':
				$this->settinggroup_form('add');
				break;
			case 'settinggroup_showedit':
				$this->settinggroup_form('edit');
				break;
			case 'settinggroup_add':
				$this->savegroup('add');
				break;
			case 'settinggroup_edit':
				$this->savegroup('edit');
				break;
			case 'settingnew':
				$this->setting_form('add');
				break;
			case 'setting_showedit':
				$this->setting_form('edit');
				break;
			case 'setting_add':
				$this->setting_save('add');
				break;
			case 'setting_edit':
				$this->setting_save('edit');
				break;
			case 'setting_view':
				$this->setting_view();
				break;
			case 'setting_delete':
				$this->setting_delete();
				break;
			case 'setting_revert':
				$this->setting_revert();
				break;
			case 'setting_update':
				$this->setting_update();
				break;
			case 'check_perms':
				$this->check_perms();
				break;
			case 'phpinfo':
				phpinfo();
				exit;
			default:
				$this->setting_start();
				break;
		}
	}

	function check_perms()
	{
		global $forums, $DB, $bboptions;
		$pagetitle = $forums->lang['check_perms'];
		$detail = $forums->lang['check_perms_desc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array($forums->lang['file_dir_perms'] , "40%");
		$forums->admin->columns[] = array($forums->lang['file_dir_status'] , "60%");

		$forums->admin->print_table_start($forums->lang['check_perms']);

		$dirs = array(
			ROOT_PATH . "data",
			ROOT_PATH . "data/dbbackup",
			ROOT_PATH . "cache",
			ROOT_PATH . "cache/cache",
			ROOT_PATH . "cache/templates",
			$bboptions['uploadfolder'],
			$bboptions['uploadfolder'] . '/user',
			ROOT_PATH . "images/smiles",
		);
		foreach ($dirs AS $dir)
		{

			$status = $this->WriteDirAccess($dir) ? $forums->lang['can_write'] : "<span style='background:yellow;font-weight:bold;color:#000000'>" . $forums->lang['can_not_write'] . "</span>";
			$forums->admin->print_cells_row(array("<strong>{$dir}</strong>",
					"<center>" . $status . "</center>",
					));
		}

		$files = array(
			ROOT_PATH . "includes/config.php",
			);
		foreach ($files AS $f)
		{
			$status = is_writable($f) ? $forums->lang['can_write'] : "<span style='background:yellow;font-weight:bold;color:#000000'>" . $forums->lang['can_not_write'] . "</span>";
			$forums->admin->print_cells_row(array("<strong>{$f}</strong>",
					"<center>" . $status . "</center>",
					));
		}

		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function WriteDirAccess($dir)
	{
		if (is_dir($dir))
		{
			if ($fp = @fopen($dir . '/test.php', 'wb'))
			{
				@fclose($fp);
				@unlink($dir . '/test.php');
				return true;
			}
		}
		return false;
	}

	function setting_start()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managesettings'];
		$detail = $forums->lang['managesettingsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("" , "100%");
		$forums->admin->columns[] = array("" , "");
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>\n";
		echo "<div style='float:right'><form method='post' action='settings.php?{$forums->sessionurl}do=setting_view'><input type='text' size='25' onclick='this.value=\"\"' value='" . $forums->lang['search'] . "...' name='search' class='button' />&nbsp;<input type='submit' class='button' value='" . $forums->lang['ok'] . "' /></form>&nbsp;</div>\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $forums->lang['systemsetting'] . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		$forums->admin->print_table_start();
		$this->setting_get_groups();
		foreach($this->setting_groups AS $i => $r)
		{
			$forums->admin->print_cells_row(array("<a href='settings.php?{$forums->sessionurl}do=setting_view&amp;groupid={$r['groupid']}'><strong>{$r['title']}</strong></a>&nbsp;<span style='font-size:9px;'>(" . intval($r['groupcount']) . ")</span><div class='description'>{$r['description']}</div>" ,
					"<div align='center' style='white-space:nowrap'>
																   <input type='button' class='button' value='" . $forums->lang['edit'] . "' onclick='self.location.href=\"settings.php?{$forums->sessionurl}do=settinggroup_showedit&amp;id={$r['groupid']}\"' title='" . $forums->lang['editsettinggroupdesc'] . "' />
																  <input type='button' class='button' value='" . $forums->lang['delete'] . "' onclick='self.location.href=\"settings.php?{$forums->sessionurl}do=deletegroup&amp;id={$r['groupid']}\"' title='" . $forums->lang['deletesettinggroup'] . "' />
																          </div>"
					));
		}
		$forums->admin->print_cells_row(array(array("<div align='center' style='white-space:nowrap'>" . $forums->admin->print_button($forums->lang['addsettinggroup'], "settings.php?{$forums->sessionurl}do=settinggroup_new") . "</div>", 3, 'pformstrip')
				));
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function deletegroup()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'])
		{
			$conf = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "setting WHERE groupid=" . $_INPUT['id'] . "");
			$count = intval($conf['count']);
			if ($count > 0)
			{
				$forums->main_msg = $forums->lang['cannotdeletegroup'];
			}
			else
			{
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "settinggroup WHERE groupid=" . $_INPUT['id'] . "");
				$forums->main_msg = $forums->lang['settinggroupdeleted'];
			}
		}
		$this->setting_start();
	}

	function group_recount($gid)
	{
		global $forums, $DB, $_INPUT;
		if ($gid)
		{
			$conf = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "setting WHERE groupid=" . $gid . "");
			$count = intval($conf['count']);
			$DB->update(TABLE_PREFIX . 'settinggroup', array('groupcount' => $count), 'groupid=' . $gid);
		}
	}

	function settinggroup_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'add')
		{
			$formcode = 'settinggroup_add';
			$pagetitle = $forums->lang['addnewsettinggroup'];
			$button = $forums->lang['addsettinggroup'];
		}
		else
		{
			$conf = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "settinggroup WHERE groupid=" . $_INPUT['id'] . "");
			if (! $conf['groupid'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->setting_start();
			}
			$forums->lang['editsettinggroup'] = sprintf($forums->lang['editsettinggroup'], $conf['title']);
			$formcode = 'settinggroup_edit';
			$pagetitle = $forums->lang['editsettinggroup'];
			$button = $forums->lang['savechange'];
		}
		$detail = $forums->lang['managesettingsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', $formcode), 2 => array('id', $_INPUT['id'])));
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['grouptitle'] . ":</strong>", $forums->admin->print_input_row('title', $_INPUT['title'] ? $_INPUT['title'] : $conf['title'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['groupdesc'] . ":</strong>", $forums->admin->print_textarea_row('description', $_POST['description'] ? $_POST['description'] : $conf['description'])));
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function savegroup($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'edit')
		{
			if (! $_INPUT['id'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->setting_form();
			}
		}
		if (trim($_INPUT['title']) == '')
		{
			$forums->main_msg = $forums->lang['inputsettingname'];
			$this->setting_form();
		}
		$array = array('title' => $_INPUT['title'], 'description' => convert_andstr($_POST['description']));
		if ($type == 'add')
		{
			$DB->insert(TABLE_PREFIX . 'settinggroup', $array);
			$forums->main_msg = $forums->lang['settinggroupadded'];
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'settinggroup', $array, 'groupid=' . $_INPUT['id']);
			$forums->main_msg = $forums->lang['settinggroupedited'];
		}
		$forums->func->recache('settings');
		$this->setting_start();
	}

	function setting_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'add')
		{
			$formcode = 'setting_add';
			$pagetitle = $forums->lang['addnewsetting'];
			$button = $forums->lang['addsetting'];
			$conf = array('groupid' => $_INPUT['groupid'], 'addcache' => 1);
			if ($_INPUT['groupid'])
			{
				$max = $DB->query_first("SELECT max(displayorder) as max FROM " . TABLE_PREFIX . "setting WHERE groupid=" . $_INPUT['groupid'] . "");
			}
			else
			{
				$max = $DB->query_first("SELECT max(displayorder) as max FROM " . TABLE_PREFIX . "setting");
			}
			$conf['displayorder'] = $max['max'] + 1;
		}
		else
		{
			$conf = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "setting WHERE settingid=" . $_INPUT['id'] . "");
			if (! $conf['settingid'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->setting_start();
			}
			$formcode = 'setting_edit';
			$pagetitle = $forums->lang['editsetting'] . " " . $conf['title'];
			$button = $forums->lang['savechange'];
		}
		$detail = $forums->lang['managesettingsdesc'];
		$this->setting_get_groups();
		$groups = array();
		foreach($this->setting_groups AS $i => $r)
		{
			$groups[] = array($r['groupid'], $r['title']);
		}
		$types = array(0 => array('input', $forums->lang['inputitems']),
			1 => array('dropdown', $forums->lang['dropdownitems']),
			2 => array('yes_no', $forums->lang['yesnoitems']),
			3 => array('textarea', $forums->lang['textareaitems']),
			4 => array('multi', $forums->lang['multiitems']),
			);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , $formcode), 2 => array('id' , $_INPUT['id'])));
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingtitle'] . "</strong>", $forums->admin->print_input_row('title', $_INPUT['title'] ? $_INPUT['title'] : $conf['title'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingorder'] . "</strong>", $forums->admin->print_input_row('displayorder', $_INPUT['displayorder'] ? $_INPUT['displayorder'] : $conf['displayorder'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingdesc'] . "</strong>", $forums->admin->print_textarea_row('description', $_INPUT['description'] ? $_INPUT['description'] : $conf['description'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingingroup'] . "</strong>", $forums->admin->print_input_select_row('groupid', $groups, $_INPUT['groupid'] ? $_INPUT['groupid'] : $conf['groupid'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingtype'] . "</strong>", $forums->admin->print_input_select_row('type', $types, $_INPUT['type'] ? $_INPUT['type'] : $conf['type'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingvar'] . "</strong>", $forums->admin->print_input_row('varname', $_INPUT['varname'] ? $_INPUT['varname'] : $conf['varname'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingvalue'] . "</strong>", $forums->admin->print_textarea_row('value', $_INPUT['value'] ? $_INPUT['value'] : $conf['value'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingdefault'] . "</strong>", $forums->admin->print_textarea_row('defaultvalue', $_INPUT['defaultvalue'] ? $_INPUT['defaultvalue'] : $conf['defaultvalue'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingextra'] . "</strong><div class='description'>" . $forums->lang['settingextradesc'] . "</div>", $forums->admin->print_textarea_row('dropextra', $_INPUT['dropextra'] ? $_INPUT['dropextra'] : $conf['dropextra'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['settingaddcache'] . "</strong>", $forums->admin->print_yes_no_row('addcache', $_INPUT['addcache'] ? $_INPUT['addcache'] : $conf['addcache'])));
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function setting_view()
	{
		global $forums, $DB, $_INPUT;
		$start = intval($_INPUT['pp']);
		$end = 50;
		$entry = array();
		if ($_INPUT['search'])
		{
			$keywords = strtolower($_INPUT['search']);
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE LOWER(title) LIKE '%" . $keywords . "%' OR LOWER(description) LIKE '%" . $keywords . "%' ORDER BY title LIMIT " . $start . ", " . $end . "");
			while ($r = $DB->fetch_array())
			{
				if ($r['settingid'] == 7 && $r['groupid'] == 1)
				{
					continue;
				}
				$entry[ $r['settingid'] ] = $r;
			}
			if (! count($entry))
			{
				$forums->lang['norecord'] = sprintf($forums->lang['norecord'], $keywords);
				$forums->admin->print_cp_error($forums->lang['norecord']);
			}
			$title = $forums->lang['keyword'] . ": " . $keywords;
		}
		else
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE groupid='" . $_INPUT['groupid'] . "' ORDER BY displayorder, title LIMIT " . $start . ", " . $end . "");
			while ($r = $DB->fetch_array())
			{
				if ($r['settingid'] == 7 && $r['groupid'] == 1)
				{
					continue;
				}
				$entry[ $r['settingid'] ] = $r;
			}
			$title = $forums->lang['settinggroup'] . ": {$this->setting_groups[$_INPUT['groupid']]['title']}";
		}
		$pagetitle = $forums->lang['managesettings'];
		$detail = $forums->lang['managesettingsdesc'] . "<br />" . $forums->lang['managesettingsdesc1'];
		$forums->admin->nav[] = array('settings.php', $forums->lang['managesettings']);
		$_INPUT['search'] = trim(rawurldecode($_INPUT['search']));
		if (! $_INPUT['groupid'] AND ! $_INPUT['search'])
		{
			$forums->main_msg = $forums->lang['noselectgroup'];
			$this->setting_start();
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$this->setting_get_groups();
		$forums->admin->print_form_header(array(1 => array('do', 'setting_update'),
				2 => array('id', $_INPUT['groupid']),
				3 => array('search', $_INPUT['search']),)
			);
		$pages = $forums->func->build_pagelinks(array('totalpages' => $this->setting_groups[$_INPUT['groupid']]['groupcount'],
				'perpage' => $end,
				'curpage' => $start,
				'pagelink' => "settings.php?" . $forums->sessionurl . "do=setting_view&amp;search={$_INPUT['search']}",)
			);
		if (! $_INPUT['search'])
		{
			$newbutton = $forums->admin->print_button($forums->lang['addsetting'] , "settings.php?{$forums->sessionurl}do=settingnew&amp;groupid=" . $_INPUT['groupid']);
		}
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>\n";
		echo "<div style='float:right'>$newbutton&nbsp;</div>\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $title . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		foreach($entry AS $id => $r)
		{
			$this->parse_entry($r);
		}
		echo "<input type='hidden' name='settings_save' value='" . implode(",", $this->key_array) . "' />\n";
		$forums->admin->print_form_end_standalone($forums->lang['updatesetting']);
		echo "<br /><br /><div align='right'><strong>" . $forums->lang['quickjump'] . "</strong>: " . $this->setting_make_dropdown() . "</div>\n";
		$forums->admin->print_cp_footer();
	}

	function parse_entry($r)
	{
		global $forums, $DB, $_INPUT;
		$form_element = '';
		$dropdown = array();
		$start = '';
		$end = '';
		$revert_button = '';
		$tdrow1 = "tdrow1";
		$tdrow2 = "tdrow2";
		$key = $r['varname'];
		$value = $r['value'] != '' ? $r['value'] : $r['defaultvalue'];
		$show = 1;
		$css = '';
		if ($r['value'] != "" AND ($r['value'] != $r['defaultvalue']))
		{
			$tdrow1 = "tdrow1shaded";
			$tdrow2 = "tdrow2shaded";
			$revert_button = "<div style='width:auto;float:right;padding-top:2px;'><a href='settings.php?{$forums->sessionurl}do=setting_revert&amp;id={$r['settingid']}&amp;groupid={$r['groupid']}&amp;search={$_INPUT['search']}' title='" . $forums->lang['restoredefault'] . "'><img src='{$forums->imageurl}/te_revert.gif' alt='' border='0' /></a></div>";
		}
		switch ($r['type'])
		{
			case 'input':
				$form_element = $forums->admin->print_input_row($key, str_replace("'", "&#39;", $value), 30);
				break;
			case 'textarea':
				$form_element = $forums->admin->print_textarea_row($key, $value, 45, 5);
				break;
			case 'yes_no':
				$form_element = $forums->admin->print_yes_no_row($key, $value);
				break;
			default:
				if ($r['dropextra'])
				{
					if ($r['dropextra'] == '#show_forums#')
					{
						$allforum = $forums->adminforum->forumcache;
						foreach($allforum AS $forum)
						{
							$dropdown[] = array($forum['id'], depth_mark($forum['depth'], '--') . $forum['name']);
						}
					}
					else if ($r['dropextra'] == '#show_groups#')
					{
						$DB->query("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup");
						while ($row = $DB->fetch_array())
						{
							$dropdown[] = array($row['usergroupid'], $forums->lang[ $row['grouptitle'] ]);
						}
					}
					else if ($r['dropextra'] == '#show_styles#')
					{
						$forums->admin->cache_styles();
						foreach($forums->admin->stylecache AS $style)
						{
							$dropdown[] = array($style['styleid'], depth_mark($style['depth'], '--') . $style['title']);
						}
					}
					else if ($r['dropextra'] == '#show_lang#')
					{
						$forums->func->check_cache('lang_list');
						require(ROOT_PATH . 'languages/list.php');
						foreach ($lang_list as $dir => $name)
						{
							$dropdown[] = array($dir, $name);
						}
					}
					else
					{
						foreach(explode("\n", $r['dropextra']) AS $l)
						{
							list ($k, $v) = explode("=", $l);
							if ($k != "" AND $v != "")
							{
								$dropdown[] = array(trim($k), trim($v));
							}
						}
					}
				}
				if ($r['varname'] == 'timezoneoffset')
				{
					require_once(ROOT_PATH . "includes/functions_user.php");
					$this->fu = new functions_user();
					foreach($this->fu->fetch_timezone() AS $off => $words)
					{
						$dropdown[] = array($off, $words);
					}
				}
				$form_element = ($r['type'] == 'dropdown') ? $forums->admin->print_input_select_row($key, $dropdown, $value) : $forums->admin->print_multiple_select_row($key . "[]", $dropdown, explode(",", $value), 5);
				break;
		}
		$delete = "&#0124; <a href='settings.php?{$forums->sessionurl}do=setting_delete&amp;id={$r['settingid']}&amp;gid={$r['groupid']}' title='key: \$bboptions[" . $r['varname'] . "]'>" . $forums->lang['delete'] . "</a>";
		$edit = "<a href='settings.php?{$forums->sessionurl}do=setting_showedit&amp;id={$r['settingid']}' title='id: {$r['settingid']}'>" . $forums->lang['edit'] . "</a>";
		if ($_INPUT['search'])
		{
			$r['title'] = preg_replace("/(" . $_INPUT['search'] . ")/i", "<span style='background:#878787'>\\1</span>", $r['title']);
			$r['description'] = preg_replace("/(" . $_INPUT['search'] . ")/i", "<span style='background:#878787'>\\1</span>", $r['description']);
		}
		echo "<table cellpadding='5' cellspacing='0' border='0' width='100%'>\n";
		echo "<tr>\n";
		echo "<td width='40%' class='$tdrow1' title='key: \$bboptions[" . $r['varname'] . "]'><strong>{$r['title']}</strong><div class='description'>{$r['description']}</div></td>\n";
		echo "<td width='45%' class='$tdrow2'>{$revert_button}<div align='left' style='width:auto;'>{$form_element}</div></td>\n";
		if ($edit OR $delete)
		{
			echo "<td width='10%' class='$tdrow1' align='center'>{$edit}{$delete}</td>\n";
		}
		if (! $_INPUT['search'])
		{
			echo "<td width='5%' class='$tdrow2' align='center'><input type='text' size='2' name='cp_{$r['settingid']}' value='{$r['displayorder']}' class='button' /></td>\n";
		}
		echo "</tr></table>\n";
		$this->key_array[] = preg_replace("/\[\]$/", "", $key);
	}

	function setting_update($donothing = "")
	{
		global $forums, $DB, $_INPUT;
		if (! $_INPUT['id'] AND ! $_INPUT['search'])
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->setting_start();
		}
		foreach ($_INPUT as $key => $value)
		{
			if (preg_match("/^cp_(\d+)$/", $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					$DB->update(TABLE_PREFIX . 'setting', array('displayorder' => $_INPUT[$match[0]]), 'settingid=' . $match[1]);
				}
			}
		}
		$fields = explode(",", trim($_INPUT['settings_save']));
		if (! count($fields))
		{
			$forums->main_msg = $forums->lang['noanysaveitems'];
			$forums->settings_view();
		}
		$db_fields = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname IN ('" . implode("','", $fields) . "')");
		while ($r = $DB->fetch_array())
		{
			$db_fields[ $r['varname'] ] = $r;
		}
		foreach($db_fields as $key => $data)
		{
			$data['value'] = str_replace("\\", "/", $data['value']);
			$data['defaultvalue'] = str_replace("\\", "/", $data['defaultvalue']);
			if (is_array($_POST[$key]))
			{
				$_POST[$key] = implode(",", $_POST[$key]);
			}
			if (($_POST[$key] != $data['defaultvalue']))
			{
				$value = str_replace("&#39;", "'", convert_andstr($_POST[$key]));
				$DB->update(TABLE_PREFIX . 'setting', array('value' => $value), 'settingid=' . $data['settingid']);
			}
			else if ($_INPUT[$key] != '' && ($_INPUT[$key] == $data['defaultvalue']) && $data['value'] != '')
			{
				$DB->update(TABLE_PREFIX . 'setting', array('value' => ''), 'settingid=' . $data['settingid']);
			}
			if ($key == 'threadviewsdelay' && $_POST[$key])
			{
				$DB->update(TABLE_PREFIX . 'cron', array('enabled' => 1), "filename='threadviews.php'");
			}
			else if ($key == 'threadviewsdelay' && $_POST[$key])
			{
				$DB->update(TABLE_PREFIX . 'cron', array('enabled' => 1), "filename='attachmentviews.php'");
			}
		}
		$_INPUT['groupid'] = $_INPUT['id'];
		$forums->func->recache('settings');
		if (! $donothing)
		{
			if ($_INPUT['id'])
			{
				$forums->admin->redirect("settings.php?{$forums->sessionurl}do=setting_view&amp;groupid={$_INPUT['id']}", $forums->lang['settingupdated'], $forums->lang['settingupdated']);
			}
			else
			{
				$forums->admin->redirect("settings.php?{$forums->sessionurl}", $forums->lang['settingupdated'], $forums->lang['settingupdated']);
			}
			$this->setting_view();
		}
	}

	function setting_save($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'edit')
		{
			if (! $_INPUT['id'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->setting_form();
			}
		}
		if (!$_INPUT['title'] OR !$_INPUT['groupid'] OR !$_INPUT['varname'])
		{
			$forums->main_msg = $forums->lang['inputallforms'];
			$this->setting_form();
		}
		$array = array('title' => $_INPUT['title'],
			'description' => convert_andstr($_POST['description']),
			'groupid' => intval($_INPUT['groupid']),
			'type' => $_INPUT['type'],
			'varname' => $_INPUT['varname'],
			'value' => convert_andstr($_POST['value']),
			'defaultvalue' => convert_andstr($_POST['defaultvalue']),
			'dropextra' => convert_andstr($_POST['dropextra']),
			'displayorder' => intval($_INPUT['displayorder']),
			'addcache' => intval($_INPUT['addcache']),
			);
		if ($type == 'add')
		{
			$DB->insert(TABLE_PREFIX .'setting', $array);
			$this->group_recount($_INPUT['groupid']);
			$forums->main_msg = $forums->lang['settingadded'];
		}
		else
		{
			$DB->update(TABLE_PREFIX .'setting', $array, 'settingid=' . $_INPUT['id']);
			$forums->main_msg = $forums->lang['settingedited'];
		}
		$forums->func->recache('settings');
		$this->setting_view();
	}

	function setting_revert()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		if (! $_INPUT['id'])
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->setting_form();
		}
		$conf = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "setting WHERE settingid=" . $_INPUT['id'] . "");
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "setting SET value='' WHERE settingid=" . $_INPUT['id'] . "");
		$forums->main_msg = $forums->lang['settingrestored'];
		$forums->func->recache('settings');
		$this->setting_view();
	}

	function setting_delete()
	{
		global $forums, $DB, $_INPUT;
		if (! $_INPUT['id'])
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->setting_form();
		}
		if ($_INPUT['update'])
		{
			$conf = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "setting WHERE settingid=" . $_INPUT['id'] . "");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "setting WHERE settingid=" . $_INPUT['id'] . "");
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "settinggroup SET groupcount=groupcount-1 WHERE groupid=" . $conf['groupid'] . "");
			$forums->main_msg = $forums->lang['settingdeleted'];
			$forums->func->recache('settings');
			$this->group_recount($conf['groupid']);
			$this->setting_start();
		}
		else
		{
			$pagetitle = $forums->lang['deletesetting'];
			$detail = $forums->lang['confirmdeletesetting'];
			$forums->admin->nav[] = array('', $forums->lang['deletesetting']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'setting_delete'), 2 => array('id', $_INPUT['id']), 3 => array('update', 1)));
			$forums->admin->print_table_start($forums->lang['confirmdeletesetting']);
			$forums->admin->print_cells_single_row($forums->lang['deletesettingdesc'], "center");
			$forums->admin->print_form_end($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_cp_footer();
		}
	}

	function setting_get_groups()
	{
		global $forums, $DB;
		$this->setting_groups = array();
		$settings = $DB->query("SELECT * FROM " . TABLE_PREFIX . "settinggroup ORDER BY groupid");
		while ($setting = $DB->fetch_array())
		{
			if (!$setting['title']) continue;
			$this->setting_groups[ $setting['groupid'] ] = $setting;
		}
	}

	function setting_make_dropdown()
	{
		global $forums, $DB, $_INPUT;
		if (! is_array($this->setting_groups))
		{
			$this->setting_get_groups();
		}
		$ret = "<form method='post' action='settings.php?{$forums->sessionurl}do=setting_view'>\n<select name='groupid' class='dropdown' onchange='submit()'>\n";
		foreach($this->setting_groups AS $id => $data)
		{
			$ret .= ($id == $_INPUT['groupid']) ? "<option value='{$id}' selected='selected'>{$data['title']}</option>\n" : "<option value='{$id}'>{$data['title']}</option>\n";
		}
		$ret .= "\n</select>\n<input type='submit' class='button' value='" . $forums->lang['ok'] . "' /></form>";
		return $ret;
	}
}

$output = new settings();
$output->show();

?>