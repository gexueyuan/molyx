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
require ('./global.php');

class threads
{
	var $thread = array();
	function show()
	{
		global $forums, $_INPUT;
		$this->admin = explode(',', SUPERADMIN);
		$forums->admin->nav[] = array('threads.php', $forums->lang['managethreads']);
		require_once(ROOT_PATH . "includes/functions_moderate.php");
		$this->mod = new modfunctions();
		switch ($_INPUT['do'])
		{
			case 'massprune':
				$this->massoperate('prune');
				break;
			case 'massmove':
				$this->massoperate('move');
				break;
			case 'domassprune':
				$this->domassoperate('prune');
				break;
			case 'domassmove':
				$this->domassoperate('move');
				break;
			case 'pruneuserthread':
				$this->dopruneuser();
				break;
			case 'pruneuserselect':
				$this->pruneuserselect();
				break;
			case 'finishoperate':
				$this->finishoperate();
				break;
			case 'operateselect':
				$this->operateselect();
				break;
			case 'dooperateselect':
				$this->dooperateselect();
				break;
			default:
				$this->massoperate('move');
				break;
		}
	}

	function recount($forumid = '')
	{
		$this->mod->forum_recount($forumid);
	}

	function massoperate($type = 'move')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if ($type == 'move')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassmovethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$pagetitle = $forums->lang['batchmovethread'];
			$detail = $forums->lang['batchmovethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchmovethread']);
			$button = $forums->lang['movethread'];
			$code = 'domassmove';
		}
		else
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$pagetitle = $forums->lang['batchdeletethread'];
			$detail = $forums->lang['batchdeletethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchdeletethread']);
			$button = $forums->lang['deletethread'];
			$code = 'domassprune';
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', $code)), 'bytform');
		$allforum = $forums->adminforum->forumcache;
		$forumlist[] = array(-2, $forums->lang['selectforums']);
		$forumlist[] = array(-1, $forums->lang['allforums']);
		foreach($allforum AS $key => $value)
		{
			$forumlist[] = array($value[id], depth_mark($value['depth'], '--') . $value[name]);
		}
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sourceforum'] . ":</strong>", $forums->admin->print_input_select_row("thread[forum_id]", $forumlist) . $forums->admin->print_checkbox_row("thread[subforums]", 0, 1) . $forums->lang['includesubforum']));
		if ($type == 'move')
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['destforum'] . ":</strong>", $forums->admin->print_input_select_row("thread[move_id]", $forumlist)));
		}
		$forums->admin->print_cells_single_row($forums->lang['dateoptions'], "left", "pformstrip");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['originaldaysolder'] . "</strong>", $forums->admin->print_input_row("thread[originaldaysolder]", 0)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['originaldaysnewer'] . "</strong>", $forums->admin->print_input_row("thread[originaldaysnewer]", 0)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['lastdaysolder'] . "</strong>", $forums->admin->print_input_row("thread[lastdaysolder]", 0)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['lastdaysnewer'] . "</strong>", $forums->admin->print_input_row("thread[lastdaysnewer]", 0)));
		$forums->admin->print_cells_single_row($forums->lang['viewoptions'], "left", "pformstrip");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['repliesleast'] . "</strong>", $forums->admin->print_input_row("thread[repliesleast]", 0)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['repliesmost'] . "</strong><br />(" . $forums->lang['ignoreoptions'] . ")", $forums->admin->print_input_row("thread[repliesmost]", -1)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['viewsleast'] . "</strong>", $forums->admin->print_input_row("thread[viewsleast]", 0)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['viewsmost'] . "</strong><br />(" . $forums->lang['ignoreoptions'] . ")", $forums->admin->print_input_row("thread[viewsmost]", -1)));
		$forums->admin->print_cells_single_row($forums->lang['threadstatus'], "left", "pformstrip");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['issticky'] . "</strong>", $forums->admin->print_yes_no_row("thread[issticky]", 0, $forums->lang['any'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['isquintessence'] . "</strong>", $forums->admin->print_yes_no_row("thread[isquintessence]", 0, $forums->lang['any'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['isclosed'] . "</strong>", $forums->admin->print_yes_no_row("thread[isclosed]", -1, $forums->lang['any'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['isvisible'] . "</strong>", $forums->admin->print_yes_no_row("thread[isvisible]", -1, $forums->lang['any'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['isredirect'] . "</strong>", $forums->admin->print_yes_no_row("thread[isredirect]", -1, $forums->lang['any'])));
		$forums->admin->print_cells_single_row($forums->lang['otheroptions'], "left", "pformstrip");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['threadstarter'] . "</strong>", $forums->admin->print_input_row("thread[threadstarter]", '')));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['threadtitle'] . "</strong>", $forums->admin->print_input_row("thread[threadtitle]", '')));
		$forums->admin->print_form_submit($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		if ($type == 'prune')
		{
			$forums->admin->print_form_header(array(1 => array('do', 'pruneuserthread')), 'byuserform');
			$forums->admin->columns[] = array("&nbsp;" , "40%");
			$forums->admin->columns[] = array("&nbsp;" , "60%");
			$forums->admin->print_table_start($forums->lang['prunebyuser']);
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['username'] . "</strong>", $forums->admin->print_input_row("username", '')));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sourceforum'] . ":</strong>", $forums->admin->print_input_select_row("forum_id", $forumlist) . $forums->admin->print_checkbox_row("subforums", 0, 1) . $forums->lang['includesubforum']));
			$forums->admin->print_form_submit($button);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
		}
		$forums->admin->print_cp_footer();
	}

	function dopruneuser()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$username = trim($_INPUT['username']);
		if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		if ($username == "" OR $_INPUT['forum_id'] == -2)
		{
			$forums->admin->print_cp_error($forums->lang['requirepruneoptions']);
		}
		if (!$user = $DB->query_first("SELECT id,name FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'"))
		{
			$forums->admin->print_cp_error($forums->lang['cannotfindstarter']);
		}
		if (!$_INPUT['finishprune'])
		{
			$pagetitle = $forums->lang['batchdeletethread'];
			$detail = $forums->lang['batchdeletethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchdeletethread']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			if ($_INPUT['forum_id'] != -1)
			{
				$forum = $DB->query_first("SELECT name FROM " . TABLE_PREFIX . "forum WHERE id=" . $_INPUT['forum_id'] . "");
				$forums->lang['deleteuserthreads'] = sprintf($forums->lang['deleteuserthreads'], $user['user'], $forum['name']);
				$forumtitle = $forums->lang['deleteuserthreads'];
			}
			else
			{
				$forums->lang['deleteuserallthreads'] = sprintf($forums->lang['deleteuserallthreads'], $user['user']);
				$forumtitle = $forums->lang['deleteuserallthreads'];
			}
			$forums->admin->print_form_header(array(1 => array('do', 'pruneuserthread'), 2 => array('finishprune', 1), 3 => array('subforums', $_INPUT['subforums']), 4 => array('username', $_INPUT['username']), 5 => array('forum_id', $_INPUT['forum_id'])), 'automatic');
			$forums->admin->print_table_start($forums->lang['automatic'] . $forumtitle);
			$forums->admin->print_cells_single_row($forums->lang['clickautomatic'] . $forumtitle);
			$forums->admin->print_form_submit($forums->lang['automatic'] . $forumtitle);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_form_header(array(1 => array('do', 'pruneuserselect'), 2 => array('subforums', $_INPUT['subforums']), 3 => array('username', $_INPUT['username']), 4 => array('forum_id', $_INPUT['forum_id'])), 'selectable');
			$forums->admin->print_table_start($forums->lang['selectable'] . $forumtitle);
			$forums->admin->print_cells_single_row($forums->lang['clickselectable'] . $forumtitle);
			$forums->admin->print_form_submit($forums->lang['selectable'] . $forumtitle);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
		else
		{
			if ($_INPUT['forum_id'] != -1)
			{
				if ($_INPUT['subforums'])
				{
					$forumcheck = "(t.forumid = " . $_INPUT['forum_id'] . " OR f.parentlist LIKE '%," . $_INPUT['forum_id'] . ",%' OR f.parentlist LIKE '" . $_INPUT['forum_id'] . ",%' OR f.parentlist LIKE '%," . $_INPUT['forum_id'] . "') AND ";
				}
				else
				{
					$forumcheck = "t.forumid = " . $_INPUT['forum_id'] . " AND ";
				}
			}
			else
			{
				$forumcheck = '';
			}
			$threadids = array();
			$threads = $DB->query("SELECT tid,title FROM " . TABLE_PREFIX . "thread t LEFT JOIN " . TABLE_PREFIX . "forum f ON (t.forumid=f.id) WHERE $forumcheck postusername = '" . $user['name'] . "'");
			while ($thread = $DB->fetch_array($threads))
			{
				$threadids[] = $thread['tid'];
			}
			$this->mod->thread_delete($threadids);
			$this->recount($_INPUT['forum_id']);
			$forums->admin->recount_stats();
			$forums->lang['batchdeluserthreads'] = sprintf($forums->lang['batchdeluserthreads'], $user['name']);
			$forums->lang['utbatchdeleted'] = sprintf($forums->lang['utbatchdeleted'], $user['name']);
			$forums->admin->save_log($forums->lang['batchdeluserthreads']);
			$forums->admin->redirect("threads.php?do=massprune", $forums->lang['batchdeletethread'], $forums->lang['utbatchdeleted']);
		}
	}

	function pruneuserselect()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$username = trim($_INPUT['username']);
		if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		if ($username == "" OR $_INPUT['forum_id'] == -2)
		{
			$forums->admin->print_cp_error($forums->lang['requirepruneoptions']);
		}
		if (!$user = $DB->query_first("SELECT id,name FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "' OR name='" . $username . "'"))
		{
			$forums->admin->print_cp_error($forums->lang['cannotfindstarter']);
		}
		if (!$_INPUT['finishprune'])
		{
			$pagetitle = $forums->lang['batchdeletethread'];
			$detail = $forums->lang['batchdeletethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchdeletethread']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'pruneuserselect'), 2 => array('finishprune', 1), 3 => array('subforums', $_INPUT['subforums']), 4 => array('username', $_INPUT['username']), 5 => array('forum_id', $_INPUT['forum_id'])));
			$forums->admin->columns[] = array($forums->lang['thread'], "90%");
			$forums->admin->columns[] = array($forums->lang['delete'], "10%");
			$forums->admin->print_table_start($forums->lang['deletethread']);
			if ($_INPUT['forum_id'] != -1)
			{
				if ($_INPUT['subforums'])
				{
					$forumcheck = "(t.forumid = " . $_INPUT['forum_id'] . " OR f.parentlist LIKE '%," . $_INPUT['forum_id'] . ",%') AND ";
				}
				else
				{
					$forumcheck = "t.forumid = " . $_INPUT['forum_id'] . " AND ";
				}
			}
			else
			{
				$forumcheck = '';
			}
			$threads = $DB->query("SELECT t.tid,t.title FROM " . TABLE_PREFIX . "thread t LEFT JOIN " . TABLE_PREFIX . "forum f ON (t.forumid=f.id) WHERE $forumcheck postuserid = '" . $user['id'] . "' ORDER BY t.lastpost DESC
			");
			while ($thread = $DB->fetch_array($threads))
			{
				$threadids[] = $thread['tid'];
				$forums->admin->print_cells_row(array("<a href='../showthread.php?{$forums->sessionurl}t=$thread[tid]' target='_blank'>$thread[title]</a>", $forums->admin->print_checkbox_row('deletethread[' . $thread['tid'] . ']', 1, 1)));
			}
			$forums->admin->print_table_footer();
			$forums->admin->print_table_start($forums->lang['deletepost']);

			$posts = $DB->query("SELECT p.pid,t.tid,t.title FROM " . TABLE_PREFIX . "post p, " . TABLE_PREFIX . "thread t LEFT JOIN " . TABLE_PREFIX . "forum f ON (t.forumid=f.id) WHERE t.tid = p.threadid AND t.firstpostid <> p.pid AND $forumcheck p.userid='" . $user['id'] . "' ORDER BY p.threadid DESC, p.dateline DESC");
			while ($post = $DB->fetch_array($posts))
			{
				$forums->admin->print_cells_row(array("<a href='../redirect.php?t={$post[tid]}&amp;goto=findpost&amp;p=$post[pid]' target='_blank'>$post[title]</a>", $forums->admin->print_checkbox_row('deletepost[' . $post['pid'] . ']', 1, 1)));
			}
			$forums->admin->print_form_submit($forums->lang['deleteselectedtp']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
		else
		{
			if (is_array($_POST['deletethread']))
			{
				$threadids = array();
				foreach ($_POST['deletethread'] AS $threadid => $confirm)
				{
					if ($confirm == 1)
					{
						$threadids[] = $threadid;
					}
				}
				$this->mod->thread_delete($threadids);
			}
			if (is_array($_POST['deletepost']))
			{
				foreach ($_POST['deletepost'] AS $postid => $confirm)
				{
					if ($confirm == 1)
					{
						$postids[] = $postid;
					}
				}
				$this->mod->post_delete($postids);
			}
			$this->recount($_INPUT['forum_id']);
			$forums->admin->recount_stats();
			$forums->lang['batchdeluserthreads'] = sprintf($forums->lang['batchdeluserthreads'], $user['name']);
			$forums->lang['utbatchdeleted'] = sprintf($forums->lang['utbatchdeleted'], $user['name']);
			$forums->admin->save_log($forums->lang['batchdeluserthreads']);
			$forums->admin->redirect("threads.php?do=massprune", $forums->lang['batchdeletethread'], $forums->lang['utbatchdeleted']);
		}
	}

	function domassoperate($type = 'move')
	{
		global $DB, $_INPUT, $forums, $bbuserinfo;
		if ($_INPUT['forum_id'] == -2)
		{
			$forums->admin->print_cp_error($forums->lang['selectbatchdelforum']);
		}
		$this->thread = $_INPUT['thread'];
		$this->fetch_thread_move_prune_sql();
		$forum_id = intval($this->thread['forum_id']);
		$move_id = intval($this->thread['move_id']);
		if ($type == 'move')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassmovethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			if ($forum_id == $move_id)
			{
				$forums->admin->print_cp_error($forums->lang['destnotsamesource']);
			}
			$allforum = $forums->adminforum->forumcache;
			$foruminfo = $allforum[$move_id];
			if (!$foruminfo)
			{
				$forums->admin->print_cp_error($forums->lang['selectbatchmoveforum']);
			}
			if (!$foruminfo['allowposting'])
			{
				$forums->admin->print_cp_error($forums->lang['destforumnothreads']);
			}
			$pagetitle = $forums->lang['batchmovethread'];
			$detail = $forums->lang['batchmovethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchmovethread']);
		}
		else
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$pagetitle = $forums->lang['batchdeletethread'];
			$detail = $forums->lang['batchdeletethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchdeletethread']);
		}
		$fullquery = "
			SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "thread t
			LEFT JOIN " . TABLE_PREFIX . "forum f ON (f.id = t.forumid)
			WHERE $this->query
		";
		$cnt = $DB->query_first($fullquery);
		if (!$cnt['count'])
		{
			$forums->admin->print_cp_error($forums->lang['nomatchresult']);
		}
		$forums->lang['totalrecords'] = sprintf($forums->lang['totalrecords'], $cnt['count']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'finishoperate'), 2 => array('type', $type), 3 => array('contains', serialize($this->thread))), 'autoform');
		$forums->admin->print_table_start($forums->lang['automatic'] . $pagetitle . " - " . $forums->lang['totalrecords']);
		$forums->admin->print_cells_single_row($forums->lang['clickautomatic'] . $pagetitle);
		$forums->admin->print_form_submit($forums->lang['automatic'] . $pagetitle);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do', 'operateselect'), 2 => array('type', $type), 3 => array('contains', serialize($this->thread))), 'clickform');
		$forums->admin->print_table_start($forums->lang['selectable'] . $pagetitle . " - " . $forums->lang['totalrecords']);
		$forums->admin->print_cells_single_row($forums->lang['clickselectable'] . $pagetitle);
		$forums->admin->print_form_submit($forums->lang['clickselectable'] . $pagetitle);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function finishoperate()
	{
		global $DB, $_INPUT, $forums, $bbuserinfo;
		$this->thread = unserialize($_POST['contains']);
		$this->fetch_thread_move_prune_sql();
		$fullquery = "
			SELECT t.tid, f.name
			FROM " . TABLE_PREFIX . "thread t
			LEFT JOIN " . TABLE_PREFIX . "forum f ON (f.id = t.forumid)
			WHERE $this->query
		";
		$threads = $DB->query($fullquery);
		if ($_INPUT['type'] == 'prune')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$threadids = array();
			while ($thread = $DB->fetch_array($threads))
			{
				$threadids[] = $thread['tid'];
			}
			$this->mod->thread_delete($threadids);
			$this->recount($this->thread['forum_id']);
			$forums->admin->recount_stats();
			$forums->lang['batchdelforumthreads'] = sprintf($forums->lang['batchdelforumthreads'], $thread['name']);
			$forums->lang['ftbatchdeleted'] = sprintf($forums->lang['ftbatchdeleted'], $thread['name']);
			$forums->admin->save_log($forums->lang['batchdelforumthreads']);
			$forums->admin->redirect("threads.php?do=massprune", $forums->lang['batchdeletethread'], $forums->lang['ftbatchdeleted']);
		}
		else if ($_INPUT['type'] == 'move')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassmovethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$move_id = intval($this->thread['move_id']);
			$threadslist = '0';
			while ($thread = $DB->fetch_array($threads))
			{
				$threadslist .= ",$thread[tid]";
			}
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET forumid = $move_id WHERE tid IN ($threadslist)");
			$this->recount($this->thread['forum_id']);
			$this->recount($move_id);
			$forums->lang['batchmoveforumthreads'] = sprintf($forums->lang['batchmoveforumthreads'], $thread['name']);
			$forums->lang['ftbatchmoved'] = sprintf($forums->lang['ftbatchmoved'], $thread['name']);
			$forums->admin->save_log($forums->lang['batchmoveforumthreads']);
			$forums->admin->redirect("threads.php?do=massmove", $forums->lang['batchmovethread'], $forums->lang['ftbatchmoved']);
		}
	}

	function operateselect()
	{
		global $DB, $_INPUT, $forums, $bbuserinfo;
		$this->thread = unserialize($_POST['contains']);
		$this->fetch_thread_move_prune_sql();
		$fullquery = "
			SELECT t.*, f.name
			FROM " . TABLE_PREFIX . "thread t
			LEFT JOIN " . TABLE_PREFIX . "forum f ON (f.id = t.forumid)
			WHERE $this->query
		";
		$threads = $DB->query($fullquery);
		if ($_INPUT['type'] == 'prune')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$pagetitle = $forums->lang['batchdeletethread'];
			$detail = $forums->lang['batchdeletethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchdeletethread']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'dooperateselect'), 2 => array('type', $_INPUT['type']), 3 => array('forum_id', $this->thread['forum_id'])));
			$forums->admin->columns[] = array($forums->lang['threadtitle'], "50%");
			$forums->admin->columns[] = array($forums->lang['threadstarter'], "13%");
			$forums->admin->columns[] = array($forums->lang['replies'], "10%");
			$forums->admin->columns[] = array($forums->lang['lastpost'], "22%");
			$forums->admin->columns[] = array($forums->lang['delete'], "5%");
			$forums->admin->print_table_start($pagetitle);
			while ($thread = $DB->fetch_array($threads))
			{
				$forums->admin->print_cells_row(array("<a href=\"../showthread.php?{$forums->sessionurl}t=$thread[tid]\" target=\"_blank\">$thread[title]</a>", $thread['postusername'], $thread['post'], $forums->func->get_date($thread['lastpost'], 2), $forums->admin->print_checkbox_row('thread[' . $thread['tid'] . ']', 1, 1)));
			}
			$forums->admin->print_form_submit($forums->lang['deleteselectedthreads']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
		else if ($_INPUT['type'] == 'move')
		{
			if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassmovethreads'])
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			$pagetitle = $forums->lang['batchmovethread'];
			$detail = $forums->lang['batchmovethreaddesc'];
			$forums->admin->nav[] = array('' , $forums->lang['batchmovethread']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'dooperateselect'), 2 => array('type', $_INPUT['type']), 3 => array('move_id', $this->thread['move_id']), 4 => array('forum_id', $this->thread['forum_id'])));
			$forums->admin->columns[] = array($forums->lang['threadtitle'], "50%");
			$forums->admin->columns[] = array($forums->lang['threadstarter'], "13%");
			$forums->admin->columns[] = array($forums->lang['replies'], "10%");
			$forums->admin->columns[] = array($forums->lang['lastpost'], "22%");
			$forums->admin->columns[] = array($forums->lang['move'], "5%");
			$forums->admin->print_table_start($pagetitle);
			while ($thread = $DB->fetch_array($threads))
			{
				$forums->admin->print_cells_row(array("<a href=\"../showthread.php?{$forums->sessionurl}t=$thread[tid]\" target=\"_blank\">$thread[title]</a>", $thread['postusername'], $thread['post'], $forums->func->get_date($thread['lastpost'], 2), $forums->admin->print_checkbox_row('thread[' . $thread['tid'] . ']', 1, 1)));
			}
			$forums->admin->print_form_submit($forums->lang['moveselectedthreads']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}

	function dooperateselect()
	{
		global $DB, $_INPUT, $forums, $bbuserinfo;
		if (is_array($_POST['thread']))
		{
			if ($_INPUT['type'] == 'prune')
			{
				if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassprunethreads'])
				{
					$forums->admin->print_cp_error($forums->lang['nopermissions']);
				}
				$threadids = array();
				foreach ($_POST['thread'] AS $threadid => $confirm)
				{
					if ($confirm == 1)
					{
						$threadids[] = $threadid;
					}
				}
				$this->mod->thread_delete($threadids);
				$this->recount($_INPUT['forum_id']);
				$forums->admin->recount_stats();
				$forums->admin->save_log($forums->lang['batchdeletethread']);
				$forums->admin->redirect("threads.php?do=massprune", $forums->lang['batchdeletethread'], $forums->lang['forumthreaddeleted']);
			}
			if ($_INPUT['type'] == 'move')
			{
				if (!in_array($bbuserinfo['id'], $this->admin) && !$forums->adminperms['canmassmovethreads'])
				{
					$forums->admin->print_cp_error($forums->lang['nopermissions']);
				}
				foreach ($_POST['thread'] AS $threadid => $confirm)
				{
					if ($confirm == 1)
					{
						$threadids[] = $threadid;
					}
				}
				$this->mod->thread_move($threadids, $_INPUT['forum_id'], $_INPUT['move_id']);
				$this->recount($_INPUT['forum_id']);
				$this->recount($_INPUT['move_id']);
				$forums->admin->save_log($forums->lang['batchmovethread']);
				$forums->admin->redirect("threads.php?do=massmove", $forums->lang['batchmovethread'], $forums->lang['forumthreadmoved']);
			}
		}
		else
		{
			$forums->admin->print_cp_error($forums->lang['needselecthread']);
		}
	}

	function fetch_thread_move_prune_sql()
	{
		global $DB, $forums;
		$this->query = '1=1';
		if (intval($this->thread['originaldaysolder']))
		{
			$this->query .= ' AND t.dateline <= ' . (TIMENOW - ($this->thread['originaldaysolder'] * 86400));
		}
		if (intval($this->thread['originaldaysnewer']))
		{
			$this->query .= ' AND t.dateline >= ' . (TIMENOW - ($this->thread['originaldaysnewer'] * 86400));
		}
		if (intval($this->thread['lastdaysolder']))
		{
			$this->query .= ' AND t.lastpost <= ' . (TIMENOW - ($this->thread['lastdaysolder'] * 86400));
		}
		if (intval($this->thread['lastdaysnewer']))
		{
			$this->query .= ' AND t.lastpost >= ' . (TIMENOW - ($this->thread['lastdaysnewer'] * 86400));
		}
		if (intval($this->thread['repliesleast']) > 0)
		{
			$this->query .= ' AND t.post >= ' . intval($this->thread['repliesleast']);
		}
		if (intval($this->thread['repliesmost']) > -1)
		{
			$this->query .= ' AND t.post <= ' . intval($this->thread['repliesmost']);
		}
		if (intval($this->thread['viewsleast']) > 0)
		{
			$this->query .= ' AND t.views >= ' . intval($this->thread['viewsleast']);
		}
		if (intval($this->thread['viewsmost']) > -1)
		{
			$this->query .= ' AND t.views <= ' . intval($this->thread['viewsmost']);
		}
		if ($this->thread['issticky'] == 1)
		{
			$this->query .= ' AND t.sticky > 0';
		}
		else if ($this->thread['issticky'] == 0)
		{
			$this->query .= ' AND t.sticky = 0';
		}
		if ($this->thread['isquintessence'] == 1)
		{
			$this->query .= ' AND t.quintessence > 0';
		}
		else if ($this->thread['isquintessence'] == 0)
		{
			$this->query .= ' AND t.quintessence = 0';
		}
		if ($this->thread['isclosed'] == 1)
		{
			$this->query .= ' AND t.open = 0';
		}
		else if ($this->thread['isclosed'] == 0)
		{
			$this->query .= ' AND t.open = 1';
		}
		if ($this->thread['isvisible'] == 1)
		{
			$this->query .= ' AND t.visible = 1';
		}
		else if ($this->thread['isvisible'] == 0)
		{
			$this->query .= ' AND t.visible = 0';
		}
		if ($this->thread['isredirect'] == 1)
		{
			$this->query .= ' AND t.open = 2';
		}
		else if ($this->thread['isredirect'] == 0)
		{
			$this->query .= ' AND t.open <> 2';
		}
		if ($this->thread['threadstarter'])
		{
			if (!$user = $DB->query_first("SELECT id FROM " . TABLE_PREFIX . "user WHERE name = '" . addslashes($this->thread['threadstarter']) . "'"))
			{
				$forums->admin->print_cp_error($forums->lang['cannotfindstarter']);
			}
			$this->query .= " AND t.postuserid = " . $user['id'];
		}
		if ($this->thread['threadtitle'])
		{
			$this->query .= " AND t.title LIKE '%" . $this->thread['threadtitle'] . "%'";
		}
		if ($this->thread['forum_id'] != -1)
		{
			if ($this->thread['subforums'])
			{
				$this->query .= " AND (t.forumid = " . $this->thread['forum_id'] . " OR f.parentlist LIKE '%," . $this->thread['forum_id'] . ",%' OR f.parentlist LIKE '" . $this->thread['forum_id'] . ",%' OR f.parentlist LIKE '%," . $this->thread['forum_id'] . "')";
			}
			else
			{
				$this->query .= " AND t.forumid = " . $this->thread['forum_id'] . "";
			}
		}
	}
}

$output = new threads();
$output->show();

?>