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
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'index');
require('./global.php');

$forums->forum->forums_init(0);
$forums->func->load_lang('index');

if ($bbuserinfo['id'])
{
	//积分
	$forums->func->check_cache('creditlist');
	if (is_array($forums->cache['creditlist']))
	{
		$expand_credit = "";
		foreach ($forums->cache['creditlist'] as $creditid => $v)
		{
			if (!$v['used']) continue;
			$expand_credit .= $v['name'] . ": " . intval($bbuserinfo[$v['tag']]) . $v['unit'] . " / ";
		}
	}
	$expand_credit = substr($expand_credit, 0, -3);
	$lastvisit = $forums->func->get_date($bbuserinfo['lastvisit'], 2);
}

// 论坛信息
$forums->func->check_cache('stats');
if ($bboptions['showstatus'])
{
	$show['stats'] = true;
	$totalthreads = fetch_number_format($forums->forum->total['thread']);
	$totalposts = fetch_number_format($forums->forum->total['post']);
	$todaypost = fetch_number_format($forums->forum->total['todaypost']);
	$numbermembers = fetch_number_format($forums->cache['stats']['numbermembers']);
}
$newuserid = $forums->cache['stats']['newuserid'];
$newusername = $forums->cache['stats']['newusername'];

// 显示会员在线信息
if ($bboptions['showloggedin'])
{
	$show['stats'] = true;
	$online = array(
		'guests' => 0,
		'users' => 0,
		'username' => array(),
		'invisible' => 0,
	);
	$maxonline = fetch_number_format($forums->cache['stats']['maxonline']);
	$maxonlinedate = $forums->func->get_date($forums->cache['stats']['maxonlinedate'], 2);
	$cutoff = $bboptions['cookietimeout'] != '' ? $bboptions['cookietimeout'] : '15';
	$oltime = TIMENOW - $cutoff * 60;

	// 当前访问者的资料
	$this_user = array(
		'invisible' => isset($bbuserinfo['invisible']) ? $bbuserinfo['invisible'] : 0,
		'lastactivity' => TIMENOW,
		'userid' => $bbuserinfo['id'],
		'username' => $bbuserinfo['name'],
		'avatar' => $bbuserinfo['avatar'],
		'usergroupid' => $bbuserinfo['usergroupid']
	);

	//用户组列表
	$usergroups = array();
	$forums->func->check_cache('usergroup');
	$usergroups = $forums->cache['usergroup'];
	foreach ($usergroups as $groupid => $info)
	{
		$usergroups[$groupid]['grouptitle'] = $forums->lang[$usergroups[$groupid]['grouptitle']];
		$usergroups[$groupid]['groupranks'] = $forums->lang[$usergroups[$groupid]['groupranks']];
	}

	// 检查隐藏和显示在线列表的设置
	if (isset($_INPUT['online']))
	{
		switch ($_INPUT['online'])
		{
			case 'hide':
				$forums->func->set_cookie('online', '1');
				$hideonline = 1;
			break;

			case 'show':
				$forums->func->set_cookie('online', '0', -1);
				$hideonline = 0;
			break;
		}
	}
	else
	{
		$hideonline = $forums->func->get_cookie('online');
	}

	$totalonline = 0;
	if (!$hideonline)
	{
		$sql = "SELECT sessionhash, userid, username, invisible, lastactivity, usergroupid, mobile, avatar
			FROM " . TABLE_PREFIX . "session
			WHERE lastactivity > $oltime
			ORDER BY userid DESC";
		$result = $DB->query($sql);
		$totalonline += $DB->num_rows($result);

		// 在线人数超出系统限制则隐藏在线列表
		if (defined('MAX_ONLINE_USERS') && $bboptions['maxonlineusers'] > MAX_ONLINE_USERS)
		{
			$bboptions['maxonlineusers'] = MAX_ONLINE_USERS;
		}

		if ($totalonline > $bboptions['maxonlineusers'] && !isset($_INPUT['online']))
		{
			$hideonline = 1;
			$forums->func->set_cookie('online', '1');
		}
		else
		{
			$forums->func->check_cache('usergroup');

			/**
			 * 根据会员资料设置在线列表资料和更新在线人数信息
			 *
			 * @param array $user 会员信息数组
			 * @param array $online 在线列表统计数组
			 * @return boolean
			 */
			function count_online(&$user, &$online)
			{
				global $forums, $bbuserinfo;
				static $cached = array();

				$user['lastactivity'] = $forums->func->get_time($user['lastactivity']);
				// 注册会员
				if ($user['userid'] != 0)
				{
					// 判断该会员是否已经处理过
					if (!isset($cached[$user['userid']]))
					{
						$cached[$user['userid']] = true;

						$user['opentag'] = $forums->cache['usergroup'][$user['usergroupid']]['opentag'];
						$user['closetag'] = $forums->cache['usergroup'][$user['usergroupid']]['closetag'];
						if ($forums->cache['usergroup'][$user['usergroupid']]['onlineicon'])
						{
							$user['usericon'] = "<img src='{$forums->cache['usergroup'][$user['usergroupid']]['onlineicon']}' border='0' alt='' />";
						}
						else
						{
							$user['usericon'] = $forums->func->get_avatar($user['userid'], $user['avatar'], 2);
						}
						$user['mobile'] = (isset($user['mobile']) && $user['mobile']) ? 1 : 0;

						// 隐身会员
						if ($user['invisible'])
						{
							if ($bbuserinfo['usergroupid'] == 4)
							{
								$user['show_icon'] = 1;
								$online['username'][] = $user;
							}
							$online['invisible']++;
						}
						else
						{
							$online['username'][] = $user;
						}

						$online['users']++;
						return true;
					}
					else
					{
						return false;
					}
				}
				else // 游客
				{
					$online['username'][] = array(
						'userid' => 0,
						'usericon' => $forums->func->get_avatar(0, 0, 2),
						'mobile' => ($user['mobile']) ? 1 : 0,
						'username' => $forums->lang['_guset']
					);
					if ($forums->cache['usergroup'][$user['usergroupid']]['onlineicon'])
					{
						$user['usericon'] = "<img src='{$forums->cache['usergroup'][$user['usergroupid']]['onlineicon']}' border='0' alt='' />";
					}
					return true;
				}
			}

			// 当前访问者为注册会员时作为第一个处理
			if ($this_user['userid'] > 0)
			{
				count_online($this_user, $online);
			}

			if (!$this_user['userid'] || $totalonline > 1)
			{
				while ($row = $DB->fetch_array($result))
				{
					// userid 为 0 表示会员信息已经提取完成, 不显示游客的话不需要继续处理游客信息
					if (!$bboptions['showguest'] && $row['userid'] == 0)
					{
						break;
					}
					count_online($row, $online);
				}
			}

			if ($totalonline < $online['users'])
			{
				$totalonline = $online['users'];
				$online['guests'] = 0;
			}
			else
			{
				$online['guests'] = $totalonline - $online['users'];
			}

			$forums->lang['onlineusers'] = sprintf($forums->lang['onlineusers'], $online['guests'], $online['users'], $online['invisible'], $maxonline, $maxonlinedate);
		}
		$DB->free_result($result);
	}
	else // 隐藏在线列表只查询当前在线人数
	{
		$sql = "SELECT COUNT(sessionhash) AS count
			FROM " . TABLE_PREFIX . "session
			WHERE lastactivity > $oltime";
		$totalonline = $DB->query_first($sql);
		$totalonline = $totalonline['count'];
	}
	$forums->lang['onlineclosed'] = sprintf($forums->lang['onlineclosed'], $totalonline, $maxonline, $maxonlinedate);
}

