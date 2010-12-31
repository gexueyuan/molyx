<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
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
		$stats = $DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "forum SET todaypost = 0");
		$DB->updateCache('todaypost', 0);
		cache::update('stats');
		cache::update('ad');
		cache::update('forum');
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