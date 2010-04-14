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
class cron_forum_active_user
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions, $_INPUT;
		if ($bboptions['forum_active_user'])
		{
			$forums->func->check_cache('forum');
			foreach ($forums->cache['forum'] AS $fid => $v)
			{
				$_INPUT['f'] = $fid;
				$forums->func->recache('forum_active_user');
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