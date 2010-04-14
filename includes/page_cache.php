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
$config = array(
	'index' => 3600, //index.php ����ʱ��
	'showthread' => 3600, //showthread.php ����ʱ��
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