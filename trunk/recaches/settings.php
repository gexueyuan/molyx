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

function recache_settings()
{
	$db = db::base();

	$return = array();

	$result = $db->query("SELECT *
		FROM " . TABLE_PREFIX . "setting
		WHERE addcache = 1");
	while ($r = $db->fetch($result))
	{
		$value = !empty($r['value']) ? $r['value'] : $r['defaultvalue'];
		if ($value == '{blank}')
		{
			$value = '';
		}

		$return[$r['varname']] = $value;
	}
	$db->freeResult($result);

	return $return;
}