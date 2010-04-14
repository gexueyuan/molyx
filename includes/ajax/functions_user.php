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
$mxajax_register_functions = array(
	'do_change_signature',
	'check_user_account',
	'check_user_email',
	'delete_user_avatar',
	'report_post',
	'do_report_post',
	'evaluation_post',
	'do_evaluation_post',
	'ban_user_post',
); //注册ajax函数

function check_user_account($username)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	$forums->func->load_lang('register');
	$check = unclean_value($username);
	if ($bboptions['namenoallowenus'])
	{
		$pattern .= 'a-zA-Z';
	}
	if ($bboptions['namenoallownumber'])
	{
		$pattern .= '0-9';
	}
	$specialchar = unclean_value(addslashes($bboptions['namenoallowspecial']));
	if ($specialchar)
	{
		if (preg_match('/\s{1,}/', $specialchar))
		{
			$specialchar = preg_replace('/\s{1,}/', '', $specialchar);
			$pattern .= '\s';
		}
		$char = explode('|', $specialchar);
		if (!empty($char))
		{
			foreach ($char as $v)
			{
				if (!$v)
				{
					continue;
				}
				if ($v=="\'" || $v=='\"' || $v=="\\\\")
				{
					$pattern .= '\\\\'.addslashes($v);
				}
				else
				{
					$pattern .= '\\'.$v;
				}
			}
		}
	}
	if ($pattern && preg_match('/[' . $pattern . ']+/', $check))
	{
		display_check_result('name', $forums->lang['ajaxerror1']);
		return $response;
	}

	$len_u = utf8_strlen($check);
	if (empty($username) || strstr($check, ';'))
	{
		display_check_result('name', $forums->lang['ajaxusernameempty']);
		return $response;
	}
	else if ($len_u < $bboptions['usernameminlength'] || $len_u > $bboptions['usernamemaxlength'] || strlen($username) > 60)
	{
		display_check_result('name', sprintf($forums->lang['errorusername'], $bboptions['usernameminlength'], $bboptions['usernamemaxlength']));
		return $response;
	}
	$DB->query("SELECT content FROM " . TABLE_PREFIX . "banfilter WHERE type = 'name'");
	while ($r = $DB->fetch_array())
	{
		$banfilter[] = $r['content'];
		if ($r['content'])
		{
			if (preg_match("/" . preg_quote($r['content'], '/') . "/i", $username))
			{
				display_check_result('name', $forums->lang['ajaxerror1']);
				return $response;
			}
		}
	}
	$checkuser = $DB->query_first("SELECT id, name, email, usergroupid, password, host, salt
			FROM " . TABLE_PREFIX . "user
			WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'");
	if (($checkuser['id']) OR ($username == $forums->lang['guest']))
	{
		display_check_result('name', $forums->lang['ajaxexist']);
		return $response;
	}
	else
	{
		display_check_result('name', $forums->lang['ajaxv'], 'ok');
		return $response;
	}
}

function display_check_result($field, $msg, $type = 'err')
{
	global $response;
	if ($type == 'ok')
	{
		$r_type = 'err';
		$response->assign('submit_registerinfo', 'disabled', false);
	}
	else
	{
		$r_type = 'ok';
		$response->assign('submit_registerinfo', 'disabled', true);
	}
	$response->script('$("' . $field . '_img_' . $r_type . '").style.display = "none"');
	$response->script('$("' . $field . '_img_' . $type, '").style.display = "inline"');
	$response->assign($field . '_ver', 'innerHTML', $msg);
}

function check_user_email($email)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	$forums->func->load_lang('register');
	$email = strtolower(trim($email));
	if (strlen($email) < 6)
	{
		display_check_result('mail', $forums->lang['ajaxmailempty']);
		return $response;
	}
	$email = clean_email($email);
	if (!$email)
	{
		display_check_result('mail', $forums->lang['ajaxerroremail']);
		return $response;
	}
	$DB->query("SELECT content FROM " . TABLE_PREFIX . "banfilter WHERE type = 'email'");
	while ($r = $DB->fetch_array())
	{
		if ($r['content'])
		{
			$banemail = preg_replace("/\*/", '.*' , $r['content']);
			if (preg_match("/$banemail/", $email))
			{
				display_check_result('mail', $forums->lang['ajaxerroremail']);
				return $response;
			}
		}
	}
	$DB->query("SELECT email FROM " . TABLE_PREFIX . "user WHERE email = '" . $email . "'");
	if ($DB->num_rows() != 0)
	{
		display_check_result('mail', $forums->lang['ajaxmailexist']);
		return $response;
	}
	else
	{
		display_check_result('mail', $forums->lang['ajaxv'], 'ok');
		return $response;
	}
}

