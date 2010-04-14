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
class template_import
{
	var $style_version;
	var $style_master;
	var $style_name;
	var $style_title;
	var $style_array;
	var $style_counter;
	var $style_type;
	var $intemplate;
	var $curtag;

	function importxmlstyle($xml = false, $styleid = -1, $parentid = 1, $title = '', $anyversion = 0, $usedefault = 0, $userselect = 1)
	{
		global $DB, $forums, $bboptions;

		if (!$xml)
		{
			$forums->admin->print_cp_error($forums->lang['cannotimportstyle']);
		}

		$this->intemplate = $this->style_counter = 0;
		$this->curtag = '';
		$this->style_array = array();
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_style_otag', 'parse_style_ctag');
		xml_set_character_data_handler($parser, 'parse_style_cdata');

		if (!@xml_parse($parser, $xml))
		{
			$forums->admin->print_cp_error($forums->lang['xmlerror'] . ': ' . xml_error_string(xml_get_error_code($parser)) . ' in line: ' . xml_get_current_line_number($parser));
		}

		xml_parser_free($parser);

		if (empty($this->style_array) || empty($this->style_name) || !preg_match('/^[a-z][a-z0-9_]*$/i', $this->style_name) || ($this->style_master && $this->style_name != 'global'))
		{
			$forums->admin->print_cp_error($forums->lang['styleerror']);
		}

		$version = $this->style_version;
		$master = $this->style_master;
		$name = $this->style_name;
		$title = empty($title) ? $this->style_title : $title;

		if ($version != $bboptions['version'] && !$anyversion && !$master)
		{
			$forums->lang['styleversionnotsame'] = sprintf($forums->lang['styleversionnotsame'], $version, $bboptions['version']);
			$forums->admin->print_cp_error($forums->lang['styleversionnotsame']);
		}

		if ($master)
		{
			$styleid = 1;
			$name = 'global';
		}
		else
		{
			if ($styleid == -1)
			{
				if ($DB->query_first('SELECT styleid
					FROM ' . TABLE_PREFIX . 'style
					WHERE title_en = ' . $DB->validate($name)))
				{
					$forums->lang['styleexist'] = sprintf($forums->lang['styleexist'], $title);
					$forums->admin->print_cp_error($forums->lang['styleexist']);
				}
				else
				{
					$DB->insert(TABLE_PREFIX . 'style', array(
						'title_en' => $name,
						'title' => $title,
						'parentid' => $parentid,
						'userselect' => $userselect,
						'usedefault' => $usedefault
					));
					$styleid = $DB->insert_id();
				}
			}
			else
			{
				if (!$DB->query_first("SELECT styleid
					FROM " . TABLE_PREFIX . "style
					WHERE styleid = $styleid"))
				{
					$forums->admin->print_cp_error($forums->lang['notoveremstyles']);
				}
			}
		}

		$dir = ROOT_PATH . 'templates/' . $name . '/';
		if (!checkdir($dir, 1))
		{
			$forums->admin->print_cp_error($forums->lang['mkdirerror']);
		}

		$querybits = array();
		$querytemplates = 0;
		$css = '';
		foreach ($this->style_array as $type)
		{
			foreach ($type as $title => $template)
			{
				$match = array();
				if ($template['templatetype'] == 'css')
				{
					$template['template'] = unserialize($template['template']);
					if (is_array($template['template']))
					{
						$css .= $title . " {\n";
						foreach ($template['template'] as $key => $value)
						{
							$css .= $key . ': ' . $value . ";\n";
						}
						$css .= "}\n";
					}
					continue;
				}
				else if ($template['templatetype'] == 'stylevars')
				{
					$stylebits[$title] = $template['template'];
					continue;
				}
				else
				{
					file_write($dir . $title . '.htm', $template['template']);
				}
			}
		}

		if ($css)
		{
			file_write($dir . 'style.css', $css);
		}

		if ($styleid != 1)
		{
			$parent = $DB->query_first('SELECT parentlist
				FROM ' . TABLE_PREFIX . "style
				WHERE styleid = $parentid");
			$stylebits['parentlist'] = $styleid . ',' . $parent['parentlist'];
		}

		if ($stylebits)
		{
			$DB->update(TABLE_PREFIX . 'style', $stylebits, 'styleid = ' . $styleid);
		}
	}

	function parse_style_otag($parser, $name, $attrs)
	{
		$this->curtag = $name;
		switch ($name)
		{
			case 'style':
				$this->style_name = $attrs['name'];
				$this->style_title = $attrs['title'] ? $attrs['title'] : ucfirst(str_replace('_', ' ', strtolower($this->style_name)));
				$this->style_version = $attrs['version'];
				$this->style_master = ($attrs['type'] == 'master') ? true : false;
			break;

			case 'template':
				$this->intemplate = 1;
				$this->style_counter = $attrs['name'];
				$this->style_type = $attrs['templatetype'];
				$this->style_array[$this->style_type][$this->style_counter] = array(
					'templatetype' => $attrs['templatetype'],
					'templategroup' => $attrs['group'],
					'template' => ''
				);
			break;
		}
	}

	function parse_style_ctag($parser, $name)
	{
		if ($name == 'template')
		{
			$this->intemplate = 0;
		}
	}

	function parse_style_cdata($parser, $data)
	{
		if ($this->curtag == 'template' && $this->intemplate)
		{
			$this->style_array[$this->style_type][$this->style_counter]['template'] .= $data;
		}
	}
}
?>