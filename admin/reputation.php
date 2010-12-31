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

class reputation
{
	function show()
	{
		global $forums, $bbuserinfo;
		$forums->admin->nav[] = array('reputation.php' , $forums->lang['managereputation']);
		switch (input::get('do', ''))
		{
			case 'list':
				$this->replist();
				break;
			case 'reset':
				$this->resetrep();
				break;
			default:
				$this->replist();
				break;
		}
	}

	function replist()
	{
		global $forums, $DB;
		$pp = input::get('pp', '') ? input::get('pp', '') : 0;
		if (input::str('key')) $query = "AND " . input::get('type', '') . "='" . input::get('key', '') . "'";
		$pagetitle = $forums->lang['reputationlist'];
		$detail = $forums->lang['reputationlistdesc'];
		cache::update('splittable');
		cache::get('splittable');
		$deftable = $forums->cache['splittable']['default'];
		$posttable = $deftable['name']?$deftable['name']:'post';
		$row = $DB->queryFirst("SELECT COUNT(pid) as count FROM " . TABLE_PREFIX . "$posttable WHERE reppost!=''" . $query . "");
		$row_count = $row['count'];
		$links = $forums->func->build_pagelinks(array('totalpages' => $row_count,
				'perpage' => 20,
				'curpage' => $pp,
				'pagelink' => "reputation.php?{$forums->sessionurl}do=list&amp;key=" . input::get('key', '') . "&amp;type=" . input::get('type', '') . "",
				)
			);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array($forums->lang['postid'], "5%");
		$forums->admin->columns[] = array($forums->lang['inthread'], "40%");
		$forums->admin->columns[] = array($forums->lang['torepuser'], "10%");
		$forums->admin->columns[] = array($forums->lang['repuser'], "10%");
		$forums->admin->columns[] = array($forums->lang['repnum'], "5%");
		$forums->admin->columns[] = array($forums->lang['option'], "20%");
		$forums->admin->print_form_header();
		$forums->admin->print_table_start($forums->lang['reputationlist']);
		$reputations = $DB->query("SELECT p.pid,p.pagetext,p.reppost,p.threadid,u.id,u.name,t.title 
				FROM " . TABLE_PREFIX . "$posttable p 
			LEFT JOIN " . TABLE_PREFIX . "user u ON (p.userid=u.id)
			LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid=t.tid) 
				WHERE reppost!=''" . $query . " ORDER BY p.pid DESC LIMIT " . $pp . ", 20");
		if ($DB->numRows($reputations))
		{
			while ($reputation = $DB->fetch($reputations))
			{
				$rr = unserialize($reputation['reppost']);
				$repnumber = intval($rr['number']);
				$repuser = trim($rr['user']);
				if (!$repnumber) continue;
				$reputation['title'] = strip_tags($reputation['title']);
				$reputation['pagetext'] = $forums->func->fetch_trimmed_title(strip_tags($reputation['pagetext']), 200);
				$forums->admin->print_cells_row(array("<a href='../redirect.php?t=" . $reputation['threadid'] . "&amp;goto=findpost&amp;p=" . $reputation['pid'] . "' target='_blank'>" . $reputation['pid'] . "</a>",
						"<a href='../showthread.php?t=" . $reputation['threadid'] . "' target='_blank' title='" . $reputation['pagetext'] . "'>" . $reputation['title'] . "</a>",
						"<a href='../profile.php?u=" . $reputation['id'] . "' target='_blank'>" . $reputation['name'] . "</a>(" . $reputation['reputation'] . ")",
						$repuser,
						$repnumber,
						"<center><a href='#' onclick=\"pop_win('reputation.php?{$forums->sessionurl}do=reset&amp;id={$reputation['pid']}','" . $forums->lang['view'] . "', 400,100)\">{$forums->lang['reset']}</a></center>"));
			}
			$forums->admin->print_cells_single_row($links, 'center', 'pformstrip');
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['noreputations'], 'center');
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do', 'list')), 'searchform');
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['searchreputation']);
		$form_array = array(0 => array('pid', $forums->lang['postid']),
			1 => array('threadid', $forums->lang['threadid']),
			2 => array('username', $forums->lang['torepuser']),
			);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['keyword'] . "</strong>" ,
				$forums->admin->print_input_row("key")
				));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['searchtype'] . "</strong>" ,
				$forums->admin->print_input_select_row("type", $form_array)
				));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function resetrep()
	{
		global $forums, $DB;
		if (0 == input::int('id'))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		cache::update('splittable');
		cache::get('splittable');
		$deftable = $forums->cache['splittable']['default'];
		$posttable = $deftable['name']?$deftable['name']:'post';
		
		$tarinfo = $DB->queryFirst("SELECT p.reppost,p.threadid,u.id, u.name
					     FROM " . TABLE_PREFIX . "$posttable p
					     LEFT JOIN " . TABLE_PREFIX . "user u ON (p.userid = u.id) 
						WHERE pid = " . input::get('id', ''));
		$rr = unserialize($tarinfo['reppost']);
		$rep = '-' . intval($rr['number']);
		$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "user SET reputation = reputation+" . $rep . " WHERE id = " . $tarinfo['id'] . " LIMIT 1");
		$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "$posttable SET reppost = '" . $reputation . "' WHERE pid = " . input::get('id', '') . "");
		$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "thread SET allrep = allrep+" . $rep . " WHERE tid = " . $tarinfo['threadid'] . "");
		$forums->lang['postreputationreset'] = sprintf($forums->lang['postreputationreset'], $tarinfo['name'], input::get('id', ''));
		$forums->admin->print_popup_header();
		$forums->admin->print_cells_single_row($forums->lang['postreputationreset'], 'center');
		$forums->admin->print_popup_footer();
	}
}

$output = new reputation();
$output->show();

?>