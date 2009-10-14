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
# $Id: adminfunctions.php 460 2008-01-08 01:04:21Z develop_tong $
# **************************************************************************#
class adminfunctions
{
	var $errors = '';
	var $nav = array();
	var $menu_ids = array();
	var $stylecache = array();

	function cache_styles($styleid = 1, $depth = 0)
	{
		global $forums, $DB, $stylecache;
		if (!is_array($stylecache))
		{
			$result = $DB->query('SELECT *
				FROM ' . TABLE_PREFIX . 'style');
			while ($style = $DB->fetch_array($result))
			{
				$stylecache[$style['parentid']][$style['styleid']] = $style;
			}
		}

		if (is_array($stylecache[$styleid]))
		{
			foreach ($stylecache[$styleid] as $style)
			{
				$this->stylecache[$style['styleid']] = $style;
				$this->stylecache[$style['styleid']]['depth'] = $depth;
				$this->cache_styles($style['styleid'], $depth + 1);
			}
		}
	}

	function show_download($data, $name, $type = "application/octetstream")
	{
		@header('Content-Type: ' . $type);
		@header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		@header('Content-Disposition: attachment; filename="' . $name . '"');
		//@header('Content-Length: ' . 446615);
		@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		@header('Pragma: public');
		echo $data;
		exit();
	}

	function save_log($action = '')
	{
		global $DB, $bbuserinfo, $_INPUT;
		$DB->insert(TABLE_PREFIX . 'adminlog', array(
			'script' => SCRIPT,
			'action' => $_INPUT['do'],
			'userid' => $bbuserinfo['id'],
			'dateline' => TIMENOW,
			'note' => $action,
			'host' => IPADDRESS,
		));
		return true;
	}

	function rm_dir($file, $delfolder = true)
	{
		$errors = 0;
		$file = preg_replace('#/$#', '', $file);
		if (file_exists($file))
		{
			@chmod($file, 0777);
			if (is_dir($file))
			{
				$handle = opendir($file);
				while (($filename = readdir($handle)) !== false)
				{
					if (($filename != '.') && ($filename != '..'))
					{
						$this->rm_dir($file . '/' . $filename);
					}
				}
				closedir($handle);
				if ($delfolder)
				{
					if (!@rmdir($file))
					{
						$errors++;
					}
				}
			}
			else
			{
				if (!@unlink($file))
				{
					$errors++;
				}
			}
		}
		if ($errors == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function compile_forum_permission()
	{
		global $DB, $forums, $_INPUT;
		$r_array = array('read' => '', 'reply' => '', 'start' => '', 'upload' => '', 'show' => '');
		$DB->query("SELECT usergroupid, grouptitle, canshow, canviewothers, canpostnew, canreplyothers, attachlimit FROM " . TABLE_PREFIX . "usergroup ORDER BY usergroupid");
		while ($data = $DB->fetch_array())
		{
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				if ($_INPUT[ $bit . '_' . $data['usergroupid'] ] == 1)
				{
					$r_array[ $bit ] .= $data['usergroupid'] . ",";
				}
				if ($data['canshow'] AND $bit == 'show')
				{
					$g_array['show'] .= $data['usergroupid'] . ",";
				}
				if ($data['canviewothers'] AND $bit == 'read')
				{
					$g_array['read'] .= $data['usergroupid'] . ",";
				}
				if ($data['canpostnew'] AND $bit == 'start')
				{
					$g_array['start'] .= $data['usergroupid'] . ",";
				}
				if ($data['canreplyothers'] AND $bit == 'reply')
				{
					$g_array['reply'] .= $data['usergroupid'] . ",";
				}
				if ($data['attachlimit'] != -1 AND $data['attachlimit'] != '' AND $bit == 'upload')
				{
					$g_array['upload'] .= $data['usergroupid'] . ",";
				}
			}
		}
		$perms = $DB->query_first("SELECT COUNT(usergroupid) AS count FROM " . TABLE_PREFIX . "usergroup ORDER BY usergroupid");
		foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
		{
			if ($r_array[ $bit ] == $g_array[ $bit ])
			{
				$r_array[ $bit ] = '';
			}
			else if ($r_array[ $bit ] == "")
			{
				$r_array[ $bit ] = '-';
			}
			else
			{
				$curcount = count(explode(',', $r_array[ $bit ])) - 1;
				if ($perms['count'] == $curcount)
				{
					$r_array[ $bit ] = '*';
				}
			}
		}
		if ($r_array['start'] == "-" AND $r_array['reply'] = "-" AND $r_array['read'] = "-" AND $r_array['upload'] = "-" AND $r_array['show'] = "-")
		{
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				$r_array[ $bit ] = '';
			}
		}
		$r_array['start'] = preg_replace("/,$/", "", $r_array['start']);
		$r_array['reply'] = preg_replace("/,$/", "", $r_array['reply']);
		$r_array['read'] = preg_replace("/,$/", "", $r_array['read']);
		$r_array['upload'] = preg_replace("/,$/", "", $r_array['upload']);
		$r_array['show'] = preg_replace("/,$/", "", $r_array['show']);
		return $r_array;
	}

	function build_group_perms($perms = array())
	{
		global $forums, $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY displayorder ASC");
		while ($data = $DB->fetch_array())
		{
			foreach(array('read', 'reply', 'start', 'upload', 'show') AS $bit)
			{
				if ($perms[ $bit ] == '')
				{
					if ($bit == 'show')
					{
						$permission['show'] = $data['canshow'] ? "<input type='checkbox' name='show_{$data['usergroupid']}' value='1' checked='checked' />" : "<input type='checkbox' name='show_{$data['usergroupid']}' value='1' />";
					}
					if ($bit == 'read')
					{
						$permission['read'] = $data['canviewothers'] ? "<input type='checkbox' name='read_{$data['usergroupid']}' value='1' checked='checked' />" : "<input type='checkbox' name='read_{$data['usergroupid']}' value='1' />";
					}
					if ($bit == 'reply')
					{
						$permission['reply'] = $data['canreplyothers'] ? "<input type='checkbox' name='reply_{$data['usergroupid']}' value='1' checked='checked' />" : "<input type='checkbox' name='reply_{$data['usergroupid']}' value='1' />";
					}
					if ($bit == 'start')
					{
						$permission['start'] = $data['canpostnew'] ? "<input type='checkbox' name='start_{$data['usergroupid']}' value='1' checked='checked' />" : "<input type='checkbox' name='start_{$data['usergroupid']}' value='1' />";
					}
					if ($bit == 'upload')
					{
						$permission['upload'] = ($data['attachlimit'] == -1 OR $data['attachlimit'] == '') ? "<input type='checkbox' name='upload_{$data['usergroupid']}' value='1' />" : "<input type='checkbox' name='upload_{$data['usergroupid']}' value='1' checked='checked' />";
					}
				}
				else if ($perms[ $bit ] == '*')
				{
					$permission[ $bit ] = "<input type='checkbox' name='{$bit}_{$data['usergroupid']}' value='1' checked />";
				}
				else if (preg_match("/(^|,)" . $data['usergroupid'] . "(,|$)/", $perms[ $bit ]))
				{
					$permission[ $bit ] = "<input type='checkbox' name='{$bit}_{$data['usergroupid']}' value='1' checked />";
				}
				else
				{
					$permission[ $bit ] = "<input type='checkbox' name='{$bit}_{$data['usergroupid']}' value='1' />";
				}
			}
			$this->print_cells_row(array("<div style='float:right'><input type='button' class='button' value='+' onclick='checkrow({$data['usergroupid']},1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkrow({$data['usergroupid']},0)' /></div><strong>{$forums->lang[ $data['grouptitle'] ]}</strong>",
					"<center class='pgroup1'>" . $permission['show'] . "</center>",
					"<center class='pgroup2'>" . $permission['read'] . "</center>",
					"<center class='pgroup3'>" . $permission['reply'] . "</center>",
					"<center class='pgroup4'>" . $permission['start'] . "</center>",
					"<center class='pgroup5'>" . $permission['upload'] . "</center>",
					));
		}
		$this->print_cells_row(array("&nbsp;",
				"<center><input type='button' class='button' value='+' onclick='checkcol(5,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(5,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(1,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(1,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(2,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(2,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(3,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(3,0)' /></center>",
				"<center><input type='button' class='button' value='+' onclick='checkcol(4,1)' />&nbsp;<input type='button' class='button' value='-' onclick='checkcol(4,0)' /></center>",
				));
	}

	function build_groupeval_perms($perms = array())
	{
		global $forums, $DB;
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY grouptitle ASC");
		while ($data = $DB->fetch_array())
		{
			$groups[$data['usergroupid']] = $data;
		}
		foreach ($groups as $key => $group)
		{
			$array = array();
			$array[] = "<strong>{$forums->lang[ $group['grouptitle'] ]}</strong>";
			foreach ($groups as $k => $v)
			{
				$permission = $perms[$key][$k] ? "<input type='checkbox' name='show_".$key.'_'.$k."' value='1' checked='checked' />" : "<input type='checkbox' name='show_".$key.'_'.$k."' value='1' />";
				$array[] = "<center class='pgroup3'>" . $permission . "</center>";
			}
			$this->print_cells_row($array);
		}
	}

	function print_popup_header()
	{
		global $forums;
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['view'] . "</title>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $forums->lang['charset'] . "\" />\n";
		echo "<meta HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Expires\" CONTENT=\"Mon, 06 May 1996 04:57:00 GMT\" />\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $forums->imageurl . "/controlpanel.css\" />\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('global') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('controlpanel') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='../scripts/global.js'></script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->imageurl . "/controlpanel.js'></script>\n";
		echo "</head>\n";
		echo "<body>\n";
	}

	function print_popup_footer()
	{
		echo "</body></html>\n";
		exit();
	}

	function print_cp_header($title = "", $desc = "", $js = "", $notable = "", $redirect = "")
	{
		global $forums, $bboptions, $_INPUT;
		$navigation = "<a href='index.php?s=" . $forums->sessionid . "' target='body'><img src='" . $forums->imageurl . "/nav001.gif' border='0' alt='' /><font color='#C8DBF0'>" . $forums->lang['admincphome'] . "</font></a>";
		if (count($this->nav) > 0)
		{
			$c = 2;
			foreach ($this->nav AS $idx => $links)
			{
				$link = preg_replace("#(.*)(^|\.php)#", '\\1\\2?' . $forums->sessionurl, str_replace("?", '', $links[0]));
				if ($links[0] != "")
				{
					$navigation .= "<img src='" . $forums->imageurl . "/nav00" . $c . ".gif' border='0' alt='' /><a href='" . $link . "' target='body'><font color='#C8DBF0'>{$links[1]}</font></a>";
				}
				else
				{
					$navigation .= "<img src='" . $forums->imageurl . "/nav00" . $c . ".gif' border='0' alt='' /><font color='#C8DBF0'>{$links[1]}</font>";
				}
				$c++;
			}
		}
		$this->showheader = true;
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['admincp'] . "</title>\n";
		echo "<meta HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=" . $forums->lang['charset'] . "\" />\n";
		echo "<meta HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Expires\" CONTENT=\"Mon, 06 May 1996 04:57:00 GMT\" />\n";
		echo $redirect;
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $forums->imageurl . "/controlpanel.css\" />\n";
		echo "<script type=\"text/javascript\">\n";
		echo "<!--\n";
		echo "var current_page = \"{$_INPUT['pp']}\";\n";
		echo "var cookie_id = \"{$bboptions['cookieprefix']}\";\n";
		echo "var cookie_domain = \"{$bboptions['cookiedomain']}\";\n";
		echo "var cookie_path   = \"{$bboptions['cookiepath']}\";\n";
		echo "var lang_a = [];\n";
		echo "//-->\n";
		echo "</script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('global') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('controlpanel') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='../scripts/global.js'></script>\n";
		echo "<script type=\"text/javascript\" src='{$forums->imageurl}/controlpanel.js'></script>\n";
		echo "</head>\n";
		echo "<body {$this->top_extra} {$js}>\n";
		echo "<div id='logostrip'>\n";
		echo "<div id='logostripinner'><div style='font-weight:bold;font-size:12px;color:#FFFFFF;padding-top:44px;padding-left:20px'>:: $title ::</div></div>\n";
		echo "</div>\n";
		if (!defined('LOGIN'))
		{
			echo "<div id='nav'>\n";
			echo "<div style='float:right;font-weight:bold;color:#C8DBF0;padding-top:11px;padding-right:4px'><a href='index.php?" . $forums->sessionurl . "do=logout' target='_parent'><font color='#C8DBF0'>" . $forums->lang['logout'] . "</font></a> | <a href='{$bboptions['bburl']}/{$bboptions['forumindex']}' target='_blank'><font color='#C8DBF0'>" . $forums->lang['boardhome'] . "</font></a></div>\n";
			echo "<div style='font-weight:bold;color:#FFF;padding-left:4px;'>" . $navigation . "</div>\n";
			echo "</div>\n";
		}
		else
		{
			echo "<br />\n";
		}
		if (!$notable)
		{
			echo "<table width='100%' cellspacing='0' cellpadding='3' align='center' border='0'>\n";
			echo "<tr><td>\n";
		}
		if ($desc)
		{
			echo "<div id='maintd'><div id='description'>$desc</div></div>\n";
		}
		if ($forums->main_error)
		{
			echo "<br />\n";
			echo "<div class='tableborder'>\n";
			echo "<div class='pformstrip'>" . $forums->lang['settingerrors'] . "</div>\n";
			echo "<div class='tdrow1' style='font-size:11px'>{$forums->main_msg}</div>\n";
			echo "</div>\n";
		}
		if ($forums->main_msg)
		{
			echo "<br />\n";
			echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0' class='infoborder'>\n";
			echo "<tr><td style='background-image:url(table_center.gif);'>\n";
			echo "<div style='float:left;font-weight:bold;color:#C8DBF0;padding-right:4px' id='infoleft'>&nbsp;</div>\n";
			echo "<div style='float:right;font-weight:bold;color:#C8DBF0;padding-right:4px' id='inforight'>&nbsp;</div>\n";
			echo "<div class='catfont'>\n";
			echo "<img src='" . $forums->imageurl . "/arrow.gif' alt='' />&nbsp;&nbsp;" . $forums->lang['admincpmessage'] . "</div>\n";
			echo "<div class='tdrow1'>{$forums->main_msg}</div>\n";
			echo "</td></tr>\n\n";
			echo "</table><br />\n\n";
		}
		echo "<br />\n";
	}

	function print_cp_footer()
	{
		global $forums, $bboptions;
		echo "<br />\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<div class='copyright' align='center'>Powered By <a href='http://www.molyx.com' target='_blank'>MolyX {$bboptions['version']}</a> &copy; 2004-2006 <a href='http://www.hogesoft.com' target='_blank'>HOGE Software</a></div>\n";
		echo "</body>\n";
		echo "</html>\n";
		$forums->func->finish();
		exit();
	}

	function redirect($url, $text, $text2, $is_popup = 0, $time = 3)
	{
		global $forums;
		$forums->main_msg = "";
		$url = preg_replace("#(.*)(^|\.php)#", '\\1\\2?' . $forums->sessionurl, str_replace("?", '', $url));
		$pagetitle = $forums->lang['settingsupdated'];
		$redirect = "<meta http-equiv='refresh' content=\"{$time}; url={$url}{$extra}\" />\n";
		$this->print_cp_header($pagetitle, $detail, "", "", $redirect);
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' alt='' />&nbsp;&nbsp;" . $forums->lang['redirecting'] . $text . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td>\n";
		echo "<div class='tdrow1' style='padding:8px'>\n";
		echo "<br />\n";
		echo "<center>" . $text2 . "<br /><a href='{$url}'>" . $forums->lang['redirectinginfo'] . "</a><br /></center>\n";
		echo "<br />\n";
		echo "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		$this->print_cp_footer();
	}

	function print_cp_error($error = "")
	{
		global $forums;
		if ($this->showheader != true)
		{
			$pagetitle = $forums->lang['admincpmessage'];
			$this->print_cp_header($pagetitle);
		}
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>\n";
		echo "<div class='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif' alt='' />&nbsp;&nbsp;" . $forums->lang['admincperrormessage'] . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td>\n";
		echo "<div class='tdrow1' style='padding:8px'>\n";
		echo "<div style='font-size:12px'>\n";
		echo "<br />\n";
		echo "<center>$error</center>\n";
		echo "<br /></div>\n";
		echo "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		$this->print_cp_footer();
		exit;
	}

	function menu()
	{
		global $forums, $options, $bboptions, $bbuserinfo, $_INPUT;
		$admin = explode(',', SUPERADMIN);
		$thispanel = $_INPUT['panellist'] ? trim($_INPUT['panellist']) : 'forum';
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['admincpmenu'] . "</title>\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$forums->imageurl}/controlpanel.css\" />\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('global') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('controlpanel') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='../scripts/global.js'></script>\n";
		echo "<script type=\"text/javascript\" src='{$forums->imageurl}/controlpanel.js'></script>\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<div class='menuborder'>\n";
		echo "<div class='menulinkwrap'>\n";
		require_once(ROOT_PATH . "includes/adminfunctions_nav_" . $thispanel . ".php");
		$collapsed_ids = "," . $forums->func->get_cookie('cpcollapseprefs') . ",";
		for($i = 1, $n = count($options); $i <= $n; $i++)
		{
			$this->menu_ids[] = $thispanel . $i;
		}
		$forums->func->load_lang('admin_nav');
		if (is_file(ROOT_PATH . "lang/{$bboptions['language']}/admin_nav_{$thispanel}.php"))
		{
			$forums->func->load_lang('admin_nav_' . $thispanel);
		}
		echo "<script type=\"text/javascript\">\n";
		echo "<!--\n";
		echo "var cookie_id = \"{$bboptions['cookieprefix']}\";\n";
		echo "var cookie_domain = \"{$bboptions['cookiedomain']}\";\n";
		echo "var cookie_path   = \"{$bboptions['cookiepath']}\";\n";
		echo "var menu_ids         = \"" . implode(',', $this->menu_ids) . "\";\n";
		echo "//-->\n";
		echo "</script>\n";
		echo "<a href='javascript:expandmenu();'>" . $forums->lang['expandmenu'] . "</a> &middot; <a href='javascript:collapsemenu();'>" . $forums->lang['collapsemenu'] . "</a>\n";
		echo "<br /><a href='index.php?{$forums->sessionurl}' target='body'>" . $forums->lang['admincphome'] . "</a> &middot; <a href='{$bboptions['bburl']}/{$bboptions['forumindex']}' target='body'>" . $forums->lang['boardhome'] . "</a>\n";
		echo "</div>\n";
		echo "</div>\n";
		echo "<br />\n";
		$cid = 0;
		foreach($options AS $cat => $data)
		{
			$s = explode('||', $cat);
			$name = $s[0];
			if (!in_array($bbuserinfo['id'], $admin))
			{
				if (isset($s[1]) AND !$forums->adminperms[$s[1]]) continue;
			}
			$cid++;
			$show['div_fc'] = 'show';
			$show['div_fo'] = 'none';
			if (strstr($collapsed_ids, ',' . $thispanel . $cid . ','))
			{
				$show['div_fc'] = 'none';
				$show['div_fo'] = 'show';
			}
			echo "<div style='padding:0px;'>\n";
			echo "<div class='' style='display:{$show['div_fo']}' id='fo_" . $thispanel . $cid . "'>\n";
			echo "<div class='menuborder'>\n";
			echo "<a href='#' onclick=\"togglemenucategory('" . $thispanel . $cid . "', 1); return false;\"><img src='{$forums->imageurl}/toc_collapse.gif' border='0' alt='" . $forums->lang['collapsecategory'] . "' title='" . $forums->lang['collapsecategory'] . "' /></a> <a href='#' title='' onclick=\"togglemenucategory('" . $thispanel . $cid . "', 1); return false;\">" . $forums->lang[$name] . "</a>";
			echo "</div>\n";
			echo "<div class='menulinkwrap'>\n";
			foreach($data AS $pid => $pdata)
			{
				$t = explode('||', $pdata[0]);
				$sub = $t[0];
				if (!in_array($bbuserinfo['id'], $admin))
				{
					if (isset($t[1]) AND !$forums->adminperms[$t[1]]) continue;
				}
				$icon = "<img src='{$forums->imageurl}/toc_end.gif' border='0' alt='' class='inline' />";
				$extra_css = "";
				$link = preg_replace("#(.*)(^|\.php)#", '\\1\\2?' . $forums->sessionurl, str_replace("?", '', $pdata[1]));
				echo "<div class='menusub' id='m_" . $thispanel . $cid . "_{$pid}' onmouseout=\"change_cell_color('m_" . $thispanel . $cid . "_{$pid}', 'menusub');\" onmouseover=\"change_cell_color('m_" . $thispanel . $cid . "_{$pid}', 'menusubon');\">&nbsp;{$icon}&nbsp;<a href='" . $link . "' target='" . ($sub == 'portal_main' ? '_blank' : 'body') . "' >" . $forums->lang[$sub] . "</a></div>\n";
			}
			echo "</div>\n";
			echo "</div>\n";
			echo "<div class='' style='display:{$show['div_fc']}' id='fc_" . $thispanel . $cid . "'>\n";
			echo "<div class='menuborder'>\n";
			echo "<a href='#' onclick=\"togglemenucategory('" . $thispanel . $cid . "', 0); return false;\"><img src='{$forums->imageurl}/toc_expand.gif' border='0' alt='" . $forums->lang['expandcategory'] . "' title='" . $forums->lang['expandcategory'] . "' /></a> <a href='#' title='' onclick=\"togglemenucategory('" . $thispanel . $cid . "', 0); return false;\">" . $forums->lang[$name] . "</a>\n";
			echo "</div>\n";
			echo "</div>\n";
			echo "</div>\n";
		}
		echo "</body></html>";
		exit;
	}

	function nav()
	{
		global $forums, $options, $bboptions, $bbuserinfo, $_INPUT;
		$admin = explode(',', SUPERADMIN);
		$panellist = array();
		$handle = @opendir(ROOT_PATH . 'includes');
		$forums->func->load_lang('admin_nav');
		while ($file = @readdir($handle))
		{
			if (preg_match("/^adminfunctions_nav_(.*)\.php$/", $file, $regs))
			{
				if (is_file(ROOT_PATH . "lang/{$bboptions['language']}/admin_nav_{$regs[1]}.php"))
				{
					$forums->func->load_lang('admin_nav_' . $regs[1]);
				}
				$panelfile = fopen(ROOT_PATH . "includes/$file", 'r');
				fseek($panelfile, 9);
				$name = utf8_htmlspecialchars(str_replace('// ', '', trim(fgets($panelfile, 255))));
				fclose($panelfile);
				$panellist[] = array($regs[1], $forums->lang[$name]);
				flush();
			}
		}
		closedir($handle);
		$thispanel = $_INPUT['panellist'] ? trim($_INPUT['panellist']) : 'forum';
		echo "<html>\n";
		echo '<head><title>' . $forums->lang['admincp'] . "</title>\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$forums->imageurl}/controlpanel.css\" />\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<div class='menuborder'>\n";
		echo $this->print_form_header(array(1 => array('do' , 'menu')), 'cpform', ' target="mainFrame"', '', 'get');
		echo $this->print_input_select_row('panellist', $panellist, $thispanel) . "<br /><input type='submit' class='button' value='" . $forums->lang['switchpanel'] . "' />";
		echo $this->print_form_end();
		require_once(ROOT_PATH . 'includes/adminfunctions_nav_' . $thispanel . '.php');
		$collapsed_ids = ',' . $forums->func->get_cookie('cpcollapseprefs') . ',';
		for($i = 1, $n = count($options); $i <= $n; $i++)
		{
			$this->menu_ids[] = $thispanel . $i;
		}
		echo "</div>\n";
		echo '</body></html>';
		exit();
	}

	function print_frame_set()
	{
		global $forums, $_INPUT;
		$_GET['reffer_url'] = preg_replace("#(.*)(^|\.php)#", '\\1\\2?' . $forums->sessionurl, str_replace("?", '', rawurldecode($_GET['reffer_url'])));
		if (preg_match("#(reffer_url|logout)#", $_GET['reffer_url']))
		{
			$_GET['reffer_url'] = "index.php?{$forums->sessionurl}";
		}
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['admincp'] . "</title></head>\n";
		echo "<frameset rows='*' cols='200, *' border='0' framespacing='0'>\n";
		echo "<frameset rows='*,50' border='0' framespacing='0'>\n";
		echo "<frame src='index.php?s=" . $forums->sessionid . "&amp;do=menu' name='mainFrame' frameborder='0' noresize='noresize' />\n";
		echo "<frame src='index.php?s=" . $forums->sessionid . "&amp;do=nav' name='bottomFrame' scrolling='no' noresize='noresize' frameborder='0' />\n";
		echo "</frameset>\n";
		echo "<frame name='body' noresize='noresize' scrolling='auto' src='" . $_GET['reffer_url'] . "' frameborder='0' />\n";
		echo "</frameset>\n";
		echo "</html>";
		exit();
	}

	function print_cp_login($message = "")
	{
		global $forums, $DB, $bboptions;
		define ('LOGIN', true);
		$cut_off_stamp = TIMENOW - 7200;
		$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "adminsession WHERE logintime < $cut_off_stamp");
		$name = "";
		$extra = "";
		$userid = intval($forums->func->get_cookie('userid'));
		if ($userid > 0)
		{
			if ($r = $DB->query_first("SELECT u.id, u.name, u.usergroupid, g.cancontrolpanel FROM " . TABLE_PREFIX . "user u, " . TABLE_PREFIX . "usergroup g WHERE u.id=$userid AND g.usergroupid=u.usergroupid AND g.cancontrolpanel=1"))
			{
				$name = $r['name'];
				$extra = 'onload="document.cpform.password.focus();"';
			}
		}
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head><title>" . $forums->lang['admincp'] . "</title>\n";
		echo "<meta HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=" . $forums->lang['charset'] . "\" />\n";
		echo "<meta HTTP-EQUIV=\"Pragma\"  CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\" />\n";
		echo "<meta HTTP-EQUIV=\"Expires\" CONTENT=\"Mon, 06 May 1996 04:57:00 GMT\" />\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$forums->imageurl}/controlpanel.css\" />\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('global') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='" . $forums->func->load_lang_js('controlpanel') . "'></script>\n";
		echo "<script type=\"text/javascript\" src='../scripts/global.js'></script>\n";
		echo "<script type=\"text/javascript\" src='{$forums->imageurl}/controlpanel.js'></script>\n";
		echo "</head>\n";
		echo "<body {$extra}>\n";
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "<!--\n";
		echo "if (top.location != self.location) { top.location = self.location }\n";
		echo "//-->\n";
		echo "</script>\n";
		echo "<table width='450' cellspacing='0' cellpadding='3' align='center' border='0'>\n";
		echo "<tr><td>\n";
		echo "<br /><br /><br />\n";

		$this->columns[] = array("&nbsp;" , "40%");
		$this->columns[] = array("&nbsp;" , "60%");
		$SCRIPTPATH = str_replace("do=logout", " ", SCRIPTPATH);
		$this->print_form_header(array(1 => array('login' , 'yes'), 2 => array("reffer_url", $SCRIPTPATH)));
		$this->print_table_start($forums->lang['loginadmincp']);
		if ($message != '')
		{
			$this->print_cells_single_row("<span class='highlight'>$message</span>");
		}
		$this->print_cells_row(array("<strong>" . $forums->lang['username'] . ":</strong>", "<input type='text' style='width:100%' class='textinput' name='username' value='$name' />"));
		$this->print_cells_row(array("<strong>" . $forums->lang['password'] . ":</strong>", "<input type='password' style='width:100%' class='textinput' name='password' value='' />",));
		$this->print_cells_single_row("<input type='submit' value='" . $forums->lang['loginpanel'] . "' class='button' accesskey='s' />", "center", "pformstrip");
		$this->print_table_footer();
		$this->print_form_end();
		$this->print_cp_footer();
	}

