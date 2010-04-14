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
define('THIS_SCRIPT', 'newthread');
require_once('./global.php');

class newthread
{
	var $posthash = '';
	var $post = array();
	var $thread = array();

	function show()
	{
		global $forums, $_INPUT;
		$forums->func->load_lang('post');
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();
		$this->posthash = $_INPUT['posthash'] ? $_INPUT['posthash'] : md5(microtime());
		require_once(ROOT_PATH . 'includes/functions_credit.php');
		$this->credit = new functions_credit();
		require_once(ROOT_PATH . 'includes/functions_post.php');
		$this->lib = new functions_post();
		$this->lib->dopost($this);
		$_INPUT['f'] = isset($_INPUT['f']) ? intval($_INPUT['f']) : 0;
	}

	function showform()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$this->check_permission();
		$forums->func->check_cache('usergroup');
		$usergrp = $forums->cache['usergroup'];
		foreach ($usergrp AS $k => $v)
		{
			$v['grouptitle'] = $forums->lang[$v['grouptitle']];
			$usergrp[$k] = $v;
		}
		$forums->func->check_cache('creditlist');
		$hidecredit = array();
		if ($forums->cache['creditlist'])
		{
			foreach ($forums->cache['creditlist'] as $k => $v)
			{
				$hidecredit[$v['tag']] = $v['name'];
			}
		}
		$hidetypes = $this->hidefunc->generate_hidetype_list(1);
		$title = isset($_INPUT['title']) ? trim($_INPUT['title']) : '';
		$description = (isset($_INPUT['description']) && trim($_INPUT['description'])) ? $forums->func->fetch_trimmed_title(trim($_INPUT['description']), 80) : '';
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
		if ($_POST['post'])
		{
			$content = utf8_htmlspecialchars($_POST['post']);
		}
		$show['title'] = true;
		if ($this->lib->obj['errors'])
		{
			$show['errors'] = true;
			$errors = $this->lib->obj['errors'];
		}
		if ($this->lib->moderator['caneditthreads'] OR $bbuserinfo['supermod'])
		{
			$show['colorpicker'] = true;
		}
		if ($this->lib->obj['preview'])
		{
			$show['preview'] = true;
			require_once(ROOT_PATH . 'includes/class_textparse.php');
			$preview = textparse::convert_text($this->post['pagetext']);
		}
		$form_start = $this->lib->fetch_post_form(array(
			1 => array('do', 'update'),
			2 => array('posthash', $this->posthash),
		));
		$forums->lang['threaddesc'] = sprintf($forums->lang['threaddesc'], $this->lib->forum['name']);
		$postdesc = $forums->lang['newthread'];
		$modoptions = $this->lib->modoptions();
		if ($this->lib->canupload)
		{
			$show['upload'] = true;
			$upload = $this->lib->fetch_upload_form($this->posthash, 'new');
		}
		$upload['maxnum'] = intval($bbuserinfo['attachnum']);
		if ($this->lib->forum['threadprefix'])
		{
			$threadprefix = explode('||', $this->lib->forum['threadprefix']);
		}
		if ($this->lib->forum['specialtopic'])
		{
			$forums->func->check_cache('st');
			$special_selected[0] = ' selected="selected"';
			$specialtopic = explode(',', $this->lib->forum['specialtopic']);
			$forumsspecial = $forums->cache['st'];
		}
		$credit_list = $this->credit->show_credit('newthread', $bbuserinfo['usergroupid'], $_INPUT['f']);
		$smiles = $this->lib->construct_smiles();
		$smile_count = $smiles['count'];
		$all_smiles = $smiles['all'];
		$smiles = $smiles['smiles'];
		$icons = $this->lib->construct_icons();
		$checked = $this->lib->construct_checkboxes();
		$pagetitle = $forums->lang['newthread'] . " - " . $bboptions['bbtitle'];
		$nav = array_merge($this->lib->nav, array($forums->lang['newthread']));
		$extrabuttons = $this->lib->code->construct_extrabuttons();
		$previewfunc = ' onclick="preview_post(' . $this->lib->forum['id'] . ');"';
		$antispam = $this->lib->code->showantispam();
		$forum = $this->lib->forum;

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

