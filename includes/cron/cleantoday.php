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
class cron_cleantoday
{
	var $cron = '';

	function docron()
	{
		global $forums, $DB;
		$forums->func->load_lang('cron');
		$stats = $DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "forum SET todaypost = 0");
		$DB->update_cache('todaypost', 0);
		$forums->func->recache('stats');
		$forums->func->recache('ad');
		$forums->func->recache('forum');
		$this->class->cronlog($this->cron, $forums->lang['cleantoday']);
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