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

function recache_ranks()
{
	$db = db::base();

	$return = array();

	$result = $db->query("SELECT id, title, ranklevel, post
		FROM " . TABLE_PREFIX . "usertitle
		ORDER BY post DESC");
	while ($i = $db->fetch($result))
	{
		$return[$i['id']] = array(
			'title' => $i['title'],
			'ranklevel' => $i['ranklevel'],
			'post' => $i['post']
		);
	}
	$db->freeResult($result);

	return $return;
}