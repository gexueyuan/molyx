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
# $Id: insertlink.php 307 2007-10-19 04:51:45Z sancho $
# **************************************************************************#
define('IN_MXB', true);
define('ROOT_PATH' , './../');
require_once(ROOT_PATH . 'includes/init.php');
header("Content-Type:text/html; charset=UTF-8");

require_once(ROOT_PATH . 'includes/functions.php');
$forums->func = new functions();
$_INPUT = init_input();
$bboptions['language'] = 'en-us';
$forums->func->check_cache('settings');
$bboptions = $forums->cache['settings'];
$forums->func->check_lang();
$forums->func->load_lang('editor');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">@import "mxedlg.css";</style>
<title><?php echo $forums->lang['insert_url'];
?></title>
<script language="JavaScript" type="text/javascript">
<!--
	var agt=navigator.userAgent.toLowerCase();
	isIE=(agt.indexOf("msie")!=-1 && document.all);

function bodyOnLoad()
{
	var tarea = document.getElementById('linktextarea');
	if(isIE && dialogArguments.rng.htmlText)
	{
		document.linkForm.linkText.value = dialogArguments.rng.htmlText;
		tarea.style.visibility = 'hidden';
	} else if (!isIE && window.opener.rng.toString()) {
		document.linkForm.linkText.value = window.opener.rng.toString();
		tarea.style.visibility = 'hidden';
	}
}

function changeType()
{
	var insertForm = document.linkForm;
	var idx = insertForm.linkType.selectedIndex;
	var cValue = insertForm.linkURL.value;
	if(cValue.substr(0,7).toLowerCase()=="http://") {
		cValue=cValue.substr(7);
	} else if(cValue.substr(0,8).toLowerCase()=="https://") {
		cValue=cValue.substr(8);
	} else if(cValue.substr(0,7).toLowerCase()=="mailto:") {
		cValue=cValue.split(":")[1];
	} else if(cValue.substr(0,7).toLowerCase()=="ed2k://") {
		cValue=cValue.substr(7);
	} else if(cValue.substr(0,6).toLowerCase()=="ftp://") {
		cValue=cValue.substr(6);
	} else if(cValue.substr(0,5).toLowerCase()=="news:") {
		cValue=cValue.split(":")[1];
	}
	insertForm.linkURL.value = insertForm.linkType.options[idx].value + cValue;
}

function applyLink() {
	var insertForm = document.linkForm;
	var cV = insertForm.linkURL.value;
	if (cV == '') {
		alert('<?php echo $forums->lang['url_empty'];
?>');
		return false;
	} else if (cV == 'http://' || cV == 'https://' || cV == 'mailto:' || cV == 'ed2k://' || cV == 'ftp://' || cV == 'news:')
	{
		alert('<?php echo $forums->lang['url_error'];
?>');
		return false;
	}
	if (insertForm.linkText.value == '') {
		insertForm.linkText.value = insertForm.linkURL.value;
	}

	var html = '<a href="' + insertForm.linkURL.value + '" target="_blank">' + insertForm.linkText.value + '</a>';
	if (isIE){
		window.returnValue = html;
	} else {
		window.opener.mexcCommand('insertHTML', false, html);
	}
	window.close();
}
//-->
</script>
</head>
<body onload='bodyOnLoad()'>
<form name="linkForm">
<table width='100%' height='100%' align='center' cellpadding='0' cellspacing='0'>
<tr>
<td valign='top' style='padding:5;height:100%'>
	<table width='100%'>
	<tr>
		<td nowrap='nowrap'><?php echo $forums->lang['url'];
?>:</td>
		<td width="100%">
			<select id='linkType' name='linkType' onchange='changeType();'>
				<option value='http://'>http://</option>
				<option value='https://'>https://</option>
				<option value='mailto:'>mailto:</option>
				<option value='ed2k://'>ed2k://</option>
				<option value='ftp://'>ftp://</option>
				<option value='news:'>news:</option>
				<option value=''><?php echo $forums->lang['other_type'];
?></option>
			</select>
			<input type='text' id='linkURL' name='linkURL' size="37" value='http://'>
		</td>
	</tr>
	<tr id='linktextarea'>
		<td nowrap='nowrap'><?php echo $forums->lang['link_text'];
?>:</td>
		<td><input type='text' id='linkText' name='linkText' size="50" value=''></td>
	</tr>
	</table>
</td>
</tr>
<tr>
<td style='padding:6px;' align='right'>
	<button onclick='window.close();'><?php echo $forums->lang['cancel']; ?></button>&nbsp;
	<button onclick='applyLink();'><?php echo $forums->lang['insert_url']; ?></button>
</td>
</tr>
</table>
</form>
</body>
</html>