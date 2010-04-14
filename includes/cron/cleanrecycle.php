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
class cron_cleanrecycle
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;

		$forums->func->load_lang('cron');
		if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
		{
			$recycleforumid = intval($bboptions['recycleforumid']);
		}
		if (!$recycleforumid) return false;
		$dotids = array();
		$date = TIMENOW - 777600;
		//清除过期的主题和主题下的帖子
		$t_result = $DB->query("SELECT tid, posttable
				FROM " . TABLE_PREFIX . "thread
			WHERE forumid = $recycleforumid AND addtorecycle <= $date");
		$all_tids = array();
		while($row = $DB->fetch_array($t_result))
		{
			$posttable = $row['posttable']?$row['posttable']:'post';
			$dotids[$posttable][] = $row['tid'];
			$all_tids = $row['tid'];
		}
		if (!empty($dotids))
		{
			require(ROOT_PATH . 'includes/functions_moderate.php');
			$modfunc = new modfunctions();
			$thread = $post = $forum = $attach = $user = array();
			$today = getdate();
			$posts = array();
			foreach ($dotids as $posttable => $tids)
			{
				$result = $DB->query("SELECT t.tid, t.forumid, t.visible, p.pid, p.userid, p.dateline, p.moderate, f.countposts
				FROM " . TABLE_PREFIX . "thread t
					LEFT JOIN " . TABLE_PREFIX . "$posttable p
						ON t.tid = p.threadid
					LEFT JOIN " . TABLE_PREFIX . "forum f
						ON t.forumid = f.id
				WHERE " . $DB->sql_in('t.tid', $tids));
				while ($r = $DB->fetch_array($result))
				{
					$posts[$r['tid']] = $r;
				}
			}

			foreach ($posts as $tid => $r)
			{
				if ($r['countposts'])
				{
					$user['posts'][$r['userid']]++;
				}
				$forum['post'][$r['forumid']]++;
				if ($r['moderate'])
				{
					$forum['unmodposts'][$r['forumid']]++;
				}
				$posttime = $forums->func->get_time($r['dateline'], 'd');
				if ($today['mday'] == $posttime)
				{
					$forum['todaypost'][$r['forumid']]++;
				}
				$post[] = $r['pid'];
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
			$DB->delete(TABLE_PREFIX . 'poll', $DB->sql_in('tid', $all_tids));
			$DB->delete(TABLE_PREFIX . 'thread', $DB->sql_in('tid', $all_tids));
			if (count($post))
			{
				$attachments = $DB->query('SELECT attachmentid, location, attachpath, thumblocation
					FROM ' . TABLE_PREFIX . 'attachment
					WHERE ' . $DB->sql_in('postid', $post));
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
			foreach ($dotids as $posttable => $tids)
			{
				$DB->delete(TABLE_PREFIX . $posttable, $DB->sql_in('threadid', $tids));
			}
			$modfunc->decrease_user($user);
			$modfunc->decrease_forum($forum);
			$modfunc->forum_recount($recycleforumid);
			$this->class->cronlog($this->cron, $forums->lang['cleanrecycle']);
		}
	}

	function register_class(&$class)
	{
		$this->class = &$class;
	}

	function pass_cron($this_cron)
	{
		$this->cron = $this_cron;
	}
}

?>