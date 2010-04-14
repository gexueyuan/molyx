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
define('THIS_SCRIPT', 'newthread');
require_once('./global.php');

class newthread
{
	var $posthash = '';
	var $post = array();
	var $thread = array();
	var $type = "new";
	var $creditevent = '';

	function show()
	{
		global $forums, $DB, $_INPUT;
		$this->posthash = $_INPUT['posthash'] ? $_INPUT['posthash'] : md5(microtime());
		if ($_INPUT['t'])
		{
			$this->type = 'reply';
			$this->thread = $DB->query_first("SELECT t.*, u.usergroupid FROM " . TABLE_PREFIX . "thread t
											  		LEFT JOIN " . TABLE_PREFIX . "user u ON (t.postuserid=u.id)
			                                  WHERE t.forumid='" . intval($_INPUT['f']) . "' AND t.tid='" . intval($_INPUT['t']) . "'");
			if (! $this->thread['tid'])
			{
				$forums->func->load_lang('error');
				$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
				$contents = convert($forums->lang['erroraddress']);
				include $forums->func->load_template('wap_info');
				exit;
			}
		}
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		require ROOT_PATH . "includes/functions_post.php";
		$this->lib = new functions_post();
		$this->lib->dopost($this);
	}

	function showform()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$this->check_permission();

		$posttitle = ($this->type == 'new') ? $forums->lang['newthread'] : $forums->lang['newreply'];
		$posthash = $this->posthash;
		$userhash = $this->lib->userhash;

		if ($this->type == 'new')
		{
			$isthread = true;
		}
		if ($this->lib->obj['errors'])
		{
			$show['errors'] = true;
			$message = convert($this->lib->obj['errors']);
		}

		$posttitle = convert($posttitle);
		$forums->lang['title'] = convert($forums->lang['title']);
		$forums->lang['content'] = convert($forums->lang['content']);

		$pagetitle = $forums->lang['newthread'] . " - " . $bboptions['bbtitle'];
		$nav = $this->lib->nav;
		include $forums->func->load_template('wap_post');
		exit;
	}

