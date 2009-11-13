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
# $Id: mxajax_functions_post.php 347 2007-11-04 14:19:48Z develop_tong $
# **************************************************************************#
$mxajax_register_functions = array(
	'open_close_thread',
	'change_thread_attr',
	'change_thread_title',
	'do_change_forumrule',
); //注册ajax函数

/**
 * 开放或关闭主题
 *
 * @param 主题ID $tid
 * @param 版面ID $fid
 * @return ajaxresponse
 */
function open_close_thread($input, $postuserid, $fid = 0, $openclose = 0, $prms_fids = '')
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $response;
	if (is_array($input))
	{
		$_INPUT = init_input($input);
		$valid_tids = $input['tid'];
		$fid = intval($input['f']);
	}
	else
	{
		$valid_tids = array(intval($input));
	}

	//在没有传递主题ID参数或没有登陆，则不进行任何操作直接返回
	if (!$valid_tids || !$bbuserinfo['id'])
	{
		show_processinfo($forums->lang['choice_threadorlogout']);
		return $response;
	}

	/*检查主题开放和关闭权限
	 超级版主，可以开放和关闭主题的版主，可以自己开放和关闭主题的会员
	*/
	$can_open_close_thread = false;
	$can_batch = true;
	if ($bbuserinfo['supermod'])
	{
		$can_open_close_thread = true;
	}
	elseif ($bbuserinfo['_moderator'][$fid] && $bbuserinfo['_moderator'][$fid]['canopenclose'])
	{
		$can_open_close_thread = true;
		$extra_condition = $prms_fids;
	}
	elseif ($postuserid == $bbuserinfo['id'] && $bbuserinfo['canopenclose'])
	{
		$can_open_close_thread = true;
		$can_batch = false;
		$extra_condition = ' AND postuserid=' . $bbuserinfo['id'];
	}
	$tid_num = count($valid_tids);

	//没有权限直接返回
	if (!$can_open_close_thread || ($tid_num > 1 && !$can_batch))
	{
		show_processinfo($forums->lang['noprmsmodthread']);
		return $response;
	}
	$valid_tids = implode(',', $valid_tids);
	if ($tid_num == 1)
	{
		//更新数据库，若开放则关闭，反之关闭则开放
		$sql = 'UPDATE ' . TABLE_PREFIX . 'thread
				SET open = (CASE open WHEN 0 THEN 1 ELSE 0 END)
				WHERE tid IN (' . $valid_tids . ')' . $extra_condition;
	}
	else
	{
		$sql = 'UPDATE ' . TABLE_PREFIX . 'thread
				SET open = ' . intval($openclose) . '
				WHERE tid IN (' . $valid_tids . ')' . $extra_condition;
	}
	$DB->query($sql);
	response_op_result($valid_tids, $fid);
	$response->call('hideElement', 'operation_pannel');
	$response->clear('show_operation', 'innerHTML');
	return $response;
}

function response_op_result($valid_tids, $fid)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	//查询处理结果进行相应反馈和日志及短消息通知
	$threads = $DB->query("SELECT open, postusername, postuserid, title, tid, sticky FROM " . TABLE_PREFIX . "thread  WHERE  tid IN (" . $valid_tids . ")");

	$pm_touser = array();
	$user_thread_num = array();
	$moderator_log = array();
	while ($thread = $DB->fetch_array($threads))
	{
		if ($thread['open'] == 1) //开放主题
		{
			$pic = 'folder.gif';
			$open_close_lang = $forums->lang['openthread'];
			$_INPUT['title'] = $forums->lang['openthreadpm'];
			$pic_alt = $forums->lang['openthread'];
			$opreate_ret = $forums->lang['openthreadsuc'] . "[{$thread['title']}]";
		}
		else //关闭主题
		{
			$pic = 'closedfolder.gif';
			$pic_alt = $forums->lang['closethread'];
			$_INPUT['title'] = $forums->lang['closethreadpm'];
			$open_close_lang = $forums->lang['closethread'];
			$opreate_ret = $forums->lang['closethreadsuc'] . "[{$thread['title']}]";
		}
		$moderator_log[] = $open_close_lang . ': <a href="' . $bboptions['bburl'] . '/showthread.php?t=' . $thread['tid'] . '">' . $thread['tid'] . '</a>';
		if ($thread['postuserid'] != $bbuserinfo['id'])
		{
			$pm_touser[$thread['postusername']][] = $open_close_lang . ': <a href="' . $bboptions['bburl'] . '/showthread.php?t=' . $thread['tid'] . '">' . $thread['title'] . '</a>';
			if (intval($user_thread_num[$thread['postusername']]))
			{
				$user_thread_num[$thread['postusername']] = $user_thread_num[$thread['postusername']] + 1;
			}
			else
			{
				$user_thread_num[$thread['postusername']] = $_INPUT['title'];
			}
		}
		//反馈操作结果
		show_processinfo($opreate_ret);
		$response->assign('pic' . $thread['tid'], 'src', ROOT_PATH . 'images/' . $bbuserinfo['imgurl'] . '/' . $pic);
		$response->assign('pic' . $thread['tid'], 'alt', $pic_alt);
		$response->assign('tid' . $thread['tid'], 'checked', false);
	}
	$response->assign('selectall', 'checked', false);

	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$modlog = new modfunctions();
	//记录版主操作日志
	$modlog->add_moderate_log($fid, 0, '0', '', $forums->lang['batch_openorclose'] . '<br />' . implode(',', $moderator_log));

	if ($pm_touser)
	{
		//发送短消息给主题作者
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$pm = new functions_private();
		$_INPUT['noredirect'] = 1;
		$bboptions['usewysiwyg'] = 1;
		$bboptions['pmallowhtml'] = 1;
		foreach ($pm_touser AS $uname => $thread)
		{
			$_INPUT['username'] = $uname;
			if (intval($user_thread_num[$uname]) > 1)
			{
				$_INPUT['title'] = $forums->lang['threads_openorclose'];
			}
			else
			{
				$_INPUT['title'] = $user_thread_num[$uname];
			}
			$_POST['post'] = implode('<br />', $thread);
			$pm->sendpm();
		}
	}
}

/**
 * 修改主题属性
 *
 * @param 主题ID $tid
 * @param 颜色值 $color
 * @param 是否加粗 $bold
 * @return ajaxresponse
 */