/**
 * 修改会员签名
 *
 * @param int $pid
 * @param int $uid
 * @param string $signature
 * @param int $wmode
 * @return ajaxrespons
 */
function do_change_signature($pid, $uid, $signature = '', $wmode = 1)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response;
	$response->script('openquick();');
	if ($bbuserinfo['id'] != $uid && !$bbuserinfo['supermod'])
	{
		show_processinfo($forums->lang['noprms_change_signature']);
		$response->script("$('signature{$pid}').innerHTML = $('oldHTML{$pid}').value");
		return $response;
	}
	if (($bboptions['signaturemaxlength'] && utf8_strlen(strip_tags($signature)) > $bboptions['signaturemaxlength']) || strlen($signature) > 16777215)
	{
		show_processinfo(sprintf($forums->lang['signatureuplinmit'], $bboptions['signaturemaxlength']));
		$response->script("$('signature{$pid}').innerHTML = $('oldHTML{$pid}').value");
		return $response;
	}
	require_once(ROOT_PATH . 'includes/functions_credit.php');
	$credit = new functions_credit();
	$check_credit = $credit->check_credit('addsignature', $bbuserinfo['usergroupid'], 0, 1, false);
	if ($check_credit)
	{
		show_processinfo(sprintf($forums->lang['credit_limit_over'], $check_credit));
		$response->script("$('signature{$pid}').innerHTML = $('oldHTML{$pid}').value");
		return $response;
	}
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
	$signature = $bbuserinfo['usewysiwyg'] ? $signature : utf8_htmlspecialchars($signature);
	$signature = $lib->censoredwords($signature);
	$sig = $lib->convert(array(
		'text' => $signature,
		'allowsmilies' => 1,
		'allowcode' => $bboptions['signatureallowbbcode'],
	));
	$sig = textparse::parse_html($sig, $bboptions['signatureallowhtml']);
	$DB->update(TABLE_PREFIX . 'user', array('signature' => $sig), 'id = ' . intval($uid));
	$credit->update_credit('addsignature', $bbuserinfo['id'], $bbuserinfo['usergroupid'], 0);
	show_processinfo($forums->lang['signature_change_succ']);
	$sscript = "if (signature_a_height){ $('signature{$pid}').style.height = signature_a_height;}";
	$response->script('mxe = mxeWin = mxeDoc = mxeTxa = mxeTxH = mxeEbox = mxeStatus = mxeWidth = mxeHeight = eWidth = null;' . $sscript);
	$response->assign('signature' . $pid, 'innerHTML', $sig);
	return $response;
}

function delete_user_avatar($uid, $avatar_no = 0)
{
	global $forums, $DB, $bbuserinfo, $bboptions, $response;
	if (!intval($uid))
	{
		show_processinfo($forums->lang['selected_user_first']);
		return $response;
	}
	$userdir = $bboptions['uploadurl'] . '/user';
	$default_avatar = $userdir . '/' . 'a-default-0.jpg';
	if (!$bbuserinfo['supermod'] && !$bbuserinfo['caneditusers'])
	{
		show_processinfo($forums->lang['noprms_editusers']);
		return $response;
	}
	$DB->update(TABLE_PREFIX . 'user', array('avatar' => 0), 'id=' . $uid);
	$DB->update(TABLE_PREFIX . 'session', array('avatar' => 1), "userid = {$uid}");
	$userdir = split_todir($uid, $bboptions['uploadfolder'] . '/user');
	@unlink($userdir[0] . '/a-' . $uid . '-0.jpg');
	@unlink($userdir[0] . '/a-' . $uid . '-1.jpg');
	@unlink($userdir[0] . '/a-' . $uid . '-2.jpg');
	$response->assign('avatar_temp_id' . $avatar_no, 'src', $default_avatar);
	$response->assign('avatar_temp_id' . $avatar_no, 'onmouseover', '');
	$response->assign('avatar_temp_id' . $avatar_no, 'onmouseout', '');
	$response->removeHandler('avatar_temp_id' . $avatar_no, 'onmouseout', 'hide_avatar_opt');
	$response->removeHandler('avatar_temp_id' . $avatar_no, 'onmouseover', 'edit_user_avatar');
	return $response;
}

