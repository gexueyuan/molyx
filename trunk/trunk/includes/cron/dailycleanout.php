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
# $Id: dailycleanout.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class cron_dailycleanout
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;
		$forums->func->load_lang('cron');
		$deleted = 0;
		$subscribethreadids = array();
		if ($bboptions['removesubscibe'] > 0)
		{
			$time = TIMENOW - ($bboptions['removesubscibe'] * 86400);
			$result = $DB->query('SELECT s.subscribethreadid
				FROM ' . TABLE_PREFIX . 'subscribethread s, ' . TABLE_PREFIX . "thread t
				WHERE t.tid = s.threadid
					AND t.lastpost < '$time'");
			while ($row = $DB->fetch_array($result))
			{
				$subscribethreadids[] = $row['subscribethreadid'];
			}

			if (count($subscribethreadids) > 0)
			{
				$DB->delete(TABLE_PREFIX . 'subscribethread', $DB->sql_in('subscribethreadid', $subscribethreadids));
			}
		}
		$this->class->cronlog($this->cron, $forums->lang['cleansubscribe']);
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