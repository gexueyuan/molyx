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

function recache_birthdays()
{
	require_once(ROOT_PATH . 'includes/functions_cron.php');
	$func = new functions_cron();
	require_once(ROOT_PATH . 'includes/cron/birthdays.php');
	$cron = new cron_birthdays();
	$cron->register_class($func);
	$cron->docron();
}