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
define('THIS_SCRIPT', 'moderate');
require_once('./global.php');

class moderate
{
	var $moderator = array();
	var $forum = array();
	var $thread = array();
	var $tids = array();
	var $pids = array();
	var $uploadfolder = '';
	var $recycleforum = 0;

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('moderate');
		$forums->func->errheader = false; //关闭报错的html头尾
		require_once(ROOT_PATH . 'includes/xfunctions_bank.php');
		$this->bankfunc = new bankfunc();
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		if (!in_array($_INPUT['do'], array('edituser', 'announcement', 'doannouncement', 'updateannouncement', 'deleteannouncement', 'findmember', 'dofindmember')) AND !empty($_INPUT['do']))
		{
			if ($_INPUT['tid'])
			{
				foreach ($_INPUT['tid'] AS $key)
				{
					$key = intval($key);
					if (empty($key)) continue;
					$this->tids[] = $key;
				}
				if (count($this->tids) == 1)
				{
					$this->thread = $DB->query_first("SELECT t.* FROM " . TABLE_PREFIX . "thread t WHERE t.tid = " . implode("", $this->tids));
				}
			}
			if ($_INPUT['t'])
			{
				$_INPUT['t'] = intval($_INPUT['t']);
				if (! $_INPUT['t'])
				{
					$forums->func->standard_error("erroraddress");
				}
				else
				{
					if (! $this->thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid=" . $_INPUT['t']))
					{
						$forums->func->standard_error("erroraddress");
					}
					$this->tids[] = $this->thread['tid'];
				}
			}
			if ($_INPUT['pid'])
			{
				if (is_array($_INPUT['pid']))
				{
					foreach ($_INPUT['pid'] AS $key)
					{
						$key = intval($key);
						if (empty($key)) continue;
						$this->pids[] = $key;
					}
				}
				else
				{
					$this->pids[] = intval($_INPUT['pid']);
				}
			}
			$_INPUT['f'] = intval($_INPUT['f']);
			if (! $_INPUT['f'] && !$bbuserinfo['supermod'])
			{
				$forums->func->standard_error("erroraddress");
			}
			$_INPUT['pp'] = intval($_INPUT['pp']);
			$this->forum = $forums->forum->single_forum($_INPUT['f']);
			if ($bbuserinfo['_moderator'][ $_INPUT['f'] ])
			{
				$this->moderator = $bbuserinfo['_moderator'][ $_INPUT['f'] ];
			}
		}
		require(ROOT_PATH . 'includes/functions_moderate.php');
		$this->modfunc = new modfunctions();
		$this->modfunc->init($this->forum);
		$this->uploadfolder = $bboptions['uploadfolder'];
		$this->posthash = $forums->func->md5_check();
		if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
		{
			if ($bbuserinfo['cancontrolpanel'])
			{
				$this->recycleforum = $bboptions['recycleforadmin'] ? $bboptions['recycleforumid'] : 0;
			}
			else if ($bbuserinfo['supermod'])
			{
				$this->recycleforum = $bboptions['recycleforsuper'] ? $bboptions['recycleforumid'] : 0;
			}
			else if ($bbuserinfo['is_mod'])
			{
				$this->recycleforum = $bboptions['recycleformod'] ? $bboptions['recycleforumid'] : 0;
			}
			else
			{
				$this->recycleforum = $bboptions['recycleforumid'];
			}
		}
		switch ($_INPUT['do'])
		{
			case 'edituser':
				$this->edituser();
				break;
			case 'editpoll':
				$this->editpoll();
				break;
			case 'deletepoll':
				$this->deletepoll();
				break;
			case 'close':
				$this->openclose('close');
				break;
			case 'open':
				$this->openclose('open');
				break;
			case 'stick':
				$this->stickunstick('stick');
				break;
			case 'gstick':
				$this->gstick('gstick');
				break;
			case 'ungstick':
				$this->gstick('ungstick');
				break;
			case 'unstick':
				$this->stickunstick('unstick');
				break;
			case 'approve':
				$this->approveunapprove('approve');
				break;
			case 'unapprove':
				$this->approveunapprove('unapprove');
				break;
			case 'quintessence':
				$this->quintessence('quintessence');
				break;
			case 'unquintessence':
				$this->quintessence('unquintessence');
				break;
			case 'move':
				$this->movethread();
				break;
			case 'domove':
				$this->domove();
				break;
			case 'merge':
				$this->mergethread();
				break;
			case 'domerge':
				$this->domerge();
				break;
			case 'delete':
				$this->deletethread();
				break;
			case 'revert':
				$this->revertthread();
				break;
			case 'canrevertthreads':
				$this->revertthread();
				break;
			case 'unsubscribe':
				$this->unsubscribeall();
				break;
			case 'movepost':
				$this->movepost();
				break;
			case 'deletepost':
				$this->deletepost();
				break;
			case 'revertpost':
				$this->revertpost();
				break;
			case 'splitthread':
				$this->splitthread();
				break;
			case 'approvepost':
				$this->approvepost(1);
				break;
			case 'unapprovepost':
				$this->approvepost(0);
				break;
			case 'recount':
				$this->recount($this->forum['id'], 1);
				break;
			case 'announcement':
				$this->announcement();
				break;
			case 'doannouncement':
				$this->announcementform();
				break;
			case 'updateannouncement':
				$this->updateannouncement();
				break;
			case 'deleteannouncement':
				$this->deleteannouncement();
				break;
			case 'cleanmoveurl':
				$this->cleanmoveurl();
				break;
			case 'findmember':
				$this->findmember();
				break;
			case 'dofindmember':
				$this->dofindmember();
				break;
			case 'cleanlog':
				$this->cleanlog();
				break;
			case 'specialtopic':
				$this->specialtopic();
				break;
			case 'dospecialtopic':
				$this->dospecialtopic();
				break;
			case 'unspecialtopic':
				$this->unspecialtopic();
				break;
			default:
				$this->announcement();
				break;
		}
	}

	function moderate_log($action = 'Unknown')
	{
		global $_INPUT;
		$this->modfunc->add_moderate_log($_INPUT['f'], $this->thread['tid'], $_INPUT['p'], $this->thread['title'], $action);
	}

