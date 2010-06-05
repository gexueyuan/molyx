<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'register');
if (isset($_GET['do']) && $_GET['do'] === 'showimage')
{
	$content_type = true;
}
require_once('./global.php');

class register
{
	function show()
	{
		global $forums, $bboptions;
		$forums->func->load_lang('register');
		$forums->lang['errorusername'] = sprintf($forums->lang['errorusername'], $bboptions['usernameminlength'], $bboptions['usernamemaxlength']);

		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();

		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();

		require_once(ROOT_PATH . "includes/functions_showcode.php");
		$this->showcode = new functions_showcode();

		switch (input::get('do', ''))
		{
			case 'create':
				$this->create();
				break;
			case 'validate':
				$this->validate();
				break;
			case 'activationaccount':
				$this->do_form();
				break;
			case 'lostpassform':
				$this->do_form('lostpass');
				break;
			case 'changeemail':
				$this->do_form('newemail');
				break;
			case 'lostpassword':
				$this->lostpassword();
				break;
			case 'sendlostpassword':
				$this->sendlostpassword();
				break;
			case 'showimage':
				$simg = input::get('simg', 0);
				$this->showcode->showimage($simg);
				break;
			case 'resend':
				$this->reactivationform();
				break;
			case 'reactivation':
				$this->do_reactivation();
				break;
			case 'checkname':
				$this->checkusername();
				break;
			case 'checkemail':
				$this->checkeusermail();
				break;
			case 'safechange':
				$userid = input::get('id', 0);
				$this->safechange($userid);
				break;
			default:
				$this->start_register();
				break;
		}
	}

	function start_register($errors = "")
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		if (!$bboptions['allowregistration'] OR !$bboptions['bbactive'])
		{
			$forums->func->standard_error("notallowregistration");
		}
		$errors = $forums->lang[$errors];
		if ($bbuserinfo['id'])
		{
			$forums->func->standard_redirect();
		}
		if ($bboptions['reg_ip_time'] > 0)
		{
			$check_time = TIMENOW - $bboptions['reg_ip_time'] * 60;
			$result = $DB->query("SELECT id
				FROM " . TABLE_PREFIX . "user
				WHERE host != '127.0.0.1'
					AND host = " . $DB->validate(IPADDRESS) . "
					AND joindate > {$check_time}");
			if ($DB->num_rows($result))
			{
				$forums->func->standard_error("limit_time_registration", false, $bboptions['reg_ip_time']);
			}
		}
		$pagetitle = $forums->lang['register'] . " - " . $bboptions['bbtitle'];
		$step = input::int('step');
		if (!$step)
		{
			$nav = array($forums->lang['register'] . ' - ' . $forums->lang['step1']);
			$cache = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname='registerrule'");
			$text = $cache['value'] ? $cache['value'] : $cache['defaultvalue'];
			$text = str_replace("\n", "<br />", $text);
			$text = str_replace("{bbtitle}", $bboptions['bbtitle'], $text);
		}
		else
		{
			if (!input::get('agree_to_terms', 0))
			{
				$forums->func->standard_error("notagreeterms", false, $bboptions['forumindex']);
			}
			if ($bboptions['moderatememberstype'])
			{
				$show['extra'] = true;
			}
			$nav = array($forums->lang['register'] . ' - ' . $forums->lang['step2']);
			$this->clean_validations();
			if ($bboptions['enableantispam'])
			{
				$regimagehash = md5(uniqid(microtime()));
				$imagestamp = mt_rand(100000, 999999);
				$DB->insert(TABLE_PREFIX . 'antispam', array(
					'regimagehash' => $regimagehash,
					'imagestamp' => $imagestamp,
					'host' => IPADDRESS,
					'dateline' => TIMENOW
				));
			}
			if ($bboptions['enableantispam'] == 'gd')
			{
				$show['gd'] = true;
			}
			else if ($bboptions['enableantispam'] == 'gif')
			{
				$this->showcode->rc = $regimagehash;
				$image = $this->showcode->showimage();
				$show['gif'] = true;
			}
			$offset = input::get('timezoneoffset', 8);
			$time_select = "<select name='timezoneoffset'>";
			require_once(ROOT_PATH . "includes/functions_user.php");
			$this->fu = new functions_user();
			foreach($this->fu->fetch_timezone() AS $off => $words)
			{
				$time_select .= $off == $offset ? "<option value='$off' selected='selected'>$words</option>\n" : "<option value='$off'>$words</option>\n";
			}
			$time_select .= "</select>";
			$usepm = 'checked="checked"';
			$pmpop = 'checked="checked"';
			$allowadmin = 'checked="checked"';
			$pmover = 'checked="checked"';
			$pmwarn = 'checked="checked"';
			if (input::get('do', '') == 'creat')
			{
				$usepm = input::get('usepm', 0) ? 'checked="checked"' : '';
				$pmpop = input::get('pmpop', 0) ? 'checked="checked"' : '';
				$allowadmin = input::get('allowadmin', 0) ? 'checked="checked"' : '';
				$pmover = input::get('pmover', 0) ? 'checked="checked"' : '';
				$pmwarn = input::get('pmwarn', 0) ? 'checked="checked"' : '';
				$pmwarnmode = input::get('pmwarnmode', 0) ? 'checked="checked"' : '';
			}
			$usewysiwyg = input::get('usewysiwyg', 0) ? 'checked="checked"' : '';
			$emailonpm = input::get('emailonpm', 0) ? 'checked="checked"' : '';
			$hideemail = input::get('hideemail', 0) ? 'checked="checked"' : '';
			$dst_checked = input::get('dst', 0) ? 'checked="checked"' : '';
			$forums->lang['namefaq'] = sprintf($forums->lang['namefaq'], $bboptions['usernameminlength'], $bboptions['usernamemaxlength']);

			// 自定义字段
			$usrext_field = $this->get_usrext_form();

			$mxajax_register_functions = array('check_user_account', 'check_user_email'); //注册ajax函数
			require_once(ROOT_PATH . 'includes/ajax/ajax.php');
			add_head_element('js', ROOT_PATH . 'scripts/register.js');
			$username = input::str('username');
		}

		require $forums->func->load_template('register');
		exit;
	}

