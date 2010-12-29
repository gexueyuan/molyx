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

function recache_forum_active_user($id)
{
	$id = (int) $id;
	$db = db::base();

	$return = array();
	$result = $db->query('SELECT u.avatar, u.id, u.name, count(tid) AS threads
		FROM ' . TABLE_PREFIX . 'thread t
			LEFT JOIN ' . TABLE_PREFIX . 'user u
				ON u.id = t.postuserid
		WHERE t.forumid = ' . $id . '
			AND t.visible = 1
			AND t.postuserid > 0
		GROUP BY u.id
		ORDER BY threads DESC, u.lastactivity DESC
		LIMIT ' . intval($GLOBALS['bboptions']['forum_active_user']));

	while ($r = $db->fetch($result))
	{
		$return[$r['tid']] = $r;
	}
	$db->freeResult($result);

	return $return;
}