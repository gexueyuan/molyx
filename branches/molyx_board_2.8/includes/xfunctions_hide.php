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
# $Id: xfunctions_hide.php 252 2007-09-30 06:10:19Z develop_tong $
# **************************************************************************#
class hidefunc
{
	function already_bought($buyers = array())
	{
		global $bbuserinfo;
		if (!$buyers OR !count($buyers))
		{
			return false;
		}
		else if (in_array($bbuserinfo['name'], $buyers))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function canview_hide($hideinfo = array(), $posterid = 0, $forumid = '')
	{
		global $bbuserinfo;
		if (!count($hideinfo) OR !$bbuserinfo['id'])
		{
			return false;
		}
		else if (!$posterid OR $bbuserinfo['id'] == $posterid OR $bbuserinfo['supermod'] OR is_array($bbuserinfo['_moderator'][ $forumid ]))
		{
			return true;
		}
		else if (($hideinfo['type'] == 1 OR $hideinfo['type'] == 2 OR $hideinfo['type'] == 111) AND $this->already_bought($hideinfo['buyers']))
		{
			return true;
		}
		else if ($hideinfo['type'] == 3 AND $bbuserinfo['reputation'] >= $hideinfo['cond'])
		{
			return true;
		}
		else if ($hideinfo['type'] == 4 AND $bbuserinfo['posts'] >= $hideinfo['cond'])
		{
			return true;
		}
		else if ($hideinfo['type'] == 5 AND $bbuserinfo['name'] == $hideinfo['cond'])
		{
			return true;
		}
		else if ($hideinfo['type'] == 11 AND ($bbuserinfo['usergroupid'] == $hideinfo['cond'] OR $bbuserinfo['membergroupids'] == $hideinfo['cond']))
		{
			return true;
		}
		else if ($hideinfo['type'] == 999 && $bbuserinfo[$hideinfo['credit_type']] >= $hideinfo['cond'])
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function canview_hideattach($hideinfo = array(), $posterid = 0, $forumid = '')
	{
		global $bbuserinfo;
		if (!$hideinfo OR !count($hideinfo))
		{
			return true;
		}
		else if (!$bbuserinfo['id'])
		{
			return false;
		}
		else if (!$posterid OR $bbuserinfo['id'] == $posterid OR $bbuserinfo['supermod'] OR is_array($bbuserinfo['_moderator'][ $forumid ]))
		{
			return true;
		}
		else if (($hideinfo['type'] == 2 OR $hideinfo['type'] == 1) AND $this->already_bought($hideinfo['buyers']))
		{
			return true;
		}
		else if (!preg_match("#\[hide\](.*)\[/hide\]#siU", $row['pagetext']) AND $this->canview_hidecontent AND $hideinfo['type'] != 2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function parse_hide_code($row = array(), $forumid = '')
	{
		global $bbuserinfo, $forums, $DB;
		if (!$row OR !count($row))
		{
			return '';
		}
		if (!$row['hidepost'])
		{
			return $row;
		}
		$hideinfo = unserialize($row['hidepost']);
		$condition = $hideinfo['cond'];
		$forums->func->check_cache('usergroup');
		$forums->func->check_cache('creditlist');
		$hidecredit = array();
		if ($forums->cache['creditlist']) 
		{
			foreach ($forums->cache['creditlist'] as $k => $v) 
			{
				$hidecredit[$v['tag']] = $v['name'];
			}
		}
		$hidestatus = '';
		if ($this->canview_hide($hideinfo, $row['userid'], $forumid))
		{
			switch ($hideinfo['type'])
			{
				case 4:
					$requirepost = sprintf($forums->lang['requirepost'], $condition);
					$hidestatus = ' ' . $requirepost;
					break;
				case 5:
					$requireuser = sprintf($forums->lang['requireuser'], $condition);
					$hidestatus = ' ' . $requireuser;
					break;
				case 11:
					$requiregroup = sprintf($forums->lang['requiregroup'], $forums->lang[ $forums->cache['usergroup'][$condition]['grouptitle'] ]);
					$hidestatus = ' ' . $requiregroup;
					break;
				case 999:
					if (!$hidecredit[$hideinfo['credit_type']])
					{
						$skip_hide = true;
						$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "post SET hidepost = '' WHERE pid='" . $row['pid'] . "'");
					}
					$hidestatus = sprintf($forums->lang['requirecredit'] , $condition , $hidecredit[$hideinfo['credit_type']]);
					break;
				default:
					$hidestatus = ' ' . $forums->lang['requirereply'];
					break;
			}
			$hidetop = $forums->lang['hiddencontent'] . ': ' . $hidestatus;
			$repcontent = '\\1';
			$this->canview_hidecontent = true;
		}
		else
		{
			switch ($hideinfo['type'])
			{
				case 4:
					$requirepost = sprintf($forums->lang['requirepost'], $condition);
					$hidestatus = $requirepost;
					break;
				case 5:
					$requireuser = sprintf($forums->lang['requireuser'], $condition);
					$hidestatus = $requireuser;
					break;
				case 11:
					$requiregroup = sprintf($forums->lang['requiregroup'], $forums->lang[ $forums->cache['usergroup'][$condition]['grouptitle'] ]);
					$hidestatus = $requiregroup;
					break;
				case 999:
					if (!$hidecredit[$hideinfo['credit_type']])
					{
						$skip_hide = true;
						$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "post SET hidepost = '' WHERE pid='" . $row['pid'] . "'");
					}
					$hidestatus = sprintf($forums->lang['requirecredit'] , $condition , $hidecredit[$hideinfo['credit_type']]);
					break;
				default:
					$hidestatus = ' ' . $forums->lang['requirereply'];
					break;
			}
			$hidetop = $forums->lang['hidecontent'] . ': ';
			$repcontent = $hidestatus;
			$this->canview_hidecontent = false;
		}
		$hidebegin = '<div class="hidetop">' . $hidetop . '</div><div class="hidemain">';
		$hideend = '</div>';
		$replacement = $hidebegin . $repcontent . $hideend;
		if ($this->canview_hideattach($hideinfo, $row['userid'], $forumid) == true)
		{
			$row['canview_hideattach'] = 1;
		}
		else
		{
			$row['canview_hideattach'] = 0;
		}
		if (!preg_match("#\[hide\](.*)\[/hide\]#siU", $row['pagetext']))
		{
			if ($hideinfo['type'] != 2)
			{
				$row['pagetext'] = "[hide]" . $row['pagetext'] . "[/hide]";
			}
			$row['attachextra'] = 1;
		}
		if ($hideinfo['type'] != 2)
		{
			if ($skip_hide)
			{
				$row['pagetext'] = preg_replace('#\[hide\](.*)\[/hide\]#siU', "\\1", $row['pagetext']);
			}
			else
			{
				$row['pagetext'] = preg_replace('#\[hide\](.*)\[/hide\]#siU', $replacement, $row['pagetext']);
			}
		}
		if ($hideinfo['type'] == 2)
		{
			$row['hidecond'] = $hideinfo['cond'];
			$row['buyernum'] = count($hideinfo['buyers']);
			$row['onlyattach'] = 1;
		}
		
		return $row;
	}

	function check_hide_condition($tid='')
	{
		global $_INPUT, $forums, $bbuserinfo;

		if (!$_INPUT['hidetype'])
		{
			return '';
		}
		switch ($_INPUT['hidetype'])
		{
			case 1 :
			case 2 :
			case 3 :
			case 4 :
				$_INPUT['hidecond'] = intval($_INPUT['hidecond']);
				if ($_INPUT['hidecond'] < 1)
				{
					$errmsg = $forums->lang['notzero'];
				}
				$forums->func->check_cache('banksettings');
				if (($_INPUT['hidetype'] == 1 OR $_INPUT['hidetype'] == 2) AND $_INPUT['hidecond'] * 2 > $bbuserinfo['cash'])
				{
					$errmsg = $forums->lang['exceedlimit'];
				}
				$cond = $_INPUT['hidecond'];
				break;
			case 5 :
				if (!$_INPUT['hidecond'])
				{
					$errmsg = $forums->lang['requirereply'];
				}
				$cond = $_INPUT['hidecond'];
				break;
			case 11 :
				$forums->func->check_cache('usergroup');
				$usergrp = $forums->cache['usergroup'];
				if (!count($usergrp[$_INPUT['hidegrpid']]))
				{
					$errmsg = $forums->lang['mustusergroup'];
				}
				$cond = $_INPUT['hidegrpid'];
				break;
			case 111:
				$requirereply = sprintf($forums->lang['requirereply'], $condition);
				$hidestatus .= ' ' . $requirereply;
				break;
			case 999:
				$_INPUT['hidecreditcond'] = intval($_INPUT['hidecreditcond']);
				if ($_INPUT['hidecreditcond'] < 1)
				{
					$errmsg = $forums->lang['notzero'];
				}
				$forums->func->check_cache('creditlist');
				$hidecredit = array();
				if ($forums->cache['creditlist']) 
				{
					foreach ($forums->cache['creditlist'] as $k => $v) 
					{
						$hidecredit[$v['tag']] = $v['name'];
					}
				}
				if (!$hidecredit[$v['tag']])
				{
					$errmsg = $forums->lang['credittypewrong'];
				}
				$cond = $_INPUT['hidecreditcond'];
				$hideinfo['credit_type'] = $_INPUT['hidecredit'];
				break;
			default :
				$errmsg = $forums->lang['conditionerror'];
				$cond = 0;
				break;
		}
		/*if ( ($_INPUT['hidetype'] == 1 OR $_INPUT['hidetype'] == 2) AND $bbuserinfo['mkaccount'] < 1 ) {
			$errmsg = $forums->lang['nousefunction'];
		}*/

		if (!$errmsg)
		{
			$hideinfo['type'] = $_INPUT['hidetype'];
			$hideinfo['cond'] = $cond;
			$hideinfo['attach'] = ($_INPUT['hidetype'] != 2) ? 0 : 1;
			$hideinfo['buyers'] = array();
		
			return $hideinfo;
		}
		else
		{
			return $errmsg;
		}
	}

	function generate_hidetype_list($newthread = 0)
	{
		global $forums;
		$credit_expand = "";
		$hide_list[] = array("val" => 0, "des" => "=====" . $forums->lang['selectcondition'] . "=====");
		//$hide_list[] = array("val" => 1, "des" => $forums->lang['viewpostmoney']);
		//$hide_list[] = array("val" => 2, "des" => $forums->lang['viewattachmoney']);
		//$hide_list[] = array("val" => 3, "des" => $forums->lang['viewpostreputation']);
		$hide_list[] = array("val" => 4, "des" => $forums->lang['viewpostposts']);
		$hide_list[] = array("val" => 5, "des" => $forums->lang['onlyuser']);
		$hide_list[] = array("val" => 11, "des" => $forums->lang['onlyusergroup']);
		$hide_list[] = array("val" => 111, "des" => $forums->lang['viewrequirereply']);

		$forums->func->check_cache('creditlist');
		if (count($forums->cache['creditlist']))
		{
			$hide_list[] = array("val" => 999, "des" => $forums->lang['viewrequirecredit']);
		}

		return $hide_list;
	}
	
	function hide_attachment($uid,$hidetype,$threadid,$postid='',$forumid='',$returntype = 1)
	{
		global $forums,$DB,$bbuserinfo,$bboptions;
		
		static $sforumid ,$i = 0;
		static $extracreditc =array();
		static $sendposttimesc = array();
		static $ingroupc = array();
		static $replyedc = array();
		static $extracreditb = array();
		static $sendposttimesb =  array();
		static $ingroupb = array();
		static $replyedb = array();

		if (empty($hidetype)||!intval($threadid)||!$bboptions['hideattach'])
		{
			return true; 
		}

		if(!$bbuserinfo['id'])
		{
			return false;
		}
		if(!$sforumid)
		{
			if(!$forumid)
			{
				$forumid = $DB->query_first("SELECT f.id as forumid FROM ". TABLE_PREFIX . "post p
					LEFT JOIN " . TABLE_PREFIX . "thread t ON p.threadid =t.tid
					LEFT JOIN " . TABLE_PREFIX . "forum f ON t.forumid = f.id
					WHERE p.pid = '".$postid."'");
				$sforumid = $forumid['forumid'];
			}
			else
			{
				$sforumid = $forumid;
			}
	    }
		//匿名发帖|非隐藏附件|发帖人自己|超级管理员|该版块版主
		if(!$uid  || !$hidetype || $bbuserinfo['id'] == $uid || $bbuserinfo['supermod'] || is_array($bbuserinfo['_moderator'][ $sforumid]))
		{
			return true;
		}
	
		$extracredits = $sendposttimes = $ingroups = $replyeds = true;


		$hidetype = unclean_value($hidetype);
	    $conditions = explode(']::[',$hidetype);
		
		//扩展积分
		if (!empty($conditions[0]))
		{   
			if(!in_array($conditions[0],$extracreditc))
			{
				$extracredit = $DB->query_first("SELECT id FROM ".TABLE_PREFIX."userexpand 
				WHERE id='".$bbuserinfo['id']."' AND ".$conditions[0]."");
			
				$extracredits = $extracredit['id']?true:false;	
				$extracreditc[$i] = $conditions[0];
				$extracreditb[$i] = $extracredits;

				$i++;
			}
			else
			{	
				$key =  array_search($conditions[0],$extracreditc);
				$extracredits = $extracreditb[$key];				
			}
		}

        //发帖数
		if (!empty($conditions[1]))
		{	
			if(!in_array($conditions[1],$extracreditc))
			{
				$sendposttime = preg_replace('/^(posts)(<|>)([\d]+)$/i','\$postcount[\'postcount\']\\2\\3',$conditions[1]);
				$postcount = $DB->query_first("SELECT count(*) AS postcount FROM ".TABLE_PREFIX."post 
				WHERE userid = '".$bbuserinfo['id']."'");

				eval("\$sendposttimes = $sendposttime?true:false;");
				$extracreditc[$i] = $conditions[1];
				$extracreditb[$i] = $sendposttimes;
				$i++;
			}
			else
			{	
				$key =  array_search($conditions[1],$extracreditc);
				$sendposttimes = $extracreditb[$key];
			}
		} 

		//用户组
		if (!empty($conditions[2]))
		{
			if(!in_array($conditions[2],$extracreditc))
			{
				$groupids = preg_replace('/-:::-/',',',$conditions[2]);			
				$usergroupid = $DB->query_first("SELECT id FROM ".TABLE_PREFIX."user 
				WHERE usergroupid  IN(".$groupids.") AND id = '".$bbuserinfo['id']."'");

				$ingroups =  $usergroupid['id']?true:false;		
				$extracreditc[$i] = $conditions[2];
				$extracreditb[$i] = $ingroups;
				$i++;
			}
			else
			{	
				$key =  array_search($conditions[2],$extracreditc);
				$ingroups = $extracreditb[$key];
			}
		}

        //回复
		if (!empty($conditions[3]))
		{			
			if(!in_array($conditions[3],$extracreditc))
			{
				$reply = $DB->query_first("SELECT count(pid) AS replycount FROM ".TABLE_PREFIX."post 
				WHERE threadid = '".$threadid."' AND  newthread = '0' AND  userid ='".$bbuserinfo['id']."'");
	
				$replyeds = $reply['replycount']?true:false;
				$extracreditc[$i] = $conditions[3];
				$extracreditb[$i] = $replyeds;
				$i++;
			}
			else
			{
				$key =  array_search($conditions[3],$extracreditc);
				$replyeds = $extracreditb[$key];
			}
		}
	
		return $extracredits && $sendposttimes && $ingroups&& $replyeds;
	}

	function hideattachcondition($conditions = array(),$hidetype = '',$tid = '',$returntype = 1)
	{
		global $forums,$bboptions;
		if ((!is_array($conditions)||!count($conditions))&&!intval($tid)&&empty($hidetype))
		{
			return null;
			exit;
		}
		if(!count($conditions))
		{
		   if(!empty($hidetype))
			{
				$conditions = explode(']::[',$hidetype);
			}
			else if($tid)
			{
				$hidetype = $DB->query_first("SELECT hidetype FROM ".TABLE_PREFIX."attachment WHERE tid = '".intval($tid)."'");
				if(!empty($hidetype['hidetype']))
				{
					$conditions = explode(']::[',$hidetype['hidetype']);
				}
				else
				{
					return null;
					exit;
				}
			}
		}

		
		$forums->func->check_cache('creditlist');
		$hidecredit = array();
		if ($forums->cache['creditlist']) 
		{
			foreach ($forums->cache['creditlist'] as $k => $v) 
			{
				$hidecredit[$v['tag']] = $v['name'];
			}
		}
		$forums->func->check_cache('usergroup');
		$usergrp = $forums->cache['usergroup'];
		$extracredit = $sendposttimes = $usergroupids = $usergroupslang = $needreply = '';

		if($conditions[0])
		{
			$extracredit = preg_replace("/([a-zA-Z]+)(&lt;|&gt;)([\d]+)/e","\$hidecredit['\\1'].'\\2\\3'",$conditions[0]);
        }
		
         
		if($conditions[1])
		{
			$sendposttimes = 
			preg_replace("/(posts)(&lt;|&gt;)([\d]+)/e", "\$forums->lang['_hideattachaboutposts'].'\\2\\3'",$conditions[1]);
		}
		

		$usergroupids = explode('-:::-',$conditions[2]);
		
		//用户组
		if (count($usergroupids))
		{
			$usergroupslang = '';
			
			foreach($usergrp AS $k => $v)
			{
				if (in_array($v['usergroupid'],$usergroupids))
				{
					$usergroupslang.= $forums->lang[$v['grouptitle']].' | ';
				}
			}		
			$usergroupslang =substr_replace($usergroupslang,'',-3);
			
		}
		
		if ($conditions[3])
		{
			$needreply = $forums->lang['_needreply'];
		}
		if (empty($extracredit)&&empty($sendposttimes)&&empty($usergroupslang)&&empty($needreply))
		{
			return null;
			exit;
		}

		
		if ($returntype == 1)
		{
			$extracredit = empty($extracredit)?'/':$extracredit;
		    $usergroupslang = empty($usergroupslang)?'/':$usergroupslang;
		    $sendposttimes = empty($sendposttimes)?(empty($needreply)?'/':$needreply):(empty($needreply)?$sendposttimes:$sendposttimes.'&'.$needreply);

			$returnvalue = <<<str
			  <tr  class='row2'>
			   <td>
			   {$extracredit}
		       </td>
			   <td>
			   {$sendposttimes}
			   </td>
			   <td>
			   {$usergroupslang}
			   </td>
			  </tr>

str;
		}
		else
		{
			if($extracredit)
				$returnvalue['extraintegral'] = $extracredit;
			if($sendposttimes)
				$returnvalue['sendposttimes'] = $sendposttimes;
			if($needreply)
				$returnvalue['replyed'] = $needreply;
			if($usergroupslang)
				$returnvalue['usergroupslang'] = $usergroupslang;
		}
		return $returnvalue;
	}

	

}

?>