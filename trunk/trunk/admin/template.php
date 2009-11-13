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
# $Id: template.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
require ('./global.php');

class template
{
	var $altered;
	var $template;

	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditstyles'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		require_once(ROOT_PATH . 'includes/adminfunctions_template.php');
		$this->template = new adminfunctions_template();
		$forums->admin->nav[] = array('style.php' , $forums->lang['managestyle']);
		$this->unaltered = "<img src='{$forums->imageurl}/style_unaltered.gif' border='0' alt='-' title='" . $forums->lang['styleunaltered'] . "' />&nbsp;";
		$this->altered = "<img src='{$forums->imageurl}/style_altered.gif' border='0' alt='+' title='" . $forums->lang['stylealtered'] . "' />&nbsp;";
		$this->inherited = "<img src='{$forums->imageurl}/style_inherited.gif' border='0' alt='|' title='" . $forums->lang['styleinherited'] . "' />&nbsp;";
		$forums->admin->cache_styles();

		switch ($_INPUT['do'])
		{
			case 'edit':
				$this->edittemplates();
				break;
			case 'doedit':
				$this->do_edit();
				break;
			case 'remove_bit':
				$this->removetemplatebit();
				break;
			case 'edittemplatebit':
				$this->edittemplatebit();
				break;
			case 'floateditor':
				$this->floatededitor();
				break;
			case 'addbit':
				$this->addtemplatebit();
				break;
			case 'doadd':
				$this->do_addtemplatebit();
				break;
			case 'preview':
				$this->do_preview();
				break;
			case 'search':
				$this->edittemplates('search');
				break;
			case 'compare':
				$this->compare();
				break;
			default:
				$this->edittemplates();
				break;
		}
	}

	function edittemplates($type = 'list')
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		if (!$_INPUT['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$styles = $forums->admin->stylecache[$_INPUT['id']];
		$forums->admin->nav[] = array('template.php?id=' . $_INPUT['id'] , $forums->lang['templatesetting'] . ' - "' . $styles['title'] . '"');

		$groups = array();
		$group_bits = array();
		$inherit = true;
		if ($type == 'search')
		{
			$search_word = $_GET['searchkeywords'] ? rawurldecode($_GET['searchkeywords']) : $_POST['searchkeywords'];
			$search_word = trim($search_word);
			$search_safe = urlencode($search_word);
			$search_all = intval($_INPUT['searchall']);

			if (!$search_word)
			{
				$forums->main_msg = $forums->lang['requirekeyword'];
				return $this->edittemplates();
			}

			if ($search_all)
			{
				$templates = $this->template->get_templates($styles['styleid'], 'all');
			}
			else
			{
				$templates = $this->template->get_templates($styles['styleid'], 'this');
				$inherit = false;
			}

			if (empty($templates))
			{
				$forums->main_msg = $forums->lang['cannotfindtemplate'];
				return $this->edittemplates();
			}
			$final = $matches = array();
			foreach($templates as $group => $d)
			{
				foreach($templates[$group] as $tmp_name => $tmp_data)
				{
					$tpl_data = $this->template->get_template_content($styles['styleid'], $tmp_data['title'], $inherit);
					if (strpos(strtolower($tpl_data), strtolower($search_word)) !== false)
					{
						if (!isset($final[$group]))
						{
							$final[$group] = array();
						}
						$final[$group][] = $tmp_data;
						$matches[$group]++;
					}
				}
			}

			if (empty($final))
			{
				$forums->main_msg = $forums->lang['cannotfindtemplate'];
				return $this->edittemplates();
			}
		}
		$pagetitle = $forums->lang['edittemplate'];
		$detail = $forums->lang['edittemplatedesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$grouptitles = $this->template->get_templates($styles['styleid'], 'groups');
		foreach($grouptitles as $title => $g)
		{
			$g['easy_name'] = '<strong>' . $g['templategroup'] . '</strong>';
			$g['easy_desc'] = '';
			$groups[] = $g;
		}
		echo <<<JS
<script language='javascript' type='text/javascript'>
var toggleon  = 0;
function toggleselectall()
{
	if (toggleon)
	{
		toggleon = 0;
		dotoggleselectall(0);
	}
	else
	{
		toggleon = 1;
		dotoggleselectall(1);
	}
}

function dotoggleselectall(selectall)
{
	var fmobj = document.mutliact;
	for (var i=0;i<fmobj.elements.length;i++)
	{
		var e = fmobj.elements[i];
		if (e.type=='checkbox')
		{
			if (selectall) e.checked = true;
			else e.checked = false;
		}
	}
}

function dodeletealltemp()
{
	if (!confirm('{$forums->lang['areyousuredelete']}\\n{$forums->lang['cannotrevert']}'))
		return false;
	var fmobj = document.mutliact;
	var orgaction = fmobj.action;
	fmobj.action = fmobj.delmultiaction.value
	fmobj.submit();
	fmobj.action = orgaction
}
</script>
JS;
	echo "
<form action='template.php?{$forums->sessionurl}do=search&amp;id={$_INPUT['id']}&amp;searchall=1' method='post'>
<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>
<tr><td class='tableborder'>
<div style='float:right'><input type='text' size='20' class='textinput' name='searchkeywords' value='{$forums->lang['searchtpl']}...' onfocus=\"this.value=''\" />&nbsp;<input type='submit' class='button' value='{$forums->lang['ok']}' />&nbsp;".$forums->admin->print_button($forums->lang['addnewtemplate'], "template.php?{$forums->sessionurl}do=addbit&amp;id={$_INPUT['id']}&amp;p={$_INPUT['p']}&amp;", "button", $forums->lang['addnewtemplate'])."</div>
<div id='catfont'>
<img src='" . $forums->imageurl . "/arrow.gif' class='inline' />&nbsp;&nbsp;" . $forums->lang['templatelist'] . "</div>
</td></tr>
</table>
</form>
<div class='tdrow1'>\n";
		foreach ($groups as $group)
		{
			$eid = $group['tid'];
			$exp_content = '';
			if ($_INPUT['expand'] == $group['templategroup'] || count($final[$group['templategroup']]))
			{
				$forums->admin->checkdelete();

				echo "
<a name='{$group['templategroup']}'></a>
<div style='padding:4px;border-top:1px solid #E1EEF7;border-bottom:1px solid #142938;'>
<table cellspacing='0' cellpadding='0' border='0'>
<tr>
<td align='center' width='1%'><img src='{$forums->imageurl}/toc_collapse.gif' alt='" . $forums->lang['templategroup'] . "' style='vertical-align:middle' /></td>
<td align='left' width='60%'>\n";
				if ($type == 'search')
				{
					$search_match = intval($matches[ $group['templategroup'] ]);
					$poxbox = '_' . $group['templategroup'];
					echo "
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"toggle('popbox_{$group['templategroup']}'); return false;\">{$group['easy_name']}</a> (" . $forums->lang['result'] . ": {$search_match})
</td>
<td align='right' width='40%'>{$group['easy_preview']}</td>
</tr>
</table>
</div>
<div style='margin-left:25px;border:1px solid #555;display:none;' id='popbox_{$group['templategroup']}'>\n";
				}
				else
				{
					echo "
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='template.php?{$forums->sessionurl}do=edit&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}' onclick=\"toggle('popbox'); return false;\">{$group['easy_name']}</a>
</td>
<td align='right' width='40%'>{$group['easy_preview']}</td>
</tr>
</table>
</div>
<div style='border:1px solid #555;' id='popbox'>\n";
				}
				echo "
<div style='background-color:#142938' class='styleeditortopstrip'>
<div style='float:right'>
<a href='#' onclick=\"togglediv('popbox{$poxbox}'); return false;\" title='" . $forums->lang['closetemplategroup'] . "'><img src='{$forums->imageurl}/style_close.gif' border='0' alt='" . $forums->lang['closetemplategroup'] . "' /></a>
</div>
<div>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"toggleselectall(); return false;\" title='" . $forums->lang['checkuncheck'] . "'><img src='{$forums->imageurl}/style_checked.gif' border='0' alt='" . $forums->lang['checkuncheck'] . "' /></a>
&nbsp;{$group['easy_name']}
</div>
</div>
<form name='mutliact' action='template.php?{$forums->sessionurl}do=edittemplatebit&amp;expand={$group['templategroup']}&amp;id={$_INPUT['id']}&amp;p={$_INPUT['p']}&amp;search_string=$search_safe' method='post'>
<input type='hidden' name='delmultiaction' id='delmultiaction' value='template.php?{$forums->sessionurl}do=remove_bit&amp;type=multi&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}' />
<div>
<table cellspacing='0' cellpadding='2'>\n";
				$temp = '';
				$sec_arry = array();
				if ($type == 'search')
				{
					$group_bits = $final[$group['templategroup']];
				}
				else
				{
					$group_bits = $this->template->get_templates($styles['styleid'], 'groups', $_INPUT['expand']);
				}

				foreach($group_bits as $eye => $i)
				{
					$sec_arry[$i['title']] = $i;
					$sec_arry[$i['title']]['easy_name'] = $i['title'];
				}

				foreach($sec_arry as $id => $sec)
				{
					$sec['easy_name'] = preg_replace('/^(\d+)\:\s+?/', '', $sec['easy_name']);
					$custom_bit = '';
					if ($sec['type'] == 1)
					{
						$altered_image = $this->altered;
						$css_info = '#4790C4';
					}
					else if ($sec['type'] == 0)
					{
						$altered_image = $this->unaltered;
						$css_info = '#285270';
					}
					else
					{
						$altered_image = $this->inherited;
						$css_info = '#FFF2D3';
					}

					$remove_button = "<img src='{$forums->imageurl}/blank.gif' alt='' border='0' width='44' height='16' />&nbsp;";
					if ($sec['type'] == 1)
					{
						$pre = (empty($group['templategroup']) || $group['templategroup'] == 'global') ? '' : $group['templategroup'] . '_';
						if (file_exists(ROOT_DIR . 'templates/global/' . $pre . $sec['title'] . '.htm'))
						{
							$remove_button = "<a title='" . $forums->lang['revertoriginaltpl'] . "' href=\"javascript:checkdelete('template.php','do=remove_bit&amp;type=single&amp;title={$sec['title']}&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}')\"><img src='{$forums->imageurl}/te_revert.gif' alt='X' border='0' /></a>&nbsp;";
						}
						else
						{
							$css_info = '#4790C4';
							$custom_bit = ' (' . $forums->lang['customtemplate'] . ')';
							$remove_button = "<a title='" . $forums->lang['revertoriginaltpl'] . "' href=\"javascript:checkdelete('template.php','do=remove_bit&amp;type=single&amp;title={$sec['title']}&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}')\"><img src='{$forums->imageurl}/te_remove.gif' alt='X' border='0' /></a>&nbsp;";
						}
					}
					echo "<tr>\n";
					echo "<td width='2%' style='background-color:$css_info' align='center'><img src='{$forums->imageurl}/file.gif' title='" . $forums->lang['styleid'] . ":{$sec['styleid']}' alt='" . $forums->lang['template'] . "' style='vertical-align:middle' /></td>\n";
					echo "<td width='88%' style='background-color:$css_info'><input type='checkbox' style='background-color:$css_info' name='cb_{$sec['title']}' value='1' />&nbsp;{$altered_image}<a href='template.php?{$forums->sessionurl}do=edittemplatebit&amp;title={$sec['title']}&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}&amp;type=single&amp;search_string=$search_safe' title='" . $forums->lang['templatename'] . ": {$sec['title']}'>{$sec['easy_name']}</a>{$custom_bit}</td>\n";
					echo "<td width='10%' style='background-color:$css_info' align='right' nowrap='nowrap'>" . $remove_button . "<a style='text-decoration:none' title='" . $forums->lang['viewtemplateastext'] . "' href='javascript:pop_win(\"template.php?{$forums->sessionurl}do=preview&amp;id={$_INPUT['id']}&amp;title={$sec['title']}&amp;type=text\", \"Preview\", 400, 450)'><img src='{$forums->imageurl}/preview_text.gif' border='0' alt='" . $forums->lang['viewtemplateastext'] . "' /></a>\n";
					echo "<a style='text-decoration:none' title='" . $forums->lang['viewtemplateashtml'] . "' href='javascript:pop_win(\"template.php?{$forums->sessionurl}do=preview&amp;id={$_INPUT['id']}&amp;title={$sec['title']}&amp;type=html\", \"Preview\", 400, 450)'><img src='{$forums->imageurl}/preview_html.gif' border='0' alt='" . $forums->lang['viewtemplateashtml'] . "' />&nbsp;</a>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";
				echo "</div>\n";
				echo "<div style='background:#142938'>\n";
				echo "<div align='left' style='padding:5px;margin-left:25px'>\n";
				echo "<div style='float:right'>" . $forums->admin->print_button($forums->lang['addnewtemplate'], "template.php?{$forums->sessionurl}do=addbit&amp;id={$_INPUT['id']}&amp;p={$_INPUT['p']}&amp;expand={$group['templategroup']}", "button", $forums->lang['addnewtemplate']) . "</div>\n";
				echo "<div><input type='submit' class='button' value='" . $forums->lang['editselecttemplate'] . "' />&nbsp;&nbsp;";
				if ($type != 'search')
				{
					echo "<input type='button' class='button' onclick = 'dodeletealltemp();' value='" . $forums->lang['resetdelselecttpl'] . "' />";
				}
				echo "</div>\n";
				echo "</div>\n";
				echo "</div>\n";
				echo "</form>\n";
				echo "</div>\n";
			}
			else if ($type != 'search')
			{
				$altered = sprintf('%02d', $this->template->template_count[$group['templategroup']]['altered']);
				$original = sprintf('%02d', $this->template->template_count[$group['templategroup']]['original']);
				$inherited = sprintf('%02d', $this->template->template_count[$group['templategroup']]['inherited']);
				$count_string = '';
				if ($styles['parentid'] != 1)
				{
					$count_string = "{$this->unaltered} $original | {$this->inherited} $inherited | {$this->altered} $altered ";
				}
				else
				{
					$count_string = "{$this->unaltered} $original | {$this->altered} $altered";
				}

				if ($altered > 0)
				{
					$folder_blob = $this->altered;
				}
				else if ($styles['parentid'] != 1 && $inherited > 0)
				{
					$folder_blob = $this->inherited;
				}
				else
				{
					$folder_blob = $this->unaltered;
				}

				echo "
<div style='padding:4px;border-bottom:1px solid #DDDDDD;'>
<table cellspacing='0' cellpadding='0' border='0'>
<tr>
<td align='center' width='1%'><img src='{$forums->imageurl}/toc_expand.gif' alt='" . $forums->lang['templategroup'] . "' style='vertical-align:middle' /></td>
<td align='left' width='60%'>&nbsp;{$folder_blob}&nbsp;<a style='font-size:12px' onmouseover=\"return togglediv('desc_{$group['templategroup']}', 1);\" onmouseout=\"return togglediv('desc_{$group['templategroup']}', 0);\" href='template.php?{$forums->sessionurl}do=edit&amp;id={$_INPUT['id']}&amp;expand={$group['templategroup']}#{$group['templategroup']}'>{$group['easy_name']}</a></td>
<td align='right' width='40%'>($count_string)</td>
</tr>
</table>
</div>\n";
			}
		}
		echo "
</div>
<br />
<div><strong>{$forums->lang['templatemenudesc']}:</strong><br />
{$this->altered} {$forums->lang['hastemplatealtered']}<br />
{$this->unaltered} {$forums->lang['hastemplateunaltered']}<br />
{$this->inherited} {$forums->lang['hastemplateinherited']}
</div>
<br />\n";
		$forums->admin->print_cp_footer();
	}

	function do_preview()
	{
		global $forums, $DB, $_INPUT;
		$styleid = $_INPUT['id'] ? 1 : intval($_INPUT['id']);
		$title = trim($_INPUT['title']);
		if (!$title || !$styleid)
		{
			$forums->admin->print_cp_error($forums->lang['requiretemplatename']);
		}

		$groupname = ($pos = strpos($title, '_')) ? substr($title, 0, $pos) : 'global';
		$content = $this->template->get_template_content($styleid, $title);

		if ($_INPUT['type'] == 'html')
		{
			$css_text = $this->template->get_template_content($styleid, 'style.css');
			$css_text = "\n<style>\n<!--\n" . str_replace('<#IMAGE#>', 'images/' . $r['img_dir'], $css) . "\n//-->\n</style>";
		}

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['preview'] . "</title>$css_text</head><body> \n";
		echo "<table width='100%' cellpadding='4' style='background-color: #000000;font-family:verdana, arial;font-size:12px;color:white'>\n";
		echo "<tr>\n";
		echo "<td align='center' style='font-family:verdana, arial;font-size:12px;color:white'>" . $forums->lang['templategroup'] . ": $groupname ; " . $forums->lang['templatename'] . ": $title</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='center' style='font-family:verdana, arial;font-size:12px;color:white'>[ <a href='template.php?{$forums->sessionurl}do=preview&amp;id=$styleid&amp;title=$title&amp;type=text' style='font-family:verdana, arial;font-size:12px;color:white'>" . $forums->lang['textmode'] . "</a> | <a href='template.php?{$forums->sessionurl}do=preview&amp;id=$styleid&amp;title=$title&amp;type=html' style='font-family:verdana, arial;font-size:12px;color:white'>" . $forums->lang['htmlmode'] . "</a> ]</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<br /><br />\n";
		if ($_INPUT['type'] == 'text')
		{
			$html = $this->convert_tags($content);
			$html = $this->highlight_code($html);
			echo "<table width='100%' cellpadding='4' style='font-family:verdana, arial;font-size:12px;'><tr><td>" . $html . "</td></tr></table>";
			exit();
		}
		else if ($_INPUT['type'] == 'html')
		{
			echo $this->convert_tags($content);
			exit();
		}
	}

	function edittemplatebit()
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['edittemplate'];
		$titles = array();
		$groupname = '';
		if ($_INPUT['type'] == 'single')
		{
			$titles[] = $_INPUT['title'];
			$groupname = $this->template->get_groupname($_INPUT['title']);
		}
		else
		{
			foreach ($_INPUT as $key => $value)
			{
				if (preg_match('/^cb_([a-z0-9_]+)$/i', $key, $match))
				{
					if ($_INPUT[$match[0]])
					{
						$titles[] = $match[1];
						if (empty($groupname))
						{
							$groupname = $this->template->get_groupname($match[1]);
						}
					}
				}
			}
		}

		if (count($titles) < 1)
		{
			$forums->admin->print_cp_error($forums->lang['noselecttemplate']);
		}

		$forums->admin->nav[] = array("template.php?do=edit&amp;id={$_INPUT['id']}&amp;expand={$groupname}#{$groupname}", $forums->lang['templategroup'] . ' - ' . $groupname);
		$forums->admin->print_cp_header($pagetitle, $detail);
		echo <<<JS
<script language='javascript' type='text/javascript'>
function restore(title, expand, styleid)
{
	if (confirm('{$forums->lang['reverttploriginal1']}\\n{$forums->lang['reverttploriginal2']}'))
		window.location = 'template.php?{$forums->js_sessionurl}do=edittemplatebit&type=single&title=' + title + '&expand=' + expand + '&id=' + styleid;
}
</script>
JS;
		$styleid = intval($_INPUT['id']);

		$forums->admin->print_form_header(array(
			array('do', 'doedit'),
			array('title', $_INPUT['title']),
			array('type', $_INPUT['type']),
			array('id', $styleid),
		), 'theform');

		$dir = ROOT_PATH . 'templates/';
		foreach ($titles as $name)
		{
			$current = $this->template->get_template_dir($styleid, $name . '.htm');

			if ($current === false)
			{
				$forums->admin->print_cp_error($forums->lang['nofindtemplate']);
			}
			else
			{
				if ($current == 'global')
				{
					$altered_image = $this->unaltered;
				}
				else if ($current == $forums->admin->stylecache[$styleid]['title_en'])
				{
					$altered_image = $this->altered;
				}
				else
				{
					$altered_image = $this->inherited;
				}
				$d = $dir . $current . '/';
				$templ = file_get_contents($d . $name . '.htm');
			}

			$templ = str_replace(
				array('&', '<', '>', '\n'),
				array('&#38;', '&#60;', '&#62;', '&#092;n'),
				$templ
			);

			$forums->admin->columns[] = array('&nbsp;' , '20%');
			$forums->admin->columns[] = array('&nbsp;' , '80%');
			$forums->admin->print_table_start($altered_image . $name);
			$forums->admin->print_cells_row(array(
				"<input type='button' value='" . $forums->lang['enlargeeditor'] . "' class='button' title='" . $forums->lang['enlargeeditor'] . "' onclick=\"pop_win('template.php?{$forums->sessionurl}do=floateditor&amp;id=$styleid&amp;title={$name}', 'Float{$name}', 800, 500)\" /><br /><br /><input type='button' value='" . $forums->lang['reverttemplate'] . "' class='button' title='" . $forums->lang['reverttemplatedesc'] . "' onclick='restore(\"$name\", \"{$_INPUT['expand']}\", \"$styleid\")' /><br /><br /><input type='button' value='" . $forums->lang['vieworiginaltemplate'] . "'  class='button' title='" . $forums->lang['vieworiginaltemplate'] . "' onclick='pop_win(\"template.php?{$forums->sessionurl}do=preview&amp;title=$name&amp;id=1&amp;type=text\", \"OriginalPreview\", 400,400)' />\n",
				$forums->admin->print_textarea_row("txt$name", $templ, '100', '30', 'none', "t$name")
			));

			$forums->admin->print_cells_row(array(
				"<strong>" . $forums->lang['searchintemplate'] . "</strong>",
				$forums->admin->print_input_row('string' . $name, $_INPUT['search_string']) . "<INPUT class=\"button\" accessKey=\"f\" onclick=\"findInPage('t$name', document.theform.string$name.value);\" tabIndex=\"1\" type=\"button\" value=\" " . $forums->lang['find'] . " \" /><INPUT class=button accessKey=c onclick=highlightAll(t$name); type='button' value=' " . $forums->lang['copy'] . " ' />"
			));

			unset($forums->admin->columns);
		}
		$forums->admin->print_form_submit($forums->lang['savetemplate'], '', "<input type='submit' name='savereload' value='" . $forums->lang['savereload'] . "' class='button' />\n");
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function floatededitor()
	{
		global $forums, $DB, $_INPUT;
		$forums->admin->print_popup_header();
		$forums->admin->print_form_header('', 'theform');
		$forums->admin->print_table_start($_INPUT['title']);
		$forums->admin->print_cells_single_row($forums->admin->print_textarea_row('templatebit', $html, '100', '27', 'none', 'templatebit'));
		$forums->admin->print_form_end($forums->lang['saveandreturn'], 'onclick="saveandclose();"');
		$forums->admin->print_table_footer();
		echo <<<JS
<script type="text/javascript">
var templateTitle = '{$_INPUT['title']}';
var templateBit  = eval('opener.document.theform.txt' + templateTitle + '.value');
document.theform.templatebit.value = templateBit;
function saveandclose()
{
	eval('opener.document.theform.txt' + templateTitle + '.value = document.theform.templatebit.value');
	window.close();
}
</script>
JS;
		$forums->admin->print_popup_footer();
	}

	function do_edit()
	{
		global $forums, $DB, $_INPUT;
		$titles = $cb_titles = array();
		$groupname = '';
		foreach ($_INPUT as $key => $value)
		{
			if (preg_match('/^txt([a-z0-9_]+)$/i', $key, $match))
			{
				if (isset($_INPUT[$match[0]]))
				{
					if (empty($groupname))
					{
						$groupname = $this->template->get_groupname($match[1]);
					}
					$titles[] = $match[1];
				}
			}
		}

		if (count($titles) < 1)
		{
			$forums->admin->print_cp_error($forums->lang['noselecttemplate']);
		}

		$styleid = intval($_INPUT['id']);
		$del_cache = false;
		$dir = ROOT_PATH . 'templates/' . $forums->admin->stylecache[$styleid]['title_en'] . '/';
		foreach($titles as $name)
		{
			$text = $_POST['txt' . $name];
			$text = str_replace(
				array('&#60;', '&#62;', '&#38;', '&#092;n', '\\n', "\r"),
				array('<', '>', '&', '\n', '\\\\\\n', ''),
				$text
			);

			$content = $this->template->get_template_content($styleid, $name);
			if ($content != $text)
			{
				if (!$del_cache)
				{
					$del_cache = true;
				}
				file_write($dir . $name . '.htm', $text);
			}

			if ($_INPUT['type'] == 'single' )
			{
				$_INPUT['title'] = $name;
			}
			else
			{
				$_INPUT['cb_' . $name] = 1;
			}
		}
		$type = '';
		if ($_INPUT['type'] != 'single' )
		{
			$type = 'edit';
		}

		if ($del_cache)
		{
			$this->template->delstylecache($styleid);
		}

		if (!$_INPUT['savereload'])
		{
			$forums->admin->redirect("template.php?do=edit&amp;id={$_INPUT['id']}&amp;expand={$groupname}#{$groupname}", $forums->lang['templateupdated'], $forums->lang['templateupdateddesc']);
		}
		else
		{
			$forums->main_msg = $forums->lang['templateupdated'];
			$this->edittemplatebit();
		}
	}

	function addtemplatebit()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['addnewtemplate'];
		$groupname = trim($_INPUT['expand']);
		$styleid = intval($_INPUT['id']);
		$title = $forums->admin->stylecache[$styleid]['title'];
		$forums->admin->nav[] = array("template.php?{$forums->sessionurl}do=edit&amp;id=$styleid&amp;expand={$groupname}#{$groupname}", $styles['title']);
		$forums->admin->nav[] = array('', $forums->lang['addnewtemplate'] . ' - ' . $title);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$grouptitles = $this->template->get_templates($styleid, 'groups');
		$formatted_groups = array();
		foreach ($grouptitles as $v)
		{
			$formatted_groups[] = array($v['templategroup'], $v['templategroup']);
		}
		$forums->admin->print_form_header(array(
			1 => array('do', 'doadd'),
			2 => array('id', $styleid),
			3 => array('expand', $groupname),
		) , 'theform');
		$forums->admin->columns[] = array('', '25%');
		$forums->admin->columns[] = array('', '75%');
		$forums->admin->print_table_start($forums->lang['addnewtemplate']);
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['templatename'] . '</strong><br />' . $forums->lang['templatenamedesc'],
			$forums->admin->print_input_select_row('templategroup', $formatted_groups, $groupname ? $groupname : 'global'). '_' .$forums->admin->print_input_row('title', $_INPUT['title'])
		));
		$forums->admin->print_cells_row(array(
			$forums->lang['createnewgroup'] . '<br />' . $forums->lang['createnewgroupdesc'],
			$forums->admin->print_input_row('new_templategroup', $_INPUT['new_templategroup'])
		));
		$forums->admin->print_table_footer();
		$forums->admin->print_table_start($forums->lang['addnewtemplate']);
		$forums->admin->print_cells_single_row($forums->admin->print_textarea_row("newtemplate", $_POST['newtemplate'], '100', '40', 'none'));
		$forums->admin->print_form_end($forums->lang['addnewtemplate'], '', " <input type='submit' name='savereload' value='" . $forums->lang['savereload'] . "' class='button'>");
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function do_addtemplatebit()
	{
		global $forums, $DB, $_INPUT;
		$styleid = intval($_INPUT['id']);
		$templategroup = strtolower(trim($_INPUT['new_templategroup']));
		$title = strtolower(trim($_INPUT['title']));

		if ($templategroup)
		{
			if (preg_match("#[^\w_]#s", $templategroup))
			{
				$forums->main_msg = $forums->lang['templategroupnameerror'];
				$this->addtemplatebit();
			}
		}
		$templategroup = $templategroup ? $templategroup : trim($_INPUT['templategroup']);

		if (!$title || preg_match("#[^\w_]#s", $title))
		{
			$forums->main_msg = $forums->lang['templatenameerror'];
			$this->addtemplatebit();
		}

		if (!trim($_POST['newtemplate']))
		{
			$forums->main_msg = $forums->lang['templatenotempty'];
			$this->addtemplatebit();
		}

		$text = convert_andstr($_POST['newtemplate']);
		$text = str_replace(array('&amp;nbsp;', '&amp;lt;', '&amp;gt;'), array('&nbsp;', '&lt;', '&gt;'), $text);
		$title = ($templategroup == 'global') ? $title : $templategroup . '_' . $title;

		if ($this->template->get_templates($styleid, 'all', $title))
		{
			$forums->lang['templateexist'] = sprintf($forums->lang['templateexist'], $templategroup, $title);
			$forums->main_msg = $forums->lang['templateexist'];
			$this->addtemplatebit();
		}

		$dir = ROOT_PATH . 'templates/' . $forums->admin->stylecache[$styleid]['title'] . '/';
		if (checkdir($dir, 1))
		{
			file_write($dir . $title . '.htm', $text);
			$this->template->delstylecache($styleid);
		}
		else
		{
			$forums->main_msg = $forums->lang['mkdirerror'];
			$this->addtemplatebit();
		}

		$forums->admin->redirect("style.php?do=tools", $forums->lang['assocgroupupdated'], $forums->lang['assocgroupupdateddesc']);
	}

	function removetemplatebit()
	{
		global $forums, $DB, $_INPUT;
		$titles = array();
		$styleid = intval($_INPUT['id']);
		if ($_INPUT['type'] == 'single')
		{
			$titles[] = $_INPUT['title'];
		}
		else
		{
			foreach ($_INPUT as $key => $value)
			{
				if (preg_match('/^cb_([a-z0-9_]+)$/i', $key, $match))
				{
					if (isset($_INPUT[$match[0]]))
					{
						$titles[] = $match[1];
					}
				}
			}
		}

		if (count($titles) < 1 || $styleid === 1)
		{
			$forums->admin->print_cp_error($forums->lang['noselecttemplate']);
		}

		$dir = ROOT_PATH . 'templates/' . $forums->admin->stylecache[$styleid]['title_en'] . '/';
		foreach ($titles as $name)
		{
			if ($this->template->get_template_dir($styleid, $name . '.htm', false) === false)
			{
				$forums->admin->print_cp_error($forums->lang['notdeleteorigtemplate']);
			}
			@unlink($dir . $name . '.htm');
		}
		$this->template->delstylecache($styleid);

		$expand = trim($_INPUT['expand']);
		$forums->admin->redirect("template.php?do=edit&id=$styleid&expand=$expand#$expand", $forums->lang['templatereverted'], $forums->lang['templatereverteddesc']);
	}

	function convert_tags($text = '')
	{
		if (empty($text))
		{
			return '';
		}
		return preg_replace(
			array('/{?\\$forums->sessionurl}?/', '/{?\\$forums->sessionid}?/'),
			array('{sessionurl}', '{sessionid}'),
			$text
		);
	}

	function htmlspace($m)
	{
		$result = str_replace(
			array("\t", ' ', "\r\n", "\r"),
			array('&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;', "\n", "\n"),
			$m[0]
		);
		return $result;
	}

	/**
	 * 高亮 MX 模板语法代码
	 *
	 * @param string $code 源代码
	 * @return string
	 */
	function highlight_code($code)
	{
		$code = htmlspecialchars($code);
		$regex = array(
			'/&lt;(if|for|while|foreach|elseif)=(&quot;|\')(.*?)(\\2)&gt;/i',
			'/\t*&lt;php&gt;.*?&lt;\/php&gt;/is',
			'/&lt;(#IMAGE#|else)&gt;/i',
			'/&lt;\/(if|for|while|foreach)&gt;/',
			'/\{\$[a-zA-Z0-9_\x7f-\xfe\[\]\'"\-\>]+\}/U'
		);
		$code = preg_replace($regex, '<span style="background-color:#D7FED1; color:#009900;">\\0</span>', $code);
		$code = preg_replace_callback('/>[^<]+?</',array(&$this, 'htmlspace'), $code);
		return '<pre style="line-height:18px;">'.$code.'</pre>';
	}
}

$output = new template();
$output->show();
?>