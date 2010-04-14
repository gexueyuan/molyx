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
class adminfunctions_language
{
	function readjs($file_name)
	{
		$return = array();
		if (file_exists($file_name))
		{
			$array = file($file_name);
			for ($i = 1, $n = count($array); $i < $n; $i++)
			{
				$array[$i] = trim($array[$i]);
				if (empty($array[$i]))
				{
					continue;
				}

				$matches = array();
				preg_match('/^lang_[a-z0-9_-]+\[("|\')(.*)\\1\] = ("|\')(.*)\\3;$/iU', trim($array[$i]), $matches);
				$return[$matches[2]] = str_replace(array("\\'", '\\n'), array("'", "\n"), $matches[4]);
			}
		}

		return $return;
	}

	function get_var_name($file_name)
	{
		$line = '';
		if (file_exists($file_name))
		{
			$fp = fopen($file_name, 'r');
			while (!feof($fp))
			{
		        $line = trim(fgets($fp));
		        if (strpos($line, 'lang_') === 0)
		        {
		        	break;
		        }
		    }
			fclose($fp);
		}

		if (!empty($line))
		{
			$matches = array();
			preg_match('/^(lang_[a-z]+)(?: |\[|=)/iU', $line, $matches);
			return $matches[1];
		}
		else
		{
			return 'lang_' . substr(basename($file_name), 0, 1);
		}
	}

	function writefile($filename, $lang = array(), $arrname = 'lang')
	{
		global $forums;
		if (!checkdir($filename, 1, true))
		{
			$forums->admin->print_cp_error($forums->lang['filecannotwrite']);
		}

		$ext = strrchr($filename, '.');
		if ($ext == '.php')
		{
			$content = '<' . "?php\n\$lang = " . var_export($lang, true) . ";\n?" . '>';
		}
		else
		{
			$content = "var $arrname = [];\n";
			foreach ($lang as $k => $v)
			{
				$v = str_replace(array("'", "\n"), array("\\'", '\\n'), $v);
				$content .= "{$arrname}['$k'] = '$v';\n";
			}
		}
		return file_write($filename, $content);
	}

	function get_fileoptions($dir, $fname = '')
	{
		$fileoptions = array();
		$is_js = $found = false;
		if (is_dir($dir))
		{
			$dh = opendir($dir);
			while (false !== ($name = readdir($dh)))
			{
				$ext = strrchr($name, '.');
				if (is_file($dir . $name) && ($ext == '.php' || $ext == '.js'))
				{
					if ($fname && $fname == $name)
					{
						$found = true;
						if ($ext == '.js')
						{
							$is_js = true;
						}
					}
					$fileoptions[] = array($name, $name);
				}
			}
			closedir($dh);
		}
		if (!$found)
		{
			$fname = 'global.php';
		}

		return array(
			'fileoptions' => $fileoptions,
			'is_js' => $is_js,
			'fname' => $fname
		);
	}
}
?>