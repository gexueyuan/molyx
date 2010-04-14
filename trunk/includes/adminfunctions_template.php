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
class adminfunctions_template
{
	var $messages = array();
	var $template_count = array();

	function rebuildallcaches($id)
	{
		global $forums, $DB;
		if ($id == 1)
		{
			return;
		}
		$forums->func->check_cache('style');
		$style = $forums->cache['style'][$id];
		$forums->lang['recachestylescss'] = sprintf($forums->lang['recachestylescss'], $style['title']);
		$forums->lang['recachetemplates'] = sprintf($forums->lang['recachetemplates'], $style['title']);
		$this->messages[] = '<strong>' . $forums->lang['recachestylescss'] . '</strong>';
		$this->writecsscache($id, false);
		$this->messages[] = '<br /><strong>' . $forums->lang['recachetemplates'] . '</strong>';
		$this->recachetemplates($id);
		$forums->func->recache('style');
		return $message;
	}

	/**
	 * 生成 CSS 缓存
	 *
	 * @param intager $id 风格 ID
	 * @param string $lang 语言标示
	 * @param boolean $recache_childs 是否重新生成继承风格的 CSS 缓存
	 * @param string $name CSS 名称
	 */
	function writecsscache($id, $recache_childs = true, $name = 'style')
	{
		global $forums, $DB, $bboptions;

		if ($id == 1)
		{
			return;
		}

		$css = str_replace(
			array('&lt;', '&gt;', '&quot;', '&#039;', "\r\n", "\r", 'cache/'),
			array('<', '>', '"', "'", "\n", "\n", ''),
			$this->get_template_content($id, $name . '.css')
		);
		$css = $this->optimize_css($css);

		$dot = SAFE_MODE ? '_' : '/';
		$styles = IN_ACP ? $forums->admin->stylecache : $forums->cache['style'];

		if (!$recache_childs)
		{
			$styles = array($styles[$id]);
		}

		$dir = ROOT_PATH . 'cache/templates' . $dot;
		foreach ($styles as $v)
		{
			if ($v['styleid'] == $id || (strpos($v['parentlist'], ",$id,") !== false && in_array($this->get_template_dir($v['styleid'], 'style.css'), array('global', $styles[$id]['title_en']))))
			{
				if (!SAFE_MODE && !checkdir($dir, 3))
				{
					$this->messages[] = '<strong>' . $forums->lang['cssdirnotwrite'] . '</strong>';
					continue;
				}

				$cssfile = $dir . 'style_' . $v['styleid'] . '.css';
				$thiscss = str_replace('<#IMAGE#>/', "../../images/{$v['imagefolder']}/", $css);
				if (file_write($cssfile, $thiscss))
				{
					$this->messages[] = sprintf($forums->lang['recachestylecssfile'], $id['title'], $id['styleid']);
				}
				else
				{
					$this->messages[] = '<strong>' . sprintf($forums->lang['cssfilenotupdate'], $id['styleid'], $id['styleid']) . '</strong>';
				}
			}
		}
	}

	function recachetemplates($id, $template_name = '')
	{
		global $DB, $forums, $bboptions;
		$id = intval($id);
		if ($id == 1)
		{
			return;
		}

		if ($template_name)
		{
			$templates = array(array(array('title' => $template_name)));
		}
		else
		{
			$this->writecsscache($id);
			$templates = $this->get_templates($id, 'all');
		}

		if (count($templates) < 1)
		{
			return;
		}

		$style = isset($forums->admin->stylecache) ? $forums->admin->stylecache[$id] : $forums->cache['style'][$id];
		$imagefolder = $style['imagefolder'];
		$dot = SAFE_MODE ? '_' : '/';

		$cache_header = "<?php\n// Template Cach for MolyX 2.8\n";
		$cache_header .= "if(!defined('IN_MXB')) exit('Access denied.Sorry, you can not access this file directly.');\n?>\n";
		foreach ($templates as $template)
		{
			foreach ($template as $v)
			{
				$content = $this->get_template_content($id, $v['title']);
				$content = $this->parse_template($id, $content, $imagefolder);
				$content = $cache_header . $content . "\n";
				$this->writetemplate($id, $v['title'], preg_replace('#<<<.+?>>>#', '', $content));
			}
		}

		if (empty($template_name))
		{
			$this->messages[] = $forums->lang['templaterecached'] . "... (id: $id)";
		}
	}