function change_thread_attr($tid, $color = 'reset', $bold = 0)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $response;
	//在没有传递主题ID参数或没有登陆，则不进行任何操作直接返回
	if (!$tid || !$bbuserinfo['id'])
	{
		return $response;
	}
	//取出主题信息
	$thread = $DB->query_first("SELECT title, forumid, sticky, postuserid FROM " . TABLE_PREFIX . "thread WHERE tid = " . intval($tid));
	if (!$thread)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroraddress']);
		return $response;
	}
	/*检查主题标题的修改权限
	 超级版主，有编辑主题标题的版主，可以自己编辑主题标题的会员
	 设置自己的标题属性需要做积分检测
	*/
	$need_update_credit = $can_edit_thread_title = false;
	if ($bbuserinfo['supermod'])
	{
		$can_edit_thread_title = true;
	}
	elseif ($bbuserinfo['_moderator'][$thread['forumid']] && $bbuserinfo['_moderator'][$thread['forumid']]['caneditthreads'])
	{
		$can_edit_thread_title = true;
	}
	elseif ($thread['postuserid'] == $bbuserinfo['id'] && $bbuserinfo['caneditthread'])
	{
		$can_edit_thread_title = true;
		require_once(ROOT_PATH . 'includes/functions_credit.php');
		$credit = new functions_credit();
		$check_credit = $credit->check_credit('threadhighlight', $bbuserinfo['usergroupid'], $thread['forumid'], 1, false);
		if ($check_credit)
		{
			show_processinfo(sprintf($forums->lang['credit_limit_over'], $check_credit));
			return $response;
		}
		$need_update_credit = true;
	}
	if(!$can_edit_thread_title)
	{
		show_processinfo($forums->lang['noprmsmodthread']);
		return $response;
	}
	$logtitle = $thread['title'];//用于记录日志，日志记录修改之前的标题

	//如果有加粗属性，则取出其中加粗标签部分的内容
	if (preg_match('#<strong>(.*)</strong>#siU', $thread['title']))
	{
		$thread['title'] = preg_replace('#<strong>(.*)</strong>#siU', '\\1', $thread['title']);
		$thread['bb'] = 1;
	}
	//如果有颜色属性，则取出其中颜色标签中的内容
	if (preg_match('#<font color=(\'|")(.*)(\\1)>(.*)</font>#siU', $thread['title']))
	{
		$thread['color'] = preg_replace('#<font color=(\'|")(.*)(\\1)>(.*)</font>#siU', '\\2', $thread['title']);
	}
	//将得到的主题标题中的其他任何html标签去除
	$thread['title'] = strip_tags($thread['title']);
	//给定了标准的颜色值
	if ($color == 'X' || $color == 'reset')
	{
		$thread['color'] = '';
	}
	else if ($color)
	{
		$thread['color'] = $color;
	}
	//是否设置加粗
	if ($bold)
	{
		$thread['bb'] = 1;
	}
	else
	{
		$thread['bb'] = 0;
	}

	//重新设置标题
	if ($thread['color'])
	{
		$thread['title'] = "<font color='" . $thread['color'] . "'>" . $thread['title'] . "</font>";
	}
	if ($thread['bb'])
	{
		$thread['title'] = "<strong>" . $thread['title'] . "</strong>";
	}
	if (!strip_tags($thread['title']))
	{
		return $response;
	}
	//更新数据库
	$DB->update(TABLE_PREFIX . 'thread', array('title' => $thread['title']), 'tid = ' . intval($tid));

	//记录操作日志
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$modlog = new modfunctions();
	$modlog->add_moderate_log($thread['forumid'], $tid, '0', '', $forums->lang['changetitleattr'] . '[<a href="../showthread.php?t=' . $tid . '" target="_blank">' . $logtitle . '</a>]');
	if ($need_update_credit)
	{
		$credit->update_credit('threadhighlight', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $thread['forumid']);
	}
	//反馈操作结果
	show_processinfo($forums->lang['modthreadsuc']);
	$response->assign('show' . $tid, 'innerHTML', $thread['title']);
	return $response;
}

function change_thread_title($tid, $title, $oldthreadhtml = '')
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $response;
	//在没有传递主题ID参数或没有登陆，则不进行任何操作直接返回
	if (!$tid || !$bbuserinfo['id'])
	{
		return $response;
	}
	//取出主题信息
	$thread = $DB->query_first("SELECT title, forumid, sticky, postuserid FROM " . TABLE_PREFIX . "thread WHERE tid = " . intval($tid));
	if (!$thread)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroraddress']);
		return $response;
	}
	/*检查主题标题的修改权限
	 超级版主，有编辑主题标题的版主，可以自己编辑主题标题的会员
	 编辑自己的标题需要做积分检测
	*/
	$need_update_credit = $can_edit_thread_title = false;
	if ($bbuserinfo['supermod'])
	{
		$can_edit_thread_title = true;
	}
	elseif ($bbuserinfo['_moderator'][$thread['forumid']] && $bbuserinfo['_moderator'][$thread['forumid']]['caneditthreads'])
	{
		$can_edit_thread_title = true;
	}
	elseif ($thread['postuserid'] == $bbuserinfo['id'] && $bbuserinfo['caneditthread'])
	{
		$can_edit_thread_title = true;
		require_once(ROOT_PATH . 'includes/functions_credit.php');
		$credit = new functions_credit();
		$check_credit = $credit->check_credit('editthread', $bbuserinfo['usergroupid'], $thread['forumid'], 1, false);
		if ($check_credit)
		{
			show_processinfo(sprintf($forums->lang['credit_limit_over'], $check_credit));
			$response->assign('oldhtml' . $tid, 'parentNode.innerHTML', $oldthreadhtml);
			$response->assign('show' . $tid, 'innerHTML', $thread['title']);
			return $response;
		}
		$need_update_credit = true;
	}
	if(!$can_edit_thread_title)
	{
		show_processinfo($forums->lang['noprmsmodthread']);
		$response->assign('oldhtml' . $tid, 'parentNode.innerHTML', $oldthreadhtml);
		$response->assign('show' . $tid, 'innerHTML', $thread['title']);
		return $response;
	}
	if ($title && $title != strip_tags($thread['title']))
	{
		$title = init_input(array($title));
		$title = $title[0];
		if (strlen($title) < 2 || strlen($title) > 250)
		{
			show_processinfo($forums->lang['titletoolongorshort']);
			return $response;
		}
		if (preg_match('#<strong>(.*)</strong>#siU', $thread['title']))
		{
			$thread['title'] = preg_replace('#<strong>(.*)</strong>#siU', '\\1', $thread['title']);
			$thread['bb'] = 1;
		}

		if (preg_match('#<font color=(\'|")(.*)(\\1)>(.*)</font>#siU', $thread['title']))
		{
			$thread['color'] = preg_replace('#<font color=(\'|")(.*)(\\1)>(.*)</font>#siU', '\\2', $thread['title']);
		}

		$title = trim($title);
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		$lib = new functions_codeparse();
		$title = $lib->censoredwords($title);

		if ($thread['color'])
		{
			$title = "<font color='" . $thread['color'] . "'>" . $title . "</font>";
		}

		if ($thread['bb'])
		{
			$title = "<strong>" . $title . "</strong>";
		}

		$titletext = strip_tags($title);
		$titletext = implode(' ', duality_word($titletext)); //记录主题全文检索数据
		$DB->update(TABLE_PREFIX . 'thread', array('title' => $title, 'titletext' => $titletext), 'tid = ' . intval($tid));

		//记录操作日志
		require_once(ROOT_PATH . "includes/functions_moderate.php");
		$modlog = new modfunctions();
		$modlog->add_moderate_log($thread['forumid'], $tid, '0', '', $forums->lang['changetitle'] . '[<a href="../showthread.php?t=' . $tid . '" target="_blank">' . $thread['title'] . '</a>]');
		//反馈操作结果
		show_processinfo($forums->lang['modthreadsuc']);
	}
	else
	{
		$title = $thread['title'];
	}
	if ($need_update_credit)
	{
		$credit->update_credit('editthread', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $thread['forumid']);
	}
	$response->assign('oldhtml' . $tid, 'parentNode.innerHTML', $oldthreadhtml);
	$response->assign('show' . $tid, 'innerHTML', $title);
	return $response;
}

