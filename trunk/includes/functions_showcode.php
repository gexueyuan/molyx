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
class functions_showcode
{
	var $rc;
	var $fu = null;

	function showantispam()
	{
		global $forums, $DB, $bboptions;

		if ($bboptions['useantispam'])
		{
			$regimagehash = md5(uniqid(microtime()));
			$imagestamp = mt_rand(1000, 9999);
			$DB->insert(TABLE_PREFIX . 'antispam', array(
				'regimagehash' => $regimagehash,
				'imagestamp' => $imagestamp,
				'host' => IPADDRESS,
				'dateline' => TIMENOW
			));
			if ($bboptions['enableantispam'] == 'gd')
			{
				$show['gd'] = true;
				return array('imagehash' => $regimagehash, 'text' => 1);
			}
			else
			{
				$this->rc = $regimagehash;
				return array('imagehash' => $regimagehash, 'text' => $this->showimage());
			}
		}
	}

	function construct_extrabuttons()
	{
		global $forums, $DB, $_INPUT, $bboptions, $bbuserinfo;
		$forums->func->check_cache('bbcode');
		if ($bbuserinfo['canuseflash'])
		{
			$forums->cache['bbcode']['flash'] = array('bbcodetag' => 'flash', 'imagebutton' => 'images/editor/flash.gif');
		}
		$arraynum = count($forums->cache['bbcode']);
		$i = 1;
		foreach ($forums->cache['bbcode'] AS $bbcode)
		{
			if ($bbcode['imagebutton'])
			{
				$bbcode['bbcodetag'] = strtolower($bbcode['bbcodetag']);
				$alt = sprintf($forums->lang['_inserttags'], $bbcode['bbcodetag']);
				if ($bbcode['twoparams'] == 1)
				{
					$extrabuttons[] = 'true';
					if ($i < $arraynum)
					{
						$buttonpush .= "'" . $bbcode['bbcodetag'] . "',";
						$alts .= "'" . $alt . "',";
						$sty .= "'',";
						$i++;
					}
					else
					{
						$buttonpush .= "'" . $bbcode['bbcodetag'] . "'";
						$alts .= "'" . $alt . "'";
						$sty .= "''";
					}
				}
				else
				{
					$extrabuttons[] = 'false';
					if ($i < $arraynum)
					{
						$buttonpush .= "'" . $bbcode['bbcodetag'] . "',";
						$alts .= "'" . $alt . "',";
						$sty .= "'',";
						$i++;
					}
					else
					{
						$buttonpush .= "'" . $bbcode['bbcodetag'] . "'";
						$alts .= "'" . $alt . "'";
						$sty .= "''";
					}
				}
			}
		}
		$extrabuttons = implode(",", (array) $extrabuttons);
		$extrabuttons = "[[" . $buttonpush . "],[" . $alts . "],[" . $extrabuttons . "],[" . $sty . "]]";
		return $extrabuttons;
	}

	function showimage($simg = 0)
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$rc = (isset($_INPUT['rc']) && $_INPUT['rc']) ? trim($_INPUT['rc']) : trim($this->rc);
		if ($rc == '')
		{
			return false;
		}
		$sql = 'SELECT *
			FROM ' . TABLE_PREFIX . 'antispam
			WHERE regimagehash = ' . $DB->validate($rc);
		if (!$row = $DB->query_first($sql))
		{
			return false;
		}

		if (is_null($this->fu))
		{
			require_once(ROOT_PATH . 'includes/functions_user.php');
			$this->fu = new functions_user();
		}

		if ($bboptions['enableantispam'] == 'gd')
		{
			$this->fu->show_gd_img($row['imagestamp'], $simg);
		}
		else
		{
			return $this->fu->show_gif_img($row['imagestamp']);
		}
	}
}
?>