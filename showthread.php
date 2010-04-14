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
define('THIS_SCRIPT', 'showthread');
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
	var $hidefunc;
	var $lib;
	var $codeparse;
	var $parser;
	var $usecode = array();
	var $bankcredit = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions,$ip;
		$tid = intval($_INPUT['t']);
		if ($tid < 1)
		{
			$forums->func->standard_error('errorthreadlink');
		}
		$forums->func->load_lang('showthread');
		$forums->func->load_lang('post');
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		require_once(ROOT_PATH . 'includes/functions_moderate.php');
		$this->modfunc = new modfunctions();
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		$this->codeparse = new functions_codeparse();
		$this->posthash = $forums->func->md5_check();
		$this->recycleforum = $bboptions['recycleforumid'];
		$this->thread = $DB->query_first('SELECT *
			FROM ' . TABLE_PREFIX . "thread
			WHERE tid = '$tid'");
		if($_INPUT['r_forumid'])
		{
			$this->forum = $forums->forum->single_forum($_INPUT['r_forumid']);
			$this->thread['forumid'] = intval($_INPUT['r_forumid']);
		}
		else
		{
			$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		}
		if (!$this->forum['id'] || !$this->thread['tid'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$forums->forum->load_forum_style($this->forum['style']);
		if (!$this->can_moderate($this->forum['id']))
		{
			if ($this->thread['visible'] != 1)
			{
				$forums->func->standard_error("errorthreadlink");
			}
		}
		$this->thread['dateline'] = $forums->func->get_date($this->thread['dateline'], 2);
		$this->thread['lastpost'] = $forums->func->get_date($this->thread['lastpost'], 2);
		$this->thread['digg_users'] = sprintf($forums->lang['has_digg_users'], intval($this->thread['digg_users']));
		$this->thread['digg_exps'] = intval($this->thread['digg_exps']);
		$your_digg_exp = $forums->func->fetch_user_digg_exp();
		$your_digg_exp = sprintf($forums->lang['your_digg_exp'], '+' . $your_digg_exp);
		require_once(ROOT_PATH . 'includes/functions_showthread.php');
		$this->lib = new functions_showthread();
		require_once(ROOT_PATH . 'includes/class_textparse.php');

		$forums->func->check_cache('ranks');

		if ($this->thread['open'] == 2)
		{
			$f_stuff = explode('&', $this->thread['moved']);
			$forums->func->standard_redirect(ROOT_PATH . "showthread.php{$forums->sessionurl}t={$f_stuff[0]}");
		}
		$forums->forum->check_permissions($this->forum['id'], 1, 'thread', $this->thread['postuserid']);
		if ($threadread = $forums->func->get_cookie('threadread'))
		{
			$threadread = unserialize($threadread);
			if (!is_array($threadread))
			{
				$threadread = array();
			}
		}
		$this->forum['jump'] = $forums->func->construct_forum_jump();
		$this->pp = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		if (!$bbuserinfo['canviewothers'] && $this->thread['postuserid'] != $bbuserinfo['id'])
		{
			$forums->func->standard_error("cannotviewthread");
		}
		if ($bbuserinfo['id'])
		{
			$threadread[$this->thread['tid']] = TIMENOW;
			$forums->func->set_cookie('threadread', serialize($threadread), -1);
			if ($this->recycleforum != $this->forum['id'])
			{
				$digg_event = ' onclick="digg_thread(' . $this->thread['tid'] . ');"';
			}
			else
			{
				$digg_event = ' style="cursor:default;"';
			}
		}
		else
		{
			$digg_event = ' style="cursor:default;"';
		}
		$this->maxposts = $bboptions['maxposts'] ? $bboptions['maxposts'] : '10';
		if ($bbuserinfo['id'] && !$bbuserinfo['supermod'])
		{
			$this->moderator = $bbuserinfo['_moderator'][ $this->forum['id'] ];
		}
		$this->thread['replybutton'] = $this->reply_button();
		$_INPUT['highlight'] = isset($_INPUT['highlight']) ? $_INPUT['highlight'] : '';
		if ($_INPUT['highlight'])
		{
			$highlight = '&amp;highlight=' . $_INPUT['highlight'];
		}
		if ($this->can_moderate($this->forum['id']))
		{
			$this->thread['post'] += intval($this->thread['modposts']);
		}
		if ($_INPUT['extra'])
		{
			$extra = '&amp;extra=' . urlencode(str_replace("&amp;", "&", $_INPUT['extra']));
		}
		$this->thread['pagenav'] = $forums->func->build_pagelinks(array(
			'totalpages' => ($this->thread['post'] + 1),
			'perpage' => $this->maxposts,
			'curpage' => $_INPUT['pp'],
			'pagelink' => "showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . $highlight . $extra,
		));
		if (($this->thread['post'] + 1) > $this->maxposts)
		{
			$this->thread['gotonew'] = "<a href='redirect.php{$forums->sessionurl}f=" . $this->forum['id'] . "&amp;t=" . $this->thread['tid'] . "&amp;goto=newpost'>" . $forums->lang['gotonewpost'] . "</a>";
		}
		if ($bboptions['threadviewsdelay'])
		{
			file_write(ROOT_PATH . 'cache/cache/threadviews.txt', $tid . "\n", 'a');
		}
		else
		{
			$DB->shutdown_update(TABLE_PREFIX . 'thread', array('views' => array(1, '+')), 'tid = ' . $this->thread['tid']);
		}
		$caneditposts = false;
		$candeleteposts = false;
		if ($this->moderator['caneditposts'])
		{
			$caneditposts = true;
		}
		if ($this->moderator['candeleteposts'])
		{
			$candeleteposts = true;
		}
		if ($bbuserinfo['supermod'])
		{
			$caneditposts = true;
			$candeleteposts = true;
		}
		if ($this->thread['pollstate'])
		{
			$show['poll'] = true;
			$poll_footer = '';
			$poll_data = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . 'poll
				WHERE tid = ' . $this->thread['tid']);
			if (!$poll_data['pollid'])
			{
				return;
			}
			if (!$poll_data['question'])
			{
				$poll_data['question'] = $this->thread['title'];
			}
			if (!$bbuserinfo['id'])
			{
				$show['results'] = true;
				$poll_footer = $forums->lang['guestcannotpoll'];
			}
			else
			{
				$delete_link = '';
				$edit_link = '';
				if ($caneditposts)
				{
					$edit_poll = "[ <a href=\"moderate.php{$forums->sessionurl}do=editpoll&amp;f=" . $this->forum['id'] . "&amp;t=" . $this->thread['tid'] . "&amp;posthash=" . $this->posthash . "\">" . $forums->lang['edit'] . "</a> ]";
				}
				if ($candeleteposts)
				{
					$delete_poll = "[ <a href=\"moderate.php{$forums->sessionurl}do=deletepoll&amp;f=" . $this->forum['id'] . "&amp;t=" . $this->thread['tid'] . "&amp;posthash=" . $this->posthash . "\">" . $forums->lang['delete'] . "</a> ]";
				}
				$poll_data['voters'] = ',' . $poll_data['voters'];
				if (preg_match("#\," . $bbuserinfo['id'] . "\,#", $poll_data['voters']))
				{
					$show['results'] = true;
					$poll_footer = $forums->lang['youvoted'];
				}
				if ($this->thread['open'] == 0)
				{
					$show['results'] = true;
					$poll_footer = '&nbsp;';
				}
				if ($bboptions['allowviewresults'])
				{
					if ($_INPUT['mode'] == 'showpoll')
					{
						$show['results'] = true;
						$poll_footer = "";
					}
				}
			}
			$polloptions = unserialize($poll_data['options']);
			reset($polloptions);
			if ($show['results'])
			{
				$votetotal = 0;
				foreach ($polloptions as $entry)
				{
					$entry['id'] = intval($entry[0]);
					$entry['choice'] = $entry[1];
					$entry['votes'] = intval($entry[2]);
					$votetotal += $entry['votes'];
					if (strlen($entry['choice']) < 1) continue;
					$entry['percent'] = $entry['votes'] == 0 ? 0 : $entry['votes'] / $poll_data['votes'] * 100;
					$entry['percent'] = sprintf('%.2f' , $entry['percent']);
					$entry['width'] = $entry['percent'] > 0 ? (int) $entry['percent'] * 2 : 0;
					$voters[$entry['id']] = $entry;
				}
				$votetotal = $votetotal == $poll_data['votes'] ? $votetotal : $poll_data['votes'];
			}
			else
			{
				$question = $poll_data['question'];
				foreach ($polloptions AS $entry)
				{
					$entry['id'] = intval($entry[0]);
					$entry['choice'] = $entry[1];
					$entry['votes'] = intval($entry[2]);
					if (strlen($entry['choice']) < 1) continue;
					$voters[$entry['id']] = $entry;
				}
			}
			if (empty($poll_footer))
			{
				if ($bboptions['allowviewresults'])
				{
					if ($_INPUT['mode'] == 'showpoll')
					{
						$showresult = "<input type='button' class='button' name='viewresult' value='" . $forums->lang['showoptions'] . "'  title='" . $forums->lang['returnandshow'] . "' onclick='show_votes()' />";
					}
					else
					{
						$showresult = "<input type='button' value='" . $forums->lang['showresults'] . "' title='" . $forums->lang['viewresults'] . "' class='button' onclick='get_votes()' />";
						$votepoll = "<input type='submit' name='submit' value=' " . $forums->lang['poll'] . " ' class='button' title='" . $forums->lang['addpoll'] . "' />";
					}
				}
				else
				{
					$votepoll = "<input type='submit' name='submit' value=' " . $forums->lang['poll'] . " ' class='button' title='" . $forums->lang['addpoll'] . "' />";
					$showresult = "<input type='submit' name='nullvote' class='button' value='" . $forums->lang['addemptypoll'] . "' title='" . $forums->lang['viewresultsnotpoll'] . "' />";
				}
			}
			else
			{
				$votepoll = $poll_footer;
			}
		}
		if ($bboptions['cookietimeout'] == '')
		{
			$bboptions['cookietimeout'] = 15;
		}
		if ($this->forum['moderatepost'])
		{
			$moderate = ' AND p.moderate=0';
			if ($this->can_moderate($this->thread['forumid']))
			{
				$moderate = '';
				if ($_INPUT['modfilter'] == 'invisiblepost')
				{
					$moderate = ' AND p.moderate=1';
				}
			}
		}
		else
		{
			$moderate = '';
		}
		$thread = $this->thread;
		$forum = $this->forum;
		$posthash = $this->posthash;
		$showpost = array();
		$posttable = $this->thread['posttable'] ? $this->thread['posttable'] : 'post';

		$post = $DB->query('SELECT up.*, p.*, u.id,u.name,u.usergroupid,u.gender,u.email,u.joindate,u.quintessence, u.posts, u.lastvisit, u.lastactivity,u.options,u.options, u.signature, u.usercurdo, u.userdotime, u.avatar, g.*, m.forumid as moderatorfid
			FROM ' . TABLE_PREFIX . $posttable . ' p
				LEFT JOIN ' . TABLE_PREFIX . 'user u ON (p.userid = u.id)
				LEFT JOIN ' . TABLE_PREFIX . 'usergroup g ON (g.usergroupid = u.usergroupid)
				LEFT JOIN ' . TABLE_PREFIX . 'userexpand up ON (up.id = p.userid)
				LEFT JOIN ' . TABLE_PREFIX . "moderator m ON (p.userid = m.userid AND m.forumid = '{$this->thread['forumid']}')
			WHERE p.threadid = '{$this->thread['tid']}'  $moderate
			ORDER BY p.pid
			LIMIT {$this->pp}, {$this->maxposts}");
		$allpostrows = $attachment_inpost = array();

		while ($row = $DB->fetch_array($post))
		{
			if ($row['userid'] == $bbuserinfo['id'])
			{
				$this->already_replied = true;
			}
			//已删除贴子对于一般会员显示方式
			if ($row['state'] == '2' && !$bbuserinfo['supermod'] && $this->moderator['forumid'] != $this->forum['id'])
			{
				$row['pagetext'] = "<!--editpost--><br /><br /><br /><div><font class='editinfo'>{$row['logtext']}</font></div><!--editpost1-->";
			}
			$allpostrows[$row['pid']] = $row;
		}
		$pids = array_keys($allpostrows);
		if ($this->thread['attach'])
		{
			$attachment = $this->lib->parse_attachment($pids, 'postid', $this->thread['tid'], $this->thread['posttable']);
			$attachment_inpost = $attachment['attachments_inpost'];
			$attachment = $attachment['attachments'];
		}
		$forums->func->check_cache('usergroup');
		$forums->func->check_cache('icon');
		$forums->func->check_cache('creditlist');
		if ($this->moderator['bantimelimit'])
		{
			$bantimelimit = $this->moderator['bantimelimit'];
		}
		$fid = $this->forum['id'];

		foreach ($allpostrows as $row)
		{
			$this->canview_hidecontent = false;
			$row['attachment_inpost'] = ($attachment_inpost[$row['pid']]) ? $attachment_inpost[$row['pid']] : '';

			$return = $this->parse_row($row);
			$return['row']['stype'] = ($bbuserinfo['id'] != $return['row']['userid'])?1:0;

			$return['row']['banpost'] = false;
			if (($this->moderator['forumid'] == $this->forum['id'] && ($this->moderator['canbanpost'] || $this->moderator['canbanuser']) && !$return['row']['supermod'] && $return['row']['moderatorfid'] != $this->forum['id']))
			{
				$return['row']['banpost'] = true;
			}
			elseif ($this->moderator['isgroup'] && $bbuserinfo['usergroupid'] == $this->moderator['usergroupid'] && !$return['row']['supermod'])
			{
				if ($bbuserinfo['id'] != $return['row']['userid'])
				{
					$return['row']['banpost'] = true;
				}
			}
			elseif ($bbuserinfo['supermod'])
			{
				if ($bbuserinfo['id'] != $return['row']['userid'])
				{
					$return['row']['banpost'] = true;
				}
			}
			//ajax编辑签名

			if (($bbuserinfo['id'] == $return['row']['userid'] || $bbuserinfo['supermod']))
			{
				$return['poster']['ajaxeditsigevent'] = " ondblclick = \"edit_signature_event('{$return['row']['pid']}','{$return['row']['userid']}', '450', 1);\"";
			}
			else
			{
				$return['poster']['ajaxeditsigevent'] = '';
			}
			$return['poster']['expand_credit'] = '';
			if (is_array($forums->cache['creditlist']))
			{
				foreach ($forums->cache['creditlist'] as $creditid => $v)
				{
					if (!$v['used']) continue;
					$return['poster']['expand_credit'] .=  '<li>' . $v['name'] . ": " . intval($return['poster'][$v['tag']]) . $v['unit'] . "</li>";
				}
			}

			if($attachment[$row['pid']])
			{
				$return['attachment'] = $attachment[$row['pid']];
			}
			//帖子更新信息
			$return['row']['updatetime'] = $forums->func->get_date($row['updatetime'], 2);
			$return['row']['updatelog'] = sprintf($forums->lang['postupdatelog'], $row['updateuname'], $return['row']['updatetime']);
			//帖子最新评分记录
			$return['row']['reppost'] = unserialize($row['reppost']);
			$return['row']['evalcredit'] = $return['row']['reppost']['ac'];
			unset($return['row']['reppost']['ac']);
			if ($return['row']['reppost'])
			{
				foreach ($return['row']['reppost'] AS $k => $v)
				{
					$return['row']['reppost'][$k][4] = $forums->func->get_date($v[4], 2);
				}
			}
			$showpost[] = $return;
		}
		$showvoters = $voters;
		$mod = $this->moderation_panel(); //版主操作菜单
		$mod_thread = $this->moderation_thread_panel(); //版主操作主题菜单
		$title_no_tags = strip_tags($this->thread['title']);
		//处理相关主题
		$relatethread = array();
		if ($bboptions['showrelatedthread'] && !$_INPUT['pp']) //是否显示相关主题
		{
			$hignlight_key = duality_word($title_no_tags, 1);
			$tokey = array();
			foreach ($hignlight_key AS $k => $wd)
			{
				$tokey[$k] = '<span class="highlight">' . $wd . '</span>';
			}
			if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
			{
				$where = ' AND forumid != ' . intval($bboptions['recycleforumid']);
			}
			$ft = duality_word($title_no_tags);
			$ft = implode(' ', $ft);
			$sql = "SELECT tid, title, postusername, postuserid, dateline,
					MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE ) AS score
					FROM " . TABLE_PREFIX . "thread
					WHERE MATCH( titletext ) AGAINST( '{$ft}' IN BOOLEAN MODE )
						AND visible=1
						AND tid != " . $this->thread['tid'] . "
						{$where}
				ORDER BY score DESC
				LIMIT 4
				";
			$DB->query($sql);
			while ($r = $DB->fetch_array())
			{
				$r['dateline'] = $forums->func->get_date($r['dateline'], 3);
				$r['source_title'] = strip_tags($r['title']);
				$r['title'] = $forums->func->fetch_trimmed_title($r['title'], 20);
				$r['threadtitle'] = str_replace($hignlight_key, $tokey, $r['title']);
				$relatethread[$r['tid']] = $r;
			}
		}
		$pagetitle = $title_no_tags . " - " . $bboptions['bbtitle'];
		$nav = array_merge($forums->forum->forums_nav($this->forum['id'], $_INPUT['extra']), array("<a href='showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "' title='" . $title_no_tags . "'>" . $forums->func->fetch_trimmed_title($this->thread['title'], 25) . "</a>"));

		//处理快速回复框
		$postajaxrep = count($showpost) + 1;
		if (($forums->func->fetch_permissions($this->forum['canreply'], 'canreply') == true) && ($this->thread['open'] != 0))
		{
			$show['quickreply'] = true;
			require_once(ROOT_PATH . "includes/functions_showcode.php");
			$this->code = new functions_showcode();
			$antispam = $this->code->showantispam();

			if ($bbuserinfo['redirecttype'])
			{
				$redirect = ' checked="checked"';
			}
			if ($bbuserinfo['usewysiwyg'])
			{
				$showwysiwyg = "&wysiwyg=1";
			}
		}
		//加载简洁编辑器
		load_editor_js('', 'quick');

		$mxajax_register_functions = array('quick_reply', 'do_change_signature', 'returnpagetext', 'do_edit_post', 'process_post_form', 'delete_user_avatar', 'report_post', 'do_report_post', 'evaluation_post', 'do_evaluation_post', 'ban_user_post', 'send_mailto_friend', 'digg_thread'); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		add_head_element('js', ROOT_PATH . 'scripts/mxajax_thread.js');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_post.js');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_user.js');

		$referer = SCRIPTPATH;
		include $forums->func->load_template('showthread_index');
		exit;
	}

	function parse_row($row = array())
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		static $cached_users;
		$poster = array();
		if ($row['userid'])
		{
			if (isset($cached_users[$row['userid']]))
			{
				$poster = $cached_users[$row['userid']];
			}
			else
			{
				$poster = $forums->func->fetch_user($row);
				$cached_users[$row['userid']] = $poster;
			}
			$row['name_css'] = 'normalname';
		}
		else
		{
			$poster = $forums->func->set_up_guest($row['username']);
			$row['name_css'] = 'unreg';
		}
		if ($row['anonymous'])
		{
			if ($bbuserinfo['usergroupid'] == 4)
			{
				$poster['name'] = $poster['name'] . " (" . $forums->lang['anonymouspost'] . ")";
			}
			else
			{
				$poster = array();
				$poster['name'] = $forums->lang['anonymous'] . '-' . mt_rand(100000, 999999);
				$poster['id'] = 0;
				$poster['grouptitle'] = $forums->lang['byanonymous'];
				$poster['posts'] = $forums->lang['unknown'];
			}
		}
		if ($row['moderate'] || ($this->thread['firstpostid'] == $row['pid'] && $this->thread['visible'] != 1))
		{
			$row['post_css'] = $this->postcount % 2 ? 'item_list_shaded' : 'item_change_shaded';
			$row['altrow'] = 'row1shaded';
		}
		else
		{
			$row['post_css'] = $this->postcount % 2 ? 'item_list' : 'item_change';
			$row['altrow'] = 'item_list';
		}
		$keywords = array();
		if ($_INPUT['highlight'])
		{
			$highlights = str_replace('+', ',', $_INPUT['highlight']);
			if (preg_match('/,(and|or),/i', $highlights))
			{
				while (preg_match('/,(and|or),/i', $highlights, $match))
				{
					$word_array = explode(',' . $match[1] . ',', $highlights);
					if (is_array($word_array))
					{
						foreach ($word_array as $keyword)
						{
							$row['pagetext'] = preg_replace("/(.*)(" . preg_quote($keyword, '/') . ")(.*)/is", "\\1<span class='highlight'>\\2</span>\\3", $row['pagetext']);
							$keywords[] = $keyword;
						}
					}
				}
			}
			else
			{
				$row['pagetext'] = preg_replace('/(.*)(' . preg_quote($highlights, '/') . ')(.*)/i', '\\1<span class="highlight">\\2</span>\\3', $row['pagetext']);
				$keywords[] = $keyword;
			}
		}
		//处理code
		if (strpos($row['pagetext'], '[code') !== false)
		{
			$row['pagetext'] = preg_replace("#\[code(.+?)?\](.+?)\[/code\]#ies" , "\$this->codeparse->paste_code('\\1', '\\2')" , $row['pagetext']);
		}

		if (strpos($row['pagetext'], '<!--emule1-->') !== false)
		{
			$row['pagetext'] = preg_replace("/<!--emule1-->(.+?)<!--emule2-->/ie", "\$this->lib->paste_emule('\\1')", $row['pagetext']);
		}
		//处理引用
		if (strpos($row['pagetext'], '[quote') !== false)
		{
			$row['pagetext'] = preg_replace("#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$this->codeparse->parse_quotes('\\1')" , $row['pagetext']);
		}

		//处理flash
		if (strpos($row['pagetext'], '[FLASH') !== false)
		{
			$pregfind = array("#(\[flash\])(.+?)(\[\/flash\])#ie", "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#ie");
			$pregreplace = array("\$this->codeparse->parse_flash('','','\\2')", "\$this->codeparse->parse_flash('\\2','\\4','\\6')");
			$row['pagetext'] = preg_replace($pregfind, $pregreplace, $row['pagetext']);
		}

		if ($row['attachment_inpost'])
		{

			$row['pagetext'] = preg_replace("/<!--attachid::(.+?)-->.+?<!--attachid-->/ie", "\$this->lib->paste_attachment('\\1', \$row['attachment_inpost']['\\1'])", $row['pagetext']);

		}

		if ($row['userid'])
		{
			$timelimit = TIMENOW - $bboptions['cookietimeout'] * 60;
			$poster['status'] = 0;
			$this->user['options'] = intval($this->user['options']);
			$forums->func->convert_bits_to_array($row, $row['options']);
			if (($row['lastvisit'] > $timelimit || $row['lastactivity'] > $timelimit) && $row['invisible'] != 1 && $row['loggedin'] == 1)
			{
				$poster['status'] = 1;
			}
		}
		else
		{
			$poster['status'] = '';
		}
		$edit_delete_btn = $this->edit_delete_button($row);
		$row['delete_button'] = $edit_delete_btn[1];
		$row['edit_button'] = $edit_delete_btn[0];
		$row['ajaxeditpostevent'] = $edit_delete_btn['ajaxeditpostevent'];
		$row['dateline'] = $forums->func->get_date($row['dateline'], 2);
		$row['post_icon'] = $row['iconid'] ? 1 : 0;
		$row['post_icon_hash'] = $forums->cache['icon'][$row['iconid']]['image'];
		$row['host'] = $this->view_ip($row, $poster);
		$row['report_link'] = ($bboptions['disablereport'] != 1 && $bbuserinfo['id']) ? 1 : 0;
		$row['signature'] = '';
		if ($poster['signature'])
		{
			if (!$this->sigcache[$row['userid']] || !$bboptions['onlyonesignatures'])
			{
				$this->sigcache[$row['userid']] = 1;
				$row['sig'] = textparse::convert_text($poster['signature'], $bboptions['signatureallowhtml']);
				$signature_path = split_todir($row['userid'], $bboptions['uploadurl'] . '/user');
				$row['sig'] = str_replace('{$signature_path}', $signature_path[0] . '/', $row['sig']);
				$row['signature'] = 1;
			}
			else
			{
				$row['signature'] = 0;
			}
		}
		if ($poster['id'])
		{
			$poster['name'] = "<a href='profile.php{$forums->sessionurl}u=" . $poster['id'] . "'>" . $poster['name'] . "</a>";
		}
		$row['pagetext'] = textparse::convert_text($row['pagetext'], ($this->forum['allowhtml'] && $forums->cache['usergroup'][$poster['usergroupid']]['canposthtml']));
		$row['supermod'] = $forums->cache['usergroup'][$poster['usergroupid']]['supermod'];
		if ($row['hidepost'] && $row['hidepost'] != null)
		{
			$row = $this->hidefunc->parse_hide_code($row, $this->forum['id']);
		}
		$this->postcount++;
		$row['postcount'] = intval($_INPUT['pp']) + $this->postcount;


		return array('row' => $row, 'poster' => $poster);
	}

	function edit_delete_button($row)
	{
		global $forums, $bbuserinfo, $_INPUT;
		if (!$bbuserinfo['id'])
		{
			return array();
		}
		if ($bbuserinfo['supermod'])
		{
			$ajaxeditpostevent = " ondblclick = \"edit_post_event('{$row['pid']}','{$this->forum['id']}', '{$row['userid']}','{$this->thread['tid']}', '{$row['dateline']}');\"";
			return array(1, 1, 'ajaxeditpostevent' => $ajaxeditpostevent);
		}
		$edit_btn = $delete_btn = 0;
		$ajaxeditpostevent = '';
		if ($this->moderator['caneditposts'])
		{
			$edit_btn = 1;
		}
		if ($this->moderator['candeleteposts'])
		{
			$delete_btn = 1;
		}
		if ($row['userid'] == $bbuserinfo['id'])
		{
			if ($bbuserinfo['caneditpost'])
			{
				if ($bbuserinfo['edittimecut'] > 0)
				{
					if ($row['dateline'] > (TIMENOW - (intval($bbuserinfo['edittimecut']) * 60)))
					{
						$edit_btn = 1;
					}
					else
					{
						$edit_btn = 0;
					}
				}
				else
				{
					$edit_btn = 1;
				}
			}
			if ($bbuserinfo['candeletepost'])
			{
				$delete_btn = 1;
			}
		}
		if ($edit_btn)
		{
			$ajaxeditpostevent = " ondblclick = \"edit_post_event('{$row['pid']}','{$this->forum['id']}', '{$row['userid']}','{$this->thread['tid']}', '{$row['dateline']}');\"";
		}
		else
		{
			$ajaxeditpostevent = '';
		}
		return array($edit_btn, $delete_btn, 'ajaxeditpostevent' => $ajaxeditpostevent);
	}

	function reply_button()
	{
		global $forums, $bbuserinfo;
		if ($this->thread['open'] == 0)
		{
			if ($bbuserinfo['canpostclosed'])
			{
				$return['isurl'] = 1;
				$return['button'] = 'closed';
				return $return;
			}
			else
			{
				$return['isurl'] = 0;
				$return['button'] = 'closed';
				return $return;
			}
		}
		if ($this->thread['open'] == 2)
		{
			$return['isurl'] = 0;
			$return['button'] = 't_moved';
			return $return;
		}
		if ($this->thread['pollstate'] == 2)
		{
			$return['isurl'] = 0;
			$return['button'] = 'closed';
			return $return;
		}
		$return['isurl'] = 1;
		$return['button'] = 'newreply';
		return $return;
	}

	function view_ip($row, $poster)
	{
		global $forums, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['supermod'] && !$this->moderator['canviewips'])
		{
			return "";
		}
		else
		{
			$row['host'] = $poster['usergroupid'] == 4 ? '' : " IP: " . $row['host'];
			return $row['host'];
		}
	}

	function moderation_thread_panel()
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
		$modlink = '<ul class="thread_op">';
		if ($this->recycleforum && $this->recycleforum == $this->forum['id'])
		{
			$actions = array(
				'deletethreads' => array('candeletethreads', $forums->lang['deletethreads']),
				'revert' => array('canrevertthreads', $forums->lang['modrevert'])
			);
		}
		else
		{
			$actions = array(
				'openclose' => array('canopenclose', $forums->lang['openclose']),	//开放\关闭主题
				'stickorcancel' => array('canstickthread', $forums->lang['stickorcancel']),	//置顶\撤销主题
				'approveorcancel' => array('canmanagethreads', $forums->lang['approveorcancel']),	//验证\撤销主题
				'mergethreads' => array('canmergethreads', $forums->lang['mergethreads']),		//合并主题
				'deletethreads' => array('candeletethreads', $forums->lang['deletethreads']),		//删除主题
				'moveclearthreads' => array('canremoveposts', $forums->lang['moveclearthreads']),		//移动主题
				'quintessence' => array('canquintessence', $forums->lang['quintessence_op']),		//设置精华
				'dospecialtopic' => array('cansetst', $forums->lang['dospecialtopic']),
				'commend_thread' => array('modcancommend', $forums->lang['commend_thread']),		//推荐主题
			);
		}
		foreach($actions AS $this_action => $value)
		{
			if ($this_action == 'dospecialtopic')
			{
				if ($this->forum['specialtopic'])
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'t\');">' . $value[1] . "</li>\n";
				}
			}
			else if ($bbuserinfo['supermod'])
			{
				$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'t\');">' . $value[1] . "</li>\n";
			}
			else if ($this->moderator)
			{
				if ($value[0])
				{
					if ($this->moderator[$value[0]])
					{
						$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'t\');">' . $value[1] . "</li>\n";
					}
				}
			}
			else if ($this_action == 'openclose')
			{
				if ($bbuserinfo['canopenclose'])
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'t\');">' . $value[1] . "</li>\n";
				}
			}
			else if ($this_action == 'deletethreads')
			{
				if ($bbuserinfo['candeletethreads'])
				{
					$modlink .= '<li onclick="ajax_submit_form(\'modform\', \'' . $this_action . '\', \'t\');">' . $value[1] . "</li>\n";
				}
			}
		}
		$modlink .= '</ul>';
		return $modlink;
	}

	function moderation_panel()
	{
		global $bbuserinfo, $forums;
		if (!$bbuserinfo['id'])
		{
			return '';
		}
		if (!$bbuserinfo['supermod'] && !$this->moderator)
		{
			return '';
		}
		$modlink = '';
		$mod_action = array(
			'cansplitthreads' => 'splitthread',
			'canremoveposts' => 'movepost',
			'candeleteposts' => 'deletepost',
			'canmoderateposts' => 'approvepostorcancel',
		);
		if ($this->recycleforum && $this->recycleforum == $this->forum['id'])
		{
			$actions = array(
				'candeleteposts' => $forums->lang['deletepost'],
				'revertpost' => $forums->lang['revertpost'],
			);
		}
		else
		{
			$actions = array(
				'canremoveposts' => $forums->lang['movepost'],
				'candeleteposts' => $forums->lang['deletepost'],
				'canmoderateposts' => $forums->lang['approvepostorcancel'],
				'cansplitthreads' => $forums->lang['splitthread'],
				//'cleanlog' => $forums->lang['cleanlog'],
			);
		}
		foreach($actions as $key => $value)
		{
			$this_action = ($mod_action[$key]) ? $mod_action[$key] : $key;

			if ($bbuserinfo['supermod'])
			{
				$modlink .= $this->append_link($key, $value, $this_action);
			}
			else if ($this->moderator)
			{
				if ($this->moderator[strtolower($key)])
				{
					$modlink .= $this->append_link($key, $value, $this_action);
				}
			}
		}
		return $modlink;
	}

	function append_link($key = '', $value = '', $mod_action = '')
	{
		if ($key == '' ||
		($this->thread['open'] == 2 && ($key == 'closethread' || $key == 'canremoveposts'))
		)
		{
			return '';
		}

		return '<li onclick="ajax_submit_form(\'modform\', \'' . $mod_action . '\', \'pid\');">' . $value . "</li>\n";
	}

	function can_moderate($fid = 0)
	{
		global $bbuserinfo;
		$return = false;
		if ($bbuserinfo['supermod'] || ($fid && $bbuserinfo['is_mod'] && $bbuserinfo['_moderator'][$fid]['canmoderateposts']))
		{
			$return = true;
		}
		return $return;
	}

	/**
	 * 载入语法高亮使用的 JavaScript 和 CSS 文件
	 */
	function load_syntax_hightlight()
	{
	}
}

$output = new showthread();
$output->show();
?>