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
# $Id: xfunctions_bank.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class bankfunc
{
	var $dorw = '';
	var $desc = '';
	var $trdesc = '';

	function get_userinfo($userid = 0)
	{
		global $DB;
		if (!$userid)
			return;
		if (is_array($userid) AND count($userid))
		{
			$userinfo = $userid;
			if ($userinfo['id'] < 1)
				return;
		}
		else
		{
			$userinfo = $DB->query_first("SELECT u.id, u.bank, u.cash, u.mkaccount
						      FROM " . TABLE_PREFIX . "user u
						      WHERE u.id = " . intval($userid));
		}
		if (!$userinfo OR !count($userinfo) OR !$userinfo['id'])
			return;
		return $userinfo;
	}

	function get_propertysum()
	{
		global $DB, $bbuserinfo;
		
		$DB->query("SELECT tag_name, name, type FROM " . TABLE_PREFIX . "credit WHERE used = 1");
		while ($row = $DB->fetch_array())
		{
			$creditlist[$row['type']][$row['tag_name']] = $row['name'];
		}
		foreach ($creditlist[1] as $k => $v)
		{
			$this->parameters = array_merge($this->parameters, array($k => array($v, intval($bbuserinfo[$k]))));
		}
	}
	
	function patch_bankinfo($user = array())
	{
		global $DB, $bbuserinfo;
		$userinfo = $user ? $user : $bbuserinfo;
		if (!$userinfo['id'])
		{
			return $userinfo;
		}
		$infoarray = $DB->query_first("SELECT id, loanamount, loanreturn, loaninterest FROM " . TABLE_PREFIX . "userextra WHERE id = " . $userinfo['id']);
		if (!$infoarray['id'])
		{
			return $userinfo;
		}
		$userinfo = array_merge($userinfo, $infoarray);
		return $userinfo;
	}

	function bank_savepick_money($costamount = 0, $saveamount = 0, $tag = '', $type = '')
	{
		global $DB, $bbuserinfo, $forums;
		$forums->func->check_cache('banksettings');
		$banksettings = $forums->cache['banksettings'];
		$forums->func->check_cache('credit_'.$tag, 'credit');
		$actcredit = $forums->cache['credit_'.$tag];
		$forums->func->check_cache('credit_'.$banksettings['bankcredit'], 'credit');
		$defcredit = $forums->cache['credit_'.$banksettings['bankcredit']];
		$saveamount = intval($saveamount);
		if (!$bbuserinfo['id'] OR $costamount < 1 OR $saveamount < 1 OR $tag == '')
		{
			return 0;
		}
		if ($this->dorw != "s" AND $this->dorw != "p")
		{
			return 0;
		}
		if ($bbuserinfo['mkaccount'] < 1)
		{
			return 0;
		}
		if ($this->dorw == "s")
		{
			$typeid = 2;
			$creditleftsql = "$tag - $costamount";
			$bankleftsql = "bank + $saveamount";
			$bank_log = sprintf($forums->lang['deposit_log'], $saveamount . $actcredit['unit'] . $actcredit['name']);
		}
		else
		{
			$creditleftsql = "$tag + $saveamount";
			$typeid = 1;
			if ($type != 'clean')
			{
				$bankleftsql = "bank - $costamount";
				$bank_log = sprintf($forums->lang['payout_log'], $costamount . $defcredit['unit'] , $actcredit['name']);
			}
			else
			{
				$bankleftsql = "0";
				$bank_log = $forums->lang['doclean_log'];
			}
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET bank=" . $bankleftsql . " WHERE id='" . $bbuserinfo['id'] . "'");
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $tag=" . $creditleftsql . " WHERE id='" . $bbuserinfo['id'] . "'");
		$banklog = array (
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $bbuserinfo['id'],
				'action' => addslashes($bank_log),
				'dateline' => TIMENOW,
				'type' => $typeid,
		);
		$DB->insert(TABLE_PREFIX . 'banklog', $banklog);
			
		return 1;
	}
	
	function bank_exchange_money($costamount = array(), $saveamount = 0, $tag = '')
	{
		global $DB, $bbuserinfo, $forums;
		$saveamount = intval($saveamount);
		if (!$bbuserinfo['id'] || !is_array($costamount) || empty($costamount) || $saveamount < 1 || !$tag)
		{
			return 0;
		}
		if ($bbuserinfo['mkaccount'] < 1)
		{
			return 0;
		}
		$forums->func->check_cache('credit_'.$tag, 'credit');
		$savecredit = $forums->cache['credit_'.$tag];
		$savelog = $costlog = "";
		$costsql = array();
		$costsql[] = "$tag = $tag + $saveamount";
		$savelog = " $saveamount{$savecredit['unit']}{$savecredit['name']} ";
		foreach ($costamount as $key => $val)
		{
			if ($val < 1) return 0;
			$forums->func->check_cache('credit_'.$key, 'credit');
			$costcredit = $forums->cache['credit_'.$key];
			$costsql[] = "$key = $key - $val";
			$costlog .= round($val)."{$costcredit['unit']}{$costcredit['name']} ";
		}
		$bank_log = sprintf($forums->lang['exchange_log'], $costlog, $savelog);
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET " . implode(',', $costsql) . " WHERE id='" . $bbuserinfo['id'] . "'");
		$banklog = array (
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $bbuserinfo['id'],
				'action' => addslashes($bank_log),
				'dateline' => TIMENOW,
				'type' => 3,
		);
		$DB->insert(TABLE_PREFIX . 'banklog', $banklog);
		
		return 1;
	}
	
	function bank_transfer_money($tar = array(), $transferin = 0, $transferout = 0, $tag = '')
	{
		global $DB, $bbuserinfo;
		$transferin = intval($transferin);
		$transferout = intval($transferout);
		if (!$bbuserinfo['id'] OR !$tar['id'] OR $transferin < 1 OR $transferout < 1 OR $tag == '')
		{
			return 0;
		}
		if ($tag == 'bank')
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET bank = bank - " . $transferout . " WHERE id = " . $bbuserinfo['id'] . "");
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET bank = bank + " . $transferin . " WHERE id = " . $tar['id'] . "");
		}
		else 
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $tag = $tag - " . $transferout . " WHERE id = " . $bbuserinfo['id'] . "");
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $tag = $tag + " . $transferin . " WHERE id = " . $tar['id'] . "");
		}
		if ($this->trdesc AND strlen($this->trdesc) > 0)
		{
			$banklog = array (
				'fromuserid' => $bbuserinfo['id'],
				'touserid' => $tar['id'],
				'action' => addslashes($this->trdesc),
				'dateline' => TIMENOW,
				'type' => 4,
			);
			$DB->insert(TABLE_PREFIX . 'banklog', $banklog);
		}
		
		return 1;
	}
	
	function gettransfervalue($num = 0, $intag='', $outtag='', $type='')
	{
		global $forums;
		$return = array();
		if ($num == 0 || $intag == '' || $outtag == '' || $type == '') return $return;
		$forums->func->check_cache('banksettings');
		$banksettings = $forums->cache['banksettings'];
		$forums->func->check_cache('credit_'.$intag, 'credit');
		$increditinfo = $forums->cache['credit_'.$intag];
		$forums->func->check_cache('credit_'.$outtag, 'credit');
		$outcreditinfo = $forums->cache['credit_'.$outtag];
		if (!$increditinfo['ratio'] || !$outcreditinfo['ratio']) return $return;
		if ($type == 'clean' && $banksettings['bankpurgeexcost'])
		{
			$expenses = $this->getexpenses($num, $banksettings['bankpickexcost'], $banksettings['bankpickexcostlimit']);
			$num = $num - $expenses;
		}
		$return[0] = floor($num * $outcreditinfo['ratio'] / $increditinfo['ratio']);
		$mod = round($num- ($return[0] * $increditinfo['ratio'] / $outcreditinfo['ratio']), 2);
		$return[1] = $num - $mod;
		if (($type == 'pick' || $type == 'exchange') && !empty($return))
		{
			if ($type == 'pick')
			{
				$expenses = $this->getexpenses($return[1], $banksettings['bankpickexcost'], $banksettings['bankpickexcostlimit']);
			}
			else 
			{
				$expenses = $this->getexpenses($return[1], $banksettings['exchangeexcost'], $banksettings['exchangeexcostlimit']);
			}
			$return[1] = $return[1] + ceil($expenses);
		}

		return  $return;
	}
	
	function calculate_interest($num = 0, $rate = 0)
	{
		$result = $num * $rate / 1000;
		
		return $result;
	}
	
	function getexchangelist ($k = '')
	{
		global $forums;
		if (!$k) return;
		$forums->func->check_cache('credit_'.$k, 'credit');
		$exchange = unserialize($forums->cache['credit_'.$k]['exchange']);
		$handexchange = explode(',', $exchange['hand']);
		$autoexchange = explode(',', $exchange['auto']);
		$exchangelist = trim(implode(',', array_merge($handexchange, $autoexchange)), ',');
		
		return $exchangelist;
	}
	
	function getexpenses($num = 0, $rate = 0, $limit = '')
	{
		$expenses = 0;
		if (!$num || !$rate || $limit == '') return $expenses;
		$expenses = $this->calculate_interest($num, $rate);
		$expenses = ceil($expenses);
		if ($expenses !== '')
		{
			$expenseslimit = str_replace(' ', '', $limit);
			$expenseslimit = explode('|', $expenseslimit);
			$expenses = min($expenses, $expenseslimit[1]);
			$expenses = max($expenses, $expenseslimit[0]);
		}

		return $expenses;
	}
}

?>