	function checkdelete()
	{
		global $forums;
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "<!--\n";
		echo "function checkdelete(theURL, action) {\n";
		echo "final_url = theURL + \"?" . $forums->js_sessionurl . "\" + action;\n";
		echo "if ( confirm('" . $forums->lang['areyousuredelete'] . "\\n" . $forums->lang['cannotrevert'] . "') )\n";
		echo "{\n";
		echo "document.location.href=final_url;\n";
		echo "}\n";
		echo "}\n";
		echo "//-->\n";
		echo "</script>\n";
	}

	function print_form_header($hiddens = array(), $name = 'cpform', $js = '', $script = '', $method = 'post')
	{
		global $forums;
		$thisscript = $script ? $script : SCRIPT;
		echo '<form action="' . $thisscript . '" method="' . $method . '" name="' . $name . '" ' . $js . '>';
		echo '<input type="hidden" name="s" value="' . $forums->sessionid . '" />';
		if (is_array($hiddens))
		{
			foreach ($hiddens as $k => $v)
			{
				echo '<input type="hidden" name="' . $v[0] . '" value="' . $v[1] . '" />';
			}
		}
	}

	function print_hidden_row($hiddens = "")
	{
		if (is_array($hiddens))
		{
			foreach ($hiddens AS $k => $v)
			{
				echo "\n<input type='hidden' name='{$v[0]}' value='{$v[1]}' />";
			}
		}
	}

