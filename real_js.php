<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'real_js');
require_once('./global.php');

$id = input::get('id', 0);
cache::get('realjs');
if (is_array($forums->cache['realjs'][$id]))
{
	require_once(ROOT_PATH . 'includes/adminfunctions_javascript.php');
	$lib = new adminfunctions_javascript();
	echo $lib->createjs($forums->cache['realjs'][$id], 0);
}