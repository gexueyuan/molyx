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
# $Id: ajax.php 361 2007-11-12 02:42:52Z develop_tong $
# **************************************************************************#

// 初始化ajax
require_once(ROOT_PATH . 'includes/ajax/xajax.inc.php');
$mxajax = new xajax(ROOT_PATH . 'mxajax.php');
//$mxajax->debugOn();
$mxajax_register_functions[] = 'changemedo';
$mxajax_register_functions[] = 'switch_editor_mode';
$mxajax_register_functions[] = 'process_form';
$mxajax_register_functions[] = 'send_mailto_user';
$mxajax_register_functions[] = 'announcement';
foreach ($mxajax_register_functions as $func)
{
	$mxajax->registerFunction($func);//注册ajax函数
}
unset($mxajax_register_functions);
$mxajaxscript = $mxajax->getJavascript('', ROOT_PATH . 'includes/ajax/js/xajax.js');//获取ajax的js函数
?>