	function get_usrext_form()
	{
		global $forums;
		$forums->func->check_cache('userextrafield');

		$return = array('must' => array(), 'other' => array());
		foreach ($forums->cache['userextrafield']['a'] as $k => $v)
		{
			$_k = input::get($k, '');
			$form = '';
			$type = isset($forums->cache['userextrafield']['f'][$k]) ? 'must' : 'other';
			switch ($v['showtype'])
			{
				case 'text':
					$form = '<input type="text" size="25" value="' . $_k . '" name="' . $k . '" class="input_normal"';
					$form .= ($type == 'must') ? ' tabindex="%s"' : '';
					$form .= ' />';
				break;

				case 'select':
					$form = '<select name="' . $k . '"';
					$form .= ($type == 'must') ? ' tabindex="%s"' : '';
					$form .= '>';
					foreach ($v['listcontent'] as $list)
					{
						$form .= '<option value="' . $list[0] . '"';
						$form .= ($_k == $list[0]) ? ' selected="selected"' : '';
						$form .= '>' . $list[1] . '</option>';
					}
					$form .= '</select>';
				break;

				case 'textarea':
					$form = '<textarea cols="40" rows="5" name="' . $k . '"';
					$form .= ($type == 'must') ? ' tabindex="%s"' : '';
					$form .= '>' . $_k . '</textarea>';
				break;
			}
			$return[$type][] = array(
				'name' => $v['fieldname'],
				'desc' => $v['fielddesc'],
				'html' => $form
			);
		}
		return $return;
	}

