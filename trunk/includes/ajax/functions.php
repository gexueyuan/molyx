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
/**
 * 会员修改当前在干什么
 *
 * @param string $do
 * @return ajaxresponse
 */
function changemedo($do = '')
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	$do = init_input(array('d' => $do));
	$do = $do['d'];
	$userdo = str_replace(array("\n", '<br />'), ' ', trim($do));
	if (!$bbuserinfo['id'])
	{
		show_processinfo($forums->lang['notlogin']);
		return $response;
	}
	// !$userdo 起始状态为空,新状态为空--相等的特例
	if ($bbuserinfo['usercurdo'] == $userdo || (!$bbuserinfo['userdotime'] && !$userdo))
	{
		edit_do_relust($bbuserinfo['userdo'], $bbuserinfo['userdotime']);
		return $response;
	}
	if ($userdo == '') //清空当前在做什么
	{
		$DB->update(TABLE_PREFIX . 'user', array('usercurdo' => '', 'userdotime' => 0), 'id = ' . intval($bbuserinfo['id']));
		// to be move to language
		edit_do_relust($forums->lang['notfilldowhat'], '');
		show_processinfo($forums->lang['cleardosuc']);
		return $response;
	}
	if (utf8_strlen($userdo) > intval($bboptions['userdolenlimit'])) //长度超过限制
	{
		show_processinfo($forums->lang['userdolimited']);
		return $response;
	}

	require_once(ROOT_PATH . 'includes/functions_codeparse.php');
	$lib = new functions_codeparse();
	$userdo = $lib->censoredwords($userdo);
	if (substr($userdo, 0, 1) == '@') //发向其他用户
	{
		$usrnamepos = strpos($userdo, ' ');
		$tousername = substr($userdo, 1, $usrnamepos - 1);
		if ($tousername != $bbuserinfo['name'])
		{
			$touser = $DB->query_first('SELECT id, name FROM ' . TABLE_PREFIX . "user
										WHERE LOWER(name) = '" . strtolower($tousername) . "'"
			);
		}
	}
	if ($touser) //短信通知
	{
		$userdo = substr($userdo, $usrnamepos + 1);
		$_INPUT['title'] = sprintf($forums->lang['tomsgtitle'], $bbuserinfo['name']);
		$_POST['post'] = $userdo;
		$_INPUT['username'] = $tousername;
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$pm = new functions_private();
		$_INPUT['noredirect'] = 1;
		$bboptions['usewysiwyg'] = 1;
		$bboptions['pmallowhtml'] = 1;
		$pm->sendpm();
	}
	else
	{
		$DB->update(TABLE_PREFIX . 'user', array('usercurdo' => $userdo, 'userdotime' => TIMENOW), 'id = ' . intval($bbuserinfo['id']));
	}
	$DB->insert(TABLE_PREFIX . 'userdo', array(
		'userid' => $bbuserinfo['id'],
		'time' => TIMENOW,
		'dowhat' => $userdo,
		'touserid' => intval($touser['id']),
		'tousername' => $tousername,
	));
	if (!$touser)
	{
		edit_do_relust($userdo, $forums->func->get_date(TIMENOW));
		return $response;
	}
	else
	{
		edit_do_relust($bbuserinfo['userdo'], $bbuserinfo['userdotime']);
		$sucmsg = sprintf($forums->lang['sendmsgsucc'], $tousername);
		show_processinfo($sucmsg);
		return $response;
	}
}

/**
 * 修改结果向页面元素usercurdo进行反馈
 *
 * @param string $do
 * @param unknown_type $time
 */
function edit_do_relust($do, $time = '')
{
	global $response;
	$response->assign('usercurdo', 'innerHTML', '<cite>' . $time . '</cite><em>' . $do. '</em>');

	$response->assign('usercurdo', 'title', $do);
	$response->addHandler('usercurdo', 'onclick', 'changemedotext');
}

/**
 * 操作提示在页面show_process进行反馈，定时消失
 *
 * @param string $msg
 */
function show_processinfo($msg = '')
{
	global $response;
	if ($msg)
	{
		$response->assign('show_process', 'innerHTML', $msg);
		$response->script('$("show_process").style.display = "inline";');
		$response->script('$("show_process").style.top = getScrollY() + "px";');
		$response->script('setTimeout(\'$("show_process").style.display = "none";\', 3000);');
	}
}

