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

function recache_forum_commend_thread($id)
{
	$id = (int) $id;
	$db = db::base();

	$return = array();
	$result = $db->query('SELECT u.avatar, t.tid, t.mod_commend, t.title, t.postusername, t.postuserid, t.dateline
		FROM ' . TABLE_PREFIX . 'thread t
			LEFT JOIN ' . TABLE_PREFIX . 'user u
				ON u.id = t.postuserid
		WHERE t.forumid = ' . $id . '
			AND t.mod_commend > 0
			AND t.visible = 1
		ORDER BY t.mod_commend DESC, t.dateline DESC
		LIMIT ' . intval($GLOBALS['bboptions']['commend_thread_num']));

	while ($r = $db->fetch($result))
	{
		$return[$r['tid']] = $r;
	}
	$db->freeResult($result);

	return $return;
}