	function print_form_end($text = '', $js = '', $extra = '')
	{
		$colspan = '';
		if ($text != '')
		{
			if ($this->td_colspan > 0)
			{
				$colspan = " colspan='" . $this->td_colspan . "' ";
			}
			echo "<tr><td align='center' class='pformstrip'" . $colspan . "><input type='submit' value='$text'" . $js . " class='button' accesskey='s' />{$extra}</td></tr>\n";
		}
		echo "</form>\n";
	}

	function print_form_submit($text = '', $js = '', $extra = '')
	{
		if ($text != '')
		{
			$colspan = '';
			if ($this->td_colspan > 0)
			{
				$colspan = ' colspan="' . $this->td_colspan . '" ';
			}
			echo '<tr><td align="center" class="pformstrip"' . $colspan . '><input type="submit" value="' . $text . '"' . $js . ' class="button" accesskey="s" />' . $extra . '</td></tr>';
		}
	}

	function print_form_end_standalone($text = '', $js = '')
	{
		if ($text != '')
		{
			echo "<div class='tableborder' align='center'><input type='submit' value='$text'" . $js . " class='button' accesskey='s' /></div>\n";
		}
		echo "</form>\n";
	}

	function print_table_start($title = '', $desc = '', $extra = '', $createform = '')
	{
		global $forums;
		$tdcolspan = count($this->columns);
		if ($title != '')
		{
			$this->has_title = 1;
			echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
			echo "<tr><td class='tableborder'>$extra\n";
			echo "<div class='catfont'>\n";
			echo "<img src='" . $forums->imageurl . "/arrow.gif' class='inline' alt='' />&nbsp;&nbsp;$title</div>\n";
			echo "</td></tr>\n";
			echo "</table>\n";
			if (!empty($desc))
			{
				echo "<div class='pformstrip'>$desc</div>\n";
			}
			if ($createform)
			{
				$this->print_form_header($createform);
			}
			echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		}
		else
		{
			echo '<table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">';
		}
		if (isset($this->columns[0]))
		{
			if ($this->columns[0][0] == '&nbsp;' && $this->columns[1][0] == '&nbsp;' && (! isset($this->columns[2][0])))
			{
				$this->columns[0][0] = '{none}';
				$this->columns[1][0] = '{none}';
			}
			$tdstring = '';
			foreach ($this->columns as $td)
			{
				$width = ($td[1] != '') ? " width='{$td[1]}' " : '';
				if ($td[0] != '{none}')
				{
					$tdstring .= "<td class='tcat'" . $width . "align='center' nowrap='nowrap'>{$td[0]}</td>\n";
				}
				$this->td_colspan++;
			}
			if ($tdstring)
			{
				echo '<tr>';
				echo $tdstring;
				echo '</tr>';
			}
		}
	}