	function writetemplate($id, $templatename, $content)
	{
		global $DB, $forums;
		if ($id == '1')
		{
			return;
		}
		$return = 0;
		$good_to_go = 1;
		$dot = SAFE_MODE ? '_' : '/';
		$dir = ROOT_PATH . "cache/templates/style_$id";
		$good_to_go = checkdir($dir, 1);
		if ($good_to_go)
		{
			if (file_exists("{$dir}{$dot}{$templatename}.php"))
			{
				if (!is_writeable("$dir{$dot}$templatename.php"))
				{
					$this->messages[] = "::style_{$id}{$dot}$templatename.php " . $forums->lang['templatesnotwrite'];
					$good_to_go = 0;
				}
				else
				{
					$good_to_go = 1;
				}
			}
			else
			{
				$good_to_go = 1;
			}
		}
		else
		{
			$good_to_go = 0;
			$forums->lang['templatedirnotwrite'] = sprintf($forums->lang['templatedirnotwrite'], $change);
			$this->messages[] = $forums->lang['templatedirnotwrite'];
		}

		if ($good_to_go)
		{
			$name = "cache/templates/style_$id{$dot}$templatename.php";
			if (false !== file_write(ROOT_PATH . $name, $content))
			{
				$return = 1;
				$this->messages[] = $forums->lang['writed'] . " $name";
			}
		}
		return $return;
	}

	function parse_template($id, $html, $imagefolder = '')
	{
		$this->subtemplate_writein($id, $html);
		$html = str_replace('{CRON}', '<' . '?php echo CRON; ?>', $html);
		$html = str_replace('{CACHE_CSS}', '<link id="link_css_style" rel="stylesheet" type="text/css" href="{include:style.css}" />', $html);
		$html = str_replace('<#IMAGE#>', $imagefolder, $html);
		$html = preg_replace('#\{include:([a-z0-9_]+?)\.css\}#i', '<' . '?php echo $forums->func->load_css(\'\\1\'); ?' . '>', $html);
		$html = preg_replace('#\{include:([a-z0-9_]+?)\}#i', '<' . '?php include $forums->func->load_template(\'\\1\'); ?' . '>', $html);
		$html = preg_replace('#\{ads:(.+?)\}#ise', "\$this->parse_ad('\\1')", $html);
		$html = preg_replace('#\{lang:(.+?)\}#ise', "\$this->parse_lang('\\1')", $html);

		if (stristr($html, '<if'))
		{
			$html = preg_replace(array('#<if=("|\')(.+?)(?:\\1)>#ise'), "\$this->parse_if('\\2')", $html);
			$html = preg_replace('#<elseif=("|\')(.+?)(?:\\1)>#ise', "\$this->parse_elseif('\\2')", $html);
			$html = preg_replace(array('#<if condition=("|\')(.+?)(?:\\1)>#ise'), "\$this->parse_if('\\2')", $html);
			$html = preg_replace('#<elseif condition=("|\')(.+?)(?:\\1) />#ise', "\$this->parse_elseif('\\2')", $html);
			$html = str_replace(array('<else>', '<else />'), '<' . '?php } else { ?' . '>', $html);
			$html = str_replace('</if>', '<' . '?php } ?' . '>', $html);
		}

		if (stristr($html, '<foreach'))
		{
			$html = preg_replace('#<foreach=("|\')(.+?)(?:\\1)>#ise', "\$this->parse_foreach('\\2')", $html);
			$html = preg_replace('#<foreach condition=("|\')(.+?)(?:\\1)>#ise', "\$this->parse_foreach('\\2')", $html);
			$html = str_replace('</foreach>', '<' . '?php } ?' . '>', $html);
		}

		$html = preg_replace('#<php>(.+?)</php>#ise', "\$this->prae_code('\\1')", $html);
		$html = preg_replace('#(</td>|</tr>|<td>|<tr>)( |\r\n|\r|\n){0,}(</td>|</tr>|<td>|<tr>)#', '\\1\\3', $html);
		$html = $this->parse_tags_after($html);
		return $html;
	}

	function parse_lang($file)
	{
		$file = explode('.', $file);
		if (!isset($file[1]))
		{
			return '';
		}

		$return = '<' . '?php $forums->func->load_lang';
		if ($file[1] == 'js')
		{
			$return = str_replace('<' . '?php', '<' . '?php echo', $return);
			$return .= '_js';
		}
		$return .= '(\'' . $file[0] . '\')?' . '>';
		return $return;
	}