function switch_editor_mode($content, $mode, $type, $allowsmile)
{
	global $forums, $bboptions, $bbuserinfo, $response;
	if ($type == 'signature')
	{
		$allowcode = intval($bboptions['signatureallowbbcode']);
		$allowhtml = intval($bboptions['signatureallowhtml']);
	}
	else if ($type == 'pm')
	{
		$allowcode = intval($bboptions['pmallowbbcode']);
		$allowhtml = intval($bboptions['pmallowhtml']);
	}
	else
	{
		$fid = intval($type);
		if ($fid > 0)
		{
			$forum = $forums->forum->single_forum($fid);
			$allowcode = $forum['allowbbcode'];
			$allowhtml = ($forum['allowhtml'] && $bbuserinfo['canposthtml']);
		}
		else
		{
			$allowcode = 1;
			$allowhtml = 0;
		}
	}

	require_once(ROOT_PATH . 'includes/functions_codeparse.php');
	$lib = new functions_codeparse();

	if ($mode == 0)
	{
		$content = $lib->convert(array (
			'text' => utf8_htmlspecialchars($content),
			'allowsmilies' => intval($allowsmile),
			'allowcode' => $allowcode,
			'change_editor' => 1,
		));
		if ($allowhtml)
		{
			require_once(ROOT_PATH . 'includes/class_textparse.php');
			$content = textparse::parse_html($content);
		}
		$content = str_replace(array("\r\n", "\r", "\n"), '', $content);
	}
	else
	{
		$content = $lib->unconvert($content, $allowcode, $allowhtml, 0, 1);
		$content = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies", "str_replace(array('<br />', '&lt;br /&gt;'), array('&lt;br /&gt;', '&amp;lt;br /&amp;gt;'), '[code\\1]\\2[/code]')", $content);
		$content = preg_replace("#<br.*>#siU", "\n", $content);
		$content = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies", "str_replace(array('&lt;br /&gt;', '&amp;lt;br /&amp;gt;'), array('<br />', '&lt;br /&gt;'), '[code\\1]\\2[/code]')", $content);
		$content = str_replace(array("\r\n", "\r"), "\n", $content);
	}
	$response->script('completedChangeMxeMode();');
	$response->call('cdSwitchtext', $content);
	return $response;
}
/**
 * 检查版主操作权限
 *
 * @param 何种动作权限 $prms_action
 * @param 版面 $fid
 * @return bool or array
 * 返回数组时是返回版主的管理的版面id，可以附加到条件中
 */
function check_moderate_prms($prms_action, $fid)
{
	global $bbuserinfo;
	if ($bbuserinfo['supermod'])
	{
		return true;
	}
	else
	{
		if (!$prms_action || $bbuserinfo['_moderator'][$fid][$prms_action])
		{
			return true;
		}
		else
		{
			if (!$fid && is_array($bbuserinfo['_moderator']))
			{
				$prms_fid = array();
				foreach ($bbuserinfo['_moderator'] as $key => $value)
				{
					if (isset($value[$prms_action]) && $value[$prms_action])
					{
						$prms_fid[] = $key;
					}
				}
				return $prms_fid;
			}
		}
	}
	return false;
}

function add_moderate_log($action = 'Unknown', $title = '')
{
	global $_INPUT, $mod_func;
	$mod_func->add_moderate_log($_INPUT['f'], $_INPUT['tid'], $_INPUT['p'], $title, $action);
}

function add_thread_log($tids, $action = 'Unknown')
{
	global $DB, $forums, $bbuserinfo;
	if (is_array($tids))
	{
		$uptid = "tid IN (" . implode(",", $tids) . ")";
	}
	else if (count($tids) == 1)
	{
		$uptid = "tid=" . intval($tids);
	}
	else
	{
		return;
	}
	$timenow = $forums->func->get_date(TIMENOW , 2, 1);
	$threadlog = sprintf($forums->lang['threadlog'], $bbuserinfo['name'], $timenow, $action);
	$DB->shutdown_update(TABLE_PREFIX . 'thread', array('logtext' => $threadlog), $uptid);
}

function forum_recount($fid = '')
{
	global $_INPUT, $mod_func;
	$forumid = $fid ? $fid : $_INPUT['f'];
	if(!$mod_func)
	{
		require_once(ROOT_PATH . "includes/functions_moderate.php");
		$mod_func = new modfunctions();
	}
	$mod_func->forum_recount($forumid);
}

function list_forums($override = 0)
{
	global $forums, $_INPUT;
	$foruminfo = $forums->cache['forum'];

	foreach((array) $foruminfo as $forum)
	{
		if (($forum['canshow'] != '*' && $forums->func->fetch_permissions($forum['canshow'], 'canshow') != true) || $forum['url'])
		{
			continue;
		}
		if ($override == 1)
		{
			$selected = ($_INPUT['f'] && $_INPUT['f'] == $forum['id']) ? " selected='selected'" : '';
		}
		$forum_jump .= '<option value="' . $forum['id'] . '"' . $selected . '>' . depth_mark($forum['depth'], '--') . ' ' . $forum['name'] . '</option>' . "\n";
	}
	return $forum_jump;
}


/**
 * 获取回收站版面ID, 不同身份决定其是否使用
 *
 * @return 版面ID
 */
function fetch_recycleforum()
{
	global $bbuserinfo, $bboptions;
	if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
	{
		if ($bbuserinfo['cancontrolpanel'])
		{
			$recycleforum = $bboptions['recycleforadmin'] ? $bboptions['recycleforumid'] : 0;
		}
		else if ($bbuserinfo['supermod'])
		{
			$recycleforum = $bboptions['recycleforsuper'] ? $bboptions['recycleforumid'] : 0;
		}
		else if ($bbuserinfo['is_mod'])
		{
			$recycleforum = $bboptions['recycleformod'] ? $bboptions['recycleforumid'] : 0;
		}
		else
		{
			$recycleforum = $bboptions['recycleforumid'];
		}
	}
	return $recycleforum;
}
?>