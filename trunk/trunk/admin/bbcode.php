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
# $Id: bbcode.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class bbcode
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditbbcodes'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('bbcode.php', $forums->lang['managebbcode']);
		switch ($_INPUT['do'])
		{
			case 'add':
				$this->bbcode_form('add');
				break;
			case 'doadd':
				$this->save('add');
				break;
			case 'edit':
				$this->bbcode_form('edit');
				break;
			case 'doedit':
				$this->save('edit');
				break;
			case 'test':
				$this->test();
				break;
			case 'delete':
				$this->dodelete();
				break;
			default:
				$this->showcode();
				break;
		}
	}

	function dodelete()
	{
		global $forums, $DB, $_INPUT;
		if (! $_INPUT['id'])
		{
			$forums->main_msg = $forums->lang['noids'];
			$this->showcode();
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "bbcode WHERE bbcodeid=" . $_INPUT['id'] . "");
		$forums->func->recache('bbcode');
		$this->showcode();
	}

	function test()
	{
		global $forums, $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "bbcode ORDER BY title");
		$text = convert_andstr($_POST['bbtest']);
		while ($row = $DB->fetch_array())
		{
			if (substr_count($row['bbcodereplacement'], '{content}') > 1)
			{
				if ($row['twoparams'])
				{
					preg_match("#\[" . $row['bbcodetag'] . "=(?:&quot;|&\#39;)?(.+?)(?:&quot;|&\#39;)?\](.+?)\[/" . $row['bbcodetag'] . "\]#si", $text, $match);
					$row['bbcodereplacement'] = str_replace('{option}' , $match[1], $row['bbcodereplacement']);
					$row['bbcodereplacement'] = str_replace('{content}', $match[2], $row['bbcodereplacement']);
					$text = preg_replace("#\[" . $row['bbcodetag'] . "=(?:.+?)\](?:.+?)\[/" . $row['bbcodetag'] . "\]#si", $row['bbcodereplacement'], $text);
				}
				else
				{
					preg_match("#\[" . $row['bbcodetag'] . "\](.+?)\[/" . $row['bbcodetag'] . "\]#si", $text, $match);
					$row['bbcodereplacement'] = str_replace('{content}', $match[1], $row['bbcodereplacement']);
					$text = preg_replace("#\[" . $row['bbcodetag'] . "\](?:.+?)\[/" . $row['bbcodetag'] . "\]#si", $row['bbcodereplacement'], $text);
				}
			}
			else
			{
				$replace = explode('{content}', $row['bbcodereplacement']);
				if ($row['twoparams'])
				{
					$text = preg_replace("#\[" . $row['bbcodetag'] . "=(?:&quot;|&\#39;)?(.+?)(?:&quot;|&\#39;)?\]#si", str_replace('{option}', "\\1", $replace[0]), $text);
				}
				else
				{
					$text = str_replace('[' . $row['bbcodetag'] . ']' , $replace[0], $text);
				}
				$text = str_replace('[/' . $row['bbcodetag'] . ']', $replace[1], $text);
			}
		}
		$forums->main_msg = "<strong>" . $forums->lang['testbbcode'] . ":</strong><br /><br />" . $text;
		$this->showcode();
	}

	function save($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'edit')
		{
			if (! $_INPUT['id'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->bbcode_form($type);
			}
		}
		if (! $_INPUT['title'] OR ! $_INPUT['bbcodetag'] OR ! $_INPUT['bbcodereplacement'])
		{
			$forums->main_msg = $forums->lang['inputallforms'];
			$this->bbcode_form($type);
		}
		if (! strstr($_INPUT['bbcodereplacement'], '{content}'))
		{
			$forums->main_msg = $forums->lang['requirecontents'];
			$this->bbcode_form($type);
		}
		if (! strstr($_INPUT['bbcodereplacement'], '{option}') AND $_INPUT['twoparams'])
		{
			$forums->main_msg = $forums->lang['requireoption'];
			$this->bbcode_form($type);
		}
		$bbcode = array(
			'title' => convert_andstr($_POST['title']),
			'description' => convert_andstr($_POST['description']),
			'bbcodetag' => $_INPUT['bbcodetag'],
			'bbcodereplacement' => convert_andstr($_POST['bbcodereplacement']),
			'bbcodeexample' => convert_andstr($_POST['bbcodeexample']),
			'twoparams' => convert_andstr($_POST['twoparams']),
			'imagebutton' => convert_andstr($_POST['imagebutton'])
		);
		if ($type == 'add')
		{
			$DB->insert(TABLE_PREFIX . 'bbcode', $bbcode);
			$forums->main_msg = $forums->lang['bbcodeadded'];
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'bbcode', $bbcode, 'bbcodeid=' . $_INPUT['id']);
			$forums->main_msg = $forums->lang['bbcodeedited'];
		}
		$forums->func->recache('bbcode');
		$this->showcode();
	}

	function bbcode_form($type = 'add')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'edit')
		{
			if (! $_INPUT['id'])
			{
				$forums->main_msg = $forums->lang['noids'];
				$this->showcode();
			}
			$bbcode = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "bbcode WHERE bbcodeid='" . $_INPUT['id'] . "'");
			$button = $forums->lang['editbbcode'];
			$code = 'doedit';
			$pagetitle = $forums->lang['editbbcode'] . ": " . $bbcode['title'];
			$forums->admin->nav[] = array('' , $forums->lang['editbbcode']);
		}
		else
		{
			$bbcode = array();
			$code = 'doadd';
			$pagetitle = $forums->lang['addbbcode'];
			$button = $forums->lang['addbbcode'];
			$forums->admin->nav[] = array('' , $forums->lang['addbbcode']);
		}
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(1 => array('do' , $code), 2 => array('id', $_INPUT['id'])));
		$forums->admin->columns[] = array("&nbsp;", "40%");
		$forums->admin->columns[] = array("&nbsp;", "60%");
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodetitle'] . "</strong><div class='description'>" . $forums->lang['bbcodetitledesc'] . "</div>", $forums->admin->print_input_row('title', $_INPUT['title'] ? $_INPUT['title'] : $bbcode['title'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodedesc'] . "</strong>", $forums->admin->print_textarea_row('description', $_INPUT['description'] ? $_INPUT['description'] : $bbcode['description'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodeexample'] . "</strong><div class='description'>" . $forums->lang['bbcodeexampledesc'] . "</div>", $forums->admin->print_textarea_row('bbcodeexample', $_INPUT['bbcodeexample'] ? $_INPUT['bbcodeexample'] : $bbcode['bbcodeexample'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodetag'] . "</strong><div class='description'>" . $forums->lang['bbcodetagdesc'] . "</div>", '[ ' . $forums->admin->print_input_row('bbcodetag', $_INPUT['bbcodetag'] ? $_INPUT['bbcodetag'] : $bbcode['bbcodetag'], '', '', 10) . ' ]'));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['twoparams'] . "</strong><div class='description'>" . $forums->lang['twoparamsdesc'] . "</div>", $forums->admin->print_yes_no_row('twoparams', $_INPUT['twoparams'] ? $_INPUT['twoparams'] : $bbcode['twoparams'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodereplacement'] . "</strong><div class='description'>&lt;tag&gt;{content}&lt;/tag&gt;<br />&lt;tag='{option}'&gt;{content}&lt;/tag&gt;</div>", $forums->admin->print_textarea_row('bbcodereplacement', $_INPUT['bbcodereplacement'] ? $_INPUT['bbcodereplacement'] : utf8_htmlspecialchars($bbcode['bbcodereplacement']))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bbcodeimage'] . "</strong><div class='description'>" . $forums->lang['bbcodeimagedesc'] . "</div>", $forums->admin->print_yes_no_row('imagebutton', $_INPUT['imagebutton'] ? $_INPUT['imagebutton'] : $bbcode['imagebutton'])));
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		echo "<div class='tableborder' style='padding:6px'>" . $forums->lang['bbcodehelp'] . "</div>";
		$forums->admin->print_cp_footer();
	}

	function showcode()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managebbcode'];
		$detail = $forums->lang['managebbcodedesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'add')), 'addform');
		$forums->admin->columns[] = array($forums->lang['title'], "30%");
		$forums->admin->columns[] = array($forums->lang['tags'], "35%");
		$forums->admin->columns[] = array($forums->lang['button'], "5%");
		$forums->admin->columns[] = array($forums->lang['option'], "30%");
		$forums->admin->print_table_start($forums->lang['managebbcode']);
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "bbcode ORDER BY title");
		while ($row = $DB->fetch_array())
		{
			if ($row['twoparams'])
			{
				$option = '={option}';
			}
			else
			{
				$option = '';
			}
			$image = $row['imagebutton'] ? "<img src='../editor/images/" . strtolower($row['bbcodetag']) . ".gif' alt='' align='middle'  width='22' height='22' />" : '&nbsp;';
			$forums->admin->print_cells_row(array("<strong>" . $row['title'] . "</strong>",
					'[' . $row['bbcodetag'] . $option . ']{content}[/' . $row['bbcodetag'] . ']',
					"<div align='center'>" . $image . "</div>",
					"<div align='center'>" . $forums->admin->print_button($forums->lang['edit'], "bbcode.php?{$forums->sessionurl}do=edit&amp;id={$row['bbcodeid']}") . '&nbsp;' . $forums->admin->print_button($forums->lang['delete'], "bbcode.php?{$forums->sessionurl}do=delete&amp;id={$row['bbcodeid']}")
					 . "</div>",
					));
		}
		$forums->admin->print_form_submit($forums->lang['addbbcode']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do' , 'test'),), 'testform');
		$forums->admin->columns[] = array("&nbsp;", "40%");
		$forums->admin->columns[] = array("&nbsp;", "60%");
		$forums->admin->print_table_start($forums->lang['testbbcode']);
		$forums->admin->print_cells_row(array($forums->lang['testbbcode'], $forums->admin->print_textarea_row('bbtest', $_INPUT['bbtest'])));
		$forums->admin->print_form_submit($forums->lang['testbbcode']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new bbcode();
$output->show();

?>