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

function recache_badwords()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT badbefore, badafter, type
		FROM " . TABLE_PREFIX . "badword");
	while ($r = $db->fetch($result))
	{
		$return[] = $r;
	}
	$db->freeResult($result);

	return $return;
}