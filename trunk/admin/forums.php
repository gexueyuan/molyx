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

class forums
{
	var $forumfunc;
	var $cache;
	var $allforum;
	var $forum;

	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditforums'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$this->allforum = $forums->adminforum->forumcache;
		$this->forum = $this->allforum[$_INPUT['f']];
		switch ($_INPUT['do'])
		{
			case 'new':
				$this->do_form('new');
				break;
			case 'donew':
				$this->donew();
				break;
			case 'edit':
				$this->do_form('edit');
				break;
			case 'doedit':
				$this->doeditforum();
				break;
			case 'editpermissions':
				$this->editpermissions();
				break;
			case 'doeditpermissions':
				$this->doeditpermissions();
				break;
			case 'doreorder':
				$this->doreorder();
				break;
			case 'delete':
				$this->delete_form();
				break;
			case 'dodelete':
				$this->dodelete();
				break;
			case 'recount':
				$this->recount();
				break;
			case 'empty':
				$this->emptyforum();
				break;
			case 'doempty':
				$this->doemptyforum();
				break;
			default:
				$this->show_forums();
				break;
		}
	}

	function recount($f_override = "")
	{
		global $forums, $DB, $_INPUT;
		if ($f_override != "")
		{
			$_INPUT['f'] = $f_override;
		}
		require_once(ROOT_PATH . 'includes/functions_moderate.php');
		$modfunc = new modfunctions();
		$modfunc->forum_recount($_INPUT['f']);
		$forums->lang['recacheforum'] = sprintf($forums->lang['recacheforum'], $this->forum['name']);
		$forums->admin->save_log($forums->lang['recacheforum']);
		$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['forumstatsupdated']);
	}

	function emptyforum()
	{
		global $forums, $DB, $_INPUT;
		$form_array = array();
		if ($_INPUT['f'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query("SELECT id, name FROM " . TABLE_PREFIX . "forum WHERE id=" . $_INPUT['f'] . "");
		if (!$DB->num_rows())
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$forum = $DB->fetch_array();
		$pagetitle = $forums->lang['emptyforum'] . " - '{$this->forum['name']}'";
		$detail = $forums->lang['emptyforumdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'doempty'), 2 => array('f', $_INPUT['f']), 3 => array('name' , $this->forum['name'])));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['emptyforum'] . " - '{$this->forum['name']}");
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectemptyforum'] . ": </strong>" , $this->forum['name']));
		$forums->admin->print_form_end($forums->lang['emptyforum']);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function doemptyforum()
	{
		global $forums, $DB, $_INPUT;
		require_once(ROOT_PATH . 'includes/functions_moderate.php');
		$modfunc = new modfunctions();
		if ($_INPUT['f'] == '')
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (! $forum = $DB->query_first("SELECT id, name, post, thread FROM " . TABLE_PREFIX . "forum WHERE id=" . $_INPUT['f'] . ""))
		{
			$forums->admin->print_cp_error($forums->lang['noemptyforums']);
		}
		$DB->query("SELECT tid FROM " . TABLE_PREFIX . "thread WHERE forumid=" . $_INPUT['f'] . "");
		while ($t = $DB->fetch_array())
		{
			$tids[] = $t['tid'];
		}
		$modfunc->thread_delete($tids);
		$modfunc->forum_recount($_INPUT['f']);
		$forums->lang['emptyedinfo'] = sprintf($forums->lang['emptyedinfo'], $forum['name']);
		$forums->admin->save_log($forums->lang['emptyedinfo']);
		$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['forumemptyed']);
	}

	function delete_form()
	{
		global $forums, $DB, $_INPUT;
		$form_array = array();
		$_INPUT['f'] = intval($_INPUT['f']);
		if (! $_INPUT['f'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$result = $DB->query("SELECT id, name FROM " . TABLE_PREFIX . "forum");
		if ($DB->num_rows($result) < 2)
		{
			$forums->admin->print_cp_error($forums->lang['onlyoneforum']);
		}
		while ($r = $DB->fetch_array($result))
		{
			if ($r['id'] == $_INPUT['f'])
			{
				$name = $r['name'];
				continue;
			}
		}
		foreach($forums->adminforum->forumcache AS $key => $value)
		{
			$forumlist[] = array($value[id], depth_mark($value['depth'], '--') . $value[name]);
		}
		
		$post = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "thread WHERE forumid=" . $_INPUT['f'] . "");
		$children = $DB->query_first("SELECT count(*) as count FROM " . TABLE_PREFIX . "forum WHERE parentid=" . $_INPUT['f'] . "");
		$pagetitle = $forums->lang['deleteforum'] . " - '$name'";
		$detail = $forums->lang['deleteforumdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'dodelete'), 2 => array('f', $_INPUT['f'])));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['deleteforum']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['deleteforum'] . ":</strong>", $name));
		if ($post['count'])
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['movepostto'] . ":</strong>", $forums->admin->print_input_select_row("move_id", $forumlist)));
		}
		if ($children['count'])
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['moveforumto'] . ":</strong>", $forums->admin->print_input_select_row("new_parentid", $forumlist)));
		}
		$forums->admin->print_form_end($forums->lang['deleteforum']);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function dodelete()
	{
		global $forums, $DB, $_INPUT;
		$id = intval($_INPUT['f']);
		$_INPUT['move_id'] = intval($_INPUT['move_id']);
		$_INPUT['new_parentid'] = intval($_INPUT['new_parentid']);
		if (!$id)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (!$_INPUT['new_parentid'])
		{
			$_INPUT['new_parentid'] = -1;
		}
		else
		{
			if ($_INPUT['new_parentid'] == $_INPUT['f'])
			{
				$forums->main_msg = $forums->lang['cannotmoveforum'];
				$this->delete_form();
			}
		}

		require_once(ROOT_PATH . 'includes/functions_moderate.php');
		$modfunc = new modfunctions();
		if ($_INPUT['move_id'])
		{
			if ($_INPUT['move_id'] == $id)
			{
				$forums->main_msg = $forums->lang['cannotmovepost'];
				$this->delete_form();
			}
			$sql_array = array('forumid' => $_INPUT['move_id']);
			$DB->update(TABLE_PREFIX . 'thread', $sql_array, 'forumid=' . $id);
			$DB->update(TABLE_PREFIX . 'poll', $sql_array, 'forumid=' . $id);
			$modfunc->forum_recount($_INPUT['move_id']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "forum WHERE id=" . $id);
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "moderator WHERE forumid=" . $id);
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "subscribeforum WHERE forumid=" . $id);
		$forums->func->rmcache(array('subforum_' . $id, 'forum_' . $id));
		if (!$_INPUT['new_parentid'])
		{
			$_INPUT['new_parentid'] = -1;
		}
		$DB->update(TABLE_PREFIX . 'forum', array('parentid' => $_INPUT['new_parentid']), "parentid = $id");
		$forums->func->recache('forum');
		$forums->func->recache('moderator');
		$forums->adminforum->build_forum_child_lists($forums->adminforum->forumcache[$_INPUT['f']]['parentid']);
		$forums->admin->save_log($forums->lang['manageforum'] . " '{$this->forum['name']}'");
		$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['forumdeleted']);
	}

	function donew()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['name'] = trim($_INPUT['name']);
		if ($_INPUT['name'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['requireforumtitle']);
		}
		$parentlist = $forums->adminforum->fetch_forum_parentlist($_INPUT['parentid']);
		$perms = $forums->admin->compile_forum_permission();
		$perm_array = serialize(array(
			'canstart' => $perms['start'],
			'canreply' => $perms['reply'],
			'canread' => $perms['read'],
			'canupload' => $perms['upload'],
			'canshow' => $perms['show']
		));
		if ($_POST['threadprefix'] != '')
		{
			$threadprefix = str_replace("\r\n", "||", convert_andstr($_POST['threadprefix']));
		}
		if (is_array($_INPUT['st']))
		{
			foreach ($_INPUT['st'] as $key => $val)
			{
				if ($val)
				{
					$specialtopic[$_INPUT['storder'][$key]] = $val;
				}
				else
				{
					unset($_INPUT['st'][$key]);
				}
			}
			ksort($specialtopic);
			$specialtopic = implode(',', $specialtopic);
		}
		else
		{
			$specialtopic = '';
		}
		$forumrule = trim($_INPUT['forumrule']) ? 1 : 0;
		$DB->insert(TABLE_PREFIX . 'forum', array(
			'thread' => 0,
			'post' => 0,
			'style' => $_INPUT['style'] ? intval($_INPUT['style']) : 0,
			'name' => convert_andstr($_POST['name']),
			'forumicon' => convert_andstr($_POST['forumicon']),
			'description' => str_replace("\n", '<br />', convert_andstr($_POST['description'])),
			'allowbbcode' => $_INPUT['allowbbcode'],
			'allowhtml' => $_INPUT['allowhtml'],
			'status' => $_INPUT['status'],
			'password' => $_INPUT['password'],
//			'lastpostid' => 0,
			'sortby' => $_INPUT['sortby'],
			'sortorder' => $_INPUT['sortorder'],
			'prune' => $_INPUT['prune'],
			'moderatepost' => $_INPUT['moderatepost'],
			'allowpoll' => $_INPUT['allowpoll'],
			'allowpollup' => $_INPUT['allowpollup'],
			'countposts' => $_INPUT['countposts'],
			'parentid' => $_INPUT['parentid'],
			'allowposting' => $_INPUT['allowposting'],
			'permissions' => $perm_array,
			'forumrule' => $forumrule,
			'threadprefix' => $threadprefix,
			'forcespecial' => $_INPUT['forcespecial'],
			'specialtopic' => $specialtopic,
			'showthreadlist' => $_INPUT['showthreadlist'],
			'url' => $_INPUT['url'],
			'customerror' => str_replace("\n", '<br />', convert_andstr($_POST['customerror'])),
		));
		$forumid = $DB->insert_id();
		if ($forumrule)
		{
			require_once(ROOT_PATH . 'includes/functions_codeparse.php');
			$lib = new functions_codeparse();
			$content = $lib->convert(array(
				'text' => convert_andstr($_POST['forumrule']),
				'allowsmilies' => 1,
				'allowcode' => 1,
			));

			require_once(ROOT_PATH . 'includes/class_textparse.php');
			$content = textparse::parse_html($content, true);
			$fp = file_write(ROOT_PATH . "cache/cache/rule_{$forumid}.txt", $content, 'wb');
			if ($fp)
			{
				$DB->insert(TABLE_PREFIX . 'forum_attr', array(
					'forumid' => $forumid,
					'forumrule' => $content,
				));
			}
			else
			{
				$DB->delete(TABLE_PREFIX . 'forum', 'id = ' . $forumid);
				$forums->admin->print_cp_error($forums->lang['cachefoldererror']);
			}
		}
		if ($_INPUT['parentid'] != -1)
		{
			$DB->query("SELECT id FROM " . TABLE_PREFIX . "forum WHERE parentid = " . $_INPUT['parentid'] . " ");
			while ($cid = $DB->fetch_array())
			{
				$childids[] = $cid['id'];
			}
		}
		if ($childids && count($childids) > 0)
		{
			$childlist = $_INPUT['parentid'] . "," . implode(",", $childids);
		}
		else
		{
			$childlist = $forumid;
		}
		$DB->update(TABLE_PREFIX . 'forum', array('parentlist' => "$forumid,$parentlist", 'childlist' => $forumid), "id = $forumid");
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "forum SET childlist = concat(childlist,'," . $forumid . "') WHERE id IN(" . $DB->escape_string($parentlist) . ")");
		$this->update_specialtopic($_INPUT['st'], $forumid);
		$forums->func->recache('forum');
		$forums->lang['forumcreated'] = sprintf($forums->lang['forumcreated'], $_INPUT['name']);
		$forums->admin->save_log($forums->lang['forumcreated']);
		$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['forumcreated']);
	}

	function do_form($type = 'edit')
	{
		global $forums, $DB, $_INPUT;
		$detail = $forums->lang['forumeditdesc'];
		if ($type == 'edit')
		{
			if ($_INPUT['f'] == "")
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			if ($this->forum['id'] == "")
			{
				$forums->admin->print_cp_error($forums->lang['noforumdata'] . " (" . $forums->lang['forumids'] . ": {$_INPUT['f']})");
			}
			$parentid = $this->forum['parentid'];
			$pagetitle = $forums->lang['editforum'] . ": {$this->forum['name']}";
			$button = $forums->lang['editforum'];
			$do = "doedit";
			$basic_title = "<div style='float:right'>" . $forums->admin->print_button($forums->lang['recount'], "forums.php?{$forums->sessionurl}do=recount&amp;f={$_INPUT['f']}") . "&nbsp;&nbsp;</div>{$this->forum['name']} " . $forums->lang['basicsetting'];
		}
		else
		{
			$f_name = '';
			if ($_INPUT['name'] != '')
			{
				$f_name = rawurldecode($_INPUT['name']);
			}
			if ($_INPUT['c'] == 1)
			{
				$subcanpost = 0;
			}
			else
			{
				$subcanpost = 1;
			}
			if (!$_INPUT['p'])
			{
				$parentid = -1;
			}
			else
			{
				$parentid = $_INPUT['p'];
				$this->forum['allowposting'] = 1;
				$this->forum['allowbbcode'] = 1;
				$this->forum['allowpoll'] = 1;
				$this->forum['countposts'] = 1;
			}
			$forum = array('allowposting' => $subcanpost,
				'name' => $f_name,
				'parentid' => $parentid,
				'allowbbcode' => 1,
				'allowpoll' => 1,
				'prune' => 100,
				'sortby' => 'lastpost',
				'sortorder' => 'desc',
				'countposts' => 1,
				);
			$pagetitle = $forums->lang['addforum'];
			$button = $forums->lang['addforum'];
			$do = "donew";
			$basic_title = $forums->lang['basicsetting'];
		}
		$threadprune = $this->forum['prune'] ? $this->forum['prune'] : 100;
		$forumlist[] = array('-1', $forums->lang['noparentforum']);
		$allforum = $forums->adminforum->forumcache;
		foreach($allforum AS $key => $value)
		{
			$forumlist[] = array($value['id'], depth_mark($value['depth'], '--') . $value['name']);
		}
		$threadprefix = str_replace("||", "\r\n", $this->forum['threadprefix']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', $do), 2 => array('f', $_INPUT['f']), 3 => array('name' , $this->forum['name'])));
		$forums->admin->columns[] = array('' , '40%');
		$forums->admin->columns[] = array('', '60%');
		$forums->admin->print_table_start($basic_title);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumtitle'] . "</strong>", $forums->admin->print_input_row("name", utf8_htmlspecialchars($this->forum['name']))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumicon'] . "</strong><br />" . $forums->lang['forumicondesc'], $forums->admin->print_input_row("forumicon", $this->forum['forumicon'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumdesc'] . "</strong><br />" . $forums->lang['forumdescdesc'], $forums->admin->print_textarea_row("description", br2nl($this->forum['description']))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forum_redirecturl'] . "</strong><br />" . $forums->lang['forum_redirecturl_desc'], $forums->admin->print_textarea_row("url", br2nl($this->forum['url']))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumparent'] . "</strong><br />", $forums->admin->print_input_select_row('parentid', $forumlist, $parentid)));
		$styleid = array(0 => array('', $forums->lang['useddefaultstyle']));
		$forums->admin->cache_styles();
		foreach($forums->admin->stylecache AS $style)
		{
			$styleid[] = array($style[styleid], depth_mark($style['depth'], '--') . $style[title]);
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumstyle'] . "</strong><br />" . $forums->lang['forumstyledesc'], $forums->admin->print_input_select_row('style', $styleid)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumstatus'] . "</strong>", $forums->admin->print_input_select_row("status", array(0 => array(0, $forums->lang['readonly']),
						1 => array(1, $forums->lang['open']),
						2 => array(2, $forums->lang['notdisplay']),
						), $this->forum['status'] ? $this->forum['status'] : 1)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumallowposting'] . "</strong><br />" . $forums->lang['forumapdesc'], $forums->admin->print_yes_no_row("allowposting", $this->forum['allowposting'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumcolumns'] . "</strong><br />" . $forums->lang['forumcolumnsdesc'], $forums->admin->print_input_row("forumcolumns", $this->forum['forumcolumns'])));
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array('' , '40%');
		$forums->admin->columns[] = array('', '60%');
		$forums->admin->print_table_start($forums->lang['permissionsetting']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['showthreadlist'] . "</strong><br />" . $forums->lang['showthreadlistdesc'], $forums->admin->print_yes_no_row("showthreadlist", $this->forum['showthreadlist'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['customerror'] . "</strong><br />" . $forums->lang['customerrordesc'], $forums->admin->print_textarea_row("customerror", br2nl($this->forum['customerror']))));
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array('' , '40%');
		$forums->admin->columns[] = array('', '60%');
		$forums->admin->print_table_start($forums->lang['postsetting']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['allowhtml'] . "</strong>", $forums->admin->print_yes_no_row("allowhtml", $this->forum['allowhtml'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['allowbbcode'] . "</strong>", $forums->admin->print_yes_no_row("allowbbcode", $this->forum['allowbbcode'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['allowpoll'] . "</strong>", $forums->admin->print_yes_no_row("allowpoll", $this->forum['allowpoll'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['allowpollup'] . "</strong>" , $forums->admin->print_yes_no_row("allowpollup", $this->forum['allowpollup'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['countposts'] . "</strong>", $forums->admin->print_yes_no_row("countposts", $this->forum['countposts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['moderatepost'] . "</strong><br />" . $forums->lang['moderatepostdesc'], $forums->admin->print_input_select_row("moderatepost", array(0 => array(0, $forums->lang['no']),
						1 => array(1, $forums->lang['moderateallpost']),
						2 => array(2, $forums->lang['moderatethread']),
						3 => array(3, $forums->lang['moderatereply']),
						), $this->forum['moderatepost'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumpassword'] . "</strong><br />" . $forums->lang['forumpassworddesc'], $forums->admin->print_input_row("password", $this->forum['password'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumprunelist'] . "</strong>", $forums->admin->print_input_select_row("prune", array(0 => array(1, '1 ' . $forums->lang['forumprunedays']),
						1 => array(5, '5 ' . $forums->lang['forumprunedays']),
						2 => array(7, '7 ' . $forums->lang['forumprunedays']),
						3 => array(10, '10 ' . $forums->lang['forumprunedays']),
						4 => array(15, '15 ' . $forums->lang['forumprunedays']),
						5 => array(20, '20 ' . $forums->lang['forumprunedays']),
						6 => array(25, '25 ' . $forums->lang['forumprunedays']),
						7 => array(30, '30 ' . $forums->lang['forumprunedays']),
						8 => array(60, '60 ' . $forums->lang['forumprunedays']),
						9 => array(90, '90 ' . $forums->lang['forumprunedays']),
						10 => array(100, $forums->lang['showallthread']),
						), $threadprune)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumsortby'] . "</strong>", $forums->admin->print_input_select_row("sortby", array(0 => array('lastpost', $forums->lang['sortbylastpost']),
						1 => array('title', $forums->lang['sortbytitle']),
						2 => array('postusername', $forums->lang['sortbyusername']),
						3 => array('post', $forums->lang['sortbypost']),
						4 => array('views', $forums->lang['sortbyviews']),
						5 => array('dateline', $forums->lang['sortbydateline']),
						6 => array('lastposter', $forums->lang['sortbylastposter']),
						), $this->forum['sortby'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumsortorder'] . "</strong>" ,
				$forums->admin->print_input_select_row("sortorder", array(0 => array('desc', $forums->lang['descending']),
						1 => array('asc', $forums->lang['ascending']),
						), $this->forum['sortorder'])));
		if ($this->forum['forumrule'])
		{
			$forumrule = $DB->query_first('SELECT *
											FROM ' . TABLE_PREFIX . 'forum_attr
											WHERE forumid = ' . $this->forum['id'] . '
			');
			$forumrule = $forumrule['forumrule'];
			require ROOT_PATH . "includes/functions_codeparse.php";
			$lib = new functions_codeparse();
			$forumrule = preg_replace("#<br.*>#siU", "\n", $lib->unconvert($forumrule, 1, 1, 1));
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forumrule'] . "</strong><br />" . $forums->lang['forumruledesc'] , $forums->admin->print_textarea_row("forumrule", $forumrule)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['threadprefix'] . "</strong><br />" . $forums->lang['threadprefixdesc'] , $forums->admin->print_textarea_row("threadprefix", $threadprefix)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['forcespecial'] . "</strong>" ,
				$forums->admin->print_yes_no_row("forcespecial", $this->forum['forcespecial'])
				));
		$forums->func->check_cache('st');
		if (is_array($forums->cache['st']) && $forums->cache['st'])
		{
			$specialtopic = $forum_specialtopic = array();
			$i = 0;
			if ($this->forum['specialtopic'])
			{
				$forum_specialtopic = explode(',', $this->forum['specialtopic']);
				foreach ($forum_specialtopic as $id)
				{
					$i++;
					$specialtopic[] = "<input name='st[$id]' value='{$forums->cache['st'][$id]['id']}' type='checkbox' checked='checked'> {$forums->cache['st'][$id]['name']}  " . $forums->admin->print_input_row("storder[$id]", $i, 'text', '', 1);
				}
			}
			foreach ($forums->cache['st'] as $v)
			{
				if (!in_array($v['id'], $forum_specialtopic))
				{
					$i++;
					$specialtopic[] = "<input name='st[{$v['id']}]' value='{$v['id']}' type='checkbox'> {$v['name']}  " . $forums->admin->print_input_row("storder[{$v['id']}]", $i, 'text', '', 1);
				}
			}
			$specialtopic = implode("<br />", $specialtopic);
		}
		else
		{
			$specialtopic = $forums->lang['nospecialtopic'];
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['specialtopic'] . "</strong><br />" . $forums->lang['specialtopicdesc'], $specialtopic));
		$forums->admin->print_table_footer();

		if ($type == 'edit')
		{
			$forums->admin->print_form_end_standalone($forums->lang['editforum']);
		}
		else
		{
			$forums->admin->columns[] = array($forums->lang['forumtitle'], "40%");
			$forums->admin->columns[] = array($forums->lang['viewforum'], "12%");
			$forums->admin->columns[] = array($forums->lang['viewthread'], "12%");
			$forums->admin->columns[] = array($forums->lang['replythread'], "12%");
			$forums->admin->columns[] = array($forums->lang['postnewthread'], "12%");
			$forums->admin->columns[] = array($forums->lang['uploadfile'], "12%");
			$forums->admin->print_table_start($forums->lang['forumpermission']);
			$forums->admin->build_group_perms(array ('show' => $this->forum['canshow'], 'read' => $this->forum['canread'], 'start' => $this->forum['canstart'], 'reply' => $this->forum['canreply'], 'upload' => $this->forum['canupload']));
			$forums->admin->print_form_submit($forums->lang['createforum']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
		}
		$forums->admin->nav[] = array('forums.php', $forums->lang['manageforum']);
		$forums->admin->nav[] = array('', $forums->lang['addeditforum']);
		$forums->admin->print_cp_footer();
	}

	function doeditforum()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['name'] = trim($_INPUT['name']);
		if ($_INPUT['name'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['requireforumtitle']);
		}
		$forumid = intval($_INPUT['f']);
		$parentid = intval($_INPUT['parentid']);
		if ($parentid == $forumid)
		{
			$forums->admin->print_cp_error($forums->lang['parentnotsame']);
		}
		$foruminfo = $DB->query_first("SELECT id, name, parentlist FROM " . TABLE_PREFIX . "forum WHERE id=$parentid");
		$parents = explode(',', $foruminfo['parentlist']);
		foreach($parents as $val)
		{
			if ($val == $forumid)
			{
				$forums->admin->print_cp_error($forums->lang['parentnotsub']);
			}
		}
		if ($_POST['threadprefix'] != '')
		{
			$threadprefix = str_replace("\r\n", "||", convert_andstr($_POST['threadprefix']));
		}
		if ($_POST['specialtopic'] != '')
		{
			$stopic = explode("\r\n", convert_andstr($_POST['specialtopic']));
			$i = 1;
			foreach ($stopic AS $id => $key)
			{
				if ($key == '') continue;
				$specialtopic[$i] = $key;
				$i++;
			}
			$specialtopic = serialize($specialtopic);
		}
		$oldforuminfo = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "forum WHERE id = $forumid");
		$this->forum['parentlist'] = $forumid . ',' . $forums->adminforum->fetch_forum_parentlist($parentid);
		$forumrule = trim($_INPUT['forumrule']) ? 1 : 0;
		if ($forumrule)
		{
			require ROOT_PATH . "includes/functions_codeparse.php";
			$lib = new functions_codeparse();
			$content = $lib->convert(array(
				'text' => convert_andstr($_POST['forumrule']),
				'allowsmilies' => 1,
				'allowcode' => 1,
			));
			require_once(ROOT_PATH . 'includes/class_textparse.php');
			$content = textparse::parse_html($content, true);
			$fp = file_write(ROOT_PATH . "cache/cache/rule_{$_INPUT['f']}.txt", $content, 'wb');

			if ($fp)
			{
				$DB->replace(TABLE_PREFIX . 'forum_attr', array(
					'forumid' => $forumid,
					'forumrule' => $content,
				));
			}
			else
			{
				$forums->admin->print_cp_error($forums->lang['cachefoldererror']);
			}
		}
		if (is_array($_INPUT['st']))
		{
			foreach ($_INPUT['st'] as $key => $val)
			{
				if ($val)
				{
					$specialtopic[$_INPUT['storder'][$key]] = $val;
				}
				else
				{
					unset($_INPUT['st'][$key]);
				}
			}
			ksort($specialtopic);
			$specialtopic = implode(',', $specialtopic);
		}
		else
		{
			$specialtopic = '';
		}
		$DB->update(TABLE_PREFIX . 'forum', array(
				'name' => convert_andstr($_POST['name']),
				'forumicon' => convert_andstr($_INPUT['forumicon']),
				'description' => str_replace("\n", "<br />", convert_andstr($_POST['description'])),
				'style' => $_INPUT['style'] ? intval($_INPUT['style']) : 0,
				'forumcolumns' => intval($_INPUT['forumcolumns']),
				'allowbbcode' => $_INPUT['allowbbcode'],
				'allowhtml' => $_INPUT['allowhtml'],
				'status' => $_INPUT['status'],
				'password' => $_INPUT['password'],
				'sortby' => $_INPUT['sortby'],
				'sortorder' => $_INPUT['sortorder'],
				'prune' => $_INPUT['prune'],
				'moderatepost' => $_INPUT['moderatepost'],
				'allowpoll' => $_INPUT['allowpoll'],
				'allowpollup' => $_INPUT['allowpollup'],
				'countposts' => $_INPUT['countposts'],
				'parentid' => $_INPUT['parentid'],
				'allowposting' => $_INPUT['allowposting'],
				'showthreadlist' => $_INPUT['showthreadlist'],
				'parentlist' => $this->forum['parentlist'],
				'forumrule' => $forumrule,
				'threadprefix' => $threadprefix,
				'specialtopic' => $specialtopic,
				'forcespecial' => $_INPUT['forcespecial'],
				'url' => $_INPUT['url'],
				'customerror' => str_replace("\n", "<br />", convert_andstr($_POST['customerror'])),
			), "id={$_INPUT['f']}");
		$forums->lang['forumedited'] = sprintf($forums->lang['forumedited'], $_INPUT['name']);
		$forums->admin->save_log($forums->lang['forumedited']);
		$forums->adminforum->build_forum_parentlists($forumid);
		$forums->adminforum->build_forum_parentlists($parentid);
		$forums->adminforum->build_forum_child_lists($forumid);
		$forums->adminforum->build_forum_child_lists($oldforuminfo['parentid']);
		$this->update_specialtopic($_INPUT['st'], $_INPUT['f']);
		$forums->func->recache('forum');
		$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['forumedited']);
	}

	function update_specialtopic($stids = array(), $fid = 0)
	{
		global $forums, $DB, $_INPUT;
		if (!$stids || !is_array($stids))
		{
			$stids = array();
		}
		$forums->func->check_cache('st');
		$forums->func->check_cache('forum_' . $fid, 'forum');
		if (!empty($forums->cache['forum_' . $fid]['self']['specialtopic']))
		{
			$forum_st = explode(',', $forums->cache['forum_' . $fid]['self']['specialtopic']);
			$del_st = array_diff($forum_st, $stids);
			if (is_array($del_st) && $del_st)
			{
				$fids = '';
				foreach ($this->allforum as $id => $value)
				{
					if ($id != $fid)
					{
						$fids .= $id . ',';
					}
				}
				$fids = substr($fids, 0, -1);
				foreach ($del_st as $stid)
				{
					if ($stid)
					{
						if ($forums->cache['st'][$stid]['forumids'] != '-1')
						{
							$update_forumid = str_replace(",$fid,", '', ",{$forums->cache['st'][$stid]['forumids']},");
							$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "specialtopic SET forumids = '" . $update_forumid . "' WHERE id = {$stid}");
						}
						else
						{
							$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "specialtopic SET forumids = '" . $fids . "' WHERE id = {$stid}");
						}
					}
				}
			}
		}
		foreach ($stids as $stid)
		{
			if (empty($stid) || strstr(",{$forums->cache['st'][$stid]['forumids']},", ",$fid,"))
			{
				continue;
			}
			if ($forums->cache['st'][$stid]['forumids'] != '-1')
			{
				$forumids = explode(',', $forums->cache['st'][$stid]['forumids']);
				$forumids[] = $fid;
				if (count($forumids) == count($this->allforum))
				{
					$update_forumid = '-1';
				}
				else
				{
					asort($forumids);
					$update_forumid = implode(',', $forumids);
				}
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "specialtopic SET forumids = '" . $update_forumid . "' WHERE id = {$stid}");
			}
		}
		$forums->func->recache('st');
	}

	function editpermissions()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['f'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$forum = $this->forum;
		$next = "";
		$previous = "";
		$relative = $this->get_next_id($_INPUT['f']);
		if ($relative['next'] > 0)
		{
			$next = "<input type='submit' name='donext' value='" . $forums->lang['savenextto'] . "' class='button' />";
		}
		if ($relative['previous'] > 0)
		{
			$previous = "<input type='submit' name='doprevious' value='" . $forums->lang['saveprevto'] . "' class='button' />";
		}
		if ($this->forum['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noforumdata'] . " (" . $forums->lang['forumids'] . ": {$_INPUT['f']})");
		}
		$forums->lang['editforumpermission'] = sprintf($forums->lang['editforumpermission'], $this->forum['name']);
		$pagetitle = $forums->lang['editforumpermission'];
		$detail = "<strong>" . $forums->lang['permissionsetting'] . "</strong><br />" . $forums->lang['permissionsettingdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'doeditpermissions'),
				2 => array('f', $_INPUT['f']),
				3 => array('name', $this->forum['name']),
				4 => array('nextid', $relative['next']),
				5 => array('previd', $relative['previous']),
				));
		$forums->admin->columns[] = array($forums->lang['forumtitle'], "40%");
		$forums->admin->columns[] = array($forums->lang['viewforum'], "12%");
		$forums->admin->columns[] = array($forums->lang['viewthread'], "12%");
		$forums->admin->columns[] = array($forums->lang['replythread'], "12%");
		$forums->admin->columns[] = array($forums->lang['postnewthread'], "12%");
		$forums->admin->columns[] = array($forums->lang['uploadfile'], "12%");
		$forums->admin->print_table_start($forums->lang['editforumpermission']);
		$forums->admin->build_group_perms(array ('show' => $this->forum['canshow'], 'read' => $this->forum['canread'], 'start' => $this->forum['canstart'], 'reply' => $this->forum['canreply'], 'upload' => $this->forum['canupload']));
		$forums->admin->print_table_footer();
		echo "<div class='pformstrip' align='center'>" . $previous . "\n";
		echo "<input type='submit' value='" . $forums->lang['save'] . "' class='button' />\n";
		echo "<input type='submit' name='reload' value='" . $forums->lang['savereload'] . "' class='button' />\n";
		echo $next . "</div></form>\n";
		$forums->admin->nav[] = array('forums.php', $forums->lang['manageforum']);
		$forums->admin->nav[] = array('', $forums->lang['editforumpermission']);
		$forums->admin->print_cp_footer();
	}

	function get_next_id($fid)
	{
		global $forums;
		$nextid = 0;
		$ids = array();
		$index = 0;
		$count = 0;
		foreach($this->allforum AS $id => $forum_data)
		{
			$ids[ $count ] = $forum_data['id'];
			if ($forum_data['id'] == $fid)
			{
				$index = $count;
			}
			$count++;
			if (is_array($this->allforum[ $forum_data['id'] ]))
			{
				foreach($this->allforum[ $forum_data['id'] ] AS $id => $forum_data)
				{
					$children = $forums->forum->forums_get_children($forum_data['id']);
					$ids[ $count ] = $forum_data['id'];
					if ($forum_data['id'] == $fid)
					{
						$index = $count;
					}
					$count++;
					if (is_array($children) AND count($children))
					{
						foreach($children AS $kid)
						{
							$ids[ $count ] = $kid;
							if ($kid == $fid)
							{
								$index = $count;
							}
							$count++;
						}
					}
				}
			}
		}
		return array('next' => $ids[ $index + 1 ], 'previous' => $ids[ $index - 1 ]);
	}

	function doeditpermissions()
	{
		global $forums, $DB, $_INPUT;
		$perms = $forums->admin->compile_forum_permission();
		$DB->update(TABLE_PREFIX . 'forum', array(
			'permissions' => serialize(array(
				'canstart' => $perms['start'],
				'canreply' => $perms['reply'],
				'canread' => $perms['read'],
				'canupload' => $perms['upload'],
				'canshow' => $perms['show']
			)),
		), 'id=' . $_INPUT['f']);
		$forums->lang['permissionedited'] = sprintf($forums->lang['permissionedited'], $_INPUT['name']);
		$forums->admin->save_log($forums->lang['permissionedited']);
		$forums->func->recache('forum');
		if ($_INPUT['doprevious'] AND $_INPUT['previd'] > 0)
		{
			$forums->main_msg = $forums->lang['permissionedited'];
			$_INPUT['f'] = $_INPUT['previd'];
			$forums->func->standard_redirect("forums.php?{$forums->sessionurl}do=editpermissions&amp;f={$_INPUT['f']}");
		}
		else if ($_INPUT['donext'] AND $_INPUT['nextid'] > 0)
		{
			$forums->main_msg = $forums->lang['permissionedited'];
			$_INPUT['f'] = $_INPUT['nextid'];
			$forums->func->standard_redirect("forums.php?{$forums->sessionurl}do=editpermissions&amp;f={$_INPUT['f']}");
		}
		else if ($_INPUT['reload'])
		{
			$forums->func->standard_redirect("forums.php?{$forums->sessionurl}do=editpermissions&amp;f={$_INPUT['f']}");
		}
		else
		{
			$forums->admin->redirect("forums.php", $forums->lang['manageforum'], $forums->lang['permissionedited']);
		}
	}

	function doreorder()
	{
		global $forums, $DB, $_INPUT;
		$ids = array();
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^f_(\d+)$/", $key, $match))
			{
				if ($_INPUT[$match[0]])
				{
					$ids[ $match[1] ] = $_INPUT[$match[0]];
				}
			}
		}
		if (count($ids))
		{
			$ordersql = $fids = '';
			foreach($ids as $forumid => $new_position)
			{
				if ($forumid > 0)
				{
					$ordersql .= " WHEN id = $forumid THEN " . intval($new_position);
					$fids .= ",$forumid";
				}
			}
			if (!empty($fids))
			{
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "forum
					SET displayorder = CASE $ordersql ELSE 0 END
					WHERE id IN (0$fids)");
			}
		}
		$forums->func->recache('forum');
		$forums->func->standard_redirect("forums.php?" . $forums->sessionurl);
	}

	function show_forums()
	{
		global $forums;
		$pagetitle = $forums->lang['managecategory'];
		$detail = $forums->lang['managecategorydesc'];
		$forums->admin->nav[] = array('forums.php', $forums->lang['manageforum']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'doreorder'),));
		$forums->admin->print_table_start($forums->lang['forumlist']);
		$forums->adminforum->type = 'manage';
		$forums->adminforum->forums_list_forums($this->allforum);
		$forums->admin->print_form_submit($forums->lang['forumreorder'], '', $forums->admin->print_button($forums->lang['addcategory'], "forums.php?{$forums->sessionurl}do=new"));
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
}

$output = new forums();
$output->show();
?>