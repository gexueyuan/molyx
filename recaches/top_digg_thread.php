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

function recache_top_digg_thread()
{
	$db = db::base();

	$return = array();
	$bboptions = $GLOBALS['bboptions'];

	$orderby = 'ORDER BY t.digg_exps DESC';
	if ($bboptions['diggshowcondition'])
	{
		if (in_array($bboptions['diggshowtype'], array('lastpost', 'dateline')))
		{
			$bboptions['diggshowtype'] ;
			$where = ' AND t.' . $bboptions['diggshowtype'] . ' >= ' . (TIMENOW - intval($bboptions['diggshowcondition']) * 86400);
		}
		elseif(in_array($bboptions['diggshowtype'], array('views', 'digg_exps', 'post', 'views', 'digg_users')))
		{
			$where = ' AND t.' . $bboptions['diggshowtype'] . ' >= ' . intval($bboptions['diggshowcondition']);
		}
		elseif ($bboptions['diggshowtype'] == 'digg_time')
		{
			$field = ', sum(exponent) AS cur_digg_exps ';
			$leftjoin = ' LEFT JOIN ' . TABLE_PREFIX . 'digg_log d
							ON d.threadid=t.tid';
			$orderby = 'GROUP BY t.tid ORDER BY cur_digg_exps DESC';
			$where = ' AND d.digg_time >= ' . (TIMENOW - intval($bboptions['diggshowcondition']) * 86400);
		}
	}

	$result = $db->query("SELECT u.avatar, t.tid, t.digg_users, t.digg_exps, t.post, t.views, t.lastpost, t.title, t.postusername, t.postuserid, t.dateline{$field}
		FROM " . TABLE_PREFIX . 'thread t
			LEFT JOIN ' . TABLE_PREFIX . 'user u
				ON u.id = t.postuserid
				' . $leftjoin . '
		WHERE t.visible = 1
			AND digg_exps > 0
			' . $where . '
		' . $orderby . '
		LIMIT ' . intval($bboptions['top_digg_thread_num']));
	while ($r = $db->fetch($result))
	{
		$return[$r['tid']] = $r;
	}
	$db->freeResult($result);

	return $return;
}