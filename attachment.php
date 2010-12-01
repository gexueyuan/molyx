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
define('THIS_SCRIPT', 'attachment');
if (isset($_REQUEST['do']) && $_REQUEST['do'] !== 'showthread')
{
	$content_type = true;
}
require_once('./global.php');

$output = new attachment();

$id = input::get('id', 0);

$forums->func->check_cache('attachmenttype');
switch (input::get('do', ''))
{
	case 'showthread':
		$output->listattachment();
		break;
	case 'showthumb':
		$output->showthumb();
		break;
	default:
		$output->showattachment();
		break;
}

class attachment
{
	function listattachment()
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$forums->func->load_lang('showthread');

		$tid = input::get('tid', 0);
		if (!$tid)
		{
			$forums->func->standard_error("cannotviewattach");
		}
		$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid = $tid");
		if (!$thread['attach'])
		{
			$forums->func->standard_error("cannotviewattach");
		}
		$this->forum = $forums->forum->single_forum($thread['forumid']);
		if (!$this->forum['id'])
		{
			$forums->func->standard_error("cannotviewthispage");
		}
		$posttable = $thread['posttable'] ? $thread['posttable'] : 'post';
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$hidefunc = new hidefunc();
		$attach = array();
		$attachments = $DB->query("SELECT a.*,t.*, p.threadid, p.pid,p.hidepost,p.userid AS puid FROM " . TABLE_PREFIX . "attachment a
			LEFT JOIN " . TABLE_PREFIX . $posttable . " p ON ( a.postid=p.pid )
			LEFT JOIN " . TABLE_PREFIX . "thread t ON ( t.tid=p.threadid )
			WHERE p.threadid = $tid
			ORDER BY a.dateline"
			);
		$canviewattach = true;
		if ($forums->func->fetch_permissions($this->forum['canread'], 'canread') == true)
		{
			$hashidden = false;
			while ($row = $DB->fetch_array($attachments))
			{
				if($hidefunc->hide_attachment($row['userid'],$row['hidetype'],$row['threadid'],'',$row['forumid']))
				{
					$row['hidden'] = 1;
				}
				else
				{
					$row['hidden'] = 0;
				}

				$row['image'] = $forums->cache['attachmenttype'][ $row['extension'] ]['attachimg'];
				$row['shortname'] = $forums->func->fetch_trimmed_title($row['filename']);
				$row['dateline'] = $forums->func->get_date($row['dateline'], 1);
				$row['filesize'] = fetch_number_format($row['filesize'], true);
				$attach[] = $row;
			}
		}
		else
		{
			$canviewattach = false;
		}
		$pagetitle = $forums->lang['attachlist'] . ' -> ' . $bboptions['bbtitle'];
		include $forums->func->load_template('attachment_list');
		exit;
	}

	function showattachment()
	{
		global $DB, $forums, $bbuserinfo, $bboptions,$hidefunc;

		$forums->noheader = 1;

		$tid = input::get('tid', 0);
		$attack = input::get('attach', '');
		if (!$attack || strpos($attack, '..') !== false)
		{
			$forums->func->standard_error("cannotviewattach");
		}
		if (!$bbuserinfo['candownload'])
		{
			$forums->func->standard_error("cannotdownload");
		}

		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$hidefunc = new hidefunc();

		$id = input::get('id', 0);
		$hidetype = $DB->query_first("SELECT hidetype,postid,userid FROM ".TABLE_PREFIX."attachment WHERE attachmentid = $id");

		if(!$hidefunc->hide_attachment($hidetype['userid'],$hidetype['hidetype'], $tid,$hidetype['postid']))
		{
			$forums->func->standard_error("cannotviewattachabout");
		}

		$u = input::get('u', 0);
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		$this->credit->check_credit('downattach', $bbuserinfo['usergroupid'], $this->forum['id']);
		$this->credit->update_credit('downattach', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $this->forum['id']);

		if ($bboptions['remoteattach'])
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $u, -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $bboptions['remoteattach'] . "/" . $subpath;
			$attack = str_replace("\\", "/", $attack);
			$attack = str_replace("/", "", substr($attack, strrpos($attack, '/')));
			$showfile = $subpath . "/" . $attack;
			$forums->func->standard_redirect($showfile);
		}
		else
		{

			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $u, -1, PREG_SPLIT_NO_EMPTY));
			$subpath = input::get('attachpath', $subpath);
			if (strpos($subpath, '..') !== false)
			{
				exit();
			}

			$path = $bboptions['uploadfolder'] . '/' . $subpath;
			$attack = str_replace("\\", "/", $attack);
			$attack = str_replace("/", "", substr($attack, strrpos($attack, '/')));
			$showfile = $path . "/" . $attack;

			$extension = strtolower(input::get('extension', ''));

			if (is_file($showfile) && ($forums->cache['attachmenttype'][$extension]['mimetype'] != ""))
			{
				if ($bboptions['attachmentviewsdelay'])
				{
					if (@$fp = fopen(ROOT_PATH . 'cache/cache/attachmentviews.txt', 'a'))
					{
						fwrite($fp, $id . "\n");
						fclose($fp);
					}
				}
				else
				{
					$DB->shutdown_update(TABLE_PREFIX . 'attachment', array('counter' => array(1, '+')), 'attachmentid = ' . $id);
				}
				$filename = urldecode(input::get('filename', ''));
				$filename = encoding::convert($filename, 'utf-8', 'gbk');

				@header('Content-Type: ' . $forums->cache['attachmenttype'][$extension]['mimetype']);
				@header('Cache-control: max-age=31536000');
				@header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
				@header('Content-Disposition: inline; filename="' . $filename . '"');
				@header('Content-Transfer-Encoding: binary');
				@header('Content-Length: ' . (string)(filesize($showfile)));
				@readfile($showfile);
				exit();
			}
			else
			{
				$forums->func->standard_error("cannotviewattach");
			}
		}
	}

	function showthumb()
	{
		global $DB, $forums, $bbuserinfo, $bboptions;
		$forums->noheader = 1;

		$attach = input::get('attach', '');
		if (!$attach || strpos($attack, '..') !== false)
		{
			$forums->func->standard_error("cannotviewattach");
		}
		if (!$bbuserinfo['candownload'])
		{
			$forums->func->standard_error("cannotdownload");
		}

		$u = input::get('u', 0);
		if ($bboptions['remoteattach'])
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $u, -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $bboptions['remoteattach'] . "/" . $subpath;
			$attach = str_replace("\\", "/", $attach);
			$attach = str_replace("/", "", substr($attach, strrpos($attach, '/')));
			$showfile = $subpath . "/" . $attach;
			$forums->func->standard_redirect($showfile);
		}
		else
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $u, -1, PREG_SPLIT_NO_EMPTY));
			$subpath = input::get('attachpath', $subpath);
			if (strpos($subpath, '..') !== false)
			{
				exit();
			}

			$path = $bboptions['uploadfolder'] . '/' . $subpath;
			$attach = str_replace("\\", "/", $attach);
			$attach = str_replace("/", "", substr($attach, strrpos($attach, '/')));
			$showfile = $path . "/" . $attach;
			$extension = strtolower(input::get('extension', ''));

			$filename = urldecode(input::get('filename', ''));
			$filename = encoding::convert($filename, 'utf-8', 'gbk');

			if (file_exists($showfile) AND ($forums->cache['attachmenttype'][$extension]['mimetype'] != ""))
			{

				@header('Cache-control: max-age=31536000');
				@header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
				@header('Content-Type: ' . $forums->cache['attachmenttype'][$extension]['mimetype']);
				@header('Content-Disposition: inline; filename="' . $filename . '"');
				@header('Content-Transfer-Encoding: binary');
				@header('Content-Length: ' . (string) (filesize($showfile)));
				@readfile($showfile);
				exit();
			}
			else
			{
				return '';
			}
		}
	}
}