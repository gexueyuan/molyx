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
# $Id$
# **************************************************************************#
class cron_bankloancheck
{
	var $class;
	var $cron = "";

	function docron()
	{
		global $DB, $forums;
		$forums->func->load_lang('cron');
		$overloanusers = $DB->query("SELECT u.id, u.name, u.usergroupid, u.bank, u.mkaccount, e.*, ex.*
				FROM " . TABLE_PREFIX . "userextra e
			LEFT JOIN " . TABLE_PREFIX . "userexpand ex ON (e.id = ex.id)	
			LEFT JOIN " . TABLE_PREFIX . "user u ON (e.id = u.id) 
			WHERE e.loanamount > 0 AND e.loanreturn < " . TIMENOW);
		$forums->func->check_cache('banksettings');
		$banksettings = $forums->cache['banksettings'];
		require_once(ROOT_PATH . 'includes/xfunctions_bank.php');
		$this->bankfunc = new bankfunc();
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		$forums->func->check_cache('credit_'.$banksettings['bankcredit'], 'credit');
		$bankcredit = $forums->cache['credit_'.$banksettings['bankcredit']];
		$defaultration = $bankcredit['ratio'];
		$forums->func->check_cache('creditlist');
		$lists = array();
		if ($forums->cache['creditlist']) 
		{
			foreach ($forums->cache['creditlist'] as $k => $v) 
			{
				$lists[$v['tag']] = $v['name'];
			}
		}
		while ($thisuser = $DB->fetch_array($overloanusers))
		{
			$property = 0;
			foreach ($lists as $tagname => $name)
			{
				$forums->func->check_cache("credit_$tagname", 'credit');
				$ruptcycredit = $forums->cache["credit_$tagname"]['bankruptcy'];
				$ruptcycredit = str_replace(' ', '', $ruptcycredit);
				$c_limit = $forums->cache["credit_$tagname"]['c_limit'];
				$c_limit = intval($c_limit);
				$ratio = $forums->cache["credit_$tagname"]['ratio'];
				if ($ruptcycredit == '=_limit' || ((substr($ruptcycredit, 0 ,1)== "=") && (substr($ruptcycredit, 1) == $c_limit)))
				{
					if (!$ratio || !$defaultration) continue;
					$property += $thisuser[$tagname] * $ratio / $defaultration;
				}
			}
			$property = $property + $thisuser['bank'];
			$moneytoreturn = $this->bankfunc->calculate_interest($thisuser['loanamount'], 1000 + $thisuser['loaninterest']);
			$leftmoney = $moneytoreturn - $property;
			if ($leftmoney > 0)
			{
				if ($banksettings['bankruptcy'])
				{
					$banhours = ceil($leftmoney / $banksettings['bankruptcy']);
					$endtime = TIMENOW + $banhours*3600;
					$banstring = TIMENOW . ":" . $endtime . ":" . $banhours . ":h:" . $bbuserinfo['usergroupid'] . ":banksystem";
					$DB->update(TABLE_PREFIX . 'user', array ('mkaccount' => 0, 'bank' => -$leftmoney, 'liftban' => $banstring), 'id=' . $thisuser['id']);
				}
			}
			else 
			{
				$DB->update(TABLE_PREFIX . 'user', array ('mkaccount' => 0, 'bank' => abs($leftmoney)), 'id=' . $thisuser['id']);
			}
			$banklog = array (
				'fromuserid' => $thisuser['id'],
				'touserid' => $thisuser['id'],
				'action' => addslashes($forums->lang['cleanamount']),
				'dateline' => TIMENOW,
				'type' => 0,
			);
			$DB->insert(TABLE_PREFIX . 'banklog', $banklog);
			$userextra = array (
				'loanreturn' => 0,
				'loanamount' => 0,
				'loaninterest' => 0,
				'question' => '',
				'answer' => '',
			);
			$DB->update(TABLE_PREFIX . 'userextra', $userextra, 'id=' . $thisuser['id']);
		}
		$this->class->cronlog($this->cron, $forums->lang['bankloan']);
	}

	function register_class(&$class)
	{
		$this->class = $class;
	}

	function pass_cron($this_cron)
	{
		$this->cron = $this_cron;
	}
}

?>