	function create()
	{
		global $forums, $DB, $bboptions;
		if (!$bboptions['allowregistration'] OR !$bboptions['bbactive'])
		{
			$forums->func->standard_error("notallowregistration");
		}

		$username = input::get('username', '');
		$check = unclean_value($username);
		$len_u = utf8_strlen($check);
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
					if (!$v) continue;
					if ($v == "\'" || $v == '\"' || $v == "\\\\")
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
			return $this->start_register('hasnoallowchars');
		}

		$password = input::get('password', '');
		$email = strtolower(input::get('email', ''));
//		if (strtolower(input::get('emailconfirm', '')) != $email)
//		{
//			return $this->start_register('erroremailconfirm');
//		}
		if (empty($username) || $len_u < $bboptions['usernameminlength'] || $len_u > $bboptions['usernamemaxlength'] || (strlen($username) > 60))
		{
			return $this->start_register('errorusername');
		}
		if (empty($password) || (utf8_strlen($password) < 3) || (strlen($password) > 32))
		{
			return $this->start_register('passwordfaq');
		}
		if (input::get('passwordconfirm', '') != $password)
		{
			return $this->start_register('errorpassword');
		}
		if (strlen($email) < 6)
		{
			return $this->start_register('erroremail');
		}
		$email = clean_email($email);
		if (! $email)
		{
			return $this->start_register('erroremail');
		}
		$checkuser = $DB->query_first("SELECT id, name, email, usergroupid, password, host, salt
				FROM " . TABLE_PREFIX . "user
				WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'");
		if (($checkuser['id']) OR ($username == $forums->lang['guest']))
		{
			return $this->start_register('namealreadyexist');
		}
		$DB->query("SELECT email FROM " . TABLE_PREFIX . "user WHERE email = '" . $email . "'");
		if ($DB->num_rows() != 0)
		{
			$this->start_register('mailalreadyexist');
			return;
		}
		$banfilter = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "banfilter WHERE type != 'title'");
		while ($r = $DB->fetch_array())
		{
			$banfilter[ $r['type'] ][] = $r['content'];
		}
		if (is_array($banfilter['name']) AND count($banfilter['name']))
		{
			foreach ($banfilter['name'] AS $n)
			{
				if ($n == "")
				{
					continue;
				}
				if (preg_match("/" . preg_quote($n, '/') . "/i", $username))
				{
					return $this->start_register('badusername');
				}
			}
		}
		if (is_array($banfilter['email']) AND count($banfilter['email']))
		{
			foreach ($banfilter['email'] AS $banemail)
			{
				$banemail = str_replace('*', '.*' , $banemail);
				if (preg_match("/$banemail/", $email))
				{
					$this->start_register("bademail");
				}
			}
		}
		//开始检测扩展字段
		$user_data = $forums->func->check_usrext_field();
		if ($user_data['err'])
		{
			$this->start_register($user_data['err']);
		}
		//检测结束
		if ($bboptions['enableantispam'])
		{
			$regimghash = input::get('regimagehash', '');
			if ($regimghash == "")
			{
				$this->start_register('badimagehash');
				return;
			}
			if (!$row = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "antispam WHERE regimagehash='" . addslashes($regimghash) . "'"))
			{
				return $this->start_register('badimagehash');
			}
			if (input::get('imagestamp', 0) != $row['imagestamp'])
			{
				return $this->start_register('badimagehash');
			}
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "antispam WHERE regimagehash='" . addslashes($regimghash) . "'");
		}

		$usergroupid = 3;
		if ($bboptions['moderatememberstype'])
		{
			$usergroupid = 1;
		}
		$newusers = $DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname = 'newuser_pm'");
		if ($DB->num_rows())
		{
			while ($newuser = $DB->fetch_array($newusers))
			{
				if ($newuser['varname'] == 'newuser_pm' AND ($newuser['value'] != '' OR $newuser['defaultvalue'] != ''))
				{
					$do_send_pm = true;
					$pm_contents = $newuser['value'] ? $newuser['value'] : $newuser['defaultvalue'];
				}
			}
		}

		$salt = generate_user_salt(5);
		$saltpassword = md5(md5($password) . $salt);
		$options['adminemail'] =input::get('allowadmin', 0) ? 1 : 0;
		$options['dstonoff'] = input::get('dst', 0) ? 1 : 0;
		$options['hideemail'] = input::get('hideemail', 0) ? 1 : 0;
		$options['usepm'] = input::get('usepm', 0) ? 1 : 0;
		$options['pmpop'] = input::get('pmpop', 0) ? 1 : 0;
		$options['pmover'] = input::get('pmover', 0) ? 1 : 0;
		$options['pmwarn'] = input::get('pmwarn', 0) ? 1 : 0;
		$options['pmwarnmode'] = input::get('pmwarnmode', 0) ? 1 : 0;
		$options['emailonpm'] = input::get('emailonpm', 0) ? 1 : 0;
		$options['usewysiwyg'] = input::get('usewysiwyg', 0) ? 1 : 0;
		$options = $forums->func->convert_array_to_bits($options);
		$emailcharset = input::get('emailcharset', 'GBK');
		$user= array('name' => $username,
			'salt' => $salt,
			'password' => $saltpassword,
			'email' => $email,
			'emailcharset' => $emailcharset,
			'usergroupid' => $usergroupid,
			'posts' => 0,
			'joindate' => TIMENOW,
			'host' => IPADDRESS,
			'timezoneoffset' => input::get('timezoneoffset', 0),
			'gender' => input::get('gender', 0),
			'forbidpost' => 0,
			'options' => $options,
			'pmtotal' => 0,
			'pmunread' => 0,
			'pmfolders' => '',
			'avatar' => 0,
			'signature' => ''
		);
		if (is_array($user_data['user']))
		{
			$user = $user + $user_data['user'];
		}

		$DB->insert(TABLE_PREFIX . 'user', $user);
		$user['id'] = $DB->insert_id();
		$userexpand = array(
			'id' => $user['id'],
		);
		if ($bboptions['updateuserview'])
		{
			$user['password'] = $password;
			update_user_view($user);
			$user['password'] = $saltpassword;
		}
		if (is_array($user_data['userexpand']))
		{
			$userexpand = $userexpand + $user_data['userexpand'];
		}
		$DB->insert(TABLE_PREFIX . 'userexpand', $userexpand);
		$this->credit->update_credit('register', $user['id'], $user['usergroupid']);
		if ($do_send_pm)
		{
			input::set('title', $forums->lang['welcome_register'] . $user['name']);
			$_POST['post'] = $pm_contents;
			input::set('username', $user['name']);
			require_once(ROOT_PATH . 'includes/functions_private.php');
			$pm = new functions_private();
			input::set('noredirect', 1);
			$pm->cookie_mxeditor = "wysiwyg";
			$pm->sendpm();
		}
		$activationkey = md5($forums->func->make_password() . TIMENOW);
		if (($bboptions['moderatememberstype'] == 'user') OR ($bboptions['moderatememberstype'] == 'admin'))
		{
			$DB->insert(TABLE_PREFIX . 'useractivation', array(
				'useractivationid' => $activationkey,
				'userid' => $user['id'],
				'usergroupid' => 3,
				'tempgroup' => 1,
				'dateline' => TIMENOW,
				'type' => 2,
				'host' => IPADDRESS
			));
			if ($bboptions['moderatememberstype'] == 'user')
			{
				$message = $this->email->fetch_email_activationaccount(array('link' => $bboptions['bburl'] . "/register.php?do=validate&amp;u=" . urlencode($user['id']) . "&amp;a=" . urlencode($activationkey),
						'name' => $user['name'],
						'linkpage' => $bboptions['bburl'] . "/register.php?do=activationaccount",
						'id' => $userid,
						'code' => $activationkey)
					);
				$this->email->build_message($message);
				$this->email->char_set = $user['emailcharset'];
				$forums->lang['registerinfo'] = sprintf($forums->lang['registerinfo'], $bboptions['bbtitle']);
				$this->email->subject = $forums->lang['registerinfo'];
				$this->email->to = $user['email'];
				$this->email->send_mail();
				$forums->lang['mustactivation'] = sprintf($forums->lang['mustactivation'], $user['name'], $user['email']);
				$forums->func->redirect_screen($forums->lang['mustactivation']);
			}
			else if ($bboptions['moderatememberstype'] == 'admin')
			{
				$forums->lang['adminactivation'] = sprintf($forums->lang['adminactivation'], $user['name']);
				$forums->func->redirect_screen($forums->lang['adminactivation']);
			}
		}
		else
		{
			$this->update_stats($user);
			$forums->func->set_cookie("userid", $user['id'] , 86400);
			$forums->func->set_cookie("password", $user['password'], 86400);
			$forums->func->standard_redirect('login.php' . $forums->sessionurl . 'do=autologin&amp;logintype=fromreg');
		}
	}

	function reactivationform($errors = "")
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$username = $bbuserinfo['id'] == "" ? '' : $bbuserinfo['name'];
		$pagetitle = $forums->lang['resend'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['resend']);
		include $forums->func->load_template('reactivation_account');
		exit;
	}

	function do_reactivation()
	{
		global $forums, $DB, $bboptions;
		$username = input::get('username', '');
		if (!$username)
		{
			$this->reactivationform('errorusername');
			return;
		}
		if (! $user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'"))
		{
			$this->reactivationform('namenotexist');
			return;
		}
		if (! $activation = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "useractivation WHERE userid='" . $user['id'] . "'"))
		{
			$this->reactivationform('namenotexist');
			return;
		}
		if ($activation['type'] == 1)
		{
			$message = $this->email->fetch_email_lostpassword(array('name' => $user['name'],
					'link' => $bboptions['bburl'] . "/register.php?do=lostpassform&u=" . $user['id'] . "&a=" . $activation['useractivationid'],
					'linkpage' => $bboptions['bburl'] . "/register.php?do=lostpassform",
					'id' => $user['id'],
					'code' => $activation['useractivationid'],
					'host' => IPADDRESS)
				);
			$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
			$this->email->build_message($message);
			$forums->lang['resetpassword'] = sprintf($forums->lang['resetpassword'], $bboptions['bbtitle']);
			$this->email->subject = $forums->lang['resetpassword'];
			$this->email->to = $user['email'];
			$this->email->send_mail();
		}
		else if ($activation['type'] == 2)
		{
			$message = $this->email->fetch_email_activationaccount(array('link' => $bboptions['bburl'] . "/register.php?do=validate&amp;u=" . urlencode($user['id']) . "&amp;a=" . urlencode($val['useractivationid']),
					'name' => $user['name'],
					'linkpage' => $bboptions['bburl'] . "/register.php?do=activationaccount",
					'id' => $userid,
					'code' => $activation['useractivationid'],)
				);
			$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
			$this->email->build_message($message);
			$forums->lang['registerinfo'] = sprintf($forums->lang['registerinfo'], $bboptions['bbtitle']);
			$this->email->subject = $forums->lang['registerinfo'];
			$this->email->to = $user['email'];
			$this->email->send_mail();
		}
		else if ($activation['type'] == 3)
		{
			$message = $this->email->fetch_email_changeemail(array('link' => $bboptions['bburl'] . "/register.php?do=validate&amp;type=newemail&amp;u=" . urlencode($user['id']) . "&amp;a=" . urlencode($activation['useractivationid']),
					'name' => $user['name'],
					'linkpage' => $bboptions['bburl'] . "/register.php?do=changeemail",
					'id' => $userid,
					'code' => $activation['useractivationid'],)
				);
			$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
			$this->email->build_message($message);
			$forums->lang['changeemail'] = sprintf($forums->lang['changeemail'], $bboptions['bbtitle']);
			$this->email->subject = $forums->lang['changeemail'];
			$this->email->to = $user['email'];
			$this->email->send_mail();
		}
		else
		{
			$this->reactivationform('namenotexist');
			return;
		}
		$forums->func->redirect_screen($forums->lang['hassendmail']);
	}

	function lostpassword($errors = "")
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		if ($bboptions['enableantispam'])
		{
			$passtime = TIMENOW - (60 * 60 * 6);
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "antispam WHERE dateline < " . $passtime . "");
			$regimagehash = md5(uniqid(microtime()));
			$imagestamp = mt_rand(100000, 999999);
			$DB->insert(TABLE_PREFIX . 'antispam', array(
				'regimagehash' => $regimagehash,
				'imagestamp' => $imagestamp,
				'host' => IPADDRESS,
				'dateline' => TIMENOW
			));
		}
		$errors = $forums->lang[$errors];
		if ($bboptions['enableantispam'] == 'gd')
		{
			$show['gd'] = true;
		}
		else if ($bboptions['enableantispam'] == 'gif')
		{
			$this->showcode->rc = $regimagehash;
			$image = $this->showcode->showimage();
			$show['gif'] = true;
		}
		$pagetitle = $forums->lang['lostpassword'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['lostpassword']);
		include $forums->func->load_template('lost_password');
		exit;
	}

	function sendlostpassword()
	{
		global $forums, $DB, $bboptions;
		if ($bboptions['enableantispam'])
		{
			$regimagehash = input::get('regimagehash', '');
			if ($regimagehash == "")
			{
				return $this->lostpassword('badimagehash');
			}
			if (! $row = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "antispam WHERE regimagehash='" . addslashes($regimagehash) . "'"))
			{
				return $this->lostpassword('badimagehash');
			}
			if (input::get('imagestamp', 0) != $row['imagestamp'])
			{
				return $this->lostpassword('badimagehash');
			}
		}
		$username = input::get('username', '');
		if ($username == "")
		{
			return $this->lostpassword('errorusername');
		}
		if (! $user = $DB->query_first("SELECT id, name, email, emailcharset, usergroupid FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'"))
		{
			return $this->lostpassword('namenotexist');
		}
		else
		{
			if (input::get('safechange', 0))
			{
				$this->safechange($user['id']);
			}
			else
			{
				$activationkey = md5($forums->func->make_password() . TIMENOW);
				$DB->shutdown_insert(TABLE_PREFIX . 'useractivation', array(
					'useractivationid' => $activationkey,
					'userid' => $user['id'],
					'usergroupid' => $user['usergroupid'],
					'tempgroup' => $user['usergroupid'],
					'dateline' => TIMENOW,
					'type' => 1,
					'host' => IPADDRESS
				));
				$message = $this->email->fetch_email_lostpassword(array('name' => $user['name'],
						'link' => $bboptions['bburl'] . "/register.php?do=lostpassform&amp;u=" . $user['id'] . "&amp;a=" . $activationkey,
						'linkpage' => $bboptions['bburl'] . "/register.php?do=lostpassform",
						'id' => $user['id'],
						'code' => $activationkey,
						'host' => IPADDRESS
						)
					);
				$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
				$this->email->build_message($message);
				$forums->lang['resetpassword'] = sprintf($forums->lang['resetpassword'], $bboptions['bbtitle']);
				$this->email->subject = $forums->lang['resetpassword'];
				$this->email->to = $user['email'];
				$this->email->send_mail();
				$forums->func->redirect_screen($forums->lang['hassendpass']);
			}
		}
	}

	function safechange($userid = 0)
	{
		global $forums, $DB, $bboptions;
		$safe = $DB->query_first("SELECT answer, question FROM " . TABLE_PREFIX . "userextra WHERE id = {$userid}");
		if (!$safe['question'] OR !$safe['answer'])
		{
			$forums->func->standard_error("cannotusesafe");
		}
		if (input::get('update', 0))
		{
			if ($safe['answer'] != input::get('answer', ''))
			{
				$forums->func->standard_error("answererror");
			}
			$password = input::get('password', '');
			if (empty($password) OR (strlen($password) < 3) OR (strlen($password) > 32))
			{
				$errors = $forums->lang['passwordfaq'];
			}
			if (input::get('passwordconfirm', '') != $password)
			{
				$errors = $forums->lang['errorpassword'];
			}
			if (!$errors)
			{
				$salt = generate_user_salt(5);
				$saltpassword = md5(md5($password) . $salt);
				$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET password='" . $saltpassword . "', salt='" . addslashes($salt) . "' WHERE id={$userid}");
				$forums->func->redirect_screen($forums->lang['hasresetpass']);
			}
		}
		$pagetitle = $forums->lang['lostpassword'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['lostpassword']);
		include $forums->func->load_template('lost_password_question');
		exit;
	}

	function validate()
	{
		global $forums, $DB, $bboptions;
		$userid = input::get('u', 0);
		$activationkey = trim(rawurldecode(input::get('a', '')));
		$type = input::get('type', '');
		$username = input::get('name', '');
		if ($type == "")
		{
			$type = 'reg';
		}
		if (!preg_match('/^(?:[\d\w]){32}$/', $activationkey) || !preg_match('/^(?:\d){1,}$/', $userid))
		{
			$forums->func->standard_error("cannotvalidate");
		}
		if ($username)
		{
			$user = $DB->query_first("SELECT id,name,password,salt,email FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'");
		}
		else
		{
			$user = $DB->query_first("SELECT id,name,password,salt,email FROM " . TABLE_PREFIX . "user WHERE id='" . $userid . "'");
		}
		if (! $user['id'])
		{
			$forums->func->standard_error("cannotvalidate");
		}
		$useractivation = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "useractivation WHERE userid='" . $user['id'] . "'");
		if (! $useractivation['userid'])
		{
			$forums->func->standard_error("cannotvalidate");
		}
		if (($useractivation['type'] == 2) && ($bboptions['moderatememberstype'] == "admin"))
		{
			$forums->func->standard_error("requireadminmoderate");
		}
		if ($useractivation['useractivationid'] != $activationkey)
		{
			$forums->func->standard_error("badimagehash");
		}
		else
		{
			if ($type == 'reg')
			{
				if ($useractivation['type'] != 2)
				{
					$forums->func->standard_error("cannotvalidate");
				}
				if (empty($useractivation['usergroupid']))
				{
					$useractivation['usergroupid'] = 3;
				}
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET usergroupid='" . intval($useractivation['usergroupid']) . "' WHERE id='" . intval($user['id']) . "'");

				$this->update_stats($user);
				$forums->func->set_cookie("userid", $user['id'], 86400);
				$forums->func->set_cookie("password", $user['password'], 86400);
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "useractivation WHERE useractivationid='" . $useractivation['useractivationid'] . "' OR (userid='" . $user['id'] . "' AND type=2)");
				$this->clean_validations();
				$forums->func->standard_redirect('login.php' . $forums->sessionurl . 'do=autologin&amp;logintype=fromreg');
			}
			else if ($type == 'lostpass')
			{
				if ($useractivation['type'] != 1)
				{
					$forums->func->standard_error("notfindpassword");
				}
				$pass1 = input::get('pass1', '');
				$pass2 = input::get('pass2', '');
				if ($pass1 == '' OR $pass2 == '')
				{
					$forums->func->standard_error("plzinputallform");
				}

				if (strlen($pass1) < 3)
				{
					$forums->func->standard_error("passwordtooshort");
				}
				if ($pass1 != $pass2)
				{
					$forums->func->standard_error("errorpassword");
				}
				$newpassword = md5($pass1);
				if (! $user['email'] OR ! $newpassword)
				{
					return false;
				}
				$newpassword = md5($newpassword . $user['salt']);
				$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET password='" . $newpassword . "' WHERE id='" . intval($user['id']) . "'");
				$forums->func->set_cookie("userid", $user['id'], 86400);
				$forums->func->set_cookie("password", $newpassword, 86400);
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "useractivation WHERE useractivationid='" . $useractivation['useractivationid'] . "' OR (userid={$user['id']} AND type=1)");
				$this->clean_validations();
				$forums->func->standard_redirect('login.php' . $forums->sessionurl . 'do=autologin&amp;logintype=frompass');
			}
			else if ($type == 'newemail')
			{
				if ($useractivation['type'] != 3)
				{
					$forums->func->standard_error("validatetooold");
				}
				if (empty($useractivation['usergroupid']))
				{
					$useractivation['usergroupid'] = 3;
				}
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET usergroupid='" . intval($useractivation['usergroupid']) . "' WHERE id='" . intval($user['id']) . "'");
				$forums->func->set_cookie("userid", $user['id'], 86400);
				$forums->func->set_cookie("password", $user['password'], 86400);
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "useractivation WHERE useractivationid='" . $useractivation['useractivationid'] . "' OR (userid={$user['id']} AND type=3)");
				$this->clean_validations();
				$forums->func->standard_redirect('login.php' . $forums->sessionurl . 'do=autologin&amp;logintype=fromemail');
			}
		}
	}

	function do_form($type = 'reg')
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		$pagetitle = $forums->lang['activationform'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['activationform']);
		if ($type == 'lostpass')
		{
			$userid = input::get('u', 0);
			$activationkey = trim(rawurldecode(input::get('a', '')));
			if ($userid && $activationkey)
			{

				if (! preg_match('/^(?:[\d\w]){32}$/', $activationkey) OR ! preg_match('/^(?:\d){1,}$/', $userid))
				{
					$forums->func->standard_error("cannotvalidate");
				}
				if (! $user = $DB->query_first("SELECT * FROM  " . TABLE_PREFIX . "user WHERE id=$userid "))
				{
					$forums->func->standard_error("cannotvalidate");
				}
				$validate = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "useractivation WHERE userid=$userid AND useractivationid='$activationkey' AND type=1");
				if (!$validate['userid'])
				{
					$forums->func->standard_error("cannotvalidate");
				}
				$show['havekey'] = true;
			}
			else
			{
				$show['validate'] = true;
			}
		}
		else
		{
			$show['validate'] = true;
		}
		include $forums->func->load_template('lost_password_mail');
		exit;
	}

	function clean_validations()
	{
		global $forums, $DB, $bboptions;
		$userids = array();
		$activationids = array();
		if (intval($bboptions['removemoderate']) > 0)
		{
			$less_than = TIMENOW - $bboptions['removemoderate'] * 86400;
			$DB->query("SELECT ua.useractivationid, ua.userid, u.posts FROM " . TABLE_PREFIX . "useractivation ua LEFT JOIN " . TABLE_PREFIX . "user u ON (ua.userid=u.id) WHERE ua.dateline < " . $less_than . " AND ua.type != 1");
			while ($i = $DB->fetch_array())
			{
				if (intval($i['posts']) < 1)
				{
					$userids[] = $i['userid'];
					$activationids[] = "'" . $i['useractivationid'] . "'";
				}
			}
			if (count($userids) > 0)
			{
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "user WHERE id IN(" . implode(",", $userids) . ")");
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "useractivation WHERE useractivationid IN(" . implode(",", $activationids) . ")");
			}
		}
	}

	function update_stats($user)
	{
		global $forums, $DB;
		$DB->update_case(CACHE_TABLE, 'title', array(
			'data' => array(
				'numbermembers' => array(1, '+'),
				'newusername' => $user['name'],
				'newuserid' => intval($user['id'])
			)
		));

		$forums->func->check_cache('stats');
		$forums->cache['stats']['newusername'] = $user['name'];
		$forums->cache['stats']['newuserid'] = $user['id'];
		$forums->cache['stats']['numbermembers']++;
		$forums->func->update_cache(array('name' => 'stats'));
	}
}

$output = new register();
$output->show();