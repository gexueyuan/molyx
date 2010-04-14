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
define('THIS_SCRIPT', 'search');
require_once('./global.php');

class search
{
	var $highlight = '';

	var $prune = 0;
	var $usertitle = array();
	var $cached_query = 0;
	var $cached_matches = 0;
	var $search_type = 'post';
	var $posttable = '';
	var $search;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('search');

		if (! $bboptions['enablesearches'])
		{
			$forums->func->standard_error("cannotsearch");
		}
		if ($bbuserinfo['cansearch'] != 1)
		{
			$forums->func->standard_error("cannotviewthispage");
		}

		require_once(ROOT_PATH . "includes/functions_search.php");
		$this->search = new functions_search();
		$this->search->highlight =  urlencode($_INPUT['highlight']);
		$this->search->uniqueid = $_INPUT['searchid'];
		$this->search->pagelink = "search.php{$forums->sessionurl}&amp;do=show&amp;searchid=" . $this->search->uniqueid . "&amp;highlight=" . $this->search->highlight;

		$this->search->page = intval($_INPUT['pp']);
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		switch ($_INPUT['do'])
		{
			case 'search':
				$this->do_search();
				break;
			case 'detail_search':
				$this->detail_search();
				break;
			case 'searchinresults':
				$this->searchinresults();
				break;
			case 'show':
				$this->show_results();
				break;
			default:
				$this->showform();
				break;
		}
	}

	function showform()
	{
		global $forums, $_INPUT, $bboptions, $bbuserinfo, $DB;
		$showforum = $this->search_forum_jump(1, 1);
		if (! $_INPUT['f'])
		{
			$selected = ' selected="selected"';
		}
		$credit_list = $this->credit->show_credit('search', $bbuserinfo['usergroupid']);
		$pagetitle = $forums->lang['search'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['search']);

		$tables = array();
		$forums->func->check_cache('splittable');
		$tables = $forums->cache['splittable']['all'];
		$splittable = '';
		$achive = array();
		foreach ($tables as $id => $v)
		{
			$maxtid = $DB->query_first('SELECT MAX(threadid) AS tid FROM ' . TABLE_PREFIX . $v['name']);
			$achive_time = $DB->query_first('SELECT dateline FROM ' . TABLE_PREFIX .'thread WHERE tid=' . intval($maxtid['tid']));
			if ($achive_time['dateline'])
			{
				$achive_time['table'] = $v['name'];
				$achive_time['dateformat'] = $forums->func->get_date($achive_time['dateline'], 5, 1);
				$achive[$achive_time['dateline']] = $achive_time;
			}
		}
		krsort($achive);
		include $forums->func->load_template('search');
		exit;
	}
	function search_forum_jump($html = 0, $override = 0)
	{
		global $forums, $_INPUT, $bboptions, $bbuserinfo;
		$this->foruminfo = $forums->cache['forum'];
		if($bboptions['recycleforumid'])
		{
			$key1 = intval($bboptions['recycleforumid']);
			$key2 = $key1 - 1;
			unset($this->foruminfo["$key1"],$this->foruminfo["$key2"]);
		}
		foreach((array) $this->foruminfo as $id => $forum)
		{
			if (($forum['canshow'] != '*' && $forums->func->fetch_permissions($forum['canshow'], 'canshow') != true) || $forum['url'])
			{
				continue;
			}

			if ($html == 1 || $override == 1)
			{
				$selected = ($_INPUT['f'] && $_INPUT['f'] == $forum['id']) ? " selected='selected'" : '';
			}
			$forum_jump .= '<option value="' . $forum['id'] . '"' . $selected . '>' . depth_mark($forum['depth'], '--') . ' ' . $forum['name'] . '</option>' . "\n";
		}
		return $forum_jump;
	}

	function detail_search()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		//搜索行为影响的分值增减
		$this->search->flood_contol();
		//搜索行为影响的分值增减
		$this->credit->check_credit('search', $bbuserinfo['usergroupid']);
        //搜索区域|关键字过滤
		if ($_INPUT['namesearch'] != "")
		{
			$name_filter = $this->search->filter_keywords($_INPUT['namesearch'], 1);
		}
		$keywords = $this->search->filter_keywords($_INPUT['keywords']);
		//按照关键词搜索
		if ($name_filter == "" AND $_INPUT['keywords'] != "")
		{
			$type = 'postonly';
		}
		//按照用户名搜索
		else if ($name_filter != "" AND $_INPUT['keywords'] == "")
		{
			$type = 'nameonly';
		}

		//选择搜索帖子表
		$this->posttable = $_INPUT['posttable']?$_INPUT['posttable']:$this->posttable;

		$checkwords = str_replace("%", "", trim($keywords));
		//NULL
		if (!$checkwords OR $checkwords == "" OR !isset($checkwords))
		{
			if ($type != 'nameonly')
			{
				$forums->func->standard_error("requirekeyword");
			}
		}

		/*
		搜索方式

		仅搜索标题 <search_in_titles>
		搜索标题和内容 <earch_in_post>
		仅搜索精华主题 <search_in_quintessence>
		仅搜索积分帖 <search_in_reputation>

		*/
		$this->searchin = $_INPUT['searchin'];

		//搜索论坛
		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}
		/*
		搜索结果按照

		最新回复 <lastpost>
		帖子数量 <post>
		发帖会员名称 <postusername>
		论坛名称 <forumid>

		排序方式

		*/
		foreach(array('lastpost', 'post', 'postusername', 'forumid') AS $v)
		{
			if ($_INPUT['sortby'] == $v)
			{
				$this->sortby = $v;
			}
		}
		//发帖时间 <%d天>
		foreach (array(1, 7, 30, 60, 90, 180, 365, 0) AS $v)
		{
			if ($_INPUT['prune'] == $v)
			{
				$this->prune = $v;
			}
		}
		//排序
		if (in_array($_INPUT['order'], array('asc', 'desc')))
		{
			$this->order = $_INPUT['order'];
		}
		else
		{
			$this->order = 'DESC';
		}
		$bboptions['minsearchlength'] = $bboptions['minsearchlength'] ? $bboptions['minsearchlength'] : 4;

		if ($this->prune > 0)
		{
			//相对与发帖时间的 %d天前 <older> 或者 %d天后 <newer>的帖子
			$gt_lt = $_INPUT['prune_type'] == 'older' ? "<" : ">";
			//计算时间差
			$time = TIMENOW - ($_INPUT['prune'] * 86400);
			//建立sql语句条件关系
			//主题
			$threads_datecut = "t.lastpost $gt_lt $time AND";
			//内容|回复
			$posts_datecut = "p.dateline $gt_lt $time AND";
		}
		$name_filter = trim($name_filter);
		$user_string = "";

		//按照用户名搜索
		if ($name_filter != "")
		{
			//转义 | <OR> 防止sql查询错误
			$name_filter = str_replace('|', "&#124;", $name_filter);
			//精确匹配用户名
			if ($_INPUT['exactmatch'] == 1)
			{
				$sql_query = "SELECT id from " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($name_filter) . "' OR name='" . $name_filter . "'";
			}
			else
			{
				//模糊匹配用户名
				$sql_query = "SELECT id from " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE concat('%','" . strtolower($name_filter) . "','%') OR name LIKE concat('%','" . $name_filter . "','%')";
			}
			//数据库查询
			$DB->query($sql_query);
			while ($row = $DB->fetch_array())
			{
				$user_string .= "'" . $row['id'] . "',";
			}
			//去除多余','符
			//$user_string = preg_replace("/,$/", "", $user_string);
			$user_string = substr_replace($user_string,'',-1);

			//没有搜索到该<类>用户
			if ($user_string == "")
			{
				$forums->func->standard_error("nosearchuserresult");
			}
			$posts_name = " AND p.userid IN ($user_string)";
			$threads_name = " AND t.postuserid IN ($user_string)";
		}

		//复合查询
		if ($type != 'nameonly')
		{
			//如果有and|or|&||则进行关系转换
			if (preg_match("/ and|or|&|\| /", $keywords))
			{
				//取到关键词中的逻辑关系格式的数据
				preg_match_all("/(^|and|or|&|\|)\s{1,}(\S+?)\s{1,}/", $keywords, $matches);
				$title_like = "(";
				$post_like = "(";
				//循环取出所有数据
				for ($i = 0, $n = count($matches[0]); $i < $n; $i++)
				{
					//逻辑关系符 and|or|&||
					$boolean = $matches[1][$i];
					//逻辑关系后的数据
					$word = trim($matches[2][$i]);
					//取出的数据长度小于系统设置的搜索关键词的最小长度[常规设置]
					if (utf8_strlen($word) < $bboptions['minsearchlength'])
					{
						$forums->func->standard_error("keywordtooshort", false, $bboptions['minsearchlength']);
					}
					//转换| & == OR AND
					if ($boolean)
					{
						if ($boolean == "&") $boolean = "AND";
						if ($boolean == "|") $boolean = "OR";
						$boolean = " $boolean";
					}
					$title_like .= "$boolean LOWER(t.title) LIKE '%$word%' ";
					$post_like .= "$boolean LOWER(p.pagetext) LIKE '%$word%' ";
				}
				//完成sql语句条件关系的拼接
				$title_like .= ")";
				$post_like .= ")";
			}
			else
			{
				//单一条件
				$keywords = str_replace('|', "&#124;", $keywords);
				if (utf8_strlen(trim($keywords)) < $bboptions['minsearchlength'])
				{
					$forums->func->standard_error("keywordtooshort", false, $bboptions['minsearchlength']);
				}
				$title_like = " LOWER(t.title) LIKE '%" . trim($keywords) . "%' ";
				$post_like = " LOWER(p.pagetext) LIKE '%" . trim($keywords) . "%' ";
			}
		}

		//随机数
		$uniqueid = md5(uniqid(microtime()));
		//帖子精确查询[指定id]
		if ($this->threadid != '')
		{
			//拼接条件关系
			$search_tid = " AND t.tid = $this->threadid";
			$search_pid = " AND p.threadid = $this->threadid";
		}
		//搜索精华主题
		if ($this->searchin == 'quintessence')
		{
			//拼接条件关系
			$quintessence = " AND t.quintessence = 1";
		}
		//搜索积分帖
		if ($this->searchin == 'reputation')
		{
			//拼接条件关系
			$rept = " AND t.allrep != 0";
			$repp = " AND p.reppost != ''";
			$this->searchin = 'post';
		}
		//不按照用户名搜索
		if ($type != 'nameonly')
		{

			/*

			@param threads_datecut	主题发布时间相对时间内查询
			@param threads_name		主题作者

			*/

			$query_to_cache = "SELECT *, title AS threadtitle
							FROM " . TABLE_PREFIX . "thread t
							WHERE $threads_datecut t.forumid IN ($forumlist)
							$threads_name $quintessence $rept $search_tid AND t.visible=1 AND ($title_like) $t_addsql";
			//搜索标题和内容
			if ($this->searchin == 'post' AND $bbuserinfo['cansearchpost'])
			{
				/*

				@param posts_datecut	主题/回复相对时间内查询
				@serach_pid				帖子精确查询[指定id]
		        @posts_name				发帖/回复的用户[s]
				@post_like				条件
				@repp					积分
				@forumlist				论坛

				*/
				$query_to_cache = "SELECT *, title AS threadtitle
								FROM " . TABLE_PREFIX . $this->posttable . " p
								 LEFT JOIN " . TABLE_PREFIX . "thread t on (t.tid=p.threadid)
								WHERE $posts_datecut  t.forumid IN ($forumlist) $search_pid $repp
								 AND p.moderate != 1
								 $posts_name AND ($post_like)";
			}
		}
		else
		{
			$query_to_cache = "SELECT *, title AS threadtitle
							FROM " . TABLE_PREFIX . "thread t
							WHERE $threads_datecut t.forumid IN ($forumlist)
							$threads_name $quintessence $rept $t_addsql";
			//除了不关联用户[s]外如上
			if ($this->searchin == 'post' AND $bbuserinfo['cansearchpost'])
			{
				$query_to_cache = "SELECT *, title AS threadtitle
								FROM " . TABLE_PREFIX . $this->posttable . " p
								LEFT JOIN " . TABLE_PREFIX . "thread t on (t.tid=p.threadid)
								WHERE $posts_datecut  t.forumid IN ($forumlist) $repp
								AND p.moderate != 1
								 $posts_name $p_addsql $t_addsql ";
			}
		}
		$uniqueid = md5(uniqid(microtime()));
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1');
		$results = $DB->query_first(str_replace('*, title AS threadtitle', 'COUNT(*) as count', $query_to_cache) . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1');

		//写入查询表中
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'searchresult',
		));

		//积分更新
		$this->credit->update_credit('search', array($bbuserinfo['id']), array($bbuserinfo['usergroupid']));
		//跳转
		$forums->func->standard_redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;searchin=" . $this->searchin . "&amp;showposts=" . $this->showposts . "&amp;highlight=" . urlencode(trim($keywords)));

	}

	function do_search()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->search->flood_contol();
		//搜索行为影响的分值增减
		$this->credit->check_credit('search', $bbuserinfo['usergroupid']);

		$keywords = $this->search->filter_keywords($_INPUT['keywords']);
		//搜索论坛
		$forumlist = $this->search->get_forums();
		if ($forumlist == "")
		{
			$forums->func->standard_error("selectsearchforum");
		}

		$bboptions['minsearchlength'] = $bboptions['minsearchlength'] ? $bboptions['minsearchlength'] : 4;

		if (utf8_strlen($keywords) < $bboptions['minsearchlength'])
		{
			$forums->func->standard_error("keywordtooshort", false, $bboptions['minsearchlength']);
		}
		$uniqueid = md5(uniqid(microtime()));

		$ft = duality_word($keywords);
		$ft = implode(' ', $ft);
		$sql = "SELECT COUNT(*) AS count
						FROM " . TABLE_PREFIX . "thread
						WHERE MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE )
						AND forumid IN ($forumlist)
						AND visible=1
							" . $queryconds;
		$results = $DB->query_first($sql);
		$query_to_cache = "SELECT *, title AS threadtitle,
					MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE ) AS score
					FROM " . TABLE_PREFIX . "thread
					WHERE MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE )
						AND forumid IN ($forumlist)
						AND visible=1
				";
		$this->sortby = 'score';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1');
		//写入查询表中
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'searchresult',
		));

		//积分更新
		$this->credit->update_credit('search', $bbuserinfo['id'], $bbuserinfo['usergroupid']);

		//跳转
		$forums->func->standard_redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;highlight=" . urlencode(trim($keywords)));
	}

	function searchinresults()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;

		//搜索行为影响的分值增减
		$this->credit->check_credit('search', $bbuserinfo['usergroupid']);

		$keywords = $this->search->filter_keywords($_INPUT['keywords']);

		$bboptions['minsearchlength'] = $bboptions['minsearchlength'] ? $bboptions['minsearchlength'] : 4;

		if (utf8_strlen($keywords) < $bboptions['minsearchlength'])
		{
			$forums->func->standard_error("keywordtooshort", false, $bboptions['minsearchlength']);
		}
		$results = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "search WHERE searchid='" . $this->search->uniqueid . "'");
		if (!$results['query'])
		{
			$forums->func->standard_error("searchresulttimeout");
		}
		$prequery = $results['query'];
		$uniqueid = md5(uniqid(microtime()));

		$ft = duality_word($keywords);
		$ft = implode(' ', $ft);
		$sql = "SELECT COUNT(*) AS count
						FROM (" . $prequery . ") AS thread
						WHERE MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE )
							" . $queryconds;
		$results = $DB->query_first($sql);
	 	$query_to_cache = "SELECT *, title AS threadtitle,
					MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE ) AS score
					FROM (" . $prequery . ") AS t
					WHERE MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE )
				";
		$this->sortby = 'score';
		$this->order = 'DESC';
		$DB->query($query_to_cache . ' ORDER BY ' . $this->sortby . ' ' . $this->order . ' LIMIT 1'); //检测是否有数据库错误，一旦出错不进入数据库
		//写入查询表中
		$DB->insert(TABLE_PREFIX . 'search', array(
			'searchid' => $uniqueid,
			'dateline' => TIMENOW,
			'maxrecord' => $results['count'],
			'sortby' => $this->sortby,
			'sortorder' => $this->order,
			'userid' => $bbuserinfo['id'],
			'host' => IPADDRESS,
			'query' => $query_to_cache,
			'searchype' => 'searchresult',
			'presearchid' => $this->search->uniqueid,
		));

		//积分更新
		$this->credit->update_credit('search', $bbuserinfo['id'], $bbuserinfo['usergroupid']);
		//跳转
		$forums->func->standard_redirect("search.php{$forums->sessionurl}do=show&amp;searchid=$uniqueid&amp;highlight=" . urlencode(trim($keywords)));

	}

	function show_results()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$hignlight = '&amp;hignlight=' . $this->search->highlight;
		$this->search->query_results();
		$pagelinks = $this->search->pagenavlink;
		$allthread = $this->search->threads;

		//加载ajax
		$mxajax_register_functions = array(
						'open_close_thread',
						'change_thread_attr',
						'change_thread_title',); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_thread.js');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['searchresult'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['searchresult']);
		$show['threadmode'] = true;
		$thread['cansee'] = true;
		$searchid = $this->search->uniqueid;
		$search_type = 'search';
		include $forums->func->load_template('find_posts');
		exit;
	}
}

$output = new search();
$output->show();

?>