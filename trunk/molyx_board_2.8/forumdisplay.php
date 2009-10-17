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
# $Id: forumdisplay.php 461 2008-01-22 08:42:38Z develop_tong $
# **************************************************************************#
define('THIS_SCRIPT', 'forumdisplay');
require_once('./global.php');

class forum
{
	var $posthash = '';
	var $threadread = array();
	var $forum = array();
	var $newpost = 0;
	var $can_edit_thread_title = false; //是否可以编辑主题标题
	var $can_open_close_thread = false; //是否可以开放和关闭主题
	var $is_mod = false; //是否是版主
	var $extra = '';
	var $moderator = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$forums->func->load_lang('forumdisplay');
		$forumid = intval($_INPUT['f']);

		if ($_INPUT['f'] !== $forumid)
		{
			switch ($_INPUT['f'])
			{
				case 'search':
					$goto = 'search.php';
				break;

				case 'wol':
					$goto = 'online.php';
				break;

				case 'cp':
					$goto = 'usercp.php';
				break;

				case 'faq':
					$goto = 'faq.php';
				break;

				case 'home':
					$goto = $bboptions['forumindex'];
				break;
			}

			if ($goto)
			{
				$forums->func->standard_redirect($goto . $forums->si_sessionurl);
			}
		}

		$forums->forum->forums_init($forumid);
		$this->forum = $forums->forum->single_forum($forumid);
		if (!$this->forum['id'])
		{
			$forums->func->standard_error("cannotfindforum");
		}
		$forums->forum->load_forum_style($this->forum['style']);
		$this->forum['forum_jump'] = $forums->func->construct_forum_jump();
		if ($_INPUT['pwd'])
		{
			$this->check_permissions();
		}
		else
		{
			$forums->forum->check_permissions($this->forum['id'], 1);
		}

