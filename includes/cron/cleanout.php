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