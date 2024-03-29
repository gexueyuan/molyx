<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
require ('./global.php');

class moderate
{
	function show()
	{
		global $forums, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditusers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		switch (input::get('do', ''))
		{
			case 'add':
				$this->add_one();
				break;
			case 'add_two':
				$this->add_two();
				break;
			case 'add_final':
				$this->mod_form('add');
				break;
			case 'doadd':
				$this->add_mod();
				break;
			case 'edit':
				$this->mod_form('edit');
				break;
			case 'doedit':
				$this->do_edit();
				break;
			case 'remove':
				$this->remove();
				break;
			default:
				$this->show_list();
				break;
		}
	}

	function remove()
	{
		global $forums, $DB;
		if (input::str('id') == '' OR ! $mod = $DB->queryFirst('SELECT username, userid, usergroupid, usergroupname, isgroup FROM ' . TABLE_PREFIX . 'moderator WHERE moderatorid=' . input::int('id')))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if ($mod['isgroup'])
		{
			$name = $forums->lang['usergroup'] . ': ' . $mod['usergroupname'];
			@unlink(ROOT_PATH . 'cache/cache/moderator_group_' . $mod['usergroupid'] . '.php');
		}
		else
		{
			$name = $mod['username'];
			$user = $DB->queryFirst('SELECT usergroupid FROM ' . TABLE_PREFIX . 'user WHERE id=' . $mod['userid']);
			if ($user['usergroupid'] == 7)
			{
				$mod_number = $DB->queryFirst('SELECT COUNT(*) as count FROM ' . TABLE_PREFIX . 'moderator WHERE userid=' . intval($mod['userid']));
				if ($mod_number == 1)
				{
					$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "user SET usergroupid=3 WHERE id=" . $mod['userid']);
				}
			}
		}
		$DB->queryUnbuffered("DELETE FROM " . TABLE_PREFIX . "moderator WHERE moderatorid=" . input::int('id') . "");		
		if ($DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "moderator WHERE userid=" . intval($mod['userid']))) 
		{
		}		
		else 
		{
			@unlink(ROOT_PATH . 'cache/cache/moderator_user_' . $mod['userid'] . '.php');
		}
		cache::update('moderator');
		$forums->admin->save_log($forums->lang['moderatedeleted'] . " '{$name}'");
		$forums->admin->redirect("moderate.php", $forums->lang['managemoderate'], $forums->lang['moderatedeleted']);
	}

	function do_edit()
	{
		global $forums, $DB;
		if (input::str('userid') == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$bantimelimit = input::int('bantimelimit')<0?0:input::int('bantimelimit');
		$mod = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "moderator WHERE moderatorid='" . input::int('u') . "'");
		$DB->update(TABLE_PREFIX . 'moderator', array(
			'forumid' => input::get('forumid', ''),
			'caneditposts' => input::get('caneditposts', ''),
			'caneditthreads' => input::get('caneditthreads', ''),
			'candeleteposts' => input::get('candeleteposts', ''),
			'candeletethreads' => input::get('candeletethreads', ''),
			'canviewips' => input::get('canviewips', ''),
			'canopenclose' => input::get('canopenclose', ''),
			'canremoveposts' => input::get('canremoveposts', ''),
			'canstickthread' => input::get('canstickthread', ''),
			'canqstickthread' => input::get('canqstickthread', ''),
			'cangstickthread' => input::get('cangstickthread', ''),
			'canmoderateposts' => input::get('canmoderateposts', ''),
			'canmanagethreads' => input::get('canmanagethreads', ''),
			'canmergethreads' => input::get('canmergethreads', ''),
			'cansplitthreads' => input::get('cansplitthreads', ''),
			'caneditusers' => input::get('caneditusers', ''),
			'caneditrule' => input::get('caneditrule', ''),
			'cansetst' => input::get('cansetst', ''),
			'canbanuser' => input::get('canbanuser', ''),
			'canquintessence' => input::get('canquintessence', ''),
			'canbanpost' => input::get('canbanpost', ''),
			'modcancommend' => input::get('modcancommend', ''),
			'bantimelimit' => $bantimelimit . input::get('bantimeunit', ''),
			'sendbanmsg' => input::get('sendbanmsg', ''),
		), 'moderatorid=' . input::int('u'));
		cache::update('moderator');
		$forums->admin->save_log($forums->lang['moderateedited'] . " '{$mod['username']}'");
		$forums->admin->redirect("moderate.php", $forums->lang['managemoderate'], $forums->lang['moderateedited']);
	}

	function add_mod()
	{
		global $forums, $DB;
		if (input::str('fid') == "")
		{
			$forums->admin->print_cp_error($forums->lang['selectmodforum']);
		}
		$bantimelimit = input::int('bantimelimit');
		$bantimelimit = $bantimelimit<0?0:$bantimelimit;
		$moderator = array('caneditposts' => input::get('caneditposts', ''),
			'caneditthreads' => input::get('caneditthreads', ''),
			'candeleteposts' => input::get('candeleteposts', ''),
			'candeletethreads' => input::get('candeletethreads', ''),
			'canviewips' => input::get('canviewips', ''),
			'canopenclose' => input::get('canopenclose', ''),
			'canremoveposts' => input::get('canremoveposts', ''),
			'canstickthread' => input::get('canstickthread', ''),
			'canqstickthread' => input::get('canqstickthread', ''),
			'cangstickthread' => input::get('cangstickthread', ''),
			'canmoderateposts' => input::get('canmoderateposts', ''),
			'canmanagethreads' => input::get('canmanagethreads', ''),
			'canmergethreads' => input::get('canmergethreads', ''),
			'cansplitthreads' => input::get('cansplitthreads', ''),
			'canquintessence' => input::get('canquintessence', ''),
			'caneditusers' => input::get('caneditusers', ''),
			'caneditrule' => input::get('caneditrule', ''),
			'cansetst' => input::get('cansetst', ''),
			'canbanuser' => input::get('canbanuser', ''),
			'canbanpost' => input::get('canbanpost', ''),
			'modcancommend' => input::get('modcancommend', ''),
			'bantimelimit' => $bantimelimit . input::get('bantimeunit', ''),
			'sendbanmsg' => input::get('sendbanmsg', '')
			);
		$forumids = array();
		$DB->query("SELECT id FROM " . TABLE_PREFIX . "forum WHERE id IN(" . input::get('fid', '') . ")");
		while ($i = $DB->fetch())
		{
			$forumids[ $i['id'] ] = $i['id'];
		}
		if (input::str('mtype') == 'group')
		{
			if (input::str('gid') == "")
			{
				$forums->admin->print_cp_error($forums->lang['groupnotmatch']);
			}
			if (! $group = $DB->queryFirst("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid='" . input::get('gid', '') . "'"))
			{
				$forums->admin->print_cp_error($forums->lang['groupnotmatch']);
			}
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "moderator WHERE forumid IN(" . input::get('fid', '') . ") AND usergroupid='" . input::get('gid', '') . "'");
			while ($f = $DB->fetch())
			{
				unset($forumids[ $f['forumid'] ]);
			}
			$moderator['username'] = '-1';
			$moderator['userid'] = '-1';
			$moderator['usergroupid'] = $group['usergroupid'];
			$moderator['usergroupname'] = $forums->lang[ $group['grouptitle'] ];
			$moderator['isgroup'] = 1;
			$forums->lang['addgroupmod'] = sprintf($forums->lang['addgroupmod'], $forums->lang[ $group['grouptitle'] ]);
			$ad_log = $forums->lang['addgroupmod'];
		}
		else
		{
			if (!input::int('userid'))
			{
				$forums->admin->print_cp_error($forums->lang['selectmoduser']);
			}
			if (! $user = $DB->queryFirst("SELECT id, name, usergroupid FROM " . TABLE_PREFIX . "user WHERE id='" . input::int('userid') . "'"))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "moderator WHERE forumid IN(" . input::get('fid', '') . ") AND userid='" . input::int('userid') . "'");
			while ($f = $DB->fetch())
			{
				unset($forumids[ $f['forumid'] ]);
			}
			if ($user['usergroupid'] != 4 && $user['usergroupid'] != 6 && $user['usergroupid'] != 7)
			{
				$DB->update(TABLE_PREFIX . 'user', array('usergroupid' => 7), "id = {$user['id']}");
			}
			$moderator['username'] = $user['name'];
			$moderator['userid'] = $user['id'];
			$moderator['isgroup'] = 0;
			$forums->lang['addusermod'] = sprintf($forums->lang['addusermod'], $user['name']);
			$ad_log = $forums->lang['addusermod'];
		}
		if (count($forumids) == 0)
		{
			$forums->admin->print_cp_error($forums->lang['noselectmod']);
		}
		foreach ($forumids AS $cartman)
		{
			$moderator['forumid'] = $cartman;
			$DB->insert(TABLE_PREFIX . 'moderator', $moderator);
		}
		$forums->admin->save_log($ad_log);
		cache::update('moderator');
		$forums->admin->redirect("moderate.php", $forums->lang['managemoderate'], $forums->lang['moderatoradded']);
	}

	function mod_form($type = 'add')
	{
		global $forums, $DB;
		$group = array();
		if ($type == 'add')
		{
			if (input::str('fid') == "")
			{
				$forums->admin->print_cp_error($forums->lang['selectmodforum']);
			}
			$mod = $this->get_default_prms();
			$names = array();
			$DB->query("SELECT name FROM " . TABLE_PREFIX . "forum WHERE id IN(" . input::get('fid', '') . ")");
			while ($r = $DB->fetch())
			{
				$names[] = $r['name'];
			}
			$thenames = implode(", ", $names);
			$button = $forums->lang['addmoderator'];
			$form_code = 'doadd';
			if (input::str('mtype') == 'group')
			{
				if (! $group = $DB->queryFirst("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid='" . input::get('mod_group', '') . "'"))
				{
					$forums->admin->print_cp_error($forums->lang['notfindmodgroup']);
				}
				$pagetitle = $forums->lang['addmodgroup'];
				$forums->lang['addmodgroupdesc'] = sprintf($forums->lang['addmodgroupdesc'], $forums->lang[ $group['grouptitle'] ], $thenames);
				$detail = $forums->lang['addmodgroupdesc'];
			}
			else
			{
				if (input::str('userid') == "")
				{
					$forums->admin->print_cp_error($forums->lang['noids']);
				}
				else
				{
					if (! $user = $DB->queryFirst("SELECT name, id FROM " . TABLE_PREFIX . "user WHERE id='" . input::int('userid') . "'"))
					{
						$forums->admin->print_cp_error($forums->lang['noids']);
					}
					$userid = $user['id'];
					$username = $user['name'];
				}
				$pagetitle = $forums->lang['addmoderator'];
				$forums->lang['addmoderatordesc'] = sprintf($forums->lang['addmoderatordesc'], $username, $thenames);
				$detail = $forums->lang['addmoderatordesc'];
			}
		}
		else
		{
			if (input::str('u') == "")
			{
				$forums->admin->print_cp_error($forums->lang['selectmoderator']);
			}
			$button = $forums->lang['editmoderator'];
			$form_code = "doedit";
			$pagetitle = $forums->lang['editmoderator'];
			$detail = $forums->lang['editmoderatordesc'];
			if (! $mod = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "moderator WHERE moderatorid='" . input::int('u') . "'"))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$userid = $mod['userid'];
			$username = $mod['username'];
		}
		$bantimelimit = $mod['bantimelimit'];
		$mod['bantimelimit'] = substr($mod['bantimelimit'], 0, strlen($mod['bantimelimit'])-1);
		$mod['bantimeunit'] = substr($bantimelimit, -1);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', $form_code),
				2 => array('u', $mod['moderatorid']),
				3 => array('fid', input::get('fid', '')),
				4 => array('userid', $userid),
				5 => array('mtype', input::get('mtype', '')),
				6 => array('gid', $group['usergroupid']),
				7 => array('gname', $forums->lang[ $group['grouptitle'] ]),
				));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['generalsetting']);
		if ($type == 'edit')
		{
			$forumlist = array();
			$DB->query("SELECT id, name FROM " . TABLE_PREFIX . "forum");
			while ($r = $DB->fetch())
			{
				$forumlist[] = array($r['id'], $r['name']);
			}
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['moderateforums'] . "</strong>", $forums->admin->print_input_select_row("forumid", $forumlist, $mod['forumid'])));
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditposts'] . "</strong>", $forums->admin->print_yes_no_row("caneditposts", $mod['caneditposts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditthreads'] . "</strong>", $forums->admin->print_yes_no_row("caneditthreads", $mod['caneditthreads'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['candeleteposts'] . "</strong>", $forums->admin->print_yes_no_row("candeleteposts", $mod['candeleteposts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['candeletethreads'] . "</strong>", $forums->admin->print_yes_no_row("candeletethreads", $mod['candeletethreads'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canquintessence'] . "</strong>", $forums->admin->print_yes_no_row("canquintessence", $mod['canquintessence'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canviewips'] . "</strong>", $forums->admin->print_yes_no_row("canviewips", $mod['canviewips'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canopenclose'] . "</strong>", $forums->admin->print_yes_no_row("canopenclose", $mod['canopenclose'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canremoveposts'] . "</strong>", $forums->admin->print_yes_no_row("canremoveposts", $mod['canremoveposts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canstickthread'] . "</strong>", $forums->admin->print_yes_no_row("canstickthread", $mod['canstickthread'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canqstickthread'] . "</strong>", $forums->admin->print_yes_no_row("canqstickthread", $mod['canqstickthread'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cangstickthread'] . "</strong>", $forums->admin->print_yes_no_row("cangstickthread", $mod['cangstickthread'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansplitthreads'] . "</strong>", $forums->admin->print_yes_no_row("cansplitthreads", $mod['cansplitthreads'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canmergethreads'] . "</strong>", $forums->admin->print_yes_no_row("canmergethreads", $mod['canmergethreads'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canmanagethreads'] . "</strong>", $forums->admin->print_yes_no_row("canmanagethreads", $mod['canmanagethreads'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canmoderateposts'] . "</strong>", $forums->admin->print_yes_no_row("canmoderateposts", $mod['canmoderateposts'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditusers'] . "</strong>", $forums->admin->print_yes_no_row("caneditusers", $mod['caneditusers'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['caneditrule'] . "</strong>", $forums->admin->print_yes_no_row("caneditrule", $mod['caneditrule'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['cansetst'] . "</strong>", $forums->admin->print_yes_no_row("cansetst", $mod['cansetst'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canbanuser'] . "</strong>", $forums->admin->print_yes_no_row("canbanuser", $mod['canbanuser'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['canbanpost'] . "</strong>", $forums->admin->print_yes_no_row("canbanpost", $mod['canbanpost'])));
		$forums->admin->print_cells_row(array("<strong>版主可以推荐主题</strong>", $forums->admin->print_yes_no_row("modcancommend", $mod['modcancommend'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['bantimelimit'] . "</strong><div class='description'>" . $forums->lang['bantimelimitdesc'] . "</div>", $forums->admin->print_input_row("bantimelimit", $mod['bantimelimit'], 'text', '', '5')."&nbsp;&nbsp;".
			$forums->admin->print_input_select_row("bantimeunit", array(array('d', $forums->lang['days']), array('h', $forums->lang['hours'])), $mod['bantimeunit'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['sendbanmsg'] . "</strong>", $forums->admin->print_yes_no_row("sendbanmsg", $mod['sendbanmsg'])));	
		$forums->admin->print_form_end($button);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function add_one()
	{
		global $forums, $DB;
		$fid = "";
		$forumids = array();
		if (count(input::str('add')))
		{
			foreach (input::get('add', '') AS $value)
			{
				if ($value)
				{
					$forumids[] = $value;
				}
			}
		}
		if (count($forumids) < 1)
		{
			$forums->admin->print_cp_error($forums->lang['mustselectforums']);
		}
		$fid = implode("," , $forumids);
		if (input::str('userid'))
		{
			input::set('fid', $fid);
			$this->mod_form();
			exit();
		}
		$pagetitle = $forums->lang['addmoderator'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add_two'),
				2 => array('fid', $fid),
				3 => array('mtype', input::get('mtype', '')),
				));
		$forums->admin->columns[] = array("&nbsp;", "40%");
		$forums->admin->columns[] = array("&nbsp;", "60%");
		if (input::str('mtype') == 'user')
		{
			$forums->admin->print_table_start($forums->lang['finduser']);
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['inputusername'] . "</strong>" ,
					$forums->admin->print_input_row("username")
					));
			$forums->admin->print_form_end($forums->lang['finduser']);
			$forums->admin->print_table_footer();
		}
		else
		{
			$user_group = array();
			$DB->query("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup ORDER BY grouptitle");
			while ($r = $DB->fetch())
			{
				$user_group[] = array($r['usergroupid'] , $forums->lang[ $r['grouptitle'] ]);
			}
			$forums->admin->print_table_start($forums->lang['selectmodgroup']);
			$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectusergroup'] . "</strong>", $forums->admin->print_input_select_row("mod_group", $user_group)));
			$forums->admin->print_form_end($forums->lang['addmodgroup']);
			$forums->admin->print_table_footer();
		}
		$forums->admin->print_cp_footer();
	}

	function add_two()
	{
		global $forums, $DB;
		if (input::str('mtype') == 'group')
		{
			$this->mod_form();
			exit();
		}
		if (input::str('username') == "")
		{
			$forums->admin->print_cp_error($forums->lang['selectmoduser']);
		}
		$DB->query("SELECT id, name FROM " . TABLE_PREFIX . "user WHERE LOWER(name) LIKE concat('" . strtolower(input::str('username')) . "','%') OR name LIKE concat('" . input::get('username', '') . "','%')");
		if (! $DB->numRows())
		{
			$forums->admin->print_cp_error($forums->lang['notfindmoduser']);
		}
		$form_array = array();
		while ($r = $DB->fetch())
		{
			$form_array[] = array($r['id'] , $r['name']);
		}
		$pagetitle = $forums->lang['addmoderator'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'add_final'),
				2 => array('fid', input::get('fid', '')),
				));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['finduser']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectmatchuser'] . "</strong>" ,
				$forums->admin->print_input_select_row("userid", $form_array)
				));
		$forums->admin->print_form_end($forums->lang['selectuser']);
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function show_list()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['managemoderate'];
		$detail = $forums->lang['managemoderatedesc'] . "<br />";
		$detail .= $forums->lang['onlinestatusdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do' , 'add')));
		if (input::str('type') == 'single')
		{
			echo "<input type='hidden' name='add[]' value='" . input::get('f', '') . "'>";
		}
		else
		{
			if (input::str('userid'))
			{
				$user = $DB->queryFirst("SELECT id,name FROM " . TABLE_PREFIX . "user WHERE id=" . input::int('userid') . "");
				echo "<input type='hidden' name='userid' value='" . $user['id'] . "'>";
				$forums->lang['setmodforum'] = sprintf($forums->lang['setmodforum'], $user['name']);
				$title = $forums->lang['setmodforum'];
			}
			else
			{
				$title = $forums->lang['modlist'];
			}
			$forums->admin->columns[] = array($forums->lang['add'], "5%");
			$forums->admin->columns[] = array($forums->lang['forumtitle'], "40%");
			$forums->admin->columns[] = array($forums->lang['moderator'], "55%");
			$forums->admin->print_table_start($title);
			$forums->adminforum->moderator = array();
			$DB->query("SELECT u.lastactivity,m.* FROM " . TABLE_PREFIX . "moderator m LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id=m.userid)");
			while ($r = $DB->fetch())
			{
				$forums->adminforum->moderator[] = $r;
			}
			$forums->adminforum->type = 'moderator';
			$forums->adminforum->show_all = 1;
			$forums->adminforum->forums_list_forums();
			if (input::str('userid'))
			{
				$forums->admin->print_form_submit($forums->lang['addmoderatortoforum']);
			}
			$forums->admin->print_table_footer();
		}
		if (!input::int('userid'))
		{
			$forums->admin->columns[] = array("&nbsp;" , "40%");
			$forums->admin->columns[] = array("&nbsp;" , "60%");
			$forums->admin->print_table_start($forums->lang['addmoderator']);
			$forums->admin->print_cells_single_row("<strong>" . $forums->lang['selectaddmodtype'] . ":</strong> &nbsp;" . $forums->admin->print_input_select_row("mtype",
					array(0 => array('user', $forums->lang['user']),
						1 => array('group', $forums->lang['usergroup'])
						)
					) , "center");
			$forums->admin->print_form_submit($forums->lang['addmoderatortoforum']);
			$forums->admin->print_table_footer();
		}
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}
	
	function get_default_prms($type = 'mod_default_prms')
	{
		global $DB;
		$prms = $DB->queryFirst('SELECT value FROM ' . TABLE_PREFIX . "setting WHERE varname='$type'");
		return unserialize($prms['value']);
	}
}

$output = new moderate();
$output->show();

?>