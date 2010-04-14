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
require ('./global.php');

class usergroup
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusergroups'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('usergroup.php', $forums->lang['manageusergroup']);
		switch ($_INPUT['do'])
		{
			case 'doadd':
				$this->savegroup('add');
				break;
			case 'add':
				$this->groupform('add');
				break;
			case 'edit':
				$this->groupform('edit');
				break;
			case 'doedit':
				$this->savegroup('edit');
				break;
			case 'delete':
				$this->deleteform();
				break;
			case 'dodelete':
				$this->dodeletegroup();
				break;
			case 'forumpermission':
				$this->forumpermission();
				break;
			case 'deletepermission':
				$this->deletepermission();
				break;
			case 'doforumpermission':
				$this->doforumpermission();
				break;
			case 'permission':
				$this->permission();
				break;
			case 'viewpermissionuser':
				$this->viewpermissionuser();
				break;
			case 'removepermission':
				$this->removepermission();
				break;
			case 'previewforums':
				$this->previewforums();
				break;
			case 'dopermadd':
				$this->addpermission();
				break;
			case 'addpromotions':
				$this->adduserpromotion('add');
				break;
			case 'editpromotions':
				$this->adduserpromotion('edit');
				break;
			case 'deletepromotions':
				$this->deletepromotion();
				break;
			case 'updatepromotions':
				$this->updatepromotion();
				break;
			case 'promotions':
				$this->userpromotion();
				break;
			case 'donameedit':
				$this->editpermissionname();
				break;
			case 'doreorder':
				$this->doreorder();
				break;
			default:
				$this->mainform();
				break;
		}
	}

	function userpromotion()
	{
		global $forums, $DB, $_INPUT;
		$usergroupid = intval($_INPUT['usergroupid']);
		$gquery = '';
		if ($usergroupid)
		{
			$gquery = " WHERE up.usergroupid = $usergroupid";
		}
		$pagetitle = $forums->lang['userpromotion'];
		$detail = $forums->lang['userpromotiondesc'];
		$forums->admin->nav[] = array('usergroup.php?do=promotions', $forums->lang['userpromotion']);

		$promotions = array();
		$getpromos = $DB->query("SELECT up.*, u.grouptitle
			FROM " . TABLE_PREFIX . "userpromotion up
				LEFT JOIN " . TABLE_PREFIX . "usergroup u
					ON up.joinusergroupid = u.usergroupid
			$gquery"
		);
		while ($promotion = $DB->fetch_array($getpromos))
		{
			$promotions[$promotion['usergroupid']][] = $promotion;
		}
		unset($promotion);

		$row = $DB->query_first('SELECT name
			FROM ' . TABLE_PREFIX . 'credit
			WHERE tag = \'reputation\'');
		$forums->lang['reputation'] = $row['name'];

		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array($forums->lang['promotiongroup'], "15%");
		$forums->admin->columns[] = array($forums->lang['promotiontype'], "15%");
		$forums->admin->columns[] = array($forums->lang['promotionstrategy'], "40%");
		$forums->admin->columns[] = array($forums->lang['joindate'], "10%");
		$forums->admin->columns[] = array($forums->lang['posts'], "10%");
		$forums->admin->columns[] = array($forums->lang['reputation'], "10%");
		$forums->admin->columns[] = array($forums->lang['option'], "10%");
		$forums->admin->print_form_header(array(1 => array('do', 'addpromotions')));
		$forums->admin->print_table_start($forums->lang['promotionlist']);
		if (count($promotions) > 0)
		{
			$forums->func->check_cache('usergroup');
			foreach($promotions AS $groupid => $promos)
			{
				$forums->admin->print_cells_single_row($forums->lang['pusergroup'] . " - " . $forums->lang[ $forums->cache['usergroup'][$groupid]['grouptitle'] ] . "", "left", "pformstrip");
				foreach($promos AS $promotion)
				{
					$posts = ($promotion['strategy'] < 17 OR $promotion['strategy'] == 17) ? $promotion['posts_sign'] . $promotion['posts'] : '&nbsp;';
					$date = ($promotion['strategy'] < 17 OR $promotion['strategy'] == 18) ? $promotion['date_sign'] . $promotion['date'] : '&nbsp;';
					$reputation = ($promotion['strategy'] < 17 OR $promotion['strategy'] == 19) ? $promotion['reputation_sign'] . $promotion['reputation'] : '&nbsp;';
					if ($promotion['strategy'] == 17)
					{
						$type = $forums->lang['posts'];
					}
					else if ($promotion['strategy'] == 18)
					{
						$type = $forums->lang['joindate'];
					}
					else if ($promotion['strategy'] == 19)
					{
						$type = $forums->lang['reputation'];
					}
					else if ($promotion['strategy'] == 1)
					{
						$type = $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['joindate'] . $forums->lang['and'] . $forums->lang['reputation'];
					}
					else if ($promotion['strategy'] == 2)
					{
						$type = $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['joindate'] . $forums->lang['or'] . $forums->lang['reputation'];
					}
					else if ($promotion['strategy'] == 3)
					{
						$type = "(" . $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['joindate'] . ")" . $forums->lang['or'] . $forums->lang['reputation'];
					}
					else if ($promotion['strategy'] == 4)
					{
						$type = "(" . $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['joindate'] . ")" . $forums->lang['and'] . $forums->lang['reputation'];
					}
					else if ($promotion['strategy'] == 5)
					{
						$type = $forums->lang['posts'] . $forums->lang['and'] . "(" . $forums->lang['joindate'] . $forums->lang['or'] . $forums->lang['reputation'] . ")";
					}
					else if ($promotion['strategy'] == 6)
					{
						$type = $forums->lang['posts'] . $forums->lang['or'] . "(" . $forums->lang['joindate'] . $forums->lang['and'] . $forums->lang['reputation'] . ")";
					}
					else if ($promotion['strategy'] == 7)
					{
						$type = "(" . $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['reputation'] . ")" . $forums->lang['and'] . $forums->lang['joindate'];
					}
					else if ($promotion['strategy'] == 8)
					{
						$type = "(" . $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['reputation'] . ")" . $forums->lang['or'] . $forums->lang['joindate'];
					}
					$forums->admin->print_cells_row(array(
						"<strong>" . $forums->lang[ $promotion['grouptitle'] ] . "</strong>" ,
						$promotion['type'] == 1 ? $forums->lang['changemastergroup'] : $forums->lang['addtomembersgroup'],
						$type,
						$date,
						$posts,
						$reputation,
						"<div align='center'><a href='usergroup.php?{$forums->sessionurl}do=editpromotions&amp;id={$promotion['userpromotionid']}'>" . $forums->lang['edit'] . "</a> <a href='usergroup.php?{$forums->sessionurl}do=deletepromotions&amp;id={$promotion['userpromotionid']}'>" . $forums->lang['delete'] . "</a></div>",
					));
				}
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noanypromotions'], "center");
		}
		$forums->admin->print_form_submit($forums->lang['addnewpromotions']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function adduserpromotion($type = 'edit')
	{
		global $forums, $DB, $_INPUT;
		$DB->query("SELECT usergroupid, grouptitle
			FROM " . TABLE_PREFIX . "usergroup
			ORDER BY grouptitle");
		while ($r = $DB->fetch_array())
		{
			$usergroup[] = array($r['usergroupid'] , $forums->lang[ $r['grouptitle'] ]);
		}
		if ($type == 'add')
		{
			$promotion = array('date' => 30, 'posts' => 100, 'reputation' => 50, 'type' => 1, 'strategy' => 17, 'userpromotionid' => '', 'date_sign' => '>=', 'posts_sign' => '>=', 'reputation_sign' => '>=');
			$pagetitle = $forums->lang['addnewpromotions'];
			$detail = $forums->lang['addpromotionsdesc'];
		}
		else
		{
			$promotion = $DB->query_first("SELECT up.*, u.grouptitle
				FROM " . TABLE_PREFIX . "userpromotion up, " . TABLE_PREFIX . "usergroup u
				WHERE up.userpromotionid = " . $_INPUT['id'] . "
					AND up.usergroupid = u.usergroupid"
			);
			$pagetitle = $forums->lang['editpromotions'];
			$detail = $forums->lang['editpromotionsdesc'];
		}
		$signlist = array('>=', '<=', '>', '<', '==');
		$forums->admin->print_cp_header($pagetitle, $detail);

		$forums->admin->print_form_header(array(1 => array('do' , 'updatepromotions'), 2 => array('userpromotionid' , $promotion['userpromotionid'])));
		$forums->admin->columns[] = array("&nbsp;", "50%");
		$forums->admin->columns[] = array("&nbsp;", "50%");
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usepromotiongroup'] . "</strong>" , $forums->admin->print_input_select_row("usergroupid", $usergroup, $promotion['usergroupid'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['joindates'] . "</strong>", $forums->admin->print_input_select_row('date_sign', $signlist, $promotion['date_sign']) . $forums->admin->print_input_row("date", $promotion['date'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['posts'] . "</strong>", $forums->admin->print_input_select_row('posts_sign', $signlist, $promotion['posts_sign']) . $forums->admin->print_input_row("posts", $promotion['posts'])));

		$row = $DB->query_first('SELECT name
			FROM ' . TABLE_PREFIX . 'credit
			WHERE tag = \'reputation\'');
		$forums->lang['reputation'] = $row['name'];
		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['reputation'] . "</strong>",
			$forums->admin->print_input_select_row('reputation_sign', $signlist, $promotion['reputation_sign']) . $forums->admin->print_input_row("reputation", $promotion['reputation'])
		));
		$forums->admin->print_cells_row(array(
			"<strong>" . $forums->lang['promotionstrategy'] . "</strong><div class='description'>" . $forums->lang['promotionstrategydesc'] . "</div>",
			$forums->admin->print_input_select_row('strategy', array(
				0 => array('17', $forums->lang['posts']),
				1 => array('18', $forums->lang['joindates']),
				2 => array('19', $forums->lang['reputation']),
				3 => array('1', $forums->lang['complex'] . ': ' . $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['joindate'] . $forums->lang['and'] . $forums->lang['reputation']),
				4 => array('2', $forums->lang['complex'] . ': ' . $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['joindate'] . $forums->lang['or'] . $forums->lang['reputation']),
				5 => array('3', $forums->lang['complex'] . ': (' . $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['joindate'] . ')' . $forums->lang['or'] . $forums->lang['reputation']),
				6 => array('4', $forums->lang['complex'] . ': (' . $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['joindate'] . ')' . $forums->lang['and'] . $forums->lang['reputation']),
				7 => array('5', $forums->lang['complex'] . ': ' . $forums->lang['posts'] . $forums->lang['and'] . '(' . $forums->lang['joindate'] . $forums->lang['or'] . $forums->lang['reputation'] . ')'),
				8 => array('6', $forums->lang['complex'] . ': ' . $forums->lang['posts'] . $forums->lang['or'] . '(' . $forums->lang['joindate'] . $forums->lang['and'] . $forums->lang['reputation'] . ')'),
				9 => array('7', $forums->lang['complex'] . ': (' . $forums->lang['posts'] . $forums->lang['or'] . $forums->lang['reputation'] . ')' . $forums->lang['and'] . $forums->lang['joindate']),
				10 => array('8', $forums->lang['complex'] . ': (' . $forums->lang['posts'] . $forums->lang['and'] . $forums->lang['reputation'] . ')' . $forums->lang['or'] . $forums->lang['joindate']),
			), $promotion['strategy'])
		));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['promotiontype'] . "</strong><div class='description'>" . $forums->lang['promotiontypedesc'] . "</div>", $forums->admin->print_input_select_row('type',
					array(0 => array('1', $forums->lang['mastergroup']),
						1 => array('2', $forums->lang['membersgroup']),
						), $promotion['type']
					)));
		$promotionusergroup = array_merge(array(0 => array(-1 , $forums->lang['selectpromotiongroup'])), $usergroup);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['promotiontogroup'] . "</strong>" ,
				$forums->admin->print_input_select_row("joinusergroupid", $promotionusergroup, $promotion['joinusergroupid'])
				));
		$forums->admin->print_form_submit($forums->lang['savepromotion']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function updatepromotion()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['joinusergroupid'] == -1)
		{
			$forums->admin->print_cp_error($forums->lang['selectpromotiongroup']);
		}
		if ($_INPUT['usergroupid'] == 4)
		{
			$forums->admin->print_cp_error($forums->lang['istoppromotion']);
		}
		if (!$group = $DB->query_first("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid=" . intval($_INPUT['usergroupid']) . ""))
		{
			$forums->admin->print_cp_error($forums->lang['promotiongroupnotfound']);
		}
		$promotion = array('usergroupid' => intval($_INPUT['usergroupid']),
			'joinusergroupid' => intval($_INPUT['joinusergroupid']),
			'date' => intval($_INPUT['date']),
			'posts' => intval($_INPUT['posts']),
			'reputation' => intval($_INPUT['reputation']),
			'strategy' => intval($_INPUT['strategy']),
			'type' => intval($_INPUT['type']),
			'date_sign' => trim($_POST['date_sign']),
			'posts_sign' => trim($_POST['posts_sign']),
			'reputation_sign' => trim($_POST['reputation_sign']),
		);
		if ($_INPUT['usergroupid'] == $promotion['joinusergroupid'])
		{
			$forums->admin->print_cp_error($forums->lang['promotionnotsame']);
		}
		if (!empty($_INPUT['userpromotionid']))
		{
			$DB->update(TABLE_PREFIX . 'userpromotion', $promotion, 'userpromotionid=' . intval($_INPUT['userpromotionid']));
		}
		else
		{
			$DB->insert(TABLE_PREFIX . 'userpromotion', $promotion);
		}
		$forums->lang['addpromotions'] = sprintf($forums->lang['addpromotions'], $change);
		$forums->admin->save_log($forums->lang['addpromotions']);
		$forums->admin->redirect("usergroup.php?do=promotions", $forums->lang['userpromotion'], $forums->lang['promotionadded']);
	}

	function deletepromotion()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['update'])
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "userpromotion WHERE userpromotionid = " . $_INPUT['userpromotionid'] . "");
			$forums->admin->save_log($forums->lang['deletepromotion']);
			$forums->admin->redirect("usergroup.php?do=promotions", $forums->lang['userpromotion'], $forums->lang['promotiondeleted']);
		}
		else
		{
			$pagetitle = $forums->lang['deletepromotion'];
			$detail = $forums->lang['confirmdeletepromotion'];
			$forums->admin->nav[] = array('', $forums->lang['deletepromotion']);
			$forums->admin->print_cp_header($pagetitle, $detail);
			$forums->admin->print_form_header(array(1 => array('do', 'deletepromotions'), 2 => array('userpromotionid', $_INPUT['id']), 3 => array('update', 1)));
			$forums->admin->print_table_start($forums->lang['confirmdeletepromotion']);
			$forums->admin->print_cells_single_row($forums->lang['deletepromotiondesc'], "center");
			$forums->admin->print_form_submit($forums->lang['confirmdelete']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			$forums->admin->print_cp_footer();
		}
	}

	function previewforums()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (! $perms = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid=" . $_INPUT['id'] . ""))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		switch ($_INPUT['t'])
		{
			case 'start':
				$type = $forums->lang['postnewthread'];
				$code_word = 'canstart';
				break;
			case 'reply':
				$type = $forums->lang['replythread'];
				$code_word = 'canreply';
				break;
			case 'show':
				$type = $forums->lang['viewboard'];
				$code_word = 'canshow';
				break;
			case 'upload':
				$type = $forums->lang['uploadattach'];
				$code_word = 'canupload';
				break;
			default:
				$type = $forums->lang['readforum'];
				$code_word = 'canread';
				break;
		}
		$forums->admin->print_popup_header();
		$forums->admin->print_form_header(array(1 => array('do', 'previewforums'), 2 => array('id', $_INPUT['id'])));
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->print_table_start($forums->lang['legendreference']);
		$forums->admin->print_cells_row(array($forums->lang['can'] . $type, "<input type='text' readonly='readonly' style='border:1px solid black;size=30px' name='blah' />"));
		$forums->admin->print_cells_row(array($forums->lang['notcan'] . $type, "<input type='text' readonly='readonly' style='border:1px solid black;background-color:yellow;size=30px' name='blah' />"));
		$forums->admin->print_cells_row(array($forums->lang['testtype'],
				$forums->admin->print_input_select_row('t',
					array(0 => array('start', $forums->lang['postnewthread']),
						1 => array('reply', $forums->lang['replythread']),
						2 => array('read' , $forums->lang['readforum']),
						3 => array('show' , $forums->lang['viewboard']),
						4 => array('upload', $forums->lang['uploadattach']),
						), $_INPUT['t'])
				));
		$forums->admin->print_form_submit($forums->lang['permissiontest']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->columns[] = array("$type" , "100%");
		$forums->admin->print_table_start($forums->lang['viewpermsgroup'] . ": " . $forums->lang[ $perms['grouptitle'] ]);
		$html = "";
		$perm_id = intval($_INPUT['id']);
		$allforum = $forums->adminforum->forumcache;
		foreach($allforum AS $key => $value)
		{
			$theforums[] = array($value[id], depth_mark($value['depth'], '--') . $value[name]);
		}
		foreach($theforums AS $i => $v)
		{
			$id = $v[0];
			$name = $v[1];
			if ($allforum[$id][ $code_word ] == '*')
			{
				$html[] = "<span style='background-color:white;color:black;'><strong>" . $name . "</strong></span>";
			}
			else if (preg_match("/(^|,)" . $perm_id . "(,|$)/", $allforum[$id][ $code_word ]))
			{
				$html[] = "<span style='background-color:white;color:black;'><strong>" . $name . "</strong></span>";
			}
			else
			{
				$html[] = "<span style='background-color:yellow;color:black;'><strong>" . $name . "</strong></span>";
			}
		}
		$html = implode("<br />", $html);
		$forums->admin->print_cells_row(array($html));
		$forums->admin->print_table_footer();
		$forums->admin->print_popup_footer();
	}

	function permission()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managepermission'];
		$detail = $forums->lang['managepermissiondesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$perms = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "usergroup");
		while ($r = $DB->fetch_array())
		{
			$perms[ $r['usergroupid'] ] = $forums->lang[ $r['grouptitle'] ];
		}
		$forums->admin->columns[] = array($forums->lang['permsgroupname'], "20%");
		$forums->admin->columns[] = array($forums->lang['previewperms'], "10%");
		$forums->admin->columns[] = array($forums->lang['edit'], "15%");
		$forums->admin->print_table_start($forums->lang['managepermission']);
		foreach($perms AS $id => $name)
		{
			$forums->admin->print_cells_row(array("<strong>$name</strong>" ,
					"<center><span style='cursor:pointer;' onclick='javascript:pop_win(\"usergroup.php?{$forums->sessionurl}do=previewforums&amp;id=$id&amp;t=read\", \"600\",\"700\");' title='" . $forums->lang['previewpermsdesc'] . "'>" . $forums->lang['previewperms'] . "</span></center>",
					"<center><a href='usergroup.php?{$forums->sessionurl}do=forumpermission&amp;id=$id'>" . $forums->lang['edit'] . "</a></center>",
					));
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function forumpermission()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$group = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid=" . $_INPUT['id'] . "");
		$gid = $group['usergroupid'];
		$gname = $forums->lang[ $group['grouptitle'] ];
		$forums->lang['editgroupperms'] = sprintf($forums->lang['editgroupperms'], $gname);
		$pagetitle = $forums->lang['editgroupperms'];
		$detail = $forums->lang['editgrouppermsdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'doforumpermission'), 2 => array('id', $gid)));
		$forums->admin->columns[] = array($forums->lang['forumtitle'], "25%");
		$forums->admin->columns[] = array($forums->lang['viewboard'], "10%");
		$forums->admin->columns[] = array($forums->lang['readforum'], "10%");
		$forums->admin->columns[] = array($forums->lang['replythread'], "10%");
		$forums->admin->columns[] = array($forums->lang['postnewthread'], "10%");
		$forums->admin->columns[] = array($forums->lang['uploadattach'], "10%");
		$allforum = $forums->adminforum->forumcache;
		$forums->admin->print_table_start($forums->lang['editgroupperms']);
		foreach($allforum AS $id => $r)
		{
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				if ($r['can' . $bit] == '*')
				{
					$permission[ $bit ] = " checked='checked'";
				}
				else if (preg_match("/(^|,)" . $gid . "(,|$)/", $r['can' . $bit]))
				{
					$permission[ $bit ] = " checked='checked'";
				}
				else if ($r['can' . $bit] == '')
				{
					if ($bit == 'show')
					{
						$permission['show'] = $group['canshow'] ? " checked" : "";
					}
					if ($bit == 'read')
					{
						$permission['read'] = $group['canviewothers'] ? " checked" : "";
					}
					if ($bit == 'reply')
					{
						$permission['reply'] = $group['canreplyothers'] ? " checked" : "";
					}
					if ($bit == 'start')
					{
						$permission['start'] = $group['canpostnew'] ? " checked" : "";
					}
					if ($bit == 'upload')
					{
						$permission['upload'] = ($group['attachlimit'] == -1 OR $group['attachlimit'] == '') ? "" : " checked";
					}
				}
				else
				{
					$permission[ $bit ] = "";
				}
			}
			$forums->admin->print_cells_row(array("<div style='float:right'><input type='button' class='button' value='+' onclick='checkrow({$r['id']},1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkrow({$r['id']},0)' /></div><strong>" . $r['name'] . "</strong>",
					"<center class='pgroup1'><input type='checkbox' name='show_" . $r['id'] . "' value='1'" . $permission['show'] . " /></center>",
					"<center class='pgroup2'><input type='checkbox' name='read_" . $r['id'] . "' value='1'" . $permission['read'] . " /></center>",
					"<center class='pgroup3'><input type='checkbox' name='reply_" . $r['id'] . "' value='1'" . $permission['reply'] . " /></center>",
					"<center class='pgroup4'><input type='checkbox' name='start_" . $r['id'] . "' value='1'" . $permission['start'] . " /></center>",
					"<center class='pgroup5'><input type='checkbox' name='upload_" . $r['id'] . "' value='1'" . $permission['upload'] . " /></center>",
					));
		}
		$forums->admin->print_cells_row(array("&nbsp;",
				"<center><input type='button' class='button' value='+' onclick='checkcol(5,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(5,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(1,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(1,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(2,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(2,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(3,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(3,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(4,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(4,0)' /></center>",
				));
		$forums->admin->print_form_submit($forums->lang['updategroupperms']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doforumpermission()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$gid = $_INPUT['id'];
		$thisgroup = false;
		$forumpermission = $DB->query("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY usergroupid");
		while ($group = $DB->fetch_array($forumpermission))
		{
			if ($_INPUT['id'] == $group['usergroupid'])
			{
				$thisgroup = true;
			}
			$groupperms[] = $group['usergroupid'];
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				if ($group['canshow'] AND $bit == 'show')
				{
					$g_array['show'] .= $group['usergroupid'] . ",";
				}
				if ($group['canviewothers'] AND $bit == 'read')
				{
					$g_array['read'] .= $group['usergroupid'] . ",";
				}
				if ($group['canpostnew'] AND $bit == 'start')
				{
					$g_array['start'] .= $group['usergroupid'] . ",";
				}
				if ($group['canreplyothers'] AND $bit == 'reply')
				{
					$g_array['reply'] .= $group['usergroupid'] . ",";
				}
				if ($group['attachlimit'] != -1 AND $group['attachlimit'] != '' AND $bit == 'upload')
				{
					$g_array['upload'] .= $group['usergroupid'] . ",";
				}
			}
		}
		if (! $thisgroup)
		{
			$forums->admin->print_cp_error($forums->lang['nogroupperms']);
		}
		$forumperms = $DB->query("SELECT * FROM " . TABLE_PREFIX . "forum");
		while ($row = $DB->fetch_array($forumperms))
		{
			$perms = unserialize($row['permissions']);
			$newperms = array();
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				$newperms[ $bit ] = '';
				if ($perms['can' . $bit] == '*')
				{
					if ($_INPUT[ $bit . '_' . $row['id'] ] != 1)
					{
						foreach ($groupperms AS $g)
						{
							if ($gid == $g)
							{
								continue;
							}
							else
							{
								$newperms[ $bit ] .= $g . ",";
							}
						}
						$newperms[ $bit ] = $newperms[ $bit ] == $g_array[$bit] ? "" : $newperms[ $bit ];
					}
					else
					{
						$newperms[ $bit ] = '*';
					}
				}
				else if ($perms['can' . $bit] == '')
				{
					$bit_ids = explode(",", $g_array[$bit]);
					foreach ($bit_ids AS $i)
					{
						if ($i == '') continue;
						if ($gid != $i)
						{
							$newperms[ $bit ] .= $i . ",";
							$bit_counts++;
						}
					}
					if ($_INPUT[ $bit . '_' . $row['id'] ] == 1)
					{
						$newperms[ $bit ] .= $gid . ",";
						$bit_counts++;
					}
					$zzzzzz = explode(",", $newperms[ $bit ]);
					foreach ($zzzzzz AS $newids)
					{
						if (!$newids) continue;
						$dddd[$newids] = $newids;
					}
					if (is_array($dddd))
					{
						ksort($dddd);
						$tempids = implode(',', $dddd) . ',';
					}
					$newperms[ $bit ] = $tempids == $g_array[$bit] ? "" : $tempids;
					if ($bit_counts == count($groupperms))
					{
						$newperms[ $bit ] = '*';
					}
					unset($bit_counts, $bit_ids, $dddd);
					$newperms[ $bit ] = preg_replace("/,$/", "", $newperms[ $bit ]);
					$newperms[ $bit ] = preg_replace("/^,/", "", $newperms[ $bit ]);
				}
				else
				{
					$bit_ids = explode(",", $perms['can' . $bit]);
					if (is_array($bit_ids))
					{
						$bit_counts = 0;
						foreach ($bit_ids AS $i)
						{
							if ($i == '') continue;
							if ($gid != $i AND $i != '')
							{
								$newperms[ $bit ] .= $i . ",";
								$bit_counts++;
							}
						}
					}
					if ($_INPUT[ $bit . '_' . $row['id'] ] == 1)
					{
						$newperms[ $bit ] .= $gid . ",";
						$bit_counts++;
					}
					$zzzzzz = explode(",", $newperms[ $bit ]);
					foreach ($zzzzzz AS $newids)
					{
						if (!$newids) continue;
						$dddd[$newids] = $newids;
					}
					if (is_array($dddd))
					{
						ksort($dddd);
						$tempids = implode(',', $dddd) . ',';
					}
					$newperms[ $bit ] = $tempids == $g_array[$bit] ? "" : $tempids;
					if ($bit_counts == count($groupperms))
					{
						$newperms[ $bit ] = '*';
					}
					unset($bit_counts, $bit_ids, $dddd);
					$newperms[ $bit ] = preg_replace("/,$/", "", $newperms[ $bit ]);
					$newperms[ $bit ] = preg_replace("/^,/", "", $newperms[ $bit ]);
				}
			}
			$DB->update(TABLE_PREFIX . 'forum', array(
				'permissions' => serialize(array(
					'canstart' => $newperms['start'],
					'canreply' => $newperms['reply'],
					'canread' => $newperms['read'],
					'canupload' => $newperms['upload'],
					'canshow' => $newperms['show'],
				))
			), 'id=' . $row['id']);
		}
		$forums->func->recache('forum');
		$forums->lang['grouppermsedited'] = sprintf($forums->lang['grouppermsedited'], $change);
		$forums->admin->save_log($forums->lang['grouppermsedited']);
		$forums->admin->redirect("usergroup.php?do=permission", $forums->lang['managepermission'], $forums->lang['grouppermsupdated']);
	}

	function deleteform()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($_INPUT['id'] < 7)
		{
			$forums->admin->print_cp_error($forums->lang['cannotdeletepregroup']);
		}
		$black_adder = $DB->query_first("SELECT COUNT(id) as users FROM " . TABLE_PREFIX . "user WHERE usergroupid=" . $_INPUT['id'] . "");
		if ($black_adder['users'] < 1)
		{
			$black_adder['users'] = 0;
		}
		$group = $DB->query_first("SELECT grouptitle FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid=" . $_INPUT['id'] . "");
		$DB->query("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid <> " . $_INPUT['id'] . "");
		$usergroup = array();
		while ($r = $DB->fetch_array())
		{
			$usergroup[] = array($r['usergroupid'], $forums->lang[ $r['grouptitle'] ]);
		}
		$pagetitle = $forums->lang['deleteusergroup'] . " - " . $forums->lang[ $group['grouptitle'] ];
		$detail = $forums->lang['deleteusergroupdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'dodelete'), 2 => array('id', $_INPUT['id']), 3 => array('grouptitle', $forums->lang[ $group['grouptitle'] ])));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['deleteusergroup'] . " - " . $forums->lang[ $group['grouptitle'] ]);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['deletegroupusers'] . "</strong>", "<strong>" . $black_adder['users'] . "</strong>"));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['deletegroupusermoveto'] . "</strong>", $forums->admin->print_input_select_row("to_id", $usergroup)));
		$forums->admin->print_form_submit($forums->lang['deleteusergroup']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function dodeletegroup()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['id'] = intval($_INPUT['id']);
		$_INPUT['to_id'] = intval($_INPUT['to_id']);
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($_INPUT['to_id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['nodeletegroupid']);
		}
		if ($_INPUT['id'] == $_INPUT['to_id'])
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		$DB->update(TABLE_PREFIX . 'user', array('usergroupid' => $_INPUT['to_id']), 'usergroupid = ' . $_INPUT['id']);
		$DB->delete(TABLE_PREFIX . 'usergroup', 'usergroupid = ' . $_INPUT['id']);
		$DB->delete(TABLE_PREFIX . 'moderator', 'isgroup = 1 AND usergroupid = ' . $_INPUT['id']);

		$result = $DB->query_first("SELECT grouptitle, groupranks
				FROM " . TABLE_PREFIX . 'usergroup
			WHERE usergroupid = ' . $_INPUT['id']);

		require_once ROOT_PATH . "includes/adminfunctions_language.php";
		require ROOT_PATH . 'languages/list.php';
		foreach ($lang_list as $k => $v)
		{
			$file = ROOT_PATH . 'languages/' . $k . '/init.php';
			@include($file);

			if ($result['grouptitle'] && isset($lang[$result['grouptitle']]))
			{
				unset($lang[$result['grouptitle']]);
			}

			if ($result['groupranks'] && isset($lang[$result['groupranks']]))
			{
				unset($lang[$result['groupranks']]);
			}


			adminfunctions_language::writefile($file, $lang);
		}

		$forums->func->rmcache('usergroup_' . $_INPUT['id']);
		$forums->func->recache('usergroup');
		$forums->admin->save_log($forums->lang['usergroupedited'] . " - '{$forums->lang[ $_INPUT['grouptitle'] ]}'");
		$forums->admin->redirect("usergroup.php", $forums->lang['manageusergroup'], $forums->lang['usergroupdeleted']);
	}

	function savegroup($type = 'edit')
	{
		global $forums, $DB, $_INPUT, $bboptions;
		if ($_INPUT['grouptitle'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['requiregrouptitle']);
		}
		if ($type == 'edit')
		{
			if ($_INPUT['id'] == "")
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			if ($_INPUT['id'] == 4 AND $_INPUT['cancontrolpanel'] != 1)
			{
				$forums->admin->print_cp_error($forums->lang['cannotforbidadmincp']);
			}
		}
		$opentag = preg_replace("/&#39;/", "'" , convert_andstr($_POST['opentag']));
		$opentag = preg_replace("/&lt;/" , "<" , $opentag);
		$closetag = preg_replace("/&#39;/", "'" , convert_andstr($_POST['closetag']));
		$closetag = preg_replace("/&lt;/" , "<" , $closetag);
		if ($_INPUT['attachlimit'] != 0)
		{
			if (isset($_INPUT['perpostattach']) AND $_INPUT['attachlimit'] != -1)
			{
				if (($_INPUT['perpostattach'] > $_INPUT['attachlimit']) OR ($_INPUT['perpostattach'] == 0 AND $_INPUT['attachlimit'] > 0))
				{
					$forums->main_msg = $forums->lang['postattachuplimit'];
					$this->groupform('edit');
				}
			}
		}
		$_INPUT['p_width'] = str_replace(":", "", $_INPUT['p_width']);
		$_INPUT['p_height'] = str_replace(":", "", $_INPUT['p_height']);
		$usergroup = array('grouptitle' => trim($_INPUT['grouptitle']),
			'groupicon' => trim(convert_andstr($_POST['groupicon'])),
			'onlineicon' => trim(convert_andstr($_POST['onlineicon'])),
			'canview' => intval($_INPUT['canview']),
			'canshow' => intval($_INPUT['canshow']),
			'canviewmember' => intval($_INPUT['canviewmember']),
			'canviewothers' => intval($_INPUT['canviewothers']),
			'cansearch' => intval($_INPUT['cansearch']),
			'cansearchpost' => intval($_INPUT['cansearchpost']),
			'cansignature' => intval($_INPUT['cansignature']),
			'canemail' => intval($_INPUT['canemail']),
			'caneditprofile' => intval($_INPUT['caneditprofile']),
			'canpostnew' => intval($_INPUT['canpostnew']),
			'cananonymous' => intval($_INPUT['cananonymous']),
			'canreplyown' => intval($_INPUT['canreplyown']),
			'canreplyothers' => intval($_INPUT['canreplyothers']),
			'caneditpost' => intval($_INPUT['caneditpost']),
			'edittimecut' => intval($_INPUT['edittimecut']),
			'candeletepost' => intval($_INPUT['candeletepost']),
			'canopenclose' => intval($_INPUT['canopenclose']),
			'candeletethread' => intval($_INPUT['candeletethread']),
			'canpostpoll' => intval($_INPUT['canpostpoll']),
			'canvote' => intval($_INPUT['canvote']),
			'candownload' => intval($_INPUT['candownload']),
			'canuseflash' => intval($_INPUT['canuseflash']),
			'cansigimg' => intval($_INPUT['cansigimg']),
			'supermod' => intval($_INPUT['supermod']),
			'cancontrolpanel' => intval($_INPUT['cancontrolpanel']),
			'canappendedit' => intval($_INPUT['canappendedit']),
			'canviewoffline' => intval($_INPUT['canviewoffline']),
			'passmoderate' => intval($_INPUT['passmoderate']),
			'passflood' => intval($_INPUT['passflood']),
			'attachlimit' => intval($_INPUT['attachlimit']),
			'canuseavatar' => intval($_INPUT['canuseavatar']),
			'pmquota' => intval($_INPUT['pmquota']),
			'pmsendmax' => intval($_INPUT['pmsendmax']),
			'searchflood' => intval($_INPUT['searchflood']),
			'opentag' => $opentag,
			'closetag' => $closetag,
			'groupranks' => convert_andstr($_POST['groupranks']),
			'hidelist' => $_INPUT['hidelist'],
			'canpostclosed' => intval($_INPUT['canpostclosed']),
			'canposthtml' => intval($_INPUT['canposthtml']),
			'caneditthread' => intval($_INPUT['caneditthread']),
			'passbadword' => intval($_INPUT['passbadword']),
			'canpmattach' => intval($_INPUT['canpmattach']),
			'perpostattach' => $_INPUT['perpostattach'],
			'canevaluation' => intval($_INPUT['canevaluation']),
			'canevalsameuser' => intval($_INPUT['canevalsameuser']),
			'attachnum' => intval($_INPUT['attachnum']),
			'grouppower' => intval($_INPUT['grouppower']),
		);
		if ($type == 'edit')
		{
			$DB->update(TABLE_PREFIX . 'usergroup', $usergroup, 'usergroupid=' . $_INPUT['id']);
			$DB->update(TABLE_PREFIX . 'moderator', array('usergroupname' =>trim($_INPUT['grouptitle'])), 'usergroupid =' . $_INPUT['id']);
			$usergroupid = $_INPUT['id'];
			$forums->admin->save_log($forums->lang['usergroupedited'] . " - '{$forums->lang[ $_INPUT['grouptitle'] ]}'");
		}
		else
		{
			$DB->insert(TABLE_PREFIX . 'usergroup', $usergroup);
			$usergroupid = $DB->insert_id();
			$DB->query_unbuffered('UPDATE ' . TABLE_PREFIX . 'usergroup SET displayorder = ' . $usergroupid . ' WHERE usergroupid = ' . $usergroupid);
			$forums->admin->save_log($forums->lang['usergroupadded'] . " - '{$forums->lang[ $_INPUT['grouptitle'] ]}'");
		}

		$langvar = $groupvar = array();
		$name = 'fieldusergroup' . $usergroupid;
		$langvar[$name . '_title'] = trim($_INPUT['grouptitle']);
		$groupvar['grouptitle'] = $name . '_title';
		if ($_POST['groupranks'])
		{
			$langvar[$name . '_rank'] = convert_andstr($_POST['groupranks']);
			$groupvar['groupranks'] = $name . '_rank';
		}
		$DB->update(TABLE_PREFIX . 'usergroup', $groupvar, 'usergroupid=' . $usergroupid);
		$this->updatelanguage($langvar);
		$forums->func->recache('usergroup');

		if ($type == 'edit')
		{
			$forums->admin->redirect("usergroup.php", $forums->lang['manageusergroup'], $forums->lang['usergroupedited']);
		}
		else
		{
			$forums->admin->redirect("usergroup.php", $forums->lang['manageusergroup'], $forums->lang['usergroupadded']);
		}
	}

	function clean_perms($str)
	{
		return str_replace(",,", ",", preg_replace("/,$/", "", $str));
	}

	function groupform($type = 'edit')
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;

		if ($type == 'edit')
		{
			if ($_INPUT['id'] == "")
			{
				$forums->admin->print_cp_error($forums->lang['usergroupnotselect']);
			}
			if ($_INPUT['id'] == 4)
			{
				if ($bbuserinfo['usergroupid'] != 4)
				{
					$forums->admin->print_cp_error($forums->lang['usergroupnotedit']);
				}
			}
			$form_code = 'doedit';
			$button = $forums->lang['dogroupedit'];
		}
		else
		{
			$form_code = 'doadd';
			$button = $forums->lang['addusergroup'];
		}
		$group = array();
		if ($_INPUT['id'] != "")
		{
			$group = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid=" . $_INPUT['id'] . "");
		}

		$translatetitle = $translaterank = '';
		if ($type == 'edit')
		{
			$lang = $forums->func->load_lang('init', true);
			if (isset($lang[$group['grouptitle']]))
			{
				$translatetitle = '(<a href="' . ROOT_PATH . 'admin/language.php?' . $forums->sessionurl . 'do=editmutivar&amp;vid=' . $group['grouptitle'] . '&amp;fid=init.php" target="_blank">' . $forums->lang['translate'] . "</a>)";
			}
			if (isset($lang[$group['groupranks']]))
			{
				$translaterank = '(<a href="' . ROOT_PATH . 'admin/language.php?' . $forums->sessionurl . 'do=editmutivar&amp;vid=' . $group['groupranks'] . '&amp;fid=init.php" target="_blank">' . $forums->lang['translate'] . "</a>)";
			}

			$group['grouptitle'] = $lang[$group['grouptitle']];
			$pagetitle = $forums->lang['editusergroup'] . " - " . $group['grouptitle'];

		}
		else
		{
			$pagetitle = $forums->lang['addusergroup'];
			$group['grouptitle'] = $forums->lang['inputgrouptitle'];
		}
		$detail = $forums->lang['editusergroupdesc'];
		$forums->admin->nav[] = array('' , $pagetitle);
		$forums->admin->print_cp_header($pagetitle, $detail);
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "<!--\n";
		echo "function checkform() {\n";
		echo "isAdmin = document.forms[0].cancontrolpanel;\n";
		echo "isMod   = document.forms[0].supermod;\n";
		echo "msg = '';\n";
		echo "if (isAdmin && isAdmin[0].checked == true)\n";
		echo "{\n";
		echo "msg += '" . $forums->lang['canloginadmincp'] . "\\n\\n';\n";
		echo "}\n";
		echo "if (isMod && isMod[0].checked == true)\n";
		echo "{\n";
		echo "msg += '" . $forums->lang['issupermoderator'] . "\\n\\n';\n";
		echo "}\n";
		echo "if (msg != '')\n";
		echo "{\n";
		echo "msg = '" . $forums->lang['warning'] . "\\n--------------\\" . $forums->lang['usergroup'] . ": ' + document.forms[0].grouptitle.value + '\\n--------------\\n\\n' + msg + '" . $forums->lang['doaction'] . "';\n";
		echo "formCheck = confirm(msg);\n";
		echo "if (formCheck == true)\n";
		echo "{\n";
		echo "return true;\n";
		echo "}\n";
		echo "else\n";
		echo "{\n";
		echo "return false;\n";
		echo "}\n";
		echo "}\n";
		echo "}\n";
		echo "//-->\n";
		echo "</script>\n";
		$forums->admin->print_form_header(array(1 => array('do', $form_code), 2 => array('id', $_INPUT['id'])) , 'adform', "onSubmit='return checkform()'");
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$opentag = str_replace("'", '&#39;', $group['opentag']);
		$opentag = str_replace('<', '&lt;', $opentag);
		$closetag = str_replace("'", '&#39;', $group['closetag']);
		$closetag = str_replace('<', '&lt;', $closetag);
		$forums->admin->print_table_start($forums->lang['basicsetting'], $forums->lang['basicsettingdesc']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergrouptitle'] . "</strong>", $forums->admin->print_input_row("grouptitle", $group['grouptitle']) . "&nbsp;" . $translatetitle));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergroupicon'] . "</strong><div class='description'>" . $forums->lang['usergroupicondesc'] . "</div>", $forums->admin->print_input_row("groupicon", $group['groupicon'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergroupprefix'] . "</strong><div class='description'>" . $forums->lang['usergroupprefixdesc'] . "</div>", $forums->admin->print_input_row("opentag", $opentag, '', '', 15) . " {$forums->lang['username']} " . $forums->admin->print_input_row("closetag", $closetag, '', '', 15)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergrouponlineicon'] . "</strong><div class='description'>" . $forums->lang['usergrouponlineicondesc'] . "</div>", $forums->admin->print_input_row("onlineicon", $group['onlineicon'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergrouprank'] . "</strong><div class='description'>" . $forums->lang['usergrouprankdesc'] . "</div>", $forums->admin->print_input_row("groupranks", $forums->lang[ $group['groupranks'] ]) . "&nbsp;" . $translaterank));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['usergrouppower'] . "</strong><div class='description'>" . $forums->lang['usergrouppowerdesc'] . "</div>", $forums->admin->print_input_row("grouppower", $group['grouppower'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['hidelist'] . "</strong>", $forums->admin->print_yes_no_row("hidelist", $group['hidelist'])));
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['groupuploadperms'], $forums->lang['groupuploadpermsdesc']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['groupattachlimit'] . "</strong><div class='description'>" . $forums->lang['groupattachlimitdesc'] . "</div>", $forums->admin->print_input_row("attachlimit", $group['attachlimit']) . ' (' . $forums->lang['convert_value'] . ': ' . ($group['attachlimit'] == 0 ? $forums->lang['nolimit'] : fetch_number_format($group['attachlimit'] * 1024, true)) . ')'));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['perpostattach'] . "</strong><div class='description'>" . $forums->lang['perpostattachdesc'] . "</div>", $forums->admin->print_input_row("perpostattach", $group['perpostattach']) . ' (' . $forums->lang['convertvalue'] . ': ' . ($group['perpostattach'] == 0 ? $forums->lang['nolimit'] : fetch_number_format($group['perpostattach'] * 1024, true)) . ')'));
		if ($group['usergroupid'] != 2)
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canuseavatar'] . "</strong>", $forums->admin->print_yes_no_row("canuseavatar", $group['canuseavatar'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canpmattach'] . "</strong>", $forums->admin->print_yes_no_row("canpmattach", $group['canpmattach'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['groupattachnum'] . "</strong><div class='description'>" . $forums->lang['groupattachnumdesc'] . "</div>", $forums->admin->print_input_row("attachnum", $group['attachnum']) . ($group['attachnum'] == 0 ? ' (' . $forums->lang['nolimit'] . ')' : '')));
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['groupperms'], $forums->lang['grouppermsdesc']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canviewboard'] . "</strong><div class='description'>" . $forums->lang['canviewboarddesc'] . "</div>", $forums->admin->print_yes_no_row("canview", $group['canview'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canshowforum'] . "</strong><div class='description'>" . $forums->lang['canshowforumdesc'] . "</div>", $forums->admin->print_yes_no_row("canshow", $group['canshow'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canviewoffline'] . "</strong>", $forums->admin->print_yes_no_row("canviewoffline", $group['canviewoffline'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canviewmember'] . "</strong>", $forums->admin->print_yes_no_row("canviewmember", $group['canviewmember'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canviewothers'] . "</strong>", $forums->admin->print_yes_no_row("canviewothers", $group['canviewothers'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['candownload'] . "</strong>", $forums->admin->print_yes_no_row("candownload", $group['candownload'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansignature'] . "</strong>", $forums->admin->print_yes_no_row("cansignature", $group['cansignature'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansigimg'] . "</strong>", $forums->admin->print_yes_no_row("cansigimg", $group['cansigimg'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansearch'] . "</strong>", $forums->admin->print_yes_no_row("cansearch", $group['cansearch'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansearchpost'] . "</strong>", $forums->admin->print_yes_no_row("cansearchpost", $group['cansearchpost'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['searchflood'] . "</strong><div class='description'>" . $forums->lang['searchflooddesc'] . "</div>", $forums->admin->print_input_row("searchflood", $group['searchflood'])));
		list($limit, $flood) = explode(":", $group['emaillimit']);
		if ($group['usergroupid'] != 2)
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canemail'] . "</strong><div class='description'>" . $forums->lang['canemaildesc'] . "</div>", $forums->admin->print_yes_no_row("canemail", $group['canemail'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditprofile'] . "</strong>", $forums->admin->print_yes_no_row("caneditprofile", $group['caneditprofile'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pmsendmax'] . "</strong><div class='description'>" . $forums->lang['pmsendmaxdesc'] . "</div>", $forums->admin->print_input_row("pmsendmax", $group['pmsendmax'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pmquota'] . "</strong><div class='description'>" . $forums->lang['pmquotadesc'] . "</div>", $forums->admin->print_input_row("pmquota", $group['pmquota'])));
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['grouppostperms'], $forums->lang['grouppostpermsdesc']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canpostnew'] . "</strong>", $forums->admin->print_yes_no_row("canpostnew", $group['canpostnew'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canreplyown'] . "</strong>", $forums->admin->print_yes_no_row("canreplyown", $group['canreplyown'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canreplyothers'] . "</strong>", $forums->admin->print_yes_no_row("canreplyothers", $group['canreplyothers'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canuseflash'] . "</strong>", $forums->admin->print_yes_no_row("canuseflash", $group['canuseflash'])));
		if ($group['usergroupid'] != 2)
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cananonymous'] . "</strong>", $forums->admin->print_yes_no_row("cananonymous", $group['cananonymous'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canpostpoll'] . "</strong>", $forums->admin->print_yes_no_row("canpostpoll", $group['canpostpoll'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canvote'] . "</strong>", $forums->admin->print_yes_no_row("canvote", $group['canvote'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditpost'] . "</strong>", $forums->admin->print_yes_no_row("caneditpost", $group['caneditpost'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['edittimecut'] . "</strong><div class='description'>" . $forums->lang['edittimecutdesc'] . "</div>", $forums->admin->print_input_row("edittimecut", $group['edittimecut'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canappendedit'] . "</strong>", $forums->admin->print_yes_no_row("canappendedit", $group['canappendedit'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['candeletepost'] . "</strong>", $forums->admin->print_yes_no_row("candeletepost", $group['candeletepost'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canopenclose'] . "</strong>", $forums->admin->print_yes_no_row("canopenclose", $group['canopenclose'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditthread'] . "</strong>", $forums->admin->print_yes_no_row("caneditthread", $group['caneditthread'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['candeletethread'] . "</strong>", $forums->admin->print_yes_no_row("candeletethread", $group['candeletethread'])));
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['passflood'] . "</strong>", $forums->admin->print_yes_no_row("passflood", $group['passflood'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['passmoderate'] . "</strong>", $forums->admin->print_yes_no_row("passmoderate", $group['passmoderate'])));
		if ($group['usergroupid'] != 2)
		{
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canposthtml'] . "</strong>", $forums->admin->print_yes_no_row("canposthtml", $group['canposthtml'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['passbadword'] . "</strong>", $forums->admin->print_yes_no_row("passbadword", $group['canpostclosed'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canpostclosed'] . "</strong>", $forums->admin->print_yes_no_row("canpostclosed", $group['canpostclosed'])));
		}
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['grouprepperms'], $forums->lang['groupreppermsdesc']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canevaluation'] . "</strong>", $forums->admin->print_yes_no_row("canevaluation", $group['canevaluation'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canevalsameuser'] . "</strong>", $forums->admin->print_yes_no_row("canevalsameuser", $group['canevalsameuser'])));
		$forums->admin->print_table_footer();
		if ($group['usergroupid'] != 2)
		{
			$forums->admin->columns[] = array("&nbsp;" , "40%");
			$forums->admin->columns[] = array("&nbsp;" , "60%");
			$forums->admin->print_table_start($forums->lang['groupmodperms'], $forums->lang['groupmodpermsdesc']);
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['groupissupermod'] . "</strong>", $forums->admin->print_yes_no_row("supermod", $group['supermod'])));
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cancontrolpanel'] . "</strong>", $forums->admin->print_yes_no_row("cancontrolpanel", $group['cancontrolpanel'])));
			$forums->admin->print_table_footer();
		}

		$forums->admin->print_form_end_standalone($button);
		$forums->admin->print_cp_footer();
	}

	function mainform()
	{
		global $forums, $DB, $bboptions;
		$pagetitle = $forums->lang['manageusergroup'];
		$detail = $forums->lang['manageusergroupdesc'];
		$forums->admin->print_form_header(array(1 => array('do' , 'doreorder'),));
		$forums->admin->print_cp_header($pagetitle, $detail);
		$g_array = array();
		$forums->admin->columns[] = array($forums->lang['usergrouptitle'], "20%");
		$forums->admin->columns[] = array($forums->lang['usergrouppower'], "14%");
		$forums->admin->columns[] = array($forums->lang['canadmincp'], "13%");
		$forums->admin->columns[] = array($forums->lang['supermoderator'], "13%");
		$forums->admin->columns[] = array($forums->lang['groupusers'], "10%");
		$forums->admin->columns[] = array($forums->lang['order'], "15%");
		$forums->admin->columns[] = array($forums->lang['option'], "15%");
		$forums->admin->print_table_start($forums->lang['manageusergroup']);
		$result = $DB->query("SELECT g.usergroupid, g.cancontrolpanel, g.supermod, g.grouptitle, g.grouppower, g.opentag, g.closetag, COUNT(u.id) as count, g.displayorder
				FROM " . TABLE_PREFIX . "usergroup g
		         LEFT JOIN " . TABLE_PREFIX . "user u ON (u.usergroupid = g.usergroupid)
		         GROUP BY g.usergroupid ORDER BY g.displayorder");
		$i = 0;

		while ($r = $DB->fetch_array($result))
		{
			$i++;
			$del = '&nbsp;';
			$mod = '&nbsp;';
			$admin = '&nbsp;';
			if ($r['usergroupid'] > 7)
			{
				$del = "<a href='usergroup.php?{$forums->sessionurl}do=delete&amp;id=" . $r['usergroupid'] . "'>" . $forums->lang['delete'] . "</a>";
			}
			if ($r['cancontrolpanel'] == 1)
			{
				$admin = '<center><span style="color:red">' . $forums->lang['yes'] . '</span></center>';
			}
			if ($r['supermod'] == 1)
			{
				$mod = '<center><span style="color:red">' . $forums->lang['yes'] . '</span></center>';
			}
			$set_prms = "<a href='usergroup.php?{$forums->sessionurl}do=forumpermission&amp;id=" . $r['usergroupid'] . "'>" . $forums->lang['setperm'] . "</a>";
			if ($r['usergroupid'] != 2 AND $r['usergroupid'] != 5)
			{

				$total_linkage = "<a href='../memberlist.php?max_results=30&amp;filter={$r['usergroupid']}&amp;order=asc&amp;sortby=name&amp;pp=0' target='_blank' title='" . $forums->lang['userlist'] . "'>" . $r['opentag'] . $forums->lang[ $r['grouptitle'] ] . $r['closetag'] . "</a>";
			}
			else
			{
				$total_linkage = $r['opentag'] . $forums->lang[ $r['grouptitle'] ]. $r['closetag'];
			}
			$forums->admin->print_cells_row(array("<strong>$total_linkage</strong>" ,
					'<center>' . $r['grouppower'] . '</center>',
					$admin,
					$mod,
					'<center>' . $r['count'] . '</center>',
					'<center>' . $forums->admin->print_input_row('ug_' . $r['usergroupid'], $i, 'text', '', 1) . '</center>',
					"<center><a href='usergroup.php?{$forums->sessionurl}do=edit&amp;id=" . $r['usergroupid'] . "'>" . $forums->lang['edit'] . "</a> $del $set_prms</center>",
					));
			$g_array[] = array($r['usergroupid'], $forums->lang[ $r['grouptitle'] ]);
		}
		$forums->admin->print_form_submit($forums->lang['forumreorder']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do' , 'add'),));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['addnewusergroup']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['basicusergroup'] . "</strong>", $forums->admin->print_input_select_row("id", $g_array, 3)));
		$forums->admin->print_form_submit($forums->lang['addusergroup']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doreorder()
	{
		global $forums, $DB, $_INPUT;
		$ids = array();
		foreach ($_INPUT AS $key => $value)
		{
			if (preg_match("/^ug_(\d+)$/", $key, $match))
			{
				if ($_INPUT[$match[0]])
				{
					$ids[$match[1]] = intval($_INPUT[$match[0]]);
				}
			}
		}
		if (count($ids))
		{
			$orderssql = $ugids = '';
			foreach($ids as $usergroupid => $new_position)
			{
				if ($usergroupid > 0)
				{
					$orderssql .= " WHEN usergroupid = $usergroupid THEN $new_position";
					$ugids .= ",$usergroupid";
				}

				$DB->update(TABLE_PREFIX . 'usergroup',array('displayorder' => intval($new_position)), 'usergroupid=' . $usergroupid);
			}
			if (!empty($ugids))
			{
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "thread
					SET displayorder = CASE $orderssql ELSE 0 END
					WHERE tid IN (0$ugids)");
			}
		}
		$forums->func->recache('usergroup');
		$forums->func->standard_redirect("usergroup.php?" . $forums->sessionurl);
	}

	//将词条内容写入数据库
	function updatelanguage($langvar = array())
	{
		global $forums, $DB, $bboptions;
		if (empty($langvar))
		{
			return;
		}

		$lang = array();
		$file = ROOT_PATH . 'languages/' . $bboptions['language'] . '.php';
		@include $file;

		$lang = array_merge($lang, $langvar);
		require_once ROOT_PATH . "includes/adminfunctions_language.php";
		adminfunctions_language::writefile($file, $getlang['languageid'], $lang);
	}
}

$output = new usergroup();
$output->show();

?>