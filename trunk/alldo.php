<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'alldo');
require_once('./global.php');

class alldo
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('alldo');
		/*状态历程*/
		$firstpost = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$showcondition = array();
		$showcondition[] = " userdotime > 0";
		$showcondition = ' WHERE ' . implode(',', $showcondition);
		$sql = 'SELECT count(*) AS count FROM ' . TABLE_PREFIX . 'user
				' . $showcondition;
		$sqlcount = $DB->query_first($sql);
		$perpage = 20;
		$pagenav = $forums->func->build_pagelinks(array(
			'totalpages' => $sqlcount['count'],
			'perpage' => $perpage,
			'curpage' => $firstpost,
			'pagelink' => "alldo.php{$forums->sessionurl}{$this->extra}",
		));
		$userjourney = array();
		$sql = 'SELECT id, name, avatar, usercurdo, userdotime FROM ' . TABLE_PREFIX . 'user
				' . $showcondition . '
				ORDER BY userdotime DESC
				LIMIT ' . $firstpost . ',' . $perpage;
		$DB->query($sql);
		while ($row = $DB->fetch_array())
		{
			$row['avatar'] = $forums->func->get_avatar($row['id'], $row['avatar'], '1');//显示中等头像
			if (!$row['usercurdo'])
			{
				$row['usercurdo'] = $forums->lang['notfilldowhat'];
			}
			$row['userdotime'] = $forums->func->get_date($row['userdotime']);
			$userjourney[$row['id']] = $row;
		}
		/*结束*/

		/*论坛新人*/
		$newmember = array();
		$sql = 'SELECT id, name, avatar FROM ' . TABLE_PREFIX . 'user
				WHERE usergroupid = 3
				ORDER BY joindate DESC
				LIMIT 0, 10
				';
		$DB->query($sql);
		while ($row = $DB->fetch_array())
		{
			$row['joindate'] = $forums->func->get_date($row['joindate']);
			$row['avatar'] = $forums->func->get_avatar($row['id'], $row['avatar'], '1');//显示中等头像
			$newmember[$row['id']] = $row;
		}

		/*结束*/

		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		$pagetitle = $forums->lang['alldo'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['alldo']);
		include $forums->func->load_template('doing');
		exit;
	}
}

$output = new alldo();
$output->show();

?>