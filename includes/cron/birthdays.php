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
class cron_birthdays
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $_INPUT;
		$forums->func->load_lang('cron');

		$send_user = false;
		$show = $DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname IN ( 'birthday_send', 'birthday_send_type' )");
		if ($DB->num_rows())
		{
			while ($s = $DB->fetch_array())
			{
				$value = $s['value'] ? $s['value'] : $s['defaultvalue'];
				if ($s['varname'] == 'birthday_send' AND $value)
				{
					$send_user = true;
				}
				$send[$s['varname']] = $value;
			}
		}

		$forums->cache['birthdays'] = array();
		$birthdays = array();
		$today = $forums->func->get_time(TIMENOW, 'm-d');
		$send_title = $forums->lang['happybirthday'];
		$DB->query("SELECT ue.*, u.id, u.name, u.email, u.emailcharset, u.usergroupid, u.birthday, u.avatar FROM " . TABLE_PREFIX . "user u LEFT JOIN " . TABLE_PREFIX . "userexpand ue USING (id) WHERE u.birthday LIKE '%-$today'");
		require_once(ROOT_PATH . "includes/functions_email.php");
		$this->email = new functions_email();
		while ($r = $DB->fetch_array())
		{
			$birthdays[ $r['id'] ] = $r;
			$birthday_ids[] = $r['id'];
		}
		if ($send_user)
		{
			$skip_user = array();
			$update_id = array();
			if (is_array($birthday_ids))
			{
				$lefttime = TIMENOW - 31536000;
				$DB->query("SELECT * FROM " . TABLE_PREFIX . "birthday WHERE id IN (" . implode(", ", $birthday_ids) . ")");
				if ($DB->num_rows())
				{
					while ($sended = $DB->fetch_array())
					{
						if ($sended['dateline'] > $lefttime)
						{
							$skip_user[] = $sended['id'];
						}
						else
						{
							$update_id[] = $sended['id'];
						}
					}
				}
			}
			foreach ($birthdays AS $uid => $user)
			{
				if (in_array($uid, $skip_user)) continue;
				$send_message = str_replace("{name}", $user['name'], $send['birthday_send']);
				$send_message = preg_replace("#{(\w+)=(\d+)}#ise", "\$this->check_count('\\1', '\\2')", $send_message);
				$this->db_update = true;
				if (is_array($this->update_user))
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET " . implode(", ", $this->update_user) . " WHERE id = " . $uid . "");
				}
				if (is_array($this->update_expand))
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET " . implode(", ", $this->update_expand) . " WHERE id = " . $uid . "");
				}
				if ($send['birthday_send_type'] == 1 OR $send['birthday_send_type'] == 2)
				{
					$_INPUT['title'] = $send_title;
					$_POST['post'] = $send_message;
					$_INPUT['username'] = $user['name'];
					require_once(ROOT_PATH . 'includes/functions_private.php');
					$pm = new functions_private();
					$_INPUT['noredirect'] = 1;
					$bbuserinfo['usewysiwyg'] = 0;
					$pm->sendpm();
				}
				if ($send['birthday_send_type'] == 1 OR $send['birthday_send_type'] == 3)
				{
					$this->email->char_set = $user['emailcharset']?$user['emailcharset']:'GBK';
					$this->email->build_message($send_message);
					$this->email->subject = $send_title;
					$this->email->to = $user['email'];
					$this->email->send_mail();
				}
				if (!in_array($uid, $update_id))
				{
					$insert_birthday[] = "($uid, " . TIMENOW . ")";
				}
			}
		}
		if (count($update_id) > 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "birthday SET dateline = '" . TIMENOW . "' WHERE id IN (" . implode(",", $update_id) . ")");
		}
		if (count($insert_birthday) > 0)
		{
			$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "birthday (id, dateline) VALUES " . implode(",", $insert_birthday) . "");
		}
		$forums->func->update_cache(array('name' => 'birthdays', 'value' => $birthdays, 'array' => 1));
		$forums->lang['updatebirthdays'] = sprintf($forums->lang['updatebirthdays'], intval(count($birthdays)));
		$this->class->cronlog($this->cron, $forums->lang['updatebirthdays']);
	}

	function check_count($this_key, $this_value)
	{
		global $DB, $forums;

		$forums->func->check_cache('creditlist');
		$usedcredit = array();
		if ($forums->cache['creditlist']) 
		{
			foreach ($forums->cache['creditlist'] as $k => $v) 
			{
				$usedcredit[$v['tag']] = $v['name'];
			}
		}
		if ($usedcredit[$this_key] AND !$this->db_update)
		{
			$this->update_expand[] = "$this_key = $this_key + $this_value";
		}
		return $this_value;
	}

	function register_class(&$class)
	{
		$this->class = &$class;
	}

	function pass_cron($this_cron)
	{
		$this->cron = $this_cron;
	}
}

?>