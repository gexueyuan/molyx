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
class modfunctions
{
	var $error = '';
	var $thread = array();
	var $forum = array();
	var $moderator = array();
	var $type = array();
	var $delete_thread = array();
	var $credit = '';

	function init($forum = '', $thread = '', $moderator = '')
	{
		$this->forum = $forum;
		if (is_array($thread))
		{
			$this->thread = $thread;
		}
		if (is_array($moderator))
		{
			$this->moderator = $moderator;
		}
		return true;
	}

	function post_delete($pids, $recount=0, $posttable='')
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		if (empty($pids))
		{
			return false;
		}
		$thread = $post = $attach_tid = $forum = $user = array();
		$stats_todaypost = 0;
		$today = getdate();
		$directdel = false;
		//未开启回收站，直接删除主题，进行用户积分操作
		if (!$bboptions['enablerecyclebin'] || !$bboptions['recycleforumid'])
		{
			if (!is_object($this->credit))
			{
				require_once(ROOT_PATH . "includes/functions_credit.php");
				$this->credit = new functions_credit();
			}
			$userids = $groupids = $forumids = array();
			$directdel = true;
		}
		$posts = array();
		if ($posttable)
		{
			$result = $DB->query("SELECT p.pid, p.userid, p.dateline, t.forumid, t.tid, f.countposts, u.usergroupid
				FROM " . TABLE_PREFIX . "$posttable p
				LEFT JOIN " . TABLE_PREFIX . "thread t
					ON p.threadid = t.tid
				LEFT JOIN " . TABLE_PREFIX . "user u
					ON u.id = p.userid
				LEFT JOIN " . TABLE_PREFIX . "forum f
						ON t.forumid = f.id
				WHERE " . $DB->sql_in('p.pid', $pids));
			while ($row = $DB->fetch_array($result))
			{
				$posts[$row['pid']] = $row;
			}
		}
		else
		{
			foreach ($pids as $pid => $table)
			{
				$row = $DB->query_first("SELECT p.pid, p.userid, p.dateline, t.forumid, t.tid, f.countposts, u.usergroupid
				FROM " . TABLE_PREFIX . "$table p
					LEFT JOIN " . TABLE_PREFIX . "thread t
						ON p.threadid = t.tid
					LEFT JOIN " . TABLE_PREFIX . "user u
						ON u.id = p.userid
					LEFT JOIN " . TABLE_PREFIX . "forum f
						ON t.forumid = f.id
				WHERE p.pid = $pid");
				$posts[$row['pid']] = $row;
			}
		}
		foreach ($posts as $pid => $r)
		{
			if ($directdel)
			{
				$userids[] = $r['userid'];
				$groupids[] = $r['usergroupid'];
				$forumids[] = $r['forumid'];
			}
			$is_moderator = 0;
			if (!$bbuserinfo['supermod'] && $bbuserinfo['_moderator'][$r['forumid']])
			{
				if ($bbuserinfo['_moderator'][$r['forumid']]['forumid'] == $r['forumid'])
				{
					$is_moderator = 1;
				}
			}
			if (!$bbuserinfo['supermod'] && !$is_moderator)
			{
				$this->credit->check_credit('delreply', $r['usergroupid'], $r['forumid']);
			}
			if ($r['countposts'])
			{
				$user['posts'][$r['userid']]++;
			}
			$post[$pid] = $r['tid'];
			$thread[$r['tid']] = true;
			$forum['post'][$r['forumid']]++;
			$posttime = $forums->func->get_time($r['dateline'], 'd');
			if ($today['mday'] == $posttime)
			{
				$forum['todaypost'][$r['forumid']]++;
			}
		}
		if ($directdel)
		{
			$this->credit->update_credit('delreply', $userids, $groupids, $forumids);
		}
		$attachmentids = array();
		$result = $DB->query('SELECT attachmentid, postid, attachpath, location, thumblocation
			FROM ' . TABLE_PREFIX . 'attachment
			WHERE ' . $DB->sql_in('postid', $pids));
		$dir = $bboptions['uploadfolder'] . '/' . $attachment['attachpath'] . '/';
		while ($attachment = $DB->fetch_array($result))
		{
			if ($attachment['location'])
			{
				@unlink($dir . $attachment['location']);
			}
			if ($attachment['thumblocation'])
			{
				@unlink($dir . $attachment['thumblocation']);
			}
			$attachmentids[] = $attachment['attachmentid'];
			$attach_tid[$post[$attachment['postid']]] = $post[$attachment['postid']];
		}
		if (count($attachmentids))
		{
			$DB->delete(TABLE_PREFIX . 'attachment', $DB->sql_in('attachmentid', $attachmentids));
			require_once(ROOT_PATH . 'includes/functions_post.php');
			foreach($attach_tid as $apid => $tid)
			{
				functions_post::recount_attachment($tid);
			}
		}

		if ($posttable)
		{
			$DB->delete(TABLE_PREFIX . $posttable, $DB->sql_in('pid', $pids));
		}
		else
		{
			foreach ($pids as $pid => $table)
			{
				$DB->delete(TABLE_PREFIX . $table, "pid = $pid");
			}
		}

		if (!$recount)
		{
			$this->decrease_user($user);
			$this->rebuild_threads(array_keys($thread));
			$this->decrease_forum($forum);
			$ids = (is_array($pids)) ? implode(', ', array_keys($pids)) : intval($pids);
			$this->add_moderate_log('', '', $ids, '', $forums->lang['deletepost'] . " ($ids)");
		}
	}

	function rebuild_threads($tids)
	{
		$this->delete_thread = array();
		foreach($tids as $tid)
		{
			$this->rebuild_thread($tid, true);
		}
		if (!empty($this->delete_thread))
		{
			$this->thread_delete($this->delete_thread);
		}
	}

	function rebuild_thread($tid, $out_delete = false)
	{
		global $forums, $DB;

		$tid = intval($tid);
		$thisthread = $DB->query_first('SELECT posttable
			FROM ' . TABLE_PREFIX . "thread
			WHERE tid = $tid");
		$posttable = $thisthread['posttable'] ? $thisthread['posttable'] : 'post';
		$postcount = $DB->query_first('SELECT COUNT(*) as count
			FROM ' . TABLE_PREFIX . "$posttable
			WHERE threadid = $tid
				AND moderate != 1");
		$postcount = intval($postcount['count']) - 1;

		$lastpost = $DB->query_first('SELECT p.dateline, p.threadid, p.userid, p.username, p.pid, t.forumid, t.title
			FROM ' . TABLE_PREFIX . "$posttable p
				LEFT JOIN " . TABLE_PREFIX . "thread t
					ON p.threadid = t.tid
			WHERE threadid = $tid
				AND moderate != 1
			ORDER BY pid DESC
			LIMIT 0, 1");

		$first_post = $DB->query_first('SELECT dateline, userid, username, pid, newthread
			FROM ' . TABLE_PREFIX . "$posttable
			WHERE threadid = $tid
			ORDER BY pid ASC
			LIMIT 0, 1");

		if ($postcount == -1)
		{
			if ($out_delete)
			{
				$this->delete_thread[] = $tid;
			}
			else
			{
				$this->thread_delete($tid);
			}
		}
		else
		{
			$modpostcount = $DB->query_first('SELECT COUNT(*) as count
				FROM ' . TABLE_PREFIX . "$posttable
				WHERE threadid = $tid
					AND moderate = 1");
			$modpostcount = intval($modpostcount['count']);

			$attach = $DB->query_first('SELECT COUNT(*) as count
				FROM ' . TABLE_PREFIX . 'attachment a
					LEFT JOIN ' . TABLE_PREFIX . "$posttable p
						ON a.postid = p.pid
				WHERE p.threadid = $tid");
			$titletext = strip_tags($lastpost['title']);
			$titletext = implode(' ', duality_word($titletext));
			$sql_array = array(
				'lastpost' => $lastpost['dateline'],
				'lastposterid' => $lastpost['userid'],
				'lastposter' => $lastpost['username'],
				'modposts' => $modpostcount,
				'post' => $postcount,
				'postuserid' => $first_post['userid'],
				'postusername' => $first_post['username'],
				'dateline' => $first_post['dateline'],
				'firstpostid' => $first_post['pid'],
				'lastpostid' => $lastpost['pid'],
				'attach' => $attach['count'],
				'titletext' => $titletext,
			);
			$DB->update(TABLE_PREFIX . 'thread', $sql_array, "tid = $tid");
		}

		if ($first_post['newthread'] != 1 && $first_post['pid'])
		{
			$DB->update(TABLE_PREFIX . $posttable, array('newthread' => 1), "pid = {$first_post['pid']}");
		}
	}

	function thread_delete($ids, $fid=0)
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		if (empty($ids))
		{
			return false;
		}
		$directdel = false;
		//未开启回收站，直接删除主题，进行用户积分操作
		//if (!$bboptions['enablerecyclebin'] || $bboptions['recycleforumid'] <= 0 )
		{
			require_once(ROOT_PATH . "includes/functions_credit.php");
			$this->credit = new functions_credit();
			$userids = $groupids = $forumids = array();
			$directdel = true;
		}

		$thread = $post = $forum = $attach = $user = array();
		$today = getdate();
		$this->error = '';
		$result = $DB->query("SELECT t.tid, t.posttable, t.forumid, t.visible, t.firstpostid, t.dateline, t.postuserid, f.countposts, u.usergroupid
			FROM " . TABLE_PREFIX . "thread t
				LEFT JOIN " . TABLE_PREFIX . "user u
					ON u.id = t.postuserid
				LEFT JOIN " . TABLE_PREFIX . "forum f
					ON t.forumid = f.id
			WHERE " . $DB->sql_in('t.tid', $ids) . ' AND t.sticky > 0');
		$attach_post = $delposts = array();
		while ($r = $DB->fetch_array($result))
		{
			$currenttable = $r['posttable']?$r['posttable']:'post';
			$delposts[$r['tid']] = $currenttable;

			$posts = $DB->query_first("SELECT pid, moderate
			FROM " . TABLE_PREFIX . "$currenttable
			WHERE newthread = 1 AND threadid = {$r['tid']}");
			if ($directdel)
			{
				$userids[] = $r['postuserid'];
				$groupids[] = $r['usergroupid'];
				$forumids[] = $r['forumid'];
			}
			$is_moderator = 0;
			if (!$bbuserinfo['supermod'] && $bbuserinfo['_moderator'][$r['forumid']])
			{
				if ($bbuserinfo['_moderator'][$r['forumid']]['forumid'] == $r['forumid']) $is_moderator = 1;
			}
			if (!$bbuserinfo['supermod'] && !$is_moderator)
			{
				$this->credit->check_credit('delthread', $r['usergroupid'], $r['forumid']);
			}
			if ($r['countposts'])
			{
				$user['posts'][$r['postuserid']]++;
			}
			$forum['post'][$r['forumid']]++;
			if ($posts['moderate'])
			{
				$forum['unmodposts'][$r['forumid']]++;
			}
			$posttime = $forums->func->get_time($r['dateline'], 'd');
			if ($today['mday'] == $posttime)
			{
				$forum['todaypost'][$r['forumid']]++;
			}
			$attach_post[$r['posttable']][] = $posts['pid'];

			if (!$thread[$r['tid']])
			{
				if ($r['visible'])
				{
					$forum['thread'][$r['forumid']]++;
				}
				else
				{
					$forum['unmodthreads'][$r['forumid']]++;
				}
			}
			$thread[$r['tid']] = true;
		}
		if ($directdel)
		{
			$this->credit->update_credit('delthread', $userids, $groupids, $forumids);
		}
		$DB->delete(TABLE_PREFIX . 'poll', $DB->sql_in('tid', $ids));
		$DB->delete(TABLE_PREFIX . 'thread', $DB->sql_in('tid', $ids) . ' AND sticky = 0');
		foreach ($attach_post AS $tab => $post)
		{
			if (count($post))
			{
				$attachments = $DB->query('SELECT attachmentid, location, attachpath, thumblocation
					FROM ' . TABLE_PREFIX . 'attachment
					WHERE ' . $DB->sql_in('postid', $post) . " AND posttable = '{$tab}'");
				while ($attachment = $DB->fetch_array($attachments))
				{
					$dir = $bboptions['uploadfolder'] . '/' . $attachment['attachpath'] . '/';
					if ($attachment['location'])
					{
						@unlink($dir . $attachment['location']);
					}
					if ($attachment['thumblocation'])
					{
						@unlink($dir . $attachment['thumblocation']);
					}
					$attach[] = $attachment['attachmentid'];
				}
				if ($attach)
				{
					$DB->delete(TABLE_PREFIX . 'attachment', $DB->sql_in('attachmentid', $attach));
				}
			}
		}

		if (!empty($delposts))
		{
			foreach ($delposts as $tid => $table)
			{
				$DB->delete(TABLE_PREFIX . $table, "threadid=$tid");
			}
		}

		if (!$recount)
		{
			$this->decrease_user($user);
			$this->decrease_forum($forum);
		}
	}

	function thread_move($thread, $source_id, $dest_id, $leavelink=0)
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (empty($thread))
		{
			return false;
		}
		$this->error = '';
		$source_id = intval($source_id);
		$dest_id = intval($dest_id);
		$recycleforumid = 0;
		if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
		{
			$recycleforumid = intval($bboptions['recycleforumid']);
		}

		//若删除到回收站,则进行积分操作
		if ($recycleforumid && $recycleforumid == $dest_id)
		{
			if (!is_object($this->credit))
			{
				require_once(ROOT_PATH . "includes/functions_credit.php");
				$this->credit = new functions_credit();
			}
			$userids = $groupids = $forumids = array();
			$threads = $DB->query("SELECT t.forumid, u.id, u.usergroupid
						FROM " . TABLE_PREFIX . "thread t
					LEFT JOIN " . TABLE_PREFIX . "user u
						ON u.id = t.postuserid
					WHERE " . $DB->sql_in('t.tid', $thread) . ' AND t.sticky = 0');
			while ($r = $DB->fetch_array($threads))
			{
				$userids[] = $r['id'];
				$groupids[] = $r['usergroupid'];
				$forumids[] = $r['forumid'];
				if ($r['userid'] == $bbuserinfo['id'])
				{
					$this->credit->check_credit('delthread', $r['usergroupid'], $r['forumid']);
				}
			}
			if (!empty($userids) && !empty($groupids) && !empty($forumids))
			{
				$this->credit->update_credit('delthread', $userids, $groupids, $forumids);
			}
		}
		$where = $source_id ? "forumid = $source_id AND " : '';
		$where .= $DB->sql_in('tid', $thread);
		//主题移到回收站
		if ($recycleforumid && $recycleforumid == $dest_id)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET addtorecycle=".TIMENOW.", rawforumid=forumid WHERE $where AND sticky = 0");
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "poll SET addtorecycle=".TIMENOW.", rawforumid=forumid WHERE $where");
		}

		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET forumid=$dest_id WHERE $where AND sticky = 0");
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "poll SET forumid=$dest_id WHERE $where ");

		//移动主题，添加一条记录
		if ($leavelink != 0)
		{
			$result = $DB->query("SELECT title, forumid, description, postuserid, dateline, postusername, lastpost, tid, lastposterid, lastposter
				FROM " . TABLE_PREFIX . "thread
				WHERE " . $DB->sql_in('tid', $thread));
			while ($row = $DB->fetch_array($result))
			{
				$row['open'] = 2;
				$row['post'] = 0;
				$row['views'] = 0;
				$row['forumid'] = $source_id;
				$row['visible'] = 1;
				$row['sticky'] = 0;
				$row['moved'] = $row['tid'] . '&' . $dest_id;
				unset($row['tid']);
				$DB->insert(TABLE_PREFIX . 'thread', $row);
			}
		}
		//收藏主题
		$result = $DB->query('SELECT s.*, u.id, t.tid, t.forumid, g.usergroupid
			FROM ' . TABLE_PREFIX . 'subscribethread s
				LEFT JOIN ' . TABLE_PREFIX . 'thread t
					ON (s.threadid = t.tid)
				LEFT JOIN ' . TABLE_PREFIX . 'user u
					ON (u.id = s.userid)
				LEFT JOIN ' . TABLE_PREFIX . 'usergroup g
					ON (g.usergroupid = u.usergroupid)
			WHERE ' . $DB->sql_in('s.threadid', $thread));
		$threadid = array();
		while ($r = $DB->fetch_array($result))
		{
			$pass = false;
			$forum_perm_array = explode(',', $forums->forum->foruminfo[$r['forumid'] ]['canread']);
			if (in_array($r['usergroupid'], $forum_perm_array))
			{
				$pass = true;
			}
			if (!$pass)
			{
				$threadid[] = $r['subscribethreadid'];
			}
		}
		if (count($threadid) > 0)
		{
			$DB->delete(TABLE_PREFIX . 'subscribethread', $DB->sql_in('subscribethreadid', $threadid));
		}

		return true;
	}

	function thread_st($thread, $st_id)
	{
		global $forums, $DB;
		$this->error = '';
		$st_id = intval($st_id);
		if (empty($thread))
		{
			return false;
		}
		$DB->update(TABLE_PREFIX . 'thread', array('stopic' => $st_id), $DB->sql_in('tid', $thread));
		return true;
	}

	function thread_close($id)
	{
		$this->type[] = array('open' => 0);
		$this->do_run($id);
	}

	function thread_open($id)
	{
		$this->type[] = array('open' => 1);
		$this->do_run($id);
	}

	function thread_stick($id)
	{
		$this->type[] = array('sticky' => 1);
		$this->do_run($id);
	}

	function thread_unstick($id)
	{
		$this->type[] = array('sticky' => 0);
		$this->do_run($id);
	}

	function forum_recount($fid = '', $parent = true)
	{
		global $forums, $DB;
		static $recounted = array();
		$fid = intval($fid);
		if (!$fid && $this->forum['id'])
		{
			$fid = intval($this->forum['id']);
		}
		else if (!$fid)
		{
			return false;
		}
		if (isset($recounted[$fid]))
		{
			return true;
		}
		$recounted[$fid] = true;

		$forum = array();
		$forum = $forums->forum->single_forum($fid);
		if ($forum['parentid'] != '-1' && $parent)
		{
			$this->forum_recount($forum['parentid']);
		}

		$childlist = explode(',', $forum['childlist']);

		$this_thread = $DB->query_first('SELECT COUNT(*) AS count
			FROM ' . TABLE_PREFIX . "thread
			WHERE forumid = $fid
				AND visible = 1");
		$thread = $DB->query_first('SELECT COUNT(*) AS count, SUM(post) AS replies, SUM(modposts) AS modreplies, MAX(lastpostid) AS lastpostid
			FROM ' . TABLE_PREFIX . 'thread
			WHERE ' . $DB->sql_in('forumid', $childlist) . '
				AND visible = 1');
		$moderatethread = $DB->query_first('SELECT COUNT(*) AS count, SUM(modposts) AS modreplies
			FROM ' . TABLE_PREFIX . 'thread
			WHERE ' . $DB->sql_in('forumid', $childlist) . '
				AND visible = 0');

		$today = date('Y-n-j', TIMENOW);
		$day = explode('-', $today);
		$day_start = mktime(0, 0, 0, $day[1], $day[2], $day[0]);
		$todaypostcount = 0;

		$splittable = $deftable = array();
		$forums->func->check_cache('splittable');
		$splittable = $forums->cache['splittable']['all'];
		$deftable = $forums->cache['splittable']['default'];

		foreach ($splittable as $id => $row)
		{
			$tpost = $DB->query_first('SELECT COUNT(*) AS count
				FROM ' . TABLE_PREFIX . 'thread t
					LEFT JOIN ' . TABLE_PREFIX . "{$row['name']} p
						ON (p.threadid = t.tid)
				WHERE " . $DB->sql_in('t.forumid', $childlist) . "
					AND t.visible = 1
					AND p.dateline >= $day_start");
			$todaypostcount += $tpost['count'];
		}
		//计算lastpostid的主题id

		$tblname = $deftable['name'];
		$posttable = $tblname ? $tblname : 'post';
		unset($splittable[$deftable['id']]);

		$sql_array = array(
			'this_thread' => intval($this_thread['count']),
			'lastthreadid' => intval($this->get_forum_lastpid($forum)),
			'thread' => intval($thread['count']),
			'post' => intval($thread['replies'] + $thread['count']),
			'unmodthreads' => intval($moderatethread['count']),
			'unmodposts' => intval($thread['modreplies'] + $moderatethread['modreplies']),
			'todaypost' => intval($todaypostcount),
		);

		$DB->update(TABLE_PREFIX . 'forum', $sql_array, 'id = ' . $fid);
		return true;
	}

	function decrease_forum($forum)
	{
		global $forums, $DB;
		if (empty($forum['post']))
		{
			return false;
		}
		foreach ($forum['post'] as $fid => $v)
		{
			$row = $forums->forum->single_forum($fid);
			$parentlist = explode(',', $row['parentlist']);
			foreach ($parentlist as $v)
			{
				if ($v == '-1')
				{
					continue;
				}

				$count_post[$v] += $forum['post'][$row['id']];
				$count_today[$v] += $forum['todaypost'][$row['id']];
				if ($forum['thread'])
				{
					$count_thread[$v] += $forum['thread'][$row['id']];
				}
				if ($forum['unmodthreads'])
				{
					$count_unmodthreads[$v] += $forum['unmodthreads'][$row['id']];
				}
				if ($forum['unmodposts'])
				{
					$count_unmodposts[$v] += $forum['unmodposts'][$row['id']];
				}
				if (!isset($lastthreadid[$v]))
				{
					$lastthreadid[$v] = $this->get_forum_lastpid($row);
				}
			}
		}

		$sql_array = array(
			'post' => array($count_post, '-'),
			'todaypost' => array($count_today, '-'),
			'lastthreadid' => $lastthreadid,
		);

		if ($forum['thread'])
		{
			$sql_array['this_thread'] = array($forum['thread'], '-');
			$sql_array['thread'] = array($count_thread, '-');
		}
		if ($forum['unmodthreads'])
		{
			$sql_array['unmodthreads'] = array($count_unmodthreads, '-');
		}
		if ($forum['unmodposts'])
		{
			$sql_array['unmodposts'] = array($count_unmodposts, '-');
		}
		if ($sql_array)
		{
			$DB->update_case(TABLE_PREFIX . 'forum', 'id', $sql_array);
		}
	}

	function get_forum_lastpid($forum)
	{
		global $forums, $DB;
		static $lastthread = array();
		if (!isset($lastthread[$forum['id']]))
		{
			$thread = $DB->query_first('SELECT tid
				FROM ' . TABLE_PREFIX . 'thread
				WHERE ' . $DB->sql_in('forumid', $forum['childlist']) . '
					AND visible = 1
				ORDER BY lastpost DESC
				LIMIT 1');

			$lastthread[$forum['id']] = $thread['tid'];
		}

		return $lastthread[$forum['id']];
	}

	function decrease_user($user)
	{
		global $DB;
		$sql_array = array();
		if ($user['posts'])
		{
			$sql_array['posts'] = array($user['posts'], '-');
		}

		if ($sql_array)
		{
			$DB->update_case(TABLE_PREFIX . 'user', 'id', $sql_array);
		}
	}

	function do_run($id)
	{
		global $forums, $DB;
		if (count($this->type) < 1)
		{
			return false;
		}
		$sql_array = array();
		foreach($this->type as $idx => $array)
		{
			foreach($array as $k => $v)
			{
				$sql_array[$k] = $v;
			}
		}

		if ($id)
		{
			return $DB->update(TABLE_PREFIX . 'thread', $sql_array, $DB->sql_in('tid', $id));
		}
		else
		{
			return false;
		}
	}

	function add_moderate_log($fid, $tid, $pid, $title, $action = 'Unknown')
	{
		global $DB, $bbuserinfo;
		$log = array(
			'forumid' => intval($fid),
			'threadid' => intval($tid),
			'postid' => intval($pid),
			'userid' => $bbuserinfo['id'],
			'username' => $bbuserinfo['name'],
			'host' => IPADDRESS,
			'referer' => REFERRER,
			'dateline' => TIMENOW,
			'title' => $title,
			'action' => $action,
		);
		$DB->insert(TABLE_PREFIX . 'moderatorlog', $log);
	}

	function processcredit($ids, $action, $type = 'thread', $check = 0)
	{
		global $forums, $DB, $bbuserinfo;
		$userids = $groupids = $forumids = array();
		if (!is_object($this->credit))
		{
			require_once(ROOT_PATH . "includes/functions_credit.php");
			$this->credit = new functions_credit();
		}

		if (empty($ids)) return false;
		if (!$action) return false;
		$info = array();
		if ($type == 'post')
		{
			foreach ($ids as $table => $pids)
			{
				if ($pids)
				{
					$query = $DB->query("SELECT t.forumid, u.usergroupid, u.id
								FROM " . TABLE_PREFIX . "$table p
							LEFT JOIN " . TABLE_PREFIX . "thread t
								ON p.threadid = t.tid
							LEFT JOIN " . TABLE_PREFIX . 'user u
								ON u.id = p.userid
							WHERE p.pid IN ( ' . implode(',' , $pids) . ')');
					while ($row = $DB->fetch_array($query))
					{
						$info[] = $row;
					}
				}

			}
		}
		else
		{
			$result = $DB->query("SELECT t.forumid, u.usergroupid, u.id
								FROM " . TABLE_PREFIX . "thread t
							LEFT JOIN " . TABLE_PREFIX . "user u
								ON u.id = t.postuserid
							WHERE " . $DB->sql_in('t.tid', $ids));
			while ($r = $DB->fetch_array($result))
			{
				$info[] = $r;
			}
		}
		foreach ($info as $r)
		{
			$userids[] = $r['id'];
			$groupids[] = $r['usergroupid'];
			$forumids[] = $r['forumid'];
			if ($check && $r['id'] == $bbuserinfo['id'])
			{
				$this->credit->check_credit($action, $r['usergroupid'], $r['forumid']);
			}
		}
		if (!empty($userids) && !empty($groupids) && !empty($forumids))
		{
			$this->credit->update_credit($action, $userids, $groupids, $forumids);
		}

		return true;
	}
}
?>