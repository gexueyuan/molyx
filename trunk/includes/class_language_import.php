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
class language_import
{
	var $curtag;
	var $lang_array;
	var $lang_filename;
	var $lang_varname;
	var $lang_title;
	var $lang_name;
	var $inlangvar;
	var $importlangids = array();

	function importxmllanguage($xml = false, $title = '', $name = '')
	{
		global $DB, $forums, $bboptions;

		if (!$xml)
		{
			$forums->admin->print_cp_error($forums->lang['cannotimportlanguage']);
		}
		require_once(ROOT_PATH . 'includes/adminfunctions_language.php');

		$this->inlangvar = 0;
		$this->curtag = '';
		$this->lang_array = array();
		$this->lang_filename = $this->lang_varname = '';
		$this->lang_title = $title;
		$this->lang_name = $name;
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_language_otag', 'parse_language_ctag');
		xml_set_character_data_handler($parser, 'parse_language_cdata');

		if (!@xml_parse($parser, $xml))
		{
			$forums->admin->print_cp_error($forums->lang['xmlerror'] . ': ' . xml_error_string(xml_get_error_code($parser)) . ' in line: ' . xml_get_current_line_number($parser));
		}

		xml_parser_free($parser);

		$root = ROOT_PATH . 'languages/';
		require($root . 'list.php');
		if (isset($lang_list[$this->lang_title]))
		{
			$forums->admin->print_cp_error($forums->lang['importlanguagetitleexsit']);
		}

		if (empty($this->lang_array) || empty($this->lang_title) || !preg_match('/^[a-z][a-z0-9_-]*$/i', $this->lang_title))
		{
			$forums->admin->print_cp_error($forums->lang['languageerror']);
		}

		$dir = $root . $this->lang_title . '/';
		if (!checkdir($dir, 1))
		{
			$forums->admin->print_cp_error($forums->lang['filecannotwrite']);
		}

		foreach ($this->lang_array as $filename => $content)
		{
			$lang = array();
			$file = $dir . $filename;
			$is_js = (strrchr($filename, '.') == '.js') ? true : false;
			if (file_exists($file))
			{
				if ($is_js)
				{
					$lang = adminfunctions_language::readjs($file);
				}
				else
				{
					@include($file);
				}
				@chmod($file, 0666);
			}

			$lang = array_merge($lang, $content['variable']);
			adminfunctions_language::writefile($file, $lang, $content['arrname']);
		}
		unset($lang);

		$lang_list[$this->lang_title] = $this->lang_name;
		file_write($root . 'list.php', '<' . "?php\n\$lang_list = " . var_export($lang_list, true) . ";\n?" . '>');
	}

	function parse_language_otag($parser, $name, $attrs)
	{
		$this->curtag = $name;
		switch ($name)
		{
			case 'language':
				$this->lang_title = $this->lang_title ? $this->lang_title : $attrs['title'];
				$this->lang_name = $this->lang_name ? $this->lang_name : $attrs['name'];
			break;

			case 'languagefile':
				$this->lang_array[$attrs['name']]['arrname'] = $attrs['arrname'];
				$this->lang_array[$attrs['name']]['variable'] = array();
				$this->lang_filename = $attrs['name'];
			break;

			case 'languagevar':
				$this->inlangvar = 1;
				$this->lang_varname = $attrs['varname'];
			break;

			case 'languagelist':
			break;
		}
	}

	function parse_language_ctag($parser, $name)
	{
		if ($name == 'languagevar')
		{
			$this->inlangvar = 0;
			$this->lang_varname = '';
		}
	}

	function parse_language_cdata($parser, $data)
	{
		if ($this->curtag == 'languagevar' && $this->inlangvar && $this->lang_varname && $this->lang_filename)
		{
			$this->lang_array[$this->lang_filename]['variable'][$this->lang_varname] = $data;
		}
	}
}
?>