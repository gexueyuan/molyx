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
# $Id: index.php 189 2007-09-26 16:09:10Z sancho $
# **************************************************************************#
define('THIS_SCRIPT', 'index');
require ('./global.php');

$welcome[] = sprintf($forums->lang['welcome'], ($bbuserinfo['id'] ? $bbuserinfo['name'] : $forums->lang['guest']));
if (!$_GET['bbuid'] || !$_GET['bbpwd'])
{
	$welcome[] = $forums->lang['logprompt'];
}
$welcome = convert(implode("<br />", $welcome));
$bboptions['bbtitle'] = convert($bboptions['bbtitle']);

$foruminfo[] = $forums->lang['forum_list'];

foreach ($forums->forum->forum_cache['root'] as $id => $cat_data)
{
	if (is_array($forums->forum->forum_cache[ $cat_data['id'] ]))
	{
		$cat_data['name'] = strip_tags($cat_data['name']);
		$foruminfo[] = "<a href='forum.php{$forums->sessionurl}f={$cat_data['id']}' title='{$cat_data['name']}'>{$cat_data['name']}</a>";
	}
}
$forum_info = implode("<br />", convert($foruminfo));
$otherinfo[] = $forums->lang['other_info'];
$mythread[] = "<a href='search.php{$forums->sessionurl}do=getnew' title='{$forums->lang['todaypost']}'>{$forums->lang['todaypost']}</a>";

if (!$bbuserinfo['id'])
{
	$mythread = '';
	$otherinfo[] = "<a href='login.php{$forums->sessionurl}' title='{$forums->lang['login']}'>{$forums->lang['login']}</a>";
	$otherinfo[] = "<a href='register.php{$forums->sessionurl}' title='{$forums->lang['registeraccount']}'>{$forums->lang['registeraccount']}</a>";
}
else
{
	$forums->lang['mythread'] = convert($forums->lang['mythread']);
	$mythread[] = "<a href='search.php{$forums->sessionurl}do=finduserthread&amp;u={$bbuserinfo['id']}'>{$forums->lang['mythread']}</a>";
	$otherinfo[] = "<a href='pm.php{$forums->sessionurl}' title='{$forums->lang['pm']}'>{$forums->lang['pm']}</a>";
	$otherinfo[] = "<a href='search.php{$forums->sessionurl}' title='{$forums->lang['search']}'>{$forums->lang['search']}</a>";
	$otherinfo[] = "<a href='login.php{$forums->sessionurl}do=logout' title='{$forums->lang['logout']}'>{$forums->lang['logout']}</a>";
}
if (is_array($mythread))
{
	$mythread = "<p>" . implode("<br />", convert($mythread)) . "</p>";
}

$otherinfo[] = "<a href='announce.php{$forums->sessionurl}' title='{$forums->lang['announcement']}'>{$forums->lang['announcement']}</a>";
$other_info = implode("<br />", convert($otherinfo));

if ($bboptions['showstatus'])
{
	$show['stats'] = true;
	$totalthreads = fetch_number_format($forums->forum->total['thread']);
	$totalposts = fetch_number_format($forums->forum->total['post']);
	$todaypost = fetch_number_format($forums->forum->total['todaypost']);

	$forums->func->check_cache('stats');
	$numbermembers = fetch_number_format($forums->cache['stats']['numbermembers']);

	$statusinfo[] = $forums->lang['status_info'];
	$statusinfo[] = $forums->lang['totalthreads'] . ": " . $totalthreads;
	$statusinfo[] = $forums->lang['totalposts'] . ": " . $totalposts;
	$statusinfo[] = $forums->lang['totalmembers'] . ": " . $numbermembers;

	if ($bboptions['showloggedin'])
	{
		$cutoff = $bboptions['cookietimeout'] != "" ? $bboptions['cookietimeout'] : '15';
		$time = TIMENOW - $cutoff * 60;
		$online = $DB->query_first("SELECT COUNT(sessionhash) AS users FROM " . TABLE_PREFIX . "session WHERE lastactivity > $time");
		$statusinfo[] = sprintf($forums->lang['onlinemembers'], $online['users']);
	}
	$status_info = implode("<br />", convert($statusinfo));
}

include $forums->func->load_template('wap_index');
exit;
?>