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
define('THIS_SCRIPT', 'attachment');
if (isset($_REQUEST['do']) && $_REQUEST['do'] !== 'showthread')
{
	$content_type = true;
}
require_once('./global.php');

class attachment
{
	function show()
	{
		global $_INPUT, $forums;
		$_INPUT['id'] = intval($_INPUT['id']);
		$_INPUT['tid'] = intval($_INPUT['tid']);
		$forums->func->check_cache('attachmenttype');
		switch ($_INPUT['do'])
		{
			case 'showthread':
				$this->listattachment();
				break;
			case 'showthumb':
				$this->showthumb();
				break;
			default:
				$this->showattachment();
				break;
		}
	}

	function listattachment()
	{
		global $DB, $forums, $_INPUT, $bboptions, $bbuserinfo;
		$forums->func->load_lang('showthread');
		$_INPUT['tid'] = intval($_INPUT['tid']);
		if (!$_INPUT['tid'])
		{
			$forums->func->standard_error("cannotviewattach");
		}
		$thread = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid={$_INPUT['tid']}");
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
			WHERE p.threadid={$_INPUT['tid']}
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
		global $DB, $forums, $_INPUT, $bbuserinfo, $bboptions,$hidefunc;

		$forums->noheader = 1;

		if (!$_INPUT['attach'])
		{
			$forums->func->standard_error("cannotviewattach");
		}
		if (!$bbuserinfo['candownload'])
		{
			$forums->func->standard_error("cannotdownload");
		}

		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$hidefunc = new hidefunc();

		$hidetype = $DB->query_first("SELECT hidetype,postid,userid FROM ".TABLE_PREFIX."attachment WHERE attachmentid = '".intval(addslashes($_INPUT['id']))."'");

		if(!$hidefunc->hide_attachment($hidetype['userid'],$hidetype['hidetype'],intval($_INPUT['tid']),$hidetype['postid']))
		{
			$forums->func->standard_error("cannotviewattachabout");
		}

		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();
		$this->credit->check_credit('downattach', $bbuserinfo['usergroupid'], $this->forum['id']);
		$this->credit->update_credit('downattach', $bbuserinfo['id'], $bbuserinfo['usergroupid'], $this->forum['id']);
		if ($bboptions['remoteattach'])
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', intval($_INPUT['u']), -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $bboptions['remoteattach'] . "/" . $subpath;
			$_INPUT['attach'] = str_replace("\\", "/", $_INPUT['attach']);
			$_INPUT['attach'] = str_replace("/", "", substr($_INPUT['attach'], strrpos($_INPUT['attach'], '/')));
			$showfile = $subpath . "/" . $_INPUT['attach'];
			$forums->func->standard_redirect($showfile);
		}
		else
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', intval($_INPUT['u']), -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $_INPUT['attachpath'] ? $_INPUT['attachpath'] : $subpath;
			$path = $bboptions['uploadfolder'] . '/' . $subpath;
			$_INPUT['attach'] = str_replace("\\", "/", $_INPUT['attach']);
			$_INPUT['attach'] = str_replace("/", "", substr($_INPUT['attach'], strrpos($_INPUT['attach'], '/')));
			$showfile = $path . "/" . $_INPUT['attach'];
			$_INPUT['extension'] = strtolower($_INPUT['extension']);

			if (is_file($showfile) && ($forums->cache['attachmenttype'][$_INPUT['extension']]['mimetype'] != ""))
			{
				if ($bboptions['attachmentviewsdelay'])
				{
					if (@$fp = fopen(ROOT_PATH . 'cache/cache/attachmentviews.txt', 'a'))
					{
						fwrite($fp, intval($_INPUT['id']) . "\n");
						fclose($fp);
					}
				}
				else
				{
					$DB->shutdown_update(TABLE_PREFIX . 'attachment', array('counter' => array(1, '+')), 'attachmentid = ' . intval($_INPUT['id']));
				}
				$_INPUT['filename'] = urldecode($_INPUT['filename']);
				$_INPUT['filename'] = convert_encoding($_INPUT['filename'], 'utf-8', 'gbk');
				@header('Content-Type: ' . $forums->cache['attachmenttype'][ $_INPUT['extension'] ]['mimetype']);
				@header('Cache-control: max-age=31536000');
				@header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
				@header('Content-Disposition: inline; filename="' . $_INPUT['filename'] . '"');
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
		global $DB, $forums, $_INPUT, $bbuserinfo, $bboptions;
		$forums->noheader = 1;

		if (!$_INPUT['attach'])
		{
			$forums->func->standard_error("cannotviewattach");
		}
		if (!$bbuserinfo['candownload'])
		{
			$forums->func->standard_error("cannotdownload");
		}
		if ($bboptions['remoteattach'])
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', intval($_INPUT['u']), -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $bboptions['remoteattach'] . "/" . $subpath;
			$_INPUT['attach'] = str_replace("\\", "/", $_INPUT['attach']);
			$_INPUT['attach'] = str_replace("/", "", substr($_INPUT['attach'], strrpos($_INPUT['attach'], '/')));
			$showfile = $subpath . "/" . $_INPUT['attach'];
			$forums->func->standard_redirect($showfile);
		}
		else
		{
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', intval($_INPUT['u']), -1, PREG_SPLIT_NO_EMPTY));
			$subpath = $_INPUT['attachpath'] ? $_INPUT['attachpath'] : $subpath;
			$path = $bboptions['uploadfolder'] . '/' . $subpath;
			$_INPUT['attach'] = str_replace("\\", "/", $_INPUT['attach']);
			$_INPUT['attach'] = str_replace("/", "", substr($_INPUT['attach'], strrpos($_INPUT['attach'], '/')));
			$showfile = $path . "/" . $_INPUT['attach'];
			$_INPUT['extension'] = strtolower($_INPUT['extension']);
			if (file_exists($showfile) AND ($forums->cache['attachmenttype'][ $_INPUT['extension'] ]['mimetype'] != ""))
			{
				@header('Cache-control: max-age=31536000');
				@header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
				@header('Content-Type: ' . $forums->cache['attachmenttype'][$_INPUT['extension']]['mimetype']);
				@header('Content-Disposition: inline; filename="' . urldecode($_INPUT['filename']) . '"');
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

$output = new attachment();
$output->show();

?>