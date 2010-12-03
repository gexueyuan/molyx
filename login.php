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
define('THIS_SCRIPT', 'login');
require_once('./global.php');

class login
{
	function show($message = '')
	{
		global $forums, $bboptions;
		$forums->func->load_lang('login');
		if ($bboptions['forcelogin'] == 1)
		{
			$this->message = $forums->lang['forcelogin'];
		}

		switch (input::get('do', ''))
		{
			case 'login':
				$this->dologin();
				break;
			case 'logout':
				$this->dologout();
				break;
			case 'autologin':
				$this->autologin();
				break;
			default:
				$this->loginpage();
				break;
		}
	}

	function loginpage()
	{
		global $forums, $bboptions, $bbuserinfo;
		if ($bbuserinfo['id'])
		{
			$forums->func->standard_redirect();
		}
		if ($this->message != '')
		{
			$message = $this->message;
			$show['errors'] = true;
		}
		if ($bboptions['allowselectstyles'] && count($forums->cache['style']) > 1)
		{
			$show['style'] = true;
			$select_style = '';
			foreach($forums->cache['style'] as $id => $style)
			{
				$select_style .= '<option value="' . $id . '">';
				$select_style .= depth_mark($style['depth'], '--') . $style['title'];
				$select_style .= "</option>\n";
			}
		}
		$referer = $forums->url;
		$nav = array($forums->lang['dologin']);
		$pagetitle = $forums->lang['dologin'] . ' - ' . $bboptions['bbtitle'];
		include $forums->func->load_template('login_index');
		exit;
	}

	function dologin()
	{
		global $DB, $forums, $bboptions;
		$username = input::get('username', '');
		$password = input::get('password', '');
		if ($username == '' || $password == '')
		{
			$forums->func->standard_error('plzinputallform');
		}

		$type = input::get('logintype', 0);
		if ($type == 2)
		{
			$where = 'id = ' . intval($username);
		}
		else if ($type == 3)
		{
			if (strlen($username) < 6)
			{
				$forums->func->standard_error('erroremail');
			}
			$username = clean_email($username);
			if (!$username)
			{
				$forums->func->standard_error('erroremail');
			}
			$where = 'email = ' . $DB->validate($username);
		}
		else
		{
			$charset = input::get('charset', '');
			if ($charset == 'gb' || $charset == 'big5')
			{
				$username = encoding::convert($username, $charset, 'utf-8');
			}

			$check_name = input::unclean($username);
			if (utf8::strlen($check_name) > 32)
			{
				$forums->func->standard_error('nametoolong');
			}
			$username = str_replace('|', '&#124;', $username);
			$where = 'name = ' . $DB->validate($username);
		}
		$check_password = preg_replace('/&#([0-9]+);/', '-', $password);
		if (strlen($check_password) > 32)
		{
			$forums->func->standard_error("passwordtoolong");
		}
		$password = md5($password);
		$this->verify_strike_status($username);
		$user = $DB->queryFirst("SELECT id, name, email, usergroupid, password, host, options, salt, avatar from " . TABLE_PREFIX . "user WHERE $where");
		if (empty($user['id']))
		{
			$this->message = $forums->lang['nouser'];
			$this->exec_strike_user($username);
		}
		$old_pwd = $user['password'];
		if (strlen($old_pwd) == 16)
		{
			$check_pwd = substr($password, 8, 16);
			if ($old_pwd == $check_pwd)
			{
				$mysaltpassword = md5($password . $user['salt']);
				$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "user SET password='" . $mysaltpassword . "' WHERE id = " . $user['id']);
			}
			else
			{
				$this->message = $forums->lang['errorpassword'];
				$this->exec_strike_user($username);
			}
		}
		else if ($user['password'] != md5($password . $user['salt']))
		{
			$this->message = $forums->lang['errorpassword'];
			$this->exec_strike_user($username);
		}

		if ($user['host'] == '' || $user['host'] == '127.0.0.1')
		{
			$DB->update(TABLE_PREFIX . 'user', array('host' => IPADDRESS), 'id = ' . $user['id']);
		}
		$forums->func->convert_bits_to_array($user, $user['options']);
		$sessionid = input::get('s', '');
		if ($forums->func->get_cookie('sessionid'))
		{
			$sessionid = $forums->func->get_cookie('sessionid');
		}
		$invisible = input::get('invisible', 0);

