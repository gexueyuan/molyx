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

function recache_smile()
{
	$db = db::base();

	$return = array();

	$result = $db->query("SELECT id, smiletext, image
		FROM " . TABLE_PREFIX . "smile
		ORDER BY displayorder, id");
	while ($r = $db->fetch($result))
	{
		$return[] = $r;
	}
	$db->freeResult($result);

	return $return;
}