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

class language
{
	static private $value = array();
	static private $loaded = array();

	static public function load($name)
	{
		if (!isset(self::$loaded[$name]))
		{
			@include LANGUAGE_DIR . $name . '.php';
			self::$loaded[$name] = true;

			if (!empty($lang))
			{
				self::$value = array_merge(self::$value, $lang);
			}
		}
	}

	static public function &all()
	{
		return self::$value;
	}

	static public function get($name, $autoload = true)
	{
		if (isset(self::$value[$name]))
		{
			return self::$value[$name];
		}
		else if ($autoload && strpos($name, '_') !== false)
		{
			self::load(strstr($name, '_', true));
			return self::get($name, false);
		}

		return $name;
	}
}