		$sql_array = array(
			'username' => $user['name'],
			'userid' => $user['id'],
			'avatar' => $user['avatar'],
			'lastactivity' => TIMENOW,
			'usergroupid' => $user['usergroupid'],
			'host' => IPADDRESS,
			'useragent' => USER_AGENT,
			'invisible' => $invisible
		);
		if ($sessionid)
		{
			$DB->udpate(TABLE_PREFIX . 'session', $sql_array, 'sessionhash = ' . $DB->validate($sessionid), SHUTDOWN_QUERY);
		}
		else
		{
			$sessionid = md5(uniqid(microtime()));
			$sql_array['sessionhash'] = $sessionid;
			$DB->insert(TABLE_PREFIX . 'session', $sql_array, SHUTDOWN_QUERY);
		}
		$bbuserinfo = $user;
		$forums->sessionid = $sessionid;
		$url = '';

		$referer = input::get('referer', '');
		if ($referer && THIS_SCRIPT != 'register' && strpos($referer, 'logout') === false)
		{
			$url = preg_replace("!s=(\w){32}!", '', $referer);
		}

		$sql_array = array(
			'options' => $bbuserinfo['options'],
		);

		if ($bboptions['allowselectstyles'])
		{
			$styleid = input::get('style', 0);
			if ($forums->cache['style'][$styleid]['userselect'])
			{
				$sql_array['style'] = $styleid;
			}
		}

		$bbuserinfo['options'] = $forums->func->convert_array_to_bits(array_merge($bbuserinfo , array(
			'invisible' => input::get('invisible', 0),
			'loggedin' => 1
		)));

		$DB->update(TABLE_PREFIX . "user", $sql_array, "id='" . $bbuserinfo['id'] . "'", SHUTDOWN_QUERY);
		$DB->delete(TABLE_PREFIX . "useractivation", "userid='" . $bbuserinfo['id'] . "' AND type=1", SHUTDOWN_QUERY);
		$DB->delete(TABLE_PREFIX . 'strikes', 'strikeip = ' . $DB->validate(IPADDRESS) . ' AND username = ' . $DB->validate($username), SHUTDOWN_QUERY);

		$cookie_date = input::get('cookiedate', 0);
		if ($cookie_date)
		{
			$forums->func->set_cookie("userid", $user['id'], $cookie_date);
			$forums->func->set_cookie("password", $user['password'], $cookie_date);
			$forums->func->set_cookie("sessionid", $forums->sessionid, $cookie_date);
		}

