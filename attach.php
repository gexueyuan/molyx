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
define('THIS_SCRIPT', 'attach');
require_once('./global.php');

$output = new attach();
require_once(ROOT_DIR . 'includes/functions_post.php');
$output->lib = new functions_post(0);
switch (input::get('do', ''))
{
	case 'upload':
		$output->upload();
	break;

	case 'delete':
		$output->delete();
	break;

	default:
		$output->showattach('');
	break;
}

class attach
{
	var $lib;
	var $insert_id;

	function showattach($error)
	{
		global $forums, $bbuserinfo;
		if ($error)
		{
			$errormsg = $error;
			$notajax = 1;
		}
		else
		{
			$errormsg = $this->lib->obj['errors'];
		}

		$upload = $this->lib->fetch_upload_form(input::get('posthash', ''), input::get('pagetype', ''));

		$upload['maxnum'] = intval($bbuserinfo['attachnum']);
		$upload['tmp'] = str_replace(array('\'', "\n"), array('\\\'', ''), $upload['tmp']);

		include $forums->func->load_template('attachment_iframe');
		exit;
	}

	function upload()
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$forums->func->load_lang('post');
		$forums->func->load_lang('error');
		$forum_id = ($_POST['rsargs']['0']) ? intval($_POST['rsargs']['0']) : input::get('f', 0);
		$this->forum = $forums->forum->single_forum($forum_id);
		input::set('num', 0);
		if ($forums->func->fetch_permissions($this->forum['canupload'], 'canupload') == true)
		{
			if ($bbuserinfo['attachlimit'] != -1)
			{
				$this->lib->canupload = 1;
			}
			if (input::get('upload', 0))
			{
				$this->lib->obj['errors'] = '';
				$this->insert_id = $this->lib->process_upload();
				$this->showattach($this->lib->obj['errors']);
			}
		}
		else
		{
			$this->showattach($forums->lang['cannotupload']);
			exit;
		}
	}

	function delete()
	{
		$id = input::get('removeattachid', 0);
		if ($id)
		{
			$this->lib->remove_attachment($id, input::get('posthash', ''));
			$this->showattach('');
		}
	}
}