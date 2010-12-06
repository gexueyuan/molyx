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

class debug
{
	static private $obl = NULL;

	/**
	 * singleton loader for debugConsole
	 * DO NOT USE, private to debugConsole functions
	 */
	public static function init()
	{
		if (self::$obj === NULL)
		{
			self::$obj = new Debug_Console();
		}
	}

	/**
	 * show debug info of a variable in debugConsole,
	 * add own text for documentation or hints
	 */
	public static function dump($variable, $text)
	{
		self::$obj->dump($variable, $text);
	}

	/**
	 * watch value changes of a variable in debugConsole
	 */
	public static function watch($variable)
	{
		self::$obj->watchVariable($variable);
	}

	/**
	 * show checkpoint info in debugConsole to make sure
	 * that a certain program line has been passed
	 */
	public static function here($message = NULL)
	{
		if (empty($message))
		{
			$message = NULL;
		}

		self::$obj->passedCheckpoint($message);
	}

	/**
	 * starts a new timer clock and returns its handle
	 */
	public static function start($comment = '')
	{
		return self::$obj->startTimer($comment);
	}

	/**
	 * stops and shows a certain timer clock in debugConsole
	 */
	public static function stop($handle = NULL)
	{
		return self::$obj->stopTimer($handle);
	}
}

debug::init();