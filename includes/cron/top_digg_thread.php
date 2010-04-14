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
class cron_top_digg_thread
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions, $_INPUT;
		if ($bboptions['top_digg_thread_num'])
		{
			$forums->func->recache('top_digg_thread');
		}
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