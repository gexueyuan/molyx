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
define('THIS_SCRIPT', 'cilck');
require_once('./global.php');

class cilck
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$_INPUT['id'] = intval($_INPUT['id']);
		$forums->func->check_cache('ad');
		if ($_INPUT['id'] AND $forums->cache['ad']['content'][$_INPUT['id']])
		{
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "ad SET click = click + 1 WHERE id = {$_INPUT['id']}");
			$forums->func->standard_redirect($_GET['url']);
		}
	}
}

$output = new cilck();
$output->show();

?>