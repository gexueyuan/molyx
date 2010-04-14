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
define('IN_MXB', true);
define('ROOT_PATH', './../');
ob_start();
if (!preg_match('/mozilla/i', $_SERVER['HTTP_USER_AGENT']))
{
	@header("Content-Type: text/vnd.wap.wml;charset=UTF-8");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
}

function convert($data = '')
{
	global $forums;
	if (is_array($data))
	{
		foreach ($data as $v)
		{
			$v = convert_andstr($v);
			$newdata[] = $v;
		}
	}
	else
	{
		$data = convert_andstr($data);
		$newdata = $data;
	}
	unset($data);
	return $newdata;
}
require_once(ROOT_PATH . 'includes/init.php');
require_once(ROOT_PATH . 'includes/functions.php');

$forums->func = new functions();
$forums->func->check_cache('settings');
$bboptions = $forums->cache['settings'];
$forums->func->check_cache('style');

$forums->sessionurl = '?';
$forums->si_sessionurl = '';

require_once(ROOT_PATH . 'wap/sessions.php');
require_once(ROOT_PATH . 'includes/functions_forum.php');
$forums->forum = new functions_forum();
$_INPUT = init_input();
$forums->lang['returnindex'] = convert($forums->lang['returnindex']);
$forums->url = REFERRER;

check_lang();
$forums->func->load_lang('wap');

$session = new session();
$bbuserinfo = $session->loadsession();
$forums->func->load_style();

if (preg_match('/mozilla/i', $_SERVER['HTTP_USER_AGENT']))
{
	@header("Content-Type: text/html;charset=UTF-8");
	$forums->func->load_lang('global');
	$forums->func->load_lang('error');
	$forums->lang_list = $forums->func->generate_lang();
	$forums->style_list = $forums->func->generate_style();
	$message = $forums->lang['wapusemobile'];
	$message = sprintf($message, $bboptions['bburl'] . '/wap/index.php');
	list($user, $domain) = explode('@', $bboptions['emailreceived']);
	$safe_string = str_replace('&amp;', '&', clean_value(SCRIPT));
	$nav = array($forums->lang['errorsinfo']);
	$pagetitle = $forums->lang['errorsinfo'] . ' - ' . $bboptions['bbtitle'];
	include $forums->func->load_template('errors_index');
	$buffer = ob_get_contents();
	ob_end_clean();
	@ob_start('ob_gzhandler');
	$buffer = preg_replace('/(action|href|src|background)=(\'|"|)(\.\/|)(.+?)(\\2)/ie', "parse_hrperlink('\\1', '\\4', './../')", $buffer);
	echo $buffer;
	exit;
}

define('IS_WAP' , true);

function generate_seed($length = 5)
{
	$seed = '';
	for ($i = 0; $i < $length; $i++)
	{
		$seed .= chr(mt_rand(97, 122));
	}
	return $seed;
}

function parse_hrperlink($script = '', $action = '', $root = './../')
{
	if (strpos('./' . $action, $root) === 0 || strpos($action, $root) === 0 || preg_match("/^(javascript)/i", $action))
	{
		return $script . "='" . $action . "'";
	}

	return $script . "='" . $root . $action . "'";
}