/**
 * 修改版面规则
 *
 * @param int $fid
 * @param string $forumrule
 * @param int $wmode
 * @return ajaxrespons
 */
function do_change_forumrule($fid, $forumrule = '', $wmode = 1)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	$can_edit_forumrule = false;
	if ($bbuserinfo['supermod'])
	{
		$can_edit_forumrule = true;
	}
	elseif ($bbuserinfo['_moderator'][$fid] && $bbuserinfo['_moderator'][$fid]['caneditrule'])
	{
		$can_edit_forumrule = true;
	}
	if (!$can_edit_forumrule)
	{
		show_processinfo($forums->lang['noprms_change_forumrule']);
		return $response;
	}

	if ($forumrule)
	{
		require_once(ROOT_PATH . "includes/functions_codeparse.php");
		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$lib = new functions_codeparse();
		if ($wmode)
		{
			$bbuserinfo['usewysiwyg'] = $wmode;
		}
		else
		{
			$bbuserinfo['usewysiwyg'] = ($bboptions['mxemode']) ? 1 : 0;
		}
		$forumrule = $bbuserinfo['usewysiwyg'] ? $forumrule : utf8_htmlspecialchars($forumrule);
		$forumrule = $lib->censoredwords($forumrule);
		$forumrule = $lib->convert(array(
			'text' => $forumrule,
			'allowsmilies' => 1,
			'allowcode' => 1,
		));
		$forumrule = textparse::parse_html($forumrule, 1);
		$is_forumrule = 1;
	}
	else
	{
		$is_forumrule = 0;
	}
	if (is_writeable(ROOT_PATH . 'cache/cache'))
	{
		file_write(ROOT_PATH . "cache/cache/rule_{$fid}.txt", $forumrule, 'wb');
		$DB->update(TABLE_PREFIX . 'forum', array('forumrule' => $is_forumrule), 'id = ' . intval($fid));
		$DB->replace(TABLE_PREFIX . 'forum_attr', array(
			'forumid' => $fid,
			'forumrule' => $forumrule,
		));
		$forums->func->recache('forum');
		show_processinfo($forums->lang['forumrule_change_succ']);
		$response->script('mxe = mxeWin = mxeDoc = mxeTxa = mxeTxH = mxeEbox = mxeStatus = mxeWidth = mxeHeight = eWidth = null;');
		$response->assign('forum_rule', 'innerHTML', $forumrule);
	}
	else
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cachefoldererror']);
	}
	return $response;
}

