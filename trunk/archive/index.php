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
define('IN_MXB', true);
define('ROOT_PATH' , './../');
define('THIS_SCRIPT', 'archive');
require_once(ROOT_PATH . 'includes/init.php');
header("Content-Type:text/html; charset=UTF-8");

require_once(ROOT_PATH . 'includes/functions.php');
$forums->func = new functions();
require_once(ROOT_PATH . 'includes/sessions.php');
require_once(ROOT_PATH . 'includes/functions_forum.php');
$forums->forum = new functions_forum();
$_INPUT = init_input();
$forums->url = REFERRER;
$forums->func->check_cache('settings');
$bboptions = $forums->cache['settings'];
$forums->func->check_cache('style');
$forums->func->check_lang();
$forums->func->load_lang('global');
$forums->func->load_lang('archive');

$session = new session();
$bbuserinfo = $session->loadsession();
$forums->func->load_style();
$forums->forum->forums_init();
list($maxthreads, $maxposts) = explode("&", $bbuserinfo['viewprefs']);
$bboptions['maxthreads'] = 50;
$bboptions['maxposts'] = 20;

class archive
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions, $script;
		if (!$bbuserinfo['canview'])
		{
			$forums->func->standard_redirect($bboptions['bburl']);
		}
		if (! $bboptions['bbactive'])
		{
			if (!$bbuserinfo['canviewoffline'])
			{
				$forums->func->standard_redirect($bboptions['bburl']);
			}
		}
		if (!$bbuserinfo['id'] && $bboptions['forcelogin'])
		{
			$forums->func->standard_redirect($bboptions['bburl']);
		}
		if (IS_WIN || strstr(php_sapi_name(), 'cgi') || php_sapi_name() == 'apache2filter')
		{
			$this->server = 'WIN';
		}
		else
		{
			$this->server = 'NIX';
		}
		if ($this->server == 'WIN')
		{
			$this->filepath = $bboptions['bburl'] . '/archive/index.php?';
			$script = $_SERVER['QUERY_STRING'];
			$this->path = 'index.php?';
		}
		else
		{
			if (strpos($script, '/archive/') === false)
			{
				$forums->func->standard_redirect($bboptions['bburl'] . "/archive/");
			}
			$this->filepath = $bboptions['bburl'] . '/archive/index.php/';
			$this->path = 'index.php';
			if (strstr($script, '/'))
			{
				$script = str_replace('/', '', strrchr($script, '/'));
			}
		}
		$script = str_replace('.html', '', $script);
		$action = 'index';
		$id = 0;
		$pp = 0;
		if (strstr($script, '-'))
		{
			list($script, $pp) = explode('-', $script);
		}
		$pp = intval($pp);

		if (preg_match("#f\d#", $script))
		{
			$action = 'forum';
			$id = intval(preg_replace("#f(\d+)#", "\\1", $script));
		}
		else if (preg_match("#t\d#", $script))
		{
			$action = 'thread';
			$id = intval(preg_replace("#t(\d+)#", "\\1", $script));
		}

		switch ($action)
		{
			case 'forum':
				$this->get_forum_page($id, $pp);
				break;
			case 'thread':
				$this->get_thread_page($id, $pp);
				break;
			default:
				$this->get_index_page();
				break;
		}
	}

	function parse_hrperlink($script = '', $html = '', $root = './')
	{
		if (preg_match("#^(http|https|ftp|ed2k|news)://#", $html))
		{
			return $script . "='" . $html . "'";
		}
		return $script . "='" . $root . $html . "'";
	}

	function get_index_page()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		foreach($forums->forum->forum_cache['root'] as $id => $forum_data)
		{
			if (is_array($forums->forum->forum_cache[ $forum_data['id'] ]) and count($forums->forum->forum_cache[ $forum_data['id'] ]))
			{
				$forumlist .= "<li><strong><a href='{$this->filepath}f{$forum_data['id']}.html' class='largetext'>{$forum_data['name']}</a></strong></li>\n<ul>\n";
				$depth_guide = "";
				if (is_array($forums->forum->forum_cache[ $forum_data['id'] ]))
				{
					foreach($forums->forum->forum_cache[ $forum_data['id'] ] as $id => $forum_data)
					{
						$forumlist .= "{$depth_guide}<li><a href='{$this->filepath}f{$forum_data['id']}.html' class='content'>{$forum_data['name']}</a> <span class='desc'>(" . intval($forum_data['post']) . " " . $forums->lang['post'] . ")</span></li>\n";
						$forumlist = $this->get_forums_internal($forum_data['id'], $forumlist, "   " . $depth_guide);
					}
				}
				$forumlist .= "{$depth_guide}</ul></li>\n";
			}
		}
		$reallink = $bboptions['bburl'] . '/' . $bboptions['forumindex'];
		$pagetitle = $bboptions['bbtitle'] . ' - ' . $forums->lang['archive'];
		$full_version = $bboptions['bbtitle'];
		include $forums->func->load_template('archive_index');
		exit;
	}

	function get_forums_internal($root_id, $forumlist = "", $depth_guide = "")
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (is_array($forums->forum->forum_cache[ $root_id ]))
		{
			$forumlist .= "{$depth_guide}<ul>\n";
			foreach($forums->forum->forum_cache[ $root_id ] as $id => $forum_data)
			{
				$forumlist .= "{$depth_guide}<li><a href='{$this->filepath}f{$forum_data['id']}.html' class='content'>{$forum_data['name']}</a> <span class='desc'>(" . intval($forum_data['post']) . " " . $forums->lang['_posts'] . ")</span></li>\n";
				$forumlist = $this->get_forums_internal($forum_data['id'], $forumlist, "    " . $depth_guide);
			}
			$forumlist .= "{$depth_guide}</ul>\n";
		}
		return $forumlist;
	}

	function get_forum_page($id, $pp)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->forum = $forums->forum->single_forum($id);
		if ((($forums->func->fetch_permissions($this->forum['canread'], 'canread') != true) and (! $this->forum['showthreadlist'])) OR ($this->forum['password'] != ''))
		{
			$forums->func->standard_redirect($bboptions['bburl'] . "/archive/" . $this->path);
		}
		$nav = $this->forums_nav($id);
		$pagetitle = strip_tags($this->forum['name']) . " - " . $forums->lang['archive'];
		$full_version = $this->forum['name'];
		if (! $this->forum['allowposting'])
		{
			if (is_array($forums->forum->forum_cache[ $id ]) and count($forums->forum->forum_cache[ $id ]))
			{
				$forumlist .= "\n<li><strong><a href='{$this->filepath}f{$forums->forum->foruminfo[ $id ]['id']}.html' class='largetext'>{$forums->forum->foruminfo[ $id ]['name']}</a></strong></li>\n<ul>";
				$depth_guide = '';
				foreach($forums->forum->forum_cache[ $id ] as $cid => $forum_data)
				{
					$forumlist .= "{$depth_guide}<li><a href='{$this->filepath}f{$forum_data['id']}.html' class='content'>{$forum_data['name']}</a> <span class='desc'>({$forum_data['post']} " . $forums->lang['_posts'] . ")</span></li>";
					$forumlist = $this->get_forums_internal($forum_data['id'], $forumlist, "   " . $depth_guide);
				}
				$forumlist .= "{$depth_guide}</ul></li>";
			}
			$reallink = $bboptions['bburl'] . "/forumdisplay.php?f=" . $id;
			include $forums->func->load_template('archive_index');
			exit;
		}
		else
		{
			$pagelink = $this->get_pages($this->forum['thread'], $bboptions['maxthreads'], 'f' . $id);
			if (!$bbuserinfo['canviewothers'])
			{
				$query = "and postuserid=" . $bbuserinfo['id'];
			}
			$threads = $DB->query("SELECT * FROM " . TABLE_PREFIX . "thread WHERE visible=1 and forumid=$id $query ORDER BY sticky desc, lastpost desc LIMIT {$pp}, {$bboptions['maxthreads']}");
			while ($thread = $DB->fetch_array($threads))
			{
				if ($thread['sticky'])
				{
					$thread['prefix'] = $bboptions['stickyprefix'];
				}
				else
				{
					$thread['prefix'] = '';
				}
				if ($thread['open'] == 2)
				{
					$t_array = explode("&", $thread['moved']);
					$thread['tid'] = $t_array[0];
					$thread['forumid'] = $t_array[1];
					$thread['title'] = $thread['title'];
					$thread['post'] = '--';
					$thread['prefix'] = $bboptions['movedprefix'] . " ";
				}
				$threadlist .= "\n<li>{$thread['prefix']}<a href='{$this->filepath}t{$thread['tid']}.html'>{$thread['title']}</a> <span class='desc'>(" . $forums->lang['_re'] . ": {$thread['post']})</span></li>";
			}
			$reallink = $bboptions['bburl'] . "/forumdisplay.php?f=" . $id;
			include $forums->func->load_template('archive_threadlist');
			exit;
		}
	}

	function get_thread_page($id, $pp)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('showthread');
		require_once(ROOT_PATH . "includes/class_textparse.php");
		require_once (ROOT_PATH . "includes/functions_showthread.php");
		$this->lib = new functions_showthread();
		$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE visible=1 and tid=$id");
		$this->forum = $forums->forum->single_forum($thread['forumid']);
		if (!$thread['tid'] || !$this->forum['id'] || $this->check_permissions($thread['forumid'], 0, 'thread'))
		{
			$forums->func->standard_redirect($bboptions['bburl'] . "/archive/" . $this->path);
		}
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
		$posts = $DB->query("SELECT * FROM " . TABLE_PREFIX . "post WHERE threadid={$id}{$moderate} ORDER BY pid LIMIT {$pp}, {$bboptions['maxposts']}");
		if ($this->server == 'WIN')
		{
			$root = './../';
		}
		else
		{
			$root = './../../';
		}
		$forums->func->check_cache('usergroup');
		while ($post = $DB->fetch_array($posts))
		{
			$post['dateline'] = $forums->func->get_date($post['dateline'], 2);
			if ($post['anonymous'])
			{
				if ($bbuserinfo['usergroupid'] == 4)
				{
					$post['username'] = $post['username'] . " (" . $forums->lang['anonymouspost'] . ")";
				}
				else
				{
					$post['username'] = $forums->lang['anonymous'] . '-' . rand(100000, 999999);
					$post['id'] = 0;
				}
			}
			if ($post['hidepost'])
			{
				$post['pagetext'] = $forums->lang['_posthidden'];
			}
			else
			{
				$post['pagetext'] = preg_replace('#<img[^>]+smilietext=(\'|"|\\\")(.*)(\\1).*>#siU', " \\2 ", $post['pagetext']);
				$post['pagetext'] = nl2br($post['pagetext']);
				$post['pagetext'] = preg_replace('/(action|href|src|background)=(\'|"|)(\.\/|)(.+?)(\\2)/ie', "\$this->parse_hrperlink('\\1', '\\4', '$root')", $post['pagetext']);
				$post['pagetext'] = preg_replace("/<!--emule1-->(.+?)<!--emule2-->/ie", "\$this->lib->paste_emule('\\1')", $post['pagetext']);
				$post['pagetext'] = textparse::convert_text($post['pagetext'], ($this->forum['allowhtml'] && $forums->cache['usergroup'][$poster['usergroupid']]['canposthtml']));
			}
			$allpost[] = $post;
		}
		$nav = $this->forums_nav($thread['forumid']);
		$pagelink = $this->get_pages($thread['post'], $bboptions['maxposts'], 't' . $id);
		$pagetitle = strip_tags($thread['title']) . " - " . $forums->lang['archive'];
		$full_version = $thread['title'];
		$reallink = $bboptions['bburl'] . "/showthread.php?t=" . $id;
		include $forums->func->load_template('archive_showthread');
		exit;
	}

	function get_pages($total, $pp, $id)
	{
		$page_array = array();
		$pages = ceil($total / $pp);
		$pages = $pages ? $pages : 1;
		if ($pages < 2)
		{
			return "";
		}
		if ($pages > 1)
		{
			for($i = 0, $n = $pages - 1; $i <= $n; ++$i)
			{
				$RealNo = $i * $pp;
				$PageNo = $i + 1;
				$page_array[] = "<a href='{$this->filepath}{$id}-{$RealNo}.html'>{$PageNo}</a>";
			}
		}
		return implode(", ", $page_array);
	}

	function forums_nav($id)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$ids = explode(',', $forums->forum->foruminfo[$id]['parentlist']);
		if (is_array($ids) and count($ids))
		{
			foreach($ids as $id)
			{
				if ($id == -1) continue;
				$data = $forums->forum->foruminfo[$id];
				$nav[] = "<a href='{$this->filepath}f{$data['id']}.html'>{$data['name']}</a>";
			}
		}
		return array_reverse($nav);
	}

	function check_permissions($fid, $prompt_login = 0, $in = 'forum')
	{
		global $forums;
		$deny_access = true;
		if ($this->forum && $forums->func->fetch_permissions($this->forum['canshow'], 'canshow') == true)
		{
			if ($forums->func->fetch_permissions($this->forum['canread'], 'canread') == true)
			{
				$deny_access = false;
			}
			else
			{
				if ($this->forum['showthreadlist'])
				{
					if ($in == 'forum')
					{
						$deny_access = false;
					}
					else
					{
						$deny_access = true;
					}
				}
				else
				{
					$deny_access = true;
				}
			}
		}
		else
		{
			$deny_access = true;
		}
		if (!$deny_access)
		{
			if ($this->forum['password'])
			{
				if ($forums->forum->check_password($fid) == true)
				{
					$deny_access = false;
				}
				else
				{
					$deny_access = true;
				}
			}
		}
		return $deny_access;
	}

	function can_moderate($fid = 0)
	{
		global $bbuserinfo;
		$return = 0;
		if ($bbuserinfo['supermod'] OR ($fid AND $bbuserinfo['is_mod'] AND $bbuserinfo['_moderator'][ $fid ]['canmoderateposts']))
		{
			$return = 1;
		}
		return $return;
	}
}
$output = new archive();
$output->show();

?>