	function print_cells_row($array, $css = "", $align = 'middle')
	{
		if (is_array($array))
		{
			echo "<tr>\n";
			$count = count($array);
			$this->td_colspan = $count;
			for ($i = 0; $i < $count ; $i++)
			{
				$td_col = $i % 2 ? 'tdrow2' : 'tdrow1';
				if ($css != "")
				{
					$td_col = $css;
				}
				if (is_array($array[$i]))
				{
					$text = $array[$i][0];
					$colspan = $array[$i][1];
					$td_col = $array[$i][2] != "" ? $array[$i][2] : $td_col;
					echo "<td class='" . $td_col . "' colspan='" . $colspan . "' valign='" . $align . "' nowrap='nowrap'>" . $text . "</td>\n";
					$this->td_colspan++;
				}
				else
				{
					if ($this->columns[$i][1] != "")
					{
						$width = " width='" . $this->columns[$i][1] . "' ";
					}
					else
					{
						$width = "";
					}
					$array[$i] = $array[$i] ? $array[$i] : "&nbsp;";
					echo "<td class='" . $td_col . "' $width valign='" . $align . "'>" . $array[$i] . "</td>\n";
				}
			}
			echo "</tr>\n";
		}
	}

	function print_cells_single_row($text = '', $align = 'left', $id = 'tdrow1', $style = '')
	{
		$colspan = '';
		if ($text != '')
		{
			if ($this->td_colspan > 0)
			{
				$colspan = ' colspan="' . $this->td_colspan . '" ';
			}
			echo '<tr><td align="' . $align . '" class="' . $id . '"' . $colspan . ' ' . $style . '>' . $text . '</td></tr>';
		}
	}

