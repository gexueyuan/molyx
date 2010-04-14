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
class cron_attachmentviews
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;
		if ($bboptions['attachmentviewsdelay'])
		{
			$forums->func->load_lang('cron');
			if ($attachmentviews = @file(ROOT_PATH . 'cache/cache/attachmentviews.txt'))
			{
				@unlink(ROOT_PATH . 'cache/cache/attachmentviews.txt');
				$attachmentviews = array_count_values($attachmentviews);
				$result = $DB->update_case(TABLE_PREFIX . 'attachment', 'attachmentid', array(
					'counter' => array($attachmentviews, '+')
				));
				if ($result)
				{
					$this->class->cronlog($this->cron, $forums->lang['attachmentviews']);
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