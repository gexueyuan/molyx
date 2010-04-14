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

class iptools
{
	function show()
	{
		global $forums, $_INPUT;
		$forums->admin->nav[] = array('iptools.php', $forums->lang['manageips']);
		switch ($_INPUT['do'])
		{
			case 'showallips':
				$this->show_ips();
				break;
			case 'learnip':
				$this->learn_ip();
				break;
			default:
				$this->show_index();
				break;
		}
	}

	function learn_ip()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['ip'] == "")
		{
			$forums->main_msg = $forums->lang['inputips'];
			$this->show_index();
		}
		$ip = trim($_INPUT['ip']);
		$resolved = $forums->lang['resolvedips'];
		$exact = false;
		if (substr_count($ip, '.') == 3)
		{
			$exact = true;
		}
		if (strstr($ip, '*'))
		{
			$exact = false;
			$ip = str_replace("*", "", $ip);
		}
		if ($exact)
		{
			$resolved = gethostbyaddr($ip);
			$query = "='" . $ip . "'";
		}
		else
		{
			$query = " LIKE '" . $ip . "%'";
		}
		$pagetitle = $forums->lang['manageips'];
		$detail = $forums->lang['manageipsdesc'];
		$forums->admin->nav[] = array('', $forums->lang['viewipinfo']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->lang['ipnamehost'] = sprintf($forums->lang['ipnamehost'], $_INPUT['ip']);
		echo "<script type='text/javascript'>\n";
		echo "function js_user_jump(userid,username)\n";
		echo "{\n";
		echo "value = eval('document.userips.u' + userid + '.options[document.userips.u' + userid + '.selectedIndex].value');\n";
		echo "if (value=='showallips'){\n";
		echo "window.location = 'iptools.php?{$forums->js_sessionurl}&do=' + value + '&userid=' + userid;\n";
		echo "}\n";
		echo "if (value=='doform'){\n";
		echo "window.location = 'user.php?{$forums->js_sessionurl}&do=' + value + '&u=' + userid;\n";
		echo "}\n";
		echo "if (value=='searchpost'){\n";
		echo "window.open('../search.php?{$forums->js_sessionurl}do=finduser&u=' + userid + ''); \n";
		echo "}\n";
		echo "}\n";
		echo "</script>\n";
		echo "<script type='text/javascript'>\n";
		echo "function js_post_jump(userid,username)\n";
		echo "{\n";
		echo "value = eval('document.postips.u' + userid + '.options[document.postips.u' + userid + '.selectedIndex].value');\n";
		echo "if (value=='showallips'){\n";
		echo "window.location = 'iptools.php?{$forums->js_sessionurl}&do=' + value + '&userid=' + userid;\n";
		echo "}\n";
		echo "if (value=='doform'){\n";
		echo "window.location = 'user.php?{$forums->js_sessionurl}&do=' + value + '&u=' + userid;\n";
		echo "}\n";
		echo "if (value=='searchpost'){\n";
		echo "window.open('../search.php?{$forums->js_sessionurl}do=finduser&u=' + userid + ''); \n";
		echo "}\n";
		echo "}\n";
		echo "</script>\n";
		$forums->admin->print_table_start($forums->lang['ipnamehost']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['ipresolved'] . "</strong>", $resolved));
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array($forums->lang['username'], "20%");
		$forums->admin->columns[] = array($forums->lang['email'], "17%");
		$forums->admin->columns[] = array($forums->lang['posts'], "5%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "10%");
		$forums->admin->columns[] = array($forums->lang['joindate'], "13%");
		$forums->admin->columns[] = array($forums->lang['option'], "35%");
		$forums->admin->print_form_header("", "userips");
		$forums->admin->print_table_start($forums->lang['registeredips']);
		$users = $DB->query("SELECT id, name, email, posts, host, joindate FROM " . TABLE_PREFIX . "user WHERE host" . $query . " ORDER BY joindate DESC LIMIT 0,250");
		if (! $DB->num_rows($users))
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		else
		{
			while ($user = $DB->fetch_array($users))
			{
				$forums->admin->print_cells_row(array("<a href='user.php?{$forums->sessionurl}do=doform&amp;u=" . $user['id'] . "'>" . $user['name'] . "</a>" ,
						$user['email'],
						$user['posts'],
						$user['host'],
						$forums->func->get_date($user['joindate'], 3),
						$forums->admin->print_input_select_row('u' . $user['id'],
							array(0 => array('showallips', $forums->lang['showallips']),
								1 => array('doform', $forums->lang['edituserprofile']),
								2 => array('searchpost' , $forums->lang['finduserpost']),
								), '', "onchange=\"js_user_jump(" . $user['id'] . ", '" . $user['name'] . "');\"") . "<input type='button' class='button' value='" . $forums->lang['ok'] . "' onclick=\"js_user_jump(" . $user['id'] . ", '" . $user['name'] . "');\" />",
						));
			}
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->columns[] = array($forums->lang['username'], "20%");
		$forums->admin->columns[] = array($forums->lang['email'], "17%");
		$forums->admin->columns[] = array($forums->lang['posts'], "5%");
		$forums->admin->columns[] = array($forums->lang['postip'], "10%");
		$forums->admin->columns[] = array($forums->lang['usetimes'], "13%");
		$forums->admin->columns[] = array($forums->lang['option'], "35%");
		$forums->admin->print_form_header("", "postips");
		$forums->admin->print_table_start($forums->lang['usethisipsuser']);
		$posters = $DB->query("SELECT count(p.host) as ip, p.userid, u.name, u.email, u.posts, p.host, p.dateline FROM " . TABLE_PREFIX . "post p LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id=p.userid) WHERE p.host" . $query . " GROUP BY p.host ORDER BY p.dateline DESC LIMIT 0,250");
		if (! $DB->num_rows($posters))
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		else
		{
			while ($poster = $DB->fetch_array($posters))
			{
				$forums->admin->print_cells_row(array("<a href='user.php?{$forums->sessionurl}do=doform&amp;u=" . $poster['userid'] . "'>" . $poster['name'] . "</a>" ,
						$poster['email'],
						$poster['posts'],
						$poster['host'],
						$poster['ip'],
						$forums->admin->print_input_select_row('u' . $poster['userid'],
							array(0 => array('showallips', $forums->lang['showallips']),
								1 => array('doform', $forums->lang['edituserprofile']),
								2 => array('searchpost' , $forums->lang['finduserpost']),
								), '', "onchange=\"js_post_jump(" . $poster['userid'] . ", '" . $poster['name'] . "');\"") . "<input type='button' class='button' value='" . $forums->lang['ok'] . "' onclick=\"js_post_jump(" . $poster['userid'] . ", '" . $poster['name'] . "');\" />",
						));
			}
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->columns[] = array($forums->lang['username'], "25%");
		$forums->admin->columns[] = array($forums->lang['email'], "25%");
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "25%");
		$forums->admin->columns[] = array($forums->lang['joindate'], "25%");
		$forums->admin->print_table_start($forums->lang['usethisipsjoined']);
		$users = $DB->query("SELECT u.id, u.name, u.email, u.posts, u.joindate, ua.dateline, ua.host
						FROM " . TABLE_PREFIX . "useractivation ua
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( ua.userid=u.id)
						WHERE ua.host" . $query . " GROUP BY ua.userid ORDER BY ua.dateline DESC LIMIT 0,250");
		if (! $DB->num_rows($users))
		{
			$forums->admin->print_cells_single_row($forums->lang['noanyitems'], "center");
		}
		else
		{
			while ($user = $DB->fetch_array($users))
			{
				$forums->admin->print_cells_row(array("<a href='user.php?{$forums->sessionurl}do=doform&amp;u=" . $user['id'] . "'>" . $user['name'] . "</a>" ,
						$user['email'],
						$user['host'],
						$forums->func->get_date($user['dateline'], 1),
						));
			}
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function show_ips()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if ($_INPUT['name'] == "" AND $_INPUT['userid'] == "")
		{
			$forums->main_msg = $forums->lang['inputsearchuser'];
			$this->show_index();
		}
		if ($_INPUT['userid'])
		{
			$id = intval($_INPUT['userid']);
			if (! $user = $DB->query_first("SELECT id, name, email, host FROM " . TABLE_PREFIX . "user WHERE id='" . $id . "'"))
			{
				$forums->main_msg = $forums->lang['cannotfinduser'] . " " . $id;
				$this->show_index();
				exit;
			}
		}
		else
		{
			$username = trim($_INPUT['name']);
			if (! $user = $DB->query_first("SELECT id, name, email, host FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'"))
			{
				$forums->main_msg = $forums->lang['cannotfindusername'];
				$this->show_index($name);
				exit;
			}
		}
		$count = $DB->query_first("SELECT count(distinct(host)) as cnt FROM " . TABLE_PREFIX . "post WHERE userid='" . $user['id'] . "'");
		$curpage = intval($_INPUT['pp']);
		$end = 50;
		$links = $forums->func->build_pagelinks(array('totalpages' => $count['cnt'],
				'perpage' => $end,
				'curpage' => $curpage,
				'pagelink' => "iptools.php?{$forums->sessionurl}do=showallips&amp;userid=" . $user['id'] . "&amp;pp=" . $curpage . "",
				));
		$master = array();
		$ips = array();
		$DB->query("SELECT count(host) as ip, host, pid, threadid, dateline
				FROM " . TABLE_PREFIX . "post
				WHERE userid='" . $user['id'] . "'
				GROUP BY host
				ORDER BY ip DESC LIMIT " . $curpage . ", " . $end . "");
		while ($r = $DB->fetch_array())
		{
			$master[] = $r;
			$ips[] = '"' . $r['host'] . '"';
		}
		$reg = array();
		if (count($ips) > 0)
		{
			$DB->query("SELECT count(host) as ip, id, name, host FROM " . TABLE_PREFIX . "user WHERE host IN (" . implode(",", $ips) . ") AND id != " . $user['id'] . " GROUP BY host");
			while ($i = $DB->fetch_array())
			{
				$reg[ $i['ip'] ][] = $i;
			}
		}
		ksort ($reg);
		$pagetitle = $forums->lang['manageips'];
		$detail = $forums->lang['manageipsdesc'];
		$forums->admin->nav[] = array('', $forums->lang['viewipinfo']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array($forums->lang['ipaddress'], "20%");
		$forums->admin->columns[] = array($forums->lang['usetimes'], "10%");
		$forums->admin->columns[] = array($forums->lang['firstusetime'], "25%");
		$forums->admin->columns[] = array($forums->lang['usethisipsregistered'], "20%");
		$forums->admin->columns[] = array($forums->lang['manageips'], "25%");
		$forums->lang['matchuserips'] = sprintf($forums->lang['matchuserips'], $user['name'], $count['cnt']);
		$forums->admin->print_table_start($forums->lang['matchuserips']);
		foreach($master AS $idx => $r)
		{
			$forums->admin->print_cells_row(array("<a href='iptools.php?{$forums->sessionurl}&amp;do=learnip&amp;ip={$r['host']}'>" . $r['host'] . "</a>" ,
					"<center>" . $r['ip'] . "</center>",
					"<center>" . $forums->func->get_date($r['dateline'], 1) . "</center>",
					"<center>" . intval(count($reg[ $r['host'] ])) . "</center>",
					"<center><a href='iptools.php?{$forums->sessionurl}&amp;do=learnip&amp;ip={$r['host']}'>" . $forums->lang['viewipsotherinfo'] . "</a></center>"
					));
		}
		$forums->admin->print_cells_single_row("$links", "center", "catrow2");
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function show_index($username = "")
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = $forums->lang['manageips'];
		$detail = $forums->lang['manageipsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'showallips')), 'namesearch');
		$forums->admin->columns[] = array("&nbsp;" , "30%");
		$forums->admin->columns[] = array("&nbsp;" , "70%");
		$forums->admin->print_table_start($forums->lang['userpostips']);
		if ($username == "")
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bynamesearchips'] . "</strong>" , $forums->admin->print_input_row("name", $_INPUT['name'])));
		}
		else
		{
			$DB->query("SELECT id, name FROM " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE concat('" . strtolower($username) . "','%') OR name LIKE concat('" . $username . "','%')");
			if (! $DB->num_rows())
			{
				$forums->lang['notfindfirstcharsuser'] = sprintf($forums->lang['notfindfirstcharsuser'], $username);
				$forums->main_msg = $forums->lang['notfindfirstcharsuser'];
				$this->show_index();
			}
			$user_array = array();
			while ($m = $DB->fetch_array())
			{
				$user_array[] = array($m['id'], $m['name']);
			}
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectusers'] . "</strong>" ,
					$forums->admin->print_input_select_row("userid", $user_array)
					));
		}
		$forums->admin->print_form_submit($forums->lang['viewipinfo']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do' , 'learnip')));
		$forums->admin->columns[] = array("&nbsp;" , "30%");
		$forums->admin->columns[] = array("&nbsp;" , "70%");
		$forums->admin->print_table_start($forums->lang['manageips']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['findips'] . "</strong>", $forums->admin->print_input_row("ip", $_INPUT['ip'])));
		$forums->admin->print_form_submit($forums->lang['viewipinfo']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new iptools();
$output->show();

?>