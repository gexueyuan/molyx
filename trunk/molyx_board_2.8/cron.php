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
# $Id: cron.php 64 2007-09-07 09:19:11Z hogesoft-02 $
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