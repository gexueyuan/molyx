<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
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
		global $forums, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditads'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->func->load_lang('admin_bill');
		$forums->admin->nav[] = array("bill.php", $forums->lang['admanage']);
		$this->allforum = $forums->adminforum->forumcache;
		switch (input::get('do', ''))
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
		global $forums, $DB;
		if (input::str('id'))
		{
			$ad = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "ad WHERE id = " . input::int('id') . "");
			if (!$ad['id'])
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			input::set('type', $ad['type']);
			$code = unserialize($ad['code']);
			$list = explode(",", $ad['ad_in']);
			$starttime = $ad['starttime'] ? $forums->func->get_date($ad['starttime'], 2) : 0;
			$endtime = $ad['endtime'] ? $forums->func->get_date($ad['endtime'], 2) : 0;
		}
		if (!input::str('type'))
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

		$forums->admin->print_form_header(array(1 => array('do', 'finish'), 2 => array('type', input::str('type')), 3 => array('id', $ad['id'])));
		$forums->admin->print_table_start($forums->lang['addnewad']);

		$types = array('header' => $forums->lang['type_headers'],
			'footer' => $forums->lang['type_footers'],
			'thread' => $forums->lang['type_threads'],
			'post' => $forums->lang['type_posts'],
			'postfooter' => $forums->lang['type_postsfooter'],
			);
		$forums->admin->print_cells_row(array("<strong>{$forums->lang['select_ad_type']}</strong>", $types[input::str('type')]));

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
		global $forums, $DB;
		
		input::set('id', input::int('id'));
		input::set('type', trim(input::str('type')));
		input::set('name', trim(input::str('name')));
		input::set('starttime', trim(input::str('starttime')));
		input::set('endtime', trim(input::str('endtime')));
		input::set('codetype', input::int('codetype'));
		input::set('code', trim(input::str('code', '', false)));
		input::set('text_title', trim(input::str('text_title')));
		input::set('text_url', trim(input::str('text_url')));
		input::set('text_desc', trim(input::str('text_desc')));
		input::set('text_style', trim(input::str('text_style')));
		input::set('image_title', trim(input::str('image_title')));
		input::set('image_url', trim(input::str('image_url')));
		input::set('image_desc', trim(input::str('image_desc')));
		input::set('image_width', input::int('image_width'));
		input::set('image_height', input::int('image_height'));
		input::set('flash_url', trim(input::str('flash_url')));
		input::set('flash_width', input::int('flash_width'));
		input::set('flash_height', input::int('flash_height'));

		if (input::str('type') == "")
		{
			$forums->admin->print_cp_error($forums->lang['no_select_type']);
		}
		if (input::str('name') == "")
		{
			$forums->admin->print_cp_error($forums->lang['no_select_name']);
		}
		if (empty(input::str('ad_in', array())))
		{
			$forums->admin->print_cp_error($forums->lang['no_select_forumlist']);
		}
		$start = explode(" ", input::str('starttime'));
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
		$end = explode(" ", input::str('endtime'));
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
		switch (input::str('codetype'))
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
				if (input::str('text_title') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_text_title']);
				}
				if (input::str('text_url') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_text_url']);
				}
				$code = array("text_title" => input::get('text_title', ''),
					"text_url" => input::get('text_url', ''),
					"text_desc" => input::get('text_desc', ''),
					"text_style" => input::get('text_style', ''),
					);
				$style = input::get('text_style', '') ? " style='" . input::get('text_style', '') . "'" : "";
				$htmlcode = "<a href='click.php?id=" . input::get('id', '') . "&amp;url=" . urlencode(input::str('text_url')) . "' title='" . input::get('text_desc', '') . "' target='_blank'{$style}>" . input::get('text_title', '') . "</a>";
				break;
			case 2:
				if (input::str('image_title') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_image_title']);
				}
				if (input::str('image_url') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_image_url']);
				}
				$code = array("image_title" => input::get('image_title', ''),
					"image_url" => input::get('image_url', ''),
					"image_desc" => input::get('image_desc', ''),
					"image_width" => input::get('image_width', ''),
					"image_height" => input::get('image_height', ''),
					);
				$image_width = input::get('image_width', '') ? " width='" . input::get('image_width', '') . "'" : "";
				$image_height = input::get('image_height', '') ? " height='" . input::get('image_height', '') . "'" : "";
				$htmlcode = "<a href='click.php?id=" . input::get('id', '') . "&amp;url=" . urlencode(input::str('image_url')) . "' target='_blank'><img src='" . input::get('image_title', '') . "' border='0' alt='" . input::get('image_desc', '') . "'{$image_width}{$image_height} /></a>";
				break;
			case 3:
				if (input::str('flash_url') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_url']);
				}
				if (input::str('flash_width') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_width']);
				}
				if (input::str('flash_height') == "")
				{
					$forums->admin->print_cp_error($forums->lang['no_input_flash_height']);
				}
				$code = array(
					"flash_url" => input::get('flash_url', ''),
					"flash_width" => input::get('flash_width', ''),
					"flash_height" => input::get('flash_height', ''),
				);
				$htmlcode = "<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' width='" . input::get('flash_width', '') . "' height='" . input::get('flash_height', '') . "'><param name='movie' value='" . input::get('flash_url', '') . "' /><param name='play' value='true' /><param name='loop' value='true' /><param name='quality' value='high' /><embed src='" . input::get('flash_url', '') . "' width='" . input::get('flash_width', '') . "' height='" . input::get('flash_height', '') . "' play='true' loop='true' quality='high'></embed></object>";
				break;
		}
		$array = array(
			'type' => input::get('type', ''),
			'name' => input::get('name', ''),
			'code' => '',
			'htmlcode' => '',
		);
		if (!input::get('id', ''))
		{
			$DB->insert(TABLE_PREFIX . 'ad', $array);
			input::set('id', $DB->insert_id());
		}

		if (in_array('-1', input::get('ad_in', '')))
		{
			input::set('ad_in', array(-1));
		}

		$array = array(
			'type' => input::get('type', ''),
			'name' => input::get('name', ''),
			'ad_in' => implode(',', input::get('ad_in', '')),
			'starttime' => $starttime,
			'endtime' => $endtime,
			'codetype' => input::get('codetype', ''),
			'code' => serialize($code),
			'htmlcode' => $htmlcode,
		);
		if (input::str('id'))
		{
			$DB->update(TABLE_PREFIX . 'ad', $array, 'id=' . input::get('id', ''));
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
		global $forums, $DB;
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
		global $forums, $DB;
		$ad = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "ad WHERE id = " . input::int('id') . "");
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
		global $forums, $DB;
		if (is_array(input::str('order')))
		{
			$ads = $DB->query("SELECT id,displayorder FROM " . TABLE_PREFIX . "ad");
			while ($ad = $DB->fetch_array($ads))
			{
				if (!isset(input::get('order', '')[$ad['id']]))
				{
					continue;
				}
				$displayorder = intval(input::get('order', '')[$ad['id']]);
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