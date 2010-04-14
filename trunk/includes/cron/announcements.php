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
class cron_announcements
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums;
		$forums->func->load_lang('cron');
		$forums->func->recache('announcement');
		$this->class->cronlog($this->cron, $forums->lang['updateannounce']);
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