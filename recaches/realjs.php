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

function recache_realjs()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT id, type, jsname, inids, numbers, perline, selecttype, daylimit, orderby, trimtitle, trimdescription, trimpagetext, export, htmlcode
		FROM " . TABLE_PREFIX . "javascript
		ORDER BY id");
	while ($row = $db->fetch($result))
	{
		$return[$row['id']] = $row;
	}
	$db->freeResult($result);

	return $return;
}