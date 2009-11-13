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
# $Id: profile.php 374 2007-11-14 19:18:36Z develop_tong $
# **************************************************************************#
define('THIS_SCRIPT', 'profile');
require_once('./global.php');

class profile
{
	var $showdelclew = false;
	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('profile');
		$forums->func->load_lang('post');//需要整理
		if ($_INPUT['do'] == 'delete')
		{
			$this->delete_mestatus();
			$showdelclew = $this->showdelclew;
			$clewinfo = $forums->lang['deldoingsuc'];
		}
		$id = intval($_INPUT['u']);
		if ($bbuserinfo['canviewmember'] != 1 AND $id != $bbuserinfo['id'])
		{
			$forums->func->standard_error("cannotviewthispage");
		}
		$info = array();
		if (empty($id))
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$user = $DB->query_first("SELECT up.*, u.*, s.inforum, s.inthread, e.loanamount
			FROM " . TABLE_PREFIX . "user u
				LEFT JOIN " . TABLE_PREFIX . "session s
					ON (s.userid = u.id)
				LEFT JOIN " . TABLE_PREFIX . "userextra e
					ON (e.id = u.id)
				LEFT JOIN " . TABLE_PREFIX . "userexpand up
					ON (up.id = u.id)
			WHERE u.id = " . $id);
		if (!$user)
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$info = $forums->func->fetch_user($user);
		$forumids = array('0');
		foreach($forums->forum->foruminfo as $i => $r)
		{
			if ($forums->func->fetch_permissions($r['canread'], 'canread') == true)
			{
				$forumids[] = $r['id'];
			}
		}
		$percent = 0;
		$allposts = $DB->query_first('SELECT SUM(post) as allposts
			FROM ' . TABLE_PREFIX . "forum
			WHERE parentid = '-1'");
		$allposts = $allposts['allposts'];

		if ($user['posts'] && $allposts)
		{
			$info['perdaypost'] = round($user['posts'] / (((TIMENOW - $user['joindate']) / 86400)), 1);
			$info['totalpercent'] = sprintf('%.2f', ($user['posts'] / $allposts * 100));
		}

		if ($info['perdaypost'] > $user['posts'])
		{
			$info['perdaypost'] = $user['posts'];
		}
		$info['allposts'] = $allposts;
		$info['lastactivity'] = $forums->func->get_date($user['lastactivity'], 1);
		//这里需要处理会员自定义扩展字段
		$timelimit = TIMENOW - $bboptions['cookietimeout'] * 60;
		$info['status'] = 0;
		$info['extra'] = "";
		$forums->lang['totalpercent'] = sprintf($forums->lang['totalpercent'], $info['perdaypost'], $info['totalpercent']);
		$forums->lang['userpercent'] = sprintf($forums->lang['userpercent'], $info['activeposts'], $info['percent']);
		$user['options'] = intval($user['options']);
		$forums->func->convert_bits_to_array($user, $user['options']);
		if (($user['lastvisit'] > $timelimit || $user['lastactivity'] > $timelimit) && $user['invisible'] != 1 && $user['loggedin'] == 1)
		{
			$info['status'] = 1;
			$where = "";
			if ($user['inthread'])
			{
				$thread = $DB->query_first("SELECT tid, title, forumid FROM " . TABLE_PREFIX . "thread WHERE tid='" . $user['inthread'] . "'");
				if ($thread['tid'])
				{
					if ($forums->func->fetch_permissions($forums->forum->foruminfo[$thread['forumid']]['canread'], 'canread') == true)
					{
						$where = "( " . $forums->lang['readthread'] . ": <a href='showthread.php{$forums->sessionurl}t=" . $thread['tid'] . "'>" . $thread['title'] . "</a> )";
					}
				}
			}
			else if ($user['inforum'])
			{
				if ($forums->func->fetch_permissions($forums->forum->foruminfo[$user['inforum']]['canread'], 'canread') == true)
				{
					$where = "( " . $forums->lang['viewforum'] . ": <a href='forumdisplay.php{$forums->sessionurl}f=" . $user['inforum'] . "'>" . $forums->forum->foruminfo[$user['inforum']]['name'] . "</a> )";
				}
			}
			$info['extra'] = $where;
		}
		$info['avatar'] = $forums->func->get_avatar($user['id'], $user['avatar']);
		require_once(ROOT_PATH . "includes/class_textparse.php");
		$info['signature'] = textparse::convert_text($user['signature'], $bboptions['signatureallowhtml']);
		$signature_path = split_todir($user['id'], $bboptions['uploadurl'] . '/user');
		$signature_path = $signature_path[0] . '/';
		$info['signature'] = str_replace('{$signature_path}', $signature_path, $info['signature']);
		$info['website'] = ($user['website'] AND preg_match("/^http:\/\/\S+$/", $user['website'])) ? "<a href='" . $user['website'] . "' target='_blank'>" . $user['website'] . "</a>" : $forums->lang['noinfo'];
		if ($user['birthday'])
		{
			$birthday = explode('-', $user['birthday']);
			$info['birthday'] = ($birthday[0] == '0000') ? ($birthday[1] . "-" . $birthday[2]) : ($birthday[0] . "-" . $birthday[1] . "-" . $birthday[2]);
		}
		else
		{
			$info['birthday'] = $forums->lang['noinfo'];
		}

		$info['pm'] = ($user['usepm']) ? "<a href='private.php{$forums->sessionurl}do=newpm&amp;u=" . $user['id'] . "'><strong>" . $forums->lang['sendpm'] . "</strong></a>" : $forums->lang['nousepm'];

		$info['email'] = (! $user['hideemail']) ? "<a href='sendmessage.php{$forums->sessionurl}do=mailmember&amp;u=" . $user['id'] . "'>" . $forums->lang['sendmail'] . "</a>" : $forums->lang['nouseemail'];

		$info['post'] = fetch_number_format($info['post']);

		//$creditlist = $forums->func->fetch_credit($user);
		if ($creditlist)
		{
			$credits = explode('<br />', $creditlist);
			foreach ($credits as $credit)
			{
				if ($credit) $expand_credit[] = explode(': ', $credit);
			}
		}
		$posthash = $forums->func->md5_check();
		if ($user['id'] == $bbuserinfo['id'])
		{
			$show['options'] = true;
		}

		/*好友信息*/
		$userfriends = array();
		$sql = 'SELECT pu.*, u.id, u.avatar, u.usercurdo, u.name, u.userdotime FROM ' . TABLE_PREFIX . 'pmuserlist pu
				LEFT JOIN ' . TABLE_PREFIX . 'user u
				ON u.id=pu.contactid
				WHERE userid = ' . $user['id'] . '
				ORDER BY pu.id DESC
				LIMIT 20';
		$DB->query($sql);
		while ($row = $DB->fetch_array())
		{
			$row['avatar'] = $forums->func->get_avatar($row['id'], $row['avatar'], 1);//获取用户头像
			$row['userdotime'] = $forums->func->get_date($row['userdotime']);
			if (!$row['usercurdo'])
			{
				$row['usercurdo'] = $forums->lang['notfilldowhat'];
			}
			$userfriends[$row['id']] = $row;
		}
		/*结束*/

		/*状态历程*/
		$firstpost = $_INPUT['pp'] ? intval($_INPUT['pp']) : 0;
		$showcondition = array();
		if ($_INPUT['ufriendsdo'])
		{
			$this->extra = '&amp;ufriendsdo=' . $_INPUT['ufriendsdo'];
			$showufriendsdo = true;
			if ($userfriends)
			{
				$showcondition[] = ' ud.userid IN (' . implode(',', array_keys($userfriends)) . ')';
			}
			else
			{
				$showcondition[] = ' ud.userid = 0';
			}

		}
		else
		{
			$showufriendsdo = false;
			$showcondition[] = ' ud.userid = ' . $user['id'];
		}
		$showcondition = ' WHERE ' . implode(',', $showcondition);
		$sql = 'SELECT count(*) AS count FROM ' . TABLE_PREFIX . 'userdo ud
				' . $showcondition;
		$sqlcount = $DB->query_first($sql);
		$perpage = 20;
		$pagenav = $forums->func->build_pagelinks(array(
			'totalpages' => $sqlcount['count'],
			'perpage' => $perpage,
			'curpage' => $firstpost,
			'pagelink' => "profile.php{$forums->sessionurl}u={$user['id']}{$this->extra}",
		));
		$userjourney = array();
		$sql = 'SELECT ud.*, u.name, u.avatar FROM ' . TABLE_PREFIX . 'userdo ud
				LEFT JOIN ' . TABLE_PREFIX . 'user u
					ON ud.userid = u.id
				' . $showcondition . '
				ORDER BY time DESC
				LIMIT ' . $firstpost . ',' . $perpage;
		$DB->query($sql);
		while ($row = $DB->fetch_array())
		{
			$row['time'] = $forums->func->get_date($row['time']);
			if ($row['touserid'])
			{
				$row['dowhat'] = '@<a href="./profile.php?u=' . $row['touserid'] . '">' . $row['tousername'] . '</a> ' . $row['dowhat'];
			}
			$row['avatar'] = $forums->func->get_avatar($row['userid'], $row['avatar'], '2');
			$userjourney[$row['did']] = $row;
		}
		/*结束*/

		$mxajax_register_functions = array('do_change_signature', 'delete_user_avatar'); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');
		add_head_element('js', ROOT_PATH . 'scripts/mxajax_user.js');
		//加载简洁编辑器
		load_editor_js('', 'quick');
		$pagetitle = $forums->lang['profile'] . " - " . $bboptions['bbtitle'];
		$nav = array($forums->lang['profile']);
		include $forums->func->load_template('profile');
		exit;
	}

	function delete_mestatus()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$doid = intval($_INPUT['doid']);
		$sql = 'SELECT userid FROM ' . TABLE_PREFIX . 'userdo WHERE did=' . $doid;
		$ret = $DB->query_first($sql);
		if (!$bbuserinfo['supermod'] && $ret['userid'] != $bbuserinfo['id'])
		{
			$forums->func->standard_error("cannotdeldoing");
		}
		if (!$ret)
		{
			$forums->func->standard_error("doingnotexist");
		}
		$DB->delete(TABLE_PREFIX . 'userdo', 'did=' . $doid);
		$sql = 'SELECT * FROM ' . TABLE_PREFIX . 'userdo
				WHERE userid=' . $bbuserinfo['id'] . '
					AND touserid=0
				ORDER BY time DESC';
		$lastdoing = $DB->query_first($sql);
		$DB->update(TABLE_PREFIX . 'user', array('usercurdo' => $lastdoing['dowhat'], 'userdotime' => $lastdoing['time']), 'id = ' . intval($bbuserinfo['id']));
		$this->showdelclew = true;
	}
}

$output = new profile();
$output->show();

?>