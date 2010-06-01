<?php
class parse_command
{
	private static $attachment = array();
	private static $link = array();
	private static $code = array();

	public static function option($tag_name, $attribute, &$value)
	{
		self::is_attachment($attribute, $value);
		self::is_link($tag_name, $attribute, $value);
	}

	public static function code($name)
	{
		$name = strtolower(trim($name));
		$code = check_code_type($name);
		if (!$code)
		{
			$name = 'php';
			$code = 'Php';
		}
		self::$code[] = $code;
		return $name;
	}

	public static function get($name)
	{
		return self::$$name;
	}

	private static function is_attachment($attribute, $value)
	{
		$match = array();
		if ($attribute == 'name' && preg_match('/^attach\-([0-9]+)$/', $value, $match))
		{
			self::$attachment[] = $match[1];
		}
	}

	private static function is_link($tag_name, $attribute, $value)
	{
		if ($tag_name == 'a' && $attribute = 'href')
		{
			self::$link[] = $value;
		}
	}
}
?>