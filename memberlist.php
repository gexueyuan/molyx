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
define('THIS_SCRIPT', 'memberlist');
require_once('./global.php');

class memberlist
{
	var $usertitle = array();
	var $usergroup = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('member');
		if (!$bbuserinfo['canviewmember'])
		{
			$forums->func->standard_error("cannotviewthispage");
		}
		$usergroups = array();
		$forums->func->check_cache('ranks');
		$this->usertitle = $forums->cache['ranks'];

		$forums->func->check_cache('usergroup');
		foreach($forums->cache['usergroup'] AS $id => $row)
		{
			if ($row['hidelist'])
			{
				continue;
			}
			$usergroups[] = $row['usergroupid'];
			$this->usergroup[ $row['usergroupid'] ] = array('title' => $forums->lang[ $row['grouptitle'] ], 'icon' => $row['groupicon']);
		}
		$filter_key = array('all' => $forums->lang['allmember']);
		foreach($this->usergroup AS $id => $data)
		{
			if ($id == 2)
			{
				continue;
			}
			$filter_key[$id] = $data['title'];
		}
		$group_string = implode(",", $usergroups);
		$first = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$maxresults = $_INPUT['max_results'] ? $_INPUT['max_results'] : 10;
		$sortby = $_INPUT['sortby'] ? $_INPUT['sortby'] : 'id';
		$order = $_INPUT['order'] ? $_INPUT['order'] : 'desc';
		$filter = $_INPUT['filter'] ? $_INPUT['filter'] : 'all';
		$sort_key = array('name' => $forums->lang['username'], 'posts' => $forums->lang['posts'], 'id' => $forums->lang['joindate']);
		$results_key = array(10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50',);
		$order_key = array('desc' => $forums->lang['desc'], 'asc' => $forums->lang['asc']);
		foreach ($order_key AS $k => $v)
		{
			$order_html .= $k == $order ? "<option value='$k' selected='selected'>" . $order_key[ $k ] . "</option>\n" : "<option value='$k'>" . $order_key[ $k ] . "</option>\n";
		}
		foreach ($filter_key AS $k => $v)
		{
			$filter_html .= $k == $filter ? "<option value='$k' selected='selected'>" . $filter_key[ $k ] . "</option>\n" : "<option value='$k'>" . $filter_key[ $k ] . "</option>\n";
		}
		foreach ($sort_key AS $k => $v)
		{
			$sortby_html .= $k == $sortby ? "<option value='$k' selected='selected'>" . $sort_key[ $k ] . "</option>\n" : "<option value='$k'>" . $sort_key[ $k ] . "</option>\n";
		}
		foreach ($results_key AS $k => $v)
		{
			$max_results_html .= $k == $maxresults ? "<option value='$k' selected='selected'>" . $results_key[ $k ] . "</option>\n"
			: "<option value='$k'>" . $results_key[ $k ] . "</option>\n";
		}
		if (! isset($sort_key[ $sortby ]) OR ! isset($order_key[ $order ]) OR ! isset($filter_key[ $filter ]) OR ! isset($results_key[ $maxresults ]))
		{
			$forums->func->standard_error("errororderlist");
		}
		$query = array();
		$url = array();
		$query_string = "";
		if ($filter != 'all')
		{
			if (! preg_match("/(^|,)" . $filter . "(,|$)/", $group_string))
			{
				$query[] = "u.usergroupid IN($group_string)";
			}
			else
			{
				$query[] = "u.usergroupid='" . $filter . "' ";
			}
		}
		$dates = array('lastpost', 'lastvisit', 'joindate');
		$userinfo = array('qq' => 'u.qq',
			'aim' => 'u.aim',
			'yahoo' => 'u.yahoo',
			'icq' => 'u.icq',
			'msn' => 'u.msn',
			'uc' => 'u.uc',
			'popo' => 'u.popo',
			'skype' => 'u.skype',
			'posts' => 'u.posts',
			'joindate' => 'u.joindate',
			'lastpost' => 'u.lastpost',
			'lastvisit' => 'u.lastvisit',
			'website' => 'u.website',
			'name' => 'u.name',
			'gender' => 'u.gender',
			);
		foreach($userinfo AS $in => $tbl)
		{
			$inbit = clean_value(trim(rawurldecode($_INPUT[ $in ])));
			if ($in != 'name' && $in != 'gender')
			{
				$url[] = $in . '=' . $_INPUT[ $in ];
			}
			if ($in == 'name' AND $inbit != "")
			{
				if ($_INPUT['name_box'] == 'begins')
				{
					$query[] = "LOWER(u.name) LIKE concat('" . strtolower($inbit) . "','%')";
					$url[] = 'name_box=begins';
				}
				else
				{
					$query[] = "LOWER(u.name) LIKE concat('%','" . strtolower($inbit) . "','%')";
				}
				$url[] = 'name=' . urlencode($inbit);
			}
			else if ($in == 'posts' AND intval($inbit) > 0)
			{
				$ltmt = $_INPUT[ $in . '_ltmt' ] == 'lt' ? '<' : '>';
				$query[] = $tbl . ' ' . $ltmt . ' ' . intval($inbit);
				$url[] = $in . '_ltmt=' . $_INPUT[ $in . '_ltmt' ];
			}
			else if ($in == 'gender')
			{
				if ($inbit == 'male')
				{
					$query[] = "u.gender=1";
				}
				else if ($inbit == 'female')
				{
					$query[] = "u.gender=2";
				}
				$url[] = 'gender=' . $inbit;
			}
			else if ($_INPUT[ 'have_' . $in ])
			{
				$checkbox[$in] = " checked='checked'";
				$query[] = $inbit != "" ? $tbl . " LIKE '{$inbit}%'" : $tbl . "!=''";
				$url[] = 'have_' . $in . '=1';
			}
			else if (in_array($in, $dates) AND $inbit)
			{
				list($month, $day, $year) = explode('-', $_INPUT[ $in ]);
				if (! checkdate($month, $day, $year))
				{
					continue;
				}
				$time_int = $forums->func->mk_time(0, 0 , 0, $month, $day, $year);
				$ltmt = (trim($_INPUT[$in . '_ltmt']) == 'lt') ? '<' : '>';
				$query[] = $tbl . ' ' . $ltmt . ' ' . $time_int;
				$url[] = $in . '_ltmt=' . $_INPUT[ $in . '_ltmt' ];
			}
			else if ($inbit != "")
			{
				$query[] = $tbl . " LIKE '{$inbit}%'";
			}
		}
		if (count($query))
		{
			$query_string = " AND " . implode(" AND ", $query);
		}
		$max = $DB->query_first("SELECT COUNT(*) as count FROM " . TABLE_PREFIX . "user u
								LEFT JOIN " . TABLE_PREFIX . "usergroup g ON (g.usergroupid=u.usergroupid)
								WHERE g.hidelist <> 1{$query_string}");
		if ($max['count'] > 0)
		{
			$member['pagenav'] = $forums->func->build_pagelinks(array('totalpages' => $max['count'],
					'perpage' => $maxresults,
					'curpage' => $first,
					'pagelink' => "memberlist.php{$forums->sessionurl}&amp;sortby={$sortby}&amp;order={$order}&amp;filter={$filter}&amp;max_results={$maxresults}&amp;" . implode('&amp;', $url)
					)
				);
			$users = $DB->query("SELECT u.*,g.hidelist,g.usergroupid,g.canblog FROM " . TABLE_PREFIX . "user u
									LEFT JOIN " . TABLE_PREFIX . "usergroup g ON (u.usergroupid = g.usergroupid)
									WHERE g.hidelist <> 1{$query_string}
									ORDER BY u.{$sortby} {$order}
									LIMIT {$first}, {$maxresults}"
				);
			while ($user = $DB->fetch_array($users))
			{
				$user['userid'] = $user['id'];
				$user = $forums->func->fetch_user($user);
				$userlist[] = $user;
			}
		}
		else
		{
			$show['nouser'] = true;
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['memberlist'] . " - " . $bboptions['bbtitle'];
		$nav = array("<a href='memberlist.php{$forums->sessionurl}'>" . $forums->lang['memberlist'] . "</a>");
		include $forums->func->load_template('member_list');
		exit;
	}
}

$output = new memberlist();
$output->show();

?>