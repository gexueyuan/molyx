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
# $Id: rebuildstats.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class cron_rebuildstats
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums;
		$forums->func->load_lang('cron');
		require_once(ROOT_PATH . 'includes/adminfunctions.php');
		adminfunctions::recount_stats(1);
		$this->class->cronlog($this->cron, $forums->lang['rebuildstats']);
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