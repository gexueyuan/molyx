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

function recache_userextrafield()
{
	$db = db::base();

	$return = array();
	$result = $db->query("SELECT *
		FROM " . TABLE_PREFIX . "userextrafield");
	while ($r = $db->fetch($result))
	{
		$r['listcontent'] = unserialize($r['listcontent']);

		// 全部
		$return['a'][$r['fieldtag']] = $r;
		if ($r['ismustfill'])
		{
			//必须填写的项目
			$return['f'][$r['fieldtag']] = $r['fieldname'];
		}
		if ($r['checkregular'])
		{
			//需检测正则的项目
			$return['r'][$r['fieldtag']] = array($r['fieldname'], input::unclean($r['checkregular']));
		}
		if ($r['isonly'])
		{
			//唯一的项目
			$return['o'][$r['fieldtag']] = $r['fieldname'];
		}
	}
	$db->freeResult($result);

	return $return;
}