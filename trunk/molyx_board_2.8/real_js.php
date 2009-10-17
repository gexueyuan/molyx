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
# $Id: real_js.php 64 2007-09-07 09:19:11Z hogesoft-02 $
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