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
class cron_threadviews
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;
		if ($bboptions['threadviewsdelay'])
		{
			$forums->func->load_lang('cron');
			if ($threadviews = @file(ROOT_PATH . 'cache/cache/threadviews.txt'))
			{
				@unlink(ROOT_PATH . 'cache/cache/threadviews.txt');
				$threadviews = array_count_values($threadviews);
				$result = $DB->update_case(TABLE_PREFIX . 'thread', 'tid', array('views' => array($threadviews, '+')));
				if ($result)
				{
					$this->class->cronlog($this->cron, $forums->lang['threadviews']);
				}
			}
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