		$this->posthash = $forums->func->md5_check();
		if ($read = $forums->func->get_cookie('threadread'))
		{
			$this->threadread = unserialize($read);
		}
		if ($this->forum['allowposting'])
		{
			$this->render_forum($this->forum['id']);
		}
		else
		{
			$this->show_subforums($this->forum['id']);
		}
	}

	function show_subforums($fid)
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$pagetitle = $this->forum['name'] . ' - ' . $bboptions['bbtitle'];
		$nav = $forums->forum->forums_nav($this->forum['id']);
		$rsslink = true;
		include $forums->func->load_template('forumdisplay');
		exit;
	}

	function check_permissions()
	{
		global $forums, $_INPUT;
		if ($_INPUT['password'] == '')
		{
			$forums->func->standard_error("requiredpassword");
		}
		if ($_INPUT['password'] != $this->forum['password'])
		{
			$forums->func->standard_error("errorforumpassword");
		}
		$forums->func->set_cookie('forum_' . $this->forum['id'], md5($_INPUT['password']));
		$forums->func->standard_redirect("forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}");
	}

	function render_forum($fid)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->recycleforum = $bboptions['recycleforumid'];
		$posthash = $this->posthash;
		$forum = $this->forum;
		$forums->func->check_cache('announcement');
		$show['announce'] = false;
		if (is_array($forums->cache['announcement']) && count($forums->cache['announcement']))
		{
			$announcement = array();
			foreach($forums->cache['announcement'] as $id => $announce)
			{
				if ($announce['forumid'] == -1 || strpos(",{$announce['forumid']},", ",{$this->forum['id']},") !== false)
				{
					$show['announce'] = true;
					if ($announce['startdate'])
					{
						$announce['startdate'] = $forums->func->get_date($announce['startdate'], 'Y-m-d');
					}
					else
					{
						$announce['startdate'] = '&nbsp;';
					}
					$announce['avatar'] = $forums->func->get_avatar($announce['userid'], $announce['avatar'], 2);
					$announcement[$id] = $announce;
				}
			}
		}
		if ($_INPUT['pp'])
		{
			$firstpost = intval($_INPUT['pp']);
		}
		else
		{
			$firstpost = 0;
			if ($this->forum['allowposting'] && $this->forum['forumrule'])
			{
				$forumrule_content = $this->load_forum_rule();
			}
		}

		$_INPUT['lastvisit'] = max($forums->forum_read[$_INPUT['f']], $_INPUT['lastvisit']);

		$daysprune = 100;

		if (isset($_INPUT['daysprune']) && $_INPUT['daysprune'])
		{
			$daysprune = $_INPUT['daysprune'];
			$this->extra .= "&amp;daysprune=$daysprune";
		}
		else if (isset($this->forum['prune']) && $this->forum['prune'])
		{
			$daysprune = $this->forum['prune'];
		}

		$sortby = 'lastpost';
		if (isset($_INPUT['sortby']) && $_INPUT['sortby'])
		{
			$this->extra .= "&amp;sortby={$_INPUT['sortby']}";
			$sortby = $_INPUT['sortby'];
		}
		else if (isset($this->forum['sortby']) && $this->forum['sortby'])
		{
			$sortby = $this->forum['sortby'];
		}

		$threadfilter = isset($_INPUT['filter']) ? $_INPUT['filter'] : 'all';
		$threadprune = ($daysprune != 100) ? TIMENOW - ($daysprune * 86400) : 0;
		$bboptions['maxthreads'] = $bboptions['maxthreads'] ? $bboptions['maxthreads'] : 20;

		if ($this->forum['specialtopic'])
		{
			$forums->func->check_cache('st');
			$forums->cache['st'][0]['name'] = $forums->lang['st_all'];
			$this->forum['specialtopic'] = '0,' . $this->forum['specialtopic'];
			$st = explode(',', $this->forum['specialtopic']);
			$specialtopic = '<ul class="forum_st">';
			foreach ($st AS $st_id)
			{
				if($st_id == intval($_INPUT['st']))
				{
					$st_class = ' class="cur"';
				}
				else
				{
					$st_class = '';
				}
				$specialtopic .= "<li><a {$st_class} href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}&amp;st={$st_id}'>{$forums->cache['st'][$st_id]['name']}</a></li>";
			}
			$specialtopic .= '</ul>';
		}

		$sort = array(
			'lastpost' => $forums->lang['lastpost'],
			'lastposter' => $forums->lang['lastposter'],
			'title' => $forums->lang['title'],
			'postusername' => $forums->lang['postusername'],
			'dateline' => $forums->lang['dateline'],
			'attach' => $forums->lang['attach'],
			'post' => $forums->lang['post'],
			'views' => $forums->lang['views'],
			'digg_exps' => $forums->lang['digg_exps'],
		);

		$prune_by_day = array(
			'1' => $forums->lang['today'],
			'5' => $forums->lang['from'] . ' 5 ' . $forums->lang['days'],
			'7' => $forums->lang['from'] . ' 7 ' . $forums->lang['days'],
			'10' => $forums->lang['from'] . ' 10 ' . $forums->lang['days'],
			'15' => $forums->lang['from'] . ' 15 ' . $forums->lang['days'],
			'20' => $forums->lang['from'] . ' 20 ' . $forums->lang['days'],
			'25' => $forums->lang['from'] . ' 25 ' . $forums->lang['days'],
			'30' => $forums->lang['from'] . ' 30 ' . $forums->lang['days'],
			'60' => $forums->lang['from'] . ' 60 ' . $forums->lang['days'],
			'90' => $forums->lang['from'] . ' 90 ' . $forums->lang['days'],
			'100' => $forums->lang['fromboardopen'],
		);

		$filter = array(
			'all' => $forums->lang['allthread'],
			'open' => $forums->lang['openthread'],
			'closed' => $forums->lang['closedthread'],
			'quintessence' => $forums->lang['quintessencethread'],
			'hot' => $forums->lang['hotthread'],
			'poll' => $forums->lang['poll'],
			'moved' => $forums->lang['movedthread'],
		);

		if ($bbuserinfo['is_mod'])
		{
			$filter['visible'] = $forums->lang['visiblethread'];
		}
		if ($bbuserinfo['id'])
		{
			$filter['started'] = $forums->lang['istarted'];
			$filter['replied'] = $forums->lang['ireplied'];

		}

		if (!isset($filter[$threadfilter]) || !isset($sort[$sortby]) || !isset($prune_by_day[$daysprune]))
		{
			$forums->func->standard_error("errororderlist");
		}

		$r_sort_by = (strtolower($sort_by) == 'asc') ? 'ASC' : 'DESC';
		$queryarray = array();
		$addquery = '';
		switch ($threadfilter)
		{
			case 'all':
			break;

			case 'open':
				$queryarray[] = 'open = 1';
			break;

			case 'closed':
				$queryarray[] = 'open = 0';
			break;

			case 'quintessence':
				$queryarray[] = 't.quintessence = 1';
			break;

			case 'hot':
				$queryarray[] = 'open = 1 AND post + 1 >= ' . intval($bboptions['hotnumberposts']);
			break;

			case 'moved':
				$queryarray[] = 'open = 2';
			break;

			case 'poll':
				$queryarray[] = 'pollstate = 1';
			break;

			default:
			break;
		}

		if ($bbuserinfo['is_mod'] && $threadfilter == 'visible')
		{
			$queryarray[] = 'visible = 0';
		}

		if (! $bbuserinfo['canviewothers'] || $threadfilter == 'started')
		{
			$queryarray[] = "postuserid = '{$bbuserinfo['id']}'";
		}
		//本版版主显示操作菜单
		$moderator_form_header = '';
		$mod = array();
		$this->moderator = $bbuserinfo['_moderator'][$this->forum['id']];
		if ($bbuserinfo['supermod'] || $this->moderator['moderatorid'])
		{
			//版主操作菜单列表
			$moderator_form_header = '<form name="modform" id="modform" method="post" action="">';
			$mod = $this->moderation_panel();
			$this->is_mod = true;
			$this->can_edit_thread_title = true;
			$this->can_open_close_thread = true;
			$can_edit_forumrule = true;
			$forum['change_forumrule_event'] = ' ondblclick="change_forumrule(' . $forum['id'] . ', 0)";';

			if($bbuserinfo['supermod'])
			{
				load_editor_js('', 'quick');
			}
			else
			{
				if (!$this->moderator['caneditthreads'])
				{
					$this->can_edit_thread_title = false;
				}
				if (!$this->moderator['canopenclose'])
				{
					$this->can_open_close_thread = false;
				}
				if (!$this->moderator['caneditrule'])
				{
					$can_edit_forumrule = false;
				}
				else
				{
					load_editor_js('', 'quick');
				}
			}
		}
		if ($_INPUT['st'])
		{
			$queryarray[] = 'stopic = ' . intval($_INPUT['st']);
			$this->extra .= "&amp;st={$_INPUT['st']}";
		}

		if (!empty($queryarray))
		{
			$addquery = ' AND ' . implode(' AND ', $queryarray);
		}

		if (!$bbuserinfo['is_mod'])
		{
			$visible = ' AND visible = 1';
		}
		else
		{
			$visible = '';
		}

		if ($threadfilter == 'replied')
		{
			$prune_filter = $threadprune ? "and (sticky != 0 OR lastpost > $threadprune)" : '';
			$threadscount = $DB->query_first("SELECT COUNT(DISTINCT(p.threadid)) AS threads
				FROM " . TABLE_PREFIX . "thread t
					LEFT JOIN " . TABLE_PREFIX . "post p
						ON (p.threadid = t.tid
							AND p.userid='{$bbuserinfo['id']}'
							AND p.newthread=0)
				WHERE t.forumid = '{$this->forum['id']}' {$visible} {$prune_filter}");
		}
		else if ($addquery || $threadprune)
		{
			$threadscount = $DB->query_first('SELECT COUNT(*) AS threads
				FROM ' . TABLE_PREFIX . "thread t
			WHERE forumid = '{$this->forum['id']}' {$visible}
				AND (sticky != 0
				OR lastpost > {$threadprune}){$addquery}");
		}
		else
		{
			$threadscount['threads'] = $this->forum['this_thread'];
			$threadprune = 0;
		}

		if ($_INPUT['filter'])
		{
			$this->extra .= "&amp;filter=$threadfilter";
		}
		$forum['pagenav'] = $forums->func->build_pagelinks(array(
			'totalpages' => $threadscount['threads'],
			'perpage' => $bboptions['maxthreads'],
			'curpage' => $firstpost,
			'pagelink' => "forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}{$this->extra}",
		));

		if ($threadscount['threads'] < 1)
		{
			$show['nopost'] = true;
		}

		$threadids = array();
		$thread_sort = '';
		if ($bboptions['threadpreview'] == 1)
		{
			$previewfield = 'p.pagetext AS preview, p.hidepost, ';
			$previewjoin = "LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid = t.firstpostid)";
		}
		else if ($bboptions['threadpreview'] == 2)
		{
			$previewfield = 'p.pagetext AS preview, p.hidepost, ';
			$previewjoin = "LEFT JOIN " . TABLE_PREFIX . "post p ON (p.pid = t.lastpostid)";
		}
		else
		{
			$previewfield = '';
			$previewjoin = '';
		}

		$shownormal = false;
		$threadlist = array();
		if ($this->extra || $_INPUT['pp'])
		{
			$this->extra .= "&amp;pp={$_INPUT['pp']}";
			$this->extra = urlencode(str_replace('&amp;', '&', $this->extra));
		}
		$forums->func->check_cache('icon');
		$query = "forumid=" . $this->forum['id']  . $visible . ' AND sticky <= 1';
		if ( $threadprune )
		{
			$query .= " AND (t.lastpost > {$threadprune})";
		}
		$threads = array();
		if ($threadfilter == 'replied')
		{
			$previewjoin = 'LEFT JOIN ' . TABLE_PREFIX . "post p ON (p.threadid = t.tid AND p.userid = '{$bbuserinfo['id']}')";
			$previewjoin .= 'LEFT JOIN ' . TABLE_PREFIX . "user u ON t.postuserid = u.id";
			$previewfield = 'u.avatar, ';
			//回收站不显示总置顶
			if ((!$bboptions['enablerecyclebin'] || $bboptions['recycleforumid'] != $this->forum['id']) && !$_INPUT['pp'])
			{
				$sql =  "SELECT $previewfield t.*
				FROM " . TABLE_PREFIX . "thread t $previewjoin
				WHERE sticky > 1 AND stickforumid IN (" . $this->forum['parentlist'] . ",0)
					AND p.newthread=0
				ORDER BY sticky DESC, t.$sortby $r_sort_by";
				$result = $DB->query($sql);
				while ($row = $DB->fetch_array($result))
				{
					$threads[] = $row;
				}
			}
			$sql = "SELECT {$previewfield}DISTINCT(p.userid), t.*
				FROM " . TABLE_PREFIX . "thread t $previewjoin
				WHERE {$query}
					AND p.newthread=0
				ORDER BY sticky DESC, $sortby $r_sort_by
				LIMIT $firstpost, {$bboptions['maxthreads']}"
			;
		}
		else
		{
			$previewjoin .= 'LEFT JOIN ' . TABLE_PREFIX . "user u ON t.postuserid = u.id";
			$previewfield .= 'u.avatar, ';
			//回收站不显示总置顶
			if ((!$bboptions['enablerecyclebin'] || $bboptions['recycleforumid'] != $this->forum['id']) && !$_INPUT['pp'])
			{
				$sql =  "SELECT $previewfield t.*
				FROM " . TABLE_PREFIX . "thread t $previewjoin
				WHERE sticky > 1 AND stickforumid IN (" . $this->forum['parentlist'] . ",0) $addquery
				ORDER BY sticky DESC, t.$sortby $r_sort_by";
				$result = $DB->query($sql);
				while ($row = $DB->fetch_array($result))
				{
					$threads[] = $row;
				}
			}
			$sql = "SELECT $previewfield t.*
				FROM " . TABLE_PREFIX . "thread t $previewjoin
				WHERE $query $addquery
				ORDER BY sticky DESC, t.$sortby $r_sort_by
				LIMIT $firstpost, {$bboptions['maxthreads']}";

		}

		$result = $DB->query($sql);
		while ($row = $DB->fetch_array($result))
		{
			$threads[] = $row;
		}
		foreach ($threads AS $row)
		{
			$threadids[$t['tid']] = $row['tid'];
			$thread = $this->parse_data($row);
			if ($row['sticky'])
			{
				if ($row['sticky'] != 1 && $row['sticky'] != 99)
				{
					$thread['sticky'] = 2;
				}
			}
			else
			{
				if (!$shownormal)
				{
					$thread['shownormal'] = true;
					$shownormal = true;
				}
			}
			if ($thread['pollstate'] == 1)
			{
				$thread['thread_icon'] = 2;
			}
			else
			{
				$thread['thread_icon'] = 1;
			}
			$thread['avatar'] = $forums->func->get_avatar($row['postuserid'], $row['avatar'], 2);

			$thread['digg_users'] = sprintf($forums->lang['how_digg_users'], intval($thread['digg_users']));
			$thread['digg_exps'] = intval($thread['digg_exps']);
			$thread['digg_exps'] = $thread['digg_exps'] ? $thread['digg_exps'] : '&nbsp;' ;
			$threadlist[] = $thread;
		}
		$show['sort_by'] = select_options($sort, $sortby);
		$show['sort_prune'] = select_options($prune_by_day, $daysprune);
		$show['thread_filter'] = select_options($filter, $threadfilter);

		if ($this->newpost < 1)
		{
			$forums->forum_read[$this->forum['id']] = TIMENOW;
			$forums->forum->forumread(1);
		}

		// 查看论坛的用户
		if ($bboptions['showforumusers'])
		{
			$forums->func->check_cache('usergroup');
			$online = array('guests' => 0, 'invisible' => 0, 'users' => 0, 'username' => '');

			$this_user = array(
				'userid' => $bbuserinfo['id'],
				'username' => $bbuserinfo['name'],
				'usergroupid' => $bbuserinfo['usergroupid'],
				'invisible' => $bbuserinfo['invisible'],
				'lastactivity' => TIMENOW,
			);
			$this->count_online($this_user, $online);

			$time = TIMENOW - (($bboptions['cookietimeout'] != '') ? $bboptions['cookietimeout'] * 60 : 900);
			$result = $DB->query("SELECT s.userid, s.username, s.usergroupid, s.invisible, s.location, s.mobile
				FROM " . TABLE_PREFIX . "session s
				WHERE s.lastactivity > $time
					AND s.inforum = {$this->forum['id']}
					AND s.badlocation != 1
				ORDER BY s.lastactivity DESC");
			while ($row = $DB->fetch_array($result))
			{
				$this->count_online($row, $online);
			}

			$online['total'] = $online['users'] + $online['guests'];
			$forums->lang['onlineusers'] = sprintf($forums->lang['onlineusers'], $online['total'], $online['users'], $online['guests'], $online['invisible']);
		}
		//取本版面推荐主题
		if ($bboptions['commend_thread_num'])
		{
			$forums->func->check_cache('forum_commend_thread_' . $this->forum['id'], 'forum_commend_thread');
			$forum_commend_thread = $forums->cache['forum_commend_thread_' . $this->forum['id']];
			if ($forum_commend_thread)
			{
				foreach ($forum_commend_thread AS $tid => $c_thread)
				{
					$forum_commend_thread[$tid]['cuttitle'] = $forums->func->fetch_trimmed_title(strip_tags($c_thread['title']), 20);
					$forum_commend_thread[$tid]['dateline'] = $forums->func->get_date($c_thread['dateline'], 4);
					$forum_commend_thread[$tid]['avatar'] = $forums->func->get_avatar($c_thread['postuserid'], $c_thread['avatar'], 2);
				}
			}
		}

		//版面内活跃会员
		if ($bboptions['forum_active_user'])
		{
			$forums->func->check_cache('forum_active_user_' . $this->forum['id'], 'forum_active_user');
			$forum_active_user = $forums->cache['forum_active_user_' . $this->forum['id']];
			if ($forum_active_user)
			{
				foreach ($forum_active_user AS $uid => $user)
				{
					$forum_active_user[$uid]['cutname'] = $forums->func->fetch_trimmed_title(strip_tags($user['name']), 4);
					$forum_active_user[$uid]['lastactivity'] = $forums->func->get_date($user['lastactivity'], 2);
					$forum_active_user[$uid]['avatar'] = $forums->func->get_avatar($uid, $user['avatar'], 1);
				}
			}
		}
		//自定义版面模块
		$forums->func->check_cache('forum_area_' . $this->forum['id'], 'forum_area');
		$forum_area = $forums->cache['forum_area_' . $this->forum['id']];

		$mxajax_register_functions = array(
						'open_close_thread',
						'change_thread_attr',
						'change_thread_title',
						'do_change_forumrule',
						'digg_thread',
						); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$forum_navigation = $forums->forum->fetch_forum_guide();
		$moderator = $forums->forum->forums_moderator($this->forum['id']);
		$pagetitle = strip_tags($this->forum['name']) . ' - ' . $bboptions['bbtitle'];
		$nav = $forums->forum->forums_nav($this->forum['id']);
		$rsslink = true;
		$referer = SCRIPTPATH;
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_thread.js');
		include $forums->func->load_template('forumdisplay');
		exit;
	}

	function parse_data($thread)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$last_time = ($this->threadread[$thread['tid']] > $_INPUT['lastvisit']) ? $this->threadread[$thread['tid']] : $_INPUT['lastvisit'];
		$maxposts = $bboptions['maxposts'] ? $bboptions['maxposts'] : '10';
		if ($thread['attach'])
		{
			$thread['attach_img'] = 1;
		}
		$thread['lastposter'] = $thread['lastposterid'] ? $forums->func->fetch_user_link($thread['lastposter'], $thread['lastposterid']) : "-{$thread['lastposter']}-";
		$thread['postusername'] = $thread['postuserid'] ? $forums->func->fetch_user_link($thread['postusername'], $thread['postuserid']) : "{$thread['postusername']}*";
		$thread['folder_img'] = $forums->func->folder_icon($thread, $last_time, $this->can_open_close_thread);
		$thread['cache_icon'] = $forums->cache['icon'][$thread['iconid']]['image'];
		if ($thread['cache_icon'] == '')
		{
			$thread['cache_icon'] = 'post.gif';
		}
		$thread['thread_icon'] = $thread['iconid'] ? 1 : 0;
		$thread['quintess'] = $thread['quintessence'] ? 1 : 0;
		if ($thread['stopic'] && $forums->cache['st'][$thread['stopic']]['name'])
		{
			$thread['specialtopic'] = "[<a href='forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id'] . "&amp;st={$thread['stopic']}&amp;extra={$this->extra}'>" . $forums->cache['st'][$thread['stopic']]['name'] . "</a>]  ";
		}
		$thread['dateline'] = $forums->func->get_date($thread['dateline'], 3);
		$thread['showpages'] = $forums->func->build_threadpages(array(
			'id' => $thread['tid'],
			'totalpost' => $thread['post'],
			'perpage' => $maxposts,
			'extra' => $this->extra,
		));
		$thread['post'] = fetch_number_format(intval($thread['post']));
		$thread['views'] = fetch_number_format(intval($thread['views']));
		if ($last_time && ($thread['lastpost'] > $last_time))
		{
			$this->newpost++;
			$thread['gotonewpost'] = 1;
		}
		else
		{
			$thread['gotonewpost'] = 0;
		}
		$thread['lastpost'] = $forums->func->get_date($thread['lastpost'], 1);
		if (isset($thread['preview']) && $bboptions['threadpreview'] > 0)
		{
			$thread['info'] = $forums->lang['postusername'] . ': ' . $thread['postusername'] . "\n";
			$thread['info'] .= $forums->lang['dateline'] . ': ' . $thread['dateline'] . "\n";
			$thread['info'] .= $forums->lang['lastpost'] . ': ' . $thread['lastpost'] . "\n";
			$thread['info'] .= $forums->lang['post'] . ': ' . $thread['post'] . ' | ';
			$thread['info'] .= $forums->lang['views'] . ': ' . $thread['views'] . "\n";
			$text = ($bboptions['threadpreview'] == 1) ? $forums->lang['threadinfo'] . ': ' : $forums->lang['replyinfo'] . ': ';
			$thread['preview'] = $thread['hidepost'] ? $forums->lang['_posthidden'] : strip_tags($thread['info'] . $text . $thread['preview']);
			$thread['preview'] = utf8_htmlspecialchars($forums->func->fetch_trimmed_title($thread['preview'], 200));
		}

		$thread['open'] = intval($thread['open']);
		if ($thread['open'] == 2)
		{
			$t_array = explode('&', $thread['moved']);
			$thread['tid'] = $t_array[0];
			$thread['forumid'] = $t_array[1];
			$thread['title'] = $thread['title'];
			$thread['views'] = '--';
			$thread['post'] = '--';
			$thread['prefix'] = ' ';
			$thread['gotonewpost'] = '';
		}
		//有权限改变主题标题
		if ($this->can_edit_thread_title || ($bbuserinfo['caneditthread'] && $thread['postuserid'] == $bbuserinfo['id']))
		{
			$thread['ajax_edit_thread_title'] = ' ondblclick="showthreadin(' . $thread['tid'] .');"';
			$thread['ajax_edit_thread_title_attr'] = '<span id="thread_c_b_' . $thread['tid'] . '"><img class="inline" src="images/' . $bbuserinfo['imgurl'] . '/op_title.gif" alt="' . $forums->lang['ajaxthreadcb'] . '" onclick="change_thread_attr(' . $thread['tid'] . ')" /></span>';
		}
		else
		{
			$thread['ajax_edit_thread_title'] = '';
			$thread['ajax_edit_thread_title_attr'] = '';
		}
		//是版主显示多选框
		if ($this->is_mod)
		{
			$thread['thread_checkbox'] = '<input type="checkbox" name="tid[]" id="tid' . $thread["tid"] . '" value="' . $thread["tid"] . '" />';
		}
		else
		{
			$thread['thread_checkbox'] = '';
		}
		if ($bbuserinfo['id'] && $bboptions['recycleforumid'] != $this->forum['id'])
		{
			$thread['digg_event'] = ' onclick="digg_thread(' . $thread['tid'] . ');"';
		}
		else
		{
			$thread['digg_event'] = ' style="cursor:default;"';
		}
		$thread['extra'] = $this->extra ? "&amp;extra={$this->extra}" : '';
		return $thread;
	}

	function moderation_panel()
	{
		global $bbuserinfo, $forums;
		if (!$bbuserinfo['id'])
		{
			return '';
		}
		$canmoderate = false;
		if ($bbuserinfo['supermod'] || $this->moderator['moderatorid'] != '')
		{
			$canmoderate = true;
		}
		if (!$canmoderate)
		{
			return '';
		}
		$modlink = '<ul>';
		if ($this->recycleforum && $this->recycleforum == $this->forum['id'])
		{
			$actions = array(
				'deletethreads' => array('candeletethreads', $forums->lang['threaddelete']),
				'revert' => array('candeletethreads', $forums->lang['threadrevert'])
			);
		}
		else
		{
			$actions = array(
				'openclose' => array('canopenclose', $forums->lang['openclose']),	//开放\关闭主题
				'stickorcancel' => array('canstickthread', $forums->lang['stickorcancel']),	//置顶\撤销主题
				'approveorcancel' => array('canmanagethreads', $forums->lang['approveorcancel']),	//验证\撤销主题
				'mergethreads' => array('canmergethreads', $forums->lang['threadmerge']),		//合并主题
				'deletethreads' => array('candeletethreads', $forums->lang['threaddelete']),		//删除主题
				'moveclearthreads' => array('canremoveposts', $forums->lang['moveclearthreads']),		//移动主题
				'quintessence' => array('canquintessence', $forums->lang['quintessence_op']),		//设置精华
				'dospecialtopic' => array('cansetst', $forums->lang['dospecialtopic']),
				'commend_thread' => array('modcancommend', $forums->lang['commend_thread']),		//推荐主题
			);
		}
		foreach($actions AS $this_action => $value)
		{
			if ($bbuserinfo['supermod'])
			{
				$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'tid\');">' . $value[1] . "</li>\n";
			}
			else if ($this->moderator)
			{
				if ($value[0])
				{
					if ($this->moderator[$value[0]])
					{
						$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'tid\');">' . $value[1] . "</li>\n";
					}
				}
				else
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'tid\');">' . $value[1] . "</li>\n";
				}
			}
			else if ($this_action == 'openclose')
			{
				if ($bbuserinfo['canopenclose'])
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'tid\');">' . $value[1] . "</li>\n";
				}
			}
			else if ($this_action == 'deletethreads')
			{
				if ($bbuserinfo['candeletethreads'])
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'tid\');">' . $value[1] . "</li>\n";
				}
			}
		}
		$modlink .= '</ul>';
		return $modlink;
	}

	function count_online(&$user, &$online)
	{
		global $forums, $bbuserinfo;
		static $cached = array();

		$user['lastactivity'] = $forums->func->get_time($user['lastactivity']);
		$user['opentag'] = $forums->cache['usergroup'][$user['usergroupid']]['opentag'];
		$user['closetag'] = $forums->cache['usergroup'][$user['usergroupid']]['closetag'];
		$user['usericon'] = $forums->func->get_avatar($user['userid'], $user['avatar'], 2);
		$user['mobile'] = $user['mobile'] ? 1 : 0;
		if ($user['userid'] == 0)
		{
			$online['guests']++;
			return;
		}
		else if (!isset($cached[$user['userid']]))
		{
			$cached[$user['userid']] = true;
			if ($user['invisible'] == 1)
			{
				if ($bbuserinfo['usergroupid'] == 4)
				{
					$user['show_icon'] = 1;
					$online['username'][] = $user;
				}
				$online['invisible']++;
			}
			else
			{
				$online['username'][] = $user;
			}
			$online['users']++;
		}
	}

	function load_forum_rule()
	{
		global $DB;
		$content = @file_get_contents(ROOT_PATH . 'cache/cache/rule_' . $this->forum['id'].'.txt');
		if (!$content)
		{
			$forumrule = $DB->query_first('SELECT forumrule
				FROM ' . TABLE_PREFIX . 'forum_attr
				WHERE forumid = ' . $this->forum['id']
			);
			$content = $forumrule['forumrule'];
			file_write(ROOT_PATH . "cache/cache/rule_{$this->forum['id']}.txt", $content);
		}
		return $content;
	}
}

$output = new forum();
$output->show();
?>