	function process()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$this->check_permission();
		$forums->func->load_lang('post');
		$this->post = $this->lib->compile_post();
		if ($this->type == 'new')
		{
			$_INPUT['title'] = trim($_INPUT['title']);
			if ((utf8_strlen($_INPUT['title']) < 2) OR (!$_INPUT['title']))
			{
				$this->lib->obj['errors'] = $forums->lang['musttitle'];
			}
			if (strlen(preg_replace("/&#([0-9]+);/", "-", $_INPUT['title'])) > 80)
			{
				$this->lib->obj['errors'] = $forums->lang['titletoolong'];
			}
			$this->creditevent = 'newthread';
		}
		else
		{
			$this->creditevent = 'newreply';
		}
		$crediterrors = $this->credit->check_credit($this->creditevent, $bbuserinfo['usergroupid'], $this->thread['forumid']);
		if (is_array($crediterrors) && !empty($crediterrors))
		{
			$this->lib->obj['errors'] = sprintf($forums->lang['notenoughcreditpost'], implode(',', $crediterrors));
		}
		if (($this->lib->obj['errors'] != ""))
		{
			return $this->showform();
		}
		$this->post['posthash'] = $this->posthash;
		//$this->post['pagetext'] = convert($this->post['pagetext']) . "<br /><br /><div><font class='editinfo'>" . $forums->lang['fromwap'] . "</font></div>";
		$this->post['pagetext'] = convert($this->post['pagetext']);
		$forums->func->check_cache('banksettings');
		if ($this->type == 'new')
		{
			$_INPUT['title'] = $this->lib->parser->censoredwords($_INPUT['title']);
			$_INPUT['title'] = $this->lib->compile_title();
			$_INPUT['title'] = convert($_INPUT['title']);
			$this->thread = array(
				'title' => $_INPUT['title'],
				'titletext' => implode(' ', duality_word(strip_tags($_INPUT['title']))),
				'description' => $_INPUT['description'] ,
				'post' => 0,
				'postuserid' => $bbuserinfo['id'],
				'postusername' => $bbuserinfo['name'],
				'dateline' => TIMENOW,
				'lastposterid' => $bbuserinfo['id'],
				'lastposter' => $bbuserinfo['name'],
				'lastpost' => TIMENOW,
				'pollstate' => 0,
				'lastvote' => 0,
				'views' => 0,
				'iconid' => 0,
				'forumid' => $this->lib->forum['id'],
				'visible' => ($this->lib->obj['moderate'] == 1 || $this->lib->obj['moderate'] == 2) ? 0 : 1,
			);
			$DB->insert(TABLE_PREFIX . 'thread', $this->thread);
			$this->post['threadid'] = $DB->insert_id();
			$this->thread['tid'] = $this->post['threadid'];
			$this->post['newthread'] = 1;
			$this->post['moderate'] = 0;
			$this->post['posttype'] = 1; //自wap发表
			$DB->insert(TABLE_PREFIX . 'post', $this->post);
			$this->post['pid'] = $DB->insert_id();
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread SET firstpostid=" . $this->post['pid'] . ",lastpostid=" . $this->post['pid'] . " WHERE tid='" . $this->thread['tid'] . "'");
			$this->lib->stats_recount($this->post['threadid'], 'new');
			$this->lib->posts_recount();
			$this->credit->update_credit('newthread', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $this->thread['forumid']);
			if ($this->lib->obj['moderate'] == 1 OR $this->lib->obj['moderate'] == 2)
			{
				redirect($gotourl);
			}
		}
		else
		{
			$this->post['threadid'] = $this->thread['tid'];
			$this->post['posttype'] = 1; //自wap发表
			$this->lastpost = $this->thread['lastpost'];
			$DB->insert(TABLE_PREFIX . 'post', $this->post);
			$this->post['pid'] = $DB->insert_id();
			$this->lib->stats_recount($this->post['threadid'], 'reply');
			$post = $DB->query_first("SELECT COUNT(*) as posts FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND moderate != 1");
			$postcount = intval($post['posts'] - 1);
			$modpost = $DB->query_first("SELECT COUNT(*) as posts FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND moderate = 1");
			$modpostcount = intval($modpost['posts']);
			$poster_name = $bbuserinfo['id'] ? $bbuserinfo['name'] : $_INPUT['username'];
			$update_array = array('post' => $postcount,
				'modposts' => $modpostcount
				);
			if ($this->lib->obj['moderate'] != 1 && $this->lib->obj['moderate'] != 3)
			{
				$update_array['lastposterid'] = $bbuserinfo['id'];
				$update_array['lastposter'] = $poster_name;
				$update_array['lastpost'] = TIMENOW;
				$update_array['lastpostid'] = $this->post['pid'];
			}
			$DB->update(TABLE_PREFIX . 'thread', $update_array, 'tid = ' . $this->thread['tid']);
			$this->lib->posts_recount();
			$hideposts = $DB->query("SELECT pid, userid, hidepost FROM " . TABLE_PREFIX . "post WHERE threadid='" . $this->thread['tid'] . "' AND hidepost!=''");
			if ($DB->num_rows($hideposts))
			{
				while ($hidepost = $DB->fetch_array($hideposts))
				{
					$hideinfo = unserialize($hidepost['hidepost']);
					if ($hideinfo['type'] == '111' AND $hidepost['userid'] != $bbuserinfo['id'])
					{
						if (is_array($hideinfo['buyers']) AND in_array($bbuserinfo['name'], $hideinfo['buyers'])) continue;
						$hideinfo['buyers'][] = $bbuserinfo['name'];
						$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "post SET hidepost='" . addslashes(serialize($hideinfo)) . "' WHERE pid='" . $hidepost['pid'] . "'");
					}
				}
			}
			$this->credit->update_credit('newreply', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $this->thread['forumid']);
			$this->credit->update_credit('replythread', $this->thread['postuserid'], $this->thread['usergroupid'], $this->thread['forumid']);
		}
		$gotourl = $_GET['reffer'] ? rawurldecode($_GET['reffer']) : "forum.php{$forums->sessionurl}&amp;f=" . $this->lib->forum['id'] . "";
		redirect($gotourl);
	}

	function check_permission()
	{
		global $forums, $bbuserinfo;
		$cannotpost = false;
		if ($this->type == 'new')
		{
			if (! $bbuserinfo['canpostnew'] OR $forums->func->fetch_permissions($this->lib->forum['canstart'], 'canstart') == false)
			{
				$cannotpost = true;
			}
		}
		else
		{
			if ($this->thread['postuserid'] == $bbuserinfo['id'])
			{
				if (!$bbuserinfo['canreplyown'])
				{
					$cannotpost = true;
				}
			}
			if ($this->thread['postuserid'] != $bbuserinfo['id'])
			{
				if (!$bbuserinfo['canreplyothers'])
				{
					$cannotpost = true;
				}
			}
			if ($forums->func->fetch_permissions($this->lib->forum['canreply'], 'canreply') == false)
			{
				$cannotpost = true;
			}
			if (!$this->thread['open'])
			{
				if (!$bbuserinfo['canpostclosed'])
				{
					$cannotpost = true;
				}
			}
		}
		if ($cannotpost)
		{
			$forums->lang['wapinfo'] = convert($forums->lang['wapinfo']);
			$contents = convert($forums->lang['cannotpost']);
			include $forums->func->load_template('wap_info');
			exit;
		}
	}
}

$output = new newthread();
$output->show();

?>