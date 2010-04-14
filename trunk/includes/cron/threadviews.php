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