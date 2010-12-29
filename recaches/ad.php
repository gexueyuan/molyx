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

function recache_ad()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT *
		FROM " . TABLE_PREFIX . "ad
		WHERE (endtime = 0
				OR endtime >= " . TIMENOW . ")
			AND starttime <= " . TIMENOW . "
		ORDER BY type, displayorder");
	while ($row = $db->fetch($result))
	{
		$return['content'][$r['id']] = $r['htmlcode'];
		if ($r['ad_in'] == '-1')
		{
			$return[$r['type']]['all'][] = $r['id'];
		}
		else
		{
			$forumids = explode(',', $r['ad_in']);
			foreach ($forumids as $fid)
			{
				if ($fid == '0')
				{
					$return[$r['type']]['index'][] = $r['id'];
				}
				else
				{
					$return[$r['type']][$fid][] = $r['id'];
				}
			}
		}
	}
	$db->freeResult($result);

	return $return;
}