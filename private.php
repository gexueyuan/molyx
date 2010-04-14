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
define('THIS_SCRIPT', 'private');
require_once('./global.php');

class newprivate
{
	var $folderid = '';
	var $canupload = 0;
	var $user = array();
	var $pmselect = '';
	var $userid = 0;
	var $getpmid = 0;
	var $message = '';

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('usercp');
		$forums->func->load_lang('private');
		if (! $bbuserinfo['id'])
		{
			$forums->func->standard_error("notlogin");
		}
		$this->folderid = intval($_INPUT['folderid']);
		if (! $bbuserinfo['pmquota'])
		{
			$forums->func->standard_error("cannotusepm");
		}
		if (! $bbuserinfo['usepm'])
		{
			$forums->func->standard_error("pmclosed");
		}
		require_once(ROOT_PATH . 'includes/functions_private.php');
		$this->lib = new functions_private();
		$bbuserinfo['folder_links'] = "";
		$bbuserinfo['pmfolders'] = unserialize($bbuserinfo['pmfolders']);
		if (count($bbuserinfo['pmfolders']) < 2)
		{
			$bbuserinfo['pmfolders'] = array(-1 => array('pmcount' => 0, 'foldername' => $forums->lang['_outbox']), 0 => array('pmcount' => 0, 'foldername' => $forums->lang['_inbox']));
		}
		foreach($bbuserinfo['pmfolders'] AS $id => $data)
		{
			$this->pmselect .= "<option value='" . $id . "'>" . $data['foldername'] . "</option>";
		}
		switch ($_INPUT['do'])
		{
			case 'list':
				$this->pmlist();
				break;
			case 'editfolders':
				$this->editfolders();
				break;
			case 'savefolders':
				$this->savefolders();
				break;
			case 'empty':
				$this->emptyfolders();
				break;
			case 'doempty':
				$this->doempty();
				break;
			case 'buddy':
				$this->buddylist();
				break;
			case 'showpm':
				$this->showpm();
				break;
			case 'adduser':
				$this->adduser();
				break;
			case 'deleteuser':
				$this->deleteuser();
				break;
			case 'edituser':
				$this->edituser();
				break;
			case 'douseredit':
				$this->douseredit();
				break;
			case 'showtrack':
				$this->showtracking();
				break;
			case 'endtracking':
				$this->endtracking();
				break;
			case 'deltracked':
				$this->deltracked();
				break;
			case 'newpm':
				$this->lib->newpm();
				break;
			case 'sendpm':
				$this->lib->sendpm();
				break;
			case 'managepm':
				$this->managepm();
				break;
			case 'ignorepm':
				$this->ignorepm();
				break;
			case 'pmdelete':
				$this->pmdelete();
				break;
			default:
				$this->pmlist();
				break;
		}
	}

	function pmlist()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$sortby = "";
		switch ($_INPUT['sort'])
		{
			case 'rdate':
				$sortby = 'dateline ASC';
				break;
			case 'title':
				$sortby = 'title ASC';
				break;
			case 'name':
				$sortby = 'u.name ASC';
				break;
			default:
				$sortby = 'dateline DESC';
				break;
		}
		$total = $DB->query_first("SELECT COUNT(*) as pmtotal FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . "");
		$curfolderid = $this->folderid;
		if ($total['pmtotal'] != $bbuserinfo['pmtotal'])
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmtotal=" . $total['pmtotal'] . " WHERE id=" . $bbuserinfo['id'] . "");
		}
		$currenttotal = $DB->query_first("SELECT COUNT(*) as pmtotal FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . " AND folderid=" . intval($_INPUT['folderid']) . "");
		if ($currenttotal['pmtotal'] != intval($bbuserinfo['pmfolders'][$curfolderid]['pmcount']))
		{
			$this->lib->rebuild_foldercount($bbuserinfo['id'], $bbuserinfo['pmfolders'], $this->folderid, intval($currenttotal['pmtotal']));
		}
		$info['pmfull'] = "<br />";
		$info['img_width'] = 1;
		$info['folderid'] = $this->folderid;
		$info['date_order'] = $sortby == 'dateline DESC' ? 'rdate' : '';
		$info['totalpercent'] = $total['pmtotal'] ? sprintf("%.0f", (($total['pmtotal'] / $bbuserinfo['pmquota']) * 100)) : 0;
		$info['img_width'] = $info['totalpercent'] > 0 ? intval($info['totalpercent']) * 2.7 : 1;
		if ($info['img_width'] > 300)
		{
			$info['img_width'] = 300;
		}
		if ($total['pmtotal'] >= $bbuserinfo['pmquota'])
		{
			$info['pmfull'] = $forums->lang['post']['pmfull'];
		}
		else
		{
			$forums->lang['pmused'] = sprintf($forums->lang['pmused'], $info['totalpercent'], $total['pmtotal'], $bbuserinfo['pmquota']);
			$info['pmfull'] = $forums->lang['pmused'];
		}
		$pp = $_INPUT['pp'] > 0 ? intval($_INPUT['pp']) : 0;
		$pages = $forums->func->build_pagelinks(array('totalpages' => $currenttotal['pmtotal'],
				'perpage' => 30,
				'curpage' => $pp,
				'pagelink' => "private.php{$forums->sessionurl}do=list&amp;folderid=" . $this->folderid . "&amp;sort=" . $_INPUT['sort'])
			);
		$foldername = $bbuserinfo['pmfolders'][$this->folderid]['foldername'];
		$pmselect = $this->pmselect;
		$sender = $forums->lang['post']['sender'];
		$adminpms = $DB->query("SELECT p.* FROM " . TABLE_PREFIX . "pm p WHERE p.userid='' AND p.usergroupid!='' ORDER BY dateline");
		if ($DB->num_rows($pms))
		{
			$show['message'] = true;
			while ($adminpm = $DB->fetch_array($pms))
			{
				if (preg_match("/," . $bbuserinfo['usergroupid'] . ",/i", "," . $adminpm['usergroupid'] . ",") OR $adminpm['usergroupid'] == '-1')
				{
					$adminpm['icon'] = "sysnew";
					$adminpm['date'] = $forums->func->get_date($adminpm['dateline'] , 2);
					$adminpm['fromusername'] = $forums->lang['fromadmin'];
					$adminpm['fromadmin'] = '<strong>' . $forums->lang['_systeminfo'] . '</strong>: ';
					$adminpm['title'] = '<strong>' . $adminpm['title'] . '</strong>: ';
					$pmlist[] = $adminpm;
				}
			}
		}
		if ($this->folderid == '-1')
		{
			$sender = $forums->lang['incepter'];
			$pms = $DB->query("SELECT u.name as fromusername, u.id as from_id, p.*
						 FROM " . TABLE_PREFIX . "pm p
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( p.touserid=u.id )
						WHERE p.userid=" . $bbuserinfo['id'] . " AND p.folderid='-1'
						ORDER BY " . $sortby . " LIMIT " . $pp . ", 30");
		}
		else
		{
			$sender = $forums->lang['sender'];
			if ($this->folderid > 0)
			{
				$where = " AND p.touserid='" . $bbuserinfo['id'] . "' ";
			}
			$pms = $DB->query("SELECT p.*,u.name as fromusername, u.id as from_id
						 FROM " . TABLE_PREFIX . "pm p
						 LEFT JOIN " . TABLE_PREFIX . "user u ON ( p.fromuserid=u.id )
						WHERE p.userid='" . $bbuserinfo['id'] . "' AND p.folderid='" . $this->folderid . "'
							$where
						ORDER BY " . $sortby . " LIMIT " . $pp . ", 30");
		}
		if ($DB->num_rows($pms))
		{
			$show['message'] = true;
			while ($row = $DB->fetch_array($pms))
			{
				if ($row['attach'])
				{
					$row['attach_img'] = 1;
				}
				$row['icon'] = ($row['pmread'] == 1) ? 'old' : 'new';
				$row['date'] = $forums->func->get_date($row['dateline'] , 2);
				$pmlist[] = $row;
			}
		}
		if ($this->folderid == 0 AND $bbuserinfo['pmunread'] > 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['view'] . $foldername . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_pmlist');
		exit;
	}

	function ignorepm()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect($forums->url);
	}

	function emptyfolders()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$allfolders = $bbuserinfo['pmfolders'];
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['emptyfolder'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_emptyfolders');
		exit;
	}

	function doempty()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$folders = array();
		foreach($bbuserinfo['pmfolders'] AS $folderid => $data)
		{
			if ($_POST['folderids'][$folderid] == 1)
			{
				$ids[] = $folderid;
				$bbuserinfo['pmfolders'][$folderid]['pmcount'] = 0;
			}
		}
		if (!count($ids))
		{
			$forums->func->standard_error("selectemptyfloder");
		}
		$pmids = array();
		$DB->query("SELECT pmid FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . " AND folderid IN('" . implode("','", $ids) . "')");
		while ($d = $DB->fetch_array())
		{
			$pmids[] = $d['pmid'];
		}
		$this->delete_messages($pmids, $bbuserinfo['id']);
		$total = $DB->query_first("SELECT COUNT(*) as pmtotal FROM " . TABLE_PREFIX . "pm WHERE userid=" . $bbuserinfo['id'] . "");
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET pmtotal='" . $total['pmtotal'] . "', pmfolders='" . addslashes(serialize($bbuserinfo['pmfolders'])) . "' WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=empty");
	}

	function editfolders()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		ksort ($bbuserinfo['pmfolders']);
		foreach($bbuserinfo['pmfolders'] AS $id => $data)
		{
			if ($id == '0' OR $id == '-1')
			{
				$bbuserinfo['pmfolders'][$id]['extra'] = "&nbsp;|&nbsp;" . $forums->lang['cannotdelete'];
			}
			$bbuserinfo['pmfolders'][$id]['pmcount'] = intval($bbuserinfo['pmfolders'][$id]['pmcount']);
			$count = intval($id) + 1;
		}
		$curfolders = $bbuserinfo['pmfolders'];
		$newfolder = "";
		for ($i = $count, $n = $count + 3; $i < $n; $i++)
		{
			$newfolder .= "<li><input type='text' name='folder[" . $i . "]' value='' class='input_normal' title='Folder id: " . $i . "' /></li>\n";
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['editfolder'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_editfolders');
		exit;
	}

	function savefolders()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		if ($_POST['folder'][-1] == "" OR $_POST['folder'][0] == "")
		{
			$forums->func->standard_error("cannotdeldefault");
		}
		foreach ($_INPUT['folder'] AS $folderid => $foldername)
		{
			$folderid = intval($folderid);
			$foldername = utf8_htmlspecialchars(trim($foldername));
			if ($foldername != '')
			{
				$pmfolders[$folderid] = array('pmcount' => $bbuserinfo['pmfolders'][$folderid]['pmcount'] ? $bbuserinfo['pmfolders'][$folderid]['pmcount'] : 0, 'foldername' => $foldername);
			}
			else if (isset($bbuserinfo['pmfolders'][$folderid]))
			{
				$updatefolders[] = $folderid;
			}
		}
		if (!empty($updatefolders))
		{
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "pm SET folderid=0 WHERE userid=$bbuserinfo[id] AND folderid IN(" . implode(', ', $updatefolders) . ")");
		}
		ksort($pmfolders);
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "user SET pmfolders='" . addslashes(serialize($pmfolders)) . "' WHERE id=" . $bbuserinfo['id'] . "");
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=editfolders");
	}

	function buddylist()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "pmuserlist WHERE userid=" . $bbuserinfo['id'] . " ORDER BY contactname ASC");
		$contacts = array();
		if ($DB->num_rows())
		{
			$show['userlist'] = true;
			while ($row = $DB->fetch_array())
			{
				$row['text'] = $row['allowpm'] ? $forums->lang['allowpm'] : $forums->lang['forbidpm'];
				$contacts[] = $row;
			}
		}
		$username = "";
		if (isset($_INPUT['u']))
		{
			if (preg_match("/^(\d+)$/", $_INPUT['u']))
			{
				$user = $DB->query_first("SELECT name,id FROM " . TABLE_PREFIX . "user WHERE id=" . $_INPUT['u'] . "");
				if ($user['id'])
				{
					$username = $user['name'];
				}
			}
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['editbuddy'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_buddylist');
		exit;
	}

	function adduser()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$username = trim($_INPUT['username']);
		if (!$username)
		{
			$forums->func->standard_error("plzinputallform");
		}
		$user = $DB->query_first("SELECT name, id FROM " . TABLE_PREFIX . "user WHERE LOWER(name)='" . strtolower($username) . "'");
		if (! $user['id'])
		{
			$forums->func->standard_error("cannotfindadduser");
		}
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "pmuserlist WHERE userid=" . $bbuserinfo['id'] . " AND contactid=" . $user['id'] . "");
		if ($DB->num_rows())
		{
			$forums->func->standard_error("alreadyadd");
		}
		$_INPUT['allowpm'] = $_INPUT['allowpm'] ? $_INPUT['allowpm'] : 0;
		$DB->shutdown_query("INSERT INTO " . TABLE_PREFIX . "pmuserlist
								(userid, contactname, allowpm, description, contactid)
							VALUES
								(" . $bbuserinfo['id'] . ", '" . $user['name'] . "', " . $_INPUT['allowpm'] . ", '" . $_INPUT['description'] . "', " . $user['id'] . ")"
			);
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=buddy");
	}

	function edituser()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$_INPUT['u'] = intval($_INPUT['u']);
		if (! $_INPUT['u'])
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		if (!$user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "pmuserlist WHERE userid=" . $bbuserinfo['id'] . " AND contactid=" . $_INPUT['u'] . ""))
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$allowpm = "<select name='allowpm' class='select_normal'>";
		$allowpm .= $user['allowpm'] ? "<option value='1' selected='selected'>" . $forums->lang['_yes'] . "</option>\n<option value='0'>" . $forums->lang['_no'] . "</option>" : "<option value='1'>" . $forums->lang['_yes'] . "</option>\n<option value='0' selected>" . $forums->lang['_no'] . "</option>" ;
		$allowpm .= "</select>\n";

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['edituser'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_buddyedit');
		exit;
	}

	function douseredit()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$_INPUT['u'] = intval($_INPUT['u']);
		if (! $_INPUT['u'])
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$user = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "pmuserlist WHERE userid=" . $bbuserinfo['id'] . " AND contactid=" . $_INPUT['u'] . "");
		if (!$user['contactid'])
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "pmuserlist SET description='" . $_INPUT['description'] . "', allowpm=" . intval($_INPUT['allowpm']) . " WHERE id=" . $user['id'] . "");
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=buddy");
	}

	function deleteuser()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$_INPUT['u'] = intval($_INPUT['u']);
		if (! $_INPUT['u'])
		{
			$forums->func->standard_error("cannotfindedituser");
		}
		$DB->shutdown_query("DELETE FROM " . TABLE_PREFIX . "pmuserlist WHERE userid=" . $bbuserinfo['id'] . " AND contactid=" . $_INPUT['u'] . "");
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=buddy");
	}

	function showtracking()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$DB->query("SELECT pt.*, p.*, u.name as to_name, u.id as userid
				 FROM " . TABLE_PREFIX . "pm p
				  LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON ( p.messageid=pt.pmtextid )
				  LEFT JOIN " . TABLE_PREFIX . "user u ON (p.touserid=u.id)
				WHERE p.fromuserid='" . $bbuserinfo['id'] . "' AND p.tracking=1");
		$read = array();
		$unread = array();
		while ($r = $DB->fetch_array())
		{
			if ($r['pmread'])
			{
				$show['read'] = true;
				$r['date'] = $forums->func->get_date($r['pmreadtime'] , 2);
				$read[] = $r;
			}
			else
			{
				$show['unread'] = true;
				$r['date'] = $forums->func->get_date($r['dateline'] , 2);
				$unread[] = $r;
			}
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['pm'] . " - " . $forums->lang['pmtrack'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", $forums->lang['pm']);
		include $forums->func->load_template('pm_pmtracker');
		exit;
	}

	function showpm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$_INPUT['pmid'] = intval($_INPUT['pmid']);
		if (! $_INPUT['pmid'])
		{
			$forums->func->standard_error("cannotfindpm");
		}
		$whatuserid = ($this->folderid == '-1') ? 'touserid' : 'fromuserid';
		$DB->query("SELECT u.*, g.grouptitle, g.groupicon, g.canposthtml, p.*, pt.*
			FROM " . TABLE_PREFIX . "pm p
				LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON (p.messageid=pt.pmtextid)
				LEFT JOIN " . TABLE_PREFIX . "user u ON (p.$whatuserid=u.id)
				LEFT JOIN " . TABLE_PREFIX . "usergroup g ON (g.usergroupid=u.usergroupid)
				WHERE p.pmid='" . $_INPUT['pmid'] . "'");
		if ($pm = $DB->fetch_array())
		{
			if ($pm['userid'] != $bbuserinfo['id'] && $pm['usergroupid'] != -1 && !preg_match("/," . $bbuserinfo['usergroupid'] . ",/i", "," . $pm['usergroupid'] . ","))
			{
				$forums->func->standard_error("cannotfindpm");
			}
		}
		else
		{
			$forums->func->standard_error("cannotfindpm");
		}
		if ($bbuserinfo['pmunread'] > 0)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread=0 WHERE id=" . $bbuserinfo['id'] . "");
		}
		if ($pm['pmread'] < 1)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "pm SET pmread=1, pmreadtime=" . TIMENOW . " WHERE pmid=" . intval($_INPUT['pmid']) . "");
		}
		$pm['dateline'] = $forums->func->get_date($pm['dateline'], 2);
		$user = $forums->func->fetch_user($pm);
		$user['grouptitle'] = $forums->lang[$pm['grouptitle']];

		require_once(ROOT_PATH . 'includes/class_textparse.php');
		$pm['message'] = textparse::convert_text($pm['message'], $bboptions['pmallowhtml']);

		//处理引用
		if (strpos($pm['message'], '[quote') !== false)
		{
			require_once(ROOT_PATH . 'includes/functions_codeparse.php');
			$codeparse = new functions_codeparse();
			$pm['message'] = preg_replace("#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$codeparse->parse_quotes('\\1')" , $pm['message']);
		}

		if ($pm['attach'])
		{
			require_once(ROOT_PATH . 'includes/functions_showthread.php');
			$attach = new functions_showthread();
			$attachments = $attach->parse_attachment(array($pm['pmtextid']), 'pmid');
			$attachment = $attachments['attachments'][$pm['pmtextid']];
		}

		//加载ajax
		$mxajax_register_functions = array('delete_user_avatar'); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		$pagetitle = $forums->lang['view'] . $forums->lang['pm'] . ": " . $pm['title'] . " - " . $forums->lang['usercp'];
		$nav = array("<a href='usercp.php{$forums->sessionurl}'>" . $forums->lang['usercp'] . "</a>", "<a href='private.php{$forums->sessionurl}do=list&amp;folderid=" . $this->folderid . "'>" . $bbuserinfo['pmfolders'][$_INPUT['folderid']]['foldername'] . "</a>", $forums->lang['view'] . $forums->lang['pm'] . ": " . $pm['title']);
		include $forums->func->load_template('pm_showpm');
		exit;
	}

	function deltracked()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$ids = array();
		if (is_array($_INPUT['pmid']))
		{
			foreach ($_INPUT['pmid'] AS $key)
			{
				$key = intval($key);
				if (!$key)
				{
					continue;
				}
				$ids[] = $key;
			}
		}
		if (count($ids) > 0)
		{
			$this->delete_messages($ids, $bbuserinfo['id'], "pmread=0 AND tracking=1 AND fromuserid=" . $bbuserinfo['id']);
			$forums->func->standard_redirect("private.php{$forums->sessionurl}do=showtrack");
		}
		else
		{
			$forums->func->standard_error("nodelpms");
		}
	}

	function delete_messages($ids, $userid, $query = "")
	{
		global $DB, $forums, $bboptions;
		if (! $query)
		{
			$query = "p.userid=$userid";
		}
		$id_string = "";
		if (is_array($ids))
		{
			if (! count($ids))
			{
				return;
			}
			$id_string = 'IN (' . implode(",", $ids) . ')';
		}
		else
		{
			if (! $ids)
			{
				return;
			}
			$id_string = '=' . $ids;
		}
		$pms = $DB->query("SELECT p.pmid, p.touserid, p.folderid, p.messageid, p.usergroupid, p.userid, u.pmtotal, u.pmunread
			FROM " . TABLE_PREFIX . "pm p
			LEFT JOIN " . TABLE_PREFIX . "user u ON (u.id = p.userid)
			WHERE " . $query . " AND p.pmid " . $id_string . "");
		$final_ids = array();
		$final_pms = array();
		while ($i = $DB->fetch_array($pms))
		{
			if ($i['usergroupid'] != 0) continue;
			$extra = "";
			if ($i['pmtotal'] > 0)
			{
				$extra .= ",pmtotal=pmtotal-1";
			}
			if ($i['pmunread'] > 0)
			{
				$extra .= ",pmunread=pmunread-1";
			}
			$this->lib->rebuild_foldercount($i['userid'], '', $i['folderid'], '-2', 'save', $extra);
			$final_ids[ $i['pmid'] ] = $i['messageid'];
			$final_pms[] = $i['pmid'];
		}
		if (count($final_pms))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid IN (" . implode(',', $final_pms) . ")");
		}
		if (count($final_ids))
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "pmtext SET deletedcount=deletedcount+1 WHERE pmtextid IN (" . implode(',', $final_ids) . ")");
		}
		$deleted_ids = array();
		$attachmentids = array();
		$DB->query("SELECT pmtextid FROM " . TABLE_PREFIX . "pmtext WHERE deletedcount >= savedcount");
		while ($r = $DB->fetch_array())
		{
			$deleted_ids[] = $r['pmtextid'];
		}
		if (count($deleted_ids))
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pmtext WHERE pmtextid IN (" . implode(',', $deleted_ids) . ")");
			$DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE pmid IN (" . implode(',', $deleted_ids) . ")");
			while ($a = $DB->fetch_array())
			{
				$attachmentids[] = $a['attachmentid'];
				if ($a['location'])
				{
					@unlink($bboptions['uploadfolder'] . "/" . $a['attachpath'] . "/" . $a['location']);
				}
				if ($a['thumblocation'])
				{
					@unlink($bboptions['uploadfolder'] . "/" . $a['attachpath'] . "/" . $a['thumblocation']);
				}
			}
			if (count($attachmentids))
			{
				$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE attachmentid IN (" . implode(',', $attachmentids) . ")");
			}
		}
	}

	function managepm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$ids = array();
		if (is_array($_INPUT['pmid']))
		{
			foreach ($_INPUT['pmid'] AS $value)
			{
				$value = intval($value);
				if (!$value)
				{
					continue;
				}
				$ids[] = $value;
			}
		}
		$affected_ids = count($ids);
		if ($affected_ids > 0)
		{
			$id_string = implode(",", $ids);
			if ($_INPUT['delete'])
			{
				$_INPUT['curfolderid'] = intval($_INPUT['curfolderid']);
				$this->delete_messages($ids, $bbuserinfo['id']);
				$forums->func->standard_redirect("private.php{$forums->sessionurl}do=list&amp;folderid=" . intval($_INPUT['curfolderid']) . "");
			}
			else if ($_INPUT['move'])
			{
				if (intval($_INPUT['curfolderid'] != $this->folderid))
				{
					$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "pm SET folderid='" . $this->folderid . "' WHERE folderid != '" . $this->folderid . "' AND userid=" . $bbuserinfo['id'] . " AND pmid IN (" . $id_string . ")");
					if ($DB->affected_rows())
					{
						$returnpmfolders = $this->lib->rebuild_foldercount($bbuserinfo['id'],
							$bbuserinfo['pmfolders'],
							$_INPUT['curfolderid'],
							$bbuserinfo['pmfolders'][ $_INPUT['curfolderid'] ]['pmcount'] - $affected_ids,
							'nosave'
							);
						$returnpmfolders = unserialize($returnpmfolders);
						$this->lib->rebuild_foldercount($bbuserinfo['id'],
							$returnpmfolders,
							$this->folderid,
							$returnpmfolders[ $this->folderid ]['pmcount'] + $affected_ids,
							'save'
							);
					}
				}
				$forums->func->standard_redirect("private.php{$forums->sessionurl}do=list&amp;folderid=" . $this->folderid . "");
			}
			else
			{
				$forums->func->standard_error("noaction");
			}
		}
		else
		{
			$forums->func->standard_error("selectpmaction");
		}
	}

	function endtracking()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$ids = array();
		if (is_array($_INPUT['pmid']))
		{
			foreach ($_INPUT['pmid'] AS $value)
			{
				$value = intval($value);
				if (!$value)
				{
					continue;
				}
				$ids[] = $value;
			}
		}
		$affected_ids = count($ids);
		if ($affected_ids > 0)
		{
			$id_string = implode(",", $ids);
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "pm SET tracking=0 WHERE tracking=1 AND pmread=1 AND fromuserid=" . $bbuserinfo['id'] . " AND pmid IN (" . $id_string . ")");
			$forums->func->standard_redirect("private.php{$forums->sessionurl}do=showtrack");
		}
		else
		{
			$forums->func->standard_error("selectendtrack");
		}
	}

	function pmdelete()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$_INPUT['pmid'] = intval($_INPUT['pmid']);
		if (! $_INPUT['pmid'])
		{
			$forums->func->standard_error("nodelpms");
		}
		$this->delete_messages($_INPUT['pmid'], $bbuserinfo['id']);
		$forums->func->standard_redirect("private.php{$forums->sessionurl}do=list&amp;folderid=" . $this->folderid . "");
	}
}

$output = new newprivate();
$output->show();

?>