function process_form($input, $action)
{
	global $forums, $bboptions, $response, $_INPUT, $DB;
	$forums->func->load_lang('moderate');
	$_INPUT = init_input($input);
	$fid = intval($_INPUT['f']);
	if (!$action)
	{
		$action = $_INPUT['do'];
	}
	$prms_fids = '';
	$prms_action = array(
			'openclose' => 'canopenclose',	//开放\关闭主题
			'stickorcancel' => 'canstickthread',	//置顶\撤销主题
			'approveorcancel' => 'canmanagethreads',	//验证\撤销主题
			'moveclearthreads' => 'canremoveposts',		//移动/清理主题
			'dospecialtopic' => 'cansetst',		//设置专题
			'specialtopic' => 'cansetst',		//专题
			'unspecialtopic' => 'cansetst',		//专题

			'close' => 'canopenclose',  //开放主题
			'open' => 'canopenclose',   //关闭主题
			'stick' => 'cangstickthread',	//置顶主题
			'unstick' => 'cangstickthread',	//取消置顶
			'gstick' => 'cangstickthread',		//总置顶
			'ungstick' => 'cangstickthread',	//取消总置顶
			'approve' => 'canmanagethreads',		//验证主题
			'unapprove' => 'canmanagethreads',		//撤销验证主题
			'movethreads' => 'canremoveposts',		//移动主题
			'mergethreads' => 'canmergethreads',		//合并主题
			'deletethreads' => 'candeletethreads',		//删除主题
			'revert' => 'candeletethreads',		//恢复主题
			'cleanmoveurl' => 'canremoveposts',		//清除移动标记
			'quintessence' => 'canquintessence',		//清除移动标记
			'unquintessence' => 'canquintessence',		//清除移动标记
	);
	if ($_INPUT['t'])
	{
		$_INPUT['tid'][] = $_INPUT['t'];
	}

	if (!$_INPUT['tid'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroroperation']);
		return $response;
	}
	$prms_fid = check_moderate_prms($prms_action[$action], $fid);
	if (!$prms_fid)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['noperms']);
		return $response;
	}
	else if(is_array($prms_fid))
	{
		$prms_fids = ' AND forumid IN (' . implode(',', $prms_fid) . ')';
	}

	if ($_INPUT['do'])
	{
		switch ($_INPUT['do'])
		{
			case 'open' :
				open_close_thread($input, 0, $fid, 1, $prms_fids);
				break;
			case 'close' :
				open_close_thread($input, 0, $fid, 0, $prms_fids);
				break;
			case 'approve' :
				approveunapprove($input, $prms_fids);
				break;
			case 'unapprove' :
				approveunapprove($input, $prms_fids, 'unapprove');
				break;
			case 'mergethreads' :
				merge_threads();
				break;
			case 'movethreads' :
				move_threads();
				break;
			case 'quintessence' :
				doquintessence('quintessence');
				break;
			case 'unquintessence' :
				doquintessence('unquintessence');
				break;
			case 'stickorcancel' :
				sticky_threads();
				break;
			case 'cleanmoveurl' :
				clean_moveurl();
				break;
			case 'deletethreads' :
				delete_threads();
				break;
			case 'specialtopic' :
				do_specialtopic('specialtopic');
				break;
			case 'unspecialtopic' :
				do_specialtopic('unspecialtopic');
				break;
			case 'revert' :
				do_revert_threads();
			case 'commend_thread' :
				mod_commend_thread();
				break;
		}
	}
	else
	{
		if ($_INPUT['tid'])
		{
			$mod_action = array(
					'openclose' => array(
						'close' => $forums->lang['threadclose'],  //关闭主题
						'open' => $forums->lang['threadopen'],   //开放主题
					),	//开放/关闭主题
					'approveorcancel' => array(
						'approve' => $forums->lang['threadapprove'],		//验证主题
						'unapprove' => $forums->lang['threadunapprove'],		//撤销验证主题
					),	//验证/撤销主题
					'moveclearthreads' => array(
						'movethreads' => $forums->lang['movethreads'],		//移动主题
						'cleanmoveurl' => $forums->lang['cleanmoveurl'],		//清理验证主题
					),	//移动/清理主题
					'quintessence' => array(
						'quintessence' => $forums->lang['modquintess'],		//设置精华
						'unquintessence' => $forums->lang['modunquintess'],		//取消精华
					),	//精华/撤销精华	//移动/清理主题
					'dospecialtopic' => array(
						'specialtopic' => $forums->lang['threadspecialtopic'],	//设置专题
						'unspecialtopic' => $forums->lang['unthreadspecialtopic'],		//取消专题
					),	//设置/取消专题
			);
			//默认选中的操作
			$action_checked = array(
					'close' => ' checked="checked"',
					'gstick' => ' checked="checked"',
					'unapprove' => ' checked="checked"',
					'quintessence' => ' checked="checked"',
					'movethreads' => ' checked="checked"',
					'specialtopic' => ' checked="checked"',
			);
			//点击事件
			$click_event = array(
					'movethreads' => ' onclick="showElement(\'movethread_extra\');"',
					'cleanmoveurl' => ' onclick="hideElement(\'movethread_extra\');"',
			);
			if ($action == 'moveclearthreads')
			{
				$forums->func->check_cache('forum');
				$foruminfo = $forums->cache['forum'];
				$forums_info = list_forums();
				$forums->lang['movethreadto'] = sprintf($forums->lang['movethreadto'], $foruminfo[$fid]['name'], $forum['name']);
				$forums->lang['moveallthreadto'] = sprintf($forums->lang['moveallthreadto'], $fname);
			}
			elseif ($action == 'stickorcancel')
			{
				$forums->func->check_cache('forum');
				$this_forum = $forums->forum->single_forum($fid);
				$forums_info = '<option value="' . $fid . '">' . $forums->lang['currentforumstick'] . '</option>';
				if (check_moderate_prms('canqstickthread', $fid))
				{
					$forums_info .= list_stickys(explode(',', $this_forum['parentlist']));
				}
				if (check_moderate_prms('cangstickthread', $fid))
				{
					$forums_info .= '<option value="0">' . $forums->lang['threadgstick'] . '</option>';
				}
			}
			elseif ($action == 'dospecialtopic')
			{
				$this_forum = $forums->forum->single_forum($fid);
				if (!$this_forum['specialtopic'])
				{
					show_processinfo($forums->lang['nospecials']);
					return $response;
				}
				$forums->func->check_cache('st');
				$forumsspecial = $forums->cache['st'];
				if (!$forumsspecial)
				{
					show_processinfo($forums->lang['nospecials']);
					return $response;
				}
				$specialtopic = explode(',', $this_forum['specialtopic']);
				if ($this_forum['forcespecial'])
				{
					unset($mod_action[$action]['unspecialtopic']);
				}
			}
			$opreate_name = $forums->lang[$action]; //显示当前操作
			$opreate_description = $forums->lang[$action . 'desc']; //显示当前操作的说明
			if ($action == 'commend_thread')
			{
				$opreate_description = sprintf($opreate_description, $bboptions['commend_thread_num']);
			}
			$do_actions = $mod_action[$action];
			$tids = implode(',', $_INPUT['tid']);
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "thread
						WHERE tid IN (" . $tids . "){$prms_fids}
						ORDER BY dateline DESC");
			$thread = array();
			while ($row = $DB->fetch_array())
			{
				$thread[] = $row;
			}
			if (count($thread) == 1)
			{
				$t_title = strip_tags($thread[0]['title']);
				$special_selected[$thread[0]['stopic']] = ' selected="selected"';
				$show_single = true;
			}
			$bboptions['gzipoutput'] = 0;
			unset($input['tid'], $input['pid'], $input['code']);
			ob_end_clean();
			ob_start();
			include $forums->func->load_template('confirm_operate_thread');
			$thread_content = ob_get_contents();
			ob_end_clean();
			$response->assign('show_operation', 'innerHTML', $thread_content);
			$response->call('showElement', 'operation_pannel');
			$response->call('toCenter', 'operation_pannel');
		}
	}
	return $response;
}

function approveunapprove($input, $prms_fids, $type = 'approve')
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $mod_func, $response;
	if (!$input)
	{
		$input = $_INPUT;
	}
	if ($type == 'approve')
	{
		$action = $forums->lang['approvethread'];
		$update_to_value = 1;
		$class_name = 'item_list';
	}
	else if ($type == 'unapprove')
	{
		$action = $forums->lang['unapprovethread'];
		$update_to_value = 0;
		$class_name = 'item_list_shaded';
	}
	$tids = implode(',', $_INPUT['tid']);
	$DB->update(TABLE_PREFIX . 'thread', array('visible' => $update_to_value), 'tid IN (' . $tids . ')' . $prms_fids);
	//记录版主操作日志
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	$change_threadids = $_INPUT['tid'];
	if (count ($_INPUT['tid']) > 1)
	{
		add_thread_log($_INPUT['tid'], $action);
		add_moderate_log($action . " - " . $forums->lang['threadid'] . ": " . $tids);
	}
	else
	{
		$thread = $DB->query_first('SELECT tid, title FROM '. TABLE_PREFIX . "thread
						  			WHERE tid = " . intval($tids));

		$_INPUT['tid'] = $thread['tid'];
		add_thread_log($thread['tid'], $action);
		add_moderate_log($action . " - " . $thread['title']);
	}
	foreach ($change_threadids AS $tid)
	{
		$response->assign('ttid' . $tid, 'parentNode.className', $class_name);
		$response->assign('tid' . $tid, 'checked', false);
	}
	$response->assign('selectall', 'checked', false);
	show_processinfo($action);
	forum_recount($_INPUT['f']);
	$response->call('hideElement', 'operation_pannel');
	$response->assign('show_operation', 'innerHTML', '');
}

