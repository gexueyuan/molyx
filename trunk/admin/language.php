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
require ('./global.php');

class language
{
	var $langfunc = '';
	var $root = '';

	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditlang'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$this->root = ROOT_PATH . 'languages/';
		require_once ROOT_PATH . "includes/adminfunctions_language.php";
		$this->langfunc = new adminfunctions_language();
		switch ($_INPUT['do'])
		{
			case 'translate':
				$this->translateform();
				break;
			case 'addlanguage':
				$this->addlanguage('add');
				break;
			case 'editlanguage':
				$this->addlanguage('edit');
				break;
			case 'editmutivar':
				$this->editmutivar('edit');
				break;
			case 'doeditmutivar':
				$this->do_editmutivar('edit');
				break;
			case 'addvarname':
				$this->editmutivar('add');
				break;
			case 'doaddvarname':
				$this->do_editmutivar('add');
				break;
			case 'dotranslate':
				$this->dotranslate();
				break;
			case 'doaddlanguage':
				$this->doaddlanguage('add');
				break;
			case 'doeditlanguage':
				$this->doaddlanguage('edit');
				break;
			case 'delete':
				$this->deletevar();
				break;
			case 'dellang':
				$this->dellang();
				break;
			case 'setdefault':
				$this->setdefault();
				break;
			case 'modifyvar':
				$this->modifyvar();
				break;
			case 'modifyfile':
				$this->modifyfile();
				break;
			case 'editfile':
				$this->editfile();
				break;
			case 'doeditfile':
				$this->doeditfile();
				break;
			case 'langxml':
				$this->langxml();
				break;
			case 'exportlangxml':
				$this->exportlangxml();
				break;
			case 'importlangxml':
				$this->importlangxml();
				break;
			case 'search':
				$this->search_form();
				break;
			case 'dosearch':
				$this->dosearch();
				break;
			default:
				$this->langlist();
				break;
		}
	}

	/**
	 * 语言管理
	 */
	function langlist()
	{
		global $forums, $bboptions;
		$detail = $forums->lang['languagemanagedesc'];
		$pagetitle = $forums->lang['languagemanage'];
		$forums->admin->nav[] = array('language.php' , $forums->lang['languagemanage']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->print_form_header(array(1 => array('do', 'addlanguage')));
		$forums->admin->columns[] = array($forums->lang['language'] , "40%");
		$forums->admin->columns[] = array($forums->lang['action'] , "60%");
		$forums->admin->print_table_start($forums->lang['languagelist']);

		$dir = $this->root;
		require($dir . 'list.php');
		$dh = opendir($dir);
		while (false !== ($name = readdir($dh)))
		{
			if ($name != '.' && $name != '..' && is_dir($dir . $name) && !isset($lang_list[$name]))
			{
				$lang_list[$name] = false;
			}
		}
		closedir($dh);

		foreach ($lang_list as $k => $v)
		{
			$button = "<a href='language.php?{$forums->sessionurl}do=editlanguage&amp;languageid=$k'>{$forums->lang['edit']}</a>&nbsp;<a href='language.php?{$forums->sessionurl}do=translate&amp;languageid=$k'>{$forums->lang['translate']}</a>&nbsp;";

			if ($v === false)
			{
				$name = '<font color="#EEE">' . $k . '</font>';
				$button = "<a href='language.php?{$forums->sessionurl}do=addlanguage&amp;languageid=$k'>{$forums->lang['addlanguage']}</a>";
			}
			else if ($k == $bboptions['default_lang'])
			{
				$name= '<font color="#FF0000">' . $v . '</font>';
			}
			else
			{
				$name = $v;
				$button .= "<a href='language.php?{$forums->sessionurl}do=dellang&amp;languageid=$k' onclick='if (!confirm(\"{$forums->lang['confirmdelete']}$v{$forums->lang['language']}?\")) return false;'>" . $forums->lang['delete'] . "</a>&nbsp;<a href='language.php?{$forums->sessionurl}do=setdefault&amp;defaultlang=$k'>" . $forums->lang['defaultlang'] . "</a>";
			}

			$forums->admin->print_cells_row(array("<strong>$name</strong>", '<div align="center">' . $button . '</div>'));
		}

		$forums->admin->print_form_submit($forums->lang['addlanguage']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 搜索表单
	 */
	function search_form()
	{
		global $forums, $DB, $_INPUT, $bboptions;

		$langoptions = array();
		$langoptions[] = array(-1, $forums->lang['alllanglist']);
		$dir = $this->root;
		require($dir . 'list.php');
		foreach ($lang_list as $k => $v)
		{
			$langoptions[] = array($k, $v);
		}

		$dir .= $bboptions['default_lang'] . '/';
		extract($this->langfunc->get_fileoptions($dir));
		array_unshift($fileoptions, array(-1, $forums->lang['alllangfile']));

		$pagetitle = $forums->lang['langsearch'];
		$forums->admin->nav[] = array('language.php?do=search', $forums->lang['langsearch']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(1 => array('do', 'dosearch')));
		$forums->admin->columns[] = array('&nbsp;', '35%');
		$forums->admin->columns[] = array('&nbsp;', '65%');
		$forums->admin->print_table_start($forums->lang['langsearch']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['langsearchvalue'] . "</strong>", "<div align='left'>".$forums->admin->print_input_row('searchkey')."</div>"));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['inlanguage'] . "</strong>", "<div align='left'>".$forums->admin->print_input_select_row('lname', $langoptions, -1)."</div>"));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['inlangfile'] . "</strong>", "<div align='left'>".$forums->admin->print_multiple_select_row('fname[]', $fileoptions, array(-1))."</div>"));
		$typebutton  = "<input type='radio' name='searchtype' value='1' />&nbsp;{$forums->lang['onlylangvariable']}&nbsp;<br><br>";
		$typebutton .= "<input type='radio' name='searchtype' value='2' />&nbsp;{$forums->lang['onlylangvalue']}&nbsp;<br><br>";
		$typebutton .= "<input type='radio' name='searchtype' value='3' checked='checked'/>&nbsp;{$forums->lang['langvarandvalue']}&nbsp;";
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['insomethingsearch'] . "</strong>", "<div align='left'>".$typebutton."</div>"));
		$forums->admin->print_form_submit($forums->lang['search']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 进行搜索
	 * 从数据库中搜索
	 */
	function dosearch()
	{
		global $forums, $_INPUT;
		$searchkey = isset($_POST['searchkey']) ? $_POST['searchkey'] : urldecode($_GET['searchkey']);
		$searchkey = trim($searchkey);
		$searchtype = intval($_INPUT['searchtype']);

		if (empty($searchkey))
		{
			$forums->admin->print_cp_error($forums->lang['inputkeywords']);
		}

		$fname = $_INPUT['fname'];
		if ($fname == -1 || $fname[0] == -1 || empty($fname))
		{
			$files = -1;
		}
		else if (!is_array($fname))
		{
			$files = explode(',', urldecode($fname));
		}
		else
		{
			$files = $fname;
			$fname = implode(',', $fname);
		}


		$root = $this->root;
		require($root . 'list.php');
		$lname = trim($_INPUT['lname']);
		if ($lname == -1 || empty($lname) || !isset($lang_list[$lname]))
		{
			$langs = array_keys($lang_list);
		}
		else
		{
			$langs = array($lname);
		}

		if ($lname == -1 && ($files == -1 || count($files) > 5) && strlen($searchkey) < 4)
		{
			$forums->admin->print_cp_error($forums->lang['keywordstooshort']);
		}

		$check_key = $check_value = true;
		if ($searchtype == 1)
		{
			$check_value = false;
		}
		elseif ($searchtype == 2)
		{
			$check_key = false;
		}

		$encode_key = urlencode($searchkey);
		$query = "do=dosearch&amp;lname=$lname&amp;fname=$fname&amp;searchkey=$encode_key&amp;searchtype=$searchtype";

		$pagetitle = $forums->lang['langsearch'];
		$forums->admin->nav[] = array("language.php?do=search", $forums->lang['langsearch']);
		$forums->admin->nav[] = array("language.php?$query", $forums->lang['searchresult']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->columns[] = array($forums->lang['langvarname'], '22%');
		$forums->admin->columns[] = array($forums->lang['inlanguage'], '25%');
		$forums->admin->columns[] = array($forums->lang['langtranslatevalue'], '45%');
		$forums->admin->columns[] = array($forums->lang['action'], '8%');
		$forums->admin->print_table_start($forums->lang['searchresult']);

		foreach ($langs as $l)
		{
			$dir = $root . $l . '/';
			$dh = opendir($dir);
			while (false !== ($name = readdir($dh)))
			{
				if (is_file($dir . $name))
				{
					if ($files != -1 && !in_array($name, $files))
					{
						continue;
					}

					$ext = strrchr($name, '.');
					if ($ext == '.js')
					{
						$lang = $this->langfunc->readjs($dir . $name);
					}
					else if ($ext == '.php')
					{
						include($dir . $name);
					}

					foreach ($lang as $k => $v)
					{
						$found = false;
						if ($check_key)
						{
							if (strpos($k, $searchkey) !== false)
							{
								$found = $k;
								$k = str_replace($searchkey, '<span style="color:#FF0000">' . $searchkey . '</span>', $k);
							}
						}

						if ($check_value)
						{
							if (strpos($v, $searchkey) !== false)
							{
								if (!$found)
								{
									$found = $k;
								}
								$v = str_replace($searchkey, '<span style="color:#FF0000">' . $searchkey . '</span>', $v);
							}
						}

						if ($found)
						{
							$editurl = "language.php?{$forums->sessionurl}do=editmutivar&amp;var=$found&amp;searchkey=$encode_key&amp;lname=$lname&amp;fname=$fname&amp;searchtype=$searchtype&amp;redurl=dosearch";
							$forums->admin->print_cells_row(array('<strong>' . $k . '</strong>', '<div align="center">' . $lang_list[$l] . '</div>', '<div align="left">' . $v . '</div>', '<div align="center"><a href="' . $editurl . '">' . $forums->lang['translate'] . '</a></div>'));
						}
					}
				}
			}
			closedir($dh);
		}
		unset($lang);

		$forums->admin->print_cells_single_row('&nbsp;', 'right', 'pformstrip');
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 设置默认语言
	 */
	function setdefault()
	{
		global $forums, $DB, $_INPUT;
		$defaultlang = $_INPUT['defaultlang'];
		if (empty($defaultlang))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		require($this->root . 'list.php');
		if (!isset($lang_list[$defaultlang]))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		$DB->update(TABLE_PREFIX . 'setting', array('value' => $defaultlang), "varname = 'default_lang'");
		$forums->func->recache('settings');
		$forums->admin->redirect("language.php", $forums->lang['languagemanage'], $forums->lang['defaultlangsetsucess']);
	}

	/**
	 * 翻译表单
	 */
	function translateform()
	{
		global $forums, $_INPUT, $bboptions;
		$language = trim($_INPUT['languageid']);
		$fname = trim($_INPUT['fid']);

		$root = $this->root;
		require($root . 'list.php');
		$langoptions = array();
		foreach ($lang_list as $k => $v)
		{
			$langoptions[] = array($k, $v);
		}

		$default_lang = $bboptions['default_lang'];
		$default_lang = $root . $default_lang . '/';
		$this_lang = !isset($lang_list[$language]) ? $default_lang : $language;
		$this_lang = $root . $this_lang . '/';

		extract($this->langfunc->get_fileoptions($this_lang, $fname));

		$pagetitle = $forums->lang['languagetranslate'];
		$forums->admin->nav[] = array('language.php', $forums->lang['languagemanage']);
		$forums->admin->nav[] = array('language.php?do=translate&amp;languageid=' . $language . '&amp;fid=' . $fname, $forums->lang['languagetranslate']);
		$forums->admin->print_cp_header($pagetitle, '');
		$forums->admin->print_form_header(array(1 => array('do', 'translate')));
		$forums->admin->columns[] = array('&nbsp;', '35%');
		$forums->admin->columns[] = array('&nbsp;', '65%');
		$forums->admin->print_table_start($forums->lang['languagetranslate']);
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['inlanguage'] . '</strong>',
			'<div align="center">' . $forums->admin->print_input_select_row('languageid', $langoptions, $language, 'onchange="this.form.submit();"') . '</div>'
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['inlangfile'] . '</strong>',
			'<div align="center">' . $forums->admin->print_input_select_row('fid', $fileoptions, $fname, 'onchange="this.form.submit();"') . '</div>'
		));
		$forums->admin->print_form_end();
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(
			1 => array('do', 'dotranslate'),
			2 => array('fid', $fname),
			4 => array('languageid', $language),
		));
		$forums->admin->columns[] = array('&nbsp;', '30%');
		$forums->admin->columns[] = array('&nbsp;', '70%');
		$forums->admin->print_table_start("<strong>{$forums->lang['languagetranslate']}</strong>");
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['langvarname'] . '</strong>',
			'<strong>' . $forums->lang['langtranslatevalue'] . '</strong>'
		));

		if ($is_js)
		{
			$var = $this->langfunc->readjs($this_lang . $fname);
			if ($default_lang != $this_lang)
			{
				$default_var = $this->langfunc->readjs($default_lang . $fname);
			}
			else
			{
				$default_var = &$var;
			}

		}
		else
		{
			@include($this_lang . $fname);
			$var = $lang;

			if ($default_lang != $this_lang)
			{
				@include($default_lang . $fname);
				$default_var = $lang;
			}
			else
			{
				$default_var = &$var;
			}
			unset($lang);
		}

		if (is_array($var))
		{
			$keys = array_keys($var);
		}
		else
		{
			$var = $keys = array();
		}


		if (is_array($default_var))
		{
			if ($default_var != $var)
			{
				$keys = array_merge($keys, array_keys($default_var));
			}
		}
		else
		{
			$default_var = array();
		}

		$keys = array_unique($keys);
		if (!empty($keys))
		{
			foreach ($keys as $key)
			{
				if (!isset($default_var[$key]) || $default_var[$key] === '')
				{
					$default_content = '<font color="#FF0000">' . $forums->lang['defaultlangnotadd'] . '</font><a href="language.php?' . $forums->sessionurl . 'do=editmutivar&amp;languageid=' . $language . '&amp;vid=' . $key . '&amp;fid=' . $fname . '">' . $forums->lang['add'] . '</a>';
				}
				else
				{
					$default_content = str_replace("\n", '<br />', $default_var[$key]);
				}

				$transcontent = isset($var[$key]) ? $var[$key] : '';
				$forums->admin->print_cells_row(array("<strong>" . $key . "</strong>", "<strong>" . $default_content . '</strong><br>'.$forums->admin->print_textarea_row('text_' . $key, $transcontent, 60, 4)));
			}
		}
		else
		{
			$forums->admin->print_cells_single_row($forums->lang['langfilenotvar'], 'center');
		}

		$forums->admin->print_form_submit($forums->lang['translate']);
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 将变动写入数据库
	 */
	function dotranslate()
	{
		global $forums, $_INPUT;

		$file = trim($_INPUT['fid']);
		$language = trim($_INPUT['languageid']);

		$root = $this->root;
		require($root . 'list.php');
		if (!isset($lang_list[$language]))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		$array_name = 'lang';
		$filename = $root . $language . '/' . $file;
		if (file_exists($filename))
		{
			if (strrchr($file, '.') == '.js')
			{
				$array_name = $this->langfunc->get_var_name($filename);
			}
		}
		else
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		$lang = array();
		@include($filename);
		foreach ($_POST as $key => $value)
		{
			$matches = array();
			if (preg_match('/^text_([a-z][a-z0-9_-]*)$/i', $key, $matches))
			{
				$lang[$matches[1]] = $_POST[$matches[0]];
			}
		}

		$this->langfunc->writefile($filename, $lang, $array_name);
		$forums->admin->redirect("language.php?do=translate&amp;languageid=$language&amp;fid=$file", $forums->lang['languagetranslate'], $forums->lang['langtranslatesucess']);
	}

	/**
	 * 添加/编辑语言表单
	 *
	 * @param string $type 类型, add 添加, edit 编辑
	 */
	function addlanguage($type = 'add')
	{
		global $forums, $_INPUT;
		$language = isset($_INPUT['languageid']) ? trim($_INPUT['languageid']) : '';
		if($type == 'edit')
		{
			require($this->root . 'list.php');
			if (!isset($lang_list[$language]))
			{
				$forums->admin->print_cp_error($forums->lang['noids']);
			}
			$title = $lang_list[$language];
			$action = 'doeditlanguage';
			$pagetitle = $forums->lang['languageedit'];
			$submitbutton = $forums->lang['edit'];
		}
		else
		{
			$title = '';
			$action = 'doaddlanguage';
			$pagetitle = $forums->lang['languageadd'];
			$submitbutton = $forums->lang['add'];
		}

		$forums->admin->nav[] = array('language.php' , $forums->lang['languagemanage']);
		$forums->admin->nav[] = array('language.php?do=addlanguage' , $pagetitle);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(
			1 => array('do', $action),
			2 => array('languageid', $language)
		));
		$forums->admin->columns[] = array('&nbsp;', '25%');
		$forums->admin->columns[] = array('&nbsp;', '55%');
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['languagetitle'] . '</strong>',
			'<div align="left">' . $forums->admin->print_input_row('title', $language) . '</div>'
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['languagename'] . '</strong>',
			'<div align="left">' . $forums->admin->print_input_row('name', $title) . '</div>'
		));
		$forums->admin->print_form_submit($submitbutton);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 保存新建风格的资料
	 *
	 * @param string $type 同 addlanguage
	 */
	function doaddlanguage($type = 'add')
	{
		global $forums, $_INPUT;
		$querybit = array();
		$title = strtolower(trim($_INPUT['title']));
		$name = trim($_INPUT['name']);

		if (empty($title) || empty($name))
		{
			$forums->admin->print_cp_error($forums->lang['inputallforms']);
		}

		if (!preg_match('/^[a-z][a-z0-9_-]*$/', $title))
		{
			$forums->admin->print_cp_error($forums->lang['entitlemusten']);
		}

		$file = $this->root . 'list.php';
		require($file);
		if (!checkdir($this->root . $title, 1))
		{
			$forums->admin->print_cp_error($forums->lang['filecannotwrite']);
		}

		$lang_list[$title] = $name;

		file_write($file,  '<' . "?php\n\$lang_list = " . var_export($lang_list, true) . ";\n?" . '>');
		$forums->admin->redirect("language.php", $forums->lang['languageedit'], $forums->lang['lang' . $type .'sucess']);
	}

	/**
	 * 删除变量/文件
	 */
	function deletevar()
	{
		global $forums, $_INPUT;
		$vname = isset($_INPUT['vid']) ? trim($_INPUT['vid']) : '';
		$fname = isset($_INPUT['fid']) ? trim($_INPUT['fid']) : '';
		$deltype = isset($_INPUT['deltype']) ? trim($_INPUT['deltype']) : '';

		require($this->root . 'list.php');
		if ($deltype == 'mutivar')
		{
			foreach ($lang_list as $k => $v)
			{
				$lang = array();
				$file = $this->root . $k . '/' . $fname;
				$ext = strchr($file, '.');
				if ($ext = '.js')
				{
					$lang = $this->langfunc->readjs($file);
				}
				else if (file_exists($file))
				{
					@include($file);
				}

				if (!empty($lang) && isset($lang[$vname]))
				{
					unset($lang[$vname]);
					$array_name = 'lang';
					if ($ext = '.js')
					{
						$array_name = $this->langfunc->get_var_name($file);
					}
					$this->langfunc->writefile($file, $lang, $array_name);
				}
			}
			$url = "language.php?do=modifyvar&amp;fid=$fname";
			$msg = $forums->lang['langvardelsucess'];
		}
		else if ($deltype == 'file')
		{
			foreach ($lang_list as $k => $v)
			{
				$file = $this->root . $k . '/' . $fname;
				if (file_exists($file))
				{
					@unlink($file);
				}
			}
			$url = "language.php?do=modifyfile";
			$msg = $forums->lang['langfiledelsucess'];
		}
		else
		{
			$forums->admin->print_cp_error($forums->lang['noanyitems']);
		}
		$forums->admin->redirect($url, $forums->lang['languagemanage'], $msg);
	}

	/**
	 * 删除语言
	 */
	function dellang()
	{
		global $forums, $_INPUT, $bboptions;
		$language = intval($_INPUT['languageid']);
		require($this->root . 'list.php');
		if (!$language || !isset($lang_list[$language]))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		if ($language == $bboptions['default_lang'])
		{
			$forums->admin->print_cp_error($forums->lang['cannotdeldefaultlang']);
		}

		$forums->admin->rm_dir($this->root . $language);

		$forums->func->recache('lang_list');
		$forums->admin->redirect("language.php", $forums->lang['languageedit'], $forums->lang['langdeletesucess']);
	}

	/**
	 * 文件管理
	 */
	function modifyfile()
	{
		global $forums, $_INPUT, $bboptions;
		$pagetitle = $forums->lang['langfilemanage'];
		$forums->admin->nav[] = array('language.php?do=modifyfile' , $forums->lang['langfilemanage']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(
			array('do', 'editfile')
		));
		$forums->admin->print_table_start("<strong>{$forums->lang['langfilelist']}</strong>");
		$forums->admin->print_table_footer(false);
		$forums->admin->columns[] = array($forums->lang['langfilename'], "55%");
		$forums->admin->columns[] = array($forums->lang['action'], "45%");
		$forums->admin->print_table_start();

		$dir = $this->root . $bboptions['default_lang'] . '/';
		extract($this->langfunc->get_fileoptions($dir, $fname));
		foreach ($fileoptions as $name)
		{
			$forums->admin->print_cells_row(array("<strong>" . $name[1] . "</strong>", "<center> <a href='language.php?{$forums->sessionurl}do=translate&amp;fid=$name[0]'>" . $forums->lang['translate'] . "</a> | <a href='language.php?{$forums->sessionurl}do=delete&amp;deltype=file&amp;fid=$name[0]' onclick='if(!confirm(\"{$forums->lang['confirmdelete']}\")) return false;'>" . $forums->lang['delete'] . "</a></div> </center>"));
		}

		$forums->admin->print_form_submit($forums->lang['addlangfile']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 编辑文件信息
	 */
	function editfile()
	{
		global $forums, $_INPUT;
		$fname = isset($_INPUT['fid']) ? trim($_INPUT['fid']) : '';
		$array_name = 'lang';
		$ext = strrchr($fname, '.');
		if ($ext == '.js')
		{
			$array_name .= '_' . ($fname ? substr(basename($fname), 0, 1) : '');
		}

		$pagetitle = $forums->lang['addlangfile'];
		$forums->admin->nav[] = array('language.php?do=modifyfile' , $forums->lang['langfilemanage']);
		$forums->admin->nav[] = array('language.php?do=editfile&amp;fid='.$fname , $pagetitle);
		$forums->admin->print_cp_header($pagetitle, '');
		$forums->admin->print_form_header(array(
			array('do', 'doeditfile')
		));
		$forums->admin->columns[] = array('&nbsp;', '30%');
		$forums->admin->columns[] = array('&nbsp;', '70%');
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['langfilename'] . "</strong>", "<div align='left'>" . $forums->admin->print_input_row('filename', $fname) . "</div>"));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['langfilearrname'] . "</strong>", "<div align='left'>" . $forums->admin->print_input_row("arrname", $array_name) . "</div>"));
		$forums->admin->print_form_submit($forums->lang['save']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 保存文件信息
	 */
	function doeditfile()
	{
		global $forums, $_INPUT;
		$filename = trim($_INPUT['filename']);
		$arrname = trim($_INPUT['arrname']);
		if ($filename == '')
		{
			$forums->admin->print_cp_error($forums->lang['inputlangfilename']);
		}

		$ext = strrchr($filename, '.');
		if ($ext == '.php')
		{
			if ($arrname != 'lang')
			{
				$forums->admin->print_cp_error($forums->lang['errorphparrname']);
			}
		}
		else if ($ext == '.js')
		{
			if (strpos($arrname, 'lang_') !== 0)
			{
				$forums->admin->print_cp_error($forums->lang['errorjsarrname']);
			}
		}
		else
		{
			$forums->admin->print_cp_error($forums->lang['errorlangfilename']);
		}



		require($this->root . 'list.php');
		foreach ($lang_list as $k => $v)
		{
			$this->langfunc->writefile($this->root . $k . '/' . $filename, array(), $arrname);
		}

		$forums->admin->redirect("language.php?do=modifyfile&amp;pp=$pp", $forums->lang['langfilemanage'], $forums->lang['langfileeditsucess']);
	}

	/**
	 * 变量管理
	 */
	function modifyvar()
	{
		global $forums, $bboptions, $_INPUT;
		$fname = isset($_INPUT['fid']) ? trim($_INPUT['fid']) : '';
		$searchkey = trim(urldecode($_INPUT['searchkey']));

		$dir = $this->root . $bboptions['default_lang'] . '/';
		extract($this->langfunc->get_fileoptions($dir, $fname));

		$pagetitle = $forums->lang['langvarmanage'];
		$forums->admin->nav[] = array('language.php?do=modifyvar' , $forums->lang['langvarmanage']);
		$forums->admin->print_cp_header($pagetitle);

		$forums->admin->print_form_header(array(1 => array('do', 'modifyvar')));
		$forums->admin->columns[] = array("&nbsp;", "35%");
		$forums->admin->columns[] = array("&nbsp;", "65%");
		$forums->admin->print_table_start($forums->lang['variablesearch']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['searchkey'] . "</strong>&nbsp;" . $forums->admin->print_input_row("searchkey", $searchkey) .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
			'<strong>' . $forums->lang['inlangfile'] . '</strong>&nbsp;' . $forums->admin->print_input_select_row('fid', $fileoptions, $fname, 'onchange="this.form.submit();"') .
			"&nbsp;&nbsp;&nbsp;<input type='submit' name='reload' value='" . $forums->lang['search'] . "' class='button' />"));
		$forums->admin->print_form_end();
		$forums->admin->print_table_footer();
		$forums->admin->print_form_header(array(
			array('do', 'addvarname'),
			array('fid', $fname),
		));

		$forums->admin->print_table_start("<strong>{$forums->lang['langvarlist']}</strong>");
		$forums->admin->print_table_footer(false);

		$forums->admin->columns[] = array($forums->lang['langvarname'], '20%');
		require($this->root . 'list.php');
		$n = count($lang_list);
		foreach ($lang_list as $k => $v)
		{
			$forums->admin->columns[] = array($v);
		}
		$forums->admin->columns[] = array($forums->lang['action'], "15%");
		$forums->admin->print_table_start();

		$array = $varnames = array();
		foreach ($lang_list as $k => $v)
		{
			$lang = array();
			$filename = $this->root . $k . '/' . $fname;
			if ($is_js)
			{
				$lang = $this->langfunc->readjs($filename);
			}
			else
			{
				@include($this->root . $k . '/' . $fname);
			}

			if (!empty($lang))
			{
				foreach ($lang as $a => $v)
				{
					if (empty($searchkey) || strpos($a, $searchkey) !== false)
					{
						$varnames[$a] = $a;
						if (!empty($v))
						{
							$array[$k][$a] = $v;
						}
					}
				}
			}
		}

		foreach ($varnames as $k => $v)
		{
			if ($searchkey != '')
			{
				$v = str_replace($searchkey, '<span style="color:#FF0000">' . $searchkey . '</span>', $v);
			}
			$row = array('<strong>' . $v . '</strong>');
			foreach($lang_list as $l => $v)
			{
				if (!empty($array[$l][$k]))
				{
					$row[] = '<div align="center"><img src="' . $forums->imageurl . '/tick_yes.gif" class="inline" title="' . str_replace('"', '&quote;', $array[$l][$k]) . '"/></div>';
				}
				else
				{
					$row[] = '<div align="center"><img src="' . $forums->imageurl . '/tick_no.gif" class="inline" /></div>';
				}
			}
			$row[] = "<div align='center'><a href='language.php?{$forums->sessionurl}do=editmutivar&amp;vid=$k&amp;fid=$fname'>" . $forums->lang['translate'] . "</a> | <a href='language.php?{$forums->sessionurl}do=delete&amp;deltype=mutivar&amp;vid=$k&amp;fid=$fname' onclick='if(!confirm(\"{$forums->lang['confirmdelete']}\")) return false'>" . $forums->lang['delete'] . "</a></div>";
			$forums->admin->print_cells_row($row);
		}

		$forums->admin->print_form_submit($forums->lang['addlangvar']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 编辑变量词条
	 *
	 * @param string $type 类型, edit 编辑, 其他为添加
	 */
	function editmutivar($type = 'edit')
	{
		global $forums, $_INPUT, $bboptions;
		$fname = trim($_INPUT['fid']);
		$vname = trim($_INPUT['vid']);
		if (empty($fname))
		{
			$forums->admin->print_cp_error($forums->lang['sellangvarinfile']);
		}

		$content = array();
		if ($type == 'edit')
		{
			$varname = $vname;
			$filename = $fname;
			if (strrchr($filename, '.') == '.js')
			{
				$is_js = true;
			}

			$action = 'doeditmutivar';
			$hrefurl = 'editmutivar';
			$pagetitle = $forums->lang['languagetranslate'];
		}
		else
		{
			$dir = $this->root . $bboptions['default_lang'] . '/';
			extract($this->langfunc->get_fileoptions($dir, $fname));

			$varname = $forums->admin->print_input_row('varname');
			$filename = $forums->admin->print_input_select_row('fileid', $fileoptions, $fname);
			$action = 'doaddvarname';
			$hrefurl = 'addvarname';
			$pagetitle = $forums->lang['addlangvar'];
		}

		$language = isset($_INPUT['languageid']) ? trim($_INPUT['languageid']) : '';
		if ($language)
		{
			$forums->admin->nav[] =  array("language.php?{$forums->sessionurl}do=translate&amp;fid=$fname&amp;languageid=$language", $forums->lang['languagetranslate']);
		}
		else
		{
			$forums->admin->nav[] = array("language.php?do=modifyvar&amp;vid=$vname&amp;fid=$fname", $forums->lang['langvarmanage']);
		}

		$forums->admin->nav[] = array('language.php?do=' . $hrefurl . '&amp;fid=' . $fname . '&amp;vid=' . $vname, $pagetitle);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->print_form_header(array(
			array('do', $action),
			array('vid', $vname),
			array('fid', $fname),
			array('languageid', $language),
			array('searchkey', $_INPUT['searchkey']),
			array('lname', $_INPUT['lname']),
			array('fname', $_INPUT['fname']),
			array('searchtype', $_INPUT['searchtype']),
			array('redurl', $_INPUT['redurl']),
		));
		$forums->admin->columns[] = array('&nbsp;', '30%');
		$forums->admin->columns[] = array('&nbsp;', '70%');
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['langvarname'] . '</strong>',
			'<div align="left">' . $varname . '</div>'
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['inlangfile'] . '</strong>',
			'<div align="left">' . $filename . '</div>'
		));

		require($this->root . 'list.php');
		foreach ($lang_list as $k => $v)
		{
			$file = $this->root . $k . '/' . $fname;
			if ($is_js)
			{
				$lang = $this->langfunc->readjs($file);
			}
			else
			{
				@include($file);
			}
			$content = isset($lang[$vname]) ? $lang[$vname] : '';

			$forums->admin->print_cells_row(array(
				'<strong>' . $v . '</strong>',
				'<div align="center">' . $forums->admin->print_textarea_row('value_' . $k, $content) . '</div>'
			));
		}
		unset($lang);
		$forums->admin->print_form_submit($forums->lang['translate']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	/**
	 * 保存变量词条
	 *
	 * @param string $type 同 editmutivar
	 */
	function do_editmutivar($type = 'edit')
	{
		global $forums, $_INPUT;
		$fname = isset($_INPUT['fileid']) ? trim($_INPUT['fileid']) : trim($_INPUT['fid']);
		if (empty($fname))
		{
			$forums->admin->print_cp_error($forums->lang['sellangvarinfile']);
		}
		$is_js = (strrchr($fname, '.') == '.js');

		$varname = isset($_INPUT['varname']) ? trim($_INPUT['varname']) : trim($_INPUT['vid']);
		if (empty($varname))
		{
			$forums->admin->print_cp_error($forums->lang['inputlangvarname']);
		}

        require($this->root . 'list.php');
		foreach ($_POST as $key => $value)
		{
			$matches = array();
			if (preg_match('/^value_([a-z][a-z0-9_-]*)$/i', $key, $matches))
			{
				if (isset($lang_list[$matches[1]]))
				{
					$file = $this->root . $matches[1] . '/' . $fname;
					$array_name = 'lang';
					if ($is_js)
					{
						$lang = $this->langfunc->readjs($file);
						$array_name = $this->langfunc->get_var_name($file);
					}
					else
					{
						@include($file);
					}

					if (!empty($lang))
					{
						if ($type != 'edit' && !empty($lang[$vname]))
						{
							$forums->admin->print_cp_error($forums->lang['addlangvarnameexsits']);
						}
						$lang[$vname] = $value;
						$this->langfunc->writefile($file, $lang, $array_name);
					}
				}

			}
		}

		$language = isset($_INPUT['languageid']) ? trim($_INPUT['languageid']) : '';
		if ($language)
		{
			$url = "language.php?{$forums->sessionurl}do=translate&amp;fid=$fname&amp;languageid=$language";
		}
		else
		{
			$url = "language.php?{$forums->sessionurl}do=modifyvar&amp;vid=$vname&amp;fid=$fname";
		}
		if ($_INPUT['redurl'])
		{
			$url = "language.php?{$forums->sessionurl}do={$_INPUT['redurl']}&amp;searchkey={$_INPUT['searchkey']}&amp;lname={$_INPUT['lname']}&amp;fname={$_INPUT['fname']}&amp;searchtype={$_INPUT['searchtype']}";
		}
		$forums->admin->redirect($url, $forums->lang['langvarmanage'], $forums->lang['langvareditsucess']);
	}

	/**
	 * 导入/导出语言 XML 表单
	 */
	function langxml()
	{
		global $forums;

		$title = $forums->lang['languagemanage'];
		$forums->admin->nav[] = array('language.php?do=langxml' , $forums->lang['languagemanage']);
		$forums->admin->print_cp_header($title);
		$forums->admin->print_form_header(array(1 => array('do' , 'exportlangxml'), 'export'));
		$forums->admin->columns[] = array('&nbsp;' , '40%');
		$forums->admin->columns[] = array('&nbsp;' , '60%');
		$forums->admin->print_table_start($forums->lang['exportlang']);

		$langlist = array();
		require($this->root . 'list.php');
		foreach ($lang_list as $k => $v)
		{
			$langlist[] = array($k, $v);
		}
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectexportlang'] . "</strong>", $forums->admin->print_input_select_row('languageid', $langlist, '')));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['stylesavefilename'] . "</strong>", $forums->admin->print_input_row('filename', 'MolyX-language.xml', '', '', 30)));

		$forums->admin->print_form_submit($forums->lang['exportlang']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();

		$forums->admin->print_form_header(array(1 => array('do', 'importlangxml')) , "uploadform", " enctype='multipart/form-data' onsubmit='return confirmupload(this, this.fromlocal);'");
		$forums->admin->columns[] = array('&nbsp;' , '40%');
		$forums->admin->columns[] = array('&nbsp;' , '60%');
		$forums->admin->print_table_start($forums->lang['importlang']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploadlocallanguage'] . "</strong>", $forums->admin->print_input_row('fromlocal', '', 'file', '', 30)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['uploadserverlanguage'] . "</strong>", $forums->admin->print_input_row('fromserver', ROOT_PATH . 'MolyX-language.xml', '', '', 30)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['importlanguagename'] . "</strong><br /><span class='description'>" . $forums->lang['importlanguagenamedesc'] . "</span>", $forums->admin->print_input_row('name', '', '', '', 30)));
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['importlanguagetitle'] . "</strong><br /><span class='description'>" . $forums->lang['importlanguagenamedesc'] . "</span>", $forums->admin->print_input_row('title', '', '', '', 30)));
		$forums->admin->print_form_submit($forums->lang['styleimport']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function exportlangxml()
	{
		global $forums, $_INPUT, $bboptions;
		$language = trim($_INPUT['languageid']);

		require($this->root . 'list.php');
		if (!isset($lang_list[$language]))
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}

		$exportfile = trim($_INPUT['filename']);
		if (empty($exportfile))
		{
			$exportfile = 'MolyX-language.xml';
		}

		$xml = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\r\n";
		$xml .= '<languagelist>' . "\r\n";
		$xml .= '<language title="' . $language . '" name="' . $lang_list[$language] . '"  version="' . $bboptions['version'] . '" created-time="' . TIMENOW . '">' . "\r\n\r\n";
		$dir = $this->root . $language . '/';
		extract($this->langfunc->get_fileoptions($dir));
		foreach ($fileoptions as $v)
		{
			$v = $v[0];
			$lang = array();
			$file = $dir . $v;
			$array_name = 'lang';
			$is_js = (strrchr($v, '.') == '.js') ? true : false;
			if ($is_js)
			{
				$array_name = $this->langfunc->get_var_name($file);
				$lang = $this->langfunc->readjs($file);
			}
			else
			{
				@include($file);
			}


			$xml .= "\t" . '<languagefile name="' . $v . '" arrname="' . $array_name . '">' . "\r\n";
			foreach ($lang as $varname => $text)
			{
				$xml .= "\t\t" . '<languagevar varname="' . utf8_htmlspecialchars($varname) . '"><![CDATA[' . $text . ']]></languagevar>' . "\r\n";
			}
			$xml .= "\t" . '</languagefile>' . "\r\n\r\n";
		}
		unset($lang);
		$xml .= '</language>' . "\r\n";
		$xml .= '</languagelist>';
		$forums->admin->show_download($xml, $exportfile, 'text/xml');
	}

	function importlangxml()
	{
		global $forums, $_INPUT;
		if ((!$_FILES['fromlocal']['name'] || (isset($_FILES['fromlocal']) && $_FILES['fromlocal']['error'] != UPLOAD_ERR_OK)) && (!$_INPUT['fromserver'] || !file_exists($_INPUT['fromserver'])))
		{
			$forums->main_msg = $forums->lang['nouploadfile'];
			$this->langxml();
		}

		if ($_FILES['fromlocal']['tmp_name'] && is_uploaded_file($_FILES['fromlocal']['tmp_name']))
		{
			$xml = @file_get_contents($_FILES['fromlocal']['tmp_name']);
		}
		else if ($_INPUT['fromserver'])
		{
			$xml = @file_get_contents($_INPUT['fromserver']);
		}

		require_once(ROOT_PATH . 'includes/class_language_import.php');
		$importlang = new language_import();
		$importlang->importxmllanguage($xml, $_INPUT['title'], $_INPUT['name']);

		//重新生成语言缓存
		$recachelangid = $importlang->importlangids;
		if (!empty($recachelangid) && $recachelangid)
		{
			if (is_array($recachelangid))
			{
				foreach ($recachelangid as $langid)
				{
					$this->langfunc->recachelanguage($langid);
				}
			}
			else
			{
				$this->langfunc->recachelanguage($recachelangid);
			}
		}

		$forums->func->recache('lang_list');
		$forums->admin->redirect("language.php?do=langxml", $forums->lang['languagemanage'], $forums->lang['langimportsucess']);
	}
}

$output = new language();
$output->show();
?>