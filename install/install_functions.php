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
$cfg['appname'] = 'MolyX Board';
// $cfg['applogo'] = ROOT_PATH.'install/images/logo.gif';
$cfg['installer_ver'] = 2.0;
$cfg['updater_ver'] = 2.0;

error_reporting(7); // E_ERROR | E_WARNING | E_PARSE
set_magic_quotes_runtime(0);

if (isset($_GET))
	extract($_GET, EXTR_SKIP);
if (isset($_PUT))
	extract($_PUT, EXTR_SKIP);
if (isset($_POST))
	extract($_POST, EXTR_SKIP);

if (!isset($lang))
	$lang = '';

function create_tables($delete_existing, $charset)
{
	global $prefix, $a_lang;
	require(ROOT_PATH . 'install/mysql.php');
	foreach($mysql_data['CREATE'] as $table => $query)
	{
		$query = trim($query);
		if ($query)
		{
			if (strstr($query, 'CREATE TABLE') && $delete_existing)
			{
				ereg('CREATE TABLE ([^ ]*)', $query, $regs);
				mxb_query("DROP TABLE IF EXISTS $regs[1]");
			}
			if (preg_match('/^(CREATE TABLE).*/i', $query) && $charset)
			{
				$query = str_replace(';', '', $query);
				$query = $query . ' default charset=utf8';
			}
			mxb_query($query);
		}
	}
	foreach($mysql_data['INSERT'] AS $table => $query)
	{
		$query = trim($query);
		if ($query)
		{
			mxb_query($query);
		}
	}
}

function geterrno()
{
	$errno = mysql_errno();
	return $errno;
}

function write_access($file)
{
	$fp = @fopen($file, 'wb');
	if (!$fp)
	{
		return false;
	}
	else
	{
		fclose($fp);
		return true;
	}
}

function write_diraccess($dir)
{
	if (!is_dir($dir))
	{
		@mkdir($dir, 0777);
	}
	if (is_dir($dir))
	{
		if ($fp = @fopen($dir . '/test.php', 'wb'))
		{
			@fclose($fp);
			@unlink($dir . '/test.php');
			return 1;
		}
	}
	return 0;
}

function db_exists($dbname)
{
	$dbname = strtolower($dbname);
	$result = mxb_query('SHOW DATABASES');
	while ($row = mysql_fetch_array($result))
	{
		if ($dbname == strtolower($row[0]))
		{
			return true;
		}
	}
	return false;
}

function column_exists($table, $column)
{
	$r_query = mxb_query("DESCRIBE $table");
	while ($query = mysql_fetch_array($r_query))
	{
		if ($query['Field'] == $column)
		{
			return true;
		}
	}
	return false;
}

function mxb_query($query)
{
	$result = mysql_query($query);
	if (mysql_error())
	{
		p_errormsg(lng('error'), sprintf(lng('queryerror'), $query, mysql_error()));
	}
	return $result;
}

function install_allowed()
{
	if (file_exists(ROOT_PATH . 'includes/config.php'))
	{
		return 0;
	}
	else
	{
		return 1;
	}
}

function p_deny_install()
{
	//$linkto = "upgrade.php?lang=" . $_GET['lang'];
	print '
<b>' . lng('denied') . '</b><br />
<br />
' . sprintf(lng('deniedtxt'), $linkto);
}

