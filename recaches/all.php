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

function recache_all()
{
	$db = db::base();

	cache::update('ad');
	cache::update('adminforum');
	cache::update('announcement');
	cache::update('attachmenttype');
	cache::update('badwords');
	cache::update('banfilter');
	cache::update('bbcode');
	cache::update('birthdays');
	cache::update('creditevent');
	cache::update('creditlist');
	cache::update('icon');
	cache::update('league');
	cache::update('ranks');
	cache::update('realjs');
	cache::update('settings');
	cache::update('smile');
	cache::update('splittable');
	cache::update('st');
	cache::update('stats');
	cache::update('style');
	cache::update('top_digg_thread');
	cache::update('userextrafield');

	cache::update('forum');
	$result = $db->query('SELECT id
		FROM ' . TABLE_PREFIX . "forum");
	while ($row = $db->fetch($result))
	{
		$id = $row['id'];

		cache::update('creditrule_forum', $id);
		cache::update('forum_active_user', $id);
		cache::update('forum_area', $id);
		cache::update('forum_commend_thread', $id);
		cache::update('forum', $id);
		cache::update('subforum', $id);
		cache::update('moderator', $id);
	}

	cache::update('usergroup');
	$result = $db->query('SELECT usergroupid
		FROM ' . TABLE_PREFIX . "usergroup");
	while ($row = $db->fetch($result))
	{
		$id = $row['usergroupid'];

		cache::update('creditrule_group', $id);
		cache::update('usergroup', $id);
	}

	cache::update('moderator');
	$result = $db->query('SELECT *
		FROM ' . TABLE_PREFIX . "moderator
		WHERE usergroupid = $id
			AND isgroup = 1");
	while ($r = $db->fetch($result))
	{
		if ($row['isgourp'])
		{
			cache::update('moderator_group', $row['usergroupid']);
		}
		else
		{
			cache::update('moderator_user', $row['userid']);
		}
	}

	return TIMENOW;
}