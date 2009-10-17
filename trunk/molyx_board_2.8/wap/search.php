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
# $Id: search.php 263 2007-10-11 13:49:42Z develop_tong $
# **************************************************************************#
define('THIS_SCRIPT', 'search');
require_once('./global.php');

class search
{
	var $page = 0;
	var $threadread = array();
	var $highlight = '';

	var $showposts = 0;
	var $searchin = 'post';
	var $sortby = 'lastpost';
	var $order = 'desc';
	var $usertitle = array();
	var $cached_query = 0;
	var $cached_matches = 0;
	var $search_type = 'post';

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['do'] != 'getnew' AND $_INPUT['do'] != 'show')
		{
			if ($bbuserinfo['cansearch'] != 1)
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['cannotviewthispage']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		$this->page = intval($_INPUT['pp']);
		switch ($_INPUT['do'])
		{
			case 'search':
				$this->do_search();
				break;
			case 'getnew':
				$this->get_new_post();
				break;
			case 'show':
				$this->show_results();
				break;
			case 'searchthread':
				$this->search_thread();
				break;
			case 'finduserthread':
				$this->find_user_thread();
				break;
			default:
				$this->showform();
				break;
		}
	}

	function flood_contol()
	{
		global $DB, $bbuserinfo, $forums, $bboptions;
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
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$forums->lang['notlogin'] = sprintf($forums->lang['notlogin'] , $bbuserinfo['searchflood']);
				$contents = convert($forums->lang['notlogin']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
	}

	function showform()
	{
		global $forums, $_INPUT, $bboptions, $bbuserinfo;
		$forums->lang['keyword'] = convert($forums->lang['keyword']);
		$forums->lang['username'] = convert($forums->lang['byusername']);
		$forums->lang['search'] = convert($forums->lang['search']);
		$forums->lang['searchexplain'] = convert($forums->lang['searchexplain']);
		$forums->lang['mythread'] = convert($forums->lang['mythread']);
		include $forums->func->load_template('wap_search');
		exit;
	}

	function search_thread()
	{
		global $_INPUT;
		$this->threadid = intval($_INPUT['thread']);
		$this->do_search();
	}

	function find_user_thread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->flood_contol();
		$forumlist = $this->get_forums();
		$userid = intval($_INPUT['u']);
		if (!$userid)
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$forums->lang['notlogin'] = sprintf($forums->lang['notlogin'] , $bbuserinfo['searchflood']);
			$contents = convert($forums->lang['cannotsearchuser']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$results = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "thread WHERE visible=1 AND forumid IN($forumlist) AND postuserid=$userid");
		if (!$results['count'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$forums->lang['notlogin'] = sprintf($forums->lang['notlogin'] , $bbuserinfo['searchflood']);
			$contents = convert($forums->lang['nosearchresult']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$query_to_cache = "SELECT *, title as threadtitle
											FROM " . TABLE_PREFIX . "thread
											WHERE visible=1 AND forumid IN(" . $forumlist . ") AND postuserid='" . $userid . "'
											ORDER BY lastpost DESC";
		$DB->query($query_to_cache);
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxpost' => $results['count'],
			'sortby' => 'lastpost',
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache
		));
		redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;searchin=thread");
	}

	function get_new_post()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['id'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['notlogin']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$this->flood_contol();
		$last_time = $bbuserinfo['lastvisit'];
		if ($_INPUT['active'])
		{
			if ($_INPUT['lastdate'])
			{
				$last_time = TIMENOW - intval($_INPUT['lastdate']);
			}
			else
			{
				$last_time = TIMENOW - 86400;
			}
		}
		$forumlist = $this->get_forums();
		if ($forumlist == "")
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['selectsearchforum']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$results = $DB->query_first("SELECT count(*) as count
													FROM " . TABLE_PREFIX . "thread
													WHERE visible=1 AND forumid IN(" . $forumlist . ") AND lastpost > '" . $last_time . "'"
			);
		if (!$results['count'])
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['nonewpost']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$query_to_cache = "SELECT *, title as threadtitle
											FROM " . TABLE_PREFIX . "thread
											WHERE visible=1 AND forumid IN(" . $forumlist . ") AND lastpost > '" . $last_time . "'
											ORDER BY lastpost DESC";
		$DB->query($query_to_cache);
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxpost' => $results['count'],
			'sortby' => 'lastpost',
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache
		));
		redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;lastdate={$_INPUT['lastdate']}");
	}

	function do_search()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if ($_INPUT['namesearch'] != "")
		{
			$name_filter = $this->filter_keywords($_INPUT['namesearch'], 1);
		}
		$keywords = $this->filter_keywords($_INPUT['keywords']);
		if ($name_filter == "" AND $_INPUT['keywords'] != "")
		{
			$type = 'postonly';
		}
		else if ($name_filter != "" AND $_INPUT['keywords'] == "")
		{
			$type = 'nameonly';
		}
		$checkwords = str_replace("%", "", trim($keywords));
		if (!$checkwords OR $checkwords == "" OR !isset($checkwords))
		{
			if ($type != 'nameonly')
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['requirekeyword']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		$forumlist = $this->get_forums();
		if ($forumlist == "")
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['selectsearchforum']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$bboptions['minsearchlength'] = $bboptions['minsearchlength'] ? $bboptions['minsearchlength'] : 4;
		$name_filter = trim($name_filter);
		$user_string = "";
		if ($name_filter != "")
		{
			$name_filter = str_replace('|', "&#124;", $name_filter);
			$DB->query("SELECT id from " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($name_filter) . "'");
			while ($row = $DB->fetch_array())
			{
				$user_string .= "'" . $row['id'] . "',";
			}
			$user_string = preg_replace("/,$/", "", $user_string);
			if ($user_string == "")
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['nosearchuserresult']);
				include $forums->func->load_template('wap_info');
				exit;
			}
			$posts_name = " AND p.userid IN ($user_string)";
			$threads_name = " AND t.postuserid IN ($user_string)";
		}
		if ($type != 'nameonly')
		{
			if (preg_match("/ and|or|&|\| /", $keywords))
			{
				preg_match_all("/(^|and|or|&|\|)\s{1,}(\S+?)\s{1,}/", $keywords, $matches);
				$title_like = "(";
				$post_like = "(";
				for ($i = 0, $n = count($matches[0]); $i < $n; $i++)
				{
					$boolean = $matches[1][$i];
					$word = trim($matches[2][$i]);
					if (utf8_strlen($word) < $bboptions['minsearchlength'])
					{
						$forums->func->load_lang('error');
						$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
						$forums->lang['keywordtooshort'] = sprintf($forums->lang['keywordtooshort'], $bboptions['minsearchlength']);
						$contents = convert($forums->lang['keywordtooshort']);
						include $forums->func->load_template('wap_info');
						exit;
					}
					if ($boolean)
					{
						if ($boolean == "&") $boolean = "AND";
						if ($boolean == "|") $boolean = "OR";
						$boolean = " $boolean";
					}
					$title_like .= "$boolean LOWER(t.title) LIKE '%$word%' ";
					$post_like .= "$boolean LOWER(p.pagetext) LIKE '%$word%' ";
				}
				$title_like .= ")";
				$post_like .= ")";
			}
			else
			{
				$keywords = str_replace('|', "&#124;", $keywords);
				if (utf8_strlen(trim($keywords)) < $bboptions['minsearchlength'])
				{
					$forums->func->load_lang('error');
					$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
					$forums->lang['keywordtooshort'] = sprintf($forums->lang['keywordtooshort'], $bboptions['minsearchlength']);
					$contents = convert($forums->lang['keywordtooshort']);
					include $forums->func->load_template('wap_info');
					exit;
				}
				$title_like = " LOWER(t.title) LIKE '%" . trim($keywords) . "%' ";
				$post_like = " LOWER(p.pagetext) LIKE '%" . trim($keywords) . "%' ";
			}
		}
		$uniqueid = md5(uniqid(microtime()));
		if ($this->threadid != '')
		{
			$search_tid = " AND t.tid = $this->threadid";
			$search_pid = " AND p.threadid = $this->threadid";
		}
		if ($type != 'nameonly')
		{
			$threads_query = "SELECT t.tid
							FROM " . TABLE_PREFIX . "thread t
							WHERE $threads_datecut t.forumid IN ($forumlist)
							$threads_name $quintessence $rept $search_tid AND t.visible=1 AND ($title_like)";
		}
		else
		{
			$threads_query = "SELECT t.tid
							FROM " . TABLE_PREFIX . "thread t
							WHERE $threads_datecut t.forumid IN ($forumlist)
							$threads_name $quintessence $rept";
		}
		$threads = "";
		$posts = "";
		$DB->query($threads_query);
		$maxthread = $DB->num_rows();
		while ($row = $DB->fetch_array())
		{
			$threads .= $row['tid'] . ",";
		}
		$DB->free_result();
		$threads = preg_replace("/,$/", "", $threads);
		$posts = preg_replace("/,$/", "", $posts);
		if ($threads == "" AND $posts == "")
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['nosearchuserresult']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'threadid' => $threads,
			'maxthread' => $maxthread,
			'sortby' => 'lastpost',
			'sortorder' => 'desc',
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'postid' => $posts,
			'maxpost' => $maxpost
		));
		redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid");
	}

	function show_results()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$this->cached_query = 0;
		$this->cached_matches = 0;
		require_once(ROOT_PATH . "includes/functions_codeparse.php");
		$this->parser = new functions_codeparse();
		$searchid = $_INPUT['searchid'];
		if ($searchid == "")
		{
			$forums->func->load_lang('error');
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['nosearchuserresult']);
			include $forums->func->load_template('wap_info');
			exit;
		}
		$results = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "search WHERE searchid='" . $searchid . "'");
		$this->order = $results['sortorder'];
		if (!$results['query'])
		{
			$thread = $results['threadid'];
			$maxthread = $results['maxthread'];
			$post = $results['postid'];
			$maxpost = $results['maxpost'];
			$thread_array = array();
			$post_array = array();
			if ($thread)
			{
				foreach(explode(",", $thread) AS $t)
				{
					$thread_array[ $t ] = $t;
				}
			}
			if ($post)
			{
				foreach(explode(",", $post) AS $t)
				{
					$post_array[ $t ] = $t;
				}
			}
			if (! $thread AND ! $post)
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['nosearchuserresult']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		if ($results['query'])
		{
			$rows = $DB->query($results['query'] . " LIMIT " . $this->page . ", 8");
		}
		else
		{
			if (! $thread)
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['nosearchuserresult']);
				include $forums->func->load_template('wap_info');
				exit;
			}
			$rows = $DB->query("SELECT *, title AS threadtitle FROM " . TABLE_PREFIX . "thread WHERE tid IN(" . $thread . ") ORDER BY sticky DESC, lastpost DESC LIMIT " . $this->page . ", 8");
		}
		if ($DB->num_rows($rows))
		{
			$i = 0;
			while ($row = $DB->fetch_array($rows))
			{
				++$i;
				$showthread .= "<p>\n<img src='images/dot.gif' alt='-' /><a href='thread.php{$forums->sessionurl}t={$row['tid']}'>" . strip_tags($row['title']) . "</a><br />\n";
				$showthread .= $forums->lang['lastpost'] . ": {$row['lastposter']}<br />\n";
				$showthread .= $forums->func->get_date($row['lastpost'], 2) . "\n</p>\n\n";
			}
			$prevlink = $this->page - 8;
			$nextlink = $this->page + 8;
			$prevpage = ($prevlink < 0) ? false : true;
			$nextpage = ($i < 8) ? false : true;
		}
		else
		{
			if (! $_INPUT['lastdate'])
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['nosearchuserresult']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		$showthread = convert($showthread);

		$forums->lang['prevlink'] = $prevpage ? convert($forums->lang['prevlink']) : '';
		$forums->lang['nextlink'] = $nextpage ? convert($forums->lang['nextlink']) : '';
		$forums->lang['searchresults'] = $nextpage ? convert($forums->lang['searchresults']) : '';
		if ($prevpage OR $nextpage)
		{
			$show['p1'] = true;
		}
		include $forums->func->load_template('wap_search_results');
		exit;
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

	function get_forums()
	{
		global $forums, $DB, $_INPUT;
		$forumids = array();
		foreach($forums->forum->foruminfo as $id => $data)
		{
			if ($this->check_permissions($forums->forum->foruminfo[$data['id']]) == true)
			{
				$forumids[] = $data['id'];
			}
		}
		return implode("," , $forumids);
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

$output = new search();
$output->show();

?>