		$this->check_permission();
		$this->credit->check_credit('newthread', $bbuserinfo['usergroupid'], $_INPUT['f']);
		$this->post = $this->lib->compile_post();
		if ($this->lib->forum['forcespecial'] && isset($_INPUT['specialtopic']) && $_INPUT['specialtopic'] == '')
		{
			$this->lib->obj['errors'] = $forums->lang['forcespecial'];
		}
		$_INPUT['title'] = trim($_INPUT['title']);
		if ((utf8_strlen($_INPUT['title']) < 2) || (!$_INPUT['title']))
		{
			$this->lib->obj['errors'] = $forums->lang['musttitle'];
		}
		if (strlen($_INPUT['title']) > 250)
		{
			$this->lib->obj['errors'] = $forums->lang['titletoolong'];
		}
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
		$_INPUT['title'] = $this->lib->parser->censoredwords($_INPUT['title']);
		$_INPUT['description'] = $forums->func->fetch_trimmed_title(trim($_INPUT['description']), 80);
		$_INPUT['description'] = $this->lib->parser->censoredwords($_INPUT['description']);
		$sticky = 0;
		$stickforumid = 0;
		$open = 1;
		if (isset($_INPUT['modoptions']))
		{
			switch ($_INPUT['modoptions'])
			{
				case 'gstick':
					$sticky = 99;
					$stickforumid = 0;
					$this->lib->moderate_log($forums->lang['gstickthread'] . ' - ', $_INPUT['title']);
				break;

				case 'stick':
					$sticky = 1;
					$stickforumid = $_INPUT['f'];
					$this->lib->moderate_log($forums->lang['stickthread'] . ' - ', $_INPUT['title']);
				break;

				case 'close':
					if ($bbuserinfo['supermod'] OR $this->lib->moderator['canopenclose'])
					{
						$open = 0;
						$this->lib->moderate_log($forums->lang['closethread'] . ' - ', $_INPUT['title']);
					}
				break;

				case 'gstickclose':
					if ($bbuserinfo['supermod'])
					{
						$sticky = 99;
						$stickforumid = 0;
						$open = 0;
						$this->lib->moderate_log($forums->lang['gstickclose'] . ' - ', $_INPUT['title']);
					}
				break;

				case 'stickclose':
					if ($bbuserinfo['supermod'] OR ($this->lib->moderator['canstickthread'] AND $this->lib->moderator['canopenclose']))
					{
						$sticky = 1;
						$stickforumid = $_INPUT['f'];
						$open = 0;
						$this->lib->moderate_log($forums->lang['stickclose'] . ' - ', $_INPUT['title']);
					}
				break;
			}
		}
		$_INPUT['title'] = $this->lib->compile_title();
		if ($bbuserinfo['cananonymous'] && $_INPUT['anonymous'])
		{
			$useanonymous = array('postuserid' => 0, 'postusername' => 'anonymous*');
			$newtuserid = $bbuserinfo['id'];
			$bbuserinfo['id'] = 0;
			$_INPUT['username'] = "anonymous*";
		}
		else
		{
			$newtuserid = $bbuserinfo['id'];
		}

		$titletext = strip_tags($_INPUT['title']);
		$this->thread = array(
			'title' => $_INPUT['title'],
			'titletext' =>  implode(' ', duality_word($titletext)),
			'description' => $_INPUT['description'] ,
			'open' => $open,
			'post' => 0,
			'postuserid' => $bbuserinfo['id'],
			'postusername' => $bbuserinfo['id'] ? $bbuserinfo['name'] : $_INPUT['username'],
			'dateline' => TIMENOW,
			'lastposterid' => $bbuserinfo['id'],
			'lastposter' => $bbuserinfo['id'] ? $bbuserinfo['name'] : $_INPUT['username'],
			'lastpost' => TIMENOW,
			'iconid' => intval($_INPUT['iconid']),
			'pollstate' => 0,
			'lastvote' => 0,
			'views' => 0,
			'forumid' => $this->lib->forum['id'],
			'visible' => ($this->lib->obj['moderate'] == 1 || $this->lib->obj['moderate'] == 2) ? 0 : 1,
			'sticky' => $sticky,
			'stickforumid' => $stickforumid,
			'stopic' => intval($_INPUT['specialtopic']),
			'logtext' => '',
		);
		$DB->insert(TABLE_PREFIX . 'thread', $this->thread);
		$this->post['threadid'] = $DB->insert_id();
		$this->thread['tid'] = $this->post['threadid'];

		$this->post['posthash'] = $this->posthash;
		$this->post['newthread'] = 1;
		$this->post['moderate'] = 0;

		//帖子分表
		$splittable = $forums->func->getposttable();
		$posttable = $splittable['name'] ? $splittable['name'] : 'post';
		$DB->insert(TABLE_PREFIX . $posttable, $this->post);
		$this->post['pid'] = $DB->insert_id();
		$sql_array = array(
			'firstpostid' => $this->post['pid'],
			'lastpostid' => $this->post['pid'],
			'posttable'=> $posttable
		);
		if ($useanonymous)
		{
			$sql_array = array_merge($sql_array, $useanonymous);
		}
		$DB->update(TABLE_PREFIX . 'thread', $sql_array, 'tid = ' . intval($this->thread['tid']));

		$this->lib->stats_recount($this->post['threadid'], 'new');
		$no_attachment = $this->lib->attachment_complete(array($this->posthash), $this->thread['tid'], $this->post['pid'], $posttable);
		$this->lib->posts_recount();
		$this->credit->update_credit('newthread', $newtuserid, $bbuserinfo['usergroupid'], $_INPUT['f']);
		if ($this->lib->obj['moderate'] == 1 OR $this->lib->obj['moderate'] == 2)
		{
			$forums->lang['haspost'] = sprintf($forums->lang['haspost'], $forums->lang['thread']);
			$forums->func->redirect_screen($forums->lang['haspost'], "forumdisplay.php{$forums->sessionurl}&f=" . $this->lib->forum['id'] . "");
		}
		if ($_INPUT['redirect'])
		{
			$forums->func->standard_redirect("forumdisplay.php{$forums->sessionurl}f=" . $this->lib->forum['id'] . "");
		}
		else
		{
			$forums->func->standard_redirect("showthread.php{$forums->sessionurl}t={$this->thread['tid']}");
		}
	}

	function check_permission()
	{
		global $forums, $bbuserinfo;
		$usercanpostnew = $forums->func->fetch_permissions($this->lib->forum['canstart'], 'canstart');
		if (!($bbuserinfo['canpostnew'] && $usercanpostnew))
		{
			$forums->func->standard_error("cannotnewthread");
		}
	}
}

$output = new newthread();
$output->show();
?>