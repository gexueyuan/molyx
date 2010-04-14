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

class statistics
{
	function show()
	{
		global $forums, $_INPUT;
		$forums->admin->nav[] = array('statistics.php' , $forums->lang['boardstats']);
		switch ($_INPUT['do'])
		{
			case 'showresults':
				$this->showresults();
				break;
			default:
				$this->main_screen();
				break;
		}
	}

	function show_views()
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['statsresult'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		if (!checkdate($_INPUT['from_month'], $_INPUT['from_day'], $_INPUT['from_year']))
		{
			$forums->admin->print_cp_error($forums->lang['fromdateerror']);
		}
		if (!checkdate($_INPUT['to_month'], $_INPUT['to_day'], $_INPUT['to_year']))
		{
			$forums->admin->print_cp_error($forums->lang['enddateerror']);
		}
		$to_time = mktime(0, 0, 0, $_INPUT['to_month'], $_INPUT['to_day'], $_INPUT['to_year']);
		$from_time = mktime(0, 0, 0, $_INPUT['from_month'], $_INPUT['from_day'], $_INPUT['from_year']);
		$human_to_date = getdate($to_time);
		$human_from_date = getdate($from_time);
		$DB->query("SELECT SUM(t.views) as result_count, t.forumid, f.name as result_name
				FROM " . TABLE_PREFIX . "thread t, " . TABLE_PREFIX . "forum f
				WHERE t.dateline > '" . $from_time . "'
				AND t.dateline < '" . $to_time . "'
				AND t.forumid=f.id
				GROUP BY t.forumid
				ORDER BY result_count " . $_INPUT['sortby'] . "");
		$running_total = 0;
		$max_result = 0;
		$results = array();
		$forums->admin->columns[] = array($forums->lang['forum'] , "40%");
		$forums->admin->columns[] = array($forums->lang['result'], "50%");
		$forums->admin->columns[] = array($forums->lang['views'] , "10%");
		$forums->admin->print_table_start($forums->lang['threadviews']
			 . " ( " . $forums->lang['from'] . " {$human_from_date['year']}-{$human_from_date['mon']}-{$human_from_date['mday']} " . $forums->lang['to'] . " {$human_to_date['year']}-{$human_to_date['mon']}-{$human_to_date['mday']} )"
			);
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
			{
				if ($row['result_count'] > $max_result)
				{
					$max_result = $row['result_count'];
				}
				$running_total += $row['result_count'];
				$results[] = array('result_name' => $row['result_name'],
					'result_count' => $row['result_count'],
					);
			}
			foreach($results AS $id => $data)
			{
				$img_width = intval(($data['result_count'] / $max_result) * 100 - 8);
				if ($img_width < 1)
				{
					$img_width = 1;
				}
				$img_width .= '%';
				$forums->admin->print_cells_row(array($data['result_name'],
						"<img src='{$forums->imageurl}/bar_left.gif' border='0' width='4' height='11' align='middle' alt='' /><img src='{$forums->imageurl}/bar.gif' border='0' width='$img_width' height='11' align='middle' alt='' /><img src='{$forums->imageurl}/bar_right.gif' border='0' width='4' height='11' align='middle' alt='' />",
						"<center>" . $data['result_count'] . "</center>",
						));
			}
			$forums->admin->print_cells_row(array('&nbsp;', "<div align='right'><strong>" . $forums->lang['total'] . "</strong></div>", "<center><strong>" . $running_total . "</strong></center>"));
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function showresults()
	{
		global $forums, $DB, $_INPUT;
		if (!checkdate($_INPUT['from_month'], $_INPUT['from_day'], $_INPUT['from_year']))
		{
			$forums->admin->print_cp_error($forums->lang['fromdateerror']);
		}
		if (!checkdate($_INPUT['to_month'], $_INPUT['to_day'], $_INPUT['to_year']))
		{
			$forums->admin->print_cp_error($forums->lang['enddateerror']);
		}
		$to_time = mktime(0, 0, 0, $_INPUT['to_month'], $_INPUT['to_day'], $_INPUT['to_year']);
		$from_time = mktime(0, 0, 0, $_INPUT['from_month'], $_INPUT['from_day'], $_INPUT['from_year']);
		$human_to_date = getdate($to_time);
		$human_from_date = getdate($from_time);
		switch ($_INPUT['resulttype'])
		{
			case 'show_reg':
				$table = $forums->lang['registerstats'];
				$detail = $forums->lang['registerstatsdesc'];
				$sql_table = 'user';
				$sql_field = 'joindate';
				break;
			case 'show_thread':
				$table = $forums->lang['threadstats'];
				$detail = $forums->lang['threadstatsdesc'];
				$sql_table = 'thread';
				$sql_field = 'dateline';
				break;
			case 'show_post':
				$table = $forums->lang['poststats'];
				$detail = $forums->lang['poststatsdesc'];
				$sql_table = 'post';
				$sql_field = 'dateline';
				break;
			case 'show_msg':
				$table = $forums->lang['pmstats'];
				$detail = $forums->lang['pmstatsdesc'];
				$sql_table = 'pm';
				$sql_field = 'dateline';
				break;
			case 'show_views':
				$this->show_views();
				break;
			default:
				$table = $forums->lang['registerstats'];
				$detail = $forums->lang['registerstatsdesc'];
				$sql_table = 'user';
				$sql_field = 'joindate';
				break;
		}
		$forums->admin->nav[] = array('' , $table);
		$forums->admin->print_cp_header($table, $detail);
		switch ($_INPUT['timescale'])
		{
			case 'daily':
				$sql_date = "%w %U %m %Y";
				$php_date = "F jS - Y";
				break;
			case 'monthly':
				$sql_date = "%m %Y";
				$php_date = "F Y";
				break;
			default:
				$sql_date = "%U %Y";
				$php_date = " [F Y]";
				break;
		}

		$DB->query("SELECT MAX(" . $sql_field . ") as result_maxdate,
				 COUNT(*) as result_count,
				 DATE_FORMAT(FROM_UNIXTIME($sql_field), '$sql_date') AS result_time
				 FROM " . TABLE_PREFIX . "" . $sql_table . "
				 WHERE " . $sql_field . " > '" . $from_time . "'
				 AND " . $sql_field . " < '" . $to_time . "'
				 GROUP BY result_time
				 ORDER BY " . $sql_field . " " . $_INPUT['sortby'] . "");
		$running_total = 0;
		$max_result = 0;
		$results = array();
		$forums->admin->columns[] = array($forums->lang['date'], "20%");
		$forums->admin->columns[] = array($forums->lang['result'], "70%");
		$forums->admin->columns[] = array($forums->lang['numbers'], "10%");
		$forums->admin->print_table_start($table
			 . " ( " . $forums->lang['from'] . " {$human_from_date['year']}-{$human_from_date['mon']}-{$human_from_date['mday']} " . $forums->lang['to'] . " {$human_to_date['year']}-{$human_to_date['mon']}-{$human_to_date['mday']} )"
			);
		if ($DB->num_rows())
		{
			while ($row = $DB->fetch_array())
			{
				if ($row['result_count'] > $max_result)
				{
					$max_result = $row['result_count'];
				}
				$running_total += $row['result_count'];
				$results[] = array('result_maxdate' => $row['result_maxdate'],
					'result_count' => $row['result_count'],
					'result_time' => $row['result_time'],
					);
			}
			foreach($results AS $id => $data)
			{
				$img_width = intval(($data['result_count'] / $max_result) * 100 - 8);
				if ($img_width < 1)
				{
					$img_width = 1;
				}
				$img_width .= '%';
				if ($_INPUT['timescale'] == 'weekly')
				{
					$date = "Week #" . strftime("%W", $data['result_maxdate']) . date($php_date, $data['result_maxdate']);
				}
				else
				{
					$date = date($php_date, $data['result_maxdate']);
				}
				$forums->admin->print_cells_row(array($date,
						"<img src='{$forums->imageurl}/bar_left.gif' border='0' width='4' height='11' align='middle' alt='' /><img src='{$forums->imageurl}/bar.gif' border='0' width='$img_width' height='11' align='middle' alt='' /><img src='{$forums->imageurl}/bar_right.gif' border='0' width='4' height='11' align='middle' alt='' />",
						"<center>" . $data['result_count'] . "</center>",
						));
			}
			$forums->admin->print_cells_row(array('&nbsp;',
					"<div align='right'><strong>" . $forums->lang['total'] . "</strong></div>",
					"<center><strong>" . $running_total . "</strong></center>",
					));
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function main_screen()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['boardstats'];
		$detail = $forums->lang['boardstatsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$old_date = getdate(TIMENOW - (3600 * 24 * 90));
		$new_date = getdate(TIMENOW + (3600 * 24));
		$forums->admin->print_form_header(array(1 => array('do' , 'showresults')));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['boardstats']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectviewstats'] . "</strong>" ,
				$forums->admin->print_input_select_row('resulttype', array(0 => array('show_reg', $forums->lang['registerstats']),
						1 => array('show_thread', $forums->lang['threadstats']),
						2 => array('show_post', $forums->lang['poststats']),
						3 => array('show_msg', $forums->lang['pmstats']),
						4 => array('show_views', $forums->lang['threadviews'])
						), $_INPUT['namewhere']
					)
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['startdate'] . "</strong>" ,
				$forums->admin->print_input_select_row("from_year" , $this->year() , $old_date['year']) . '&nbsp;&nbsp;' . $forums->admin->print_input_select_row("from_month" , $this->month(), $old_date['mon']) . '&nbsp;&nbsp;' . $forums->admin->print_input_select_row("from_day" , $this->day() , $old_date['mday'])
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['enddate'] . "</strong>" ,
				$forums->admin->print_input_select_row("to_year" , $this->year() , $new_date['year']) . '&nbsp;&nbsp;' . $forums->admin->print_input_select_row("to_month" , $this->month(), $new_date['mon']) . '&nbsp;&nbsp;' . $forums->admin->print_input_select_row("to_day" , $this->day() , $new_date['mday'])
				));
		if ($mode != 'views')
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['viewtype'] . "</strong>" ,
					$forums->admin->print_input_select_row("timescale" , array(0 => array('daily', $forums->lang['daily']), 1 => array('weekly', $forums->lang['weeks']), 2 => array('monthly', $forums->lang['months'])))
					));
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sortorder'] . "</strong>" ,
				$forums->admin->print_input_select_row("sortby" , array(0 => array('asc', $forums->lang['statssortbyasc']), 1 => array('desc', $forums->lang['statssortbydesc'])), 'desc')
				));
		$forums->admin->print_form_submit($forums->lang['viewstats']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function year()
	{
		$time_now = getdate();
		$return = array();
		$start_year = 2002;
		$latest_year = intval($time_now['year']);
		if ($latest_year == $start_year) $start_year -= 1;
		for ($y = $start_year; $y <= $latest_year; $y++) $return[] = array($y, $y);
		return $return;
	}

	function month()
	{
		global $forums;
		$return = array();
		for ($m = 1 ; $m <= 12; $m++) $return[] = array($m, $m . " " . $forums->lang['months']);
		return $return;
	}

	function day()
	{
		$return = array();
		for ($d = 1 ; $d <= 31; $d++) $return[] = array($d, $d);
		return $return;
	}
}

$output = new statistics();
$output->show();

?>