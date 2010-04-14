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

class pms
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['cansendpms'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('pms.php' , $forums->lang['managepms']);
		switch ($_INPUT['do'])
		{
			case 'pmlist':
				$this->pmlist();
				break;
			case 'newpm':
				$this->newpm('new');
				break;
			case 'edit':
				$this->newpm('edit');
				break;
			case 'sendpm':
				$this->sendpm();
				break;
			case 'remove':
				$this->remove();
				break;
			default:
				$this->pmlist();
				break;
		}
	}

	function remove()
	{
		global $forums, $DB, $_INPUT;
		if ($_INPUT['id'] == "")
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		if (!$pm = $DB->query_first("SELECT p.*, pt.* FROM " . TABLE_PREFIX . "pm p LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON (p.messageid=pt.pmtextid) WHERE p.pmid=" . $_INPUT['id'] . ""))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pm WHERE pmid=" . intval($pm['pmid']) . "");
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pmtext WHERE pmtextid=" . intval($pm['pmtextid']) . "");
		$forums->admin->save_log($forums->lang['deletedpms']);
		$forums->func->standard_redirect("pms.php?" . $forums->sessionurl);
		exit();
	}

	function pmlist()
	{
		global $forums, $DB;
		$pagetitle = $forums->lang['pmslist'];
		$detail = $forums->lang['pmslistdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array($forums->lang['title'], "40%");
		$forums->admin->columns[] = array($forums->lang['sendtime'], "20%");
		$forums->admin->columns[] = array($forums->lang['usergroup'], "20%");
		$forums->admin->columns[] = array($forums->lang['option'], "20%");
		$forums->admin->print_form_header(array(1 => array('do', 'newpm')));
		$forums->admin->print_table_start($forums->lang['pmslist']);
		$usergroups = $DB->query("SELECT usergroupid,grouptitle FROM " . TABLE_PREFIX . "usergroup");
		while ($usergroup = $DB->fetch_array($usergroups))
		{
			$groups[ $usergroup['usergroupid'] ] = $usergroup;
		}
		$DB->query("SELECT p.* FROM " . TABLE_PREFIX . "pm p WHERE p.usergroupid != 0 ORDER BY dateline");
		if ($DB->num_rows())
		{
			while ($r = $DB->fetch_array())
			{
				$sendgroup = explode(',', $r['usergroupid']);
				foreach ($sendgroup AS $ids)
				{
					if ($r['usergroupid'] == -1)
					{
						$grouptitle[] = $forums->lang['allmembers'];
					}
					$grouptitle[] =  $forums->lang[ $groups[ $ids ]['grouptitle'] ];
				}
				$editbutton = $forums->admin->print_button($forums->lang['edit'], "pms.php?{$forums->sessionurl}do=edit&amp;id=" . $r['pmid'], 'button');
				$deletebutton = $forums->admin->print_button($forums->lang['delete'], "pms.php?{$forums->sessionurl}do=remove&amp;id=" . $r['pmid'], 'button');
				$sendtime = $forums->admin->print_button($forums->lang['delete'], "pms.php?{$forums->sessionurl}do=remove&amp;id=" . $r['pmid'], 'button');
				$forums->admin->print_cells_row(array("<a href='pms.php?{$forums->sessionurl}do=edit&amp;id=" . $r['pmid'] . "'><strong>" . $r['title'] . "</strong></a>", $forums->func->get_date($r['dateline'] , 2), "<center>" . implode("<br />", $grouptitle) . "</center>", "<center>{$editbutton} {$deletebutton}</center>"));
				unset($grouptitle);
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['nopms'], 'center');
		}
		$forums->admin->print_form_submit($forums->lang['sendnewpms']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function newpm($type = 'edit')
	{
		global $forums, $DB, $_INPUT;
		if ($type == 'new')
		{
			$pagetitle = $forums->lang['sendnewpms'];
			$detail = $forums->lang['sendnewpmsdesc'];
		}
		else
		{
			if (!$pm = $DB->query_first("SELECT p.*, pt.* FROM " . TABLE_PREFIX . "pm p LEFT JOIN " . TABLE_PREFIX . "pmtext pt ON (p.messageid=pt.pmtextid) WHERE p.pmid=" . $_INPUT['id'] . ""))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			require_once(ROOT_PATH . 'includes/functions_codeparse.php');
			$lib = new functions_codeparse();
			$pm['message'] = $lib->unconvert($pm['message']);
			$pagetitle = $forums->lang['editpms'];
			$detail = $forums->lang['editpmsdesc'];
		}
		$user_group[] = array ('-1', $forums->lang['allmembers']);
		$DB->query("SELECT usergroupid, grouptitle FROM " . TABLE_PREFIX . "usergroup ORDER BY grouptitle");
		while ($r = $DB->fetch_array())
		{
			if ($r['usergroupid'] == 2 OR $r['usergroupid'] == 5)
			{
				continue;
			}
			$user_group[] = array($r['usergroupid'] ,  $forums->lang[ $r['grouptitle'] ]);
		}
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array("&nbsp;" , "30%");
		$forums->admin->columns[] = array("&nbsp;" , "70%");
		$forums->admin->print_form_header(array(1 => array('do' , 'sendpm'), 2 => array('id', $pm['pmid']), 3 => array('messageid', $pm['messageid'])));
		$forums->admin->print_table_start($pagetitle);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pmstitle'] . "</strong>", $forums->admin->print_input_row('title' , $pm['title'])));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pmsrecipients'] . "</strong><br /><span class='description'>" . $forums->lang['pmsrecipientsdesc'] . "</span>", $forums->admin->print_multiple_select_row("usergroupid[]", $user_group, ($pm['usergroupid'] ? explode(',', $pm['usergroupid']) : array('-1')))));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['pmscontent'] . "</strong>", $forums->admin->print_textarea_row('message', $pm['message'], "60", "10")));
		$forums->admin->print_form_submit($pagetitle);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function sendpm()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		require_once(ROOT_PATH . 'includes/functions_codeparse.php');
		$lib = new functions_codeparse();
		$title = trim($_INPUT['title']);
		$message = $lib->convert(array(
			'text' => convert_andstr($_POST['message']),
			'allowsmilies' => 0,
			'allowcode' => 1,
		));

		if ($title == '' OR $message == '' OR !count($_INPUT['usergroupid']))
		{
			$forums->admin->print_cp_error($forums->lang['inputallforms']);
		}
		$skip = false;
		foreach ($_INPUT['usergroupid'] as $k => $ids)
		{
			if ($ids == -1)
			{
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread = pmunread+1 WHERE id != 0");
				$skip = true;
			}
			$groupids[] = $ids;
		}
		if (!$skip)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET pmunread = pmunread+1 WHERE usergroupid IN (" . implode(',', $groupids) . ")");
		}
		if (!$_INPUT['id'])
		{
			$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pmtext
								(dateline, message, deletedcount, savedcount)
							VALUES
								(" . TIMENOW . ", '" . $DB->escape_string($message) . "', 0, 1)"
				);
			$pmtextid = $DB->insert_id();
			$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "pm
									(messageid, dateline, title, usergroupid,fromuserid)
								VALUES
									(" . $pmtextid . ", " . TIMENOW . ", '" . $title . "', '" . implode(',', $_INPUT['usergroupid']) . "', " . intval($bbuserinfo['id']) . ")"
				);
			$forums->admin->save_log($forums->lang['sendnewpms']);
			$forums->func->standard_redirect("pms.php?" . $forums->sessionurl);
		}
		else
		{
			$DB->update(TABLE_PREFIX . 'pm', array('title' => $title, 'usergroupid' => implode(',', $_INPUT['usergroupid'])), 'pmid = ' . intval($_INPUT['id']));
			$DB->update(TABLE_PREFIX . 'pmtext', array('message' => $message), 'pmtextid = ' . intval($_INPUT['messageid']));
			$forums->admin->save_log($forums->lang['editpms']);
			$forums->func->standard_redirect("pms.php?" . $forums->sessionurl);
		}
	}
}

$output = new pms();
$output->show();

?>