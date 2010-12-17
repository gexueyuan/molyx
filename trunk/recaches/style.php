<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#

function recache_style()
{
	$db = db::base();
	$forums = $GLOBALS['forums'];

	$return = array();

	if ($forums->admin === NULL)
	{
		require_once(ROOT_PATH . 'includes/adminfunctions.php');
		$forums->admin = new adminfunctions();
	}

	$forums->admin->cache_styles();
	foreach ($forums->admin->stylecache as $style)
	{
		if (!$style['userselect'])
		{
			continue;
		}

		$styleid = intval($style['styleid']);
		$return[$styleid] = array(
			'styleid' => $styleid,
			'title' => $style['title'],
			'title_en' => $style['title_en'],
			'depth' => $style['depth'],
			'parentid' => $style['parentid'],
			'parentlist' => $style['parentlist'],
			'userselect' => $style['userselect'],
			'usedefault' => $style['usedefault'],
			'imagefolder' => $style['imagefolder'],
		);
	}

	return $return;
}