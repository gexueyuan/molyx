<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# copyright (c) 2004-2006 HOGE Software.
# official forum : http://molyx.com
# license : MolyX License, http://molyx.com/license
# MolyX2 is free software. You can redistribute this file and/or modify
# it under the terms of MolyX License. If you do not accept the Terms
# and Conditions stated in MolyX License, please do not redistribute
# this file.Please visit http://molyx.com/license periodically to review
# the Terms and Conditions, or contact HOGE Software.
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'thread');
require_once('./global.php');

class showthread
{
	var $posthash = '';
	var $thread = array();
	var $forum = array();
	var $page = 0;
	var $maxposts = 10;
	var $moderator = array();
	var $cached_users = array();
	var $postcount = 0;
	var $already_replied = 0;
	var $canview_hideattach = 0;
	var $canview_hidecontent = 0;
	var $pollslen = 0;
	var $ispost = false;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->posthash = $forums->func->md5_check();
		$this->offset = intval($_INPUT['offset']);
		$this->tid = intval($_INPUT['t']);
		$this->extra = $_INPUT['extra'] ? "&amp;pp=" . intval($_INPUT['extra']) : "";
		$this->pp = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		if ($this->tid < 1)
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['errorthreadlink']);
			include $forums->func->load_template('wap_info');
			exit;
		}

		$this->thread = $DB->query_first("SELECT t.*, p.anonymous, p.pagetext FROM " . TABLE_PREFIX . "thread t LEFT JOIN " . TABLE_PREFIX . "post p ON (p.newthread =1 AND p.threadid = t.tid) WHERE tid='" . $this->tid . "'");
		$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		if (!$this->forum['id'] OR !$this->thread['tid'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['erroraddress']);
			include $forums->func->load_template('wap_info');
			exit;
		}

		if (! $this->can_moderate($this->forum['id']))
		{
			if ($this->thread['visible'] != 1)
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['errorthreadlink']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		require_once(ROOT_PATH . "wap/convert.php");
		$this->lib = new convert();

		if ($this->thread['open'] == 2)
		{
			$f_stuff = explode("&", $this->thread['moved']);
			redirect(ROOT_PATH . "thread.php{$forums->sessionurl}t={$f_stuff[0]}");
		}

		check_password($this->forum['id'], 1, 'thread');

		if (! $bbuserinfo['canviewothers'] AND $this->thread['postuserid'] != $bbuserinfo['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotviewthread']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		switch ($_INPUT['do'])
		{
			case 'reply':
				$this->post();
				break;
			case 'showpost':
				$this->showpost();
				break;
			default:
				$this->thread();
				break;
		}
	}

	function showpost()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$thread_title = convert(strip_tags($this->thread['title']));
		$threadtitle = "<a href='thread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}'>{$thread_title}</a>";

		$postlink = $this->postlink();

		$moderate = ' AND moderate=0';
		if ($this->can_moderate($this->thread['forumid']))
		{
			$moderate = '';
			if ($_INPUT['modfilter'] == 'invisiblepost')
			{
				$moderate = ' AND moderate=1';
			}
		}

		$post = $DB->query_first("SELECT *, userid AS postuserid, username AS postusername FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND pid='" . intval($_INPUT['p']) . "'" . $moderate);

		if (!$post['pid'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['errorthreadlink']);
			include $forums->func->load_template('wap_info');
			exit;
		}

		$post = $this->parse_row($post);

		$showpost = "<p>\r\n{$post['row']['pagetext']}<br />\r\n";
		$showpost .= "<small>{$post['poster']['postusername']}</small><br />\r\n";
		$showpost .= "<small>{$post['row']['dateline']}</small>\r\n";

		if ($this->purllink)
		{
			$showpost .= "<br /><a href='" . $this->purllink . "'>" . convert($forums->lang['nextlink']) . "</a>\r\n";
		}
		$showpost .= "</p>\r\n";

		$post = $DB->query_first("SELECT COUNT(pid) AS count FROM " . TABLE_PREFIX . "post WHERE newthread !=1 AND threadid = " . $this->thread['tid']);

		$tlink = "<p><small>\r\n";
		$tlink .= "<a href='thread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}'>" . convert($forums->lang['returnthread']) . "</a><br />\r\n";
		$tlink .= "<a href='thread.php{$forums->sessionurl}do=reply&amp;t={$this->thread['tid']}&amp;pp={$this->pp}&amp;extra={$_INPUT['extra']}'>" . convert($forums->lang['returncomment']) . "</a>\r\n";
		$tlink .= "</small></p>\r\n";

		$otherlink = $this->otherlink();

		include $forums->func->load_template('wap_showthread');
		exit;
	}

	function post()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->ispost = true;
		$thread_title = convert(strip_tags($this->thread['title']));
		$threadtitle = "<a href='thread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}'>{$thread_title}</a>";

		$postlink = $this->postlink();

		$moderate = ' AND moderate=0';
		if ($this->can_moderate($this->thread['forumid']))
		{
			$moderate = '';
			if ($_INPUT['modfilter'] == 'invisiblepost')
			{
				$moderate = ' AND moderate=1';
			}
		}

		$posts = $DB->query("SELECT p.*, p.userid AS postuserid,p.username AS postusername
									FROM " . TABLE_PREFIX . "post p
									WHERE threadid='" . $this->thread['tid'] . "' AND newthread != 1" . $moderate . " ORDER BY dateline LIMIT " . $this->pp . ", 10");
		if ($DB->num_rows($posts))
		{
			while ($post = $DB->fetch_array($posts))
			{
				++$i;
				$this->offset = 0;
				$post = $this->parse_row($post, 40);
				$postcount = $post['row']['postcount'];
				$allpostrows[] = $post;
			}
			foreach ($allpostrows AS $post)
			{
				$showpost .= "<p># {$post['row']['postcount']}<br />{$post['row']['pagetext']}<br />\r\n";
				$showpost .= "<small>{$post['poster']['postusername']}</small><br />\r\n";
				$showpost .= "<small>{$post['row']['dateline']}</small></p>\r\n";
			}
			unset($post);
		}
		else
		{
			$showpost = "<p>" . convert($forums->lang['nonewpost']) . "</p>\r\n";
		}

		$pcount = $DB->query_first("SELECT COUNT(pid) AS count
									FROM " . TABLE_PREFIX . "post p
									WHERE threadid='" . $this->thread['tid'] . "' AND newthread != 1" . $moderate . "");

		if ($pcount > 10)
		{
			$nextpage = "<p><small>\r\n";
			$nextpage .= $this->build_pagelinks(array('totalpages' => ($pcount['count'] + 1),
					'perpage' => 10,
					'curpage' => $this->pp,
					'pagelink' => "thread.php{$forums->sessionurl}do=reply&amp;t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}",
					)
				);
			$nextpage .= "</small></p>\r\n";
		}
		else
		{
			$nextpage = "";
		}

		$otherlink = $this->otherlink();

		include $forums->func->load_template('wap_showthread');
		exit;
	}

	function thread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$thread_title = $threadtitle = convert(strip_tags($this->thread['title']));

		if ($bboptions['threadviewsdelay'])
		{
			file_write(ROOT_PATH . 'cache/cache/threadviews.txt', $tid . "\n", 'a');
		}
		else
		{
			$DB->shutdown_update(TABLE_PREFIX . 'thread', array('views' => array(1, '+')), 'tid = ' . $this->thread['tid']);
		}

		if ($this->thread['pollstate'])
		{
			$show['poll'] = true;
			$poll_footer = "";
			$poll_data = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "poll WHERE tid='" . $this->thread['tid'] . "'");
			if (! $poll_data['pollid'])
			{
				return;
			}
			if (! $poll_data['question'])
			{
				$poll_data['question'] = $this->thread['title'];
			}

			$polloptions = unserialize($poll_data['options']);
			reset($polloptions);
			$votetotal = 0;

			$showpoll = convert($forums->lang['showpoll']);
			$langvotes = convert($forums->lang['vote']);
			foreach ($polloptions AS $entry)
			{
				$entry['id'] = intval($entry[0]);
				$entry['choice'] = $entry[1];
				$entry['votes'] = intval($entry[2]);
				$votelen = strlen($entry['choice']);
				if ($votelen < 1) continue;
				$this->pollslen += $votelen;
				$showpoll .= "<br />" . convert($entry['choice']) . "(" . $entry['votes'] . "$langvotes)";
			}
		}

		$postlink = $this->postlink();

		$post = $this->parse_row($this->thread);

		$showpost = "<p>\r\n{$post['row']['pagetext']}<br />\r\n";
		$showpost .= "<small>{$post['poster']['postusername']}</small><br />\r\n";
		$showpost .= "<small>{$post['row']['dateline']}</small>\r\n";

		if ($this->turllink)
		{
			$showpost .= "<br /><a href='" . $this->turllink . "'>" . convert($forums->lang['nextlink']) . "</a>\r\n";
		}
		$showpost .= "</p>\r\n";

		$post = $DB->query_first("SELECT COUNT(pid) AS count FROM " . TABLE_PREFIX . "post WHERE newthread !=1 AND threadid = " . $this->thread['tid']);

		$tlink = "<p><small>";

		if ($this->thread['attach'])
		{
			$tlink .= "<a href='attach.php{$forums->sessionurl}do=view&amp;tid={$this->thread['tid']}&amp;extra={$_INPUT['extra']}'>" . convert($forums->lang['viewattach']) . "</a><br />";
		}

		if ($post['count'])
		{
			$allreply = sprintf($forums->lang['allreply'], $post['count']);
			$tlink .= "<a href='thread.php{$forums->sessionurl}do=reply&amp;t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}'>" . convert($allreply) . "</a>";
		}
		else
		{
			$tlink .= convert($forums->lang['noreply']);
		}
		$tlink .= "</small></p>";

		$otherlink = $this->otherlink();

		include $forums->func->load_template('wap_showthread');
		exit;
	}

	function otherlink()
	{
		global $forums, $_INPUT, $DB, $bbuserinfo;
		$otherlink = "<p>";
		$otherlink .= "{$forums->lang['forum']}: <a href='forum.php{$forums->sessionurl}f={$this->forum['id']}{$this->extra}' title='{$forums->lang['go']}'>" . strip_tags($this->forum['name']) . "</a><br />";
		$otherlink .= "{$forums->lang['thread']}: <a href='thread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;extra={$_INPUT['extra']}' title='{$forums->lang['go']}'>" . strip_tags($this->thread['title']) . "</a><br />";
		if ($prevthread = $DB->query_first("SELECT tid, title FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost < '" . $this->thread['lastpost'] . "' ORDER BY lastpost DESC LIMIT 0, 1"))
		{
			$otherlink .= "{$forums->lang['prevthread']}: <a href='thread.php{$forums->sessionurl}t={$prevthread['tid']}&amp;extra={$_INPUT['extra']}' title='{$forums->lang['go']}'>" . strip_tags($prevthread['title']) . "</a><br />";
		}
		if ($nextthread = $DB->query_first("SELECT tid, title FROM " . TABLE_PREFIX . "thread WHERE forumid='" . $this->forum['id'] . "' AND visible=1 AND open != 2 AND lastpost > '" . $this->thread['lastpost'] . "' ORDER BY lastpost LIMIT 0, 1"))
		{
			$otherlink .= "{$forums->lang['nextthread']}: <a href='thread.php{$forums->sessionurl}t={$nextthread['tid']}&amp;extra={$_INPUT['extra']}' title='{$forums->lang['go']}'>" . strip_tags($nextthread['title']) . "</a><br />";
		}
		$otherlink .= "</p>\n";
		return convert($otherlink);
	}

	function postlink()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$postlink = "<p>";
		if (!$bbuserinfo['id'])
		{
			$postlink .= "<a href='login.php{$forums->sessionurl}' title='{$forums->lang['login']}'>" . convert($forums->lang['login']) . "</a>  ";
			$postlink .= "<a href='register.php{$forums->sessionurl}' title='{$forums->lang['registeraccount']}'>" . convert($forums->lang['registeraccount']) . "</a>";
		}
		else
		{
			$reffer = urlencode("forum.php{$forums->sessionurl}f={$this->forum['id']}{$this->extra}");
			$postlink .= "<a href='post.php{$forums->sessionurl}f={$this->forum['id']}&amp;reffer={$reffer}' title='{$forums->lang['postthread']}'>" . convert($forums->lang['postthread']) . "</a>  ";
			$postlink .= "<a href='post.php{$forums->sessionurl}f={$this->forum['id']}&amp;t={$this->thread['tid']}&amp;reffer={$reffer}' title='{$forums->lang['replythread']}'>" . convert($forums->lang['replythread']) . "</a>\n";
		}
		$postlink .= "</p>";
		return $postlink;
	}

	function parse_row($row = array(), $len = 400)
	{
		global $forums, $_INPUT, $bbuserinfo;
		$poster = array();
		if ($row['postuserid'])
		{
			if (isset($this->cached_users[ $row['userid'] ]))
			{
				$poster['postusername'] = $this->cached_users[ $row['userid'] ];
			}
			else
			{
				$poster['postusername'] = convert($row['postusername']);
				$this->cached_users[ $row['userid'] ] = $poster['postusername'];
			}
		}
		else
		{
			$poster['postusername'] = convert($forums->lang['guest']);
		}
		if ($row['anonymous'])
		{
			if ($bbuserinfo['usergroupid'] != 4)
			{
				$poster = array();
				$poster['name'] = convert($forums->lang['anonymous']);
			}
		}

		$row['dateline'] = $forums->func->get_date($row['dateline'], 2);

		$this->parser->show_html = 0;

		if ($row['hidepost'])
		{
			$row['pagetext'] = $forums->lang['hidepost'];
		}

		$this->postcount++;
		$row['postcount'] = $this->pp + $this->postcount;

		$leftlen = $len - $this->pollslen;

		$row['pagetext'] = $this->lib->convert_text($row['pagetext']);
		$row['pagetext'] = $this->lib->fetch_trimmed_title($row['pagetext'], $leftlen, $this->offset);

		if ($this->lib->more)
		{
			$offset = $this->offset + $this->lib->post_set;
			$pp = $row['postcount'] -1;
			$this->turllink = "thread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;offset={$offset}&amp;extra={$_INPUT['extra']}";
			$this->purllink = "thread.php{$forums->sessionurl}do=showpost&amp;t={$this->thread['tid']}&amp;p={$row['pid']}&amp;offset={$offset}&amp;extra={$_INPUT['extra']}";
			if ($this->ispost)
			{
				$row['pagetext'] .= "[<a href='thread.php{$forums->sessionurl}do=showpost&amp;t={$this->thread['tid']}&amp;p={$row['pid']}&amp;pp={$this->pp}&amp;extra={$_INPUT['extra']}'>" . $forums->lang['viewall'] . "</a>]";
			}
		}
		$row['pagetext'] = convert($row['pagetext']);
		return array('row' => $row, 'poster' => $poster);
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

	function build_pagelinks($data)
	{
		global $forums;
		$results['pages'] = ceil($data['totalpages'] / $data['perpage']);
		$results['total_page'] = $results['pages'] ? $results['pages'] : 1;
		$results['current_page'] = $data['curpage'] > 0 ? intval($data['curpage'] / $data['perpage']) + 1 : 1;
		$prevlink = "";
		$nextlink = "";
		if ($results['total_page'] <= 1)
		{
			return '';
		}
		else
		{
			if ($results['current_page'] > 1)
			{
				$start = $data['curpage'] - $data['perpage'];
				$prevlink = " <a href='{$data['pagelink']}&amp;pp=$start' title='" . $forums->lang['_prevpage'] . "'>&lt;</a> \r\n";
			}
			if ($results['current_page'] < $results['total_page'])
			{
				$start = $data['curpage'] + $data['perpage'];
				$nextlink = " <a href='{$data['pagelink']}&amp;pp=$start' title=''>&gt;</a> \r\n";
			}
			$pagenav = "Total: {$results['total_page']} ";
			$minpage = $results['current_page'] - 6;
			$maxpage = $results['current_page'] + 5;
			$minpage = $minpage < 0 ? 0 : $minpage;
			$maxpage = $maxpage > $results['total_page'] ? $results['total_page'] : $maxpage;
			for($i = $minpage; $i < $maxpage; ++$i)
			{
				$numberid = $i * $data['perpage'];
				$pagenumber = $i + 1;
				if ($numberid == $data['curpage'])
				{
					$curpage .= " {$pagenumber}";
				}
				else
				{
					if ($pagenumber < ($results['current_page'] - 4))
					{
						$firstlink = " <a href='{$data['pagelink']}' title=''>&lt;&lt;</a> \r\n";
						continue;
					}
					if ($pagenumber > ($results['current_page'] + 4))
					{
						$url = "{$data['pagelink']}&amp;pp=" . ($results['total_page']-1) * $data['perpage'];
						$lastlink = " <a href='$url' title=''>&gt;&gt;</a> \r\n";
						continue;
					}
					$curpage .= " <a href='{$data['pagelink']}&amp;pp={$numberid}' title='$page'>{$pagenumber}</a> \r\n";
				}
			}
			return $pagenav . $firstlink . $prevlink . $curpage . $nextlink . $lastlink;
		}
	}
}

$output = new showthread();
$output->show();

?>