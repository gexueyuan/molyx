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

function recache_moderator_user($id)
{
	$db = db::base();

	$id = (int) $id;
	$return = array();
	$result = $db->query('SELECT *
		FROM ' . TABLE_PREFIX . "moderator
		WHERE userid = $id
			AND isgroup = 0");
	while ($r = $db->fetch($result))
	{
		$return[$r['moderatorid']] = $r;
	}

	return $return;
}