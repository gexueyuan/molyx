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

function recache_st()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT id, name, forumids
		FROM " . TABLE_PREFIX . "specialtopic
		ORDER BY id");
	while ($row = $db->fetch($result))
	{
		$return[$row['id']] = $row;
	}
	$db->freeResult($result);

	return $return;
}