function report_post($input, $pid)
{
	global $forums, $DB, $bbuserinfo, $bboptions, $response;
	$tid = intval($input['t']);
	$input['p'] = intval($pid);
	unset($input['do'], $input['posthash']);
	$forums->func->load_lang('report');
	if (!$bbuserinfo['id'])
	{
		show_processinfo($forums->lang['noperms']);
		return $response;
	}
	if (!$tid)
	{
		show_processinfo($forums->lang['cannotfindreport']);
		return $response;
	}
	$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid=" . $tid);
	$this_forum = $forums->forum->single_forum($thread['forumid']);
	$this_forumid = $this_forum['id'];
	if (!$pid || !$this_forumid)
	{
		show_processinfo($forums->lang['cannotfindreport']);
		return $response;
	}
	if (!check_forum_permissions($this_forum))
	{
		show_processinfo($forums->lang['cannotreport']);
		return $response;
	}
	$bboptions['gzipoutput'] = 0;
	$title = $thread['title'];
	ob_end_clean();
	ob_start();
	include $forums->func->load_template('sendmail_report');
	$content = ob_get_contents();
	ob_end_clean();
	$response->assign('show_operation', 'innerHTML', $content);
	$response->call('showElement', 'operation_pannel');
	$response->call('toCenter', 'operation_pannel');
	return $response;
}

function check_forum_permissions($this_forum)
{
	global $forums, $DB, $bbuserinfo, $bboptions;
	$return = false;
	if ($forums->func->fetch_permissions($this_forum['canread'], 'canread') == true)
	{
		$return = true;
	}
	if ($this_forum['password'])
	{
		$this_forum_password = $forums->func->get_cookie('forum_' . $this_forum['id']);
		if ($this_forum_password == $this_forum['password'])
		{
			$return = true;
		}
	}
	return $return;
}

function do_report_post($input)
{
	global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo, $response;
	$_INPUT = init_input($input);
	$forums->func->load_lang('report');
	$forums->func->load_lang('error');
	if (!$bbuserinfo['id'])
	{
		show_processinfo($forums->lang['noperms']);
		return $response;
	}
	if (trim($_INPUT['message']) == '')
	{
		show_processinfo($forums->lang['plzinputallform']);
		return $response;
	}
	$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid=" . intval($input['t']));
	$this_forum = $forums->forum->single_forum($thread['forumid']);
	$this_forumid = $this_forum['id'];
	if (!check_forum_permissions($this_forum))
	{
		show_processinfo($forums->lang['cannotreport']);
		return $response;
	}
	$mods = array();
	$nmods = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset, m.moderatorid FROM " . TABLE_PREFIX . "moderator m, " . TABLE_PREFIX . "user u WHERE m.forumid=" . $this_forumid . " and m.userid=u.id");
	if ($DB->num_rows($nmods))
	{
		while ($r = $DB->fetch_array($nmods))
		{
			$mods[] = $r;
		}
	}
	else
	{
		$smods = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset FROM " . TABLE_PREFIX . "user u, " . TABLE_PREFIX . "usergroup g WHERE g.supermod=1 AND u.usergroupid=g.usergroupid");
		if ($DB->num_rows($smods))
		{
			while ($r = $DB->fetch_array($smods))
			{
				$mods[] = $r;
			}
		}
		else
		{
			$admin = $DB->query("SELECT u.id, u.name, u.email, u.emailcharset FROM " . TABLE_PREFIX . "user u, " . TABLE_PREFIX . "usergroup g WHERE g.cancontrolpanel=1 AND u.usergroupid=g.usergroupid");
			while ($r = $DB->fetch_array($admin))
			{
				$mods[] = $r;
			}
		}
	}
	require_once(ROOT_PATH . "includes/functions_email.php");
	$email = new functions_email();
	$report = trim($_INPUT['message']);
	foreach($mods as $ids => $data)
	{
		$message = $email->fetch_email_reportpost(array('moderator' => $data['name'],
				'username' => $bbuserinfo['name'],
				'thread' => $thread['title'],
				'link' => $bboptions['bburl'] . "/showthread.php?f=" . $this_forumid . "&amp;t=" . $input['t'] . "&amp;pp=" . $_INPUT['pp'] . "#pid" . $input['p'],
				'report' => $report,
				)
			);
		$email->build_message($message);
		if ($bboptions['reporttype'] == 'email')
		{
			$email->char_set = $data['emailcharset'] ? $data['emailcharset'] : 'GBK';
			$email->subject = $forums->lang['reportbadpost'] . ' - ' . $bboptions['bbtitle'];
			$email->to = $data['email'];
			$email->send_mail();
		}
		else
		{
			$_INPUT['title'] = $forums->lang['reportthread'] . ': ' . $thread['title'];
			$_POST['post'] = $message;
			$_INPUT['username'] = $data['name'];
			require_once(ROOT_PATH . 'includes/functions_private.php');
			$pm = new functions_private();
			$bbuserinfo['pmfolders'] = unserialize($bbuserinfo['pmfolders']);
			if (count($bbuserinfo['pmfolders']) < 2)
			{
				$bbuserinfo['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']));
			}
			$_INPUT['noredirect'] = 1;
			$pm->sendpm();
		}
	}
	$forums->lang['hasreport'] = sprintf($forums->lang['hasreport'], $bbuserinfo['name']);
	show_processinfo($forums->lang['hasreport'] );
	$response->call('hideElement', 'operation_pannel');
	return $response;
}