	function parse_ad($code)
	{
		$v = explode(',', $code);
		foreach ($v as $data)
		{
			$data = trim($data);
			if (preg_match("#^\\$(.+?)#si", $data))
			{
				$value[] = $data;
			}
			else
			{
				$value[] = "'" . $data . "'";
			}
		}
		return '<' . '?php $forums->func->check_ad(' . implode(', ', $value) . '); ?' . '>';
	}

	function parse_if($code)
	{
		$code = $this->replace_if($code);
		return '<' . '?php if (' . $code . ') { ?' . '>';
	}

	function replace_if($code)
	{
		return trim(preg_replace("/(^|and|or)(\s+)(.+?)(\s|$)/ise", "\$this->replace_left('\\3', '\\1', '\\2', '\\4')", ' ' . $code));
	}

	function replace_left($left, $andor = '', $fs = '', $ls = '')
	{
		$left = trim($left);
		if (strpos('forums.', $left) === 0)
		{
			$left = substr_replace($left, '$forums->', 0, 7);
		}
		return $andor . $fs . $left . $ls;
	}

	function parse_foreach($code)
	{
		return '<' . '?php foreach (' . $code . ') { ?' . '>';
	}

	function prae_code($code)
	{
		return '<' . '?php ' . $code . ' ?' . '>';
	}

	function parse_elseif($code)
	{
		$code = $this->replace_if($code);
		return '<' . '?php } else if (' . $code . ') { ?>';
	}

	function parse_tags_after($text = '')
	{
		$regex = array(
			'/\{\$lang\[(\'|"|)([a-zA-Z0-9_\x7f-\xff]+)(\\1)\]\}/Ue',
			'/\{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\[\]\'"\$\x7f-\xff]*)\}/U',
			'/{echo_head_elements}/i',
			'/{echo_foot_elements}/i',
			'/{sessionurl}/i',
			'/{si_sessionurl}/i',
			'/{js_sessionurl}/i',
			'/{sessionid}/i',
			'/{url}/i',
			'/{debug}/i',
			'/{finish}/i',
			'/{scriptpath}/i',
			'/{lang_list}/i',
			'/{style_list}/i',
			'/{dbcount}/i',
			'/{dbexplain}/i',
			'/{end_cache_page}/i',
			'/{global\.(folder_links)([\'\a-zA-Z_\x7f-\xff\']*)}/s',
			'/{DB\.([a-zA-Z_\x7f-\xff])([\'\a-zA-Z_\x7f-\xff\']*)}/U',
		);

		$replace = array(
			'$this->fetch_lang(\'\\2\')',
			'<' . '?php echo \\1; ?' . '>',
			'<' . '?php echo add_head_element(\'echo\'); ?' . '>',
			'<' . '?php echo add_foot_element(\'echo\'); ?' . '>',
			'<' . '?php echo $forums->sessionurl; ?' . '>',
			'<' . '?php echo $forums->si_sessionurl; ?' . '>',
			'<' . '?php echo $forums->js_sessionurl; ?' . '>',
			'<' . '?php echo $forums->sessionid; ?' . '>',
			'<' . '?php echo $forums->url; ?' . '>',
			'<' . '?php echo $forums->func->debug(); ?' . '>',
			'<' . '?php echo $forums->func->finish(); ?' . '>',
			'<' . '?php echo SCRIPTPATH; ?' . '>',
			'<' . '?php echo $forums->lang_list; ?' . '>',
			'<' . '?php echo $forums->style_list; ?' . '>',
			'<' . '?php echo $DB->query_count(); ?' . '>',
			'<' . '?php if ($DB->explain !== null) { echo $DB->explain->show(); } ?' . '>',
			'<' . '?php end_cache_page(); ?' . '>',
			'<' . '?php echo $forums->\\1\\2; ?' . '>',
			'<' . '?php echo $DB->\\1\\2; ?' . '>',
		);

		return preg_replace($regex, $replace, $text);
	}

	function fetch_lang($name = '')
	{
		global $forums;
		return '<' . '?php echo $forums->lang[\'' . $name . '\']; ?' . '>';
	}

	/**
	 * 删除某个风格所有的模板缓存文件
	 *
	 * @param intager $id 风格 ID
	 */
	function delstylecache($id)
	{
		global $forums, $DB;

		if (!isset($forums->admin->stylecache))
		{
			$forums->admin->cache_styles();
		}

		foreach ($forums->admin->stylecache as $row)
		{
			if (',' . strpos($row['parentlist'], ",$id,") !== false)
			{
				$dir = ROOT_PATH . "cache/templates/style_{$row['styleid']}/";
				if (is_dir($dir))
				{
					$forums->admin->rm_dir($dir, false);
				}
			}
		}
	}

