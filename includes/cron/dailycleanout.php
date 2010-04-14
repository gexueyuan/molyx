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