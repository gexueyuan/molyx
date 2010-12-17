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

function recache_moderator($id = NULL)
{
	$db = db::base();

	$return = array();

	$sql_array = array(
		'SELECT' => '*',
		'FROM' => TABLE_PREFIX . 'moderator',
	);

	if ($id !== NULL)
	{
		$id = (int) $id;
		$sql_array['WHERE'] = "forumid = $id";
	}

	$result = $db->select($sql_array);
	while ($r = $DB->fetch($result))
	{
		$return[$i['moderatorid']] = $r;
	}
	$db->freeResult($result);

	return $return;
}