	/**
	 * 获得某个风格所有的模板名或者模板组名
	 *
	 * @param intager $styleid 风格 ID
	 * @param string $type 获得的名称数组类型, this 表示不继承上级风格, groups 表示模板组名
	 * @param mixed $matchs 要求匹配特定模板组或者模板名称
	 * @return array
	 */
	function get_templates($styleid, $type = '', $matchs = '')
	{
		global $forums;
		$inherit = ($type == 'this') ? false : true;
		$list = $this->get_style_dir($styleid, $inherit);
		$templates = $isset = array();
		for ($i = 0, $n = count($list); $i < $n; $i++)
		{
			$dirname = ROOT_PATH . 'templates/' . $list[$i] . '/';
			if ($dh = opendir($dirname))
			{
				while (false !== ($file = readdir($dh)))
				{
					if (!is_file($dirname . $file) || strrchr($file, '.') != '.htm' || isset($isset[$file]))
					{
						continue;
					}
					$isset[$file] = true;


					$title = substr($file, 0, -4);
					$tgroup = $this->get_groupname($title);

					$r = array(
						'title' => $title,
						'templategroup' => $tgroup,
					);

					if (!isset($this->template_count[$tgroup]))
					{
						$this->template_count[$tgroup] = array(
							'original' => 0,
							'altered' => 0,
							'inherited' => 0,
						);
					}

					if ($list[$i] == 'global')
					{
						$this->template_count[$tgroup]['original']++;
						$r['type'] = 0;
					}
					else if ($list[$i] == $forums->admin->stylecache[$styleid]['title_en'])
					{
						$this->template_count[$tgroup]['altered']++;
						$r['type'] = 1;
					}
					else
					{
						$this->template_count[$tgroup]['inherited']++;
						$r['type'] = 2;
					}

					if ($type == 'groups')
					{
						if (empty($matchs))
						{
							$templates[$tgroup] = $r;
						}
						else if (strpos($title, $matchs . '_') === 0  || ($matchs == 'global' && strpos($title, '_') === false))
						{
							$templates[$title] = $r;
						}
					}
					else
					{
						if (empty($matchs))
						{
							$templates[$tgroup][$title] = $r;
						}
						else
						{
							$matchs = is_array($matchs) ? implode(',', $matchs) : $matchs;
							if (strpos(',' . $matchs . ',', ',' . $title . ',') !== false)
							{
								$templates[$tgroup][$title] = $r;
							}
						}
					}
				}
				closedir($dh);
			}
		}
		ksort($templates);
		return $templates;
	}

	/**
	 * 获得某个风格的目录
	 *
	 * @param intager $styleid 风格 ID
	 * @param boolean $inherit 是否继承父风格
	 */
	function get_style_dir($styleid, $inherit = true)
	{
		static $data = array();
		$hash = $styleid . '_' . intval($inherit);
		if (isset($data[$hash]))
		{
			return $data[$hash];
		}

		global $forums;
		if (IN_ACP)
		{
			if (empty($forums->admin->stylecache))
			{
				$forums->admin->cache_styles();
			}
			$styles = $forums->admin->stylecache;
		}
		else
		{
			$forums->func->check_cache('style');
			$styles = $forums->cache['style'];
		}

		if ($inherit)
		{
			$list = explode(',', $styles[$styleid]['parentlist']);
			array_pop($list);
			foreach ($list as $k => $v)
			{
				$list[$k] = $styles[$v]['title_en'];
			}
			$list[] = 'global';
		}
		else
		{
			$list = array($styles[$styleid]['title_en']);
		}
		$data[$hash] = $list;
		return $list;
	}

	/**
	 * 获得某个模板的风格目录
	 *
	 * @param intager $styleid 风格 ID
	 * @param string $filename 文件名
	 * @param boolean $inherit 是否继承父风格
	 * @return mixed 如果能够找到模板文件返回风格目录名, 否则返回 false
	 */
	function get_template_dir($styleid, $filename, $inherit = true)
	{
		$list = $this->get_style_dir($styleid, $inherit);

		for ($i = 0, $n = count($list); $i < $n; $i++)
		{
			$filepath = ROOT_PATH . 'templates/' . $list[$i] . '/' . $filename;
			if (file_exists($filepath))
			{
				return $list[$i];
			}
		}
		return false;
	}

