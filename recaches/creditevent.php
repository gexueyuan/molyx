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

function recache_creditevent()
{
	$db = db::base();

	$return = array();
	$result = $db->query('SELECT *
		FROM ' . TABLE_PREFIX . 'creditevent');
	while ($row = $db->fetch($result))
	{
		$return[$row['eventid']] = $row;
	}
	$db->freeResult($result);

	return $return;
}