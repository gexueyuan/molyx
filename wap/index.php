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

	cache::get('stats');
	$numbermembers = fetch_number_format($forums->cache['stats']['numbermembers']);

	$statusinfo[] = $forums->lang['status_info'];
	$statusinfo[] = $forums->lang['totalthreads'] . ": " . $totalthreads;
	$statusinfo[] = $forums->lang['totalposts'] . ": " . $totalposts;
	$statusinfo[] = $forums->lang['totalmembers'] . ": " . $numbermembers;

	if ($bboptions['showloggedin'])
	{
		$cutoff = $bboptions['cookietimeout'] != "" ? $bboptions['cookietimeout'] : '15';
		$time = TIMENOW - $cutoff * 60;
		$online = $DB->queryFirst("SELECT COUNT(sessionhash) AS users FROM " . TABLE_PREFIX . "session WHERE lastactivity > $time");
		$statusinfo[] = sprintf($forums->lang['onlinemembers'], $online['users']);
	}
	$status_info = implode("<br />", convert($statusinfo));
}

include $forums->func->load_template('wap_index');
exit;
?>