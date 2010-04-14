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
define('THIS_SCRIPT', 'real_js');
require_once('./global.php');

class attach
{
	function show()
	{
		global $_INPUT, $forums;
		$id = intval($_INPUT['id']);
		$forums->func->check_cache('realjs');
		if (!is_array($forums->cache['realjs'][$id]))
		{
			$htmlcode = "";
		}
		else
		{
			require_once(ROOT_PATH . 'includes/adminfunctions_javascript.php');
			$this->lib = new adminfunctions_javascript();
			$htmlcode = $this->lib->createjs($forums->cache['realjs'][$id], 0);
		}
		echo $htmlcode;
	}
}

$output = new attach();
$output->show();

?>