function merge_threads()
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $response, $mod_func;
	if(!$mod_func)
	{
		require_once(ROOT_PATH . "includes/functions_moderate.php");
		$mod_func = new modfunctions();
	}
	$count = count($_INPUT['tid']);
	if ($count < 2 && !trim($_INPUT['threadurl']))
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['selectmerge']);
		return $response;
	}
	$merge_ids = $direct_merge_ids = $move_merge_ids = array();
	if ($count < 2)
	{
		if (preg_match('/^[0-9]+$/', $_INPUT['threadurl']))
		{
			$old_id = intval($_INPUT['threadurl']);
		}
		else
		{
			preg_match("/(\?|&amp;)t=(\d+)($|&amp;)/", $_INPUT['threadurl'], $match);
			$old_id = intval(trim($match[2]));
		}
		if (!$old_id)
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['cannotfindmerge']);
			return $response;
		}
		$old_thread = $DB->query_first('SELECT tid, title, forumid, lastpost, lastposterid, lastposter, post, views, posttable
			FROM ' . TABLE_PREFIX . "thread
			WHERE tid = $old_id");
		if (!$old_thread)
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['cannotfindmerge']);
			return $response;
		}
		$thread = $DB->query_first('SELECT *
			FROM ' . TABLE_PREFIX . 'thread
			WHERE ' . $DB->sql_in('tid', $_INPUT['tid']));
		if ($old_id == $thread['tid'])
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['mergenotsame']);
			return $response;
		}
		$pass = false;
		if ($thread['forumid'] == $old_thread['forumid'])
		{
			$pass = true;
		}
		else
		{
			if ($bbuserinfo['supermod'])
			{
				$pass = true;
			}
			else
			{
				$result = $DB->query('SELECT moderatorid
					FROM ' . TABLE_PREFIX . "moderator
					WHERE forumid = {$old_thread['forumid']}
						AND (userid = {$bbuserinfo['id']}
							OR (isgroup = 1
								AND usergroupid = {$bbuserinfo['usergroupid']}))");
				if ($DB->num_rows($result))
				{
					$pass = true;
				}
			}
		}
		if ($pass == false)
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['cannotmerge']);
			return $response;
		}
		$new_id = $thread['tid'];
		$posttable = $thread['posttable'] ? $thread['posttable'] : 'post';
		$old_posttable = $old_thread['posttable'] ? $old_thread['posttable'] : 'post';
		$new_title = $_INPUT['title'] ? $_INPUT['title'] : $thread['title'];
		if ($posttable == $old_posttable)
		{
			$direct_merge_ids[] = $old_thread['tid'];
		}
		else
		{
			$move_merge_ids[$old_posttable][] = $old_thread['tid'];
		}
		$merge_ids[] = $old_thread['tid'];
		if ($thread['forumid'] != $old_thread['forumid'])
		{
			forum_recount($old_thread['forumid']);
		}
		$forums->lang['mergethreadto'] = sprintf($forums->lang['mergethreadto'], $old_thread['title'], $new_title);
		add_moderate_log($forums->lang['mergethreadto']);
	}
	else
	{
		$forums_recount = $thread = array();
		$result = $DB->query('SELECT tid, title, description, posttable, forumid
			FROM ' . TABLE_PREFIX . 'thread
			WHERE ' . $DB->sql_in('tid', $_INPUT['tid']) . '
			ORDER BY dateline ASC');
		while ($row = $DB->fetch_array($result))
		{
			$thread[] = $row;
			$forums_recount[$row['forumid']] = 1;
		}
		if (count($thread) < 2)
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['selectmerge']);
			return $response;
		}
		$first_thread = array_shift($thread);
		$new_id = $first_thread['tid'];
		$posttable = $first_thread['posttable'] ? $first_thread['posttable'] : 'post';
		$new_title = $_INPUT['title'] ? $_INPUT['title'] : $first_thread['title'];
		foreach($thread AS $t)
		{
			$old_posttable = $t['posttable'] ? $t['posttable'] : 'post';
			if ($posttable == $old_posttable)
			{
				$direct_merge_ids[] = $t['tid'];
			}
			else
			{
				$move_merge_ids[$old_posttable][] = $t['tid'];
			}
			$merge_ids[] = $t['tid'];
		}
		add_moderate_log($forums->lang['mergethread'] . " - " . $forums->lang['threadid'] . ": " . implode(',', $_INPUT['tid']));
	}

	$merge_ids = implode(',', $merge_ids);
	$threadid_in_merge_ids = "threadid IN ($merge_ids)";
	$tid_in_merge_ids = "tid IN ($merge_ids)";
	if ($direct_merge_ids)
	{
		$DB->update(TABLE_PREFIX . $posttable, array('threadid' => $new_id), $DB->sql_in('threadid', $direct_merge_ids));
	}
	if ($move_merge_ids)
	{
		$table_fields = $DB->query('SHOW COLUMNS FROM ' . TABLE_PREFIX . $posttable);
		$fields = array();
		$values = array();
		while ($row = $DB->fetch_array($table_fields))
		{
			if ($row['Field'] == 'pid')
			{
				continue;
			}
			$fields[] = $row['Field'];
			if ($row['Field'] == 'threadid')
			{
				$values[] = $new_id;
			}
			else
			{
				$values[] = $row['Field'];
			}
		}
		foreach ($move_merge_ids AS $tab => $tids)
		{
			if ($tab && $tids)
			{
				$sql = 'INSERT INTO ' . TABLE_PREFIX . $posttable . " (" . implode(',', $fields) . ")
			SELECT " . implode(',', $values) . "
				FROM " . TABLE_PREFIX . $tab . '
				WHERE threadid IN (' . implode(',', $tids) . ')';
				$DB->query($sql);
				$DB->delete(TABLE_PREFIX . $tab, $DB->sql_in('threadid', $tids));
			}
		}
	}

	$DB->update(TABLE_PREFIX . 'thread', array('title' => $new_title), "tid = $new_id");
	$DB->delete(TABLE_PREFIX . 'poll', $tid_in_merge_ids);
	$DB->delete(TABLE_PREFIX . 'subscribethread', $threadid_in_merge_ids);
	$DB->delete(TABLE_PREFIX . 'thread', $tid_in_merge_ids);
	$mod_func->rebuild_thread($new_id);
	foreach ($forums_recount AS $fid => $val)
	{
		forum_recount($fid);
	}
	show_processinfo($forums->lang['hasmerged']);
	if ($_INPUT['f'])
	{
		$url = "showthread.php?{$forums->js_sessionurl}t=" . $new_id;
	}
	else
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
	}
	$response->redirect($url);
	return $response;
}

function list_stickys($forumids = array())
{
	global $forums, $_INPUT, $bboptions, $bbuserinfo;
	$foruminfo = $forums->cache['forum'];

	$forum_jump = '<optgroup label="' . $forums->lang['stick_to_forum'] . '">';
;
	foreach((array) $foruminfo as $id => $forum)
	{
		if (($forum['canshow'] != '*' && $forums->func->fetch_permissions($forum['canshow'], 'canshow') != true) || $forum['url'])
		{
			continue;
		}

		if ($forumids && !in_array($id, $forumids))
		{
			continue;
		}

		if ($_INPUT['f'] && $_INPUT['f'] == $forum['id'])
		{
			continue;
		}
		$forum_jump .= '<option value="' . $forum['id'] . '">' . depth_mark($forum['depth'], '--') . '  ' . $forum['name'] . '</option>' . "\n";
	}
	$forum_jump .= '</optgroup>';
	return $forum_jump;
}

