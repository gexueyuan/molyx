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
define('THIS_SCRIPT', 'cilck');
require_once('./global.php');

class cilck
{
	function show()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$_INPUT['id'] = intval($_INPUT['id']);
		$forums->func->check_cache('ad');
		if ($_INPUT['id'] AND $forums->cache['ad']['content'][$_INPUT['id']])
		{
			$DB->shutdown_query("UPDATE " . TABLE_PREFIX . "ad SET click = click + 1 WHERE id = {$_INPUT['id']}");
			$forums->func->standard_redirect($_GET['url']);
		}
	}
}

$output = new cilck();
$output->show();

?>