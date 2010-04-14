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
header("Content-type: text/vnd.wap.wml");
echo("<?xml version=\"1.0\"?>\n");
echo("<!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\"
\"http://www.wapforum.org/DTD/wml_1.1.xml\">\n\n");

?>

<wml>
<card id="init" title="Client Info">
<p>
<?php
$headers = getallheaders();
foreach ($headers as $header => $value)
{
	echo strtoupper($header) . ": " . $value . "<br/>\n";
}
echo("REMOTE_ADDR: " . $REMOTE_ADDR . "<br/>\n");
echo("REMOTE_PORT: " . $REMOTE_PORT . "<br/>\n");
echo("REMOTE_USER: " . $REMOTE_USER . "<br/>\n");
echo("GATEWAY_INTERFACE: " . $GATEWAY_INTERFACE . "<br/>\n");
echo("SERVER_PROTOCOL: " . $SERVER_PROTOCOL . "<br/>\n");
echo("REQUEST_METHOD: " . $REQUEST_METHOD . "<br/>\n");
echo("HTTP_CONNECTION: " . $HTTP_CONNECTION . "<br/>\n");
echo("HTTP_VIA: " . $HTTP_VIA . "<br/>\n");
// &#x79FB;&#x52A8;&#x9002;&#x914D;&#xFF1A; HTTP_X_UP_CALLING_LINE_ID
// &#x8054;&#x901A;&#x9002;&#x914D;&#xFF1A;HTTP_X_WAP_CLIENTID
?></p></card></wml>

