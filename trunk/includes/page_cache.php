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
$config = array(
	'index' => 3600, //index.php 过期时间
	'showthread' => 3600, //showthread.php 过期时间
);

if (array_key_exists(THIS_SCRIPT, $config))
{
	$ttl = $config[THIS_SCRIPT];
	$prefix = $bboptions['language'];
	if (THIS_SCRIPT == 'showthread')
	{
		$prefix .= '_' . floor($_INPUT['t'] / 200);
	}

	require_once(ROOT_PATH . 'includes/class_cache_page.php');
	$forums->page_cache = new cache_page($ttl, $prefix);
	$forums->page_cache->start();
}
?>