<?php
require('./global.php');
$do = (isset($_INPUT['xajaxdo'])) ? trim($_INPUT['xajaxdo']) : 'process';
if (!in_array($do, array('process', 'post', 'thread', 'user')))
{
	$forums->func->finish();
	exit('Error request!');
}

//初始化ajax
require_once(ROOT_PATH . 'includes/ajax/xajax.inc.php');
require_once(ROOT_PATH . "includes/ajax/functions.php");
require_once(ROOT_PATH . 'includes/ajax/functions_' . $do . '.php');

$mxajax = new xajax();
//$mxajax->bDebug = true;
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
$response = new xajaxResponse();
$mxajax->processRequests();
$forums->func->finish();
exit();
?>