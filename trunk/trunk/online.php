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
# $Id: online.php 295 2007-10-18 06:36:11Z develop_tong $
# **************************************************************************#
define('THIS_SCRIPT', 'online');
require_once('./global.php');

class online
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (!$bboptions['WOLenable'] && $bbuserinfo['usergroupid'] != 4)
		{
			$forums->func->standard_error("cannotviewonline");
		}
		$forums->func->load_lang('member');
		$forums->func->load_lang('online');
		$pp = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$cutoff = ($bboptions['cookietimeout'] > 0) ? $bboptions['cookietimeout'] * 60 : 900;
		$t_time = TIMENOW - $cutoff;
		$usergroup_array = array('reg' => $forums->lang['regmember'], 'guest' => $forums->lang['guest'], 'all' => $forums->lang['allmember']);
		$order_array = array('desc' => $forums->lang['desc'], 'asc' => $forums->lang['asc']);
		$sortby_array = array('click' => $forums->lang['click'], 'name' => $forums->lang['username']);
		$group_value = $_INPUT['usergroup'] ? $_INPUT['usergroup'] : 'all';
		$order_value = $_INPUT['order'] ? $_INPUT['order'] : 'desc';
		$sortby_value = $_INPUT['sortby'] ? $_INPUT['sortby'] : 'click';
		$usergroup = '';
		$sortby = '';
		$order = '';
		foreach($usergroup_array as $k => $v)
		{
			$s = '';
			if ($group_value == $k)
			{
				$s = " selected='selected'";
			}
			$usergroup .= "<option value='" . $k . "'" . $s . ">" . $v . "</option>\n";
		}
		foreach($order_array AS $k => $v)
		{
			$s = '';
			if ($order_value == $k)
			{
				$s = " selected='selected'";
			}
			$order .= "<option value='" . $k . "'" . $s . ">" . $v . "</option>\n";
		}
		foreach($sortby_array AS $k => $v)
		{
			$s = '';
			if ($sortby_value == $k)
			{
				$s = " selected='selected'";
			}
			$sortby .= "<option value='" . $k . "'" . $s . ">" . $v . "</option>\n";
		}
		$db_order = $order_value == 'asc' ? 'asc' : 'desc';
		$db_key = $sortby_value == 'click' ? 'lastactivity' : 'username';
		switch ($group_value)
		{
			case 'reg':
				$group = " AND usergroupid <> 2";
				break;
			case 'guest':
				$group = " AND usergroupid = 2";
				break;
			default:
				$group = "";
				break;
		}
		$omax = $DB->query_first("SELECT COUNT(sessionhash) as sessions FROM " . TABLE_PREFIX . "session WHERE invisible <> 1 AND lastactivity > $t_time" . $group . "");
		$links = $forums->func->build_pagelinks(array('totalpages' => $omax['sessions'],
				'perpage' => 40,
				'curpage' => $pp,
				'pagelink' => "online.php{$forums->sessionurl}sortby=$sortby_value&amp;order=$order_value&amp;usergroup=$group_value")
			);
		$tid_array = array();
		$thread = array();
		$user = array();
		$userlist = array();
		$forums->func->check_cache('usergroup');
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "session WHERE lastactivity > $t_time" . $group . " ORDER BY $db_key $db_order LIMIT " . $pp . ", 40");
		while ($r = $DB->fetch_array())
		{
			$r['opentag'] = $forums->cache['usergroup'][ $r['usergroupid'] ]['opentag'];
			$r['closetag'] = $forums->cache['usergroup'][ $r['usergroupid'] ]['closetag'];
			$user[] = $r;
			if ($r['inthread'] != "")
			{
				$tid_array[ $r['inthread'] ] = $r['inthread'];
			}
		}
		if (count($tid_array) > 0)
		{
			$DB->query("SELECT tid, title FROM " . TABLE_PREFIX . "thread WHERE tid IN (" . implode(",", $tid_array) . ")");
			while ($t = $DB->fetch_array())
			{
				$thread[ $t['tid'] ] = $t['title'];
			}
		}
		foreach($user AS $idx => $session)
		{
			$invisible = '';
			if ($session['invisible'] == 1)
			{
				if ($bbuserinfo['usergroupid'] == 4)
				{
					$invisible = '*';
				}
				else
				{
					continue;
				}
			}
			if ($session['badlocation'])
			{
				$location = $forums->lang['boardhome'];
			}
			else if (isset($session['location']))
			{
				$loc = $session['location'];
				$loc = preg_replace('/\?s=[a-z0-9]{32}(&)?/', '?', $loc);
				if ($loc == $session['location'])
				{
					$loc = preg_replace('/\?s=(&)?/', '?', $loc);
				}
				if ($loc == $session['location'])
				{
					$loc = preg_replace('/&s=[a-z0-9]{32}/', '', $loc);
				}
				if ($loc == $session['location'])
				{
					$loc = preg_replace('/&s=/', '', $loc);
				}
				$filename = strtok($loc, '?');
				$token = $filename;
				$tpos = strrpos ($filename, '/');
				if (!is_string($tpos) OR $tpos)
				{
					$filename = substr($filename, $tpos + 1);
				}
				$deny = $this->online_check($session['inforum']);
				switch ($filename)
				{
					case 'online.php':
						$location = $forums->lang['viewingonline'];
						break;
					case 'forumdisplay.php':
						if ($deny)
						{
							$location = $forums->lang['viewingboard'];
						}
						else
						{
							$location = $forums->lang['viewingboard'] . ": <a href='forumdisplay.php?f=" . $session['inforum'] . "'>" . $forums->forum->foruminfo[$session['inforum']]['name'] . "</a>";
						}
						break;
					case 'showthread.php':
						if ($deny)
						{
							$location = $forums->lang['viewingthread'];
						}
						else
						{
							$location = $forums->lang['viewingthread'] . ": <a href='showthread.php{$forums->sessionurl}t=" . $session['inthread'] . "'>" . $thread[$session['inthread']] . "</a>";
						}
						break;
					case 'memberlist.php':
						$location = $forums->lang['viewingmember'];
						break;
					case 'attachment.php':
						$location = $forums->lang['viewingattach'];
						break;
					case 'editpost.php':
						$location = $forums->lang['editingpost'];
						break;
					case 'faq.php':
						$location = $forums->lang['viewingfaq'];
						break;
					case 'addpoll.php':
						$location = $forums->lang['doaddpoll'];
						break;
					case 'private.php':
						$location = $forums->lang['dosendpm'];
						break;
					case 'register.php':
						$location = $forums->lang['doregister'];
						break;
					case 'search.php':
						$location = $forums->lang['dosearch'];
						break;
					case 'profile.php':
						$location = $forums->lang['viewingusercp'];
						break;
					case 'usercp.php':
						$location = $forums->lang['viewingusercp'];
						break;
					default:
						$location = $forums->lang['boardhome'];
						break;
				}
			}
			else
			{
				$location = $forums->lang['boardhome'];
			}
			$session['location'] = $location;
			if ($bbuserinfo['usergroupid'] == 4)
			{
				$session['host'] = " ( " . $session['host'] . " )";
			}
			else
			{
				$session['host'] = "";
			}
			if ($session['userid'])
			{
				$session['username'] = "<a href='profile.php{$forums->sessionurl}u=" . $session['userid'] . "'>" . $session['opentag'] . $session['username'] . $session['closetag'] . "</a>" . $invisible . " " . $session['host'] . "";
			}
			$session['lastactivity'] = $forums->func->get_date($session['lastactivity'], 2);
			if ($session['username'] AND $session['userid'])
			{
				$session['msg_icon'] = 1;
			}
			else
			{
				if ($session['username'])
				{
					$session['username'] = $session['opentag'] . $session['username'] . $session['closetag'] . " " . $session['host'];
				}
				else
				{
					$session['username'] = $session['opentag'] . $forums->lang['guest'] . $session['closetag'] . " " . $session['host'];
				}
				$session['msg_icon'] = 0;
			}
			$userlist[] = $session;
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['onlinelist'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['onlinelist']);
		include $forums->func->load_template('online_list');
		exit;
	}

	function online_check($fid)
	{
		global $forums;
		$deny = true;
		if ($forums->func->fetch_permissions($forums->forum->foruminfo[$fid]['canshow'], 'canshow') == true)
		{
			$deny = false;
		}
		if ($deny == false)
		{
			if ($forums->forum->foruminfo[$fid]['password'])
			{
				if ($forums->forum->check_password($fid) == true)
				{
					$deny = false;
				}
				else
				{
					$deny = true;
				}
			}
		}
		return $deny;
	}
}

$output = new online();
$output->show();

?>