	function print_table_footer($br = true)
	{
		$this->columns = array();
		if ($this->has_title && $br)
		{
			echo "</table><br />\n\n";
			$this->has_title = 0;
		}
		else
		{
			echo "</table>\n\n";
		}
	}

	function print_button($text = "", $url = "", $css = 'button', $title = "")
	{
		return "<input type='button' class='{$css}' value='{$text}' onclick='self.location.href=\"{$url}\"' title='$title' />\n";
	}

	function print_input_row($name, $value = '', $type = 'text', $js = '', $size = '30', $class = 'textinput')
	{
		return '<input type="' . $type . '" name="' . $name . '" value="' . $value . '" size="' . $size . '" ' . $js . ' class="' . $class . '" />';
	}

	function print_textarea_row($name, $value = "", $cols = '60', $rows = '5', $wrap = 'soft', $id = "", $style = "")
	{
		if ($id)
		{
			$id = "id='$id'";
		}
		if ($style)
		{
			$style = "style='$style'";
		}
		return "<textarea name='" . $name . "' cols='" . $cols . "' rows='" . $rows . "' " . $id . " " . $style . " class='multitext' style='width:100%;'>" . $value . "</textarea>\n";
	}

	function print_input_select_row($name, $list = array(), $default_val = "", $js = "", $css = "dropdown")
	{
		$dropdown = "<select name='" . $name . "' " . $js . " class='" . $css . "'>\n";
		foreach ($list as $k => $v)
		{
			if ($v == '') continue;
			$selected = '';
			if (!is_array($v))
			{
				$v = array($v, $v);
			}
			if ($default_val != '' && $v[0] == $default_val)
			{
				$selected = ' selected="selected"';
			}
			$dropdown .= "<option value='{$v[0]}'{$selected}>{$v[1]}</option>\n";
		}
		$dropdown .= "</select>\n\n";
		return $dropdown;
	}

