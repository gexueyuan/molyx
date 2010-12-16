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

function recache_usergroup($id = NULL)
{
	$db = db::base();

	$return = array();

	$sql_array = array(
		'SELECT' => '*',
		'FROM' => TABLE_PREFIX . 'usergroup',
		'ORDER_BY' => 'displayorder ASC',
	);

	if ($id !== NULL)
	{
		$id = (int) $id;
		$sql_array['WHERE'] = "usergroupid = $id";

		$return = $db->selectFirst($sql_array);
	}
	else
	{
		$result = $db->select($sql_array);
		while ($r = $DB->fetch($result))
		{
			$return[$r['usergroupid']] = $r;
		}
		$db->freeResult($result);
	}

	return $return;
}