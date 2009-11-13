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
# $Id: index.php 263 2007-10-11 13:49:42Z develop_tong $
# **************************************************************************#
require ('./global.php');

class index
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		switch ($_INPUT['do'])
		{
			case 'logout':
				$this->logout();
				break;
			default:
				$this->showindex();
				break;
		}
	}

	function showindex()
	{
		global $DB, $forums, $_INPUT, $bbuserinfo, $bboptions;
		if (isset($_INPUT['adminnews']))
		{
			switch ($_INPUT['adminnews'])
			{
				case 'hide':
					$forums->func->set_cookie('adminnews', '-1');
					$hidenews = 1;
					break;
				case 'show':
					$forums->func->set_cookie('adminnews', '0', -1);
					$hidenews = 0;
					break;
			}
		}
		else
		{
			$hidenews = $forums->func->get_cookie('adminnews');
		}
		$pagetitle = $forums->lang['welcomeadmincp'];
		$detail = $forums->lang['welcomeadmincpdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		if (!$bboptions['bbactive'])
		{
			$forums->admin->print_table_start($forums->lang['boardclosed']);
			$forums->admin->print_cells_single_row($forums->lang['boardcurrentclosed'] . "<br /><br />&raquo; <a href='settings.php?{$forums->sessionurl}do=setting_update&amp;settings_save=bbactive&amp;bbactive=1&amp;id=17'><strong>" . $forums->lang['activeboard'] . "</strong></a>");
			$forums->admin->print_table_footer();
		}
		if ($hidenews)
		{
			$link = " [<a href='index.php?{$forums->sessionurl}&amp;adminnews=show'>{$forums->lang['viewadminnews']}</a>]";
		}
		else
		{
			$link = " [<a href='index.php?{$forums->sessionurl}&amp;adminnews=hide'>{$forums->lang['hideadminnews']}</a>]";
		}
		$forums->admin->print_table_start($forums->lang['updatenews'] . $link);
		if ($hidenews)
		{
			$forums->admin->print_cells_single_row("{$forums->lang['noadminnews']}\n");
		}
		else
		{
			$forums->admin->print_cells_single_row("<div id=\"updatenews\"></div><script type='text/javascript' src='http://www.molyx.com/scripts/updatenews.js' defer='defer'></script>\n");
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array($forums->lang['username'], "20%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "20%");
		$forums->admin->columns[] = array($forums->lang['logintimes'], "20%");
		$forums->admin->columns[] = array($forums->lang['lastactive'], "20%");
		$forums->admin->columns[] = array($forums->lang['currentlocation'], "20%");
		$forums->admin->print_table_start($forums->lang['inadmincp']);
		$time = TIMENOW - 60 * 10;
		$result = $DB->query("SELECT username, location, logintime, lastactivity, host
			FROM " . TABLE_PREFIX . "adminsession
			WHERE lastactivity > $time");
		$time_now = TIMENOW;
		$seen_name = array();
		while ($r = $DB->fetch_array($result))
		{
			if ($seen_name[$r['username']] == 1)
			{
				continue;
			}
			else
			{
				$seen_name[$r['username']] = 1;
			}
			$logintime = $time_now - $r['logintime'];
			$lastactivity = $time_now - $r['lastactivity'];
			if (($log_in / 60) < 1)
			{
				$logintime = sprintf('%0d', $logintime) . ' ' . $forums->lang['beforeseconds'];
			}
			else
			{
				$logintime = sprintf('%0d', ($logintime / 60)) . ' ' . $forums->lang['beforeminutes'];
			}

			if (($lastactivity / 60) < 1)
			{
				$lastactivity = sprintf('%0d', $lastactivity) . ' ' . $forums->lang['beforeseconds'];
			}
			else
			{
				$lastactivity = sprintf('%0d', ($lastactivity / 60)) . ' ' . $forums->lang['beforeminutes'];
			}

			$forums->admin->print_cells_row(array(
				$r['username'],
				"<center><span onclick='javascript:alert(\"" . $forums->lang['hostaddress'] . ": " . @gethostbyaddr($r['host']) . "\")' title='" . $forums->lang['hostaddress'] . ": " . @gethostbyaddr($r['host']) . "' style='cursor:pointer;'>" . $r['host'] . "</span></center>",
				"<center>" . $logintime . "</center>",
				"<center>" . $lastactivity . "</center>",
				"<center>" . $r['location'] . "</center>",
			));
		}

		$forums->admin->print_table_footer();
		$reg = $DB->query_first("SELECT COUNT(*) as reg
			FROM " . TABLE_PREFIX . "useractivation
			WHERE type <> 1");
		$reg['reg'] = intval($reg['reg']);
		$forums->admin->columns[] = array("&nbsp;", "50%");
		$forums->admin->columns[] = array("&nbsp;", "50%");
		$forums->admin->print_table_start($forums->lang['systeminfo']);
		$total = $DB->query_first('SELECT SUM(thread) AS totalthreads, SUM(post) AS totalposts
			FROM ' . TABLE_PREFIX . 'forum
			WHERE parentid = \'-1\'');
		$forums->admin->print_cells_row(array(
			$forums->lang['totalthreads'] . ": " . fetch_number_format($total['totalthreads']),
			$forums->lang['totalposts'] . ": " . fetch_number_format($total['totalposts']),)
		);
		$forums->func->check_cache('stats');
		$forums->admin->print_cells_row(array($forums->lang['totalusers'] . ": " . fetch_number_format($forums->cache['stats']['numbermembers']), "<a href='user.php?{$forums->sessionurl}do=mod'>" . $forums->lang['totalmodusers'] . "</a>: " . fetch_number_format($reg['reg'])));
		$forums->admin->print_table_footer();
		$admin = explode(',', SUPERADMIN);
		if (in_array($bbuserinfo['id'], $admin) AND $forums->adminperms['canviewadminlogs'])
		{
			$forums->admin->columns[] = array($forums->lang['username'], "20%");
			$forums->admin->columns[] = array($forums->lang['doaction'], "40%");
			$forums->admin->columns[] = array($forums->lang['logtime'], "20%");
			$forums->admin->columns[] = array($forums->lang['ipaddress'], "20%");
			$forums->admin->print_table_start($forums->lang['lastfiveaction']);
			$result = $DB->query("SELECT a.*, u.id, u.name
				FROM " . TABLE_PREFIX . "adminlog a
					LEFT JOIN " . TABLE_PREFIX . "user u
						ON (a.userid=u.id)
				ORDER BY a.dateline DESC
				LIMIT 0, 5");
			if ($DB->num_rows($result))
			{
				while ($row = $DB->fetch_array($result))
				{
					$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
					$forums->admin->print_cells_row(array("<strong>{$row['name']}</strong>", "{$row['note']}", "{$row['dateline']}", "{$row['host']}"));
				}
			}
			else
			{
				$forums->admin->print_cells_single_row($forums->lang['nologged'], "center");
			}
			$forums->admin->print_table_footer();
		}
		$forums->admin->print_form_header();
		$forums->admin->columns[] = array("&nbsp;", "20%");
		$forums->admin->columns[] = array("&nbsp;", "80%");
		$forums->admin->print_table_start($forums->lang['molyxgroups']);

		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['developer'] . ":</strong>",
			"<a href='http://www.hogesoft.com' target='_blank'>" . $forums->lang['hogesoftco'] . "</a>",)
		);
		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['thisproductteam'] . ":</strong>",
			"<a href='http://www.molyx.com/profile.php?u=22549' target='_blank'>Tim</a>, <a href='http://www.molyx.com/profile.php?u=15202' target='_blank'>guyefeng</a>, <a href='http://www.molyx.com/profile.php?u=5154' target='_blank'>FirstPlan</a>, <a href='http://www.molyx.com/profile.php?u=1601' target='_blank'>FlyingHail</a>, <a href='http://www.molyx.com/profile.php?u=13441' target='_blank'>lixiangyang</a>, <a href='http://www.molyx.com/profile.php?u=22636' target='_blank'>linux528</a>, <a href='http://www.molyx.com/profile.php?u=22635' target='_blank'>majingmin</a>")
		);
		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['historyproductteam'] . ":</strong>",
			"<a href='http://www.molyx.com/profile.php?u=1' target='_blank'>firefox</a>, <a href='http://www.molyx.com/profile.php?u=2' target='_blank'>jiangcat</a>, <a href='http://www.hogesoft.com' target='_blank'>Sancho</a>, <a href='http://www.molyx.com/profile.php?u=4912' target='_blank'>liverXing</a>, <a href='http://www.molyx.com/profile.php?u=5154' target='_blank'>firstplan</a>, <a href='http://www.molyx.com/profile.php?u=1650' target='_blank'>Justlau</a>, <a href='http://www.molyx.com/profile.php?u=1766' target='_blank'>ybb</a>, <a href='http://www.molyx.com/profile.php?u=1601' target='_blank'>FlyingHail</a>, <a href='http://www.molyx.com/profile.php?u=12703' target='_blank'>kinch</a>, <a href='http://www.molyx.com/profile.php?u=13441' target='_blank'>lixiangyang</a>, <a href='http://www.molyx.com/profile.php?u=15202' target='_blank'>guyefeng</a>, <a href='http://www.molyx.com/profile.php?u=22549' target='_blank'>Tim</a>, <a href='http://www.molyx.com/profile.php?u=22636' target='_blank'>linux528</a>, <a href='http://www.molyx.com/profile.php?u=22635' target='_blank'>majingmin</a>")
		);
		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['thankproduct'] . ":</strong>",
			"<a href='http://www.molyx.com/profile.php?u=5336' target='_blank'>BreakNife</a>, <a href='http://www.molyx.com/profile.php?u=4592' target='_blank'>KissVenus</a>, <a href='http://www.molyx.com/profile.php?u=1536' target='_blank'>kavenzou</a>, <a href='http://www.molyx.com/profile.php?u=7748' target='_blank'>foliage</a>, <a href='http://www.molyx.com/profile.php?u=77' target='_blank'>bensunan</a>, <a href='http://www.molyx.com/profile.php?u=844' target='_blank'>Leez</a>, <a href='http://www.molyx.com/profile.php?u=5534' target='_blank'>CaThi</a>, <a href='http://www.molyx.com/profile.php?u=8831' target='_blank'>YiYoRain</a>, <a href='http://www.molyx.com/profile.php?u=10674' target='_blank'>小可爱</a>, <a href='http://www.molyx.com/profile.php?u=9553' target='_blank'>云飞扬</a>, <a href='http://www.molyx.com/profile.php?u=6468' target='_blank'>Napoleon</a>")
		);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_table_start($forums->lang['molyxpartner']);
		$forums->admin->print_cells_row(array("<a href='http://www.edong.com/' target='_blank'><img src='" . $forums->imageurl . "/edong.gif' style='border:1px;color:#000000;' /></a>"
				));
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function logout()
	{
		global $DB, $forums, $_INPUT, $bbuserinfo, $bboptions;
		$DB->query_unbuffered('DELETE FROM ' . TABLE_PREFIX . 'adminsession
			WHERE userid=' . $bbuserinfo['id']);
		$forums->admin->print_cp_login($forums->lang['youhavesafelogout']);
	}
}

$output = new index();
$output->show();
?>