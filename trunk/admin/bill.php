<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
require ('./global.php');

class adminlog
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditads'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->func->load_lang('admin_bill');
		$forums->admin->nav[] = array("bill.php", $forums->lang['admanage']);
		$this->allforum = $forums->adminforum->forumcache;
		switch ($_INPUT['do'])
		{
			case 'step1':
				$this->step1();
				break;
			case 'finish':
				$this->finish();
				break;
			case 'add':
				$this->add();
				break;
			case 'edit':
				$this->step1();
				break;
			case 'remove':
				$this->deletead();
				break;
			case 'reorder':
				$this->reorder();
				break;
			default:
				$this->adlist();
				break;
		}
	}

	function add()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['addad'];
		$detail = $forums->lang['addnewaddesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_form_header(array(1 => array('do', 'step1')));
		$forums->admin->print_table_start($forums->lang['addnewad']);

		$types = array(array('header', $forums->lang['type_headers']),
			array('footer', $forums->lang['type_footers']),
			array('thread', $forums->lang['type_threads']),
			array('post', $forums->lang['type_posts']),
			array('postfooter', $forums->lang['type_postsfooter']),
			);

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['select_ad_type']}</strong>",
				$forums->admin->print_input_select_row('type', $types)));
		$forums->admin->print_form_submit($forums->lang['next']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function step1()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'])
		{
			$ad = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "ad WHERE id = " . intval($_INPUT['id']) . "");
			if (!$ad['id'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$_INPUT['type'] = $ad['type'];
			$code = unserialize($ad['code']);
			$list = explode(",", $ad['ad_in']);
			$starttime = $ad['starttime'] ? $forums->func->get_date($ad['starttime'], 2) : 0;
			$endtime = $ad['endtime'] ? $forums->func->get_date($ad['endtime'], 2) : 0;
		}
		if ($_INPUT['type'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['no_select_type']);
		}
		$pagetitle = $forums->lang['addnewad'];
		$detail = $forums->lang['addnewaddesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");

		echo "<script language='javascript' type='text/javascript'>
		<!--
		function Checkwild() {
			var fchecked = eval('cpform.codetype.value');
			if (fchecked == 0) {
			eval( 'code_type.style.display=\"\"' );
			eval( 'text_type.style.display=\"none\"' );
			eval( 'image_type.style.display=\"none\"' );
			eval( 'flash_type.style.display=\"none\"' );
			} else if (fchecked == 1) {
			eval( 'code_type.style.display=\"none\"' );
			eval( 'text_type.style.display=\"\"' );
			eval( 'image_type.style.display=\"none\"' );
			eval( 'flash_type.style.display=\"none\"' );
			} else if (fchecked == 2) {
			eval( 'code_type.style.display=\"none\"' );
			eval( 'text_type.style.display=\"none\"' );
			eval( 'image_type.style.display=\"\"' );
			eval( 'flash_type.style.display=\"none\"' );
			} else if (fchecked == 3) {
			eval( 'code_type.style.display=\"none\"' );
			eval( 'text_type.style.display=\"none\"' );
			eval( 'image_type.style.display=\"none\"' );
			eval( 'flash_type.style.display=\"\"' );
			}
		}
		//-->
		</script>\n";

		$forums->admin->print_form_header(array(1 => array('do', 'finish'), 2 => array('type', $_INPUT['type']), 3 => array('id', $ad['id'])));
		$forums->admin->print_table_start($forums->lang['addnewad']);

		$types = array('header' => $forums->lang['type_headers'],
			'footer' => $forums->lang['type_footers'],
			'thread' => $forums->lang['type_threads'],
			'post' => $forums->lang['type_posts'],
			'postfooter' => $forums->lang['type_postsfooter'],
			);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['select_ad_type']}</strong>", $types[$_INPUT['type']]));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['ad_name']}</strong>",
				$forums->admin->print_input_row('name', $ad['name'])));

		$forum_list[] = array('-1' , $forums->lang['allpages']);
		$forum_list[] = array('0' , $forums->lang['index']);
		foreach($this->allforum AS $key => $value)
		{
			$forum_list[] = array($value['id'], depth_mark($value['depth'], '--') . $value['name']);
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['useinforum'] . "</strong><div class='description'>" . $forums->lang['useinforumdesc'] . "</div>", $forums->admin->print_multiple_select_row("ad_in[]", $forum_list, $list, 5)));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['starttime']}</strong><div class='description'>" . $forums->lang['timedesc'] . "</div>",
				$forums->admin->print_input_row('starttime', $starttime)));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['endtime']}</strong><div class='description'>" . $forums->lang['timedesc'] . "</div>",
				$forums->admin->print_input_row('endtime', $endtime)));

		$forums->admin->print_cells_row(array("<strong>{$forums->lang['codetype']}</strong>",
				$forums->admin->print_input_select_row("codetype", array(0 => array(0, $forums->lang['code']),
						array(1, $forums->lang['text']),
						array(2, $forums->lang['image']),
						array(3, $forums->lang['flash']),
						), $ad['codetype'], " onchange='Checkwild()'")));
		$forums->admin->print_table_footer();

		$code_type = "none";
		$text_type = "none";
		$image_type = "none";
		$flash_type = "none";

		switch ($ad['codetype'])
		{
			case 0;
				$code_type = "";
				break;
			case 1:
				$text_type = "";
				break;
			case 2:
				$image_type = "";
				break;
			case 3:
				$flash_type = "";
				break;
			default:
				$code_type = "";
				break;
		}

		echo "<div id='code_type' style='display:{$code_type}'>";
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($forums->lang['code']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['htmlcode']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>", $forums->admin->print_textarea_row("code", $code['code'])));
		$forums->admin->print_table_footer();
		echo "</div>";

		echo "<div id='text_type' style='display:{$text_type}'>";
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($forums->lang['text']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['text_title']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>",
				$forums->admin->print_input_row('text_title', $code['text_title'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['text_url']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>",
				$forums->admin->print_input_row('text_url', $code['text_url'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['text_desc']}</strong>",
				$forums->admin->print_input_row('text_desc', $code['text_desc'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['text_style']}</strong>",
				$forums->admin->print_input_row('text_style', $code['text_style'])));
		$forums->admin->print_table_footer();
		echo "</div>";

		echo "<div id='image_type' style='display:{$image_type}'>";
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($forums->lang['image']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['image_title']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>",
				$forums->admin->print_input_row('image_title', $code['image_title'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['image_url']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>",
				$forums->admin->print_input_row('image_url', $code['image_url'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['image_desc']}</strong>",
				$forums->admin->print_input_row('image_desc', $code['image_desc'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['image_width']}</strong>",
				$forums->admin->print_input_row('image_width', $code['image_width'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['image_height']}</strong>",
				$forums->admin->print_input_row('image_height', $code['image_height'])));
		$forums->admin->print_table_footer();
		echo "</div>";

		echo "<div id='flash_type' style='display:{$flash_type}'>";
		$forums->admin->columns[] = array("", "40%");
		$forums->admin->columns[] = array("", "60%");
		$forums->admin->print_table_start($forums->lang['flash']);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['flash_url']}</strong><div class='description'>" . $forums->lang['requireed'] . "</div>",
				$forums->admin->print_input_row('flash_url', $code['flash_url'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['flash_width']}</strong>",
				$forums->admin->print_input_row('flash_width', $code['flash_width'])));
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['flash_height']}</strong>",
				$forums->admin->print_input_row('flash_height', $code['flash_height'])));
		$forums->admin->print_table_footer();
		echo "</div>";

		$forums->admin->print_form_end_standalone($forums->lang['finish']);
		$forums->admin->print_cp_footer();
	}

	function finish()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		$_INPUT['type'] = trim($_INPUT['type']);
		$_INPUT['name'] = trim($_INPUT['name']);
		$_INPUT['starttime'] = trim($_INPUT['starttime']);
		$_INPUT['endtime'] = trim($_INPUT['endtime']);
		$_INPUT['codetype'] = intval($_INPUT['codetype']);
		$_POST['code'] = trim(convert_andstr($_POST['code']));
		$_INPUT['text_title'] = trim($_INPUT['text_title']);
		$_INPUT['text_url'] = trim($_INPUT['text_url']);
		$_INPUT['text_desc'] = trim($_INPUT['text_desc']);
		$_INPUT['text_style'] = trim($_INPUT['text_style']);
		$_INPUT['image_title'] = trim($_INPUT['image_title']);
		$_INPUT['image_url'] = trim($_INPUT['image_url']);
		$_INPUT['image_desc'] = trim($_INPUT['image_desc']);
		$_INPUT['image_width'] = intval($_INPUT['image_width']);
		$_INPUT['image_height'] = intval($_INPUT['image_height']);
		$_INPUT['flash_url'] = trim($_INPUT['flash_url']);
		$_INPUT['flash_width'] = intval($_INPUT['flash_width']);
		$_INPUT['flash_height'] = intval($_INPUT['flash_height']);

		if ($_INPUT['type'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['no_select_type']);
		}
		if ($_INPUT['name'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['no_select_name']);
		}
		if (!is_array($_INPUT['ad_in']))
		{
			$forums->admin->print_cp_error($forums->lang['no_select_forumlist']);
		}
		$start = explode(" ", $_INPUT['starttime']);
		if (!$start[0])
		{
			$starttime = TIMENOW;
		}
		else
		{
			$date = explode("-", $start[0]);
			$time = explode(":", $start[1]);
			$starttime = $forums->func->mk_time($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
		}
		$end = explode(" ", $_INPUT['endtime']);
		if (!$end[0])
		{
			$endtime = 0;
		}
		else
		{
			$date = explode("-", $end[0]);
			$time = explode(":", $end[1]);
			$endtime = $forums->func->mk_time($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
		}
		switch ($_INPUT['codetype'])
		{
			case 0:
				if ($_POST['code'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_code']);
				}
				$code = array("code" => $_POST['code']
					);
				$htmlcode = $_POST['code'];
				break;
			case 1:
				if ($_INPUT['text_title'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_text_title']);
				}
				if ($_INPUT['text_url'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_text_url']);
				}
				$code = array("text_title" => $_INPUT['text_title'],
					"text_url" => $_INPUT['text_url'],
					"text_desc" => $_INPUT['text_desc'],
					"text_style" => $_INPUT['text_style'],
					);
				$style = $_INPUT['text_style'] ? " style='{$_INPUT['text_style']}'" : "";
				$htmlcode = "<a href='click.php?id={$_INPUT['id']}&amp;url=" . urlencode($_INPUT['text_url']) . "' title='{$_INPUT['text_desc']}' target='_blank'{$style}>{$_INPUT['text_title']}</a>";
				break;
			case 2:
				if ($_INPUT['image_title'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_image_title']);
				}
				if ($_INPUT['image_url'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_image_url']);
				}
				$code = array("image_title" => $_INPUT['image_title'],
					"image_url" => $_INPUT['image_url'],
					"image_desc" => $_INPUT['image_desc'],
					"image_width" => $_INPUT['image_width'],
					"image_height" => $_INPUT['image_height'],
					);
				$image_width = $_INPUT['image_width'] ? " width='{$_INPUT['image_width']}'" : "";
				$image_height = $_INPUT['image_height'] ? " height='{$_INPUT['image_height']}'" : "";
				$htmlcode = "<a href='click.php?id={$_INPUT['id']}&amp;url=" . urlencode($_INPUT['image_url']) . "' target='_blank'><img src='{$_INPUT['image_title']}' border='0' alt='{$_INPUT['image_desc']}'{$image_width}{$image_height} /></a>";
				break;
			case 3:
				if ($_INPUT['flash_url'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_url']);
				}
				if ($_INPUT['flash_width'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_width']);
				}
				if ($_INPUT['flash_height'] == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_height']);
				}
				$code = array(
					"flash_url" => $_INPUT['flash_url'],
					"flash_width" => $_INPUT['flash_width'],
					"flash_height" => $_INPUT['flash_height'],
				);
				$htmlcode = "<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' width='{$_INPUT['flash_width']}' height='{$_INPUT['flash_height']}'><param name='movie' value='{$_INPUT['flash_url']}' /><param name='play' value='true' /><param name='loop' value='true' /><param name='quality' value='high' /><embed src='{$_INPUT['flash_url']}' width='{$_INPUT['flash_width']}' height='{$_INPUT['flash_height']}' play='true' loop='true' quality='high'></embed></object>";
				break;
		}
		$array = array(
			'type' => $_INPUT['type'],
			'name' => $_INPUT['name'],
			'code' => '',
			'htmlcode' => '',
		);
		if (!$_INPUT['id'])
		{
			$DB->insert(TABLE_PREFIX . 'ad', $array);
			$_INPUT['id'] = $DB->insert_id();
		}

		if (in_array('-1', $_INPUT['ad_in']))
		{
			$_INPUT['ad_in'] = array(-1);
		}

		$array = array(
			'type' => $_INPUT['type'],
			'name' => $_INPUT['name'],
			'ad_in' => implode(',', $_INPUT['ad_in']),
			'starttime' => $starttime,
			'endtime' => $endtime,
			'codetype' => $_INPUT['codetype'],
			'code' => serialize($code),
			'htmlcode' => $htmlcode,
		);
		if ($_INPUT['id'])
		{
			$DB->update(TABLE_PREFIX . 'ad', $array, 'id=' . $_INPUT['id']);
		}
		else
		{
			$DB->insert(TABLE_PREFIX . 'ad', $array);
		}
		$forums->func->recache('ad');
		$forums->admin->redirect("bill.php", $forums->lang['admanage'], $forums->lang['adupdated']);
	}

	function adlist()
	{
		global $forums, $_INPUT, $DB;
		$pagetitle = $forums->lang['admanage'];
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(1 => array('do' , 'reorder')));
		echo "<script type='text/javascript'>\n";
		echo "function js_jump(adinfo)\n";
		echo "{\n";
		echo "value = eval('document.cpform.id' + adinfo + '.options[document.cpform.id' + adinfo + '.selectedIndex].value');\n";
		echo "if (value=='remove') {\n";
		echo "okdelete = confirm('" . $forums->lang['wantdeletead'] . "');\n";
		echo "if ( okdelete == false ) {\n";
		echo "return false;\n";
		echo "}\n";
		echo "}\n";
		echo "window.location = 'bill.php?{$forums->js_sessionurl}&do=' + value + '&id=' + adinfo;\n";
		echo "}\n";
		echo "</script>\n";
		$forums->admin->columns[] = array($forums->lang['ad_name'], "20%");
		$forums->admin->columns[] = array($forums->lang['ad_type'], "5%");
		$forums->admin->columns[] = array($forums->lang['ad_codetype'] , "5%");
		$forums->admin->columns[] = array($forums->lang['ad_start'] , "10%");
		$forums->admin->columns[] = array($forums->lang['ad_end'] , "10%");
		$forums->admin->columns[] = array($forums->lang['ad_click'] , "5%");
		$forums->admin->columns[] = array($forums->lang['ad_in'] , "15%");
		$forums->admin->columns[] = array($forums->lang['action'] , "20%");
		$forums->admin->columns[] = array($forums->lang['displayorder'], "5%");
		$forums->admin->print_table_start($forums->lang['admanage']);
		$nodisplay = true;
		$imgsite = true;
		$textsite = true;
		$linesite = true;

		$types = array('header' => $forums->lang['type_headers'],
			'footer' => $forums->lang['type_footers'],
			'thread' => $forums->lang['type_threads'],
			'post' => $forums->lang['type_posts'],
			);

		$codetypes = array('0' => $forums->lang['code'],
			'1' => $forums->lang['text'],
			'2' => $forums->lang['image'],
			'3' => $forums->lang['flash'],
			);

		$ads = $DB->query("SELECT * FROM " . TABLE_PREFIX . "ad ORDER BY type, displayorder");
		if ($DB->num_rows($ads))
		{
			while ($ad = $DB->fetch_array($ads))
			{
				if ($linesite AND $ad['type'] == 'header')
				{
					$forums->admin->print_cells_single_row($forums->lang['type_headers'], "left", "pformstrip");
					$linesite = false;
				}
				if ($imgsite AND $ad['type'] == 'footer')
				{
					$forums->admin->print_cells_single_row($forums->lang['type_footers'], "left", "pformstrip");
					$imgsite = false;
				}
				if ($textsite AND $ad['type'] == 'thread')
				{
					$forums->admin->print_cells_single_row($forums->lang['type_threads'], "left", "pformstrip");
					$textsite = false;
				}
				if ($nodisplay AND $ad['type'] == 'post')
				{
					$forums->admin->print_cells_single_row($forums->lang['type_posts'], "left", "pformstrip");
					$nodisplay = false;
				}
				$ad_in = explode(",", $ad['ad_in']);
				if (in_array("-1", $ad_in))
				{
					$ad_where = $forums->lang['allpages'];
				}
				else
				{
					$ad_where = array();
					if (in_array("0", $ad_in))
					{
						$ad_where[] = $forums->lang['index'];
					}
					foreach($ad_in AS $fid)
					{
						if ($this->allforum[$fid]['id'])
						{
							$ad_where[] = "<a href='../forumdisplay.php?f=" . $this->allforum[$fid]['id'] . "' target='_blank'>" . $this->allforum[$fid]['name'] . "</a>";
						}
					}
					$ad_where = implode("<br />", $ad_where);
				}
				if ($ad['codetype'] == 0 OR $ad['codetype'] == 3)
				{
					$ad_click = $forums->lang['nocount'];
				}
				else
				{
					$ad_click = $ad['click'] ? fetch_number_format($ad['click']) : $forums->lang['noclick'];
				}
				$forums->admin->print_cells_row(array("<a href='bill.php?{$forums->sessionurl}do=edit&amp;id={$ad['id']}' target='_blank' title=''><strong>{$ad['name']}</strong></a>",
						$types[$ad['type']],
						$codetypes[$ad['codetype']],
						$forums->func->get_date($ad['starttime'], 2),
						$ad['endtime'] ? $forums->func->get_date($ad['endtime'], 2) : $forums->lang['always_show'],
						$ad_click,
						$ad_where,
						$forums->admin->print_input_select_row('id' . $ad['id'],
							array(0 => array('edit', $forums->lang['editad']),
								1 => array('remove', $forums->lang['deletead'])
								), '', "onchange='js_jump(" . $ad['id'] . ");'") . "<input type='button' class='button' value='" . $forums->lang['ok'] . "' onclick='js_jump(" . $ad['id'] . ");' />",
						$forums->admin->print_input_row("order[" . $ad['id'] . "]", $ad['displayorder'], "", "", 5)
						));
			}
		}
		$forums->admin->print_form_submit($forums->lang['reorder'], '', " " . $forums->admin->print_button($forums->lang['addad'], "bill.php?{$forums->sessionurl}do=add"));
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function deletead()
	{
		global $forums, $DB, $_INPUT;
		$ad = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "ad WHERE id = " . intval($_INPUT['id']) . "");
		if (!$ad['id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "ad WHERE id={$ad['id']}");
		$forums->func->recache('ad');
		$forums->admin->redirect("bill.php", $forums->lang['admanage'], $forums->lang['adupdated']);
	}

	function reorder()
	{
		global $forums, $_INPUT, $DB;
		if (is_array($_INPUT['order']))
		{
			$ads = $DB->query("SELECT id,displayorder FROM " . TABLE_PREFIX . "ad");
			while ($ad = $DB->fetch_array($ads))
			{
				if (!isset($_INPUT['order'][$ad['id']]))
				{
					continue;
				}
				$displayorder = intval($_INPUT['order'][$ad['id']]);
				if ($ad['displayorder'] != $displayorder)
				{
					$DB->update(TABLE_PREFIX . 'ad', array('displayorder' => $displayorder), 'id = ' . $ad['id']);
				}
			}
		}
		$forums->func->recache('ad');
		$forums->admin->redirect("bill.php", $forums->lang['admanage'], $forums->lang['adordered']);
	}
}

$output = new adminlog();
$output->show();

?>