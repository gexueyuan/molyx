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
function db_error($message = '', $db)
{
	global $bbuserinfo;
	if ($db->return_die == 1)
	{
		$db->failed = 1;
		return;
	}
	//var_dump($db->get_error());exit;
	if ($bbuserinfo['usergroupid'] == 4 || $db->debug)
	{
		$db->error = $message . "\n" . $db->get_error();
		trigger_error($db->error, E_USER_ERROR);
	}
	else
	{
		global $forums, $bboptions;

		if (empty($bboptions['language']))
		{
			$bboptions['language'] = 'zh-cn';
		}

		if (isset($forums))
		{
			$lang = $forums->func->load_lang('db', true);
		}
		else
		{
			@include(ROOT_PATH . "cache/languages/{$bboptions['language']}/db.php");
		}

		$message = $lang['db_errors'] . ": \n\n";
		$message .= $message . "\n\n";
		$message .= $lang['mysql_errors'] . ': ' . $db->error . "\n\n";
		echo "<html><head><title>{$bboptions['bbtitle']} {$lang['mysql_errors']}</title><style type=\"text/css\"><!--.error { font: 11px tahoma, verdana, arial, sans-serif, simsun; }--></style></head>\r\n<body>\r\n<blockquote><p class=\"error\">&nbsp;</p><p class=\"error\"><strong>{$bboptions['bbtitle']} {$lang['db_found_errors']}</strong><br />\r\n";
		$db_sendmail = sprintf($lang['db_sendmail'], $db->technicalemail);
		echo $db_sendmail . "</p>";
		echo "<p class=\"error\">{$lang['db_apologies']}</p>";
		echo "\r\n\r\n</body></html>";
		exit();
	}
}
?>