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

function recache_creditrule_forum($fid)
{
	$fid = (int) $fid;
	$db = db::base();

	$return = $array = array();
	$result = $db->query('SELECT *
		FROM ' . TABLE_PREFIX . 'creditrule');
	while ($row = $db->fetch($result))
	{
		if ($row['type']==0)
		{
			$array[$row['creditid']]['default'] = unserialize($row['parameters']);
			$creditlist[] = $row['creditid'];
		}
		if ($row['lists'] && $row['type'])
		{
			$ids = explode(',', $row['lists']);
			foreach ($ids as $id)
			{
				$array[$row['creditid']][$id][$row['type']] = unserialize($row['parameters']);
			}
		}
	}
	$db->freeResult($result);

	foreach ($creditlist as $creditid)
	{
		$return[$creditid]['default'] = $array[$creditid]['default'];
		$return[$creditid]['alter'] = $array[$creditid][$fid][2];
	}

	return $return;
}