	function print_multiple_select_row($name, $list = array(), $default = array(), $size = 5, $js = "")
	{
		$select = "<select name='{$name}' {$js} class='dropdown' multiple='multiple' size='{$size}'>\n";
		foreach ($list AS $k => $v)
		{
			$selected = "";
			if (count($default) > 0)
			{
				if (in_array($v[0], $default))
				{
					$selected = ' selected="selected"';
				}
			}
			$select .= "<option value='{$v[0]}'{$selected}>{$v[1]}</option>\n";
		}
		$select .= "</select>\n\n";
		return $select;
	}

	function print_yes_no_row($name, $default_val = '', $extra = '')
	{
		global $forums;
		$yes = "<input type='radio' name='{$name}' value='1' />&nbsp;" . $forums->lang['yes'];
		$no = "<input type='radio' name='{$name}' value='0' />&nbsp;" . $forums->lang['no'];
		if ($extra)
		{
			$extradio = "<input type='radio' name='{$name}' value='-1' />&nbsp;" . $extra;
		}
		if ($default_val == 1)
		{
			$yes = "<input type='radio' name='{$name}' value='1' checked />&nbsp;" . $forums->lang['yes'];
		}
		else if ($default_val == -1)
		{
			$extradio = "<input type='radio' name='{$name}' value='-1' checked />&nbsp;" . $extra;
		}
		else
		{
			$no = "<input type='radio' name='{$name}' value='0' checked />&nbsp;" . $forums->lang['no'];
		}
		return $yes . '&nbsp;&nbsp;' . $no . '&nbsp;&nbsp;' . $extradio;
	}

