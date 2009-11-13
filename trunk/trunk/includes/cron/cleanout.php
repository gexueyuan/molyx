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
# $Id: cleanout.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class cron_cleanout
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;
		$forums->func->load_lang('cron');
		$date = TIMENOW - (21600);
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "antispam WHERE dateline < " . $date . "");
		$date = $bboptions['cookietimeout'] ? (TIMENOW - $bboptions['cookietimeout'] * 60) : (TIMENOW - 3600);
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "session WHERE lastactivity < " . $date . "");
		$date = TIMENOW - 86400;
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "search WHERE dateline < " . $date . "");
		$this->class->cronlog($this->cron, $forums->lang['cleandata']);
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