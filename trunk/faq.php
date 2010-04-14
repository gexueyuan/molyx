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
define('THIS_SCRIPT', 'faq');
require_once('./global.php');

class faq
{
	var $result = array();
	var $text = '';
	var $search = '';

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo, $bboptions;
		$forums->func->load_lang('faq');
		switch ($_INPUT['do'])
		{
			case 'content':
				$show['content'] = true;
				$faqcache = $this->show_content();
				$pagetitle = $this->result['title'] . " - " . $bboptions['bbtitle'];
				$description = $forums->lang['faqdesc'];
				$nav = array("<a href='faq.php{$forums->si_sessionurl}'>" . $forums->lang['faq'] . "</a>", $this->result['title']);
				break;
			case 'search':
				$show['search'] = true;
				$faqcache = $this->do_search();
				$pagetitle = $forums->lang['faq'] . " - " . $bboptions['bbtitle'];
				$description = $forums->lang['key'] . ': ' . $this->search;
				$nav = array("<a href='faq.php{$forums->sessionurl}'>" . $forums->lang['faq'] . "</a>", $forums->lang['searchresult']);
				break;
			default:
				$show['title'] = true;
				$this->show_titles();
				$pagetitle = $forums->lang['faq'] . " - " . $bboptions['bbtitle'];
				$description = $forums->lang['faqdesc1'];
				$nav = array($forums->lang['faq']);
				break;
		}

		//加载ajax
		$mxajax_register_functions = array(); //注册ajax函数
		require_once(ROOT_PATH . 'includes/ajax/ajax.php');

		$referer = SCRIPTPATH;
		include $forums->func->load_template('help');
		exit;
	}

	function show_titles()
	{
		global $forums, $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "faq ORDER BY displayorder");
		while ($result = $DB->fetch_array())
		{
			if ($result['parentid'] == 0)
			{
				$result['parentid'] = 'root';
			}
			$forums->faqcache[ $result['parentid'] ][ $result['id'] ] = $result;
		}
	}

	function show_content()
	{
		global $forums, $DB, $_INPUT;
		if (! preg_match("/^(\d+)$/" , $_INPUT['id']))
		{
			$forums->func->standard_error("cannotfindfaq");
		}
		$result = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "faq WHERE id={$_INPUT['id']}");
		$result['content'] = true;
		if (preg_match("/<#show_credit#>/ies", $result['text']))
		{
			$result['text'] = preg_replace("/<#show_credit#>/ies", "\$this->convert_credit()", $result['text']);
		}
		$forums->faqcache['root'][ $result['id'] ] = $result;
		$forums->faqcache[ $result['id'] ][ $result['id'] ] = $result;
	}

	function do_search()
	{
		global $forums, $DB, $_INPUT;
		$this->search = utf8_htmlspecialchars(trim($_INPUT['q']));
		if (empty($this->search))
		{
			$result['content'] = true;
			$result['id'] = 1;
			$result['text'] = $forums->lang['inputkey'];
			$result['title'] = $forums->lang['searchresult'];
			$forums->faqcache['root'][ $result['id'] ] = $result;
			$forums->faqcache[ $result['id'] ][ $result['id'] ] = $result;
			return;
		}
		$this->search = strtolower(str_replace("*" , "%", $this->search));
		$this->search = preg_replace("/[<>\!\@$\^&\+\=\=\[\]\{\}\(\)\"':;\.,\/]/", "", $this->search);
		switch ($_INPUT['q_by'])
		{
			case '1':
				$q_by = "LOWER(title) LIKE '%" . $this->search . "%'";
				break;
			case '0':
				$q_by = "LOWER(text) LIKE '%" . $this->search . "%'";
				break;
			default:
				$q_by = "LOWER(title) LIKE '%" . $this->search . "%' OR LOWER(text) LIKE '%" . $this->search . "%'";
				break;
		}
		$results = $DB->query("SELECT * FROM " . TABLE_PREFIX . "faq WHERE " . $q_by . " ORDER BY title");
		if ($DB->num_rows($results))
		{
			while ($result = $DB->fetch_array($results))
			{
				$result['text'] = preg_replace("/(.*)(" . preg_quote($_INPUT['q'], '/') . ")(.*)/is", "\\1<span class='highlight'>\\2</span>\\3", $result['text']);
				$result['title'] = preg_replace("/(.*)(" . preg_quote($_INPUT['q'], '/') . ")(.*)/is", "\\1<span class='highlight'>\\2</span>\\3", $result['title']);
				$result['content'] = true;
				$forums->faqcache['root'][ $result['id'] ] = $result;
				$forums->faqcache[ $result['id'] ][ $result['id'] ] = $result;
			}
		}
		else
		{
			$result['content'] = true;
			$result['id'] = 1;
			$result['text'] = $forums->lang['noresult'];
			$result['title'] = $forums->lang['searchresult'];
			$forums->faqcache['root'][ $result['id'] ] = $result;
			$forums->faqcache[ $result['id'] ][ $result['id'] ] = $result;
		}
	}

	function convert_credit()
	{
		global $forums, $DB, $_INPUT;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "credit");
		if ($DB->num_rows())
		{
			$row = "row2";
			while ($credit = $DB->fetch_array())
			{
				foreach($credit AS $key => $value)
				{
					if (in_array($key, array("creditid", "name")))
					{
						continue;
					}
					if ($value > 0)
					{
						$value = "<strong>+" . $value . "</strong>";
					}
					else if ($value == 0)
					{
						$value = "-";
					}
					else
					{
						$value;
					}
					$credit[$key] = $value;
				}
				$c_list .= "<tr>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['name']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['newthread']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['newreply']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['quintessence']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['award']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['downattach']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['sendpm']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['search']}</td>\n";
				$c_list .= "<td align='center' class='{$row}'>{$credit['c_limit']}</td>\n";
				$c_list .= "</tr>\n";
				$row = $row == "row1" ? "row2" : "row1";
			}
		}
		else
		{
			$c_list = "<tr><td colspan='9' class='row1'>{$forums->lang['nocredit']}</td></tr>";
		}
		$credit = "
		<div id='wttborder' style='width:90%'>
<table width='100%' border='0' cellspacing='1' cellpadding='3' id='ttable'>
<tr>
<td colspan='10' class='thead' align='center'>
{$forums->lang['credit_list']}
</td>
</tr>
<tr class='tcat'>
<th width='20%' align='center' nowrap='nowrap'>{$forums->lang['creditname']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['newthread']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['newreply']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['addquin']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['addaward']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['downattach']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['sendpm']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['search']}</th>
<th width='10%' align='center' nowrap='nowrap'>{$forums->lang['creditlimit']}</th>
</tr>
{$c_list}
</table>
</div>
		";
		return $credit;
	}
}

$output = new faq();
$output->show();

?>