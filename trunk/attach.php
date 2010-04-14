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
define('THIS_SCRIPT', 'attach');
require_once('./global.php');
class attach
{
	var $insert_id;
	function show()
	{
		global $_INPUT;
		require ROOT_PATH . "includes/functions_post.php";
		$this->lib = new functions_post(0);
		switch ($_INPUT['do'])
		{
			case 'upload':
				$this->upload();
				break;
			case 'delete':
				$this->delete();
				break;
			default:
				$this->showattach('');
				break;
		}
	}

	function showattach($error)
	{
		global $forums, $_INPUT;
		if ($error)
		{
			$errormsg = $error;
			$notajax = 1;
		}
		else
		{
			$errormsg = $this->lib->obj['errors'];
		}

		$upload = $this->lib->fetch_upload_form($_INPUT['posthash'], $_INPUT["pagetype"]);

		$upload['maxnum'] = intval($bbuserinfo['attachnum']);
		$upload['tmp'] = str_replace(array('\'', "\n"), array('\\\'', ''), $upload['tmp']);

		include $forums->func->load_template('attachment_iframe');
		exit;
	}

	function upload()
	{
		global $DB, $forums, $_INPUT, $bboptions, $bbuserinfo;
		$forums->func->load_lang('post');
		$forums->func->load_lang('error');
		$forum_id = ($_POST['rsargs']['0']) ? intval($_POST['rsargs']['0']) : intval($_INPUT['f']);
		$this->forum = $forums->forum->single_forum($forum_id);
		$_INPUT['num'] = 0;
		if ($forums->func->fetch_permissions($this->forum['canupload'], 'canupload') == true)
		{
			if ($bbuserinfo['attachlimit'] != -1)
			{
				$this->lib->canupload = 1;
			}
			if ($_INPUT['upload'])
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
		global $DB, $forums, $_INPUT, $bboptions, $bbuserinfo;
		if ($_INPUT['removeattachid'])
		{
			$this->lib->remove_attachment(intval($_INPUT['removeattachid']), $_INPUT['posthash']);
			$this->showattach('');
		}
	}
}

$output = new attach();
$output->show();

?>