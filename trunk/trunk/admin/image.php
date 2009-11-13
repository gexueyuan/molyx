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
# $Id: image.php 71 2007-09-11 01:46:24Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class image
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditimages'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch ($_INPUT['do'])
		{
			case 'icon':
				$this->icon_start();
				break;
			case 'doiconedit':
				$this->doiconedit();
				break;
			case 'dosmileedit':
				$this->dosmileedit();
				break;
			case 'doiconadd':
				$this->doiconadd();
				break;
			case 'deleteicon':
				$this->deleteicon();
				break;
			case 'smile':
				$this->smile_start();
				break;
			case 'smile_doadd':
				$this->smile_add();
				break;
			case 'removesmile':
				$this->removesmile();
				break;
			case 'deletesmile':
				$this->deletesmile();
				break;
			case 'uploadsmile':
				$this->uploadsmile();
			case 'uploadicon':
				$this->uploadicon();
				break;
			default:
				$this->smile_start();
				break;
		}
	}

	function dosmileedit()
	{
		global $forums, $DB, $_INPUT;
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^smile_type_(\d+)$/", $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					if ($_INPUT['smile_remove_' . $match[1] ])
					{
						$delids[] = $match[1];
					}
					$smiletext = $_INPUT[$match[0]];
					$displayorder = $_INPUT['smile_order_' . $match[1] ];
					$smiletext = str_replace('&#092;', "", $smiletext);
					if ($smiletext AND $match[1])
					{
						$DB->update(TABLE_PREFIX . 'smile', array(
							'displayorder' => intval($displayorder),
							'smiletext' => $smiletext
						), 'id=' . $match[1]);
					}
				}
			}
		}
		if (is_array($delids))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "smile WHERE id IN (" . implode(',', $delids) . ")");
		}
		$forums->func->recache('smile');
		$forums->main_msg = $forums->lang['smiliesupdated'];
		$this->smile_start();
	}

	function deletesmile()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['name'] == "")
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->smile_start();
		}
		if ($_INPUT['update'])
		{
			if (! @unlink(ROOT_PATH . 'images/smiles/' . $_INPUT['name']))
			{
				$forums->main_msg = $forums->lang['noids'];
			}
			else
			{
				$forums->main_msg = $forums->lang['smiliesdeleted'];
			}
			$this->smile_start();
		}
		else
		{
			$pagetitle = $forums->lang['deletesmilie'];
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'deletesmile'),
					2 => array('name', $_INPUT['name']),
					3 => array('update', 1)
					));
			$forums->admin->print_table_start($forums->lang['deletesmilie']);
			$forums->admin->print_cells_row(array($forums->lang['deletesmiliedesc']));
			$forums->admin->print_form_submit($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}

	function smile_add()
	{
		global $forums, $DB, $_INPUT;
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^smile_type_(\d+)$/", $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					$smiletext = $_INPUT[$match[0]];
					$add = $_INPUT['smile_add_' . $match[1] ];
					$image = $_INPUT['smile_image_' . $match[1] ];
					$smiletext = str_replace('&#092;', "", $smiletext);
					if ($_INPUT['addall'])
					{
						$add = 1;
					}
					if ($add AND $smiletext AND $image)
					{
						$DB->insert(TABLE_PREFIX . 'smile', array(
							'smiletext' => $smiletext,
							'image' => $image
						));
					}
				}
			}
		}
		$forums->func->recache('smile');
		$forums->main_msg = $forums->lang['smiliesuploaded'];
		$this->smile_start();
	}

	function uploadsmile()
	{
		global $forums, $DB, $_INPUT;
		$overwrite = 1;
		foreach(array(1, 2, 3, 4) AS $i)
		{
			$field = 'upload_' . $i;
			$filename = $_FILES[$field]['name'];
			$filesize = $_FILES[$field]['size'];
			$filetype = $_FILES[$field]['type'];
			$filetype = preg_replace("/^(.+?);.*$/", "\\1", $filetype);
			if ($_FILES[$field]['name'] == "" OR ! $_FILES[$field]['name'] OR ($_FILES[$field]['name'] == "none"))
			{
				continue;
			}
			if (! @move_uploaded_file($_FILES[ $field ]['tmp_name'], ROOT_PATH . 'images/smiles/' . $filename))
			{
				$forums->main_msg = $forums->lang['uploadfailed'];
				$this->smile_start();
			}
			else
			{
				@chmod(ROOT_PATH . 'images/smiles/' . $filename, 0777);
				if (is_array($directories) AND count($directories))
				{
					foreach ($directories AS $newdir)
					{
						if (file_exists(ROOT_PATH . 'images/smiles/' . $filename))
						{
							if ($overwrite != 1)
							{
								continue;
							}
						}
						if (@copy(ROOT_PATH . 'images/smiles/' . $filename, ROOT_PATH . 'images/smiles/' . $filename))
						{
							@chmod(ROOT_PATH . 'images/smiles/' . $filename, 0777);
						}
					}
				}
			}
		}
		$forums->main_msg = $forums->lang['smiliesuploaded'];
		$this->smile_start();
	}

	function smile_start()
	{
		global $forums, $DB, $_INPUT;
		if (! is_dir(ROOT_PATH . 'images/smiles'))
		{
			$forums->admin->print_cp_error($forums->lang['smiliefoldererror']);
		}
		$forums->admin->nav[] = array('image.php?do=smile', $forums->lang['managesmilies']);
		$pagetitle = $forums->lang['managesmilies'];
		$detail = $forums->lang['managesmiliesdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$smile_db = array();
		$smile_file = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "smile ORDER BY displayorder");
		while ($r = $DB->fetch_array())
		{
			$smile_db[ $r['image'] ] = $r;
		}
		$smile_file = array();
		$smile_rfile = $this->get_folder_contents('smiles');
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>$extra\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $forums->lang['currentusedsmilies'] . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<form action='image.php?{$forums->sessionurl}do=dosmileedit' method='post'>\n";
		echo "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
		$count = 0;
		$poss_names = array();
		if ($smile_rfile)
		{
			foreach($smile_rfile AS $ef)
			{
				$smile_file[ $ef ] = $ef;
			}
			echo "<tr align='center'>\n";
			$per_row = 4;
			$td_width = 100 / $per_row;
			foreach($smile_db AS $image => $data)
			{
				$count++;
				unset($smile_file[ $image ]);
				echo "<td width='{$td_width}%' align='center' class='tdrow1'>\n";
				echo "<fieldset>\n";
				echo "<legend><strong>{$image}</strong></legend>\n";
				echo "\n";
				echo "<img src='../images/smiles/{$image}' border='0' alt='' /><br />\n";
				echo $forums->lang['smiletext'] . ": " . $forums->admin->print_input_row('smile_type_' . $data['id'] . '', $data['smiletext'], '', '', 8) . "<br />\n";
				echo $forums->lang['smileorder'] . ": " . $forums->admin->print_input_row('smile_order_' . $data['id'] . '', $data['displayorder'], '', '', 5) . "<br /><input name='smile_remove_" . $data['id'] . "' type='checkbox' value='1' /><strong>" . $forums->lang['delete'] . "</strong>\n";
				echo "</fieldset>\n";
				echo "</td>\n";
				if ($count == $per_row)
				{
					echo "</tr>\n\n<tr align='center'>\n";
					$count = 0;
				}
				$poss_names[$data['smiletext']] = $data['smiletext'];
			}
			if ($count > 0 AND $count != $per_row)
			{
				for ($i = $count ; $i < $per_row ; ++$i)
				{
					echo "<td class='tdrow1'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			else if ($count == 0)
			{
				echo "<td></td></tr>\n";
			}
			echo "</table>\n";
			echo "<div class='pformstrip' align='center'><input type='submit' class='button' value='" . $forums->lang['updatesmilies'] . "' /></div></form><br />\n";
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanysmilies'], "center");
			echo "</table></form><br />\n";
		}
		if (count($smile_file))
		{
			echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
			echo "<tr><td class='tableborder'>$extra\n";
			echo "<div class='catfont'>\n";
			echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $forums->lang['nousedsmilies'] . "</div>\n";
			echo "</td></tr>\n";
			echo "</table>\n";
			echo "<form action='image.php?{$forums->sessionurl}do=smile_doadd' method='post'>\n";
			echo "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
			$count = 0;
			echo "<tr align='center'>\n";
			$master_count = 0;
			foreach($smile_file AS $image)
			{
				$count++;
				$master_count++;
				$poss_name = ':' . preg_replace("/(.*)(\..+?)$/", "\\1", $image) . ':';
				if ($poss_names[ $poss_name ])
				{
					$poss_name = preg_replace("/:$/", "2:", $poss_name);
				}
				echo "<td width='{$td_width}%' align='center' class='tdrow1'>\n";
				echo "<fieldset>\n";
				echo "<legend><strong>{$image}</strong></legend>\n";
				echo "<img src='../images/smiles/{$image}' border='0' alt='' />\n";
				echo "<br />\n";
				echo "<input type='textinput' class='button' size='10' name='smile_type_{$master_count}' value='$poss_name' />\n";
				echo "<br /><input name='smile_add_{$master_count}' type='checkbox' value='1' /><strong>" . $forums->lang['add'] . "</strong>&nbsp;|&nbsp;<a href='image.php?{$forums->sessionurl}do=deletesmile&amp;name={$image}' title='" . $forums->lang['completedeleteicons'] . "'><strong>" . $forums->lang['completedelete'] . "</strong></a>\n";
				echo "<input type='hidden' name='smile_image_{$master_count}' value='{$image}' />\n";
				echo "</fieldset>\n";
				echo "</td>\n";
				if ($count == $per_row)
				{
					echo "</tr>\n\n<tr align='center'>\n";
					$count = 0;
				}
			}
			if ($count > 0 AND $count != $per_row)
			{
				for ($i = $count ; $i < $per_row ; ++$i)
				{
					echo "<td class='tdrow1'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			echo "</table>\n";
			echo "<div class='pformstrip' align='center'><input type='submit' class='button' value='" . $forums->lang['addselectedsmilies'] . "' />&nbsp;&nbsp;<input type='submit' name='addall' class='button' value='" . $forums->lang['addallsmilies'] . "' /></div></form>\n";
		}
		echo "<br />\n";
		$forums->admin->print_form_header(array(1 => array('do' , 'uploadsmile'), 2 => array('MAX_FILE_SIZE', '10000000000')) , "uploadform", " enctype='multipart/form-data'");
		$forums->admin->columns[] = array("&nbsp;" , "50%");
		$forums->admin->columns[] = array("&nbsp;" , "50%");
		$forums->admin->print_table_start($forums->lang['uploadnewsmilies']);
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('upload_1', $_INPUT['upload_1'], 'file', '', 30),
				$forums->admin->print_input_row('upload_2', $_INPUT['upload_2'], 'file', '', 30),
				));
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('upload_3', $_INPUT['upload_3'], 'file', '', 30),
				$forums->admin->print_input_row('upload_4', $_INPUT['upload_4'], 'file', '', 30),
				));
		$forums->admin->print_form_submit($forums->lang['uploadnewsmilies']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function get_folder_contents($type)
	{
		global $forums, $DB;
		$files = array();
		$dh = opendir(ROOT_PATH . 'images/' . $type);
		while ($file = readdir($dh))
		{
			if (($file != ".") && ($file != ".."))
			{
				if (preg_match("/\.(?:gif|jpg|jpeg|png|swf)$/i", $file))
				{
					$files[] = $file;
				}
			}
		}
		closedir($dh);
		return $files;
	}

	function icon_start()
	{
		global $forums, $DB, $_INPUT;
		if (! is_dir(ROOT_PATH . 'images/icons'))
		{
			$forums->admin->print_cp_error($forums->lang['iconfoldererror']);
		}
		$forums->admin->nav[] = array('image.php?do=icon', $forums->lang['manageicons']);
		$pagetitle = $forums->lang['manageicons'];
		$detail = $forums->lang['manageiconsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$icon_db = array();
		$icon_file = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "icon ORDER BY displayorder");
		while ($r = $DB->fetch_array())
		{
			$icon_db[ $r['image'] ] = $r;
		}
		$icon_file = array();
		$icon_rfile = $this->get_folder_contents('icons');
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>$extra\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $forums->lang['currentusedicons'] . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<form action='image.php?{$forums->sessionurl}do=doiconedit' method='post'>\n";
		echo "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
		$count = 0;
		$poss_names = array();
		if ($icon_rfile)
		{
			foreach($icon_rfile AS $ef)
			{
				$icon_file[ $ef ] = $ef;
			}
			echo "<tr align='center'>\n";
			$per_row = 4;
			$td_width = 100 / $per_row;
			foreach($icon_db AS $image => $data)
			{
				$count++;
				unset($icon_file[ $image ]);
				echo "<td width='{$td_width}%' align='center' class='tdrow1'>\n";
				echo "<fieldset>\n";
				echo "<legend><strong>{$image}</strong></legend>\n";
				echo "<div style='float:right' align='left'>\n";
				echo $forums->lang['smiletext'] . ": " . $forums->admin->print_input_row('icon_type_' . $data['id'] . '', $data['icontext'], '', '', 8) . "<br />\n";
				echo $forums->lang['smileorder'] . ": " . $forums->admin->print_input_row('icon_order_' . $data['id'] . '', $data['displayorder'], '', '', 5) . "<br /><input name='icon_remove_" . $data['id'] . "' type='checkbox' value='1' /><strong>" . $forums->lang['delete'] . "</strong>\n";
				echo "<br /></div>\n";
				echo "<img src='../images/icons/{$image}' border='0' alt='' /></fieldset>\n";
				echo "</td>\n";
				if ($count == $per_row)
				{
					echo "</tr>\n\n<tr align='center'>\n";
					$count = 0;
				}
				$poss_names[$data['icontext']] = $data['icontext'];
			}
			if ($count > 0 AND $count != $per_row)
			{
				for ($i = $count ; $i <= $per_row ; ++$i)
				{
					echo "<td class='tdrow1'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			else if ($count == 0)
			{
				echo "<td></td></tr>\n";
			}

			echo "</table>\n";
			echo "<div class='pformstrip' align='center'><input type='submit' class='button' value='" . $forums->lang['updateicons'] . "' /></div></form><br />\n";
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyicons'], "center");
			echo "</table>\n";
		}
		if (count($icon_file))
		{
			echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
			echo "<tr><td class='tableborder'>$extra\n";
			echo "<div class='catfont'>\n";
			echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;" . $forums->lang['nousedicons'] . "</div>\n";
			echo "</td></tr>\n";
			echo "</table>\n";
			echo "<form action='image.php?{$forums->sessionurl}do=doiconadd' method='post'>\n";
			echo "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
			$count = 0;
			echo "<tr align='center'>\n";
			$master_count = 0;
			foreach($icon_file AS $image)
			{
				$count++;
				$master_count++;
				$poss_name = ':' . preg_replace("/(.*)(\..+?)$/", "\\1", $image) . ':';
				if ($poss_names[ $poss_name ])
				{
					$poss_name = preg_replace("/:$/", "2:", $poss_name);
				}
				echo "<td width='{$td_width}%' align='center' class='tdrow1'>\n";
				echo "<fieldset>\n";
				echo "<legend><strong>{$image}</strong></legend>\n";
				echo "<img src='../images/icons/{$image}' border='0' alt='' />\n";
				echo "<br />\n";
				echo "<input type='textinput' class='button' size='10' name='icon_type_{$master_count}' value='$poss_name' />\n";
				echo "<br /><input name='icon_add_{$master_count}' type='checkbox' value='1' /><strong>" . $forums->lang['add'] . "</strong>&nbsp;|&nbsp;<a href='image.php?{$forums->sessionurl}do=deleteicon&amp;name={$image}' title='" . $forums->lang['completedeleteicons'] . "'><strong>" . $forums->lang['completedelete'] . "</strong></a>\n";
				echo "<input type='hidden' name='icon_image_{$master_count}' value='{$image}' />\n";
				echo "</fieldset>\n";
				echo "</td>\n";
				if ($count == $per_row)
				{
					echo "</tr>\n\n<tr align='center'>\n";
					$count = 0;
				}
			}
			if ($count > 0 AND $count != $per_row)
			{
				for ($i = $count ; $i < $per_row ; ++$i)
				{
					echo "<td class='tdrow1'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			else if ($count == 0)
			{
				echo "<td></td></tr>\n";
			}
			echo "</table>\n";
			echo "<div class='pformstrip' align='center'><input type='submit' class='button' value='" . $forums->lang['addselectedicons'] . "' />&nbsp;&nbsp;<input type='submit' name='addall' class='button' value='" . $forums->lang['addallicons'] . "' /></div></form>\n";
		}
		echo "<br />";
		$forums->admin->print_form_header(array(1 => array('do' , 'uploadicon'),
				2 => array('MAX_FILE_SIZE', '10000000000'),
				) , "uploadform", " enctype='multipart/form-data'");
		$forums->admin->columns[] = array("&nbsp;" , "50%");
		$forums->admin->columns[] = array("&nbsp;" , "50%");
		$forums->admin->print_table_start($forums->lang['uploadnewicons']);
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('upload_1', $_INPUT['upload_1'], 'file', '', 30), $forums->admin->print_input_row('upload_2', $_INPUT['upload_2'], 'file', '', 30)));
		$forums->admin->print_cells_row(array($forums->admin->print_input_row('upload_3', $_INPUT['upload_3'], 'file', '', 30), $forums->admin->print_input_row('upload_4', $_INPUT['upload_4'], 'file', '', 30)));
		$forums->admin->print_form_submit($forums->lang['uploadnewicons']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doiconedit()
	{
		global $forums, $DB, $_INPUT;
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^icon_type_(\d+)$/", $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					if ($_INPUT['icon_remove_' . $match[1] ])
					{
						$delids[] = $match[1];
					}
					$icontext = $_INPUT[$match[0]];
					$displayorder = $_INPUT['icon_order_' . $match[1] ];
					$icontext = str_replace('&#092;', "", $icontext);
					if ($icontext AND $match[1])
					{
						$DB->update(TABLE_PREFIX . 'icon', array(
							'displayorder' => intval($displayorder),
							'icontext' => $icontext
						), 'id=' . $match[1]);
					}
				}
			}
		}
		if (is_array($delids))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "icon WHERE id IN (" . implode(',', $delids) . ")");
		}
		$forums->func->recache('icon');
		$forums->main_msg = $forums->lang['iconsupdated'];
		$this->icon_start();
	}

	function doiconadd()
	{
		global $forums, $DB, $_INPUT;
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^icon_type_(\d+)$/", $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					$icontext = $_INPUT[$match[0]];
					$add = $_INPUT['icon_add_' . $match[1] ];
					$image = $_INPUT['icon_image_' . $match[1] ];
					$icontext = str_replace('&#092;', "", $icontext);
					if ($_INPUT['addall'])
					{
						$add = 1;
					}
					if ($add && $icontext && $image)
					{
						$DB->insert(TABLE_PREFIX . 'icon', array(
							'icontext' => $icontext,
							'image' => $image
						));
					}
				}
			}
		}
		$forums->func->recache('icon');
		$forums->main_msg = $forums->lang['iconsuploaded'];
		$this->icon_start();
	}

	function uploadicon()
	{
		global $forums, $DB, $_INPUT;
		$overwrite = 1;
		foreach(array(1, 2, 3, 4) AS $i)
		{
			$field = 'upload_' . $i;
			$filename = $_FILES[$field]['name'];
			$filesize = $_FILES[$field]['size'];
			$filetype = $_FILES[$field]['type'];
			$filetype = preg_replace("/^(.+?);.*$/", "\\1", $filetype);
			if ($_FILES[$field]['name'] == "" OR ! $_FILES[$field]['name'] OR ($_FILES[$field]['name'] == "none"))
			{
				continue;
			}
			if (! @move_uploaded_file($_FILES[ $field ]['tmp_name'], ROOT_PATH . 'images/icons/' . $filename))
			{
				$forums->main_msg = $forums->lang['uploadfailed'];
				$this->icon_start();
			}
			else
			{
				@chmod(ROOT_PATH . 'images/icons/' . $filename, 0777);
				if (is_array($directories) AND count($directories))
				{
					foreach ($directories AS $newdir)
					{
						if (file_exists(ROOT_PATH . 'images/icons/' . $filename))
						{
							if ($overwrite != 1)
							{
								continue;
							}
						}
						if (@copy(ROOT_PATH . 'images/icons/' . $filename, ROOT_PATH . 'images/icons/' . $filename))
						{
							@chmod(ROOT_PATH . 'images/icons/' . $filename, 0777);
						}
					}
				}
			}
		}
		$forums->main_msg = $forums->lang['iconsuploaded'];
		$this->icon_start();
	}

	function deleteicon()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['name'] == "")
		{
			$forums->main_msg = $forums->lang['noids'];
			return $this->icon_start();
		}
		if ($_INPUT['update'])
		{
			if (! @unlink(ROOT_PATH . 'images/icons/' . $_INPUT['name']))
			{
				$forums->main_msg = $forums->lang['noids'];
			}
			else
			{
				$forums->main_msg = $forums->lang['iconsdeleted'];
			}
			$this->icon_start();
		}
		else
		{
			$pagetitle = $forums->lang['deleteicon'];
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'deleteicon'),
					2 => array('name' , $_INPUT['name']),
					3 => array('update', 1),
					));
			$forums->admin->print_table_start($forums->lang['deleteicon']);
			$forums->admin->print_cells_row(array($forums->lang['deletesmiliedesc']));
			$forums->admin->print_form_submit($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}
}

$output = new image();
$output->show();

?>