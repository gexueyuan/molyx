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
define('THIS_SCRIPT', 'cron');
$content_type = true;
require_once('./global.php');
require_once(ROOT_PATH . 'includes/functions_cron.php');

@ignore_user_abort(1);
@set_time_limit(1200);

$filedata = base64_decode('R0lGODlhAQABAIAAAMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
header('Content-Type: image/gif');
if (!(strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false AND strpos(SAPI_NAME, 'cgi') !== false))
{
	header('Content-Length: ' . strlen($filedata));
	header('Connection: Close');
}
echo $filedata;

$functions = new functions_cron();
$functions->docron();
?>