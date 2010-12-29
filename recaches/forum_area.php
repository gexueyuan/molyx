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

function recache_forum_area($id)
{
	$id = (int) $id;
	$db = db::base();

	$return = $area = array();
	$result = $db->query('SELECT *
		FROM ' . TABLE_PREFIX . "area
		WHERE forumid IN (0, $id)
		ORDER BY orderid ASC");
	while ($r = $db->fetch($result))
	{
		$area[$r['areaid']] = $r;
	}
	$db->freeResult($result);

	if ($area)
	{
		foreach ($area as $k => $v)
		{
			if ($v['show_record'])
			{
				$return[$k]['name'] = $v['areaname'];

				$result = $db->query('SELECT *
					FROM ' . TABLE_PREFIX . "area_content
					WHERE areaid = $k
					ORDER BY orderid ASC, id DESC
					LIMIT " . intval($v['show_record']));
				while ($r = $db->fetch($result))
				{
					if ($r['titlelink'])
					{
						$r['title'] = '<a href="' . $r['titlelink'] . '" target="' . $r['target'] . '">' . $r['title'] . '</a>';
					}
					unset($r['titlelink'], $r['target']);
					$return[$r['areaid']][$r['id']] = $r;
				}
				$db->freeResult($result);
			}
		}
	}

	return $return;
}