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

function recache_adminforum()
{
	$forums = $GLOBALS['forums'];

	$return = array();
	if ($forums->adminforum instanceof adminfunctions_forum)
	{
		$return = $forums->adminforum->cache_forums('-1', 0, true);
	}

	return $return;
}