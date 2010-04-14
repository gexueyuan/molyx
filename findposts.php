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
define('THIS_SCRIPT', 'findposts');
require_once('./global.php');

class findposts
{
	var $highlight = '';

	var $prune = 0;
	var $usertitle = array();
	var $cached_query = 0;
	var $cached_matches = 0;
	var $search_type = 'post';
	var $posttable = '';
	var $sortby = '';
	var $order = '';
	var $search;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('search');
		require_once(ROOT_PATH . "includes/functions_search.php");
		$this->search = new functions_search();
		$this->search->highlight =  urlencode($_INPUT['highlight']);
		$this->search->uniqueid = $_INPUT['searchid'];
		$this->search->pagelink = "findposts.php{$forums->sessionurl}&amp;do=show&amp;searchid=" . $this->search->uniqueid;

		$this->search->page = intval($_INPUT['pp']);

		switch ($_INPUT['do'])
		{
			case 'show':
				$this->show_results();
				break;
			case 'getquintessence':
				$this->get_new_quintessence();
				break;
			case 'finduser':
				$this->find_user_post();
				break;
			case 'searchthread':
				$this->search_thread();
				break;
			case 'finduserthread':
				$this->find_user_thread();
				break;
			case 'findmod':
				$this->find_mod_post();
				break;
			default:
				$this->get_new_posts();
				break;
		}
	}

	function find_user_thread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->search->flood_contol();
		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}
		$userid = intval($_INPUT['u']);
		if (!$userid)
		{
			$forums->func->standard_error("cannotsearchuser");
		}
		$results = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "thread WHERE visible=1 AND forumid IN($forumlist) AND postuserid=$userid");
		if (!$results['count'])
		{
			$forums->func->standard_error("nosearchresult");
		}
		$query_to_cache = "SELECT t.*, title as threadtitle, u.avatar
			FROM " . TABLE_PREFIX . 'thread t
			LEFT JOIN ' . TABLE_PREFIX . "user u ON t.postuserid = u.id
			WHERE visible=1 AND forumid IN(" . $forumlist . ") AND postuserid='" . $userid . "'";
		$this->sortby = 't.lastpost';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'userthreads',
		));
		$forums->func->standard_redirect("findposts.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid");
	}

	//查找用户自己的帖子
	function find_user_post()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->search->flood_contol();

		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}
		$userid = intval($_INPUT['u']);
		if ($userid == "")
		{
			$forums->func->standard_error("cannotsearchuser");
		}
		//取得搜索的帖子表
		$splittable = array();
		$forums->func->check_cache('splittable');
		$splittable = $forums->cache['splittable']['default'];
		//只查询用户在当前帖子表的发帖
		$all_post_table = $forums->cache['splittable']['all'];
		unset($all_post_table[$splittable['id']]);
		$this->posttable = $splittable['name'] ? $splittable['name'] : 'post';
		do
		{
			$results = $DB->query_first("SELECT count(*) as count
				FROM " . TABLE_PREFIX . $this->posttable . " p
				LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
				WHERE p.moderate != 1 AND p.newthread=0 AND t.forumid IN(" . $forumlist . ") AND p.userid=" . $userid . "");
		}
		while (!$results['count'] && ($tmptable = array_pop($all_post_table)) && ($this->posttable = $tmptable['name']));

		if (!$results['count'])
		{
			$forums->func->standard_error("nosearchresult");
		}
		$anonymous = ($bbuserinfo['usergroupid'] == 4 AND $userid == $bbuserinfo['id']) ? '' : " AND p.anonymous != 1";
		$query_to_cache = "SELECT p.*, p.dateline AS pdateline, t.*, t.dateline AS tdateline, t.post as thread_post, t.title as threadtitle, u.*
			FROM " . TABLE_PREFIX . $this->posttable . " p
			LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
			LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id=p.userid)
			WHERE p.moderate != 1 AND p.newthread=0 AND t.forumid IN(" . $forumlist . ") AND p.userid='" . $userid . "'$anonymous";
		$this->sortby = 't.dateline';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'userposts',
			'posttable' => $this->posttable,
		));
		$forums->func->standard_redirect("findposts.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;showposts=1");
	}

	function get_new_quintessence()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (!$bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$this->search->flood_contol();
		$last_time = $bbuserinfo['lastvisit'];
		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}
		if ($_INPUT['userid'])
		{
			$query_condition = ' AND postuserid=' . intval($_INPUT['userid']);
		}
		$results = $DB->query_first("SELECT count(*) as count
			FROM " . TABLE_PREFIX . "thread
			WHERE quintessence=1 AND forumid IN(" . $forumlist . ")" . $query_condition);
		if (!$results['count'])
		{
			$forums->func->standard_error("noquintessence");
		}
		$query_to_cache = "SELECT *, title as threadtitle
			FROM " . TABLE_PREFIX . "thread
			WHERE quintessence=1 AND forumid IN(" . $forumlist . ")
			" . $query_condition;
		$this->sortby = 'lastpost';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'forumquintessence',
		));
		$forums->func->standard_redirect("findposts.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid");
	}

	function get_new_posts()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$this->search->flood_contol();

		if ($_INPUT['lastdate'])
		{
			$last_time = TIMENOW - intval($_INPUT['lastdate']);
		}
		elseif($bbuserinfo['lastvisit'])
		{
			$last_time = $bbuserinfo['lastvisit'];
		}
		else
		{
			$last_time = TIMENOW - 86400;
		}
		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}

		$results = $DB->query_first("SELECT count(*) as count
			FROM " . TABLE_PREFIX . "thread
			WHERE visible = 1
				AND lastpost > '" . $last_time . "'
				AND forumid IN(" . $forumlist . ")"
		);
		if (!$results['count'])
		{
			$forums->func->standard_error("nonewpost");
		}
		$query_to_cache = "SELECT *, title as threadtitle
			FROM " . TABLE_PREFIX . "thread
			WHERE visible = 1
				AND forumid IN(" . $forumlist . ")
				AND lastpost > '" . $last_time . "'
			";
		$this->sortby = 'lastpost';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'userid' => $bbuserinfo['id'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'forumnewposts',
		));
		$forums->func->standard_redirect("findposts.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid");
	}

	function find_mod_post()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$this->search->flood_contol();
		$forumlist = $this->search->get_forums();
		if ($forumlist == "" OR !$bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("selectsearchforum");
		}
		//取得搜索的帖子表
		$splittable = array();
		$forums->func->check_cache('splittable');
		$splittable = $forums->cache['splittable']['default'];
		//只查询用户在当前帖子表的发帖
		$all_post_table = $forums->cache['splittable']['all'];
		unset($all_post_table[$splittable['id']]);
		$this->posttable = $splittable['name'] ? $splittable['name'] : 'post';
		do
		{
			$results = $DB->query_first("SELECT count(*) as count
													FROM " . TABLE_PREFIX . $this->posttable . " p
													LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
													WHERE p.moderate = 1 AND t.forumid IN(" . $forumlist . ")"
			);
		}
		while (!$results['count'] && ($tmptable = array_pop($all_post_table)) && ($this->posttable = $tmptable['name']));
		if (! $results['count'])
		{
			$forums->func->standard_error("nosearchuserresult");
		}
		$query_to_cache = "SELECT p.*, p.dateline AS pdateline, t.*, t.dateline AS tdateline, t.post as thread_post, t.title as threadtitle, u.*
											FROM " . TABLE_PREFIX . $this->posttable . " p
											LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid)
											LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id=p.userid)
											WHERE p.moderate = 1 AND t.forumid IN(" . $forumlist . ")";
		$this->sortby = 'p.dateline';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		$uniqueid = md5(uniqid(microtime()));
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'verify_posts',
			'posttable' => $this->posttable,
		));
		$forums->func->standard_redirect("findposts.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;showposts=1");
	}

	function show_results()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		if (!$_INPUT['showposts'])
		{
			$this->search->query_results();
			$allthread = $this->search->threads;
			$show['threadmode'] = true;
			$thread['cansee'] = true;
		}
		else
		{
			$show['threadmode'] = false;
			$thread['cansee'] = true;
			$this->search->pagelink .= "&amp;showposts=1";
			$this->search->show_post_results();
			$allpost = $this->search->posts;
			$posttable = $this->search->posttable;
			add_head_element('js', ROOT_PATH . 'scripts/mxajax_post.js');
		}
		$pagelinks = $this->search->pagenavlink;

		//加载ajax
		$mxajax_register_functions = array(
						'open_close_thread',
						'change_thread_attr',
						'returnpagetext',
						'do_edit_post',
						'process_post_form',
						'change_thread_title',); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_thread.js');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang[$this->search->searchtype] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang[$this->search->searchtype]);
		$searchid = $_INPUT['searchid'];
		$search_type = 'findposts';
		include $forums->func->load_template('find_posts');
		exit;
	}
}

$output = new findposts();
$output->show();

?>