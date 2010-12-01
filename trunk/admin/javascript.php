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

class javascript
{
	function show()
	{
		global $forums, $bbuserinfo, $DB;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditjs'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->func->load_lang('admin_javascript');
		require_once(ROOT_PATH . 'includes/adminfunctions_javascript.php');
		$this->lib = new adminfunctions_javascript();
		$forums->admin->nav[] = array('javascript.php' , $forums->lang['jsmanage']);

		switch (input::get('do', ''))
		{
			case 'edit':
				$this->doform('edit');
				break;
			case 'new':
				$this->doform('new');
				break;
			case 'updatejs':
				$this->updatejs();
				break;
			case 'preview':
				$this->previewjs();
				break;
			case 'refresh':
				$this->refreshjs();
				break;
			case 'refreshall':
				$this->refreshalljs();
				break;
			case 'remove':
				$this->removejs();
				break;
			case 'cleantop':
				$this->cleantop();
				break;
			default:
				$this->jslist();
				break;
		}
	}

	function jslist()
	{
		global $forums, $DB, $bboptions;
		$pagetitle = $forums->lang['jslist'];
		$detail = "";
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'refreshall'), 2 => array('update', '1')), 'jsform', '', 'javascript.php');
		$forums->admin->columns[] = array($forums->lang['jstype'] , "10%");
		$forums->admin->columns[] = array($forums->lang['jsname'] , "10%");
		$forums->admin->columns[] = array($forums->lang['filename'] , "10%");
		$forums->admin->columns[] = array($forums->lang['nextupdate'] , "15%");
		$forums->admin->columns[] = array($forums->lang['loadtype'] , "35%");
		$forums->admin->columns[] = array($forums->lang['action'] , "20%");
		echo "<script type='text/javascript'>\n";
		echo "function js_jump(info,open)\n";
		echo "{\n";
		echo "tmp = eval('document.jsform.id' + info + '.options[document.jsform.id' + info + '.selectedIndex].value');\n";
		echo "value = tmp.split(',');\n";
		echo "if (value[0]=='remove') {\n";
		echo "okdelete = confirm('{$forums->lang['confirmdelete1']}\\n{$forums->lang['confirmdelete2']}');\n";
		echo "if ( okdelete == false ) {\n";
		echo "return false;\n";
		echo "}\n";
		echo "}\n";
		echo "if ( value[1]==1 ) {\n";
		echo "window.open('javascript.php?" . $forums->js_sessionurl . "do=' + value[0] + '&id=' + info);\n";
		echo "} else {\n";
		echo "window.location = 'javascript.php?" . $forums->js_sessionurl . "do=' + value[0] + '&id=' + info + '&type=' + value[2];\n";
		echo "}\n";
		echo "}\n";
		echo "</script>\n";
		$forums->admin->print_table_start($forums->lang['jslist']);
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "javascript ORDER BY id");
		if ($DB->num_rows())
		{
			while ($js = $DB->fetch_array())
			{
				$jscode = $js['refresh'] ? "{$bboptions['bburl']}/data/{$js['jsname']}" : "{$bboptions['bburl']}/real_js.php?id={$js['id']}";

				$jstype = $js['type'] ? $forums->lang['memberlist'] : $forums->lang['threadlist'];

				$forums->admin->print_cells_row(array("<strong>{$jstype}</strong>", "<strong>{$js['name']}</strong>", $js['jsname'], ($js['refresh'] ? $forums->func->get_date($js['nextrun'], 2) : $forums->lang['realupdate']), "<textarea name='textarea' cols='40' rows='3' readonly='readonly' onmouseover=\"this.select();\">" . utf8::htmlspecialchars("<script language='javascript' src='{$jscode}'></script>") . "</textarea>",
						$forums->admin->print_input_select_row('id' . $js['id'],
							array(0 => array('edit,0,' . $js['type'], $forums->lang['changesetting']),
								1 => array('preview,1', $forums->lang['jspreview']),
								2 => array('refresh,0', $forums->lang['jsrefresh']),
								3 => array('remove,0', $forums->lang['jsdelete']),
								), '', "onchange='js_jump(" . $js['id'] . ");'") . "<input type='button' class='button' value='" . $forums->lang['ok'] . "' onclick='js_jump(" . $js['id'] . ");' />"));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyjs'], "center");
		}
		$forums->admin->print_form_submit($forums->lang['refreshalljs']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doform($action = 'edit')
	{
		global $forums, $DB, $bboptions;
		$type = input::int('type');
		$id = input::int('id');
		if ($action == 'edit')
		{
			$pagetitle = $forums->lang['editjs'];
			$detail = $forums->lang['editjsdesc'];
			if (!$id OR !$js = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE id=" . $id . ""))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
		}
		else
		{
			$pagetitle = $forums->lang['addnewjs'];
			$detail = $forums->lang['addnewjsdesc'];
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_form_header(array(1 => array('do' , 'updatejs'), 2 => array('id' , $id), 3 => array('oldname' , $js['jsname'])), 'addjsform', '', 'javascript.php');

		echo "<script language='javascript' type='text/javascript'>
		<!--
		function Checkwild() {
			var fchecked = eval('thread_type.style.display');
			if (fchecked) {
			eval( 'thread_type.style.display=\"\"' );
			eval( 'member_type.style.display=\"none\"' );
			} else {
			eval( 'thread_type.style.display=\"none\"' );
			eval( 'member_type.style.display=\"\"' );
			}
		}
		//-->
		</script>\n";
		$forums->admin->print_table_start($pagetitle);

		if ($js['type'] OR input::get('type', ''))
		{
			$member_type = "";
			$thread_type = "none";
		}
		else
		{
			$member_type = "none";
			$thread_type = "";
		}

		$type = input::get('type', $js['type']);

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jstype']}</strong>",
				$forums->admin->print_input_select_row("type", array(0 => array(0, $forums->lang['threadlist']),
						1 => array(1, $forums->lang['memberlist']),
						), $type, " onchange='Checkwild()'")));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jstitle']}</strong>", $forums->admin->print_input_row("name", input::get('name', $js['name']))));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jsdescription']}</strong>", $forums->admin->print_textarea_row("description", input::get('description', $js['description']))));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jsfilename']}</strong><div class='description'>{$forums->lang['jsfilenamedesc']}</div>", $bboptions['bburl'] . "/data/" . $forums->admin->print_input_row("jsname", input::get('jsname', $js['jsname']))));

		$forums->admin->print_table_footer();

		echo "<div id='thread_type' style='display:{$thread_type}'>";
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['jssettings']);
		$catelist[] = array('-1', $forums->lang['allforums']);
		$allforum = $forums->adminforum->forumcache;
		foreach($allforum AS $forum)
		{
			$catelist[] = array($forum[id], depth_mark($forum['depth'], '--') . $forum[name]);
		}
		$inids = input::get('inids', explode(",", $js['inids']));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['inforums']}</strong><div class='description'>{$forums->lang['inforumsdesc']}</div>", $forums->admin->print_multiple_select_row('inids[]', $catelist, $inids ? $inids : array(-1))));

		$numbers = input::get('numbers', $js['numbers']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jslistnums']}</strong><div class='description'>{$forums->lang['jslistnumsdesc']}</div>", $forums->admin->print_input_row("numbers", $numbers ? $numbers : 5)));

		$perline = input::get('perline', $js['perline']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jsperline']}</strong><div class='description'>{$forums->lang['jsperlinedesc']}</div>", $forums->admin->print_input_row("perline", $perline ? $perline : 1)));

		$selecttype = input::get('selecttype', $js['selecttype']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['selecttype']}</strong><div class='description'>{$forums->lang['selecttypedesc']}</div>", $forums->admin->print_input_select_row("selecttype", array(array(0, $forums->lang['newthread']), array(1, $forums->lang['quinthread'])), $selecttype)));

		$daylimit = input::get('daylimit', $js['daylimit']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['daylimit']}</strong><div class='description'>{$forums->lang['daylimitdesc']}</div>", $forums->admin->print_input_row("daylimit", $daylimit ? $daylimit : 0)));

		$orderby = input::get('orderby', $js['orderby']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['orderby']}</strong><div class='description'>{$forums->lang['orderbydesc']}</div>", $forums->admin->print_input_select_row("orderby", array(0 => array(0, $forums->lang['threadtime']),
						1 => array(1, $forums->lang['threadids']),
						2 => array(2, $forums->lang['posts']),
						3 => array(3, $forums->lang['views']),
						4 => array(4, $forums->lang['posttime']),
						), $orderby)));

		$trimtitle = input::get('trimtitle', $js['trimtitle']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['trimtitle']}</strong><div class='description'>{$forums->lang['trimtitledesc']}</div>", $forums->admin->print_input_row("trimtitle", $trimtitle ? $trimtitle : 50)));

		$trimdesc = input::get('trimdescription', $js['trimdescription']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['trimdesc']}</strong><div class='description'>{$forums->lang['trimdescdesc']}</div>", $forums->admin->print_input_row("trimdescription", $trimdesc ? $trimdesc : 50)));

		$trimpagetext = input::get('trimpagetext', $js['trimpagetext']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['trimpagetext']}</strong><div class='description'>{$forums->lang['trimpagetextdesc']}</div>", $forums->admin->print_input_row("trimpagetext", $trimpagetext ? $trimpagetext : -1)));

		$refresh = input::get('refresh', $js['refresh']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['refreshtime']}</strong><div class='description'>{$forums->lang['refreshtimedesc']}</div>", sprintf($forums->lang['setrefreshtime'], $forums->admin->print_input_row("refresh", (isset($refresh) ? $refresh : 10), "", "", 5))));

		$export = input::get('export', $js['export']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['export']}</strong><div class='description'>{$forums->lang['exportdesc']}</div>", $forums->admin->print_input_select_row("export", array(array(0, "UTF-8 ({$forums->lang['nochange']})"), array(1, "GB2312"), array(2, "BIG5")), $export)));

		$htmlcode = input::get('htmlcode', $js['htmlcode']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['htmlcode']}</strong><div class='description'>{$forums->lang['htmlcodedesc']}</div>", $forums->admin->print_textarea_row("htmlcode", $htmlcode, '', 15)));

		$forums->admin->print_table_footer();

		echo "</div>";

		echo "<div id='member_type' style='display:{$member_type}'>";
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['jssettings']);

		$m_numbers = input::get('m_numbers', $js['numbers']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jslistnums']}</strong><div class='description'>{$forums->lang['jslistnumsdesc']}</div>", $forums->admin->print_input_row("m_numbers", $m_numbers ? $m_numbers : 5)));

		$m_perline = input::get('m_perline', $js['perline']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['jsperline']}</strong><div class='description'>{$forums->lang['jsperlinedesc']}</div>", $forums->admin->print_input_row("m_perline", $m_perline ? $m_perline : 1)));

		$s_type[] = array(0, $forums->lang['type_posts']);
		$s_type[] = array(1, $forums->lang['type_money']);
		$s_type[] = array(2, $forums->lang['type_reputation']);
		$s_type[] = array(3, $forums->lang['type_joindate']);
		$forums->func->check_cache('creditlist');
		if (is_array($forums->cache['creditlist']))
		{
			foreach ($forums->cache['creditlist'] AS $k => $v)
			{
				$s_type[] = array($v['tag'], sprintf($forums->lang['type_credit'], $v['name']));
				$c_ex_desc .= "<br />{{$v['tag']}} => {$v['name']}";
			}
		}
		$m_selecttype = input::get('m_selecttype', $js['selecttype']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['selecttype']}</strong><div class='description'>{$forums->lang['selecttypedesc']}</div>", $forums->admin->print_input_select_row("m_selecttype", $s_type, $m_selecttype)));

		$m_order = input::get('m_order', $js['orderby']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['orderby']}</strong><div class='description'>{$forums->lang['orderbydesc']}</div>", $forums->admin->print_input_select_row("m_order", array(0 => array(0, $forums->lang['ascending']),
						1 => array(1, $forums->lang['descending']),
						), $m_order)));

		$m_refresh = input::get('m_refresh', $js['refresh']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['refreshtime']}</strong><div class='description'>{$forums->lang['refreshtimedesc']}</div>", sprintf($forums->lang['setrefreshtime'], $forums->admin->print_input_row("m_refresh", (isset($m_refresh) ? $m_refresh : 10), "", "", 5))));

		$m_export = input::get('m_export', $js['export']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['export']}</strong><div class='description'>{$forums->lang['exportdesc']}</div>", $forums->admin->print_input_select_row("m_export", array(array(0, "UTF-8 ({$forums->lang['nochange']})"), array(1, "GB2312"), array(2, "BIG5")), $export)));

		$m_htmlcode = input::get('m_htmlcode', $js['htmlcode']);
		$forums->lang['mhtmlcodedesc'] = sprintf($forums->lang['mhtmlcodedesc'], $c_ex_desc);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['htmlcode']}</strong><div class='description'>{$forums->lang['mhtmlcodedesc']}</div>", $forums->admin->print_textarea_row("m_htmlcode", $m_htmlcode, '', 15)));
		$forums->admin->print_table_footer();
		echo "</div>";
		$forums->admin->print_table_start();
		$forums->admin->print_form_end($pagetitle, "", " <input type='submit' name='preview' value='{$forums->lang['jspreview']}' id='button'>");
		$forums->admin->print_cp_footer();
	}

	function previewjs()
	{
		global $forums, $DB, $bboptions;
		$id = input::int('id');
		if (!$id OR !$js = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE id=" . $id . ""))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$pagetitle = $forums->lang['jspreview'];
		$detail = sprintf($forums->lang['jspreviewdesc'], $js['jsname']);

		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_table_start($pagetitle);
		$preview = $this->preview($js);
		$forums->admin->print_cells_single_row($preview, 'left');
		$forums->admin->print_table_footer();

		$forums->admin->print_cp_footer();
	}

	function refreshjs()
	{
		global $forums, $DB, $bboptions;
		$id = input::int('id');
		$js = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE id=" . $id . "");
		if (!$js['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$this->lib->createjs($js, 1);
		$forums->func->recache('realjs');
		$forums->admin->redirect("javascript.php", $forums->lang['jsmanage'], $forums->lang['jsrefreshed']);
	}

	function refreshalljs()
	{
		global $forums, $DB, $bboptions;
		$id = input::int('id');
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "javascript");
		if ($DB->num_rows())
		{
			while ($js = $DB->fetch_array())
			{
				$this->lib->createjs($js, 1);
				if ($js['refresh'] > 0)
				{
					$nextrun = TIMENOW + $js['refresh'] * 60;
					if ($cron_time['nextrun'] > $nextrun)
					{
						$cron_time['nextrun'] = $nextrun;
						$update_db = true;
					}
					if (!$next_do_cron)
					{
						$next_do_cron = $nextrun;
					}
					$next_do_cron = ($nextrun < $next_do_cron) ? $nextrun : $next_do_cron;
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "javascript SET nextrun='" . $next_do_cron . "' WHERE id = " . $js['id'] . "");
				}
			}
			$forums->func->recache('realjs');
		}
		else
		{
			$forums->admin->print_cp_error($forums->lang['noneedupdated']);
		}
		$forums->admin->redirect("javascript.php", $forums->lang['jsmanage'], $forums->lang['jsrefreshed']);
	}

	function removejs()
	{
		global $forums, $DB, $bboptions;
		$id = input::int('id');
		$js = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE id=" . $id . "");
		if (!$js['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "javascript WHERE id=" . $id . "");
		@unlink(ROOT_PATH . 'data/' . $js['jsname']);
		$forums->func->recache('realjs');
		$forums->admin->redirect("javascript.php", $forums->lang['jsmanage'], $forums->lang['jsdeleted']);
	}

	function updatejs()
	{
		global $forums, $DB;
		$type = input::int('type');
		$id = input::int('id');
		$oldname = trim(input::str('oldname'));
		$name = trim(input::str('name'));
		$description = trim(input::str('description'));
		$jsname = trim(input::str('jsname'));
		$inids = input::get('inids', '');
		$numbers = input::get('type', '') ? input::int('m_numbers') : input::int('numbers');
		$perline = input::get('type', '') ? input::int('m_perline') : input::int('perline');
		$selecttype = input::get('type', '') ? trim(input::str('m_selecttype')) : trim(input::str('selecttype'));
		$daylimit = input::int('daylimit');
		$orderby = input::get('type', '') ? input::int('m_order') : input::int('orderby');
		$trimtitle = input::int('trimtitle');
		$trimdescription = input::int('trimdescription');
		$trimpagetext = input::int('trimpagetext');
		$refresh = input::get('type', '') ? input::int('m_refresh') : input::int('refresh');
		$export = input::get('type', '') ? input::int('m_export') : input::int('export');
		$htmlcode = input::get('type', '') ? trim(convert_andstr($_POST['m_htmlcode'])) : trim(convert_andstr($_POST['htmlcode']));

		if ($refresh > 0)
		{
			$nextrun = TIMENOW + $refresh * 60;
		}
		else
		{
			$nextrun = -1;
		}

		if (!$htmlcode)
		{
			$forums->main_msg = $forums->lang['requirehtmlcode'];
			$this->doform();
			exit;
		}

		if (!is_array($inids))
		{
			$inids[] = "-1";
		}

		$thisids = array();
		if (in_array('-1', $inids))
		{
			$thisids[] = '-1';
		}
		else
		{
			$allforum = $forums->adminforum->forumcache;
			foreach($allforum AS $forum)
			{
				if (in_array($forum['id'], $inids))
				{
					$thisids[] = $forum['id'];
					$child = explode(",", $forum['childlist']);
					foreach($child AS $k)
					{
						$k = trim($k);
						if ($k == '-1' OR !$k) continue;
						if (!in_array($k, $thisids))
						{
							$thisids[] = $k;
						}
					}
				}
			}
		}
		$js = array("type" => $type,
			"name" => $name,
			"description" => $description,
			"jsname" => $jsname,
			"inids" => implode(",", $thisids),
			"numbers" => $numbers,
			"perline" => $perline,
			"selecttype" => $selecttype,
			"daylimit" => $daylimit,
			"orderby" => $orderby,
			"trimtitle" => $trimtitle,
			"trimdescription" => $trimdescription,
			"trimpagetext" => $trimpagetext,
			"refresh" => $refresh,
			"export" => $export,
			"htmlcode" => $htmlcode,
			"nextrun" => $nextrun,
			);
		if (input::str('preview'))
		{
			$preview = $this->preview($js);
			$forums->main_msg = $preview;
			$action = $id ? "edit" : "new";
			$this->doform($action);
			exit;
		}

		if ($forums->cache['cron'] > $nextrun AND $refresh > 0)
		{
			$forums->func->update_cache(array('name' => 'cron', 'value' => $nextrun, 'array' => 0));
		}
		$old_next = $DB->query_first("SELECT nextrun FROM " . TABLE_PREFIX . "cron WHERE filename = 'refreshjs.php'");
		if ($old_next['nextrun'] > $nextrun AND $refresh != 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "cron SET nextrun='{$nextrun}' WHERE filename = 'refreshjs.php'");
		}

		if ($id)
		{
			$DB->update(TABLE_PREFIX . 'javascript', $js, "id=$id");
		}
		else
		{
			$DB->insert(TABLE_PREFIX . 'javascript', $js);
		}
		$this->lib->createjs($js, 1, $oldname);
		$forums->func->recache('realjs');
		$forums->admin->redirect("javascript.php", $forums->lang['jsmanage'], $forums->lang['jsupdated']);
	}

	function preview($js = array())
	{
		$createjs = $this->lib->createjs($js, 0);
		return "<script type='text/javascript'><!--\n" . $createjs . "//--></script>\n";
	}
}

$output = new javascript();
$output->show();

?>