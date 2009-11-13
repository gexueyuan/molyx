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
# $Id: showsource.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
define('THIS_SCRIPT', 'showsource');
require_once('./global.php');

if (!defined('DEVELOPER_MODE') || !DEVELOPER_MODE || !isset($_GET['file']) || strpos(',' . SUPERADMIN . ',', ",{$bbuserinfo['id']},") === false)
{
	exit();
}

$file = urldecode($_GET['file']);
$line = isset($_GET['line']) ? intval($_GET['line']) : 0;
$prev = isset($_GET['prev']) ? intval($_GET['prev']) : 10;
$next = isset($_GET['next']) ? intval($_GET['next']) : 10;

hl_source($file, $line, $prev, $next);

/**
 * 显示一个文件的部分代码
 *
 * @param string $file 文件名
 * @param int $line 要标注的行
 * @param int $prev 在主行前面显示的行数
 * @param int $next 在主行后面显示的行数
 * @return string
 */
function hl_source($file, $line, $prev = 10, $next = 10)
{
	if (!is_file($file))
	{
		exit();
	}

	global $forums;
	$forums->func->load_lang('debug');

	// 读取代码
	$data = highlight_file($file, true);

	// 分割行
	$data  = explode('<br />', $data);
	$count = count($data) - 1;

	// 计算显示行
	if ($prev < 0)
	{
		$prev = 0;
	}

	if ($next < 0)
	{
		$next = 0;
	}

	$start = $line - $prev;
	if ($start < 1)
	{
		$start = 1;
	}
	$end = $line + $next;
	if ($end > $count)
	{
		$end = $count + 1;
	}

	$full_prev = $line;
	$full_next = $count - $line + 1;

	echo '<style type="text/css">
	.button {font-size: 12px; text-decoration: none; display: inline-block; padding: 0 3px 0 3px; border: 1px solid;}
	</style>';
	// 显示
	if ($prev != $full_prev)
	{
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . ($prev + 1) . '&next=' . $next . '#' . ($line - 15) . '">+</a>';
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . ($prev + 10) . '&next=' . $next . '#' . ($line - 15) . '">+10</a>';
	}

	if ($prev != 0)
	{
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . ($prev - 1) . '&next=' . $next . '#' . ($line - 15) . '">-</a>';
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . ($prev - 10) . '&next=' . $next . '#' . ($line - 15) . '">-10</a>';
	}

	echo '<br />';
	echo '<table cellspacing="0" cellpadding="0" style="font-size: 12px;"><tr>';
	echo '<td style="vertical-align: top;"><code style="background-color: #FFFFCC; color: #666666;">';

	for ($i = $start; $i <= $end; $i++)
	{
		echo '<span style="height: 20px;">';
		echo '<a name="' . $i . '"></a>';
		echo ($line == $i) ? '<span style="background-color: #FF0000; color: #FFFFFF;">' : '';
		echo str_repeat('&nbsp;', (strlen($end) - strlen($i)) + 1);
		echo $i;
		echo '&nbsp;';
		echo ($line == $i) ? '</span>' : '';
		echo '</span>';
		echo '<br />';
	}
	echo '</code></td><td style="vertical-align: top;" nowrap="nowrap"><code>';
	$t = $start;
	while ($start <= $end)
	{
		echo '<span style="height: 20px;">';
		echo '&nbsp;' . $data[$start - 1];
		echo '</span>';
		echo '<br />';
		++$start;
	}
	echo '</code></td>';
	echo '</tr></table>';

	if ($next != $full_next)
	{
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . $prev . '&next=' . ($next + 1) . '#' . ($line - 15) . '">+</a>';
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . $prev . '&next=' . ($next + 10) . '#' . ($line - 15) . '">+10</a>';
	}

	if ($next != 0)
	{
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . $prev . '&next=' . ($next - 1) . '#' . ($line - 15) . '">-</a>';
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . $prev . '&next=' . ($next - 10) . '#' . ($line - 15) . '">-10</a>';
	}

	if ($prev != $full_prev || $next != $full_next)
	{
		echo '&nbsp;';
		echo '<a class="button" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($file) . '&line=' . $line . '&prev=' . $full_prev . '&next=' . $full_next . '#' . ($line - 15) . '">' . $forums->lang['view_full_source'] . '</a>';
	}
}
?>