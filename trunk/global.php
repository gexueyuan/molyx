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
define('IN_MXB', true);
define('ROOT_PATH' , './');
require_once(ROOT_PATH . 'includes/init.php');
if (!isset($content_type))
{
	header('Content-Type:text/html; charset=UTF-8');
}

$forums = new stdClass();
$forums->noheader = 0;
$forums->forum_read = $forums->lang = array();
$forums->ads = null;
$forums->url = REFERRER;

require_once(ROOT_PATH . 'includes/functions.php');
$forums->func = new functions();

$_INPUT = init_input();

if (USE_SHUTDOWN && THIS_SCRIPT != 'cron')
{
	register_shutdown_function(array(&$forums->func, 'do_shutdown'));
}

$forums->func->check_cache('cron');
if (TIMENOW >= $forums->cache['cron'])
{
	define('CRON', '<img src="' . ROOT_PATH . 'cron.php" border="0" height="1" width="1" alt="" />');
}
else
{
	define('CRON', '');
}

$forums->func->check_cache('settings');
$bboptions = &$forums->cache['settings'];
$bboptions['mxemode'] = intval($bboptions['mxemode']);
$bboptions['quickeditordisplaymenu'] = intval($bboptions['quickeditordisplaymenu']);
$forums->func->check_lang();
$page_cache = null;

$forums->func->load_lang('global');
$forums->func->load_lang('init');

require_once(ROOT_PATH . 'includes/sessions.php');
$session = new session();
$bbuserinfo = $session->loadsession();

if (defined('GUEST_PAGE_CACHE') && GUEST_PAGE_CACHE && !$bbuserinfo['id'])
{
	require_once(ROOT_PATH . 'includes/page_cache.php');
}
$forums->func->check_cache('announcement');
$forums->func->check_cache('style');
require_once(ROOT_PATH . 'includes/functions_forum.php');
$forums->forum = new functions_forum();

$forums->func->load_style();
$bbuserinfo['timenow'] = $forums->func->get_time(TIMENOW, 'H:i');

if ($forums->sessiontype == 'cookie')
{
	$forums->sessionid = '';
	$forums->sessionurl = '?';
	$forums->si_sessionurl = '';
}
else
{
	$forums->sessionid = $session->sessionid;
	$forums->sessionurl = '?s=' . $forums->sessionid . '&amp;';
	$forums->si_sessionurl = '?s=' . $forums->sessionid;
}
$forums->js_sessionurl = 's=' . $forums->sessionid . '&';
if (THIS_SCRIPT != 'login' && THIS_SCRIPT != 'register' && THIS_SCRIPT != 'cron')
{
	if (!$bbuserinfo['canview'])
	{
		$forums->func->standard_error('cannotviewboard');
	}
	if (!$bboptions['bbactive'])
	{
		if (!$bbuserinfo['canviewoffline'])
		{
			$forums->func->load_lang('error');
			$row = $DB->query_first("SELECT *
				FROM " . TABLE_PREFIX . "setting
				WHERE varname = 'bbclosedreason'");
			$message = nl2br($row['value'] ? $row['value'] : $row['defaultvalue']);
			$pagetitle = $forums->lang['_closed'] . ' - ' . $bboptions['bbtitle'];
			$nav = array($forums->lang['_closed']);
			include $forums->func->load_template('errors_index');
			exit();
		}
	}
	if (!$bbuserinfo['id'] && $bboptions['forcelogin'])
	{
		require_once(ROOT_PATH . "login.php");
	}
}

$maxthreads = $maxposts = 0;
if (isset($bbuserinfo['viewprefs']) && $bbuserinfo['viewprefs'])
{
	list($maxthreads, $maxposts) = explode('&', $bbuserinfo['viewprefs']);
}
$bboptions['maxthreads'] = ($maxthreads > 0) ? $maxthreads : (isset($bboptions['maxthreads']) ? intval($bboptions['maxthreads']) : 0);
$bboptions['maxposts'] = ($maxposts > 0) ? $maxposts : (isset($bboptions['maxposts']) ? intval($bboptions['maxposts']) : 0);

$forums->forum->forumread();

$bboptions['uploadurl'] = $bboptions['uploadurl'] ? $bboptions['uploadurl'] : $bboptions['bburl'] . '/data/uploads';
$bboptions['uploadfolder'] = $bboptions['uploadfolder'] ? $bboptions['uploadfolder'] : ROOT_PATH . 'data/uploads';

$forums->lang_list = $forums->func->generate_lang();
$forums->style_list = $forums->func->generate_style();
add_head_element('js-c', 'var current_page = "' . $_INPUT['pp'] . '";
var cookie_id = "' . $bboptions['cookieprefix'] . '";
var cookie_domain = "' . $bboptions['cookiedomain'] . '";
var cookie_path = "' . $bboptions['cookiepath'] . '";
var qmxemenu = "' . $bboptions['quickeditordisplaymenu'] . '";
var imageurl = "' . $bbuserinfo['imgurl'] . '";
var sessionid = "' . $forums->sessionid . '";');
add_head_element('js', $forums->func->load_lang_js('global'));
add_head_element('js', $forums->func->load_lang_js('ajax'));
add_head_element('js', ROOT_PATH . 'scripts/global.js');
add_head_element('js', ROOT_PATH . 'scripts/mxajax.js');
?>