function move_threads()
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $response, $mod_func;

	$dest_id = intval($_INPUT['move_id']);
	$source_id = $_INPUT['f'];
	$_INPUT['leave'] = $_INPUT['leave'] == '1' ? 1 : 0;
	if ($source_id == "")
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindsource']);
		return $response;
	}
	if ($dest_id == "" OR $dest_id == -1)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindtarget']);
		return $response;
	}
	if ($source_id == $dest_id)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['notsamesource']);
		return $response;
	}
	if (!$forum = $DB->query_first("SELECT id, allowposting, name FROM " . TABLE_PREFIX . "forum WHERE id=" . $dest_id . ""))
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindtarget']);
		return $response;
	}
	if ($forum['allowposting'] != 1)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotmove']);
		return $response;
	}
	$forums->func->check_cache('forum');
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	$mod_func->thread_move($_INPUT['tid'], $source_id, $dest_id, $_INPUT['leave']);
	forum_recount($source_id);
	forum_recount($dest_id);
	$forums->lang['movethreadto'] = sprintf($forums->lang['movethreadto'], $forums->cache['forum'][$source_id]['name'], $forums->cache['forum'][$dest_id]['name']);
	add_moderate_log($forums->lang['movethreadto']);
	add_thread_log($_INPUT['tid'], $forums->lang['movethreadto']);
	show_processinfo($forums->lang['hasmoved']);

	if ($_INPUT['t'])
	{
		$url = "showthread.php?{$forums->js_sessionurl}t=" . $_INPUT['t'];
	}
	elseif ($_INPUT['f'])
	{
		$url = "forumdisplay.php?{$forums->js_sessionurl}f=" . $source_id;
	}
	else
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
	}
	$response->redirect($url);
}

function doquintessence($type)
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions, $response, $mod_func;
	require_once(ROOT_PATH . "includes/functions_credit.php");
	$credit = new functions_credit();
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	if ($type == 'quintessence')
	{
		$action = $forums->lang['quintessencethread'];
		$operation = 1;
		$quint_sign = '+';
		$_INPUT['title'] = $forums->lang['threadquintessenced'];
		$pm_content_pre = $forums->lang['threadquintessenced'];
	}
	else if ($type == 'unquintessence')
	{
		$action = $forums->lang['unquintessencethread'];
		$operation = 0;
		$quint_sign = '-';
		$_INPUT['title'] = $forums->lang['threadunquintessenced'];
		$pm_content_pre = $forums->lang['threadunquintessenced'];
	}

	$tids = implode(',', $_INPUT['tid']);
	$users = $DB->query("SELECT t.tid, t.title, t.postuserid, t.forumid, t.postusername, t.quintessence, u.usergroupid
	                        FROM " . TABLE_PREFIX . "thread t
							LEFT JOIN " . TABLE_PREFIX . "user u
						 		ON u.id = t.postuserid
	                     WHERE t.tid IN(" . $tids . ")");

	if ($DB->num_rows($users))
	{
		$userids = $groupids = $forumids = array();
		$update_tid = $pm_touser = $update_user_quint = array();
		while ($user = $DB->fetch_array($users))
		{
			if ($user['quintessence'] == $operation)
			{
				continue;
			}
			$update_user_quint[$user['postuserid']] = $update_user_quint[$user['postuserid']] + 1;
			$pm_touser[$user['postusername']][] = '<br /><a href="' . $bboptions['bburl'] . '/showthread.php?t=' . $user['tid'] . '">' . $user['title'] . '</a>';
			$userids[] = $user['postuserid'];
			$groupids[] = $user['usergroupid'];
			$forumids[] = $user['forumid'];
			$update_tid[] = $user['tid'];
		}
		$update_case_sql = '';
		foreach ($update_user_quint AS $uid => $quint)
		{
			$update_case_sql .= ' WHEN ' . $uid . '
									THEN quintessence' . $quint_sign . intval($quint);
		}
		if($update_case_sql)
		{
			$update_case_sql = ' CASE id ' . $update_case_sql . ' ELSE quintessence END';
			$DB->shutdown_query('UPDATE ' . TABLE_PREFIX . "user SET quintessence = ($update_case_sql) WHERE id IN (" . implode(',' , array_keys($update_user_quint)) . ')');
		}

		$credit->update_credit($type, $userids, $groupids, $forumids);
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET quintessence='" . $operation . "' WHERE tid IN(" . $tids . ")");
		if ($pm_touser)
		{
			//发送短消息给主题作者
			require_once(ROOT_PATH . 'includes/functions_private.php');
			$pm = new functions_private();
			$_INPUT['noredirect'] = 1;
			$bboptions['usewysiwyg'] = 1;
			$bboptions['pmallowhtml'] = 1;
			foreach ($pm_touser AS $uname => $thread)
			{
				$_INPUT['username'] = $uname;
				$_POST['post'] = $pm_content_pre . implode('', $thread);
				$pm->sendpm();
			}
		}
		foreach ($_INPUT['tid'] AS $tid)
		{
			$pic = '<img id="quintessence_pic' . $tid . '" src="' . ROOT_PATH . 'images/' . $bbuserinfo['imgurl'] . '/quintessence.gif" alt="' . $forums->lang["quintessence"] . '" />';
			if (@in_array($tid, $update_tid))
			{
				$response->call('do_quintessence', $tid, $operation, $pic);
			}
			$response->assign('tid' . $tid, 'checked', false);
		}
		$response->assign('selectall', 'checked', false);
		if (count ($_INPUT['tid']) > 1)
		{
			add_thread_log($_INPUT['tid'], $action);
			$_INPUT['tid'] = '';
			add_moderate_log($action . " - " . $forums->lang['threadid'] . ": " . $tids);
		}
		else
		{
			$thread = $DB->query_first('SELECT tid, title FROM '. TABLE_PREFIX . "thread
							  			WHERE tid = " . intval($tids));

			$_INPUT['tid'] = $thread['tid'];
			add_thread_log($thread['tid'], $action);
			add_moderate_log($action . " - " . $thread['title']);
		}
	}
	$response->call('hideElement', 'operation_pannel');
	$response->clear('show_operation', 'innerHTML');
}

function sticky_threads()
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $response, $mod_func;
	if (!check_moderate_prms('canstickthread', $_INPUT['f']))
	{
		show_processinfo($forums->lang['noprms_stick']);
		return $response;
	}
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();

	if (!$_INPUT['cancel_stick']) //置顶操作
	{
		$this_forum = $forums->forum->single_forum($_INPUT['f']);
		$sticky = explode(',', $this_forum['parentlist']);
		$sticky = @array_search($_INPUT['stick_to_forumid'], $sticky) + 1;
		$update_arr = array('sticky' => $sticky);
		if ($_INPUT['stick_to_forumid'] == $_INPUT['f']) //置顶
		{
			$action = $forums->lang['stickthread'];
		}
		else if ($_INPUT['stick_to_forumid'] == -1) //总置顶
		{
			if (!check_moderate_prms('cangstickthread', $fid))
			{
				show_processinfo($forums->lang['noprms_stick']);
				return $response;
			}
			$update_arr['sticky'] = 99;
			$action = $forums->lang['gstickthread'];
		}
		else if ($_INPUT['stick_to_forumid'] != $_INPUT['f']) //区置顶
		{
			if (!check_moderate_prms('canqstickthread', $_INPUT['f']))
			{
				show_processinfo($forums->lang['noprms_stick']);
				return $response;
			}
			$action = $forums->lang['stick_to_forum'] . ': ' . $forums->cache['forum'][$_INPUT['stick_to_forumid']]['name'];
		}
		$update_arr['stickforumid'] = intval($_INPUT['stick_to_forumid']);
		$DB->update(TABLE_PREFIX . 'thread', $update_arr, 'tid IN (' . implode(',', $_INPUT['tid']) . ')');
	}
	else //取消置顶
	{
		if (!check_moderate_prms('canqstickthread', $_INPUT['f']))
		{
			$cancel_condition = ' sticky > 0 AND (stickforumid = ' . intval($_INPUT['f']) . ' OR stickforumid=0)';
		}
		if (!check_moderate_prms('cangstickthread', $fid))
		{
			$cancel_condition = ' AND sticky != 99';
		}
		$action = $forums->lang['unstickthread'];
		$DB->update(TABLE_PREFIX . 'thread', array('sticky' => 0, 'stickforumid' => 0), 'tid IN (' . implode(',', $_INPUT['tid']) . ')' . $cancel_condition);
	}


	$tids = implode(',', $_INPUT['tid']);
	if (count ($_INPUT['tid']) > 1)
	{
		add_thread_log($_INPUT['tid'], $action);
		$_INPUT['tid'] = '';
		add_moderate_log($action . " - " . $forums->lang['threadid'] . ": " . $tids);
	}
	else
	{
		$thread = $DB->query_first('SELECT tid, title FROM '. TABLE_PREFIX . "thread
						  			WHERE tid = " . intval($tids));

		$_INPUT['tid'] = $thread['tid'];
		add_thread_log($thread['tid'], $action);
		add_moderate_log($action . " - " . $thread['title']);
	}
	if ($_INPUT['t'])
	{
		$url = "showthread.php?{$forums->js_sessionurl}t=" . $_INPUT['t'];
	}
	elseif ($_INPUT['f'])
	{
		$url = "forumdisplay.php?{$forums->js_sessionurl}f=" . $_INPUT['f'];
	}
	else
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
	}
	$response->redirect($url);
}