if ($forums->sessiontype == 'cookie')
{
	$forums->sessionid = '';
	$forums->sessionurl = '?';
}
else
{
	$forums->sessionid = $session->sessionid;
	$forums->sessionurl = '?s=' . $forums->sessionid . '&amp;';
}
$seed = generate_seed();
$forums->sessionurl .= 'seed=' . $seed . '&amp;';
if ($_INPUT['pwd'])
{
	$forums->sessionurl .= 'pwd=' . $_INPUT['pwd'] . "&amp;";
}
if (THIS_SCRIPT != 'login' && THIS_SCRIPT != 'register')
{
	if (!$bbuserinfo['canview'])
	{
		$forums->func->load_lang('error');
		$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
		$contents = convert($forums->lang['cannotviewboard']);
		include $forums->func->load_template('wap_info');
		exit;
	}
	if (! $bboptions['bbactive'])
	{
		if (!$bbuserinfo['canviewoffline'])
		{
			$row = $DB->query_first("SELECT *
				FROM " . TABLE_PREFIX . "setting
				WHERE varname = 'bbclosedreason'");
			$message = str_replace("\n", '<br />', $row['value']);
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($message);
			include $forums->func->load_template('wap_info');
			exit;
		}
	}
	if (!$bbuserinfo['id'] && $bboptions['forcelogin'])
	{
		require_once(ROOT_PATH . 'wap/login.php');
		$output = new login();
		$output->show();
	}
}
if ($_GET['bbuid'] && $_GET['bbpwd'])
{
	if ($bbuserinfo['id'])
	{
		$forums->sessionurl .= 'bbuid=' . $_GET['bbuid'] . "&amp;bbpwd=" . rawurldecode($_GET['bbpwd']) . "&amp;";
	}
}
if ($bbuserinfo['pmunread'] > 0 AND THIS_SCRIPT != 'private')
{
	$message = sprintf($forums->lang['pmunread'], $bbuserinfo['pmunread']);
	$message .= "<br /><a href='pm.php{$forums->sessionurl}do=list&amp;folderid=0'>" . $forums->lang['viewpm'] . "</a>";
	$message .= "<br /><a href='pm.php{$forums->sessionurl}do=ignorepm'>" . $forums->lang['ignorepm'] . "</a>";
	$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
	$contents = convert($message);
	include $forums->func->load_template('wap_info');
	exit;
}

$forums->forum->forums_init();
list($maxthreads, $maxposts) = explode('&', $bbuserinfo['viewprefs']);
$bboptions['maxthreads'] = ($maxthreads > 0) ? $maxthreads : $bboptions['maxthreads'];
$bboptions['maxposts'] = ($maxposts > 0) ? $maxposts : $bboptions['maxposts'];

function redirect($url = '', $text = '')
{
	global $forums;
	$forums->lang['redirect'] = convert($forums->lang['redirect']);
	if ($text)
	{
		$text = convert($text);
	}
	else
	{
		$text = convert($forums->lang['actiondone']);
	}
	$forums->lang['redirectpage'] = convert($forums->lang['redirectpage']);
	$redirect = "<a href='{$url}'>{$forums->lang['redirectpage']}</a><br />\n";
	$redirect .= "<a href='index.php{$forums->sessionurl}'>{$forums->lang['returnindex']}</a>\n";
	$timer = 3;
	include $forums->func->load_template('wap_redirect');
	exit;
}

function check_lang()
{
	global $forums, $bboptions, $_INPUT;

	if (isset($_INPUT['lang']) && $_INPUT['lang'])
	{
		$bboptions['language'] = $_INPUT['lang'];
	}
	if ($bboptions['language'] == 1 || !$bboptions['language'])
	{
		$forums->func->set_cookie('language', '');
		$bboptions['language'] = 'zh-cn';
	}
	$bboptions['language'] = $bboptions['language'] ? $bboptions['language'] : ($bboptions['default_lang'] ? $bboptions['default_lang'] : 'zh-cn');
	$forums->sessionurl .= 'lang=' . $bboptions['language'] . '&amp;';
}

function check_password($fid, $prompt_login = 0, $in = 'forum')
{
	global $forums;
	$deny_access = true;
	if ($forums->func->fetch_permissions($forums->forum->foruminfo[$fid]['canshow'], 'canshow') == true)
	{
		if ($forums->func->fetch_permissions($forums->forum->foruminfo[$fid]['canread'], 'canread') == true)
		{
			$deny_access = false;
		}
		else
		{
			if ($forums->forum->foruminfo[$fid]['showthreadlist'])
			{
				if ($in == 'forum')
				{
					$deny_access = false;
				}
				else
				{
					forums_custom_error($fid);
					$deny_access = true;
				}
			}
			else
			{
				forums_custom_error($fid);
				$deny_access = true;
			}
		}
	}
	else
	{
		forums_custom_error($fid);
		$deny_access = true;
	}
	if (!$deny_access)
	{
		if ($forums->forum->foruminfo[$fid]['password'])
		{
			if (check_forumpwd($fid) == true)
			{
				$deny_access = false;
			}
			else
			{
				$deny_access = true;
				if ($prompt_login == 1)
				{
					forums_show_login($fid);
				}
			}
		}
	}
	else
	{
		$forums->func->load_lang('error');
		$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
		$contents = convert($forums->lang['cannotviewboard']);
		include $forums->func->load_template('wap_info');
		exit;
	}
}

function check_forumpwd($fid)
{
	global $forums;
	$forum_password = $_INPUT['pwd'];
	if (trim($forum_password) == $forums->forum->foruminfo[$fid]['password']) return true;
	else return false;
}

function forums_custom_error($forumid)
{
	global $forums, $DB;
	$error = $DB->query_first("SELECT customerror FROM " . TABLE_PREFIX . "forum WHERE id = $forumid");
	if ($error['customerror'])
	{
		$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
		$contents = convert($error['customerror']);
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "session SET badlocation=1 WHERE sessionhash='" . $forums->sessionid . "'");
		include $forums->func->load_template('wap_info');
		exit;
	}
}

function forums_show_login($forumid)
{
	global $forums, $DB, $bbuserinfo, $bboptions;
	if (empty($bbuserinfo['id']))
	{
		$forums->func->load_lang('error');
		$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
		$contents = convert($forums->lang['notlogin']);
		include $forums->func->load_template('wap_info');
		exit;
	}
	$forumname = convert($forums->forum->foruminfo[$forumid]['name']);
	$forums->lang['password'] = convert($forums->lang['password']);
	$forums->lang['login'] = convert($forums->lang['login']);
	include $forums->func->load_template('wap_forum_password');
	exit;
}
?>