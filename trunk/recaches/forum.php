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

function recache_forum($id = NULL)
{
	$forums = $GLOBALS['forums'];

	$return = $forum_layer = array();
	if (!is_object($forums->adminforum))
	{
		require_once(ROOT_PATH . 'includes/adminfunctions_forum.php');
		$forums->adminforum = new adminfunctions_forum();
	}

	$all_forum = $forums->adminforum->cache_forums('-1', 0, true, true);
	foreach ($all_forum as $fid => $r)
	{
		if ($id === NULL)
		{
			$return[$fid] = array(
				'id' => $r['id'],
				'name' => $r['name'],
				'description' => $r['description'],
				'url' => $r['url'],
				'parentid' => $r['parentid'],
				'depth' => $r['depth'],
				'canshow' => $r['canshow'],
				'canread' => $r['canread'],
				'parentlist' => $r['parentlist'],
				'showthreadlist' => $r['showthreadlist'],
				'password' => $r['password'],
			);
		}
		else
		{
			$forum = array(
				'id' => $r['id'],
				'forumicon' => $r['forumicon'],
				'parentid' => $r['parentid'],
				'parentlist' => $r['parentlist'],
			);

			if (empty($r['url']))
			{
				$forum['style'] = $r['style'];
				$forum['allowbbcode'] = $r['allowbbcode'];
				$forum['allowhtml'] = $r['allowhtml'];
				$forum['status'] = $r['status'];
				$forum['sortby'] = $r['sortby'];
				$forum['sortorder'] = $r['sortorder'];
				$forum['prune'] = $r['prune'];
				$forum['moderatepost'] = $r['moderatepost'];
				$forum['allowpoll'] = $r['allowpoll'];
				$forum['allowpollup'] = $r['allowpollup'];
				$forum['countposts'] = $r['countposts'];
				$forum['childlist'] = $r['childlist'];
				$forum['allowposting'] = $r['allowposting'];
				$forum['displayorder'] = $r['displayorder'];
				$forum['forumcolumns'] = $r['forumcolumns'];
				$forum['threadprefix'] = $r['threadprefix'];
				$forum['forcespecial'] = $r['forcespecial'];
				$forum['specialtopic'] = $r['specialtopic'];
				$forum['forumrule'] = $r['forumrule'];
				$forum['canreply'] = $r['canreply'];
				$forum['canstart'] = $r['canstart'];
				$forum['canupload'] = $r['canupload'];
			}

			if ($r['depth'] < 2)
			{
				if ($forum['parentid'] == '-1')
				{
					$forum_layer[0][$forum['id']]['self'] = $forum;
				}
				else
				{
					$forum_layer[0][$forum['parentid']]['childs'][$forum['id']] = $forum;
				}
			}

			if ($forum['parentid'] != '-1')
			{
				$forum_layer[$forum['parentid']]['childs'][$forum['id']] = $forum;
			}
			$forum_layer[$forum['id']]['self'] = $forum;
		}
	}

	if ($id !== NULL)
	{
		$id = (int) $id;
		$return = $forum_layer[$id];
	}

	return $return;
}