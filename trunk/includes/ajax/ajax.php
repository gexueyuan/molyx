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