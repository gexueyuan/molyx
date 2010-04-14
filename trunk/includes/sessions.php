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
		$forums->func->check_cache('banfilter');
		if ($forums->cache['banfilter'])
		{
			foreach ((array) $forums->cache['banfilter'] as $banip)
			{
				$banip = str_replace('\*', '.*', preg_quote($banip, '/'));
				if (preg_match("/^$banip$/", IPADDRESS) || preg_match("/^$banip$/", ALT_IP))
				{
					$forums->func->standard_error('banip');
				}
			}
		}

		$this->user = array(
			'id' => 0,
			'name' => '',
			'usergroupid' => 2,
			'timezoneoffset' => $bboptions['timezoneoffset']
		);

		if (THIS_SCRIPT === 'cron')
		{
			$this->user = $forums->func->set_up_guest();
			$forums->perm_id_array = array();
			return $this->user;
		}

		$match = array();
		if (preg_match('/(' . $bboptions['spiderid'] . ')/i', $_SERVER['HTTP_USER_AGENT'], $match))
		{
			$this->user = $forums->func->set_up_guest();
			$forums->func->check_cache("usergroup_{$bboptions['spider_roup']}", 'usergroup');
			$this->user = array_merge($this->user, $forums->cache["usergroup_{$bboptions['spider_roup']}"]);
			$this->build_group_permissions();
			$forums->sessiontype = 'cookie';
			$bot_agent = trim($match[0]);

			$spideronline = $bboptions['spideronline'];
			if ($spideronline)
			{
				$sql_array = array(
					'usergroupid' => $bboptions['spider_roup'],
					'inforum' => intval($_INPUT['f']),
					'inthread' => intval($_INPUT['t']),
					'inblog' => intval($_INPUT['bid']),
					'lastactivity' => TIMENOW,
					'location' => substr(SCRIPTPATH, 0, 250),
					'host' => IPADDRESS,
					'useragent' => USER_AGENT,
				);

				if ($spideronline == 1)
				{
					$sql = 'SELECT lastactivity
						FROM ' . TABLE_PREFIX . 'session
						WHERE sessionhash = ' . $DB->validate($bot_agent);
					$online = $DB->query_first($sql);
					if (isset($online['lastactivity']))
					{
						$online = TIMENOW - $online['lastactivity'];
						if ($online > 300)
						{
							$DB->shutdown_update(TABLE_PREFIX . 'session', $sql_array, 'sessionhash = ' . $DB->validate($bot_agent));
						}
					}
					else
					{
						$spideronline = 2;
						$this->sessionid = $bot_agent;
						$cookietimeout = $bboptions['cookietimeout'] ? (TIMENOW - $bboptions['cookietimeout'] * 60) : (TIMENOW - 3600);
						$DB->shutdown_delete(TABLE_PREFIX . 'session', "lastactivity < $cookietimeout");
					}
				}
				else if ($spideronline == 2)
				{
					$this->sessionid = md5(uniqid(microtime()));
					$DB->shutdown_delete(TABLE_PREFIX . 'session', 'host = ' . $DB->validate(IPADDRESS) . ' AND useragent = ' . $DB->validate(USER_AGENT));
				}

				if ($spideronline == 2)
				{
					$sql_array = array(
						'sessionhash' => $this->sessionid,
						'username' => $bot_agent,
						'userid' => 0,
						'invisible' => 0,
						'badlocation' => 0,
						'mobile' => 0,
					);
					$sql = $DB->sql_insert(TABLE_PREFIX . 'session', $sql_array, 'INSERT', 'INSERT IGNORE');
					$DB->shutdown_query($sql);
				}
			}

			return $this->user;
		}

		$cookie = array(
			'sessionid' => $forums->func->get_cookie('sessionid'),
			'userid' => $forums->func->get_cookie('userid'),
			'password' => $forums->func->get_cookie('password')
		);
		$this->sessionid = 0;
		if ($cookie['sessionid'])
		{
			$this->sessionid = $cookie['sessionid'];
			$forums->sessiontype = 'cookie';
		}
		else if ($_INPUT['s'])
		{
			$this->sessionid = $_INPUT['s'];
			$forums->sessiontype = 'url';
		}
		$this->sessionid = preg_replace('/([^a-zA-Z0-9]+)/', '', $this->sessionid);

		if ($this->sessionid)
		{
			if ($cookie['userid'])
			{
				$sql = 'SELECT ue.*, u.*, ue.id AS expandid, s.sessionhash
					FROM ' . TABLE_PREFIX . 'session s
						LEFT JOIN ' . TABLE_PREFIX . 'user u
							ON s.userid = u.id
						LEFT JOIN ' . TABLE_PREFIX . 'userexpand ue
							ON u.id = ue.id';
			}
			else
			{
				$sql = 'SELECT s.sessionhash, s.userid AS id, s.lastactivity
					FROM ' . TABLE_PREFIX . 'session s';
			}

			$sql .= ' WHERE s.sessionhash = ' . $DB->validate($this->sessionid) . '
				AND s.host = ' . $DB->validate(IPADDRESS);

			if ($bboptions['matchbrowser'])
			{
				$sql .= ' AND s.useragent = ' . $DB->validate(USER_AGENT);
			}
			$row = $DB->query_first($sql);
			if (!$row['sessionhash'])
			{
				$this->badsessionid = $this->sessionid;
				$this->sessionid = 0;
				$this->userid = 0;
			}
			else
			{
				$this->userid = intval($row['id']);
				if ($this->userid)
				{
					if ($cookie['userid'])
					{
						unset($row['sessionhash']);
						$this->user = $row;
						$this->load_user(0);
					}
					else
					{
						$this->load_user($this->userid);
					}

					if (!$this->user['id'])
					{
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
			unset($row);
		}

		if ($this->sessionid == 0)
		{
			if ($cookie['userid'] != '' && $cookie['password'] != '')
			{
				$this->load_user($cookie['userid']);
				if (!$this->user['id'])
				{
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

		if (!$this->user['id'])
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
							//萧山的超版处理
							if ($r['forumid'] == 0)
							{
								$forums->func->check_cache('forum');
								foreach($forums->cache['forum'] AS $k => $v)
								{
									$r['forumid'] = $k;
									$this->user['_moderator'][$k] = $r;
								}
								if ($r['candobatch'])
								{
									$this->user['candobatch'] = 1;
								}
								else
								{
									$this->user['candobatch'] = 0;
								}
								$this->user['_sup_moderator'] = 1;
							}
							else
							{
								$this->user['_moderator'][$r['forumid']] = $r;
								$this->user['_sup_moderator'] = 0;
							}
							$this->user['is_mod'] = 1;
						}
					}
				}
			}
		}

		if ($this->user['id'])
		{
			foreach (array('lastactivity', 'lastvisit') as $v)
			{
				if (!isset($_INPUT[$v]) || !$_INPUT[$v])
				{
					if ($this->user[$v])
					{
						$_INPUT[$v] = $this->user[$v];
					}
					else
					{
						$_INPUT[$v] = TIMENOW;
					}
				}
			}
			$useronline = TIMENOW - $_INPUT['lastactivity'];
			if (!$this->user['lastvisit'])
			{
				$DB->shutdown_update(TABLE_PREFIX . 'user', array('lastvisit' => TIMENOW, 'lastactivity' => TIMENOW), "id = {$this->user['id']}");
			}
			else if ($useronline > 300)
			{
				$this->user['loggedin'] = 1;
				$this->user['options'] = $forums->func->convert_array_to_bits($this->user);

				$DB->shutdown_update(TABLE_PREFIX . 'user', array('options' => $this->user['options'], 'lastactivity' => TIMENOW),  "id={$this->user['id']}");

				//更新用户评价和积分默认值
				$lefttime = TIMENOW - $this->user['lastvisit'];
				$forums->func->check_cache('creditlist');
				foreach ($forums->cache['creditlist'] as $id => $credit)
				{
					if (!$credit['used']) continue;
					if (intval($credit['inittime']) > 0)
					{
						$recycletime = intval($credit['inittime'])*3600;
						if ($lefttime > $recycletime)
						{
							$DB->shutdown_update(TABLE_PREFIX . 'userexpand', array($credit['tag'] => intval($credit['initvalue'])), "id={$this->user['id']}");
						}
					}
				}
			}
			if ($this->user['liftban'] && THIS_SCRIPT != 'login')
			{
				$ban_arr = banned_detect($this->user['liftban']);
				if ($ban_arr['timespan'] == -1)
				{
					global $bbuserinfo;
					$bbuserinfo = $this->user;
					$forums->func->standard_error('banuserever', true);
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
					$DB->shutdown_update(TABLE_PREFIX . 'user', array('liftban' => '', 'usergroupid' => $ban_arr['groupid']), "id = {$this->user['id']}");
				}
				else
				{
					global $bbuserinfo;
					$bbuserinfo = $this->user;
					if (!$ban_arr['forumid'] && THIS_SCRIPT != 'login')
					{
						$forums->func->standard_error('banusertemp', true, $forums->func->get_date($ban_arr['date_end'], 2, 1));
					}
				}
			}
		}

		$forums->func->set_cookie('sessionid', $this->sessionid, 0);
		$this->check_pm();
		$this->user['userdotime'] = $forums->func->get_date($this->user['userdotime']);
		$this->user['userdo'] = $this->user['usercurdo'];
		if (!$this->user['usercurdo'])
		{
			$this->user['userdo'] = $forums->lang['notfilldowhat'];
		}
		return $this->user;
	}

	function build_group_permissions()
	{
		global $forums;
		$this->user['membergroupid'] = $this->user['usergroupid'];
		if (isset($this->user['membergroupids']) && $this->user['membergroupids'])
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
						foreach($forums->cache["usergroup_{$pid}"] as $k => $v)
						{
							if (!in_array($k, $exclude))
							{
								if (in_array($k, $less_is_more))
								{
									if ($v < $this->user[$k])
									{
										$this->user[$k] = $v;
									}
								}
								else
								{
									if ($v > $this->user[$k])
									{
										$this->user[$k] = $v;
									}
								}
							}
						}
					}
				}
			}
			$rmp = array();
			$tmp = explode(',', preg_replace(array('/,{2,}/', '/^,/', '/,$/'), array(',', '', ''), $this->user['membergroupid']));
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
		$forums->perm_id_array = explode(',', $this->user['membergroupid']);
	}

	function load_user($userid = 0)
	{
		global $DB, $forums;
		$userid = intval($userid);
		if ($userid != 0)
		{
			$sql = "SELECT ue.*, u.*, ue.id AS expandid
				FROM " . TABLE_PREFIX . "user u
					LEFT JOIN " . TABLE_PREFIX . "userexpand ue
						ON u.id = ue.id
				WHERE u.id = $userid";
			$this->user = $DB->query_first($sql);
		}

		if ($this->user['id'])
		{
			$this->user['options'] = intval($this->user['options']);
			$forums->func->convert_bits_to_array($this->user, $this->user['options']);
			$this->check_userexpand();
		}
		else
		{
			$this->unload_user();
		}
	}

	function check_userexpand()
	{
		global $DB;
		if (!$this->user['expandid'])
		{
			$DB->insert(TABLE_PREFIX . 'userexpand', array('id' => $this->user['id']));
		}
	}

	function unload_user()
	{
		global $forums;
		$forums->func->set_cookie('sessionid', 0, -1);
		$forums->func->set_cookie('userid', 0, -1);
		$forums->func->set_cookie('password', '', -1);
		$this->user['id'] = 0;
		$this->user['name'] = '';
	}

	function update_user_session()
	{
		global $DB, $_INPUT;
		if (!$this->sessionid)
		{
			return $this->create_session($this->user['id']);
		}
		else if (empty($this->user['id']))
		{
			$this->unload_user();
			$this->create_session();
			return;
		}

		$sql_array = array(
			'username' => $this->user['name'],
			'userid' => $this->user['id'],
			'avatar' => $this->user['avatar'],
			'usergroupid' => $this->user['usergroupid'],
			'inthread' => isset($_INPUT['t']) ? intval($_INPUT['t']) : 0,
			'inforum' => isset($_INPUT['f']) ? intval($_INPUT['f']) : 0,
			'invisible' => $this->user['invisible'],
			'lastactivity' => TIMENOW,
			'location' => substr(SCRIPTPATH, 0, 250),
			'badlocation' => 0,
			'mobile' => 0,
		);
		$DB->shutdown_update(TABLE_PREFIX . 'session', $sql_array, "sessionhash = '{$this->sessionid}'");
	}

	function update_guest_session()
	{
		global $DB, $_INPUT;
		if (!$this->sessionid)
		{
			$this->create_session();
			return;
		}
		$sql_array = array(
			'username' => '',
			'userid' => 0,
			'usergroupid' => 2,
			'invisible' => 0,
			'lastactivity' => TIMENOW,
			'location' => substr(SCRIPTPATH, 0, 250),
			'badlocation' => 0,
			'mobile' => 0,
			'inforum' => isset($_INPUT['f']) ? intval($_INPUT['f']) : 0,
			'inthread' => isset($_INPUT['t']) ? intval($_INPUT['t']) : 0,
		);

		$DB->shutdown_update(TABLE_PREFIX . 'session', $sql_array, 'sessionhash = ' . $DB->validate($this->sessionid));
	}

	function create_session($userid = 0)
	{
		global $DB, $_INPUT, $forums, $bboptions;
		$cookietimeout = $bboptions['cookietimeout'] ? (TIMENOW - $bboptions['cookietimeout'] * 60) : (TIMENOW - 3600);
		$expire = ($this->user['lastactivity'] < $cookietimeout);
		if ($userid)
		{
			$DB->delete(TABLE_PREFIX . 'session', "userid = '{$this->user['id']}'");
			if ($expire)
			{
				$forums->func->set_cookie('threadread', '');
				$this->user['loggedin'] = 1;
				$this->user['options'] = $forums->func->convert_array_to_bits($this->user);

				$DB->shutdown_update(TABLE_PREFIX . 'user', array(
					'options' => $this->user['options'],
					'lastvisit' => $this->user['lastactivity'],
					'lastactivity' => TIMENOW
				), "id='{$this->user['id']}'");
				$_INPUT['lastvisit'] = $this->user['lastactivity'];
				$_INPUT['lastactivity'] = TIMENOW;
				$DB->shutdown_delete(TABLE_PREFIX . 'session', "lastactivity < $cookietimeout");
			}
		}
		else
		{
			$extra = ($this->badsessionid) ? 'sessionhash = ' . $DB->validate($this->badsessionid) . ' OR ' : '';
			$DB->shutdown_delete(TABLE_PREFIX . 'session', $extra . '(host = ' . $DB->validate(IPADDRESS) . ' AND useragent = ' . $DB->validate(USER_AGENT) . ')');
			$this->user['name'] = '';
			$this->user['id'] = 0;
			$this->user['usergroupid'] = 2;
			$this->user['invisible'] = 0;

			if ($expire)
			{
				$DB->shutdown_delete(TABLE_PREFIX . 'session', "lastactivity < $cookietimeout");
			}
		}

		$this->sessionid = md5(uniqid(microtime()));
		$sql_array = array(
			'sessionhash' => $this->sessionid,
			'username' => $this->user['name'],
			'userid' => $this->user['id'],
			'avatar' => $this->user['avatar'],
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
			'mobile' => 0,
		);
		$sql = $DB->sql_insert(TABLE_PREFIX . 'session', $sql_array, 'INSERT', 'INSERT IGNORE');
		$DB->shutdown_query($sql);
	}

	function check_pm()
	{
		global $forums, $DB, $bboptions, $_INPUT;
		$this->user['pminfo'] = '';
		if ($this->user['pmwarn'])
		{
			$pmwarmnum = intval($this->user['pmquota'] * 0.8);
			if ($this->user['id'] && $pmwarmnum > 0 && $this->user['pmtotal'] >= $pmwarmnum)
			{
				if ($this->user['pmwarnmode'])
				{
					/*require_once(ROOT_PATH . "includes/functions_email.php");
					$email = new functions_email();
					$forums->lang['_pmfullcontent'] = sprintf($forums->lang['_pmfullcontent'], $bboptions['bbtitle']);
					$contents = $forums->lang['_pmfullcontent'];
					$email->char_set = $this->user['emailcharset']?$this->user['emailcharset']:'GBK';
					$email->build_message($contents);
					$email->subject = $forums->lang['_pmfull'];
					$email->to = $this->user['email'];
					$email->send_mail();*/
				}
				else
				{
					$this->user['showpm'] = true;
					$this->user['pminfo'] = $forums->lang['_pmfull'];
				}
			}
		}
		$this->user['newpm'] = array();
		if (isset($this->user['pmunread']) && $this->user['pmunread'] > 0)
		{
			$forums->lang['_pmunread'] = sprintf($forums->lang['_pmunread'], $this->user['pmunread']);
			$this->user['pminfo'] = $forums->lang['_pmunread'];
			if ($this->user['pmpop'] && THIS_SCRIPT != 'private')
			{
				$pmlimit = min(5, $this->user['pmunread']);
				$this->user['showpm'] = true;
				$DB->query("SELECT p.*, u.name, u.id
					FROM " . TABLE_PREFIX . "pm p
						LEFT JOIN " . TABLE_PREFIX . "user u
							ON (u.id = p.fromuserid)
					WHERE (userid = {$this->user['id']} AND p.folderid = 0)
						OR p.usergroupid != 0
					ORDER BY p.dateline DESC
					LIMIT 0, $pmlimit");
				while ($pm = $DB->fetch_array())
				{
					$pm['dateline'] = $forums->func->get_date($pm['dateline'], 1);
					if ($pm['usergroupid'] != '-1' && !empty($pm['usergroupid']))
					{
						if (strpos(",{$pm['usergroupid']},", ",{$this->user['usergroupid']}," !== false))
						{
							$pm['name'] = $forums->lang['_systeminfo'];
							$this->user['newpm'][] = $pm;
						}
					}
					else
					{
						$this->user['newpm'][] = $pm;
					}
				}
			}
		}
	}
}
?>