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
# $Id: sessions.php 200 2007-09-27 09:38:07Z develop_tong $
# **************************************************************************#
class session
{
	var $user = array();
	var $sessionid = 0;
	var $badsessionid = 0;

	function loadsession()
	{
		global $DB, $_INPUT, $forums, $bboptions;
		if ($bboptions['loadlimit'] > 0)
		{
			if (!IS_WIN && @file_exists('/proc/loadavg') && $data = @file_get_contents('/proc/loadavg'))
			{
				$load_avg = explode(' ', $data);
				if (trim($load_avg[0]) > $bboptions['loadlimit'])
				{
					$forums->func->standard_error("loadlimit");
				}
			}
		}
		$this->user = array('id' => 0, 'name' => "", 'usergroupid' => 2);

		$cookie = array();
		// $cookie['sessionid']   = $forums->func->get_cookie('sessionid');
		$cookie['userid'] = $_GET['bbuid'] ? $_GET['bbuid'] : $forums->func->get_cookie('userid');
		$cookie['password'] = $_GET['bbpwd'] ? $_GET['bbpwd'] : $forums->func->get_cookie('password');
		$forums->mobile = false;
		if ($_INPUT['s'])
		{
			$this->get_session($_INPUT['s']);
			$forums->sessiontype = 'url';
		}
		else if ($cookie['sessionid'])
		{
			$this->get_session($cookie['sessionid']);
			$forums->sessiontype = 'cookie';
		}
		else
		{
			$this->sessionid = 0;
		}
		if ($this->sessionid)
		{
			if ($this->userid != 0 AND !empty($this->userid))
			{
				$this->load_user($this->userid);
				if (!$this->user['id'] OR $this->user['id'] == 0)
				{
					$this->unload_user();
					$this->update_guest_session();
				}
				else
				{
					$this->update_user_session();
				}
			}
			else
			{
				$this->update_guest_session();
			}
		}
		else
		{
			if ($cookie['userid'] != "" AND $cookie['password'] != "")
			{
				$this->load_user($cookie['userid']);
				if ((! $this->user['id']) OR ($this->user['id'] == 0))
				{
					$this->unload_user();
					$this->create_session();
				}
				else
				{
					if ($this->user['password'] == $cookie['password'])
					{
						$this->create_session($this->user['id']);
					}
					else
					{
						$this->unload_user();
						$this->create_session();
					}
				}
			}
			else
			{
				$this->create_session();
			}
		}
		if (! $this->user['id'])
		{
			$this->user = $forums->func->set_up_guest();
			$this->user['lastactivity'] = TIMENOW;
			$this->user['lastvisit'] = TIMENOW;
		}
		$forums->func->check_cache("usergroup_{$this->user['usergroupid']}", 'usergroup');
		$this->user = array_merge($this->user, $forums->cache["usergroup_{$this->user['usergroupid']}"]);
		$this->build_group_permissions();
		if ($this->user['usergroupid'] != 2)
		{
			if ($this->user['supermod'] == 1)
			{
				$this->user['is_mod'] = 1;
			}
			else
			{
				foreach (array('moderator_user_' . $this->user['id'], 'moderator_group_' . $this->user['usergroupid']) as $cache_name)
				{
					$forums->func->check_cache($cache_name, 'moderator', true);
					if (false !== $forums->cache[$cache_name])
					{
						foreach((array) $forums->cache[$cache_name] as $i => $r)
						{
							if ($this->user['caneditusers'])
							{
								$this->user['caneditusers'] = 1;
							}
							$this->user['_moderator'][$r['forumid']] = $r;
							$this->user['is_mod'] = 1;
						}
					}
				}
			}
		}
		if ($this->user['id'])
		{
			if (! $_INPUT['lastactivity'])
			{
				if ($this->user['lastactivity'])
				{
					$_INPUT['lastactivity'] = $this->user['lastactivity'];
				}
				else
				{
					$_INPUT['lastactivity'] = TIMENOW;
				}
			}
			if (! $_INPUT['lastvisit'])
			{
				if ($this->user['lastvisit'])
				{
					$_INPUT['lastvisit'] = $this->user['lastvisit'];
				}
				else
				{
					$_INPUT['lastvisit'] = TIMENOW;
				}
			}
			if (! $this->user['lastvisit'])
			{
				$DB->update(TABLE_PREFIX . 'user', array('lastvisit' => TIMENOW, 'lastactivity' => TIMENOW), 'id = ' . $this->user['id']);
			}
			else if ((TIMENOW - $_INPUT['lastactivity']) > 300)
			{
				$this->user['loggedin'] = 1;
				$this->user['options'] = $forums->func->convert_array_to_bits($this->user);
				$DB->shutdown_update(TABLE_PREFIX . 'user', array(
					'options' => $this->user['options'],
					'lastactivity' => TIMENOW
				), 'id = ' . $this->user['id']);
			}
			if ($this->user['liftban'])
			{
				$ban_arr = banned_detect($this->user['liftban']);
				if ($ban_arr['timespan'] == -1)
				{
					$bbuserinfo = $this->user;
					$forums->func->standard_error("banuserever", true);
				}
				else if (TIMENOW >= $ban_arr['date_end'])
				{
					if ($ban_arr['banposts'] == -1)
					{
						$DB->update(TABLE_PREFIX . 'post', array('state' => 0), 'userid=' . $this->user['id']);
					}
					elseif ($ban_arr['banposts'] > 0 && $ban_arr['forumid'])
					{
						$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "post p," . TABLE_PREFIX . "thread t SET p.state = 0
							WHERE p.threadid=t.tid and p.userid={$this->user['id']} and t.forumid = {$ban_arr['forumid']}");
					}
					$DB->update(TABLE_PREFIX . 'user', array('liftban' => '','usergroupid' => $ban_arr['groupid']), 'id = ' . $this->user['id']);
				}
				else
				{
					if (!$ban_arr['forumid'] && THIS_SCRIPT != 'login')
					{
						$bbuserinfo = $this->user;
						$forums->func->standard_error("banusertemp", true, $forums->func->get_date($ban_arr['date_end'], 2));
					}
				}
			}
		}
		$forums->func->set_cookie("sessionid", $this->sessionid, 31536000);
		return $this->user;
	}