function check_eval_prms($input)
{
	global $forums, $DB, $bbuserinfo, $bboptions, $response, $this_credit;
	if (!$bbuserinfo['id'])
	{
		show_processinfo($forums->lang['noperms']);
		return false;
	}
	$pid = intval($input['p']);
	$tid = intval($input['t']);
	if (!$tid || !$pid)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroraddress']);
		return false;
	}
	$forums->this_thread = $DB->query_first("SELECT tid, title, forumid, posttable, firstpostid, allrep FROM " . TABLE_PREFIX . "thread WHERE tid= $tid");
	if (!$forums->this_thread)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroraddress']);
		return false;
	}
	$forums->this_thread['posttable'] = $forums->this_thread['posttable'] ? $forums->this_thread['posttable'] : 'post';
	$forums->this_post = $DB->query_first("SELECT p.userid, p.username, u.usergroupid FROM " . TABLE_PREFIX . $forums->this_thread['posttable'] . " p
		LEFT JOIN  " . TABLE_PREFIX . "user u ON u.id = p.userid
		WHERE p.pid= $pid");
	if (!$forums->this_post)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['erroraddress']);
		return false;
	}
	$forums->this_forum = $forums->forum->single_forum($forums->this_thread['forumid']);
	$forums->this_forumid = $forums->this_forum['id'];
	if ($bbuserinfo['id'] == $forums->this_post['userid'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotevalself']);
		return false;
	}
	$forums->func->check_cache('usergroup');
	$usergroups = $forums->cache['usergroup'];
	//判断用户权重
	if ($usergroups[$bbuserinfo['usergroupid']]['grouppower'] < $usergroups[$forums->this_post['usergroupid']]['grouppower'])
	{
		if (!$usergroups[$bbuserinfo['usergroupid']]['canevaluation'])
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['cannotevalpower']);
			return false;
		}
	}
	return true;
}

