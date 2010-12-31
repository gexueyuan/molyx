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
define('THIS_SCRIPT', 'click');
require_once('./global.php');

$id = input::get('id', 0);
cache::get('ad');
if ($id && $forums->cache['ad']['content'][$id])
{
	$DB->update(TABLE_PREFIX . 'ad', array(
		'click' => array(1, '+')
	), "id = $id");

	/**
	 * @todo 此处需要对url进行域名验证
	 */
	$forums->func->standard_redirect($_GET['url']);
}