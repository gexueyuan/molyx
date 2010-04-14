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
define('THIS_SCRIPT', 'associate');
require ('./global.php');

class associate
{
	function show()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		if (!$bbuserinfo['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['notlogin']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		switch ($_INPUT['do'])
		{
			case 'undo':
				$this->unassociate();
				break;
			default:
				$this->doassociate();
				break;
		}
	}

	function doassociate()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		if ($bbuserinfo['mobile'])
		{
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['alreadyassociate']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		if ($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])
		{
			$usermobile = $_SERVER['HTTP_X_UP_CALLING_LINE_ID'];
		}
		else if ($_SERVER['HTTP_X_WAP_CLIENTID'])
		{
			$usermobile = $_SERVER['HTTP_X_UP_CALLING_LINE_ID'];
		}
		else
		{
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['unknownmobile']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$DB->update(TABLE_PREFIX . 'user', array('mobile' => $usermobile), 'id = ' . $bbuserinfo['id']);
		redirect("index.php{$forums->sessionurl}");
	}

	function unassociate()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		if (!$bbuserinfo['mobile'])
		{
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['notassociate']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET mobile='' WHERE id=" . $bbuserinfo['id'] . "");
		redirect("index.php{$forums->sessionurl}");
	}
}

$output = new associate();
$output->show();

?>