function evaluation_post($input, $pid)
{
	global $forums, $DB, $bbuserinfo, $bboptions, $response, $this_credit;
	$forums->func->load_lang('evaluation');
	unset($input['do'], $input['posthash']);
	$input['p'] = $pid;
	require_once(ROOT_PATH . "includes/functions_credit.php");
	$this_credit = new functions_credit();
	if (!check_eval_prms($input))
	{
		return $response;
	}
	$title = $forums->this_thread['title'];
	$author = $forums->this_post['username'];
	$authorid = $forums->this_post['userid'];

	$forums->func->check_cache('creditlist');
	$list_key = $list_value = array();
	foreach ($forums->cache['creditlist'] as $creditid => $v)
	{
		if (!$v['used']) continue;
		//恢复会员默认评分值
		if (intval($v['initevalvalue']) > 0 && intval($v['initevaltime']) > 0 && $bbuserinfo['eval' . $v['tag']] != $v['initevalvalue'])
		{
			$last_eval = $DB->query_first('SELECT dateline
												FROM ' . TABLE_PREFIX . 'evaluationlog
											WHERE actionuserid=' . intval($bbuserinfo['id']) . '
												 AND creditid=' . intval($creditid));

			if ($last_eval['dateline'])
			{
				$lefttime = TIMENOW - $last_eval['dateline'];
				$recycletime = intval($v['initevaltime']) * 3600;
				if ($lefttime > $recycletime)
				{
					$DB->update(TABLE_PREFIX . 'userexpand', array('eval' . $v['tag'] => intval($v['initevalvalue'])), "id={$bbuserinfo['id']}");
					$bbuserinfo['eval' . $v['tag']] = $v['initevalvalue'];
				}
			}
		}
		//积分下拉列表
		$creditlists .= "<option value='{$v['tag']}'>{$v['name']}</option>\n";

		//取得剩余的评价积分数
		$range = get_credit_range($creditid, $forums->this_forumid);
		$evalrange = implode('~', $range);
		$leftvalue = $bbuserinfo['eval' . $v['tag']];
		$evalcreditdesc = sprintf($forums->lang['evalcreditdesc'], $v['unit'], $evalrange, $v['name'], intval($leftvalue));
		$list_key[] = $v['tag'];
		$list_value[] = $evalcreditdesc;
	}

	if ($creditlists == '')
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotevalcredit']);
		return $response;
	}
	$script = '
	lists_key = ["' . implode('", "', $list_key) . '"];
	lists_value = ["' . implode('", "', $list_value) . '"];
	';
	$response->script($script);
	$bboptions['gzipoutput'] = 0;
	ob_end_clean();
	ob_start();
	include $forums->func->load_template('evaluation_post');
	$content = ob_get_contents();
	ob_end_clean();
	$response->assign('show_operation', 'innerHTML', $content);
	$response->call('showElement', 'operation_pannel');
	$response->call('toCenter', 'operation_pannel');
	$response->call('changecredit');
	return $response;
}

function get_credit_range($id = 0, $fid = 0)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response, $this_credit;
	$range = array();
	$minarrs = $this_credit->getactioncredit('evaluationmin', $bbuserinfo['usergroupid'], $fid);
	$range['min'] = $minarrs[$id]['action'];
	$maxarrs = $this_credit->getactioncredit('evaluationmax', $bbuserinfo['usergroupid'], $fid);
	$range['max'] = $maxarrs[$id]['action'];

	return $range;
}