// 当前在线人数大于历史最大在线人数时更新记录
if ($totalonline > $forums->cache['stats']['maxonline'])
{
	$DB->update_cache(array(
		array('maxonline', $totalonline),
		array('maxonlinedate', TIMENOW),
	));
	$forums->cache['stats']['maxonline'] = $totalonline;
	$forums->cache['stats']['maxonlinedate'] = TIMENOW;
	$forums->func->update_cache(array('name' => 'stats'));
}

// 显示今日过生日的会员
if ($bboptions['showbirthday'])
{
	$show['stats'] = true;
	$birthusers = '';
	$bcount = 0;
	$forums->func->check_cache('birthdays');
	if (is_array($forums->cache['birthdays']) && $forums->cache['birthdays'])
	{
		$today = $forums->func->get_time(TIMENOW, 'Y');
		foreach ($forums->cache['birthdays'] as $id => $user)
		{
			$avatar = $forums->func->get_avatar($user['id'], $user['avatar'], 2);
			$birthusers .= "<li><a href=\"profile.php{$forums->sessionurl}u={$user['id']}\">{$avatar}{$user['name']}</a>";
			$year = explode('-', $user['birthday']);
			if ($year[0] != '0000')
			{
				$birthusers .= '(<em>' . ($today - $year[0]) . '</em>)';
			}
			$birthusers .= '</li>';
			$bcount++;
		}
	}
	unset($forums->cache['birthdays']);

	$show['birthday'] = true;
	if ($bcount < 1)
	{
		$show['birthday'] = false ;
	}
	$birthusers = trim($birthusers);
	if (strrchr($birthusers, ',') == ',')
	{
		$birthusers = substr($birthusers, 0, -1);
	}
	$forums->lang['todaybirthdays'] = sprintf($forums->lang['todaybirthdays'], $bcount);
}

//推荐主题
if ($bboptions['top_digg_thread_num'])
{
	$forums->func->check_cache('top_digg_thread');
	$top_digg_thread = $forums->cache['top_digg_thread'];
	if ($top_digg_thread)
	{
		foreach ($top_digg_thread as $tid => $thread)
		{
			$top_digg_thread[$tid]['digg_users'] = sprintf($forums->lang['how_digg_users'], intval($thread['digg_users']));
			$top_digg_thread[$tid]['digg_exps'] = intval($thread['digg_exps']);
			$top_digg_thread[$tid]['cuttitle'] = $forums->func->fetch_trimmed_title(strip_tags($thread['title']), 20);
			$top_digg_thread[$tid]['dateline'] = $forums->func->get_date($thread['dateline'], 2);
			$top_digg_thread[$tid]['lastpost'] = $forums->func->get_date($thread['lastpost'], 4);
			$top_digg_thread[$tid]['avatar'] = $forums->func->get_avatar($thread['postuserid'], $thread['avatar'], 2);
		}
	}
}

// 联盟论坛(友情链接)
$forums->func->check_cache('league');
$league = $forums->cache['league'];

require_once(ROOT_PATH . 'includes/ajax/ajax.php');
$rsslink = true; // 显示RSS自动识别
$pagetitle = $bboptions['bbtitle'];
include $forums->func->load_template('index');
?>