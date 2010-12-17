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

function recache_stats()
{
	$db = db::base();

	$return = $db->readCache(array(
		'numbermembers',
		'maxonline',
		'maxonlinedate',
		'newusername',
		'newuserid'
	));

	return $return;
}