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
@set_time_limit(0);
require('global.php');
if (!intval($_INPUT['offset']))
{
	?>
	<div align="center">
	<br />
	<br />
		<div style="font-size:14px;color:#ff0000;font-weight:bold;" align="left">
		<ul>
		<li></li>
		<li></li>
		</ul>
		</div>
	<br />
	<form method="post" action="">
	每次更新的记录数：&nbsp;
	<input type="text" name="offset" size="25" value="200" />	
	<input type="submit" name="s" value="确定" />
	</form>
	</div>
	<?php
	exit;
}
$offset = intval($_INPUT['offset']);
$pp = intval($_INPUT['pp']);
$sql = "SELECT id, avatarlocation, name FROM " . TABLE_PREFIX . "user
		WHERE avatarlocation <> ''
	 LIMIT $pp,$offset
";

$q = $DB->query($sql);
$i = 0;
$old_avatar_dir = $bboptions['uploadfolder'] . '/avatar';
$default_avatar_dir = ROOT_PATH . 'images/avatars';
while ($r = $DB->fetch_array($q))
{
	$path = split_todir($r['id'], $bboptions['uploadfolder'] . '/user');	
	checkdir($path[0], $path[1] + 1);
	$new_avatar_dir = $path[0];
	$avatar = '';
	$avatar = strrchr($r['avatarlocation'], '/');
	$avatar = substr($avatar, 1);
	if (strstr($r['avatarlocation'], 'http://')) 
	{
		$content = @file_get_contents($r['avatarlocation']);
		if ($content) 
		{
			file_write($new_avatar_dir . '/' . $avatar, $content, 'wb');	
		}
		else 
		{
			$avatar = '';
		}
	}
	else if($r['avatarlocation'])
	{
		if (file_exists($old_avatar_dir . '/' . $r['avatarlocation']))
		{
			@copy($old_avatar_dir . '/' . $r['avatarlocation'], $new_avatar_dir . '/' . $avatar);
		}
		else 
		{
			$avatar = '';				
		}
	}
	
	if ($avatar)
	{	
		$DB->update(TABLE_PREFIX . 'user', array('avatar' => 1), 'id=' . $r['id']);
		$forums->func->bulid_avatars($avatar, $r['id']);
	}
	else 
	{	
		$DB->update(TABLE_PREFIX . 'user', array('avatar' => 0), 'id=' . $r['id']);	
	}
	@unlink($path[0] . '/' . $avatar);
	echo $r['name'] . '头像更新完毕<br />';
}
$end = $pp + $offset;
if($DB->num_rows($q))
echo "<meta http-equiv=\"refresh\" content=\"0;URL=?pp=".intval($end)."&amp;offset={$offset}\">";
?>