	function build_group_permissions()
	{
		global $forums;
		$this->user['membergroupid'] = $this->user['usergroupid'];
		if ($this->user['membergroupids'])
		{
			$groups_id = explode(',', $this->user['membergroupids']);
			$exclude = array('usergroupid', 'grouptitle', 'groupicon', 'onlineicon', 'opentag', 'closetag');
			$less_is_more = array('canappendedit', 'searchflood');
			if (count($groups_id))
			{
				foreach($groups_id as $pid)
				{
					$forums->func->check_cache("usergroup_{$pid}", 'usergroup');
					if ($forums->cache["usergroup_{$pid}"]['usergroupid'])
					{
						$this->user['membergroupid'] .= ',' . $pid;
						foreach($forums->cache["usergroup_{$pid}"] AS $k => $v)
						{
							if (! in_array($k, $exclude))
							{
								if (in_array($k, $less_is_more))
								{
									if ($v < $this->user[ $k ])
									{
										$this->user[ $k ] = $v;
									}
								}
								else
								{
									if ($v > $this->user[ $k ])
									{
										$this->user[ $k ] = $v;
									}
								}
							}
						}
					}
				}
			}
			$rmp = array();
			$tmp = explode(',', preg_replace(array("/,{2,}/", "/^,/", "/,$/"), array(",", "", ""), $this->user['membergroupid']));
			if (count($tmp))
			{
				foreach($tmp AS $t)
				{
					$rmp[ $t ] = $t;
				}
			}
			if (count($rmp))
			{
				$this->user['membergroupid'] = implode(',', $rmp);
			}
		}
		$forums->perm_id_array = explode(",", $this->user['membergroupid']);
	}

