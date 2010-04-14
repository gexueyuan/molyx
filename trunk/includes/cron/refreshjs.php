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
class cron_refreshjs
{
	var $cron = "";

	function docron()
	{
		global $DB, $forums;
		$forums->func->load_lang('cron');
		require_once(ROOT_PATH . 'includes/adminfunctions_javascript.php');
		$this->lib = new adminfunctions_javascript();
		$cron_time = $DB->query_first("SELECT nextrun FROM " . TABLE_PREFIX . "cron WHERE filename = 'refreshjs.php'");
		$this_jss = $DB->query("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE nextrun <= " . $cron_time['nextrun'] . " AND refresh != 0");
		if ($DB->num_rows($this_jss))
		{
			while ($js = $DB->fetch_array($this_jss))
			{
				$this->lib->createjs($js, 1);
				if ($js['refresh'] > 0)
				{
					$nextrun = TIMENOW + 60;
				}
				if ($cron_time['nextrun'] > $nextrun)
				{
					$cron_time['nextrun'] = $nextrun;
					$update_db = true;
				}
				if (!$next_do_cron)
				{
					$next_do_cron = $nextrun;
				}
				$next_do_cron = ($nextrun < $next_do_cron) ? $nextrun : $next_do_cron;
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "javascript SET nextrun='" . $next_do_cron . "' WHERE id = " . $js['id'] . "");
			}
			if ($update_db)
			{
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "cron SET nextrun='" . $cron_time['nextrun'] . "' WHERE filename = 'refreshjs.php'");
			}
			if ($forums->cache['cron'] > $cron_time['nextrun'])
			{
				$forums->func->update_cache(array('name' => 'cron', 'value' => $next_do_cron, 'array' => 0));
			}
		}
		else
		{
			$new_js = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "javascript WHERE nextrun > " . $cron_time['nextrun'] . " AND refresh != 0 ORDER BY nextrun LIMIT 0, 1");
			if ($new_js)
			{
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "cron SET nextrun='" . $new_js['nextrun'] . "' WHERE filename = 'refreshjs.php'");
			}
		}
		$this->class->cronlog($this->cron, $forums->lang['refreshjs']);
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