	function print_checkbox_row($name, $checked = 0, $val = 1)
	{
		if ($checked)
		{
			$check = " checked='checked'";
		}
		return "<input type='checkbox' name='{$name}' value='{$val}'{$check} />\n";
	}

	function print_radio_row($name, $checked = 0, $val = 1)
	{
		if ($checked)
		{
			$check = " checked='checked'";
		}
		return "<input type='radio' name='{$name}' value='{$val}'{$check} />\n";
	}


	function recount_stats($cache = 1)
	{
		global $forums, $DB, $_INPUT;

		$stats = array();
		if ($_INPUT['users'] || $cache)
		{
			$row = $DB->query_first('SELECT COUNT(id) AS users
				FROM ' . TABLE_PREFIX . 'user
				WHERE usergroupid <> 2');
			$stats[] = array('numbermembers', intval($row['users']));
		}

		if ($_INPUT['lastreg'] || $cache)
		{
			$row = $DB->query_first('SELECT id, name
				FROM ' . TABLE_PREFIX . 'user
				WHERE usergroupid <> 2
				ORDER BY id DESC
				LIMIT 1');
			$stats[] = array('newusername', $row['name']);
			$stats[] = array('newuserid', $row['id']);
		}

		if ($_INPUT['online'] && 0)
		{
			$stats[] = array('maxonlinedate', TIMENOW);
			$stats[] = array('maxonline', 1);
		}
		$DB->update_cache($stats);
		$forums->func->recache('stats');
	}

	function mkuserfield($field, $value = array())
	{
		switch ($field['showtype'])
		{
			case 'text':
				$this->print_cells_row(array("<strong>" . $field['fieldname'] . "</strong>", $this->print_input_row($field['fieldtag'], $_INPUT[$field['fieldtag']] ? $_INPUT[$field['fieldtag']] : $value[$field['fieldtag']])));
			break;
			case 'textarea':
				$this->print_cells_row(array("<strong>" . $field['fieldname'] . "</strong>", $this->print_textarea_row($field['fieldtag'], $value[$field['fieldtag']])));
			break;
			case 'select':
				$this->print_cells_row(array("<strong>" . $field['fieldname'] . "</strong>", $this->print_input_select_row($field['fieldtag'], $field['listcontent'], $_INPUT[$field['fieldtag']] ? $_INPUT[$field['fieldtag']] : $value[$field['fieldtag']]), ''));
			break;
		}
	}
}
?>