	/**
	 * 获得模板内容
	 * 参数同 get_template_dir
	 *
	 * @return string 模板文件名
	 */
	function get_template_filename($styleid, $tpl_name, $inherit = true)
	{
		global $forums, $DB;

		$ext = strrchr($tpl_name, '.');
		$filename = ($ext != '.htm' && $ext != '.css') ? $tpl_name . '.htm' : $tpl_name;
		$dir = ROOT_PATH . '/templates/';
		$style_dir = $this->get_template_dir($styleid, $filename, $inherit);

		if ($style_dir === false)
		{
			if (!$inherit)
			{
				return '';
			}
			trigger_error($forums->lang['nofindtemplate'], E_USER_ERROR);
		}
		else
		{
			$filename = $dir . $style_dir . '/' . $filename;
		}
		return $filename;
	}


	/**
	 * 获得某个模板的风格目录
	 *
	 * @param intager $styleid 风格 ID
	 * @param string $filename 文件名
	 * @param boolean $inherit 是否继承父风格
	 * @return mixed 如果能够找到模板文件返回模板内容, 否则返回空串
	 */
	function get_template_content($styleid, $tpl_name, $inherit = true)
	{
		$filename = $this->get_template_filename($styleid, $tpl_name, $inherit);
		if ($filename)
		{
			$content = @file_get_contents($filename);
		}
		else
		{
			$content = '';
		}
		return $content;
	}


	/**
	 * 由模板名字获得模板组名
	 *
	 * @param string $title 模板名字
	 * @return string
	 */
	function get_groupname($title)
	{
		return ($pos = strpos($title, '_')) ? substr($title, 0, $pos) : 'global';
	}

	/**
	 * 将子模板写入上级模板中
	 *
	 * @param string $content 上级模板内容的引用
	 */
	function subtemplate_writein($styleid, &$content)
	{
		$templates = array();
		if (preg_match_all('#\{template:([a-z0-9_]+?)\}#i', $content, $templates))
		{
			for ($i = 0, $n = count($templates[0]); $i < $n; $i++)
			{
				$content = str_replace($templates[0][$i], $this->get_template_content($styleid, $templates[1][$i]), $content);
			}
			$this->subtemplate_writein($styleid, $content);
		}
	}

	/**
	 * 检查子模板是否有更新
	 *
	 * @param string $title 模板名
	 * @param intager $mtime 基准时间
	 * @param string $return 引用, 模板是否有更新
	 */
	function subtemplate_mtime($styleid, $title, $mtime, &$return)
	{
		if ($return === true)
		{
			return;
		}

		$templates = array();
		$content = $this->get_template_content($styleid, $title);
		if (preg_match_all('#\{template:([a-z0-9_]+?)\}#i', $content, $templates))
		{
			for ($i = 0, $n = count($templates[0]); $i < $n; $i++)
			{
				$file = $this->get_template_filename($styleid, $templates[1][$i]);
				if (@filemtime($file) > $mtime)
				{
					$return = true;
				}
				else
				{
					$this->subtemplate_mtime($styleid, $templates[1][$i], $mtime, $return);
				}

				if ($return === true)
				{
					return;
				}
			}
		}
	}

	/**
	 * 优化 CSS, 减小 CSS 文件的大小
	 */
	function optimize_css($content)
	{
		$content = utf8_unhtmlspecialchars($content);
		$content = str_replace(array("\r\n", "\r", "\n\n"), "\n", $content);
		$match = $parsed = array();
		$content = preg_replace('#/\*(.+?)\*/#s', '', $content);
		preg_match_all('/(.+?)\{(.+?)\}/s', $content, $match, PREG_PATTERN_ORDER);
		for ($i = 0, $n = count($match[0]); $i < $n; $i++)
		{
			$match[1][$i] = trim($match[1][$i]);
			$parsed[$match[1][$i]] = trim($match[2][$i]);
		}

		if (empty($parsed))
		{
			return '';
		}

		$content = '';
		foreach ($parsed as $name => $p)
		{
			if (preg_match("#^//#", $name))
			{
				continue;
			}
			$parts = explode(';', $p);
			$defs = array();
			foreach($parts as $part)
			{
				if (trim($part) != '')
				{
					list($definition, $data) = explode(':', $part);
					$defs[] = trim($definition) . ': ' . trim($data);
				}
			}
			$content .= $name . ' { ' . implode('; ', $defs) . " }\n";
		}

		return $content;
	}
}
?>