function do_evaluation_post($input)
{
	global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $response, $this_credit;
	$forums->func->load_lang('evaluation');
	$_INPUT = init_input($input);
	require_once(ROOT_PATH . "includes/functions_credit.php");
	$this_credit = new functions_credit();
	if (!check_eval_prms($_INPUT))
	{
		return $response;
	}
	$actcredit = $_INPUT['actcredit'];
	$amount = intval($_INPUT['amount']);
	$evalmessage = trim($_INPUT['evalmessage']);
	$allrep = unserialize($forums->this_thread['allrep']);
	$thiscredit = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "credit WHERE tag='$actcredit'");
	//判断用户用该积分进行的评价活动是否超出评价默认值
	if ($bbuserinfo['eval'.$actcredit]<=0)
	{
		$forums->func->load_lang('error');
		if (intval($thiscredit['initevalvalue'])<=0)
		{
			$cannot_eval = sprintf($forums->lang['notusedcrediteval'], $thiscredit['name']);
		}
		else
		{
			$cannot_eval = sprintf($forums->lang['evaloverflow'], $thiscredit['initevaltime']);
		}
		show_processinfo($cannot_eval);
		return $response;
	}
	else
	{
		$left = $bbuserinfo['eval'.$actcredit] - abs($amount);
		$forums->func->load_lang('error');
		if ($left < 0)
		{
			$cannot_eval = sprintf($forums->lang['evalrangemax'], $bbuserinfo['eval'.$actcredit]);
			show_processinfo($cannot_eval);
			return $response;
		}
	}
	//取得单主题评价中得到的最高收入
	$scorearrs = $this_credit->getactioncredit('evalthreadscore', $forums->this_post['usergroupid'], $forums->this_forumid);
	$threadscore = $scorearrs[$thiscredit['creditid']]['action'];

	$log = $DB->query_first("SELECT *
		FROM " . TABLE_PREFIX . "evaluationlog
		WHERE postid = " . intval($_INPUT['p']) . " AND actionuserid = {$bbuserinfo['id']}");
	if ($log['evaluationid'] && !$bbuserinfo['canevalsameuser'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotrepeateval']);
		return $response;
	}
	$range = get_credit_range($thiscredit['creditid'], $forums->this_forumid);
	if ($amount == 0 || $amount < $range['min'] || $amount > $range['max'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['evalamounterror']);
		return $response;
	}
	$allrep[$actcredit] = $allrep[$actcredit] + abs($amount);
	if ($allrep[$actcredit] > $threadscore)
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['evalupsinglethread']);
		return $response;
	}
	//添加评价日志
	$logarray = array('forumid' => $forums->this_forumid,
					  'threadid' => $_INPUT['t'],
					  'postid' => $_INPUT['p'],
					  'postuserid' => $forums->this_post['userid'],
					  'actionusername' => $bbuserinfo['name'],
					  'actionuserid' => $bbuserinfo['id'],
					  'affect' => $amount,
					  'creditid' => $thiscredit['creditid'],
					  'creditname' => $thiscredit['name'],
					  'reason' => $evalmessage,
					  'dateline' => TIMENOW,
	);
	$DB->insert(TABLE_PREFIX . "evaluationlog", $logarray);
	//更新被评价的用户积分
	$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $actcredit = $actcredit + ( $amount ) WHERE id = {$forums->this_post['userid']}");
	//更新评价的用户积分
	$evalcredit = 'eval'.$actcredit;
	$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $evalcredit = $evalcredit - " . abs($amount) . " WHERE id = {$bbuserinfo['id']}");
	$DB->shutdown_update(TABLE_PREFIX . "thread", array('allrep' => serialize($allrep)), "tid = " . intval($_INPUT['t']));

	//发送短消息
	if ($_INPUT['sendpm'] == 'yes')
	{
		$_INPUT['title'] = sprintf($forums->lang['evalpmtitle'], $bbuserinfo['name']);
		if ($forums->this_thread['firstpostid'] != $_INPUT['p'])
		{
			$evaluationinfo = sprintf($forums->lang['evalpmcontentposter'], $bbuserinfo['name'], $forums->this_thread['title'], $thiscredit['name'], $amount, $evalmessage);
		}
		else
		{
			$evaluationinfo = sprintf($forums->lang['evalpmcontentauthor'], $bbuserinfo['name'], $forums->this_thread['title'], $thiscredit['name'], $amount, $evalmessage);
		}
		$_POST['post'] = $evaluationinfo;
		$_INPUT['username'] = $forums->this_post['username'];
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$pm = new functions_private();
		$_INPUT['noredirect'] = 1;
		$bboptions['usewysiwyg'] = 1;
		$bboptions['pmallowhtml'] = 1;
		$pm->sendpm();
	}
	$evallog = array();
	$rs = $DB->query('SELECT actionusername, affect, dateline, reason, creditid, creditname
		FROM ' . TABLE_PREFIX . "evaluationlog
		WHERE postid=" . intval($_INPUT['p']) . "
		ORDER BY dateline DESC
		LIMIT 0,5");
	while ($r = $DB->fetch_array($rs))
	{
		$split = array();
		if (substr($r['affect'], 0, 1) != '-')
		{
			$r['affect'] = '+' . $r['affect'];
		}
		$evallog[] = array($r['actionusername'], $r['creditname'], $r['affect'], $r['reason'], $r['dateline']);
	}

	$rs = $DB->query('SELECT SUM(affect) AS credit, creditid, creditname
		FROM ' . TABLE_PREFIX . "evaluationlog
		WHERE postid=" . intval($_INPUT['p']) . "
		GROUP BY creditid");
	$postcredit = array();
	while ($r = $DB->fetch_array($rs))
	{
		$postcredit[] = $r;
	}
	$evallog['ac'] = $postcredit;
	$DB->shutdown_update(TABLE_PREFIX . $forums->this_thread['posttable'], array('reppost' => serialize($evallog)), 'pid=' . intval($_INPUT['p']));
	$url = "showthread.php?{$forums->js_sessionurl}f=" . $forums->this_forumid . "&t=" . $_INPUT['t'] . "&pp=" . $_INPUT['pp'];
	$response->redirect($url);
	return $response;
}

function ban_user_post($input, $uid = 0, $do_ban = 0)
{
	global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT, $response;
	$_INPUT = init_input($input);
	$uid = intval($uid);
	$fid = intval($_INPUT['f']);
	$user = $DB->query_first("SELECT id, name, liftban, usergroupid
		FROM " . TABLE_PREFIX . "user WHERE id=$uid");
	if (!$user['id'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindmember']);
		return $response;
	}
	$forums->func->load_lang('misc');
	if ($bbuserinfo['id'] && !$bbuserinfo['supermod'] && $fid)
	{
		$moderator = $bbuserinfo['_moderator'][$fid];
	}
	$ban = banned_detect($user['liftban']);
	if ($do_ban)
	{
		$permanent = intval($_INPUT['permanent']);
		if (!$permanent)
		{
			show_processinfo($forums->lang['notbantype']);
			return $response;
		}
		if ($permanent == 2 && !$fid)
		{
			show_processinfo($forums->lang['banpostnotforumid']);
			return $response;
		}
		//版主封禁用户在本版面内活动
		if (!$bbuserinfo['supermod'] && $permanent == 2)
		{
			$timelimit = intval($moderator['bantimelimit']);
			$limitunit = substr($moderator['bantimelimit'], -1);
			$limitfactor = ($limitunit == 'd') ? 86400 : 3600;
			$limitspan = TIMENOW + ($timelimit * $limitfactor);
			$timespan = intval($_INPUT['posttimespan']);
			$spanfactor = ($_INPUT['banpostunit'] == 'd') ? 86400 : 3600;
			$posttimespan = TIMENOW + ($timespan * $spanfactor);
			if ($limitspan < $posttimespan)
			{
				show_processinfo($forums->lang['banpostmorethanlimit']);
				return $response;
			}
		}
		if ($user['usergroupid'] != 2 && ($user['supermod']==1 || $user['usergroupid']==4))
		{
			show_processinfo($forums->lang['cannotbanuser']);
			return $response;
		}
		$liftban = "";
		$splittable = array();
		$forums->func->check_cache('splittable');
		$splittable = $forums->cache['splittable']['all'];
		$time = $forums->func->get_date(TIMENOW, 2, 1);
		if ($permanent == -1 || $permanent == 1)
		{
			$msg = $forums->lang['banusersuccess'];
			$usergroupid = 5;
			$banposts = $_INPUT['banbbspost']?-1:-2;
			$opera = $forums->lang['optionlog1'].$bbuserinfo['name'].$forums->lang['optionlog2'].$time.$forums->lang['optionlog3'];
			if ($banposts == -1)
			{
				foreach ($splittable as $id => $v)
				{
					if ($v['isempty']) continue;
					$DB->update(TABLE_PREFIX . $v['name'], array('state' => 2, 'logtext' => "$opera"), 'userid=' . $user['id']);
				}
			}
		}
		switch($permanent)
		{
			//永久封禁
			case -1:
				$liftban = banned_detect(array('timespan' => -1, 'unit' => '', 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts));
				break;
			//按时封禁用户在所有版面内
			case 1:
				if (!intval($_INPUT['usertimespan']))
				{
					show_processinfo($forums->lang['setting_time_first']);
					return $response;
				}
				$liftban = banned_detect(array('timespan' => intval($_INPUT['usertimespan']), 'unit' => $_INPUT['banuserunit'], 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts));
				break;
			//按时封禁用户在此版面内
			case 2:
				if (!intval($_INPUT['posttimespan']))
				{
					show_processinfo($forums->lang['setting_time_first']);
					return $response;
				}
				$msg = $forums->lang['banpostsuccess'];
				$usergroupid = $user['usergroupid'];
				$banposts = $_INPUT['banbbspost']?$fid:-2;
				if ($banposts > 0)
				{
					$opera = $forums->lang['optionlog1'].$bbuserinfo['name'].$forums->lang['optionlog2'].$time.$forums->lang['optionlog3'];

					$tidarrs = array();
					$rs = $DB->query("SELECT tid, posttable
						FROM " . TABLE_PREFIX . "thread
						WHERE postuserid={$user['id']} AND forumid = $fid");
					while($row = $DB->fetch_array($rs))
					{
						$table = $row['posttable']?$row['posttable']:'post';
						$tidarrs[$table][] = $row['tid'];
					}
					if (!empty($tidarrs))
					{
						foreach ($tidarrs as $tblname => $tids)
						{
							$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "$tblname SET state=2, logtext = '" . $opera . "'
							WHERE " . $DB->sql_in('threadid', $tids));
						}
					}
				}
				$liftban = banned_detect(array('timespan' => intval($_INPUT['posttimespan']), 'unit' => $_INPUT['banpostunit'], 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts, 'forumid' => $fid));
				break;
			default:
				$msg = $forums->lang['unbanusersuccess'];
				if ($ban['banposts'] == -1)
				{
					foreach ($splittable as $id => $v)
					{
						if ($v['isempty']) continue;
						$DB->update(TABLE_PREFIX . $v['name'], array('state' => 0, 'logtext' => ''), 'userid=' . $user['id']);
					}
				}
				elseif ($ban['banposts'] > 0)
				{
					$tidarrs = array();
					$rs = $DB->query("SELECT tid, posttable
						FROM " . TABLE_PREFIX . "thread
						WHERE postuserid={$user['id']} AND forumid = $fid");
					while($row = $DB->fetch_array($rs))
					{
						$table = $row['posttable']?$row['posttable']:'post';
						$tidarrs[$table][] = $row['tid'];
					}
					if (!empty($tidarrs))
					{
						foreach ($tidarrs as $tblname => $tids)
						{
							$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "$tblname SET state=0, logtext = '' WHERE " . $DB->sql_in('threadid', $tids));
						}
					}
				}
				$usergroupid = $ban['groupid'] ? intval($ban['groupid']) : $user['usergroupid'];
				$liftban = "";
		}

		$DB->update(TABLE_PREFIX . 'user', array('liftban' => $liftban, 'usergroupid' => $usergroupid), 'id=' . $user['id']);
		if ($_INPUT['sendbanmsg'])
		{
			require_once(ROOT_PATH . 'includes/functions_private.php');
			$pm = new functions_private();
			$_INPUT['noredirect'] = 1;
			$bboptions['usewysiwyg'] = 1;
			$bboptions['pmallowhtml'] = 1;
			if ($permanent == -2)
			{
				$_INPUT['title'] = $forums->lang['unliftban'];
				$_POST['post'] = $forums->lang['unliftbandesc'];
				$_INPUT['username'] = $user['name'];
				$pm->sendpm();
			}
			elseif ($permanent == 2)
			{
				$forum = $forums->forum->single_forum($fid);
				$_INPUT['title'] = $forums->lang['banpost'];
				$forums->lang['banpostdesc'] = sprintf($forums->lang['banpostdesc'], $forum['name'], $forums->func->get_date($posttimespan, 2, 1));
				$_POST['post'] = $forums->lang['banpostdesc'];
				$_INPUT['username'] = $user['name'];
				$pm->sendpm();
			}
		}
		show_processinfo($msg);
		$response->call('hideElement', 'operation_pannel');
	}
	else
	{
		if ($ban['banposts'])
		{
			$unbanchecked = ' checked = "checked"';
		}
		$banusertype = getselectoption('banuserunit');
		$banposttype = getselectoption('banpostunit');
		$banpostlimitdesc = sprintf($forums->lang['banpostlimitdesc'], $moderator['bantimelimit']);
		$banpostlimitdesc = str_replace(array('d', 'h'), array($forums->lang['days'], $forums->lang['hours']), $banpostlimitdesc);
		$banpostlimit = intval($moderator['bantimelimit']);
		unset($input['do'], $input['posthash']);
		$bboptions['gzipoutput'] = 0;
		ob_end_clean();
		ob_start();
		include $forums->func->load_template('visit_forbidden');
		$content = ob_get_contents();
		ob_end_clean();
		$response->assign('show_operation', 'innerHTML', $content);
		$response->call('showElement', 'operation_pannel');
		$response->call('toCenter', 'operation_pannel');
	}
	return $response;

}
function getselectoption($name, $option = '')
{
	global $forums;
	$units = array(0 => array('h', $forums->lang['hours']), 1 => array('d', $forums->lang['days']));
	$bantype = "<select name='".$name."' class='select_normal'>\n";
	foreach ($units AS $v)
	{
		$bantype .= "<option value='" . $v[0] . "'>" . $v[1] . "</option>\n";
	}
	$bantype .= "</select>\n\n";
	return $bantype;
}
?>