function clean_moveurl()
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $response, $mod_func;
	foreach ($_INPUT['tid'] AS $tid)
	{
		if ($cleanid = $DB->query_first("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE moved LIKE '" . $tid . "&%'"))
		{
			$thread[] = $cleanid['tid'];
		}
	}
	if (is_array($thread))
	{
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "thread WHERE tid IN (" . implode(',', $thread) . ')');
		forum_recount($_INPUT['f']);
	}
	show_processinfo($forums->lang['hascleaned']);
	if ($_INPUT['t'])
	{
		$url = "showthread.php?{$forums->js_sessionurl}t=" . $_INPUT['t'];
	}
	elseif ($_INPUT['f'])
	{
		$url = "forumdisplay.php?{$forums->js_sessionurl}f=" . $_INPUT['f'];
	}
	else
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
	}
	$response->redirect($url);
}

function delete_threads()
{
	global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT, $response, $mod_func;
	$tids = $_INPUT['tid'];
	foreach ($tids AS $link_thread)
	{
		$linked_thread = $DB->query_first( "SELECT tid, forumid FROM ".TABLE_PREFIX."thread WHERE open=2 AND moved LIKE '".$link_thread."&%'" );
		if ( $linked_thread)
		{
			$del_tids[] = $linked_thread['tid'];
			if (!$d_ceche[$linked_thread['forumid']])
			{
				$d_ceche[$linked_thread['forumid']] = $linked_thread['forumid'];
			}
		}
	}
	if (is_array($del_tids))
	{
		$DB->delete(TABLE_PREFIX . 'thread', $DB->sql_in('tid', $del_tids));
		foreach ($d_ceche AS $forumid)
		{
			forum_recount($forumid);
		}
	}
	$deltitles = $delthread = $threadforum = $recountids = array();
	$threads = $DB->query( "SELECT t.postuserid, t.tid, t.title, t.forumid, u.name
				FROM ".TABLE_PREFIX."thread t
			LEFT JOIN ".TABLE_PREFIX."user u
				ON u.id=t.postuserid
			WHERE tid IN(" . implode(',', $tids) . ')' );
	while ($thread = $DB->fetch_array($threads))
	{
		$delthread[$thread['postuserid']][] = $thread;
		$threadforum[$thread['tid']] = $thread['forumid'];
		$deltitles[$thread['tid']] = '[ID:' . $thread['tid'] . ']' . $thread['title'];
		$recountids[] = $thread['forumid'];
	}
	$recycleforumid = fetch_recycleforum();
	//给删除主题的用户发送消息
	if ($_INPUT['deletepmusers'] && !empty($delthread))
	{
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$pm = new functions_private();
		$_INPUT['noredirect'] = 1;
		$bboptions['pmallowhtml'] = 1;
		$bboptions['usewysiwyg'] = 1;
		foreach ($delthread AS $userid => $delthreadinfo)
		{
			$deltitle = '';
			foreach($delthreadinfo AS $delinfo)
			{
				if ($recycleforumid == $delinfo['forumid'])
				{
					continue;
				}
				$deltitle .= "<div>".$delinfo['title']."</div>\n";
			}
			if (!$deltitle) continue;
			$_INPUT['title'] = $forums->lang['yourthreaddeleted'];
			$forums->lang['yourthreaddeletedinfos'] = sprintf( $forums->lang['yourthreaddeletedinfo'], $deltitle, $_INPUT['deletereason'] );
			$_POST['post'] = $forums->lang['yourthreaddeletedinfos'];
			$_INPUT['username'] = $delinfo['name'];
			$pm->sendpm();
		}
	}
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	if ($recycleforumid && $recycleforumid != $_INPUT['f'])
	{
		//搜索中删除主题
		if (!$_INPUT['f'])
		{
			foreach ($threadforum as $tid => $forumid)
			{
				$mod_func->thread_move($tid, $forumid, $recycleforumid);
				$_INPUT['tid'] = $tid;
				add_moderate_log($forums->lang['movetorecycle'] . $forums->lang['threadid'] . ':' . implode('<br />', $deltitles));
				add_thread_log($forums->lang['movetorecycle']);
			}
		}
		else
		{
			$mod_func->thread_move($_INPUT['tid'], $_INPUT['f'], $recycleforumid);
			add_thread_log($forums->lang['movetorecycle']);
			$_INPUT['tid'] = '';
			add_moderate_log($forums->lang['movetorecycle'] . $forums->lang['threadid'] . ':' . implode('<br />', $deltitles));
		}
		$redirectstr = $forums->lang['hastorecycle'];
	}
	else
	{
		$mod_func->thread_delete($tids);
		$_INPUT['tid'] = '';
		add_moderate_log($forums->lang['deletethread'] . $forums->lang['threadid'] . ':' . implode('<br />', $deltitles));
	}

	if (!$_INPUT['f'] && !empty($recountids))
	{
		$fids = array_unique($recountids);
		foreach ($fids as $fid)
		{
			forum_recount($fid);
		}
	}
	else
	{
		forum_recount($_INPUT['f']);
	}

	forum_recount($recycleforumid);
	show_processinfo($redirectstr);
	if ($_INPUT['f'])
	{
		$response->redirect(ROOT_PATH . "forumdisplay.php?{$forums->js_sessionurl}f=" . $_INPUT['f'] . '&pp=' . $_INPUT['pp']);
	}
	else
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
		$response->redirect($url);
	}
	return $response;
}

function do_specialtopic($type = 'specialtopic')
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions, $response, $mod_func;

	$this_forum = $forums->forum->single_forum($_INPUT['f']);
	if ($type == 'specialtopic')
	{
		$st_id = intval($_INPUT['st_id']);
		if (!$this_forum['specialtopic'])
		{
			show_processinfo($forums->lang['nospecials']);
			return $response;
		}
		$forums->func->check_cache('st');
		if (!$forums->cache['st'])
		{
			show_processinfo($forums->lang['nospecials']);
			return $response;
		}
		if (!array_key_exists($st_id, $forums->cache['st']))
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['cannotfindst']);
			return $response;
		}
		$log_content = sprintf($forums->lang['settospecialtopic'], $forums->cache['st'][$st_id]['name']);
		$success_info = sprintf($forums->lang['hassettost'], $forums->cache['st'][$st_id]['name']);;
	}
	else
	{
		if ($this_forum['forcespecial'])
		{
			show_processinfo($forums->lang['dontcancelspecial']);
			return $response;
		}
		$st_id = 0;
		$log_content = $forums->lang['unsetspecialtopic'];
		$success_info = $forums->lang['unsetspecialtopic'];
	}
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	$mod_func->thread_st($_INPUT['tid'], $st_id);
	add_thread_log($_INPUT['tid'], $log_content);
	$tids = implode(',', $_INPUT['tid']);
	$_INPUT['tid'] = '';
	add_moderate_log($log_content . ' - ' . $forums->lang['threadid'] . ': ' . $tids);
	show_processinfo($success_info);
	$url = ($_INPUT['t']) ? "showthread.php?{$forums->js_sessionurl}t=" . $_INPUT['t'] : "forumdisplay.php?{$forums->js_sessionurl}f=" . $_INPUT['f'];
	$response->redirect($url);
}

