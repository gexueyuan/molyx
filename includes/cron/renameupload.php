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
class cron_renameupload
{
	var $class;
	var $root_path = '';
	var $cron = '';

	function docron()
	{
		global $DB, $forums, $bboptions;
		if (SAFE_MODE) return;
		$forums->func->load_lang('cron');
		$tmp = mt_rand(100000, 999999);
		$cur_path = $bboptions['uploadfolder'] ? str_replace($this->root_path . 'data/', '', $bboptions['uploadfolder']) : 'uploads';
		if (is_dir($this->root_path . 'data/' . $cur_path))
		{
			@rename($this->root_path . 'data/' . $cur_path, $this->root_path . 'data/upload_' . $tmp);
			if (is_dir($this->root_path . 'data/upload_' . $tmp))
			{
				$bboptions['uploadurl'] = $bboptions['bburl'] . '/data/upload_' . $tmp;
				$bboptions['uploadfolder'] = $this->root_path . 'data/upload_' . $tmp;
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "setting SET value='" . $bboptions['uploadurl'] . "' WHERE varname='uploadurl'");
				$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "setting SET value='" . $bboptions['uploadfolder'] . "' WHERE varname='uploadfolder'");
				$forums->cache['settings'] = $bboptions;
				$forums->func->update_cache(array('name' => 'settings', 'array' => 1));
				$this->class->cronlog($this->cron, $forums->lang['renameupload'] . ' - upload_' . $tmp);
			}
		}
	}

	function register_class(&$class)
	{
		$this->class = &$class;
		$this->root_path = $this->class->root_path;
	}

	function pass_cron($this_cron)
	{
		$this->cron = $this_cron;
	}
}

?>