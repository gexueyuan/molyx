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
# $Id: adminfunctions_javascript.php 460 2008-01-08 01:04:21Z develop_tong $
# **************************************************************************#
class adminfunctions_javascript
{
	function createjs($js = array(), $update = 1, $oldname = "")
	{
		global $forums, $DB, $bboptions;
		if ($js['numbers'])
		{
			$limit = " LIMIT 0, {$js['numbers']}";
		}
		if ($js['type'] == 0)
		{
			$query[] = "t.visible=1";
			if ($js['inids'] AND $js['inids'] != -1)
			{
				$query[] = "t.forumid IN (" . $js['inids'] . ")";
			}

			$query[] = "t.dateline < " . TIMENOW;
			if ($js['daylimit'])
			{
				$cuttime = TIMENOW - $js['daylimit'] * 86400;
				$query[] = "t.dateline > " . $cuttime;
			}
			switch ($js['orderby'])
			{
				case 0:
					$orderby[] = "t.dateline DESC";
					break;
				case 1:
					$orderby[] = "t.tid DESC";
					break;
				case 2:
					$orderby[] = "t.post DESC";
					break;
				case 3:
					$orderby[] = "t.views DESC";
					break;
				case 4:
					$orderby[] = "t.lastpost DESC";
					break;
				default:
					$orderby[] = "t.dateline DESC";
					break;
			}
			switch ($js['selecttype'])
			{
				case 0:
					$orderby[] = "t.dateline DESC";
					break;
				case 1:
					$query[] = "t.quintessence != 0";
					break;
				default:
					$orderby[] = "t.dateline DESC";
					break;
			}

			if (is_array($orderby))
			{
				$order = "ORDER BY " . implode(", ", $orderby);
			}

			if ($js['trimpagetext'] != -1)
			{
				$add_pagetext = ", p.pagetext";
				$add_leftjoin = "LEFT JOIN " . TABLE_PREFIX . "post p ON (p.newthread=1 AND p.threadid= t.tid)";
			}

			$i = 0;
			$rows = 0;
			$DB->query("SELECT t.*{$add_pagetext} FROM " . TABLE_PREFIX . "thread t {$add_leftjoin} WHERE " . implode(" AND ", $query) . " {$order} {$limit}");
			if ($DB->num_rows())
			{
				while ($t = $DB->fetch_array())
				{
					$rows++;
					if ($js['trimtitle'])
					{
						$titlecolor = "";
						$titlebold = false;
						if (preg_match("#<font[^>]+color=('|\")(.+?)(\\1)>(.+?)</font>#siU", $t['title'], $match))
						{
							$titlecolor = $match[2];
						}
						if (preg_match('#<strong>(.*)</strong>#siU', $t['title']))
						{
							$titlebold = true;
						}
						$t['title'] = $forums->func->fetch_trimmed_title(strip_tags($t['title']), $js['trimtitle']);
						if ($titlecolor)
						{
							$t['title'] = "<font color='{$titlecolor}'>" . $t['title'] . "</font>";
						}
						if ($titlebold)
						{
							$t['title'] = "<strong>" . $t['title'] . "</strong>";
						}
					}
					if ($js['trimdescription'])
					{
						$t['description'] = $forums->func->fetch_trimmed_title(strip_tags($t['description']), $js['trimdescription']);
					}
					if ($js['trimpagetext'] != -1)
					{
						$t['pagetext'] = str_replace("<br>", "<br />", $t['pagetext']);
						$t['pagetext'] = strip_tags($t['pagetext'], "<br />");
						$pagetext = explode("<br />", $t['pagetext']);
						$new_content = array();
						foreach ($pagetext AS $content)
						{
							if (!trim($content)) continue;
							$new_content[] = $content;
						}
						$t['pagetext'] = implode("<br />", $new_content);
						if ($js['trimpagetext'] > 0)
						{
							$t['pagetext'] = $forums->func->fetch_trimmed_title($t['pagetext'], $js['trimpagetext']);
						}
					}
					$htmlcode = str_replace("{c1}", $t['title'], $js['htmlcode']);
					$htmlcode = str_replace("{c2}", $t['description'], $htmlcode);

					$forum = $forums->forum->foruminfo[intval($t['forumid'])];
					$htmlcode = str_replace("{c3}", $forum['name'], $htmlcode);
					$htmlcode = str_replace("{c4}", "{$bboptions['bburl']}/showthread.php?t=" . $t['tid'], $htmlcode);
					$htmlcode = str_replace("{c5}", $t['views'], $htmlcode);
					$htmlcode = str_replace("{c6}", $t['post'], $htmlcode);
					$htmlcode = str_replace("{c7}", "<a href='{$bboptions['bburl']}/profile.php?u={$t['postuserid']}' target='_blank'>" . $t['postusername'] . "</a>", $htmlcode);
					$htmlcode = str_replace("{c8}", $forums->func->get_date($t['dateline'], 2), $htmlcode);
					$htmlcode = str_replace("{c9}", "<a href='{$bboptions['bburl']}/profile.php?u={$t['lastposterid']}' target='_blank'>" . $t['lastposter'] . "</a>", $htmlcode);
					$htmlcode = str_replace("{c10}", $forums->func->get_date($t['lastpost'], 2), $htmlcode);
					$htmlcode = str_replace("{c11}", $t['forumid'], $htmlcode);
					$htmlcode = str_replace("{c12}", $t['pagetext'], $htmlcode);

					if ($add_tr)
					{
						$jscode .= "</tr>\n\n<tr>";
					}
					$add_tr = false;
					$jscode .= "<td>\n";
					$jscode .= $htmlcode . "\n";
					$jscode .= "</td>\n";
					if ($rows == $js['perline'])
					{
						$add_tr = true;
						$rows = 0;
					}
				}
				if ($rows != $js['perline'] AND $rows != 0)
				{
					for ($t = $rows ; $t < $js['perline'] ; ++$t)
					{
						$jscode .= "<td>&nbsp;</td>\n";
					}
				}
			}
			else
			{
				$jscode .= "<td>\n";
				$htmlcode = str_replace("{c1}", "", $js['htmlcode']);
				$htmlcode = preg_replace("#{c(\d+)}#", "", $htmlcode);
				$jscode .= $htmlcode;
				$jscode .= "</td>\n";
			}
		}
		else if ($js['type'] == 1)
		{
			$forums->func->check_cache('creditlist');
			$usedcredit = array();
			if ($forums->cache['creditlist']) 
			{
				foreach ($forums->cache['creditlist'] as $k => $v) 
				{
					$usedcredit[$v['tag']] = $v['name'];
				}
			}

			switch ($js['orderby'])
			{
				case 0:
					$orderby = "ASC";
					break;
				case 1:
					$orderby = "DESC";
					break;
				default:
					$orderby = "DESC";
					break;
			}

			if ($usedcredit[$js['selecttype']])
			{
				$where = "ue." . $js['selecttype'];
			}
			else if ($js['selecttype'] == 0)
			{
				$where = "u.posts";
			}
			else if ($js['selecttype'] == 3)
			{
				$where = "u.joindate";
			}
			else
			{
				return;
			}

			$DB->query("SELECT ue.*, u.* FROM " . TABLE_PREFIX . "user u LEFT JOIN " . TABLE_PREFIX . "userexpand ue ON (u.id = ue.id) ORDER BY {$where} {$orderby} {$limit}");

			if ($DB->num_rows())
			{
				$rows = 0;
				while ($u = $DB->fetch_array())
				{
					$rows++;
					$htmlcode = $bboptions['rewritestatus'] ? str_replace("{c1}", "<a href='{$bboptions['bburl']}/user_{$u['id']}.html' target='_blank'>" . $u['name'] . "</a>", $js['htmlcode']) :str_replace("{c1}", "<a href='{$bboptions['bburl']}/profile.php?u={$u['id']}' target='_blank'>" . $u['name'] . "</a>", $js['htmlcode']);
					$htmlcode = str_replace("{c2}", $u['id'], $htmlcode);

					$htmlcode = str_replace("{c3}", $u['posts'], $htmlcode);
					$htmlcode = str_replace("{c4}", $u['cash'], $htmlcode);
					$htmlcode = str_replace("{c5}", $u['bank'], $htmlcode);
					$htmlcode = str_replace("{c6}", $u['reputation'], $htmlcode);
					$htmlcode = str_replace("{c7}", $forums->func->get_date($u['joindate'], 3), $htmlcode);
					if (is_array($usedcredit))
					{
						foreach ($usedcredit AS $type => $name)
						{
							$htmlcode = str_replace("{" . $type . "}", $u[$type], $htmlcode);
						}
					}

					if ($add_tr)
					{
						$jscode .= "</tr>\n\n<tr>";
					}
					$add_tr = false;
					$jscode .= "<td>\n";
					$jscode .= $htmlcode . "\n";
					$jscode .= "</td>\n";
					if ($rows == $js['perline'])
					{
						$add_tr = true;
						$rows = 0;
					}
				}
				if ($rows != $js['perline'] AND $rows != 0)
				{
					for ($t = $rows ; $t < $js['perline'] ; ++$t)
					{
						$jscode .= "<td>&nbsp;</td>\n";
					}
				}
			}
			else
			{
				$jscode .= "<td>\n";
				$jscode .= "";
				$jscode .= "</td>\n";
			}
		}
		$jscode = str_replace(array('"', "\n", "\r"), array('\"', "", ""), $jscode);
		if ($js['export'])
		{
			switch ($js['export'])
			{
				case 1:
					$output_code = "GBK";
					break;
				case 2:
					$output_code = "BIG5";
					break;
				default:
					break;
			}
			if ($output_code)
			{
				convert_encoding($jscode, 'UTF-8', $output_code);
			}
		}
		$allcode = "document.write(\"<table width='100%'  border='0' cellspacing='0' cellpadding='0'><tr>" . $jscode;
		$allcode .= "</tr></table>\");\n";

		if ($update)
		{
			if ($oldname)
			{
				@unlink(ROOT_PATH . 'data/' . $oldname);
			}
			if ($fp = @fopen(ROOT_PATH . 'data/' . $js['jsname'], 'wb'))
			{
				if (!fwrite($fp, $allcode))
				{
					$forums->lang['can_not_write'] = sprintf($forums->lang['can_not_write'], $js['jsname'], ROOT_PATH . "data/");
					$forums->admin->print_cp_error($forums->lang['can_not_write']);
				}
				fclose($fp);
				@chmod(ROOT_PATH . 'data/' . $js['jsname'], 0777);
			}
		}
		return $allcode;
	}
}

?>