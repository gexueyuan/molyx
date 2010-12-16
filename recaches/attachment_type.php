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

function recache_attachment_type()
{
	$db = db::base();

	$return = array();
	$result = $db->query('SELECT extension, mimetype, maxsize, usepost, useavatar, attachimg
		FROM ' . TABLE_PREFIX . 'attachmenttype
		WHERE usepost = 1 OR useavatar = 1');
	while ($r = $db->fetch($result))
	{
		$return[$r['extension']] = $r;
	}
	$db->freeResult($result);

	return $return;
}