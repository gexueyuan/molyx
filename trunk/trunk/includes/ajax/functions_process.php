<?php
$mxajax_register_functions = array(
	'digg_thread'
); //注册ajax函数

function send_mailto_user($input, $uid)
{
	global $_INPUT, $DB, $bbuserinfo, $forums, $response, $bboptions;
	if (! $bbuserinfo['id'])
	{
		show_processinfo($forums->lang['notlogin']);
		return $response;
	}
	$forums->func->load_lang('sendmessage');
	if (! $bbuserinfo['canemail'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['noperms']);
		return $response;
	}
	$_INPUT['u'] = intval($uid);
	if (!$_INPUT['u'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindmailer']);
		return $response;
	}
	if (!$user = $DB->query_first("SELECT id, name, email, emailcharset, options FROM " . TABLE_PREFIX . "user WHERE id=" . $_INPUT['u']))
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotfindmailer']);
		return $response;
	}
	$forums->func->convert_bits_to_array($user, $user['options']);
	if ($user['hideemail'])
	{
		$forums->func->load_lang('error');
		show_processinfo($forums->lang['cannotmailuser']);
		return $response;
	}
	if (!$input)
	{
		$forums->lang['sendmailto'] = sprintf($forums->lang['sendmailto'], $user['name']);
		$bboptions['gzipoutput'] = 0;
		$sessionid = $forums->sessionid;
		ob_end_clean();
		ob_start();
		include $forums->func->load_template('sendmail_mailmember');
		$content = ob_get_contents();
		ob_end_clean();
		$response->assign('show_operation', 'innerHTML', $content);
		$response->call('showElement', 'operation_pannel');
		$response->call('toCenter', 'operation_pannel');
	}
	else
	{
		$_POST['message'] = $input['message'];
		$_INPUT = init_input($input);
		if (! $_INPUT['subject'] OR ! $_INPUT['message'])
		{
			$forums->func->load_lang('error');
			show_processinfo($forums->lang['plzinputallform']);
			return $response;
		}
		require_once(ROOT_PATH . "includes/functions_email.php");
		$email = new functions_email();
		$message = $email->fetch_email_mailmember(array(
			'message' => preg_replace("#<br.*>#siU", "\n", str_replace("\r", '', $_POST['message'])),
			'username' => $user['name'],
			'from' => $bbuserinfo['name']
		));
		$email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
		$email->build_message($message);
		$email->subject = $_INPUT['subject'];
		$email->to = $user['email'];
		$email->from = $bbuserinfo['email'];
		$email->send_mail();
		show_processinfo($forums->lang['sendmail']);
		$response->call('hideElement', 'operation_pannel');
	}
	return $response;
}

function digg_thread($tid)
{
	global $_INPUT, $DB, $bbuserinfo, $forums, $response, $bboptions;
	if (! $bbuserinfo['id'])
	{
		show_processinfo($forums->lang['notlogin']);
		return $response;
	}
	$tid = intval($tid);
	if (!$tid)
	{
		show_processinfo($forums->lang['select_digg_thread']);
		return $response;
	}
	$digg = $DB->query_first('SELECT digg_id
							  FROM ' . TABLE_PREFIX . 'digg_log
							  WHERE user_id=' . $bbuserinfo['id'] . '
							  	AND threadid=' . $tid);
	if ($digg)
	{
		show_processinfo($forums->lang['thread_you_digged']);
		return $response;
	}
	$digg_exp = $forums->func->fetch_user_digg_exp();
	$sql = 'UPDATE ' . TABLE_PREFIX . 'thread
				SET digg_users = digg_users + 1, digg_exps = digg_exps + ' . $digg_exp . '
			WHERE tid=' . $tid;
	$DB->query($sql);
	$digg_log = array(
			'threadid' => $tid,
			'exponent' => $digg_exp,
			'user_id' => $bbuserinfo['id'],
			'username' => $bbuserinfo['name'],
			'ip' => IPADDRESS,
			'digg_time' => TIMENOW,
	);
	$DB->insert(TABLE_PREFIX . 'digg_log', $digg_log);
	$msg = sprintf($forums->lang['thread_digged_suc'], $digg_exp);
	show_processinfo($msg);
	$now_digg_exp = $DB->query_first('SELECT digg_users, digg_exps
									FROM ' . TABLE_PREFIX . 'thread
								WHERE tid = ' . $tid);
	$digg_users = sprintf($forums->lang['how_digg_users'], intval($now_digg_exp['digg_users']));
	$response->assign('digg_users_num_' . $tid, 'innerHTML', $digg_users);
	$response->assign('digg_exponent_' . $tid, 'innerHTML', intval($now_digg_exp['digg_exps']));
	return $response;
}
?>