		$return = input::get('return', '', false);
		if ($return)
		{
			$return = rawurldecode($return);
			if (preg_match("#^http://#", $return))
			{
				$forums->func->standard_redirect($return);
			}
		}
		$text = $forums->lang['welcomeback'] . ': ' . $bbuserinfo['name'];
		$forums->func->redirect_screen($text, $url);
	}

	function dologout()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$bbuserinfo['options'] = $forums->func->convert_array_to_bits(array_merge($bbuserinfo , array('invisible' => $bbuserinfo['invisible'], 'loggedin' => 0)));

		$DB->update(TABLE_PREFIX . "session", array(
			'username' => '',
			'userid' => 0,
			'invisible' => 0,
			'avatar' => 0
		), "sessionhash='" . $forums->sessionid . "'");

		$DB->update(TABLE_PREFIX . "user", array(
			'options' =>$bbuserinfo['options'],
			'lastvisit' => TIMENOW,
			'lastactivity' => TIMENOW
		), 'id=' . $bbuserinfo['id'], SHUTDOWN_QUERY);

		$forums->func->set_cookie('password' , '-1');
		$forums->func->set_cookie('userid' , '-1');
		$forums->func->set_cookie('sessionid', '-1');
		$forums->func->set_cookie('threadread', '-1');
		$forums->func->set_cookie('invisible' , '-1');
		$forums->func->set_cookie('forumread', '-1');
		if (is_array($_COOKIE))
		{
			foreach($_COOKIE AS $cookie => $value)
			{
				if (preg_match("/^(" . $bboptions['cookieprefix'] . ".*$)/i", $cookie, $match))
				{
					$forums->func->set_cookie(str_replace($bboptions['cookieprefix'], "", $match[0]) , '', -1);
				}
			}
		}

		$return = input::get('return', '', false);
		if ($return)
		{
			$return = rawurldecode($return);
			if (preg_match("#^http://#", $return))
			{
				$forums->func->standard_redirect($return);
			}
		}
		$text = $forums->lang['alreadylogout'];
		$forums->func->redirect_screen($text, "");
	}

	function autologin()
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		if (! $bbuserinfo['id'])
		{
			$userid = intval($forums->func->get_cookie('userid'));
			$password = $forums->func->get_cookie('password');
			If ($userid AND $password)
			{
				$DB->query("SELECT * FROM " . TABLE_PREFIX . "user WHERE id='$userid' AND password=" . $DB->validate($password));
				if ($user = $DB->fetch())
				{
					$bbuserinfo = $user;
					$forums->func->load_style();
					$forums->sessionid = "";
					$forums->func->set_cookie('sessionid', '-1');
				}
			}
		}
		$login_success = $forums->lang['loginsuccess'];
		$login_failed = $forums->lang['loginfailed'];
		$show = false;
		$type = input::get('logintype', '');
		switch ($type)
		{
			case 'fromreg':
				$login_success = $forums->lang['regsuccess'];
				$login_failed = $forums->lang['regfailed'];
				$show = true;
				break;
			case 'fromemail':
				$login_success = $forums->lang['mailsuccess'];
				$login_failed = $forums->lang['mailfailed'];
				$show = true;
				break;
			case 'frompass':
				$login_success = $forums->lang['passsuccess'];
				$login_failed = $forums->lang['passfailed'];
				$show = true;
				break;
		}
		if ($bbuserinfo['id'])
		{
			if ($show)
			{
				$forums->func->redirect_screen($login_success);
			}
			else
			{
				$forums->func->standard_redirect();
			}
		}
		else
		{
			if ($show)
			{
				$forums->func->redirect_screen($login_failed, 'login.php');
			}
			else
			{
				$forums->func->standard_redirect('login.php' . $forums->si_sessionurl);
			}
		}
	}

	function verify_strike_status($username = '')
	{
		global $DB, $forums;
		$DB->queryUnbuffered("DELETE FROM " . TABLE_PREFIX . "strikes WHERE striketime < " . (TIMENOW - 3600));
		$strikes = $DB->queryFirst("SELECT COUNT(*) AS strikes, MAX(striketime) AS lasttime
			FROM " . TABLE_PREFIX . "strikes
			WHERE strikeip = " . $DB->validate(IPADDRESS) . "
				AND username = " . $DB->validate($username));
		$this->strikes = $strikes['strikes'];
		if ($this->strikes >= 5 AND $strikes['lasttime'] > TIMENOW - 900)
		{
			$this->message = $forums->lang['strikefailed1'];
			return $this->loginpage();
		}
		$maxstrikes = $DB->queryFirst('SELECT COUNT(*) AS strikes
			FROM ' . TABLE_PREFIX . 'strikes
			WHERE strikeip = ' . $DB->validate(IPADDRESS));
		if ($this->strikes >= 30 AND $strikes['lasttime'] > TIMENOW - 1800)
		{
			$this->message = $forums->lang['strikefailed2'];
			return $this->loginpage();
		}
	}

	function exec_strike_user($username = '')
	{
		global $DB, $forums;
		$DB->insert(TABLE_PREFIX . 'strikes', array(
			'striketime' => TIMENOW,
			'strikeip' => IPADDRESS,
			'username' => $username
		), SHUTDOWN_QUERY);
		$this->strikes++;
		$times = $this->strikes;
		$forums->lang['striketimes'] = sprintf($forums->lang['striketimes'], $times);
		$this->message .= $forums->lang['striketimes'];
		return $this->loginpage();
	}
}

$output = new login();
$output->show();