	function thread_log($tids, $action = 'Unknown')
	{
		global $DB, $forums, $bbuserinfo;
		if (is_array($tids))
		{
			$uptid = "tid IN (" . implode(",", $tids) . ")";
		}
		else if (count($tids) == 1)
		{
			$uptid = "tid=" . intval($tids);
		}
		else if (!$this->thread['tid'])
		{
			return;
		}
		$timenow = $forums->func->get_date(TIMENOW , 2, 1);
		$threadlog = sprintf($forums->lang['threadlog'], $bbuserinfo['name'], $timenow, $action);
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "thread SET logtext = '" . addslashes($threadlog) . "' WHERE {$uptid}");
	}

	function cleanlog()
	{
		global $DB, $forums, $bbuserinfo;
		if (!$bbuserinfo['supermod'])
		{
			$forums->func->standard_error("noperms");
		}
		if (!$this->thread['tid'])
		{
			$forums->func->standard_error("erroraddress");
		}
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "thread SET logtext = '' WHERE tid='" . $this->thread['tid'] . "'");
		$forums->func->redirect_screen($forums->lang['threadlogcleaned'], "showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;pp=" . $_INPUT['pp']);
	}

	function recount($fid = '', $redirect = 0)
	{
		global $DB, $forums;
		$forumid = $fid ? $fid : $this->forum['id'];
		$this->modfunc->forum_recount($forumid);
		if ($redirect)
		{
			$forums->func->standard_redirect("forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
	}

	function edituser()
	{
		global $DB, $forums, $_INPUT, $bbuserinfo, $bboptions;
		$userid = intval($_INPUT['u']);
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] OR $bbuserinfo['caneditusers']) ? true : false;
		if (empty($userid) OR !$passed)
		{
			$forums->func->standard_error("noperms");
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$parser = new functions_codeparse();
		if (!$user = $DB->query_first("SELECT u.*, s.lastactivity, s.inforum, s.inthread FROM " . TABLE_PREFIX . "user u
					LEFT JOIN " . TABLE_PREFIX . "session s ON (s.userid=u.id)
				WHERE u.id='" . $userid . "'"))
		{
			$forums->func->standard_error("cannotfindmember");
		}
		$ban = banned_detect($user['liftban']);
		if (! $_INPUT['update'])
		{
			if (!$bbuserinfo['cancontrolpanel'])
			{
				if ($user['cancontrolpanel'])
				{
					$forums->func->standard_error("userisadmin");
				}
			}
			if (preg_match ("#<!--sig_img--><div>(.+?)</div><!--sig_img1-->#", $user['signature'], $match))
			{
				$user['signature'] = preg_replace("#<!--sig_img-->(.+?)<!--sig_img1-->#", "", $user['signature']);
			}
			$sigimg = preg_replace('#<img[^>]+src=(\'|")(\{\$signature_path\}\S+?)(\\1).*>#siU', '\2', $match[1]);

			if ($sigimg)
			{
				$signature_path = split_todir($user['id'], $bboptions['uploadurl'] . '/user');
				$signature_path = $signature_path[0] . '/';
				$sigimg = str_replace('{$signature_path}', $signature_path, $sigimg);
				$issigimg = 1;
			}
			else
			{
				$issigimg = 0;
			}

			$user['signature'] = $parser->unconvert($user['signature'], $bboptions['signatureallowbbcode'], $bboptions['signatureallowhtml']);
			$pagetitle = $forums->lang['edituser'] . " - " . $bboptions['bbtitle'];
			$nav = array("<a href='profile.php{$forums->sessionurl}u={$userid}'>" . $forums->lang['userinfo'] . "</a>", $forums->lang['edituser']);
			include $forums->func->load_template('edit_user');
			exit;
		}
		else
		{
			$post = utf8_htmlspecialchars($_POST['signature']);
			$_INPUT['signature'] = $parser->convert(array('text' => $post,
				'allowsmilies' => 1,
				'allowcode' => $bboptions['signatureallowbbcode'],
			));
			if (preg_match("#<!--sig_img-->(.+?)<!--sig_img1-->#is", $user['signature'], $match))
			{
				if ($_INPUT['sigimg'])
				{
					$path = split_todir($user['id'], $bboptions['uploadfolder'] . '/user');
					foreach(array('swf', 'jpg', 'jpeg', 'gif', 'png') as $extension)
					{
						if (@file_exists($path[0] . "/s-" . $user['id'] . "." . $extension))
						{
							@unlink($path[0] . "/s-" . $user['id'] . "." . $extension);
						}
					}
				}
				else
				{
					$_INPUT['signature'] .= $match[0];
				}
			}
			if ($parser->error != "")
			{
				$forums->func->standard_error($parser->error);
			}
			//开始检测扩展字段
			$user_data = $forums->func->check_usrext_field();
			if ($user_data['err'])
			{
				$forums->func->standard_error($user_data['err']);
			}
			//检测结束
			$userinfo = array(
				'signature' => $_INPUT['signature'],
				//'usergroupid' => $usergroupid,
				);
			if (is_array($user_data['user']))
			{
				$userinfo = $userinfo + $user_data['user'];
			}
			$DB->update(TABLE_PREFIX . 'user', $userinfo, 'id=' . $userid);
			if (is_array($user_data['userexpand']))
			{
				$DB->update(TABLE_PREFIX . 'userexpand', $user_data['userexpand'], "id={$userid}");
			}
			$forums->func->standard_redirect("moderate.php{$forums->sessionurl}do=edituser&amp;posthash={$posthash}&amp;u={$userid}");
		}
	}

	function editpoll()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$bboptions['maxpolloptions'] = $bboptions['maxpolloptions'] ? $bboptions['maxpolloptions'] : 10;
		$fid = $this->forum['id'];
		$tid = $this->thread['tid'];
		$t_title = $this->thread['title'];
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] || $bbuserinfo['_moderator'][$fid]['caneditposts']) ? true : false;
		if (empty($tid) || !$passed)
		{
			$forums->func->standard_error("noperms");
		}
		$poll_data = $DB->query_first("SELECT p.*, t.postuserid, u.usergroupid FROM " . TABLE_PREFIX . "poll p
		                                     LEFT JOIN " . TABLE_PREFIX . "thread t
		                                         ON p.tid = t.tid
		                                     LEFT JOIN " . TABLE_PREFIX . "user u
		                                         ON u.id = t.postuserid
		                               WHERE p.tid=" . $tid);
		if (!$poll_data['pollid'])
		{
			$forums->func->standard_error("cannotfindpolls");
		}
		$docredit = false;
		if ($bbuserinfo['id'] != $poll_data['postuserid'])
		{
			$docredit = true;
		}
		else
		{
			if ($docredit)
			{
				if ($_INPUT['docredit'])
				{
					$this->credit->check_credit('editpoll', $poll_data['usergroupid'], $fid);
				}
			}
			else
			{
				$this->credit->check_credit('editpoll', $bbuserinfo['usergroupid'], $fid);
			}
		}
		$question = $poll_data['question'];
		$polloptions = unserialize($poll_data['options']);
		require_once (ROOT_PATH . "includes/functions_codeparse.php");
		$this->parser = new functions_codeparse();
		reset($polloptions);
		if (!$_INPUT['update'])
		{
			foreach ($polloptions as $k => $v)
			{
				$polloptions[$k][1] = $this->parser->unconvert($v[1]);
			}
			$count = count($polloptions);
			if ($count < $bboptions['maxpolloptions'])
			{
				for ($i = $count; $i < $bboptions['maxpolloptions'] ; $i++)
				{
					$newpoll[] = $i;
				}
			}
			$poll_data['multipoll'] ? ($allowmulti = "selected='selected'") : ($disallowmulti = "selected='selected'");
			$pagetitle = $forums->lang['editpoll'] . ": " . $this->thread['title'] . " - " . $bboptions['bbtitle'];
			$nav = array (
				"<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>",
				"<a href='showthread.php{$forums->sessionurl}t={$this->thread['tid']}'>{$this->thread['title']}</a>"
			);
			include $forums->func->load_template('edit_poll');
			exit;
		}
		else
		{
			$newpolloptions = array();
			$ids = array();
			$rearranged = array();
			foreach ($_INPUT['poll'] as $key => $value)
			{
				if ($value)
				{
					$ids[] = $key;
				}
			}
			$votetotal = 0;
			foreach($ids as $nid)
			{
				if ($_INPUT['poll'][$nid])
				{
					$newpolloptions[] = array($nid, $this->parser->convert(array(
						'text' => $_INPUT['poll'][$nid],
						'allowsmilies' => $bboptions['enablepolltags'],
						'allowcode' => $bboptions['enablepolltags'],
					)), intval($_INPUT['votes'][$nid]));
					$votetotal += intval($_INPUT['votes'][$nid]);
				}
			}

			$sql_array = array(
				'votes' => $votetotal,
				'options' => serialize($newpolloptions),
				'question' => $_INPUT['question'],
				'multipoll' => $_INPUT['multipoll'] ? 1 : 0,
			);
			$DB->update(TABLE_PREFIX . 'poll', $sql_array, "tid = {$this->thread['tid']}");

			$sql_array = array(
				'pollstate' => ($_INPUT['pollonly'] == 1) ? 2 : 1,
			);
			$DB->update(TABLE_PREFIX . 'thread', $sql_array, "tid = {$this->thread['tid']}");
			if ($docredit)
			{
				if ($_INPUT['docredit'])
				{
					$this->credit->update_credit('editpoll', $poll_data['postuserid'], $poll_data['usergroupid'], $fid);
				}
			}
			else
			{
				$this->credit->update_credit('editpoll', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $fid);
			}
			$forums->lang['editthreadpoll'] = sprintf($forums->lang['editthreadpoll'], $this->thread['title']);
			$this->moderate_log($forums->lang['editthreadpoll']);
			$forums->func->redirect_screen($forums->lang['pollhasedited'], "showthread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;pp={$_INPUT['pp']}");
		}
	}

	function deletepoll()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$fid = $this->forum['id'];
		$passed = ($bbuserinfo['supermod'] || $bbuserinfo['_moderator'][$fid]['candeletethreads']) ? true : false;
		if (empty($this->thread['tid']) || !$passed)
		{
			$forums->func->standard_error("noperms");
		}
		$userinfo = $DB->query_first("SELECT id, usergroupid FROM " . TABLE_PREFIX . "user
		                              WHERE id=" . intval($this->thread['postuserid']));
		if ($bbuserinfo['id'] == $this->thread['postuserid'])
		{
			$this->credit->check_credit('delthread', $userinfo['usergroupid'], $fid);
		}
		$DB->query("SELECT pollid FROM " . TABLE_PREFIX . "poll WHERE tid=" . $this->thread['tid']);
		if (!$DB->num_rows())
		{
			$forums->func->standard_error("cannotfinddelpolls");
		}
		$DB->delete(TABLE_PREFIX . 'poll', "tid = {$this->thread['tid']}");
		$DB->update(TABLE_PREFIX . 'thread', array('pollstate' => 0, 'lastvote' => '', 'votetotal' => ''), "tid = {$this->thread['tid']}");

		$this->credit->update_credit('delthread', $userinfo['id'], $userinfo['usergroupid'], $fid);
		$forums->lang['deletepoll'] = sprintf($forums->lang['deletepoll'], $this->thread['title']);
		$this->moderate_log($forums->lang['deletepoll']);
		$forums->func->redirect_screen($forums->lang['pollhasdeleted'], "showthread.php{$forums->sessionurl}t={$this->thread['tid']}&amp;pp={$_INPUT['pp']}");
	}

	function openclose($type = 'open')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT;
		$passed = ($bbuserinfo['supermod'] OR ($this->thread['postuserid'] == $bbuserinfo['id'] AND $bbuserinfo['canopenclose']) OR $this->moderator['canopenclose']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		if ($type == 'close')
		{
			$action = $forums->lang['closethread'];
			$operation = 0;
		}
		else if ($type == 'open')
		{
			$action = $forums->lang['openthread'];
			$operation = 1;
		}
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "thread SET open='" . $operation . "' WHERE tid IN(" . implode(",", $this->tids) . ")");
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($action . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
		}
		else
		{
			$this->moderate_log($action . " - " . $this->thread['title']);
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function stickunstick($type = 'stick')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canstickthread']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		if ($type == 'stick')
		{
			$action = $forums->lang['stickthread'];
			$sticky = 1;
		}
		else if ($type == 'unstick')
		{
			$action = $forums->lang['unstickthread'];
			$sticky = 0;
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET sticky='" . $sticky . "' WHERE tid IN(" . implode(",", $this->tids) . ")");
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($action . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
			$this->thread_log($this->tids, $action);
		}
		else
		{
			$this->moderate_log($action . " - " . $this->thread['title']);
			$this->thread_log($this->thread['tid'], $action);
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function gstick($type = 'gstick')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['cangstickthread']) ? true : false;
		if (count($this->tids) == 0 || !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		if ($type == 'gstick')
		{
			$action = $forums->lang['gstickthread'];
			$sticky = 99;
		}
		else if ($type == 'ungstick')
		{
			$action = $forums->lang['ungstickthread'];
			$sticky = 0;
		}
		$DB->update(TABLE_PREFIX . 'thread', array('sticky' => $sticky), $DB->sql_in('tid', $this->tids));
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($action . ' - ' . $forums->lang['threadid'] . ': ' . implode(',', $this->tids));
			$this->thread_log($this->tids, $action);
		}
		else
		{
			$this->moderate_log($action . ' - ' . $this->thread['title']);
			$this->thread_log($this->thread['tid'], $action);
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}");
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid={$_INPUT['searchid']}&searchin={$_INPUT['searchin']}&showposts={$_INPUT['showposts']}&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function approveunapprove($type = 'approve')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canmoderateposts']) ? true : false;
		if (count($this->tids) == 0 || !$passed)
		{
			$forums->func->standard_error("erroroperation");
			return;
		}
		if ($type == 'approve')
		{
			$action = $forums->lang['approvethread'];
			$operation = 1;
		}
		else if ($type == 'unapprove')
		{
			$action = $forums->lang['unapprovethread'];
			$operation = 0;
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET visible='" . $operation . "' WHERE tid IN(" . implode(",", $this->tids) . ")");
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($action . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
			$this->thread_log($this->tids, $action);
		}
		else
		{
			$this->moderate_log($action . " - " . $this->thread['title']);
			$this->thread_log($this->thread['tid'], $action);
		}
		$this->recount($this->forum['id']);
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function quintessence($type = 'quintessence')
	{
		global $forums, $DB, $bbuserinfo, $_INPUT, $bboptions;

		$userids = $groupids = $forumids = array();
		if (count($this->tids) == 0 OR !$bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
			return;
		}
		if ($type == 'quintessence')
		{
			$action = $forums->lang['quintessencethread'];
			$operation = 1;
			$userquintessence = 'quintessence=quintessence+1';
		}
		else if ($type == 'unquintessence')
		{
			$action = $forums->lang['unquintessencethread'];
			$operation = 0;
			$userquintessence = 'quintessence=quintessence-1';
		}
		$users = $DB->query("SELECT t.tid, t.title, t.postuserid, t.forumid, t.postusername, t.quintessence, u.usergroupid
		                        FROM " . TABLE_PREFIX . "thread t
								LEFT JOIN " . TABLE_PREFIX . "user u
							 		ON u.id = t.postuserid
		                     WHERE t.tid IN(" . implode(",", $this->tids) . ")");
		if ($DB->num_rows($users))
		{
			while ($user = $DB->fetch_array($users))
			{
				$userids[] = $user['userid'];
				$groupids[] = $user['usergroupid'];
				$forumids[] = $user['forumid'];
				if (!$bbuserinfo['supermod'] && !$this->moderator)
				{
					$this->credit->check_credit($type, $user['usergroupid'], $user['forumid']);
				}
				if ($type == 'quintessence')
				{
					$_INPUT['title'] = $forums->lang['threadquintessenced'];
					$forums->lang['quintessenceinfo'] = sprintf($forums->lang['quintessenceinfo'], $user['tid'], $user['title'], $bboptions['bbtitle']);
					$_POST['post'] = $forums->lang['quintessenceinfo'];
					$_INPUT['username'] = $user['postusername'];
					require_once(ROOT_PATH . 'includes/functions_private.php');
					$pm = new functions_private();
					$_INPUT['noredirect'] = 1;
					$bboptions['usewysiwyg'] = 1;
					$bboptions['pmallowhtml'] = 1;
					$pm->sendpm();
				}
				else if ($type == 'unquintessence')
				{
					$_INPUT['title'] = $forums->lang['threadunquintessenced'];
					$forums->lang['unquintessenceinfo'] = sprintf($forums->lang['unquintessenceinfo'], $user['tid'], $user['title'], $bboptions['bbtitle']);
					$_POST['post'] = $forums->lang['unquintessenceinfo'];
					$_INPUT['username'] = $user['postusername'];
					require_once(ROOT_PATH . 'includes/functions_private.php');
					$pm = new functions_private();
					$_INPUT['noredirect'] = 1;
					$bboptions['usewysiwyg'] = 1;
					$bboptions['pmallowhtml'] = 1;
					$pm->sendpm();
				}
				$allusers[] = $user['postuserid'];
			}
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET $userquintessence WHERE id IN (" . implode("," , $allusers) . ")");
			$this->credit->update_credit($type, $userids, $groupids, $forumids);
		}
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET quintessence='" . $operation . "' WHERE tid IN(" . implode(",", $this->tids) . ")");
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($action . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
			$this->thread_log($this->tids, $action);
		}
		else
		{
			$this->moderate_log($action . " - " . $this->thread['title']);
			$this->thread_log($this->thread['tid'], $action);
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function cleanmoveurl()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canremoveposts']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		foreach ($this->tids AS $tid)
		{
			if ($cleanid = $DB->query_first("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE moved LIKE '" . $tid . "&%'"))
			{
				$thread[] = $cleanid['tid'];
			}
		}
		if (is_array($thread))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "thread WHERE tid IN (" . implode(",", $thread) . ")");
			$this->recount($this->forum['id']);
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($forums->lang['hascleaned'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function movethread()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$fid = $this->forum['id'];
		$fname = $this->forum['name'];
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canremoveposts']) ? true : false;
		if (count($this->tids) == 0 OR $passed != 1)
		{
			$forums->func->standard_error("erroroperation");
		}
		$thread = array();
		$desc_forum = $this->move_construct_forum_jump(0, 0);
		if (count ($this->tids) > 1)
		{
			$DB->query("SELECT title, tid FROM " . TABLE_PREFIX . "thread WHERE tid IN(" . implode(",", $this->tids) . ")");
			while ($row = $DB->fetch_array())
			{
				$thread[] = $row;
			}
			$pagetitle = $forums->lang['batchmove'] . " - " . $bboptions['bbtitle'];
			$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>");
		}
		else
		{
			$tid = $this->thread['tid'];
			$t_title = $this->thread['title'];
			$thread[] = $this->thread;
			$pagetitle = $forums->lang['movethread'] . ": " . $t_title . " - " . $bboptions['bbtitle'];
			$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>",
				"<a href='showthread.php{$forums->sessionurl}t={$this->thread['tid']}'>{$this->thread['title']}</a>"
				);
		}
		$forums->lang['movethreadto'] = sprintf($forums->lang['movethreadto'], $this->forum['name'], $forum['name']);
		$forums->lang['moveallthreadto'] = sprintf($forums->lang['moveallthreadto'], $fname);
		include $forums->func->load_template('_move_thread');
		exit;
	}


	function move_construct_forum_jump($html = 1, $override = 0)
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if ($html == 1)
		{
			$forumjump = "<form onsubmit=\"if(document.jumpmenu.f.value == -1){return false;}\" action='forumdisplay.php' method='get' name='jumpmenu'>
			             <input type='hidden' name='s' value='" . $forums->sessionid . "' />
			             <select name='f' onchange=\"if(this.options[this.selectedIndex].value != -1){ document.jumpmenu.submit() }\">
			             <optgroup label='" . $forums->lang['_jumpto'] . "'>
			              <option value='home'>" . $forums->lang['_boardindex'] . "</option>
			              <option value='search'>" . $forums->lang['_search'] . "</option>
			              <option value='faq'>" . $forums->lang['_faq'] . "</option>
			              <option value='cp'>" . $forums->lang['_usercp'] . "</option>
			              <option value='wol'>" . $forums->lang['_online'] . "</option>
			             </optgroup>
			             <optgroup label='" . $forums->lang['_boardjump'] . "'>";
		}
		$forumjump .= $this->forum_jump($html, $override);
		if ($html == 1)
		{
			$forumjump .= "</optgroup>\n</select>&nbsp;<input type='submit' value='" . $forums->lang['_ok'] . "' class='button' /></form>";
		}
		return $forumjump;
	}

    function forum_jump($html = 0, $override = 0)
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

	function domove()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canremoveposts']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$dest_id = intval($_INPUT['move_id']);
		$source_id = $this->forum['id'];
		$_INPUT['leave'] = $_INPUT['leave'] == '1' ? 1 : 0;
		if ($source_id == "")
		{
			$forums->func->standard_error("cannotfindsource");
		}
		if ($dest_id == "" OR $dest_id == -1)
		{
			$forums->func->standard_error("cannotfindtarget");
		}
		if ($source_id == $dest_id)
		{
			$forums->func->standard_error("notsamesource");
		}
		if (!$forum = $DB->query_first("SELECT id, allowposting, name FROM " . TABLE_PREFIX . "forum WHERE id=" . $dest_id . ""))
		{
			$forums->func->standard_error("cannotfindtarget");
		}
		if ($forum['allowposting'] != 1)
		{
			$forums->func->standard_error("cannotmove");
		}
		$this->modfunc->thread_move($this->tids, $source_id, $dest_id, $_INPUT['leave']);
		$this->recount($source_id);
		$this->recount($dest_id);
		$forums->lang['movethreadto'] = sprintf($forums->lang['movethreadto'], $this->forum['name'], $forum['name']);
		$this->moderate_log($forums->lang['movethreadto']);
		$this->thread_log($this->tids, $forums->lang['movethreadto']);
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen($forums->lang['hasmoved'], "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id']);
		}
		else
		{
			$forums->func->redirect_screen($action . $forums->lang['actioned'], "search.php?do=show&searchid=" . $_INPUT['searchid'] . "&searchin=" . $_INPUT['searchin'] . "&showposts=" . $_INPUT['showposts'] . "&highlight=" . urlencode(trim($_INPUT['highlight'])));
		}
	}

	function specialtopic()
	{
		global $forums, $DB, $bbuserinfo, $bboptions, $_INPUT;
		$fid = $this->forum['id'];
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['cansetst']) ? true : false;
		if (count($this->tids) == 0 OR $passed != 1)
		{
			$forums->func->standard_error("erroroperation");
		}
		if ($this->forum['specialtopic'])
		{
			$forums->func->check_cache('st');
			$this->st = explode(",", $this->forum['specialtopic']);
			$specialtopic = '';
			foreach ($this->st as $id)
			{
				$specialtopic .= '<option value="' . $forums->cache['st'][$id]['id'] . '">' . $forums->cache['st'][$id]['name'] . '</option>';
			}
		}
		$thread = array();
		if (count($this->tids) > 1)
		{
			$DB->query("SELECT title, tid FROM " . TABLE_PREFIX . "thread WHERE tid IN(" . implode(",", $this->tids) . ")");
			while ($row = $DB->fetch_array())
			{
				$thread[] = $row;
			}
			$pagetitle = $forums->lang['batchsetspecialtopic'] . " - " . $bboptions['bbtitle'];
		}
		else
		{
			$tid = $this->thread['tid'];
			$t_title = $this->thread['title'];
			$thread[] = $this->thread;
			$pagetitle = $forums->lang['setspecialtopic'] . ": " . $t_title . " - " . $bboptions['bbtitle'];
		}
		$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>");
		$t_title .= '<input type="hidden" name="t" value="' . $_INPUT['t'] . '" />';
		include $forums->func->load_template('_specialtopic_set');
		exit;
	}

	function dospecialtopic()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['cansetst']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$st_id = intval($_INPUT['st_id']);
		if ($st_id == '' OR $st_id == -1)
		{
			$forums->func->standard_error("cannotfindst");
		}
		$this->st = explode(',', $this->forum['specialtopic']);
		if (!in_array($st_id, $this->st))
		{
			$forums->func->standard_error('cannotsetst', false, $this->forum['name']);
		}
		$this->modfunc->thread_st($this->tids, $st_id);
		$forums->func->check_cache('st');
		$forums->lang['settospecialtopic'] = sprintf($forums->lang['settospecialtopic'], $forums->cache['st'][$st_id]['name']);
		$this->moderate_log($forums->lang['settospecialtopic']);
		$this->thread_log($this->tids, $forums->lang['settospecialtopic']);
		$forums->lang['hassettost'] = sprintf($forums->lang['hassettost'], $forums->cache['st'][$st_id]['name']);
		$url = ($_INPUT['t']) ? "showthread.php{$forums->sessionurl}t=" . $_INPUT['t'] : "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id'];
		$forums->func->redirect_screen($forums->lang['hassettost'], $url);
	}

	function unspecialtopic()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$fid = $this->forum['id'];
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['cansetst']) ? true : false;
		if (count($this->tids) == 0 OR $passed != 1)
		{
			$forums->func->standard_error("erroroperation");
		}
		$this->modfunc->thread_st($this->tids, 0);
		if (count ($this->tids) > 1)
		{
			$this->moderate_log($forums->lang['unsetspecialtopic'] . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
			$this->thread_log($this->tids, $forums->lang['unsetspecialtopic']);
		}
		else
		{
			$this->moderate_log($forums->lang['unsetspecialtopic'] . " - " . $this->thread['title']);
			$this->thread_log($this->thread['tid'], $forums->lang['unsetspecialtopic']);
		}

		$url = ($_INPUT['t']) ? "showthread.php{$forums->sessionurl}t=" . $_INPUT['t'] : "forumdisplay.php{$forums->sessionurl}f=" . $this->forum['id'];
		$forums->func->redirect_screen($forums->lang['unsetspecialtopic'] . $forums->lang['actioned'], $url);
		exit;
	}

	function mergethread()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$fid = $this->forum['id'];
		$fname = $this->forum['name'];
		$posthash = $this->posthash;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['candeletethreads'] OR $this->moderator['canmergethreads']) ? true : false;
		if (count($this->tids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$thread = array();
		if (count ($this->tids) > 1)
		{
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid IN (" . implode(",", $this->tids) . ") ORDER BY dateline ASC");
			while ($row = $DB->fetch_array())
			{
				$thread[] = $row;
			}
			$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>");
		}
		else
		{
			$show['single'] = true;
			$tid = $this->thread['tid'];
			$t_title = $this->thread['title'];
			$t_desc = $this->thread['description'];
			$thread[] = $this->thread;
			$nav = array ("<a href='forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}'>{$this->forum['name']}</a>",
				"<a href='showthread.php{$forums->sessionurl}t={$this->thread['tid']}'>{$this->thread['title']}</a>"
				);
		}
		$pagetitle = $forums->lang['mergethread'] . " - " . $bboptions['bbtitle'];
		include $forums->func->load_template('_merge_thread');
		exit;
	}

	function domerge()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$passed = ($bbuserinfo['supermod'] || $this->moderator['candeletethreads'] || $this->moderator['canmergethreads']) ? true : false;
		$count = count($this->tids);
		if ($count == 0 || !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		if (($count < 2 && empty($_INPUT['threadurl'])) || ($count == 1 && $_INPUT['threadurl'] == ''))
		{
			$forums->func->standard_error("selectmerge");
		}
		if ($count < 2)
		{
			preg_match("/(\?|&amp;)t=(\d+)($|&amp;)/", $_INPUT['threadurl'], $match);
			$old_id = intval(trim($match[2]));
			if (!$old_id)
			{
				$forums->func->standard_error("cannotfindmerge");
			}

			$old_thread = $DB->query_first('SELECT tid, title, forumid, lastpost, lastposterid, lastposter, post, views
				FROM ' . TABLE_PREFIX . "thread
				WHERE tid = $old_id");
			if (!$old_thread)
			{
				$forums->func->standard_error("cannotfindmerge");
			}
			$this->thread = $DB->query_first('SELECT *
				FROM ' . TABLE_PREFIX . 'thread
				WHERE ' . $DB->sql_in('tid', $this->tids));
			if ($old_id == $this->thread['tid'])
			{
				$forums->func->standard_error("mergenotsame");
			}
			$pass = false;
			if ($this->thread['forumid'] == $old_thread['forumid'])
			{
				$pass = true;
			}
			else
			{
				if ($bbuserinfo['supermod'])
				{
					$pass = true;
				}
				else
				{
					$result = $DB->query('SELECT moderatorid
						FROM ' . TABLE_PREFIX . "moderator
						WHERE forumid = {$old_thread['forumid']}
							AND (userid = {$bbuserinfo['id']}
								OR (isgroup = 1
									AND usergroupid = {$bbuserinfo['usergroupid']}))");
					if ($DB->num_rows($result))
					{
						$pass = true;
					}
				}
			}
			if ($pass == false)
			{
				$forums->func->standard_error("cannotmerge");
			}
			$new_id = $this->thread['tid'];
			$posttable = $this->thread['posttable']?$this->thread['posttable']:'post';
			$new_title = $_INPUT['title'] ? $_INPUT['title'] : $this->thread['title'];
			$new_desc = $_INPUT['description'] ? $_INPUT['description'] : $this->thread['description'];
			$merge_ids[] = $old_thread['tid'];
			if ($this->thread['forumid'] != $old_thread['forumid'])
			{
				$this->recount($old_thread['forumid']);
			}
			$forums->lang['mergethreadto'] = sprintf($forums->lang['mergethreadto'], $old_thread['title'], $new_title);
			$this->moderate_log($forums->lang['mergethreadto']);
		}
		else
		{
			$thread = array();
			$result = $DB->query('SELECT tid, title, description
				FROM ' . TABLE_PREFIX . 'thread
				WHERE ' . $DB->sql_in('tid', $this->tids) . '
				ORDER BY dateline ASC');
			while ($row = $DB->fetch_array($result))
			{
				$thread[] = $row;
			}
			if (count($thread) < 2)
			{
				$forums->func->standard_error("selectmerge");
			}
			$first_thread = array_shift($thread);
			$new_id = $first_thread['tid'];
			$new_title = $_INPUT['title'] ? $_INPUT['title'] : $first_thread['title'];
			$new_desc = $_INPUT['description'] ? $_INPUT['description'] : $first_thread['description'];
			$merge_ids = array();
			foreach($thread as $t)
			{
				$merge_ids[] = $t['tid'];
			}
			$this->moderate_log($forums->lang['mergethread'] . " - " . $forums->lang['threadid'] . ": " . implode(",", $this->tids));
		}

		$merge_ids = implode(',', $merge_ids);
		$threadid_in_merge_ids = "threadid IN ($merge_ids)";
		$tid_in_merge_ids = "tid IN ($merge_ids)";
		$DB->update(TABLE_PREFIX . $posttable, array('threadid' => $new_id), $threadid_in_merge_ids);
		$DB->update(TABLE_PREFIX . 'thread', array('title' => $new_title, 'description' => $new_desc), "tid = $new_id");
		$DB->delete(TABLE_PREFIX . 'poll', $tid_in_merge_ids);
		$DB->delete(TABLE_PREFIX . 'subscribethread', $threadid_in_merge_ids);
		$DB->delete(TABLE_PREFIX . 'thread', $tid_in_merge_ids);
		$this->modfunc->rebuild_thread($new_id);
		$this->recount($this->forum['id']);
		$forums->func->redirect_screen($forums->lang['hasmerged'], "showthread.php{$forums->sessionurl}t=" . $new_id);
	}

	function deletethread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['candeletethreads'] OR ($this->thread['postuserid'] == $bbuserinfo['id'] AND $bbuserinfo['candeletethread']) ) ? TRUE : FALSE;
		if ($_INPUT['threadid'])
		{
			$this->tids = array();
			$tids = explode(",", $_INPUT['threadid']);
			foreach ($tids AS $key)
			{
				$key = intval($key);
				if (!empty($key))
				{
					$this->tids[] = $key;
				}
			}
		}
		if (count( $this->tids ) == 0 || !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$fid = $this->forum['id'];
		if (!$_INPUT['update'])
		{
			$threadids = implode(',', $this->tids);
			$pagetitle = $forums->lang['deletethread'] . ' - ' . $bboptions['bbtitle'];
			$nav = array("<a href=\"forumdisplay.php{$forums->sessionurl}f={$this->forum['id']}\">{$this->forum['name']}</a>");
			include $forums->func->load_template('_delete_thread');
			exit;
		}
		else
		{
			foreach ($this->tids AS $link_thread)
			{
				$links = $DB->query( "SELECT tid, forumid FROM ".TABLE_PREFIX."thread WHERE open=2 AND moved LIKE '".$link_thread."&%'" );
				if ( $linked_thread = $DB->fetch_array($links) )
				{
					$del_tids[] = $linked_thread['tid'];
					if (!$d_ceche[$linked_thread['forumid']])
					{
						$d_ceche[$linked_thread['forumid']] = $linked_thread['forumid'];
					}
				}
			}
			if (is_array($del_tids))
			{
				$DB->delete(TABLE_PREFIX . 'thread', $DB->sql_in('tid', $del_tids));
				foreach ($d_ceche AS $forumid)
				{
					$this->recount($forumid);
				}
			}
			$delthread = $threadforum = $recountids = array();
			$threads = $DB->query( "SELECT t.postuserid, t.tid, t.title, t.forumid, u.name
						FROM ".TABLE_PREFIX."thread t
					LEFT JOIN ".TABLE_PREFIX."user u
						ON u.id=t.postuserid
					WHERE tid IN(".implode(",",$this->tids).")" );
			while ($thread = $DB->fetch_array($threads))
			{
				$delthread[$thread['postuserid']][] = $thread;
				$threadforum[$thread['tid']] = $thread['forumid'];
				$recountids[] = $thread['forumid'];
			}
			//给删除主题的用户发送消息
			if ($_INPUT['deletepmusers'] && !empty($delthread))
			{
				foreach ($delthread AS $userid => $delthreadinfo)
				{
					$deltitle = '';
					foreach($delthreadinfo AS $delinfo)
					{
						if ($this->recycleforum == $delinfo['forumid'])
						{
							continue;
						}
						$deltitle .= "<li>".$delinfo['title']."</li>\n";
					}
					if (!$deltitle) continue;
					$_INPUT['title'] = $forums->lang['yourthreaddeleted'];
					$forums->lang['yourthreaddeletedinfos'] = sprintf( $forums->lang['yourthreaddeletedinfo'], $deltitle, $_INPUT['deletereason'] );
					$_POST['post'] = $forums->lang['yourthreaddeletedinfos'];
					$_INPUT['username'] = $delinfo['name'];
					require_once(ROOT_PATH . 'includes/functions_private.php');
					$pm = new functions_private();
					$_INPUT['noredirect'] = 1;
					$bboptions['pmallowhtml'] = 1;
					$bboptions['usewysiwyg'] = 1;
					$pm->sendpm();
				}
			}

			if ( $this->recycleforum && $this->recycleforum != $this->forum['id'] )
			{
				//搜索中删除主题
				if (!$this->forum['id'])
				{
					foreach ($threadforum as $tid => $forumid)
					{
						$this->modfunc->thread_move($tid, $forumid, $this->recycleforum);
						$this->moderate_log($forums->lang['movetorecycle']);
						$this->thread_log($forums->lang['movetorecycle']);
					}
				}
				else
				{
					$this->modfunc->thread_move($this->tids, $this->forum['id'], $this->recycleforum);
					$this->moderate_log($forums->lang['movetorecycle']);
					$this->thread_log($forums->lang['movetorecycle']);
				}
				$redirectstr = $forums->lang['hastorecycle'];
			}
			else
			{
				$this->modfunc->thread_delete($this->tids);
				$this->moderate_log($forums->lang['deletethread']);
				$redirectstr = $forums->lang['deletethread'];
			}

			if (!$this->forum['id'] && !empty($recountids))
			{
				$fids = array_unique($recountids);
				foreach ($fids as $fid)
				{
					$this->recount($fid);
				}
			}
			else
			{
				$this->recount($this->forum['id']);
			}

			$this->recount($this->recycleforum);
			if ($this->forum['id'])
			{
				$forums->func->redirect_screen( $redirectstr, "forumdisplay.php{$forums->sessionurl}f=".$this->forum['id'] );
			}
			else
			{
				$forums->func->redirect_screen( $action.$forums->lang['actioned'], "index.php");
			}
		}
	}

	//恢复主题
	function revertthread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (count($this->tids) == 0)
		{
			$forums->func->standard_error("erroroperation");
		}
		$recountids = $revertpids = $reverttids = array();
		$result = $DB->query('SELECT tid, rawforumid, forumid FROM ' . TABLE_PREFIX . 'thread WHERE tid IN (' . implode(',', $this->tids) . ')');
		while ($row = $DB->fetch_array($result))
		{
			if ($this->recycleforum && $row['forumid']!=$this->recycleforum)
			{
				continue;
			}
			if (!$row['rawforumid'])
			{
				$recthread = $DB->query_first('SELECT posttable
						FROM ' . TABLE_PREFIX . 'thread
					WHERE tid ='. $row['tid']);
				$posttable = $recthread['posttable']?$recthread['posttable']:'post';

				$rs = $DB->query('SELECT p.pid, t.forumid, t.tid
						FROM ' . TABLE_PREFIX . "$posttable p
					LEFT JOIN " . TABLE_PREFIX . 'thread t
						ON p.rawthreadid = t.tid
					WHERE p.threadid ='. $row['tid']);
				while ($r = $DB->fetch_array($rs))
				{
					$rawtid = $r['tid'];
					$rawfid = $r['forumid'];
					$revertpids[] = $r['pid'];
				}

				$recountids[] = $rawfid;
				$DB->query('UPDATE '.TABLE_PREFIX . "$posttable SET threadid=" . $rawtid . ", newthread=0, rawthreadid='' WHERE threadid=" . $row['tid']);
				$DB->query_unbuffered('DELETE FROM ' . TABLE_PREFIX . 'thread WHERE tid=' . $row['tid']);
				$this->modfunc->rebuild_thread($rawtid);
			}
			else
			{
				$recountids[] = $row['rawforumid'];
				$reverttids[] = $row['tid'];
				$DB->query('UPDATE '.TABLE_PREFIX . "thread SET forumid=rawforumid, addtorecycle='',rawforumid='' WHERE tid=" . $row['tid']);
				$DB->query('UPDATE '.TABLE_PREFIX . "poll SET forumid=rawforumid, addtorecycle='', rawforumid='' WHERE tid=" . $row['tid']);
			}
			$this->moderate_log($forums->lang['revertthread']);
		}
		if (!empty($recountids))
		{
			$fids = array_unique($recountids);
			foreach ($fids as $fid)
			{
				$this->recount($fid);
			}
		}
		$this->recount($this->recycleforum);

		//恢复用户积分
		if (!empty($revertpids))
		{
			$this->modfunc->processcredit($revertpids, 'newreply', 'post');
		}
		if (!empty($reverttids))
		{
			$this->modfunc->processcredit($reverttids, 'newthread');
		}
		if ($this->forum['id'])
		{
			$forums->func->redirect_screen( $forums->lang['revertthreadfromrecycle'], "forumdisplay.php{$forums->sessionurl}f=".$this->forum['id'] );
		}
		else
		{
			$forums->func->redirect_screen( $action.$forums->lang['actioned'], "search.php");
		}
	}

	function unsubscribeall()
	{
		global $forums, $DB, $bbuserinfo;
		if (!$bbuserinfo['supermod'] || empty($this->thread['tid']))
		{
			$forums->func->standard_error("noperms");
		}
		$DB->shutdown_delete(TABLE_PREFIX . 'subscribethread', "threadid = {$this->thread['tid']}");
		$forums->func->redirect_screen($forums->lang['hasunsubscribe'], "showthread.php{$forums->sessionurl}t={$this->thread['tid']}");
	}

	function movepost()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		$forumid = $this->forum['id'];
		$threadid = $this->thread['tid'];
		$posttable = $this->thread['posttable']?$this->thread['posttable']:'post';
		$passed = ($bbuserinfo['supermod'] || $this->moderator['canremoveposts']) ? true : false;
		if (count($this->pids) == 0 || !$this->thread['tid'] || !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		require_once (ROOT_PATH . "includes/functions_codeparse.php");
		$this->parser = new functions_codeparse();
		if (!$_INPUT['update'])
		{
			$result = $DB->query('SELECT pagetext, pid, dateline, userid, username
				FROM ' . TABLE_PREFIX . "$posttable
				WHERE " . $DB->sql_in('pid', $this->pids) . '
				ORDER BY dateline');
			$post_count = 0;
			while ($row = $DB->fetch_array($result))
			{
				if (strlen($row['pagetext']) > 800)
				{
					$row['pagetext'] = $this->parser->unconvert($row['pagetext']);
					$row['pagetext'] = substr(strip_tags($row['pagetext']), 0, 800) . '...';
				}
				$row['date'] = $forums->func->get_date($row['dateline'], 2);
				$row['post_css'] = $post_count % 2 ? 'row1' : 'row2';
				$post_count++;
				$post[] = $row;
			}
			$pagetitle = $forums->lang['movepost'] . ' - ' . $bboptions['bbtitle'];
			$nav = array("<a href=\"showthread.php{$forums->sessionurl}t={$this->thread['tid']}\">{$this->thread['title']}</a>", $forums->lang['movepost']);
			include $forums->func->load_template('_move_post');
			exit;
		}
		else
		{
			$affected_ids = count($this->pids);
			if ($affected_ids < 1)
			{
				$forums->func->standard_error('notselectmove');
			}
			if (! intval($_INPUT['threadurl']))
			{
				preg_match("/(\?|&amp;)t=(\d+)($|&amp;)/", $_INPUT['threadurl'], $match);
				$old_id = intval(trim($match[2]));
			}
			else
			{
				$old_id = intval($_INPUT['threadurl']);
			}
			if ($old_id == '')
			{
				$forums->func->standard_error('erroraddress');
			}
			$move_to_thread = $DB->query_first('SELECT tid, forumid, title
				FROM ' . TABLE_PREFIX . "thread
				WHERE tid = $old_id");
			if (!$move_to_thread['tid'] || !$forums->forum->foruminfo[$move_to_thread['forumid']]['id'])
			{
				$forums->func->standard_error('erroraddress');
			}
			$count = $DB->query_first('SELECT COUNT(pid) AS count
				FROM ' . TABLE_PREFIX . "$posttable
				WHERE threadid = {$this->thread['tid']}");
			if ($affected_ids >= $count['count'])
			{
				$forums->func->standard_error('erroraddress');
			}
			$DB->update(TABLE_PREFIX . $posttable, array('threadid' => $move_to_thread['tid'], 'newthread' => 0), $DB->sql_in('pid', $this->pids));
			$DB->update(TABLE_PREFIX . $posttable, array('newthread' => 0), "threadid = {$this->thread['tid']}");

			$this->modfunc->rebuild_thread($move_to_thread['tid']);
			$this->modfunc->rebuild_thread($this->thread['tid']);
			$this->recount($this->thread['forumid']);
			if ($this->thread['forumid'] != $move_to_thread['forumid'])
			{
				$this->recount($move_to_thread['forumid']);
			}
			$forums->lang['movepostto'] = sprintf($forums->lang['movepostto'], $this->thread['title'], $move_to_thread['title']);
			$this->moderate_log($forums->lang['movepostto']);
			$forums->func->redirect_screen($forums->lang['posthasmoved'], "showthread.php{$forums->sessionurl}t=" . $this->thread['tid']);
		}
	}

	//删除帖子
	function deletepost()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		$forumid = $this->forum['id'];
		$threadid = $this->thread['tid'];
		if (count($this->pids) == 0)
		{
			$forums->func->standard_error("erroroperation");
		}
		if ($_INPUT['deltype'] == 'search' && $bbuserinfo['supermod'])
		{
			$torecyclepost = $delpids = array();

			$splittable = array();
			$forums->func->check_cache('splittable');
			$splittable = $forums->cache['splittable']['all'];

			foreach ($this->pids as $pid)
			{
				$tname = $curtable = '';
				foreach ($splittable as $tableid => $content)
				{
					if ($pid > $content['minpid'] && $pid < $content['maxpid'])
					{
						$tname = $content['name'];
					}
				}
				if (!$tname)
				{
					$deftable = $forums->cache['splittable']['default'];
					$curtable = $deftable['name']?$deftable['name']:'post';
				}
				else
				{
					$curtable = $tname;
				}
				$row = $DB->query_first("SELECT p.pid, t.forumid, t.title
						FROM ".TABLE_PREFIX."$curtable p
					LEFT JOIN ".TABLE_PREFIX."thread t
						ON p.threadid = t.tid
					WHERE p.pid = $pid");

				if ($this->recycleforum && $this->recycleforum != $row['forumid'])
				{
					$torecyclepost[] = $pid;
					$del_tid = $_INPUT['tid'][$pid];
					$recountids[] = $row['forumid'];
					$rawthread = $DB->query_first("SELECT count(*) as num, threadid FROM ".TABLE_PREFIX."$curtable WHERE rawthreadid={$del_tid} GROUP BY threadid");
					if ($rawthread['num'] <= 0)
					{
						$_INPUT['fid'] = $this->recycleforum;
						$_INPUT['title'] = $forums->lang['searchdeleted'];
						$_INPUT['description'] = $forums->lang['threadid'].":$del_tid";
						$newthread = array('title' => $_INPUT['title'],
							'description' => $_INPUT['description'],
							'open' => 1,
							'post' => 0,
							'postuserid' => 0,
							'postusername' => 0,
							'dateline' => TIMENOW,
							'lastposterid' => 0,
							'lastposter' => 0,
							'lastpost' => TIMENOW,
							'iconid' => 0,
							'pollstate' => 0,
							'lastvote' => 0,
							'views' => 0,
							'forumid' => $_INPUT['fid'],
							'visible' => 1,
							'sticky' => 0,
							'addtorecycle' => TIMENOW,
						);
						$DB->insert(TABLE_PREFIX . 'thread', $newthread);
						$threadid = $DB->insert_id();
					}
					$threadid = $rawthread['threadid']?$rawthread['threadid']:$threadid;
					$DB->query_unbuffered( "UPDATE ".TABLE_PREFIX."$curtable SET threadid=$threadid, newthread=0,  rawthreadid=$del_tid WHERE pid = $pid");
					$forums->lang['movethreadtorecycle'] = sprintf($forums->lang['movethreadtorecycle'], $row['title']);
					$this->moderate_log($forums->lang['movethreadtorecycle']);
					$this->modfunc->rebuild_thread($del_tid);
					$this->modfunc->rebuild_thread($threadid);
				}
				else
				{
					$delpids[$pid] = $curtable;
					$this->modfunc->post_delete($delpids);
				}
			}
			if (!empty($torecyclepost))
			{
				$this->modfunc->processcredit($torecyclepost, 'delreply', 'post', 1);
			}
			if (!empty($recountids))
			{
				foreach ($recountids as $fid)
				{
					$this->recount($fid);
				}
			}
			$this->recount($this->recycleforum);

			$userid = intval($_INPUT['userid']);
			$forums->func->redirect_screen( $forums->lang['posthasdeleted'], "search.php?do=finduser&u=$userid");
		}
		$posttable = $this->thread['posttable']?$this->thread['posttable']:'post';
		$post = $DB->query_first( "SELECT pid, userid, dateline, newthread
		FROM ".TABLE_PREFIX."$posttable
		WHERE pid IN (" . implode(",", $this->pids) . ')');
		$passed = ($bbuserinfo['supermod'] || $this->moderator['candeleteposts'] || ($bbuserinfo['candeletepost'] && $bbuserinfo['id'] == $post['userid'])) ? TRUE : FALSE;
		if (!$threadid || !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		foreach ($this->pids AS $p)
		{
			//包含主题帖
			if ($this->thread['firstpostid'] == $p)
			{
				$pagetitle = $forums->lang['deletethread'].": ".strip_tags($this->thread['title'])." - ".$bboptions['bbtitle'];
				$nav = array( "<a href='showthread.php{$forums->sessionurl}t={$this->thread['tid']}'>{$this->thread['title']}</a>", $forums->lang['deletethread'] );
				include $forums->func->load_template('_delpost_first');
				exit;
			}
		}
		if ( $this->recycleforum && $this->recycleforum != $this->forum['id'] )
		{
			$_INPUT['update'] = 1;
			$_INPUT['fid'] = $this->recycleforum;
			$forums->lang['fromdeleted'] = sprintf( $forums->lang['fromdeleted'], $this->thread['title'] );
			$_INPUT['title'] = $forums->lang['fromdeleted'];
			$_INPUT['description'] = $forums->lang['threadid'].": ".$this->thread['tid'];
			$this->userecycle = 1;
			$this->splitthread();
			$this->userecycle = 0;
		}
		else
		{
			$posttable = $this->thread['posttable']?$this->thread['posttable']:'post';
			$this->modfunc->post_delete($this->pids, 0, $posttable);
			$this->recount( $this->thread['forumid'] );
			$forums->func->redirect_screen( $forums->lang['posthasdeleted'], "showthread.php{$forums->sessionurl}t=".$this->thread['tid'] );
		}
	}

	function revertpost()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (count($this->pids) <= 0)
		{
			$forums->func->standard_error("erroroperation");
		}
		$recountids = array();
		$curtable = $this->thread['posttable']?$this->thread['posttable']:'post';
		$result = $DB->query('SELECT * FROM ' . TABLE_PREFIX . "$curtable WHERE pid IN (" . implode(',', $this->pids) . ')');
		while ($row = $DB->fetch_array($result))
		{
			if ($this->recycleforum && $row['forumid']!=$this->recycleforum)
			{
				continue;
			}
			if (!$row['rawthreadid'])
			{
				$forums->func->standard_error("errorrevertpost");
			}
			else
			{
				$rawthread = $DB->query_first('SELECT tid, forumid FROM ' . TABLE_PREFIX . 'thread WHERE tid ='.$row['rawthreadid']);
				if (!$rawthread['tid'])
				{
					$forums->func->standard_error("errorrevertpost");
				}
				$DB->query('UPDATE '.TABLE_PREFIX . "$curtable SET threadid=" . $row['rawthreadid'] . ", rawthreadid='' WHERE pid=" . $row['pid']);
				$addthread = $DB->query_first('SELECT count(*) as num FROM ' . TABLE_PREFIX . '$curtable WHERE threadid ='.$row['threadid']);
				if ($addthread['num'] <= 0)
				{
					$DB->query_unbuffered('DELETE FROM ' . TABLE_PREFIX . 'thread WHERE tid=' . $row['threadid']);
				}
				else
				{
					$this->modfunc->rebuild_thread($row['threadid']);
				}
				$recountids[] = $rawthread['forumid'];
				$this->modfunc->rebuild_thread($row['rawthreadid']);
				$this->moderate_log($forums->lang['revertpostfromrecycle']);
			}
		}
		$fids = array_unique($recountids);
		foreach ($fids as $fid)
		{
			$this->recount($rawthread['forumid']);
		}
		//恢复用户积分
		$this->modfunc->processcredit($this->pids, 'newreply', 'post');
		$this->recount($this->recycleforum);

		if ($this->forum['id'])
		{
			$forums->func->redirect_screen( $forums->lang['revertthreadfromrecycle'], "forumdisplay.php{$forums->sessionurl}f=".$this->forum['id'] );
		}
		else
		{
			$forums->func->redirect_screen( $action.$forums->lang['actioned'], "search.php");
		}
	}

	//分割主题
	function splitthread()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$posthash = $this->posthash;
		$forumid = $this->forum['id'];
		$threadid = $this->thread['tid'];
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['cansplitthreads'] OR $this->userecycle) ? TRUE : FALSE;
		if (count($this->pids)==0 OR !$threadid OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$curtable = $this->thread['posttable']?$this->thread['posttable']:'post';
		require_once(ROOT_PATH."includes/functions_codeparse.php");
        $this->parser = new functions_codeparse();
		if (!$_INPUT['update'])
		{
			$forum_jump = $forums->func->construct_forum_jump(0,1);
			$t_title = $this->thread['title'];
			$DB->query( "SELECT pagetext, pid, dateline, userid, username FROM ".TABLE_PREFIX."$curtable WHERE pid IN (".implode(",", $this->pids).") ORDER BY dateline");
			$post_count = 0;
			while ( $row = $DB->fetch_array() )
			{
				if ( strlen($row['pagetext']) > 800 )
				{
					$row['pagetext'] = $this->parser->unconvert($row['pagetext']);
					$row['pagetext'] = substr($row['pagetext'], 0, 500) . '...';
				}
				$row['date'] = $forums->func->get_date( $row['dateline'], 2 );
				$row['post_css'] = $post_count % 2 ? 'row1' : 'row2';
				$post_count++;
				$post[] = $row;
			}
			$pagetitle = $forums->lang['splitthread'].": ".$this->thread['title']." - ".$bboptions['bbtitle'];
			$nav = array( "<a href='showthread.php{$forums->sessionurl}t={$this->thread['tid']}'>{$this->thread['title']}</a>", $forums->lang['splitthread'] );
			include $forums->func->load_template('_split_thread');
			exit;
		}
		else
		{
			if ($_INPUT['title'] == "")
			{
				$forums->func->standard_error("plzinputallform");
			}
			$affected_ids = count($this->pids);
			if ($affected_ids < 1)
			{
				$forums->func->standard_error("notselectsplit");
			}

			$count = $DB->query_first( "SELECT count(pid) as cnt FROM ".TABLE_PREFIX."$curtable WHERE threadid=".$this->thread['tid']."" );
			if ( $affected_ids >= $count['cnt'] )
			{
				$forums->func->standard_error("notselectsplit");
			}
			$pids = implode( ",", $this->pids );
			$_INPUT['fid'] = intval($_INPUT['fid']);
			if ($_INPUT['fid'] != $this->forum['id'])
			{
				$forum = $forums->forum->single_forum($_INPUT['fid']);
				if (! $forum['id'])
				{
					$forums->func->standard_error("selectsplit");
				}
				if ($forum['allowposting'] != 1)
				{
					$forums->func->standard_error("cannotsplit");
				}
			}
			$rawthread = $DB->query_first("SELECT count(*) as num, threadid FROM ".TABLE_PREFIX."$curtable WHERE rawthreadid={$this->thread['tid']} GROUP BY threadid");
			if (!$this->userecycle || ($this->userecycle && $rawthread['num'] <= 0))
			{
				$newthread = array('title'				=> $_INPUT['title'],
								   'description'		=> $_INPUT['description'] ,
								   'open'				=> 1,
								   'post'				=> 0,
								   'postuserid'         => 0,
								   'postusername'	    => 0,
								   'dateline'			=> TIMENOW,
								   'lastposterid'		=> 0,
								   'lastposter'			=> 0,
								   'lastpost'			=> TIMENOW,
								   'iconid'				=> 0,
								   'pollstate'			=> 0,
								   'lastvote'			=> 0,
								   'views'				=> 0,
								   'forumid'			=> $_INPUT['fid'],
								   'visible'			=> 1,
								   'sticky'				=> 0,
									);
				if ($this->userecycle) $newthread = array_merge($newthread, array('addtorecycle'=>TIMENOW));
				$DB->insert(TABLE_PREFIX . 'thread', $newthread);
				$threadid = $DB->insert_id();
			}
			if ($this->userecycle)
			{
				$threadid = $rawthread['threadid']?$rawthread['threadid']:$threadid;
				$sql = ", rawthreadid={$this->thread['tid']}";
			}

			$DB->query_unbuffered( "UPDATE ".TABLE_PREFIX."$curtable SET threadid=$threadid, newthread=0 $sql WHERE pid IN($pids)" );

			$this->modfunc->rebuild_thread($threadid);
			$this->modfunc->rebuild_thread($this->thread['tid']);
			$this->recount($this->thread['forumid']);
			if ($this->thread['forumid'] != $_INPUT['fid'])
			{
				$this->recount($_INPUT['fid']);
			}
			if ( $this->userecycle )
			{
				$forums->lang['movethreadtorecycle'] = sprintf( $forums->lang['movethreadtorecycle'], $this->thread['title'] );
				$this->moderate_log($forums->lang['movethreadtorecycle']);
				$forums->func->redirect_screen( $forums->lang['posthasdeleted'], "showthread.php{$forums->sessionurl}t=".$this->thread['tid'] );
				$this->modfunc->processcredit($this->pids, 'delreply', 'post', 1);
			}
			else
			{
				$this->moderate_log($forums->lang['splitthread']." '".$this->thread['title']."'");
				$forums->func->redirect_screen( $forums->lang['hassplited'], "showthread.php{$forums->sessionurl}t=".$threadid );
			}
		}
	}

	function approvepost($type = 1)
	{
		global $forums, $DB, $bbuserinfo;
		$passed = ($bbuserinfo['supermod'] OR $this->moderator['canmoderateposts']) ? true : false;
		if (count($this->pids) == 0 OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$curtable = $this->thread['posttable']?$this->thread['posttable']:'post';
		$at = 1;
		$ap = 0;
		if ($type != 1)
		{
			$at = 0;
			$ap = 1;
		}
		if (in_array($this->thread['firstpostid'], $this->pids))
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET visible=" . $at . " WHERE tid=" . $this->thread['tid'] . "");
			$tmp = $this->pids;
			$this->pids = array();
			foreach($tmp AS $t)
			{
				if ($t != $this->thread['firstpostid'])
				{
					$this->pids[] = $t;
				}
			}
		}
		if (count($this->pids))
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "$curtable SET moderate=" . $ap . " WHERE pid IN (" . implode(",", $this->pids) . ")");
		}
		$this->modfunc->rebuild_thread($this->thread['tid']);
		$this->recount($this->thread['forumid']);
		$forums->func->redirect_screen($forums->lang['hasapproved'], "showthread.php{$forums->sessionurl}t=" . $this->thread['tid']);
	}

	function announcement()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$DB->query("SELECT a.*, u.name FROM " . TABLE_PREFIX . "announcement a LEFT JOIN " . TABLE_PREFIX . "user u on (a.userid=u.id) ORDER BY a.active, a.enddate DESC");
		$content = "";
		while ($r = $DB->fetch_array())
		{
			$r['startdate'] = $r['startdate'] ? $forums->func->get_time($r['startdate'], 'Y-m-d') : '-';
			$r['enddate'] = $r['enddate'] ? $forums->func->get_time($r['enddate'], 'Y-m-d') : '-';
			if ($r['forumid'] == -1)
			{
				$r['forumlist'] = $forums->lang['allforum'];
			}
			else
			{
				$tmp_forums = explode(",", $r['forumid']);
				if (is_array($tmp_forums) AND count($tmp_forums))
				{
					$tmp2 = array();
					foreach($tmp_forums AS $id)
					{
						$tmp2[] = "<a href='forumdisplay.php{$forums->sessionurl}f=" . $id . "' target='_blank'>" . $forums->forum->foruminfo[$id]['name'] . "</a>";
					}
					$r['forumlist'] = implode("<br />", $tmp2);
				}
			}
			if (!$r['active'])
			{
				$r['inactive'] = "<span class='description'>" . $forums->lang['noactive'] . "</span>";
			}
			$r['action'] = '';
			if ($bbuserinfo['id'] == $r['userid'] OR $bbuserinfo['supermod'])
			{
				$r['action'] = "[<a href='moderate.php{$forums->sessionurl}do=doannouncement&amp;id=" . $r['id'] . "'>" . $forums->lang['edit'] . "</a>] [<a href='#' onclick='delete_post(\"moderate.php{$forums->sessionurl}do=deleteannouncement&amp;id=" . $r['id'] . "\"); return false;'>" . $forums->lang['delete'] . "</a>]";
			}
			$announce[] = $r;
		}
		$pagetitle = $forums->lang['announcement'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['announcement']);
		include $forums->func->load_template('mod_announcement');
		exit;
	}

	function announcementform($errors = '')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$_INPUT['id'] = intval($_INPUT['id']);
		$forum_html = '';
		if ($_INPUT['id'])
		{
			$button = $forums->lang['editannouncement'];
			if (!$announce = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE id=" . $_INPUT['id'] . ""))
			{
				$forums->func->standard_error("cannotfindannounce");
			}
			if ($bbuserinfo['id'] != $announce['userid'] && !$bbuserinfo['supermod'])
			{
				$forums->func->standard_error("noperms");
			}
			$pagetitle = $forums->lang['editannouncement'] . " - " . $bboptions['bbtitle'];
			$nav = array("<a href='moderate.php{$forums->sessionurl}do=announcement'>" . $forums->lang['announcement'] . "</a>", $forums->lang['editannouncement']);
		}
		else
		{
			$button = $forums->lang['addannouncement'];
			$announce = array('active' => 1);
			$pagetitle = $forums->lang['addannouncement'] . " - " . $bboptions['bbtitle'];
			$nav = array("<a href='moderate.php{$forums->sessionurl}do=announcement'>" . $forums->lang['announcement'] . "</a>", $forums->lang['addannouncement']);
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$parser = new functions_codeparse();
		$announce['title'] = $announce['title'] ? $parser->unconvert($announce['title']) : $_POST['title'];
		$announce['pagetext'] = $announce['pagetext'] ? $parser->unconvert($announce['pagetext'], 1, 1, 0) : $_POST['post'];
		$announce['pagetext'] = preg_replace("#<br.*>#siU", "\n", $announce['pagetext']);
		$announce['forumids'] = $announce['forumid'] ? explode(",", $announce['forumid']) : $_POST['announceforum'];
		$announce['startdate'] = $announce['startdate'] ? $forums->func->get_time($announce['startdate'], 'Y-m-d') : ($_POST['startdate'] ? $_POST['startdate'] : $forums->func->get_time(TIMENOW, 'Y-m-d'));
		$announce['enddate'] = $announce['enddate'] ? $forums->func->get_time($announce['enddate'], 'Y-m-d') : ($_POST['enddate'] ? $_POST['enddate'] : $forums->func->get_time(TIMENOW + 2592000, 'Y-m-d'));
		if ($bbuserinfo['supermod'])
		{
			$forum_html .= "<option value='-1'>" . $forums->lang['allforum'] . "</option><optgroup label='-----------------------'>" . $forums->forum->forum_jump();
		}
		if (is_array($bbuserinfo['_moderator']))
		{
			foreach ($bbuserinfo['_moderator'] AS $id => $value)
			{
				$forum_html .= "<option value='" . $id . "'>" . $forums->forum->foruminfo[$id]['name'] . "</option>";
			}
		}
		if (is_array($announce['forumids']) AND count($announce['forumids']))
		{
			foreach($announce['forumids'] AS $f)
			{
				$forum_html = preg_replace('#<option[^>]+value=(\'|")(' . $f . ')(\\1)>#siU', "<option value='\\2' selected='selected'>", $forum_html);
			}
		}
		$forum_html .= "</optgroup>";
		$announce['active_checked'] = $announce['active'] ? 'checked="checked"' : '';
		$announce['allowhtml'] = $announce['allowhtml'] ? 'checked="checked"' : '';
		$announce['pagetext'] = br2nl($announce['pagetext']);
		include $forums->func->load_template('add_announcement');
		exit;
	}

	function updateannouncement()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		if (! $_INPUT['title'] OR ! $_INPUT['post'])
		{
			return $this->announcementform($forums->lang['requireannouncement']);
		}
		$forumids = '';
		if (is_array($_INPUT['announceforum']) AND count($_INPUT['announceforum']))
		{
			if (in_array('-1', $_INPUT['announceforum']) AND $bbuserinfo['supermod'])
			{
				$forumids = '-1';
			}
			else
			{
				if ($bbuserinfo['supermod'])
				{
					$forumids = implode(",", $_INPUT['announceforum']);
				}
				else
				{
					foreach ($bbuserinfo['_moderator'] AS $id => $value)
					{
						if (in_array($id, $_INPUT['announceforum']))
						{
							$ids[] = $id;
						}
					}
					$forumids = implode(",", $ids);
				}
			}
		}
		if (empty($forumids))
		{
			return $this->announcementform($forums->lang['selectforum']);
		}
		$startdate = 0;
		$enddate = 0;
		if (strstr($_INPUT['startdate'], '-'))
		{
			$start_array = explode('-', $_INPUT['startdate']);
			if ($start_array[0] AND $start_array[1] AND $start_array[2])
			{
				if (!checkdate($start_array[1], $start_array[2], $start_array[0]))
				{
					return $this->announcementform($forums->lang['errorstartdate']);
				}
			}
			else
			{
				return $this->announcementform($forums->lang['errorenddate']);
			}
			$startdate = $forums->func->mk_time(0, 0, 1, $start_array[1], $start_array[2], $start_array[0]);
		}
		if (strstr($_INPUT['enddate'], '-'))
		{
			$end_array = explode('-', $_INPUT['enddate']);
			if ($end_array[0] AND $end_array[1] AND $end_array[2])
			{
				if (!checkdate($end_array[1], $end_array[2], $end_array[0]))
				{
					return $this->announcementform($forums->lang['errorenddate']);
				}
			}
			else
			{
				return $this->announcementform($forums->lang['errorenddate']);
			}
			$enddate = $forums->func->mk_time(23, 59, 59, $end_array[1], $end_array[2], $end_array[0]);
		}
		require (ROOT_PATH . "includes/functions_codeparse.php");
		$parser = new functions_codeparse();
		$save_array = array(
			'title' => $parser->convert(array(
				'text' => utf8_htmlspecialchars($_POST['title']),
				'allowsmilies' => 0,
				'allowcode' => 1
			)),
			'pagetext' => $parser->convert(array(
				'text' => utf8_htmlspecialchars($_POST['post']),
				'allowsmilies' => $_INPUT['allowsmile'],
				'allowcode' => $_INPUT['allowbbcode']
			)),
			'active' => $_INPUT['active'],
			'forumid' => $forumids,
			'allowhtml' => intval($_INPUT['allowhtml']),
			'startdate' => $startdate,
			'enddate' => $enddate
		);
		if (!$_INPUT['id'])
		{
			$save_array['userid'] = $bbuserinfo['id'];
			$DB->insert(TABLE_PREFIX . 'announcement', $save_array);
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'announcement', $save_array, 'id=' . intval($_INPUT['id']));
		}
		$forums->func->recache('announcement');
		return $this->announcement();
	}

	function deleteannouncement()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if (! $bbuserinfo['is_mod'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$id = intval($_INPUT['id']);
		if (!$announce = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "announcement WHERE id=" . $id . ""))
		{
			$forums->func->standard_error("cannotfindannounce");
		}
		if ($bbuserinfo['id'] != $announce['userid'] && !$bbuserinfo['supermod'])
		{
			$forums->func->standard_error("cannotdelannounce");
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "announcement WHERE id=" . $id . "");
		$forums->func->recache('announcement');
		return $this->announcement();
	}

	function findmember()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (!$bbuserinfo['supermod'] && !$bbuserinfo['caneditusers'])
		{
			$forums->func->standard_error("erroroperation");
		}
		$pagetitle = $forums->lang['findmember'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['findmember']);
		//include $forums->func->load_template('mod_findmember');
		//need modify
		echo "findmember";
		exit;
	}

	function dofindmember()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$username = trim($_INPUT['username']);
		$first = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$passed = ($bbuserinfo['supermod'] OR $bbuserinfo['caneditusers']) ? true : false;
		if (empty($username) OR !$passed)
		{
			$forums->func->standard_error("erroroperation");
		}
		$count = $DB->query_first("SELECT COUNT(*) AS counts FROM " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE concat('" . strtolower($username) . "','%') OR name LIKE concat('" . $username . "','%')");
		if (!$count['counts'])
		{
			$forums->func->standard_error("cannotfindmember");
		}
		$pages = $forums->func->build_pagelinks(array('totalpages' => $count['counts'],
				'perpage' => 20,
				'curpage' => $first,
				'pagelink' => "moderate.php{$forums->sessionurl}do=dofindmember&amp;username=$username",
				));
		$forums->func->check_cache('usergroup');
		$users = $DB->query("SELECT name, id, host, posts, joindate, usergroupid FROM " . TABLE_PREFIX . "user WHERE name LIKE '$username%' ORDER BY joindate DESC LIMIT $first, 20");
		while ($user = $DB->fetch_array($users))
		{
			$user['joindate'] = $forums->func->get_date($user['joindate'], 3);
			$user['grouptitle'] = $forums->cache['usergroup'][$user['usergroupid']]['opentag'] . $forum->lang[ $forums->cache['usergroup'][$user['usergroupid']]['grouptitle'] ] . $forums->cache['usergroup'][$user['usergroupid']]['closetag'];
			if ($bbuserinfo['usergroupid'] != 4 AND $user['usergroupid'] == 4)
			{
				$user['host'] = '--';
			}
			$userlist[] = $user;
		}
		$pagetitle = $forums->lang['findmember'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['findmember']);
		//include $forums->func->load_template('mod_showmember');
		//need modify
		echo "mod_showmember";
		exit;
	}

	function sendpm($message, $title, $to_user)
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pmtext
							(dateline, message, deletedcount, savedcount)
						VALUES
							(" . TIMENOW . ", '" . addslashes($message) . "', 0, 1)"
			);
		$pmtextid = $DB->insert_id();
		$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pm
								(messageid, dateline, title, fromuserid, touserid, folderid, userid)
							VALUES
								(" . $pmtextid . ", " . TIMENOW . ", '" . addslashes($title) . "', " . $bbuserinfo['id'] . ", " . $to_user . ", 0, " . $to_user . ")"
			);
		$this->rebuild_foldercount($to_user, "", '0', '-1', 'save', ",pmtotal=pmtotal+1, pmunread=pmunread+1");
	}

	function rebuild_foldercount($userid, $folders, $curfolderid, $pmcount, $nosave = 'save', $extra = "")
	{
		global $DB, $forums;
		$rebuild = array();
		if (! $folders)
		{
			$user = $DB->query_first("SELECT pmfolders FROM " . TABLE_PREFIX . "user WHERE id=" . $userid . "");
			$def_folders = array('0' => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']),
				'-1' => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']),
				);
			$folders = $user['pmfolders'] ? unserialize($user['pmfolders']) : $def_folders;
		}
		foreach($folders AS $id => $data)
		{
			if ($id == $curfolderid)
			{
				$data['pmcount'] = ($pmcount == '-1') ? intval($data['pmcount'] + 1) : intval($pmcount);
			}
			$rebuild[$id] = $data;
		}
		$pmfolders = addslashes(serialize($rebuild));
		if ($nosave != 'nosave')
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmfolders='" . $pmfolders . "' " . $extra . " WHERE id=" . $userid . "");
		}
		return $pmfolders;
	}
}

$output = new moderate();
$output->show();

?>