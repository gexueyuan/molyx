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
define('THIS_SCRIPT', 'misc');
require_once('./global.php');

class misc
{
	function show()
	{
		global $_INPUT, $forums;
		$forums->func->load_lang('misc');
		require_once(ROOT_PATH . "includes/xfunctions_hide.php");
		$this->hidefunc = new hidefunc();
		require_once(ROOT_PATH . "includes/xfunctions_bank.php");
		$this->bankfunc = new bankfunc();
		switch ($_INPUT['do'])
		{
			case 'show_voters':
				$this->show_voters();
				break;
			case 'forumread':
				$this->forumread();
				break;
			case 'allforumread':
				$this->allforumread();
				break;
			case 'icon':
				$this->show_icon();
				break;
			case 'bbcode':
				$this->show_bbcode();
				break;
			case 'privacy':
				$this->privacy();
				break;
			case 'rss':
				$this->rss();
				break;
			case 'whobought':
				$this->whobought();
				break;
			case 'buyhidden':
				$this->buyhidden();
				break;
			case 'banuserpost':
				$this->banuserpost();
				break;
			default:
				$this->show_icon();
				break;
		}
	}

	function show_voters()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		$pollid = intval($_INPUT['pollid']);
		if (!$pollid)
		{
			$errmsg = $forums->lang['cannotfindpost'];
		}
		$data = $DB->query_first("SELECT pollid, voters FROM " . TABLE_PREFIX . "poll WHERE pollid = " . $pollid . " LIMIT 0, 1");
		if (!$data['pollid'])
		{
			$errmsg = $forums->lang['cannotfindpost'];
		}
		else
		{
			if (!$data['voters'])
			{
				$errmsg = $forums->lang['no_voters'];
			}
			else
			{
				$voters = explode(",", $data['voters']);
				foreach ($voters AS $userid)
				{
					if (!$userid) continue;
					$userid = intval($userid);
					$all_voters[] = $userid;
				}
				$DB->query("SELECT id, name FROM " . TABLE_PREFIX . "user WHERE id IN (" . implode(", ", $all_voters) . ")");
				if ($DB->num_rows())
				{
					$all_polls = 0;
					while ($user = $DB->fetch_array())
					{
						$all_polls++;
						$pollvoters[] = $user;
					}
					$forums->lang['allvoters'] = sprintf($forums->lang['allvoters'], $all_polls);
				}
				else
				{
					$errmsg = $forums->lang['no_voters'];
				}
			}
		}
		$pagetitle = $forums->lang['whovotes'] . " - " . $bboptions['bbtitle'];
		include $forums->func->load_template('who_voted');
		exit;
	}

	function whobought()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		$pid = intval($_INPUT['pid']);
		if (!$pid)
		{
			$errmsg = $forums->lang['cannotfindpost'];
		}
		$data = $DB->query_first("SELECT hidepost FROM " . TABLE_PREFIX . "post WHERE pid = " . $pid . " LIMIT 0, 1");
		if (!$data['hidepost'])
		{
			$errmsg = $forums->lang['norecords'];
		}
		else
		{
			$hideinfo = unserialize($data['hidepost']);
			$buyers = $hideinfo['buyers'];
			$totalbuyers = count($buyers);
			$forums->lang['allbuyers'] = sprintf($forums->lang['allbuyers'], $totalbuyers);
		}
		$pagetitle = $forums->lang['whobuypost'] . " - " . $bboptions['bbtitle'];
		include $forums->func->load_template('who_bought');
		exit;
	}

	function banuserpost()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		$uid = intval($_INPUT['uid']);
		$fid = intval($_INPUT['fid']);
		$user = $DB->query_first("SELECT id, name, liftban, usergroupid
			FROM " . TABLE_PREFIX . "user WHERE id=$uid");
		if (!$user['id'])
		{
			$forums->func->standard_error("cannotfindmember");
		}
		if ($bbuserinfo['id'] && !$bbuserinfo['supermod'] && $fid)
		{
			$moderator = $bbuserinfo['_moderator'][$fid];
		}
		$ban = banned_detect($user['liftban']);
		if ($_INPUT['update'])
		{
			$permanent = intval($_INPUT['permanent']);
			if (!$permanent)
			{
				echo "<script language='javascript'>
					alert('" . $forums->lang['notbantype'] . "');
					</script>";
				exit();
			}
			if ($permanent == 2 && !$fid)
			{
				echo "<script language='javascript'>
					alert('" . $forums->lang['banpostnotforumid'] . "');
					</script>";
				exit();
			}
			//版主封禁用户在本版面内活动
			if (!$bbuserinfo['supermod'] && $permanent == 2)
			{
				$timelimit = intval($moderator['bantimelimit']);
				$limitunit = substr($moderator['bantimelimit'], -1);
				$limitfactor = ($limitunit == 'd') ? 86400 : 3600;
				$limitspan = TIMENOW + ($timelimit * $limitfactor);
				$timespan = intval($_INPUT['posttimespan']);
				$spanfactor = ($_INPUT['banpostunit'] == 'd') ? 86400 : 3600;
				$posttimespan = TIMENOW + ($timespan * $spanfactor);
				if ($limitspan < $posttimespan)
				{
					echo "<script language='javascript'>
						alert('" . $forums->lang['banpostmorethanlimit'] . "');
						</script>";
					exit();
				}
			}
			if ($user['usergroupid']!= 2&&($user['supermod']==1||$user['usergroupid']==4))
			{
				$user['is_mod'] = 1;
			}
			$liftban = "";
			$splittable = array();
			$forums->func->check_cache('splittable');
			$splittable = $forums->cache['splittable']['all'];
			$time = $forums->func->get_date(TIMENOW, 2, 1);
			if ($permanent == -1 || $permanent == 1)
			{
				$msg = $forums->lang['banusersuccess'];
				$usergroupid = 5;
				$banposts = $_INPUT['banbbspost']?-1:-2;
				$opera = $forums->lang['optionlog1'].$bbuserinfo['name'].$forums->lang['optionlog2'].$time.$forums->lang['optionlog3'];
				if ($banposts == -1)
				{
					foreach ($splittable as $id => $v)
					{
						if ($v['isempty']) continue;
						$DB->update(TABLE_PREFIX . $v['name'], array('state' => 2, 'logtext' => "$opera"), 'userid=' . $user['id']);
					}
				}
			}
			switch($permanent)
			{
				//永久封禁
				case -1:
					$liftban = banned_detect(array('timespan' => -1, 'unit' => '', 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts));
					break;
				//按时封禁用户在所有版面内
				case 1:
					$liftban = banned_detect(array('timespan' => intval($_INPUT['usertimespan']), 'unit' => $_INPUT['banuserunit'], 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts));
					break;
				//按时封禁用户在此版面内
				case 2:
					$msg = $forums->lang['banpostsuccess'];
					$usergroupid = $user['usergroupid'];
					$banposts = $_INPUT['banbbspost']?$fid:-2;
					if ($banposts > 0)
					{
						$opera = $forums->lang['optionlog1'].$bbuserinfo['name'].$forums->lang['optionlog2'].$time.$forums->lang['optionlog3'];

						$tidarrs = array();
						$rs = $DB->query("SELECT tid, posttable
							FROM " . TABLE_PREFIX . "thread
							WHERE postuserid={$user['id']} AND forumid = $fid");
						while($row = $DB->fetch_array($rs))
						{
							$table = $row['posttable']?$row['posttable']:'post';
							$tidarrs[$table][] = $row['tid'];
						}
						if (!empty($tidarrs))
						{
							foreach ($tidarrs as $tblname => $tids)
							{
								$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "$tblname SET state=2, logtext = '" . $opera . "'
								WHERE " . $DB->sql_in('threadid', $tids));
							}
						}
					}
					$liftban = banned_detect(array('timespan' => intval($_INPUT['posttimespan']), 'unit' => $_INPUT['banpostunit'], 'groupid' => $user['usergroupid'], 'banuser' => $bbuserinfo['name'], 'banposts' => $banposts, 'forumid' => $fid));
					break;
				default:
					$msg = $forums->lang['unbanusersuccess'];
					if ($ban['banposts'] == -1)
					{
						foreach ($splittable as $id => $v)
						{
							if ($v['isempty']) continue;
							$DB->update(TABLE_PREFIX . $v['name'], array('state' => 0, 'logtext' => ''), 'userid=' . $user['id']);
						}
					}
					elseif ($ban['banposts'] > 0)
					{
						$tidarrs = array();
						$rs = $DB->query("SELECT tid, posttable
							FROM " . TABLE_PREFIX . "thread
							WHERE postuserid={$user['id']} AND forumid = $fid");
						while($row = $DB->fetch_array($rs))
						{
							$table = $row['posttable']?$row['posttable']:'post';
							$tidarrs[$table][] = $row['tid'];
						}
						if (!empty($tidarrs))
						{
							foreach ($tidarrs as $tblname => $tids)
							{
								$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "$tblname SET state=0, logtext = '' WHERE " . $DB->sql_in('threadid', $tids));
							}
						}
					}
					$usergroupid = $ban['groupid'] ? intval($ban['groupid']) : $user['usergroupid'];
					$liftban = "";
			}

			if ($user['is_mod'] AND $liftban)
			{
				echo "<script language='javascript'>
						alert('" . $forums->lang['cannotbanuser'] . "');
						</script>";
				exit();
			}
			$DB->update(TABLE_PREFIX . 'user', array('liftban' => $liftban, 'usergroupid' => $usergroupid), 'id=' . $user['id']);
			if ($moderator['sendbanmsg'])
			{
				if ($permanent == -2)
				{
					$_INPUT['title'] = $forums->lang['unliftban'];
					$_POST['post'] = $forums->lang['unliftbandesc'];
					$_INPUT['username'] = $user['name'];
				}
				elseif ($permanent == 2)
				{
					$forum = $forums->forum->single_forum($fid);
					$_INPUT['title'] = $forums->lang['banpost'];
					$forums->lang['banpostdesc'] = sprintf($forums->lang['banpostdesc'], $forum['name'], $forums->func->get_date($posttimespan, 2));
					$_POST['post'] = $forums->lang['banpostdesc'];
					$_INPUT['username'] = $user['name'];
				}
				require_once(ROOT_PATH . 'includes/functions_private.php');
				$pm = new functions_private();
				$_INPUT['noredirect'] = 1;
				$bboptions['usewysiwyg'] = 1;
				$bboptions['pmallowhtml'] = 1;
				$pm->sendpm();
			}
			echo "<script language='javascript'>
				alert('" . $msg . "');
				history.go(-1);
				</script>";
			exit();
		}
		else
		{
			if ($ban['banposts'])
			{
				$unbanchecked = ' checked = "checked"';
			}
			$pagetitle = $forums->lang['banaction'] . " - " . $bboptions['bbtitle'];
			$banusertype = $this->getselectoption('banuserunit');
			$banposttype = $this->getselectoption('banpostunit');
			$banpostlimitdesc = sprintf($forums->lang['banpostlimitdesc'], $moderator['bantimelimit']);
			$banpostlimitdesc = str_replace(array('d', 'h'), array($forums->lang['days'], $forums->lang['hours']), $banpostlimitdesc);
			$banpostlimit = intval($moderator['bantimelimit']);

			include $forums->func->load_template('visit_forbidden');
		}
	}

	function getselectoption($name, $option = '')
	{
		global $forums;
		$units = array(0 => array('h', $forums->lang['hours']), 1 => array('d', $forums->lang['days']));
		$bantype = "<select name='".$name."' class='select_normal'>\n";
		foreach ($units AS $v)
		{
			$bantype .= "<option value='" . $v[0] . "'>" . $v[1] . "</option>\n";
		}
		$bantype .= "</select>\n\n";
		return $bantype;
	}

	function buyhidden()
	{
		global $forums, $DB, $bbuserinfo, $_INPUT;
		$pid = intval($_INPUT['pid']);
		$tid = intval($_INPUT['tid']);
		if (!$pid OR !$tid)
		{
			$forums->func->standard_error("cannotfindpost");
		}
		$data = $DB->query_first("SELECT hidepost, userid FROM " . TABLE_PREFIX . "post WHERE pid = " . $pid . " LIMIT 1");
		if (!$data['hidepost'])
		{
			$forums->func->standard_error("nohidepost");
		}
		if (!$data['userid'])
		{
			$forums->func->standard_error("noauthor");
		}
		if ($bbuserinfo['id'] == $data['userid'])
		{
			$forums->func->standard_error("uahiddenauthor");
		}
		$hideinfo = unserialize($data['hidepost']);
		if ($hideinfo['type'] != 1 && $hideinfo['type'] != 2 && $hideinfo['type'] != 999)
		{
			$forums->func->standard_error("cannotbuy");
		}
		if (in_array($bbuserinfo['name'], $hideinfo['buyers']))
		{
			$forums->func->standard_error("haspurchase");
		}
		if ($bbuserinfo['cash'] < $hideinfo['cond'])
		{
			$forums->func->standard_error("noenoughmoney");
		}
		if ($hideinfo['type'] == 999)
		{
			$forums->func->check_cache('creditlist');
			$hidecredit = array();
			if ($forums->cache['creditlist'])
			{
				foreach ($forums->cache['creditlist'] as $k => $v)
				{
					$hidecredit[$v['tag']] = $v['name'];
				}
			}
			if (!$hidecredit)
			{
				$forums->func->standard_error("errorcredit");
			}
			if ($bbuserinfo[$hideinfo['credit_type']] < $hideinfo['cond'])
			{
				$forums->func->standard_error("noenoughcredit", false, $hidecredit[$hideinfo['credit_type']]);
			}
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "userexpand SET {$hideinfo['credit_type']} = {$hideinfo['credit_type']} - {$hideinfo['cond']} WHERE id = " . $bbuserinfo['id'] . "");
		}
		else
		{
			$bbuserinfo = $this->bankfunc->patch_bankinfo();
			$tarinfo = $DB->query_first("SELECT u.id, u.name, u.cash, u.bank, u.mkaccount
				FROM " . TABLE_PREFIX . "user u
				WHERE u.id = " . $data['userid']);
			if (!$tarinfo || !is_array($tarinfo) || !$tarinfo['id'])
			{
				$forums->func->standard_error("nouserid");
			}

			$this->bankfunc->trdesc = $forums->lang['buypost'] . " [<a href='redirect.php?goto=findpost&p=$pid'>{$forums->lang['view']}</a>]";
			$this->bankfunc->fromCorB = 0;
			$this->bankfunc->tarCorB = 0;
			$this->bankfunc->meextra = 0;
			$this->bankfunc->tarextra = 0;

			if (!$this->bankfunc->user_transfer_money($tarinfo, $hideinfo['cond']))
			{
				$forums->func->standard_error("errormoney");
			}
		}
		$hideinfo['buyers'][] = $bbuserinfo['name'];
		$DB->update(TABLE_PREFIX . 'post', array('hidepost' => serialize($hideinfo)), 'pid = ' . $pid);
		$forums->func->standard_redirect('showthread.php' . $forums->sessionurl . 't=' . $tid);
		exit;
	}

	function rss()
	{
		global $forums, $DB, $bboptions, $bbuserinfo, $_INPUT;
		$showforum = $forums->forum->forum_jump(1, 1);
		if ($_INPUT['update'])
		{
			$extra = array();
			$forumlist = $this->get_forums();
			if ($forumlist) $extra[] = "fid=" . $forumlist;
			if ($_INPUT['version'] != 'rss') $extra[] = "version=" . $_INPUT['version'];
			if (intval($_INPUT['limit'])) $extra[] = "limit=" . intval($_INPUT['limit']);
			$message = intval($_INPUT['limit']) > 100 ? $forums->lang['rsslimit'] : $bboptions['bburl'] . "/rss.php?" . implode("&amp;", $extra);
		}
		else
		{
			if (! $_INPUT['f'])
			{
				$selected = " selected='selected'";
			}
		}
		$pagetitle = $forums->lang['rss'] . " - " . $bboptions['bbtitle'];
		$nav[] = $forums->lang['rss'];
		include $forums->func->load_template('rss_feed');
		exit;
	}

	function get_forums()
	{
		global $forums, $_INPUT;
		$forumids = array();
		if ($_INPUT['forumlist'] != '')
		{
			foreach($forums->forum->foruminfo as $id => $data)
			{
				if (in_array($data['id'], $_INPUT['forumlist']))
				{
					$forumids[] = $data['id'];
				}
			}
		}
		return implode(',' , $forumids);
	}

	function privacy()
	{
		global $forums, $DB, $bboptions, $bbuserinfo, $_INPUT;
		if (! $bboptions['showprivacy'])
		{
			$forums->func->standard_redirect();
		}
		if ($bboptions['privacyurl'])
		{
			$forums->func->standard_redirect($bboptions['privacyurl']);
		}
		else
		{
			$privacy = $DB->query_first("SELECT value FROM " . TABLE_PREFIX . "setting WHERE varname='privacytext'");
			$privacytext = str_replace("\n", '<br />', $privacy['value']);
			$pagetitle = $bboptions['privacytitle'] . " - " . $bboptions['bbtitle'];
			$nav[] = $bboptions['privacytitle'];
			include $forums->func->load_template('privacy');
			exit;
		}
	}

	function show_icon()
	{
		global $forums, $DB, $bboptions, $bbuserinfo, $_INPUT;
		$forums->func->check_cache('smile');
		$emoticons = $forums->cache['smile'];
		$pagetitle = $forums->lang['smiles'] . " - " . $bboptions['bbtitle'];
		include $forums->func->load_template('show_smile');
		exit;
	}

	function show_bbcode()
	{
		global $forums, $DB, $bboptions, $bbuserinfo;
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		$this->parser = new functions_codeparse();
		$bbcode = array('[B]' => '[b]' . $forums->lang['boldsample'] . '[/b]',
			'[I]' => '[i]' . $forums->lang['italicsample'] . '[/i]',
			'[U]' => '[u]' . $forums->lang['underlinesample'] . '[/u]',
			'[S]' => '[s]' . $forums->lang['strikethroughsample'] . '[/s]',
			'[EMAIL]' => '[email]user@domain.com[/email]',
			'[EMAIL=xxx]' => '[email=user@domain.com]' . $forums->lang['emailsample'] . '[/email]',
			'[URL]' => '[url]http://www.domain.com[/url]',
			'[URL=xxx]' => '[url=http://www.domain.com]' . $forums->lang['websitesample'] . '[/url]',
			'[SIZE]' => '[size=7]' . $forums->lang['sizesample'] . '[/size]',
			'[FONT]' => '[font=simli]' . $forums->lang['fontsample'] . '[/font]',
			'[COLOR]' => '[color=red]' . $forums->lang['colorsample'] . '[/color]',
			'[IMG]' => '[img]http://www.google.com/images/logo.gif[/img]',
			'[LIST]' => '[list][*]' . $forums->lang['listsample'] . ' [*]' . $forums->lang['listsample'] . '[/list]',
			'[LIST=1]' => '[list=1][*]' . $forums->lang['listsample'] . ' [*]' . $forums->lang['listsample'] . '[/list]',
			'[LIST=a]' => '[list=a][*]' . $forums->lang['listsample'] . ' [*]' . $forums->lang['listsample'] . '[/list]',
			'[LIST=i]' => '[list=i][*]' . $forums->lang['listsample'] . ' [*]' . $forums->lang['listsample'] . '[/list]',
			'[QUOTE]' => '[quote]' . $forums->lang['quotesample'] . '[/quote]',
			'[CODE]' => '[code]&lt;a href=&quot;test/page.html&quot;&gt;A Test Page&lt;/a&gt;[/code]',
		);
		foreach($bbcode as $k => $v)
		{
			$code['title'] = $k;
			$code['before'] = $v;
			$code['change'] = $this->parser->convert(array('text' => $v, 'allowcode' => 1));
			$ori[] = $code;
		}
		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "bbcode");
		while ($row = $DB->fetch_array())
		{
			$code['title'] = '[' . $row['bbcodetag'] . ']';
			$code['desc'] = $row['description'];
			$code['before'] = $row['bbcodeexample'];
			$code['change'] = textparse::parse_bbcode($row['bbcodereplacement']);
			$new[] = $code;
		}
		$codelist = array_merge($ori, $new);
		$pagetitle = $forums->lang['bbcode'] . " - " . $bboptions['bbtitle'];
		include $forums->func->load_template('show_bbcode');
		exit;
	}

	function allforumread()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET lastvisit=" . TIMENOW . ", lastactivity=" . TIMENOW . " WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect();
	}

	function forumread()
	{
		global $forums, $_INPUT, $bboptions;
		$fid = intval($_INPUT['f']);
		if (!$fid)
		{
			$forums->func->standard_error("cannotfindforum");
		}
		$forum = $forums->forum->foruminfo[$fid];
		if (!$forum['id'])
		{
			$forums->func->standard_error("cannotfindforum");
		}
		$children = $forums->forum->forums_get_children($forum['id']);
		$forums->forum_read[$forum['id']] = TIMENOW;
		$forums->forum->forumread(1);
		if (count($children))
		{
			$forums->func->standard_redirect("forumdisplay.php{$forums->sessionurl}f=" . $forum['parentid']);
		}
		else
		{
			$forums->func->standard_redirect();
		}
	}
}

$output = new misc();
$output->show();

?>