function p_header($do_update = 0)
{
	global $PHP_SELF, $cfg, $lang;

	$logo = $cfg['applogo'] ? '<img src="' . $cfg['applogo'] . '" />': '';

	@header("Content-Type:text/html; charset=UTF-8");
	print '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>' . $cfg['appname'] . ' ' . lng('script') . ' - Powered By MolyX</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
<!--
.inst_button {  font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; font-size: 9pt}
td {  font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; font-size: 9pt}
input {  font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; font-size: 9pt; font-weight: bold; padding: 3px;margin: 3px;background-color: #ffffff;}
-->
</style>
</head>
<body bgcolor="#3A6EA5" text="#000000" link="#0000FF" vlink="#0033FF" alink="#0000FF">
<form name="theform" method="post" action="' . basename($PHP_SELF) . '">
  <table width="600" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
      <td bgcolor="#D4D0C8" height="1" width="1"><img src="./images/space.gif" width="1" height="1" /></td>
      <td bgcolor="#D4D0C8" height="1" width="1"></td>
      <td bgcolor="#D4D0C8" height="1"></td>
      <td bgcolor="#D4D0C8" height="1" width="1"></td>
      <td bgcolor="#000000" height="1" width="1"></td>
    </tr>
    <tr>
      <td bgcolor="#D4D0C8" height="1" width="1"></td>
      <td bgcolor="#FFFFFF" height="1" width="1"><img src="./images/space.gif" width="1" height="1" /></td>
      <td bgcolor="#FFFFFF" height="1"></td>
      <td bgcolor="#FFFFFF" height="1" width="1"></td>
      <td bgcolor="#000000" height="1" width="1"></td>
    </tr>
    <tr>
      <td bgcolor="#D4D0C8" width="1"></td>
      <td bgcolor="#FFFFFF" width="1"></td>
      <td bgcolor="#D4D0C8">

				<table width="100%" border="0" cellspacing="0" cellpadding="6">
					<tr>
					  <td><b>' . $cfg['appname'] . ' ' . lng('installation') . '</b><br />
						Version: v' . $cfg['installer_ver'] . '</td>
					  <td align="right">' . $logo . '</td>
					</tr>
				  </table>

				  <table width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
					  <td bgcolor="#808080" height="1"><img src="./images/space.gif" width="1" height="1" /></td>
					</tr>
					<tr>
					  <td bgcolor="#FFFFFF" height="1"><img src="./images/space.gif" width="1" height="1" /></td>
					</tr>
				  </table>

	      </td>
      <td bgcolor="#808080" width="1"></td>
      <td bgcolor="#000000" width="1"></td>
    </tr>
  </table>
';

	if (!$do_update)
	{
		print '

  <table width="600" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
      <td bgcolor="#D4D0C8" width="1"></td>
      <td bgcolor="#FFFFFF" width="1"></td>
      <td bgcolor="#D4D0C8">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td>
              <table width="100%" border="0" cellspacing="0" cellpadding="16">
                <tr>
                  <td>';
	}
}

function p_add_tb()
{
	print '
  <table width="600" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
      <td bgcolor="#D4D0C8" width="1"></td>
      <td bgcolor="#FFFFFF" width="1"></td>
      <td bgcolor="#D4D0C8">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td>
              <table width="100%" border="0" cellspacing="0" cellpadding="16">
                <tr>
                  <td>';
}

function show_update($code = '')
{
	print '
  <table width="600" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
      <td bgcolor="#D4D0C8" width="1"></td>
      <td bgcolor="#FFFFFF" width="1"></td>
      <td bgcolor="#D4D0C8">
	   <table width="100%" border="0" cellspacing="0" cellpadding="6">
                <tr>
                  <td><b>' . lng('nowupdatetable') . ': ' . $code . '</b></td>
                </tr>
              </table>
	  </td>
      <td bgcolor="#808080" width="1"></td>
      <td bgcolor="#000000" width="1"></td>
    </tr>
  </table>
	';
	ob_flush();
}

function p_footer($action = '', $vars = 0, $selectlang = 1)
{
	global $lang;

	if ($vars == 0)
		$vars = array();
	if ($selectlang)
	{
		//$langhidden = '<input type="hidden" name="thislang" value="' . $_POST['thislang'] . '" />';
		$langhidden = '<input type="hidden" name="lang" value="zh-cn" />';
	}
	print '
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td bgcolor="#808080" height="1"><img src="./images/space.gif" width="1" height="1" /></td>
                </tr>
                <tr>
                  <td bgcolor="#FFFFFF" height="1"><img src="./images/space.gif" width="1" height="1" /></td>
                </tr>
              </table>
              <br />
              <table width="100%" border="0" cellspacing="0" cellpadding="16">
                <tr>
                  <td><a href="http://www.molyx.com" target="_blank">' . lng('studios') . '</a><br /><a href="http://www.hogesoft.com" target="_blank">' . lng('customsupport') . '</a></td>
                  <td align="right">' . $langhidden . '
				    <input type="hidden" name="action" value="' . $action . '" />
                    ' . ($action != '' ? '<input type="submit" name="next" value="' . lng('next') . ' &gt;" class="inst_button" />' : '&nbsp;');

	while (list($k, $v) = each($vars))
	{
		print '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
	}

	if ($lang)
		print '<input type="hidden" name="lang" value="' . $lang . '" />';

	print '

                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
      <td bgcolor="#808080" width="1"></td>
      <td bgcolor="#000000" width="1"></td>
    </tr>
    <tr>
      <td bgcolor="#D4D0C8" height="1" width="1"></td>
      <td bgcolor="#FFFFFF" height="1" width="1"></td>
      <td bgcolor="#808080" height="1"></td>
      <td bgcolor="#808080" height="1" width="1"><img src="./images/space.gif" width="1" height="1" /></td>
      <td bgcolor="#000000" height="1" width="1"></td>
    </tr>
    <tr bgcolor="#000000">
      <td height="1" width="1"></td>
      <td height="1" width="1"></td>
      <td height="1"></td>
      <td height="1" width="1"></td>
      <td height="1" width="1"><img src="./images/space.gif" width="1" height="1" /></td>
    </tr>
  </table>
</form>
</body>
</html>
';
}

function p_errormsg($title, $message)
{
	p_header();
	print '
<b>' . $title . '</b><br />
<br />
' . $message . '<br />
<br />
<a href="JavaScript:history.back(0)">' . lng('back') . '</a>
';
	p_footer();
	exit;
}

function p_welcome()
{
	echo '<strong>' . lng('welcome') . '</strong>';
	if (file_exists(ROOT_PATH . 'doc/notice_' . $_POST['lang'] . '.txt'))
	{
		$content = file_get_contents(ROOT_PATH . 'doc/notice_' . $_POST['lang'] . '.txt');
		$content = str_replace(array("\r\n", "\n"), array('<br />', '<br />'), $content);
		echo '
      	<div style="margin:10px;padding:15px;border:1px #D4D0C8 outset;">
			' . $content . '
		</div>';
	}
	echo lng('infotxt');
}

function p_authorize()
{
	print lng('authorize');
}

function p_license()
{
	$license = implode('', file(ROOT_PATH . 'doc/license_' . $_POST['lang'] . '.txt'));
	$license = str_replace("\t", '    ', $license);
	$license = str_replace('  ', '&nbsp;&nbsp;', $license);
	print '
                      <b>' . lng('licagreement') . '</b><br />
                      <br />
                      <textarea cols="80" rows="15" readonly="readonly" style="width:100%;nowarp:auto;font-size:9pt">' . $license . '</textarea>
                      <p><label for="accept"><input type="checkbox" name="accept" id="accept" value="yes" />
                      ' . lng('licread') . '</label></p>';
}

function p_diraccess()
{
	print '<ul>';
	if (write_diraccess(ROOT_PATH . 'data'))
	{
		print '<li>./data ' . lng('directory') . lng('canwrite') . '</li>';
		$data_access = 1;
	}
	else
	{
		print '<li><font color="red">./data ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$data_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'data/user'))
	{
		print '<li>./data/user ' . lng('directory') . lng('canwrite') . '</li>';
		$signature_access = 1;
	}
	else
	{
		print '<li><font color="red">./data/user ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$signature_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'data/uploads'))
	{
		print '<li>./data/uploads ' . lng('directory') . lng('canwrite') . '</li>';
		$upload_access = 1;
	}
	else
	{
		print '<li><font color="red">./data/uploads ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$upload_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'data/dbbackup'))
	{
		print '<li>./data/dbbackup ' . lng('directory') . lng('canwrite') . '</li>';
		$dbbackup_access = 1;
	}
	else
	{
		print '<li><font color="red">./data/dbbackup ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$dbbackup_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'cache'))
	{
		print '<li>./cache ' . lng('directory') . lng('canwrite') . '</li>';
		$cache_access = 1;
	}
	else
	{
		print '<li><font color="red">./cache ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$cache_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'cache/cache'))
	{
		print '<li>./cache/cache ' . lng('directory') . lng('canwrite') . '</li>';
		$templates_access = 1;
	}
	else
	{
		print '<li><font color="red">./cache/cache ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$templates_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'cache/templates'))
	{
		print '<li>./cache/templates ' . lng('directory') . lng('canwrite') . '</li>';
		$templates_access = 1;
	}
	else
	{
		print '<li><font color="red">./cache/templates ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$templates_access = 0;
	}
	if (write_diraccess(ROOT_PATH . 'includes'))
	{
		print '<li>./includes ' . lng('directory') . lng('canwrite') . '</li>';
		$includes_access = 1;
	}
	else
	{
		print '<li><font color="red">./includes ' . lng('directory') . lng('cannotwrite') . '</font></li>';
		$includes_access = 1;
	}
	print '</ul>';
	if (!$data_access OR !$signature_access OR !$upload_access OR !$dbbackup_access OR !$cache_access OR !$templates_access OR !$includes_access)
	{
		print '
                      <br />
                      <br />
                      ' . lng('dirnotcontinue');
		p_footer();
	}
	else
	{
		print "
                      <br />
                      <br />
                      " . lng('dircontinue') . "
                      <br />

                     ";
		p_footer('mysqldata');
	}
}

function p_mysqldata()
{
	$config['servername'] = 'localhost';
	$config['dbname'] = '';
	$config['dbusername'] = 'root';
	$config['dbpassword'] = '';
	$config['tableprefix'] = 'mxb_';
	print '
                    <b>' . lng('mysqldata') . '</b><br />
                    <br />
                    ' . lng('entermysqldata') . '<br />
                    <br />
                    <table border="0" cellspacing="0" cellpadding="2">
                      <tr>
                        <td>' . lng('dbtype') . '</td>
                        <td width="10">&nbsp;</td>
                        <td>
                          <select name="dbtype" class="inst_button">
							  <option value="mysql" selected="selected">MySQL</option>
							  <option value="mysqli">MySQLi</option>
							  <option value="pdo">PDO MySQL</option>
							</select>
                        </td>
                      </tr>
                      <tr>
                        <td>' . lng('dbhost') . '</td>
                        <td width="10">&nbsp;</td>
                        <td>
                          <input type="text" name="hostname" class="inst_button" value="' . $config['servername'] . '" />
                        </td>
                      </tr>
                      <tr>
                        <td>' . lng('dbuser') . '</td>
                        <td width="10">&nbsp;</td>
                        <td>
                          <input type="text" name="user" class="inst_button" value="' . $config['dbusername'] . '" />
                        </td>
                      </tr>
                      <tr>
                        <td>' . lng('dbpass') . '</td>
                        <td width="10">&nbsp;</td>
                        <td>
                          <input type="password" name="pass" class="inst_button" value="' . $config['dbpassword'] . '" />
                        </td>
                      </tr>
                    </table>
					<input type="hidden" name="dbname" value="' . $config['dbname'] . '" />
					<input type="hidden" name="tableprefix" value="' . $config['tableprefix'] . '" />';
}

function p_selectdb($databases)
{
	print '
                    <b>' . lng('selectdb') . '</b><br />
                    <br />
                    ' . lng('choosedb') . '<br />
                    <br />
                    <br />
                    <select name="selected_db" size="6" class="inst_button">
					  <option value="_usefield" selected="selected">( ' . lng('usefield') . ' )</option>
					  ' . $databases . '
                    </select>
                    <br />
                    <br />
                    ' . lng('orname') . '<br />
                    <input type="text" name="name_db" class="inst_button" />';
}

function p_chooseprefix($dbname, $tables, $tableprefix)
{
	print '
<b>' . lng('chooseprefix') . '</b><br />
<br />
' . sprintf(lng('tablelist'), $dbname) . '
<ul>';
	while (list(, $v) = @each($tables))
	{
		print '<li>' . $v . '</li>';
	}
	print '</ul>';

	print '
  ' . lng('enterprefix') . '<br />
<input type="text" name="prefix" value="' . $tableprefix . '" class="inst_button" /> (' . lng('dontchange') . ')<br />
<br />
<input type="checkbox" name="delete_existing" value="yes" /> ' . lng('deleteexisting');
}

function p_importstyles()
{
	if (!file_exists(ROOT_PATH . 'install/MolyX-style.xml'))
	{
		$stylefiles = 0;
	}
	else
	{
		$stylefiles = 1;
	}
	/*
	print '
      	<div style="margin:10px;padding:15px;border:1px #D4D0C8 outset;">
			<p><b>' . lng('importstyles') . '</b></p>
			' . ($stylefiles ? lng('styleexists') : lng('stylenotexists')) . '
		</div>';
	*/
	print '
      	<div style="margin:10px;padding:15px;border:1px #D4D0C8 outset;">
			<p><b>' . lng('importstyles') . '</b></p>
		</div>';
}

function p_importlangs()
{
	if (!file_exists(ROOT_PATH . 'install/MolyX-language.xml'))
	{
		$langfiles = 0;
	}
	else
	{
		$langfiles = 1;
	}
	print '
      	<div style="margin:10px;padding:15px;border:1px #D4D0C8 outset;">
			<p><b>' . lng('importlangs') . '</b></p>
			' . ($langfiles ? lng('langexists') : lng('langnotexists')) . '
		</div>';
}

function p_settingsite()
{
	$bburl = 'http://' . $_SERVER['SERVER_NAME'] . substr(SCRIPTPATH, 0, strpos(SCRIPTPATH, '/install/'));
	$homeurl = 'http://' . $_SERVER['SERVER_NAME'];
	print '
<b>' . lng('sitesettings') . '</b><br />
<br />
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td width="60%">' . lng('hometitle') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="hometitle" value="" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('homeurl') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="homeurl" class="inst_button" value="' . $homeurl . '" />
    </td>
  </tr>
  <tr>
    <td>' . lng('bbtitle') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="bbtitle" class="inst_button" value="MolyX BOARD" />
    </td>
  </tr>
  <tr>
    <td>' . lng('bburl') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="bburl" class="inst_button" value="' . $bburl . '" />
    </td>
  </tr>
  <tr>
    <td>' . lng('emailreceived') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="emailreceived" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('emailsend') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="emailsend" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('cookiedomain') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="cookiedomain" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('cookieprefix') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="cookieprefix" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('cookiepath') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="cookiepath" class="inst_button" />
    </td>
  </tr>
</table>';
}

function p_adminprofile()
{
	print '
<b>' . lng('createadmin') . '</b><br />
<br />
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td>' . lng('username') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="admin_user" value="root" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('email') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="text" name="admin_email" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('password') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="password" name="admin_pass" class="inst_button" />
    </td>
  </tr>
  <tr>
    <td>' . lng('twopassword') . '</td>
    <td width="10">&nbsp;</td>
    <td>
      <input type="password" name="twopassword" class="inst_button" />
    </td>
  </tr>
</table>';
}

function p_prewrite($hostname, $user, $pass, $db, $prefix, $superadmin)
{
	$text = sprintf(lng('completingtxt'), $hostname, $user, $pass, $db, $prefix, $superadmin);

	print '
<b>' . lng('completing') . '</b><br />
<br />
' . $text . '';
}

function p_done($update = 0)
{
	global $forums, $PHP_SELF;
	$forumurl = "../index.php?lang=" . $_POST['lang'];
	$admincpurl = "../admin/index.php?lang=" . $_POST['lang'];
	require_once (ROOT_PATH . 'includes/functions.php');
	$forums->func = new functions();
	$forums->func->set_cookie("language", $_POST['lang']);
	$finished = $update ? sprintf(lng('updatefinished'), $forumurl, $admincpurl) : sprintf(lng('finished'), $forumurl, $admincpurl);
	return $finished;
}

function p_updatewelcome($update)
{
	if ($update)
	{
		print '
<b>Updates</b><br />
<br />
' . lng('selectupdate') . '<br />
<br />
  <select class="inst_button" name="scriptname" size="6">';

		while (list(, $scriptname) = each($update))
		{
			print '<option value="' . $scriptname . '">' . $scriptname . '</option>';
		}

		print '
  </select>';
	}
	else
	{
		print lng('noupdates');
	}
}

function p_updateinfo($update)
{
	print'
<b>' . lng('updateinfo') . '</b><br />
<br />
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr>
    <td>' . lng('reqver') . '</td>
    <td><b>' . $update->OldVersion . '</b></td>
  </tr>
  <tr>
    <td>' . lng('newver') . '</td>
    <td><b>' . $update->NewVersion . '</b></td>
  </tr>
  <tr>
    <td>' . lng('author') . '</td>
    <td><b>' . $update->Author . '</b></td>
  </tr>
  <tr>
    <td>' . lng('date') . '</td>
    <td><b>' . $update->Date . '</b></td>
  </tr>
  <tr>
    <td>' . lng('executable') . '</td>
    <td><b>' . ($update->AllowUpdate() ? lng('yes') : lng('no')) . '</b></td>
  </tr>
  <tr>
    <td>' . lng('notes') . '</td>
    <td><b>' . ($update->Notes ? $update->Notes : lng('na')) . '</b></td>
  </tr>
</table>';
}

function p_loginform($loginsys = true)
{
	global $a_lang;
	if (!$loginsys)
	{
		$str = lng('enteradminname');
	}
	print '
<b>' . lng('login') . '</b><br />' . $str . '
<br />
<table cellspacing="0" cellpadding="2" border="0">
  <tr>
    <td>' . lng('username') . '</td>
	<td width="10">&nbsp;</td>
	<td><input type="text" name="l_username" class="inst_button" /></td>
  </tr>
  <tr>
    <td>' . lng('password') . '</td>
	<td width="10">&nbsp;</td>
	<td><input type="password" name="l_userpassword" class="inst_button" /></td>
  </tr>
</table>';
}

function p_selectlang($error = '', $lang = '', $type = 'install')
{
	global $a_lang;
	if ($error)
	{
		echo "<font color='#ff0000'>" . $a_lang['install']['selectlanguage'] . "!!!</font><br />";
	}
	else
	{
		echo $a_lang['install']['selectlanguage'] . ': <br />';
	}
	echo '
<br />
<select name="lang" class="inst_button" onChange="document.location.href=\'' . ROOT_PATH . 'install/' . $type . '.php?lang=\'+this.options[this.options.selectedIndex].value;">';
	if (file_exists(ROOT_PATH . 'install/install_lang.php'))
	{
		require(ROOT_PATH . 'install/install_lang.php');
		foreach ($langs AS $k => $v)
		{
			if ($k == $lang || $k == $_GET['lang'])
				echo '<option value="' . $k . '" selected>' . $v . ' </option>';
			else
				echo '<option value="' . $k . '">' . $v . ' </option>';
		}
		echo '</select>';
	}

	print '</select>';
}

function lng($str)
{
	global $lang, $a_lang;

	$lng = 'install';
	if ($a_lang[$lng][$str])
		return $a_lang[$lng][$str];
	else
		return 'Can not find Language file ' . $str;
}

function config_file($config)
{
	return '<?php
$config = ' . var_export($config, true) . ';

define(\'MAX_ONLINE_USERS\', 500); // 在线人数达到这里定义的数字与后台常规设置中的较小值后隐藏在线列表
define(\'DISPLAY_ERRORS\', false); // 是否显示错误信息, 建议正常运行时关闭, 开启本项将同时开启自定义错误处理
//define(\'ERROR_LOG\', true); // 错误记录, 建议在无法显示错误的环境下调试时打开
define(\'DEVELOPER_MODE\', false); // 开发者模式, 不要在正常运行时开启, 否则可能泄露影响安全的信息

//define(\'EMPTY_SQL_MODE\', true); // 清除 SQL 模式设置, 提高在 MySQL5 下的兼容性
//define(\'GUEST_PAGE_CACHE\', true); // 游客页面缓存, 设置见 includes/page_cache.php
?>';
}
?>