function do_revert_threads()
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions, $response, $mod_func;
	$forumid = intval($_INPUT['f']);
	$recycleforumid = fetch_recycleforum();
	if ($recycleforumid != $forumid)
	{
		return $response;
	}
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	$recountids = $revertpids = $reverttids = array();
	$result = $DB->query('SELECT tid, rawforumid, forumid FROM ' . TABLE_PREFIX . 'thread WHERE tid IN (' . implode(',', $_INPUT['tid']) . ') AND forumid = ' . $forumid);
	while ($row = $DB->fetch_array($result))
	{
		if (!$row['rawforumid'])
		{
			$recthread = $DB->query_first('SELECT posttable
					FROM ' . TABLE_PREFIX . 'thread
				WHERE tid ='. $row['tid']);
			$posttable = $recthread['posttable'] ? $recthread['posttable'] : 'post';

			$rs = $DB->query('SELECT p.pid, t.forumid, t.tid
					FROM ' . TABLE_PREFIX . "$posttable p
				LEFT JOIN " . TABLE_PREFIX . 'thread t
					ON p.rawthreadid = t.tid
				WHERE p.threadid ='. $row['tid']);
			while ($r = $DB->fetch_array($rs))
			{
				$rawtid = $r['tid'];
				$rawfid = $r['forumid'];
				$revertpids[$posttable][] = $r['pid'];
			}

			$recountids[] = $rawfid;
			$DB->update(TABLE_PREFIX . $posttable, array('threadid' => $rawtid, 'newthread' => 0, 'rawthreadid' => ''), 'threadid=' . $row['tid']);
			$DB->delete(TABLE_PREFIX . 'thread', 'tid=' . $row['tid']);
			$mod_func->rebuild_thread($rawtid);
		}
		else
		{
			$recountids[] = $row['rawforumid'];
			$reverttids[] = $row['tid'];
			$DB->query('UPDATE '.TABLE_PREFIX . "thread SET forumid=rawforumid, addtorecycle='',rawforumid='' WHERE tid=" . $row['tid']);
			$DB->query('UPDATE '.TABLE_PREFIX . "poll SET forumid=rawforumid, addtorecycle='', rawforumid='' WHERE tid=" . $row['tid']);
		}
		add_moderate_log($forums->lang['revertthread']);
	}
	if (!empty($recountids))
	{
		$fids = array_unique($recountids);
		foreach ($fids as $fid)
		{
			forum_recount($fid);
		}
	}
	forum_recount($recycleforumid);

	//恢复用户积分
	if (!empty($revertpids))
	{
		$mod_func->processcredit($revertpids, 'newreply', 'post');
	}
	if (!empty($reverttids))
	{
		$mod_func->processcredit($reverttids, 'newthread');
	}
	show_processinfo($forums->lang['revertthreadfromrecycle']);
	if (!$forumid)
	{
		$url = $_INPUT['search_type'] . ".php?{$forums->js_sessionurl}do=show&searchid=" . $_INPUT['searchid'] . '&highlight=' . urlencode($_INPUT['highlight']);
	}
	else
	{
		$url = "forumdisplay.php?{$forums->js_sessionurl}f=" . $forumid;
	}
	$response->redirect($url);
}

function mod_commend_thread()
{
	global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions, $response, $mod_func;
	$forumid = intval($_INPUT['f']);
	if (!$bbuserinfo['supermod'] && !$bbuserinfo['_moderator'][$forumid])
	{
		show_processinfo($forums->lang['noprms_commend_thread']);
		return $response;
	}
	$commend_exp = intval($_INPUT['commend_exp']);
	if ($commend_exp > $bboptions['commend_thread_num'] || $commend_exp > 127)
	{
		show_processinfo($forums->lang['commend_exp_overflow']);
		return $response;
	}
	$sql = 'SELECT count(*) AS count FROM ' . TABLE_PREFIX . 'thread
			WHERE forumid = ' . $forumid . '
				AND mod_commend > 0';
	$commend_num = $DB->query_first($sql);
	if ($commend_exp && $commend_num['count'] >= $bboptions['commend_thread_num'])
	{
		show_processinfo($forums->lang['commend_thread_overflow']);
		return $response;
	}
	$DB->update(TABLE_PREFIX . 'thread', array('mod_commend' => $commend_exp), $DB->sql_in('tid', $_INPUT['tid']));
	$forums->func->recache('forum_commend_thread');
	if ($commend_exp)
	{
		$log = $forums->lang['commend_thread'];
	}
	else
	{
		$log = $forums->lang['cancel_commend_thread'];
	}
	require_once(ROOT_PATH . "includes/functions_moderate.php");
	$mod_func = new modfunctions();
	$log .= '(' . $forums->lang['threadid'] . implode(',', $_INPUT['tid']) . ')';
	$_INPUT['tid'] = '';
	add_moderate_log($log);
	show_processinfo($forums->lang['commend_thread_suc']);
	$url = "forumdisplay.php?{$forums->js_sessionurl}f=" . $forumid;
	$response->redirect($url);
	return $response;
}
?>