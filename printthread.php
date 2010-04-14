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
define('THIS_SCRIPT', 'printthread');
require_once('./global.php');

class printthread
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('printthread');
		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$_INPUT['t'] = intval($_INPUT['t']);
		if (!$_INPUT['t'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$thread = $DB->query_first('SELECT tid, title, forumid, postusername, postuserid, sticky,posttable
			FROM ' . TABLE_PREFIX . 'thread
			WHERE tid = ' . $_INPUT['t']);
		$forum = $forums->forum->single_forum($thread['forumid']);
		if (!$forum['id'] || !$thread['tid'])
		{
			$forums->func->standard_error('erroraddress');
		}
		if (!$bbuserinfo['canviewothers'] && $thread['postuserid'] != $bbuserinfo['id'])
		{
			$forums->func->standard_error('cannotviewthread');
		}
		$forums->forum->check_permissions($forum['id'], 1, 'thread', $thread['postuserid']);
		$maxposts = 300;
		$posttable = $thread['posttable'] ? $thread['posttable'] : 'post';
		if ($this->forum['moderatepost'])
		{
			$moderate = ' AND moderate=0';
			if ($this->can_moderate($this->thread['forumid']))
			{
				$moderate = '';
				if ($_INPUT['modfilter'] == 'invisiblepost')
				{
					$moderate = ' AND moderate=1';
				}
			}
		}
		else
		{
			$moderate = '';
		}

		$thispost = $user_ids = $user_array = $cached_users = array();
		$result = $DB->query('SELECT *
			FROM ' . TABLE_PREFIX . $posttable . '
			WHERE threadid = ' . $thread['tid'] . "{$moderate}
			ORDER BY pid
			LIMIT 0, " . $maxposts);
		while ($post = $DB->fetch_array($result))
		{
			$thispost[] = $post;
			if ($post['userid'] != 0 && !in_array($post['userid'], $user_ids))
			{
				$user_ids[] = $post['userid'];
			}
		}
		if (count($user_ids))
		{
			$result = $DB->query('SELECT *
				FROM ' . TABLE_PREFIX . 'user
				WHERE ' . $DB->sql_in('id', $user_ids));
			while ($user = $DB->fetch_array($result))
			{
				if ($user['id'] && $user['name'])
				{
					if (isset($user_array[ $user['id'] ]))
					{
						continue;
					}
					else
					{
						$user_array[ $user['id'] ] = $user;
					}
				}
			}
		}
		$forums->func->check_cache('usergroup');
		foreach ($thispost as $row)
		{
			$poster = array();
			if ($row['userid'] != 0)
			{
				if (isset($cached_users[ $row['userid'] ]))
				{
					$poster = $cached_users[ $row['userid'] ];
					$row['name_css'] = 'normalname';
				}
				else
				{
					if ($user_array[$row['userid']])
					{
						$row['name_css'] = 'normalname';
						$poster = $user_array[ $row['userid'] ];
						$cached_users[ $row['userid'] ] = $poster;
					}
					else
					{
						$poster = $forums->func->set_up_guest($row['userid']);
						$row['name_css'] = 'unreg';
					}
				}
			}
			else
			{
				$poster = $forums->func->set_up_guest($row['name']);
				$row['name_css'] = 'unreg';
			}
			$row['name'] = $poster['name'];
			$row['post_css'] = $td_col_count % 2 ? 'row1' : 'row2';
			++$td_col_count;
			$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
			if ($row['hidepost'])
			{
				$row['pagetext'] = $forums->lang['_posthidden'];
			}
			else
			{
				$row['pagetext'] = preg_replace(array("#<!--Flash (.+?)-->.+?<!--End Flash-->#e", "#<a href=[\"'](http|news|https|ftp|ed2k|rtsp|mms)://(\S+?)['\"].+?" . ">(.+?)</a>#"), array("[FLASH]" , "\\1://\\2"), $row['pagetext']);
				$row['pagetext'] = textparse::convert_text($row['pagetext'], ($forum['allowhtml'] && $forums->cache['usergroup'][$poster['usergroupid']]['canposthtml']));
			}
			$data[] = $row;
		}
		include $forums->func->load_template('print_thread');
		exit;
	}
}

$output = new printthread();
$output->show();
?>