	function load_user($userid = 0)
	{
		global $DB, $forums;
		$userid = intval($userid);
		if ($userid != 0)
		{
			if ($this->user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE id='" . $userid . "'"))
			{
				$this->user['options'] = intval($this->user['options']);
				$forums->func->convert_bits_to_array($this->user, $this->user['options']);
			}
			if (($this->user['id'] == 0) OR (empty($this->user['id'])))
			{
				$this->unload_user();
			}
		}
		unset($userid);
	}

	function unload_user()
	{
		global $forums;
		$forums->func->set_cookie("sessionid" , "0", -1);
		$forums->func->set_cookie("userid" , "0", -1);
		$forums->func->set_cookie("password" , "0", -1);
		$this->user['id'] = 0;
		$this->user['name'] = "";
	}

	function update_user_session()
	{
		global $DB, $_INPUT;
		if (! $this->sessionid)
		{
			return $this->create_session($this->user['id']);
		}
		if (empty($this->user['id']))
		{
			$this->unload_user();
			$this->create_session();
			return;
		}
		$inforum = $_INPUT['t'] ? '' : ', inforum=' . intval($_INPUT['f']);
		$DB->update(TABLE_PREFIX . 'session', array(
			'username' => $this->user['name'],
			'userid' => intval($this->user['id']),
			'usergroupid' => $this->user['usergroupid'],
			'inforum' => intval($_INPUT['f']),
			'inthread' => intval($_INPUT['t']),
			'invisible' => $this->user['invisible'],
			'lastactivity' => TIMENOW,
			'location' => substr(SCRIPTPATH, 0, 250),
			'badlocation' => 0,
			'mobile' => 1
		), "sessionhash = '{$this->sessionid}'");
	}

	function update_guest_session()
	{
		global $DB, $_INPUT;
		if (! $this->sessionid)
		{
			$this->create_session();
			return;
		}
		$DB->update(TABLE_PREFIX . 'session', array(
			'username' => '',
			'userid' => 0,
			'usergroupid' => 2,
			'inforum' => intval($_INPUT['f']),
			'inthread' => intval($_INPUT['t']),
			'invisible' => 0,
			'lastactivity' => TIMENOW,
			'location' => substr(SCRIPTPATH, 0, 250),
			'badlocation' => 0,
			'mobile' => 1
		), "sessionhash = '" . $this->sessionid . "'");
	}

	function get_session($sessionid = "")
	{
		global $DB, $bboptions;
		$result = array();
		$query = "";
		$sessionid = preg_replace("/([^a-zA-Z0-9])/", "", $sessionid);
		if ($sessionid)
		{
			if (!$result = $DB->query_first("SELECT sessionhash, userid, lastactivity, location
				FROM " . TABLE_PREFIX . 'session
				WHERE sessionhash = ' . $DB->validate($sessionid) . '
					AND host = ' . $DB->validate(IPADDRESS)))
			{
				$this->badsessionid = $sessionid;
				$this->sessionid = 0;
				$this->userid = 0;
			}
			else
			{
				$this->sessionid = $result['sessionhash'];
				$this->userid = $result['userid'];
			}
			unset($result);
			return;
		}
	}

	function create_session($userid = 0)
	{
		global $DB, $_INPUT, $forums, $bboptions;
		$cookietimeout = $bboptions['cookietimeout'] ? (TIMENOW - $bboptions['cookietimeout'] * 60) : (TIMENOW - 3600);
		if ($userid)
		{
			$DB->delete(TABLE_PREFIX . 'session', "userid = '{$this->user['id']}'");
			if (TIMENOW - $this->user['lastactivity'] > 3600)
			{
				$forums->func->set_cookie('threadread', '');
				$this->user['loggedin'] = 1;
				$this->user['options'] = $forums->func->convert_array_to_bits($this->user);
				$DB->update(TABLE_PREFIX . 'user', array(
					'options' => $this->user['options'],
					'lastvisit' => array('lastactivity', true),
					'lastactivity' => TIMENOW
				), 'id = ' . $this->user['id']);
				$_INPUT['lastvisit'] = $this->user['lastactivity'];
				$_INPUT['lastactivity'] = TIMENOW;
			}
		}
		else
		{
			$extra = (empty($this->badsessionid)) ? '' : ' OR sessionhash = ' . $DB->validate($this->badsessionid);
			$DB->delete(TABLE_PREFIX . 'session', 'host = ' . $DB->validate(IPADDRESS) . $extra);
			$this->user['name'] = '';
			$this->user['id'] = 0;
			$this->user['usergroupid'] = 2;
			$this->user['invisible'] = 0;
		}
		$DB->shutdown_delete(TABLE_PREFIX . 'session', "lastactivity < $cookietimeout");
		$this->sessionid = substr(md5(uniqid(microtime())), 0, 16);

		$sql_array = array(
			'sessionhash' => $this->sessionid,
			'username' => $this->user['name'],
			'userid' => intval($this->user['id']),
			'usergroupid' => $this->user['usergroupid'],
			'inforum' => intval($_INPUT['f']),
			'inthread' => intval($_INPUT['t']),
			'inblog' => intval($_INPUT['bid']),
			'invisible' => $this->user['invisible'],
			'lastactivity' => TIMENOW,
			'location' => substr(SCRIPTPATH, 0, 250),
			'host' => IPADDRESS,
			'useragent' => USER_AGENT,
			'badlocation' => 0,
			'mobile' => 1
		);
		$DB->insert(TABLE_PREFIX . 'session', $sql_array);
	}
}
?>