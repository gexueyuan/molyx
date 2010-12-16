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

function recache_announcement()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT a.*, u.id AS userid, u.name, u.avatar
			    FROM " . TABLE_PREFIX . "announcement a
			   		LEFT JOIN " . TABLE_PREFIX . "user u
			   			ON (a.userid = u.id)
			    WHERE a.active != 0
			    ORDER BY startdate DESC, enddate DESC");
	while ($r = $db->fetch($result))
	{
		$start_ok = false;
		$end_ok = false;

		if (!$r['startdate'])
		{
			$start_ok = true;
		}
		else if ($r['startdate'] < TIMENOW)
		{
			$start_ok = true;
		}
		if (!$r['enddate'])
		{
			$end_ok = true;
		}
		else if ($r['enddate'] > TIMENOW)
		{
			$end_ok = true;
		}

		if ($start_ok && $end_ok)
		{
			$return[$r['id']] = array(
				'id' => $r['id'],
				'title' => $r['title'],
				'notagtitle' => strip_tags($r['title']),
				'titlecut' => $GLOBALS['forums']->func->fetch_trimmed_title($r['title'], 20),
				'startdate' => $r['startdate'],
				'enddate' => $r['enddate'],
				'forumid' => $r['forumid'],
				'views' => $r['views'],
				'userid' => $r['userid'],
				'avatar' => $r['avatar'],
				'username' => $r['name']
			);
		}
	}
	$db->freeResult($result);

	return $return;
}