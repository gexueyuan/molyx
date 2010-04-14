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
define('THIS_SCRIPT', 'newreply');
require_once('./global.php');

class newreply
{
	var $posthash = '';
	var $maxposts = 10;
	var $post = array();
	var $thread = array();

	function show()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forums->func->load_lang('post');
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();
		$this->posthash = $_INPUT['posthash'] ? $_INPUT['posthash'] : md5(microtime());


		$this->thread = $DB->query_first("SELECT t.*, u.usergroupid FROM " . TABLE_PREFIX . "thread t
										 	  LEFT JOIN " . TABLE_PREFIX . "user u
												   ON u.id = t.postuserid
		                                  WHERE t.tid='" . intval($_INPUT['t']) . "' AND t.forumid='" . intval($_INPUT['f']) . "'");
		if (! $this->thread['tid'])
		{
			$forums->func->standard_error("erroraddress");
		}
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		$this->maxposts = $bboptions['maxposts'] ? $bboptions['maxposts'] : '10';
		require ROOT_PATH . "includes/functions_post.php";
		$this->lib = new functions_post();
		$this->lib->dopost($this);
	}

	function showform()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$this->check_permission($this->thread);
		$_POST['post'] = isset($_POST['post']) ? $_POST['post'] : $this->lib->check_multi_quote(1);
		$this->cookie_mxeditor = $forums->func->get_cookie('mxeditor');
		if ($this->cookie_mxeditor)
		{
			$bbuserinfo['usewysiwyg'] = ($this->cookie_mxeditor == 'wysiwyg') ? 1 : 0;
		}
		else if ($bboptions['mxemode'])
		{
			$bbuserinfo['usewysiwyg'] = 1;
		}
		else
		{
			$bbuserinfo['usewysiwyg'] = 0;
		}
		if ($bbuserinfo['usewysiwyg'])
		{
			$_POST['post'] = $this->lib->parser->convert(array(
				'text' => $_POST['post'],
				'allowsmilies' => 1,
				'allowcode' => $this->lib->forum['allowbbcode'],
				'change_editor' => 1
			));
		}
		$content = utf8_htmlspecialchars($_POST['post']);
		$content = preg_replace("#\[code\](.+?)\[/code\]#ies" , "utf8_unhtmlspecialchars('[code]\\1[/code]')", $content);
		$forums->func->check_cache('usergroup');
		$usergrp = $forums->cache['usergroup'];
		$forums->func->check_cache('creditlist');
		$hidecredit = array();
		if ($forums->cache['creditlist'])
		{
			foreach ($forums->cache['creditlist'] as $k => $v)
			{
				$hidecredit[$v['tag']] = $v['name'];
			}
		}
		$hidetypes = $this->hidefunc->generate_hidetype_list();
		if ($this->lib->obj['errors'])
		{
			$show['errors'] = true;
			$errors = $this->lib->obj['errors'];
		}
		if ($this->lib->obj['preview'])
		{
			$show['preview'] = true;
			require_once(ROOT_PATH . 'includes/class_textparse.php');
			$preview = textparse::convert_text($this->post['pagetext']);
		}
		$form_start = $this->lib->fetch_post_form(array(1 => array('do', 'update'),
				2 => array('t', $this->thread['tid']),
				3 => array('parentid', $_INPUT['parentid']),
				4 => array('posthash', $this->posthash),)
			);
		$postdesc = $forums->lang['replythread'] . ": " . $this->thread['title'];
		$modoptions = $this->lib->modoptions();
		if ($this->lib->canupload)
		{
			$show['upload'] = true;
			$upload = $this->lib->fetch_upload_form($this->posthash, 'new');
		}
		$upload['maxnum'] = intval($bbuserinfo['attachnum']);
		$credit_list = $this->credit->show_credit('newreply', $bbuserinfo['usergroupid'], $_INPUT['f']);
		$smiles = $this->lib->construct_smiles();
		$smile_count = $smiles['count'];
		$all_smiles = $smiles['all'];
		$smiles = $smiles['smiles'];
		$icons = $this->lib->construct_icons();
		$checked = $this->lib->construct_checkboxes();
		$pagetitle = $forums->lang['replythread'] . " - " . $bboptions['bbtitle'];
		$nav = array_merge($this->lib->nav, array("<a href='showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "' title='" . strip_tags($this->thread['title']) . "'>" . $forums->func->fetch_trimmed_title($this->thread['title'], 12) . "</a>", $forums->lang['replythread']));
		$extrabuttons = $this->lib->code->construct_extrabuttons();
		$previewfunc = ' onclick="preview_post(' . $this->lib->forum['id'] . ');"';
		$antispam = $this->lib->code->showantispam();

		//加载ajax
		$mxajax_register_functions = array('dopreview_post', 'smiles_page', 'set_hidden_condition'); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_post.js');

		$referer = SCRIPTPATH;
		//加载编辑器js
		load_editor_js($extrabuttons);
		include $forums->func->load_template('add_post');
		exit;
	}

	function process()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$this->check_permission($this->thread);
		$this->credit->check_credit('newreply', $bbuserinfo['usergroupid'], $_INPUT['f']);
		if ($_INPUT['qreply'] && $_INPUT['quotepost'])
		{
			$_POST['post'] = $this->lib->check_multi_quote(1);
		}

		$this->post = $this->lib->compile_post();
		$hidepostinfo = $this->hidefunc->check_hide_condition();
		if (!$hidepostinfo)
		{
			$this->post['hidepost'] = '';
		}
		else if (is_string($hidepostinfo) && strlen($hidepostinfo) > 0)
		{
			$this->lib->obj['errors'] = $hidepostinfo;
		}
		else
		{
			$hidepostinfo = serialize($hidepostinfo);
			$this->post['hidepost'] = $hidepostinfo;
		}
		if ($bboptions['useantispam'])
		{
			$antispam = $this->lib->validate_antispam();
			if (!$antispam)
			{
				$this->lib->obj['errors'] = $forums->lang['badimagehash'];
			}
		}
		if (($this->lib->obj['errors'] != "") OR ($this->lib->obj['preview'] != ""))
		{
			return $this->showform();
		}
		$this->post['threadid'] = $this->thread['tid'];
		$this->lastpost = $this->thread['lastpost'];
		$movepost = false;
		if (isset($_INPUT['modoptions']))
		{
			switch ($_INPUT['modoptions'])
			{
				case 'gstick':
					$this->thread['sticky'] = 99;
					$this->thread['stickforumid'] = -1;
					$this->lib->moderate_log($forums->lang['gstickthread'] . ' - ', $_INPUT['title']);
					break;
				case 'stick':
					$this->thread['sticky'] = 1;
					$this->thread['stickforumid'] = $_INPUT['f'];
					$this->lib->moderate_log($forums->lang['stickthread'] . ' - ', $_INPUT['title']);
					break;
				case 'close':
					if ($bbuserinfo['supermod'] OR $this->lib->moderator['canopenclose'])
					{
						$this->thread['open'] = 0;
						$this->lib->moderate_log($forums->lang['closethread'] . ' - ', $_INPUT['title']);
					}
					break;
				case 'gstickclose':
					if ($bbuserinfo['supermod'])
					{
						$this->thread['sticky'] = 99;
						$this->thread['stickforumid'] = -1;
						$this->thread['open'] = 0;
						$this->lib->moderate_log($forums->lang['gstickclose'] . ' - ', $_INPUT['title']);
					}
					break;
				case 'stickclose':
					if ($bbuserinfo['supermod'] OR ($this->lib->moderator['canstickthread'] AND $this->lib->moderator['canopenclose']))
					{
						$this->thread['sticky'] = 1;
						$this->thread['stickforumid'] = $_INPUT['f'];
						$this->thread['open'] = 0;
						$this->lib->moderate_log($forums->lang['stickclose'] . ' - ', $_INPUT['title']);
					}
					break;
				case 'move':
					if ($bbuserinfo['supermod'] OR $this->lib->moderator['canremoveposts'])
					{
						$movepost = true;
					}
					break;
			}
		}
		$this->post['posthash'] = $this->posthash;
		$posttable = $this->thread['posttable']?$this->thread['posttable']:'post';
		$DB->insert(TABLE_PREFIX . $posttable, $this->post);
		$this->post['pid'] = $DB->insert_id();
		$this->lib->stats_recount($this->thread['tid'], 'reply');
		$post = $DB->query_first("SELECT COUNT(*) as posts FROM " . TABLE_PREFIX . "$posttable WHERE threadid='" . $this->thread['tid'] . "' AND moderate != 1");
		$postcount = intval($post['posts'] - 1);
		$modpost = $DB->query_first("SELECT COUNT(*) as posts FROM " . TABLE_PREFIX . "$posttable WHERE threadid='" . $this->thread['tid'] . "' AND moderate = 1");
		$modpostcount = intval($modpost['posts']);
		$poster_name = $bbuserinfo['id'] ? $bbuserinfo['name'] : $_INPUT['username'];
		$update_array = array(
			'post' => $postcount,
			'modposts' => $modpostcount
		);
		if ($this->lib->obj['moderate'] != 1 && $this->lib->obj['moderate'] != 3)
		{
			$update_array['lastposterid'] = $bbuserinfo['id'];
			$update_array['lastposter'] = $poster_name;
			$update_array['lastpost'] = TIMENOW;
			$update_array['sticky'] = $this->thread['sticky'];
			$update_array['stickforumid'] = $this->thread['stickforumid'];
			$update_array['open'] = $this->thread['open'];
			$update_array['lastpostid'] = $this->post['pid'];
		}
		if ($bbuserinfo['cananonymous'] && $_INPUT['anonymous'])
		{
			$update_array['lastposterid'] = 0;
			$update_array['lastposter'] = 'anonymous*';
		}
		$DB->update(TABLE_PREFIX . 'thread', $update_array, 'tid = ' . $this->thread['tid']);

		$this->lib->posts_recount();
		$this->lib->attachment_complete(array($this->posthash), $this->thread['tid'], $this->post['pid'], $posttable);
		if ($this->lib->obj['moderate'] == 1 || $this->lib->obj['moderate'] == 3)
		{
			$page = floor(($this->thread['post'] + 1) / $this->maxposts);
			$page = $page * $this->maxposts;
			$forums->lang['haspost'] = sprintf($forums->lang['haspost'], $forums->lang['post']);
			$forums->func->redirect_screen($forums->lang['haspost'], "forumdisplay.php{$forums->sessionurl}&f=" . $this->lib->forum['id']);
		}
		$hideposts = $DB->query("SELECT pid, userid, hidepost FROM " . TABLE_PREFIX . "$posttable WHERE threadid='" . $this->thread['tid'] . "' AND hidepost!=''");
		if ($DB->num_rows($hideposts))
		{
			while ($hidepost = $DB->fetch_array($hideposts))
			{
				$hideinfo = unserialize($hidepost['hidepost']);
				if ($hideinfo['type'] == '111' && $hidepost['userid'] != $bbuserinfo['id'])
				{
					if (is_array($hideinfo['buyers']) && in_array($bbuserinfo['name'], $hideinfo['buyers']))
					{
						continue;
					}
					$hideinfo['buyers'][] = $bbuserinfo['name'];
					$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "$posttable SET hidepost='" . addslashes(serialize($hideinfo)) . "' WHERE pid='" . $hidepost['pid'] . "'");
				}
			}
		}
		$this->credit->update_credit('newreply', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $_INPUT['f']);
		$this->credit->update_credit('replythread', $this->thread['postuserid'], $this->thread['usergroupid'], $_INPUT['f']);
		if ($movepost)
		{
			$forums->func->standard_redirect("moderate.php{$forums->sessionurl}do=move&amp;f=" . $this->lib->forum['id'] . "&amp;t=" . $this->thread['tid'] . "");
		}
		else
		{
			$page = floor(($this->thread['post'] + 1) / $this->maxposts) * $this->maxposts;
			if ($_INPUT['redirect'])
			{
				$forums->func->standard_redirect("forumdisplay.php{$forums->sessionurl}f=" . $this->lib->forum['id'] . "");
			}
			else
			{
				$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t=" . $this->thread['tid'] . "&amp;p=" . $this->post['pid'] . "&amp;pp=" . $page . "#pid" . $this->post['pid']);
			}
		}
	}

	function check_permission($thread = array())
	{
		global $forums, $DB, $bbuserinfo;
		if ($thread['pollstate'] == 2 AND !$bbuserinfo['supermod'])
		{
			$forums->func->standard_error("cannotreply");
		}
		$usercanreplay = $forums->func->fetch_permissions($this->lib->forum['canreply'], 'canreply');
		if ($thread['postuserid'] == $bbuserinfo['id'])
		{
			if (!($bbuserinfo['canreplyown'] && $usercanreplay))
			{
				$forums->func->standard_error("cannotreply");
			}
		}
		else if (!($bbuserinfo['canreplyothers'] && $usercanreplay))
		{
			$forums->func->standard_error("cannotreply");
		}

		if ($usercanreplay == false)
		{
			$forums->func->standard_error("cannotreply");
		}
		if (!$thread['open'])
		{
			if (!$bbuserinfo['canpostclosed'])
			{
				$forums->func->standard_error("threadclosed");
			}
		}
	}
}

$output = new newreply();
$output->show();

?>