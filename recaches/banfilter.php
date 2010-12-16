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

function recache_banfilter()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT content
		FROM " . TABLE_PREFIX . "banfilter
		WHERE type = 'ip'");
	while ($r = $db->fetch($result))
	{
		$return[] = $r['content'];
	}
	$db->freeResult($result);

	return $return;
}