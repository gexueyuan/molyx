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
class functions_credit
{	
	/**
	 * 显示操作影响的积分
	 *
	 * @param string $event 事件标示
	 * @param intager $gid 用户组ID
	 * @param intager $fid 版面ID
	 */
	function show_credit($event='', $gid=0, $fid=0)
	{
		global $forums, $DB, $bbuserinfo;
		$showlist = array();
		$lists = $this->getactioncredit($event, $gid, $fid);
		foreach ($lists as $creditid => $v)
		{
			$action = intval($v['action'])>0?('+'.$v['action']):$v['action'];
			$showlist[] = array($v['name'], $action . $v['unit']);
		}
	
		return $showlist;
	}
	
	/**
	 * 取得事件在所有积分中的信息
	 *
	 * @param string $event 事件标示
	 * @param intager $gid 用户组ID
	 * @param intager $fid 版面ID
	 */
	function getactioncredit($event='', $gid=0, $fid=0)
	{
		global $forums, $DB, $bbuserinfo;
		if (!$event || !$gid) return false;
		$forums->func->check_cache('creditlist');
		if (!is_array($forums->cache['creditlist']) && !$forums->cache['creditlist'])
		{
			return false;
		}
		$action = array();
		foreach ($forums->cache['creditlist'] as $creditid => $v)
		{
			//未启用该积分
			if (!$v['used']) continue;
			
			//取得该积分事件用户组修正值,版面修正值和默认值
			$default = $groupalter = $forumalter = 0;
			$forums->func->check_cache('creditrulegroup_'.$gid);
			$groupalter = $forums->cache['creditrulegroup_'.$gid][$creditid]['alter'][$event];
			$default = $forums->cache['creditrulegroup_'.$gid][$creditid]['default'][$event];
			if ($fid)
			{
				$forums->func->check_cache('creditruleforum_'.$fid);
				$forumalter = $forums->cache['creditruleforum_'.$fid][$creditid]['alter'][$event];
			}
			
			//计算操作影响积分
			$action[$creditid] = $v;
			$action[$creditid]['action'] = $this->calculate_credit($default, $groupalter, $forumalter);
		}
		
		return $action;
	}
	
	/**
	 * 计算积分
	 *
	 * @param intager $default 默认分值
	 * @param intager $groupalter 用户组修正分值
	 * @param intager $forumalter 版面修正分值
	 */
	function calculate_credit($default=0, $groupalter=0, $forumalter=0)
	{
		global $forums, $bbuserinfo, $DB;
		$score = 0; 
		if (!$default) return $score;
		if ($forumalter)
		{
			if (substr($forumalter, -1, 1) != '%')
			{
				if ($groupalter)
				{
					if (substr($groupalter, -1, 1) != '%')
					{
						$score = intval($forumalter)+intval($groupalter);
					}
					else 
					{
						$score = intval($forumalter)*intval($groupalter)/100;
					}
				}
				else 
				{
					$score = intval($forumalter);
				}
			}
			else 
			{
				if ($groupalter)
				{
					if (substr($groupalter, -1, 1) != '%')
					{
						$score = intval($default)*intval($forumalter)/100+intval($groupalter);
					}
					else 
					{
						$score = intval($default)*intval($forumalter)*intval($groupalter)/10000;
					}
				}
				else 
				{
					$score = intval($default)*intval($forumalter)/100;
				}
			}
		}
		else 
		{
			if ($groupalter)
			{
				if (substr($groupalter, -1, 1) != '%')
				{
					$score = intval($groupalter);
				}
				else 
				{
					$score = intval($default)*intval($groupalter)/100;
				}
			}
			else 
			{
				$score = intval($default);
			}
		}
		
		return $score;
	}
	
	/**
	 * 检查积分是否低于积分下限
	 *
	 * @param string $event 事件标示
	 * @param intager $gid 用户组ID
	 * @param intager $fid 版面ID
	 */
	function check_credit($event = '', $gid = '', $fid = '', $count = 1, $display_error = true)
	{
		global $forums, $DB, $bbuserinfo;
		
		$lists = $this->getactioncredit($event, $gid, $fid);
		foreach ($lists as $creditid => $v)
		{
			$orgicredit = intval($bbuserinfo[$v['tag']]);
			$leftcredit = intval($bbuserinfo[$v['tag']]) + (intval($v['action'])*intval($count));
			if ($leftcredit < $v['downlimit'])
			{
				//若用户某种积分低于相应下限，将重置为下限
				if ($orgicredit < $v['downlimit'])
				{
					$DB->update(TABLE_PREFIX . 'userexpand', array($v['tag'] => intval($v['downlimit'])), 'id = ' . $bbuserinfo['id']);
				}
				if ($leftcredit < $orgicredit)
				{
					if ($display_error) 
					{
						$forums->func->standard_error('credit_limit_over', 0, $v['name']);
					}
					else 
					{
						return $v['name'];
					}
					
				}
			}
		}
	}

	/**
	 * 更新用户积分
	 *
	 * @param string $event 事件标示
	 * @param intager $gid 用户组ID
	 * @param intager $fid 版面ID
	 */
	function update_credit($event='', $uids='', $gids='', $fids='', $count=1)
	{
		global $forums, $bbuserinfo, $DB;
		
		if (!is_array($uids) && $uids && is_array($gids) && !empty($gids))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (!$uids && !$gids)
		{
			$uids = $bbuserinfo['id'];
			$gids = $bbuserinfo['usergroupid'];
		}
		if (is_array($fids) && !empty($fids))
		{
			if (is_array($gids) && !empty($gids))
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($fids as $k => $fid)
					{
						$this->process_credit($event, $uids[$k], $gids[$k], $fid, $count);
					}
				}
			}
			else 
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($fids as $k => $fid)
					{
						$this->process_credit($event, $uids[$k], $gids, $fid, $count);
					}
				}
				else 
				{
					foreach ($fids as $k => $fid)
					{
						$this->process_credit($event, $uids, $gids, $fid, $count);
					}
				}
			}
		}
		elseif (!is_array($fids) && $fids)
		{
			if (is_array($gids) && !empty($gids))
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($gids as $k => $gid)
					{
						$this->process_credit($event, $uids[$k], $gid, $fids, $count);
					}
				}
			}
			else 
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($uids as $k => $uid)
					{
						$this->process_credit($event, $uid, $gids, $fids, $count);
					}
				}
				else 
				{
					$this->process_credit($event, $uids, $gids, $fids, $count);
				}
			}
		}
		else 
		{
			if (is_array($gids) && !empty($gids))
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($gids as $k => $gid)
					{
						$this->process_credit($event, $uids[$k], $gids, '', $count);
					}
				}
			}
			else 
			{
				if (is_array($uids) && !empty($uids))
				{
					foreach ($uids as $k => $uid)
					{
						$this->process_credit($event, $uid, $gids, '', $count);
					}
				}
				else 
				{
					$this->process_credit($event, $uids, $gids, '', $count);
				}
			}
		}
	}
	
	function process_credit($event='', $uid=0, $gid=0, $fid=0, $count=1)
	{
		global $forums, $DB, $bbuserinfo;
		if (!$event || !$uid || !$gid) return false;
		$lists = $this->getactioncredit($event, $gid, $fid);
		
		foreach ($lists as $creditid => $v)
		{
			$update_expand = "{$v['tag']} = {$v['tag']} + (" . floatval($v['action']) * intval($count) . ')';
			$DB->query("UPDATE " . TABLE_PREFIX . "userexpand SET "  . $update_expand . " WHERE id=" . $uid);
		}
	}
}
?>