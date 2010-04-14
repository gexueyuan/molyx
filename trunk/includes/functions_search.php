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

class functions_search
{
	var $page = 0;
	var $pagelink = '';
	var $pagenavlink = '';
	var $uniqueid = '';
	var $highlight = '';
	var $posttable = 'post';
	var $searchtype = '';
	var $threads = array();
	var $posts = array();
	var $threadread = array();

	function functions_search()
	{
		global $forums;
		if ($threadread = $forums->func->get_cookie('threadread'))
		{
			$this->threadread = unserialize($threadread);
		}
	}

	function flood_contol()
	{
		global $DB, $bbuserinfo, $forums;
		if ($bbuserinfo['searchflood'] > 0)
		{
			$flood_time = TIMENOW - $bbuserinfo['searchflood'];
			if ($bbuserinfo['id'])
			{
				$where = "userid=" . $bbuserinfo['id'];
			}
			else
			{
				$where = "host=" . $DB->validate(IPADDRESS);
			}
			if ($DB->query_first("SELECT searchid FROM " . TABLE_PREFIX . "search WHERE $where AND dateline > '" . $flood_time . "'"))
			{
				$forums->func->standard_error("searchflood", false, $bbuserinfo['searchflood']);
			}
		}
	}

	function query_results()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		if ($this->uniqueid == "")
		{
			$forums->func->standard_error("nosearchuserresult");
		}
		$results = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "search WHERE searchid='" . $this->uniqueid . "'");
		if (!$results['query'])
		{
			$forums->func->standard_error("searchresulttimeout");
		}
		if ($results['presearchid'])
		{
			$this->uniqueid = $results['presearchid'];
		}
		$this->searchtype = $results['searchype'];
		require_once(ROOT_PATH . "includes/class_textparse.php");

		$this->start_page($results['maxrecord']);
		$results['query'] = $results['query'] . ' ORDER BY ' . $results['sortby'] . ' ' . $results['sortorder'];
		$rows = $DB->query($results['query'] . " LIMIT " . $this->page . ", 15");
		if ($DB->num_rows($rows))
		{
			$this->highlight = urldecode($this->highlight);
			$hignlight_key = duality_word($this->highlight, 1);
			$tokey = array();
			foreach ($hignlight_key AS $k => $wd)
			{
				$tokey[$k] = '<span class="highlight">' . $wd . '</span>';
			}
			while ($row = $DB->fetch_array($rows))
			{
				$row['keywords'] = urlencode($this->highlight);
				$row['threadtitle'] = str_replace($hignlight_key, $tokey, $row['threadtitle']);
				$this->threads[] = $this->parse_entry($row);
			}
		}
		else
		{
			$forums->func->standard_error("nosearchuserresult");
		}
	}


	function show_post_results()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		require_once(ROOT_PATH . "includes/class_textparse.php");

		if ($this->uniqueid == "")
		{
			$forums->func->standard_error("nosearchuserresult");
		}
		$results = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "search WHERE searchid='" . $this->uniqueid . "'");
		$this->order = $results['sortorder'];
		$this->sortby = $results['sortby'];
		if (!$results['query'])
		{
			$forums->func->standard_error("searchresulttimeout");
		}
		if ($results['presearchid'])
		{
			$this->uniqueid = $results['presearchid'];
		}
		$this->posttable = $results['posttable'];
		$this->searchtype = $results['searchype'];
		$this->start_page($results['maxrecord']);
		$results['query'] = $results['query'] . ' ORDER BY ' . $results['sortby'] . ' ' . $results['sortorder'];
		$rows = $DB->query($results['query'] . " LIMIT " . $this->page . ", 15");
		if ($DB->num_rows($rows))
		{
			require_once(ROOT_PATH . 'includes/functions_codeparse.php');
			$codeparse = new functions_codeparse();
			while ($row = $DB->fetch_array($rows))
			{
				$row['keywords'] = urlencode($this->highlight);
				$row['dateline'] = $row['pdateline'] ? $row['pdateline'] : $row['dateline'];
				$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
				$row['pagetext'] = $row['hidepost'] ? $forums->lang['_posthidden'] : textparse::convert_text($row['pagetext']);//处理引用
				if (strpos($row['pagetext'], '[quote') !== false)
				{
					$row['pagetext'] = preg_replace("#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$codeparse->parse_quotes('\\1')" , $row['pagetext']);
				}
				//处理flash
				if (strpos($row['pagetext'], '[FLASH') !== false)
				{
					$pregfind = array("#(\[flash\])(.+?)(\[\/flash\])#ie", "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#ie");
					$pregreplace = array("\$codeparse->parse_flash('','','\\2')", "\$codeparse->parse_flash('\\2','\\4','\\6')");
					$row['pagetext'] = preg_replace($pregfind, $pregreplace, $row['pagetext']);
				}
				if ($bboptions['postsearchlength'])
				{
					$row['pagetext'] = preg_replace('/<img (.*) \/>/iU', '', $row['pagetext']);
					$row['pagetext'] = $forums->func->fetch_trimmed_title($row['pagetext'], $bboptions['postsearchlength']);
					$row['pagetext'] = str_replace("\n", "<br />", $row['pagetext']);
				}
				$row['avatartype'] = 1; //显示中等头像
				$user = $forums->func->fetch_user($row);
				$user['groupname'] = $forums->lang["{$user['grouptitle']}"];
				$post = $this->parse_entry($row, 1);
				$this->posts[] = array('post' => $post, 'user' => $user);
			}
		}
		else
		{
			$forums->func->standard_error("nosearchuserresult");
		}
	}

	function start_page($amount)
	{
		global $forums;
		$this->pagenavlink = $forums->func->build_pagelinks(array(
			'totalpages' => $amount,
			'perpage' => 15,
			'curpage' => $this->page,
			'pagelink' => $this->pagelink,
		));
	}

	function parse_entry($thread, $view_as_post = 0)
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$thread = $this->parse_data($thread);

		if ($thread['sticky'])
		{
			$thread['prefix'] = '';
			$thread['folder_img'] = array('icons' => 'sticky.gif',
				'title' => $forums->lang['threadgstick'],);
		}
		$forums->func->check_cache('st');
		$thread['st'] = $forums->cache['st'][$thread['stopic']]['name'];
		$thread['forum_full_name'] = $forums->forum->foruminfo[ $thread['forumid'] ]['name'];
		if (strlen($thread['forum_full_name']) > 50)
		{
			$thread['forum_name'] = $forums->func->fetch_trimmed_title($thread['forum_full_name'], 25);
		}
		else
		{
			$thread['forum_name'] = $thread['forum_full_name'];
		}
		$thread['user_avatar'] = $forums->func->get_avatar($thread['postuserid'], $thread['avatar'], 2);
		return $thread;
	}

	function parse_data($thread)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$last_time = $this->threadread[$thread['tid']] > $_INPUT['lastvisit'] ? $this->threadread[$thread['tid']] : $_INPUT['lastvisit'];
		$maxposts = $bboptions['maxposts'] ? $bboptions['maxposts'] : '10';
		$thread['lastposter'] = $thread['lastposterid'] ? $forums->func->fetch_user_link($thread['lastposter'], $thread['lastposterid']) : "-" . $thread['lastposter'] . "-";
		$thread['postusername'] = $thread['postuserid'] ? $forums->func->fetch_user_link($thread['postusername'], $thread['postuserid']) : $thread['postusername'] . "*";
		if ($thread['pollstate'])
		{
			$thread['prefix'] = $bboptions['pollprefix'] . ' ';
		}
		$thread['folder_img'] = $forums->func->folder_icon($thread, $last_time);
		$forums->func->check_cache('icon');
		$thread['thread_icon'] = $thread['iconid'] ? 1 : 0;
		$thread['thread_cache_icon'] = $forums->cache['icon'][$thread['iconid']]['image'];
		$thread['showpages'] = $forums->func->build_threadpages(
			array('id' => $thread['tid'],
				'totalpost' => $thread['post'],
				'perpage' => $maxposts,
				)
			);
		$thread['post'] = fetch_number_format($thread['post']);
		$thread['views'] = fetch_number_format($thread['views']);
		if ($last_time && ($thread['lastpost'] > $last_time))
		{
			$thread['gotonewpost'] = 1;
		}
		else
		{
			$thread['gotonewpost'] = 0;
		}
		$thread['lastpost'] = $forums->func->get_date($thread['lastpost'], 1);
		$thread['dateline'] = $thread['tdateline'] ? $thread['tdateline'] : $thread['dateline'];
		$thread['dateline'] = $forums->func->get_date($thread['dateline'], 1);
		if ($thread['open'] == 2)
		{
			$t_array = explode("&", $thread['moved']);
			$thread['tid'] = $t_array[0];
			$thread['forumid'] = $t_array[1];
			$thread['views'] = '--';
			$thread['post'] = '--';
			$thread['prefix'] = $bboptions['movedprefix'] . " ";
			$thread['gotonewpost'] = "";
		}

		//有权限改变主题标题
		if ($bbuserinfo['supermod'] || $bbuserinfo['candobatch'])
		{
			if ($bbuserinfo['supermod'])
			{
				$thread['ajax_edit_thread_title'] = ' ondblclick="showthreadin(' . $thread['tid'] .');"';
				$thread['ajax_edit_thread_title_attr'] = '<span id="thread_c_b_' . $thread['tid'] . '"><img class="inline" src="images/' . $bbuserinfo['imgurl'] . '/op_title.gif" alt="' . $forums->lang['ajaxthreadcb'] . '" onclick="change_thread_attr(' . $thread['tid'] . ')" /></span>';
			}
			$thread['thread_checkbox'] = '<input type="checkbox" name="tid[]" id="tid' . $thread["tid"] . '" value="' . $thread["tid"] . '" />';
		}
		else
		{
			$thread['ajax_edit_thread_title'] = '';
			$thread['ajax_edit_thread_title_attr'] = '';
			$thread['thread_checkbox'] = '';
		}
		return $thread;
	}

	function filter_keywords($words = "", $name = 0)
	{
		global $forums;
		$words = trim(strtolower(str_replace("%", "\\%", $words)));
		$words = preg_replace("/\s+(and|or|&|\|)$/" , "" , $words);
		$words = str_replace("_", "\\_", $words);
		if ($name == 0)
		{
			$words = preg_replace("/[\[\]\{\}\(\)\,\\\\\"']|&quot;/", "", $words);
		}
		$words = preg_replace("/^(?:img|quote|code|html|javascript|a href|color|span|div)$/", "", $words);
		return " " . preg_quote($words) . " ";
	}

	function convert_highlight($words = "")
	{
	}

	function get_forums()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forumids = array();
		if (is_array($_INPUT['forumlist']))
		{
			if (in_array('0', $_INPUT['forumlist']))
			{
				foreach($forums->forum->foruminfo AS $id => $data)
				{
					$forumids[] = $data['id'];
				}
			}
			else
			{
				foreach($_INPUT['forumlist'] AS $l)
				{
					if ($forums->forum->foruminfo[ $l ])
					{
						$forumids[] = intval($l);
					}
				}
				if (count($forumids))
				{
					foreach($forumids AS $f)
					{
						$children = $forums->forum->forums_get_children($f);
						if (is_array($children) AND count($children))
						{
							$forumids = array_merge($forumids , $children);
						}
					}
				}
				else
				{
					return;
				}
			}
		}
		else
		{
			if ($_INPUT['forumlist'] == '')
			{
				$forumids = array_keys($forums->forum->foruminfo);
			}
			else
			{
				$l = intval($_INPUT['forumlist']);
				if ($forums->forum->foruminfo[$l])
				{
					$forumids[] = intval($l);
				}
				if ($_INPUT['includesubforum'] == 1)
				{
					$children = $forums->forum->forums_get_children($f);
					if (is_array($children) AND count($children))
					{
						$forumids = array_merge($forumids , $children);
					}
				}
			}
		}
		$final = array();
		foreach ($forumids AS $f)
		{
			if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'] && $f == $bboptions['recycleforumid'])
			{
				continue;
			}
			if ($this->check_permissions($forums->forum->foruminfo[$f]) == true)
			{
				$final[] = $f;
			}
		}
		return implode("," , $final);
	}

	function check_permissions($forum)
	{
		global $forums;
		$can_read = false;
		if ($forums->func->fetch_permissions($forum['canread'], 'canread') == true)
		{
			$can_read = true;
		}
		if ($forum['password'] != "" AND $can_read == true)
		{
			if ($forums->forum->check_password($forum['id']) != true)
			{
				$can_read = false;
			}
		}
		return $can_read;
	}
}

?>