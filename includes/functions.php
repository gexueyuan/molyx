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
class functions
{
	var $lang_list = array();
	var $stylecache = array();
	var $onlineuser = array();
	var $offset = '';
	var $time_offset = 0;
	var $language = null;
	var $template = null;
	var $errheader = true;

	/**
	 * 检查/载入缓存
	 *
	 * @param string $cache_name 缓存名
	 * @param string $extra 缓存使用函数名, 可选
	 * @param boolean $return 是否返回缓存内容, true 时将不重新生成缓存
	 */
	function check_cache($cache_name = '', $extra = '', $return = false)
	{
		global $forums;
		if (isset($forums->cache[$cache_name]))
		{
			return $return ? $forums->cache[$cache_name] : true;
		}
		$cache_file = ROOT_PATH . 'cache/cache/' . $this->convert_cache_name($cache_name) . '.php';
		if (!@include($cache_file))
		{
			$forums->cache[$cache_name] = false;
		}

		if ($return)
		{
			return $forums->cache[$cache_name];
		}
		else if ($forums->cache[$cache_name] === false)
		{
			$cache_name = $extra ? $extra : $cache_name;
			$this->recache($cache_name);
		}
		return true;
	}

	function update_cache($v = array())
	{
		global $forums;
		if (is_string($v) && !empty($v))
		{
			$v['name'] = $v;
		}
		if ($v['name'])
		{
			if (empty($v['value']))
			{
				$v['value'] = $forums->cache[$v['name']];
			}
			else
			{
				$forums->cache[$v['name']] = $v['value'];
			}
			$cache_file = ROOT_PATH . 'cache/cache/' . $this->convert_cache_name($v['name'], true) . '.php';
			if ($v['value'] === false)
			{
				$v['value'] = 0;
			}
			$content = '<'. "?php\n\$forums->cache['{$v['name']}'] = " . var_export($v['value'], true) . ";\n?" . '>';
			file_write($cache_file, $content);
		}
	}

	/**
	 * 重建缓存
	 *
	 * @param string $cache_name 缓存名字
	 */
	function recache($cache_name = '')
	{
		if (empty($cache_name))
		{
			return false;
		}

		static $cache = null;
		if ($cache === null)
		{
			require_once(ROOT_PATH . 'includes/adminfunctions_cache.php');
			$cache = new adminfunctions_cache();
		}
		$cache_name .= '_recache';
		if (method_exists($cache, $cache_name))
		{
			$cache->$cache_name();
			return true;
		}
		return false;
	}

	/**
	 * 删除缓存
	 *
	 * @param string $cache_name 缓存名字
	 */
	function rmcache($cache_name = '')
	{
		if (empty($cache_name))
		{
			return false;
		}

		if (is_array($cache_name))
		{
			return array_map(array(&$this, 'rmcache'), $cache_name);
		}
		else
		{
			return @unlink(ROOT_PATH . 'cache/cache/' . $this->convert_cache_name($cache_name) . '.php');
		}
	}

	/**
	 * 转换缓存名, 将其中的 - 变为 /
	 *
	 * @param string $name 缓存名
	 * @param boolean $check_dir 是否检查目录, 生成时用
	 */
	function convert_cache_name($name, $check_dir = false)
	{
		if (!SAFE_MODE && strpos($name, '-') !== false)
		{
			$name = str_replace('-', '/', $name);
			if ($check_dir && !checkdir(ROOT_PATH . 'cache/cache/' . $name, count(explode('/', $name)), true))
			{
				$this->standard_error('cachewriteerror');
			}
		}

		return $name;
	}

	/**
	 * 载入模板
	 *
	 * @param string $template 模板名
	 * @return string 模板缓存文件路径
	 */
	function load_template($template)
	{
		global $bbuserinfo, $bboptions;

		static $ob_started = false;
		if (!$ob_started)
		{
			if ($bboptions['gzipoutput'] || $bboptions['rewritestatus'])
			{
				$buffer = ob_get_contents();
				ob_end_clean();

				$user_function = array();
				if ($bboptions['gzipoutput'] && function_exists('ob_gzhandler'))
				{
					$user_function[] = 'ob_gzhandler';
				}
				if ($bboptions['rewritestatus'])
				{
					$user_function[] = array(&$this, 'rewritestatus');
				}
				ob_start($user_function);
				echo $buffer;
			}
			$ob_started = true;
		}

		$dot = SAFE_MODE ? '_' : '/';
		$styleid = $bbuserinfo['style'];
		$tplfile = ROOT_PATH . "cache/templates/style_{$styleid}{$dot}{$template}.php";

		$recache = false;

		if (!file_exists($tplfile))
		{
			$this->set_template_obj();
			$recache = true;
		}

		if (!$recache && DEVELOPER_MODE === true)
		{
			$this->set_template_obj();
			$mtime = @filemtime($tplfile);
			$file = $this->template->get_template_filename($styleid, $template);
			if (@filemtime($file) > $mtime)
			{
				$recache = true;
			}
			else
			{
				$this->template->subtemplate_mtime($styleid, $template, $mtime, $recache);
			}
		}

		if ($recache)
		{
			$this->template->recachetemplates($styleid, $template);
			return $this->load_template($template);
		}

		return $tplfile;
	}

	/**
	 * 载入 CSS
	 *
	 * @param string $css CSS 名
	 * @param string CSS 缓存文件路径
	 */
	function load_css($css)
	{
		global $bbuserinfo;

		$styleid = $bbuserinfo['style'];
		$cssfile = ROOT_PATH . "cache/templates/style_{$styleid}.css";

		$recache = false;
		if (!DEVELOPER_MODE)
		{
			if (!file_exists($cssfile))
			{
				$this->set_template_obj();
				$recache = true;
			}
		}
		else
		{
			$mtime = @filemtime($cssfile);
			if ($mtime)
			{
				$this->set_template_obj();
				$file = $this->template->get_template_filename($styleid, $css . '.css');
				if (@filemtime($file) > $mtime)
				{
					$recache = true;
				}
			}
			else
			{
				$this->set_template_obj();
				$recache = true;
			}
		}

		if ($recache)
		{
			$this->template->writecsscache($styleid, false, $css);
			return $this->load_css($css);
		}

		return $cssfile;
	}

	function set_template_obj()
	{
		if (is_null($this->template))
		{
			require_once(ROOT_PATH . 'includes/adminfunctions_template.php');
			$this->template = new adminfunctions_template();
		}
	}

	function rewritestatus($buffer)
	{
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)(?:&amp;|&)st=([0-9]+)(?:&amp;|&)pp=([0-9]+)/i', 'forum-\\1-\\3-\\2.html', $buffer);
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)(?:&amp;|&)filter=quintessence(?:&amp;|&)pp=([0-9]+)/i', 'forum-\\1-q-\\2.html', $buffer);
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)(?:&amp;|&)filter=quintessence/i', 'forum-\\1-q.html', $buffer);
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)(?:&amp;|&)pp=([0-9]+)/i', 'forum-\\1-\\2.html', $buffer);
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)(?:&amp;|&)st=([0-9]+)/i', 'forum-\\1-0-\\2.html', $buffer);
		$buffer = preg_replace('/forumdisplay\.php\?f=([0-9]+)/i', 'forum-\\1.html', $buffer);
		$buffer = preg_replace('/profile\.php\?u=([0-9]+)/i', 'user-\\1.html', $buffer);
		$buffer = preg_replace('/showthread\.php\?t=([0-9]+)(?:&amp;|&)pp=([0-9]+)/i','thread-\\1-\\2.html', $buffer);
		$buffer = preg_replace('/showthread\.php\?t=([0-9]+)/i', 'thread-\\1.html', $buffer);
		$buffer = preg_replace('/index\.php\?f([0-9]+)-([0-9]+)\.html/i', 'f-\\1-\\2.html?', $buffer );
		$buffer = preg_replace('/index\.php\?f([0-9]+)\.html/i', 'f-\\1-0.html?', $buffer );
		$buffer = preg_replace('/index\.php\?t([0-9]+)-([0-9]+)\.html/i', 't-\\1-\\2.html?', $buffer );
		$buffer = preg_replace('/index\.php\?t([0-9]+)\.html/i' , 't-\\1-0.html?', $buffer );
		$buffer = preg_replace('/html(?:&amp;|&)extra=[^\'" >\/]*/i', 'html', $buffer);
		return $buffer;
	}

	function load_style()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$update = false;
		if (isset($_INPUT['styleid']) && $_INPUT['styleid'])
		{
			$this->set_cookie('styleid', $_INPUT['styleid']);
			$bbuserinfo['style'] = $_INPUT['styleid'];
			$update = true;
		}
		else
		{
			$cookie_style = $this->get_cookie('styleid');
			$bbuserinfo['style'] = $cookie_style ? $cookie_style : ((isset($bbuserinfo['style'])) ? $bbuserinfo['style'] : '');
		}

		if (!$bbuserinfo['style'] || !$forums->cache['style'][$bbuserinfo['style']]['userselect'])
		{
			foreach ($forums->cache['style'] as $data)
			{
				if ($data['usedefault'])
				{
					$bbuserinfo['style'] = $data['styleid'];
					$update = true;
					break;
				}
			}
		}

		if ($bbuserinfo['id'] && $update)
		{
			$DB->shutdown_update(TABLE_PREFIX . 'user', array('style' => intval($bbuserinfo['style'])), "id = {$bbuserinfo['id']}");
		}

		if (empty($forums->cache['style'][$bbuserinfo['style']]['imagefolder']))
		{
			$forums->cache['style'][$bbuserinfo['style']]['imagefolder'] = 'style_1';
		}
		$bbuserinfo['imgurl'] = $forums->cache['style'][$bbuserinfo['style']]['imagefolder'];
		return $bbuserinfo;
	}

	function finish()
	{
		if (!USE_SHUTDOWN)
		{
			$this->do_shutdown();
		}
	}

	function debug()
	{
		global $DB, $bboptions;
		$mtime = explode(' ', microtime());
		$starttime = explode(' ', STARTTIME);
		$totaltime = sprintf('%.6f', ($mtime[1] + $mtime[0] - $starttime[1] - $starttime[0]));
		echo 'Processed in ' . $totaltime . ' second(s), ';
		echo ($DB) ? $DB->query_count() . ' queries, ' : '';
		echo $bboptions['gzipoutput'] ? 'GZIP On' : 'GZIP Off';
	}

	function do_shutdown()
	{
		global $DB;
		$DB->close_db();
	}

	/**
	 * 字符串截取
	 *
	 * @param string $text
	 * @param int $limit
	 * @param bool $title
	 * @return string
	 */
	function fetch_trimmed_title($text, $limit = 12, $title = false)
	{
		$strong = $color = false;
		if ($title)
		{
			if (preg_match('#<strong>(?:.*)</strong>#siU', $text))
			{
				$strong = true;
			}
			if (preg_match('#<font color=(\'|")(?:.*)(\\1)>(?:.*)</font>#siU', $text))
			{
				$color = preg_replace('#<font color=(\'|")(.*)(\\1)>(?:.*)</font>#siU', '\\2', $text);
			}
		}
		$text = strip_tags(preg_replace('/\"javascript:.*[^>|^<].*\"/si', '', $text), '<br /><br>');

		$more = (utf8_strlen($text) > $limit) ? true : false;
		$text = $more ? utf8_substr($text, 0, $limit - 1) . '…' : $text;
		if ($title)
		{
			if ($color)
			{
				$text = '<span style="color:' . $color . ';">' . $text . '</span>';
			}
			if ($strong)
			{
				$text = '<strong>' . $text . '</strong>';
			}
		}
		return $text;
	}

	function md5_check()
	{
		global $bbuserinfo;
		return $bbuserinfo['id'] ? md5($bbuserinfo['email'] . '&' . $bbuserinfo['password'] . '&' . $bbuserinfo['joindate']) : '';
	}

	function fetch_permissions($forum_perm = '', $type)
	{
		global $forums, $bbuserinfo;
		if (!is_array($forums->perm_id_array))
		{
			return false;
		}
		if ($forum_perm == '-')
		{
			return false;
		}
		else if ($forum_perm == '*')
		{
			return true;
		}
		else if ($forum_perm == '')
		{
			switch ($type)
			{
				case 'canshow':
					return $bbuserinfo['canshow'];
				case 'canread':
					return $bbuserinfo['canviewothers'];
				case 'canreply':
					return $bbuserinfo['canreplyothers'];
				case 'canstart':
					return $bbuserinfo['canpostnew'];
				case 'canupload':
					return $bbuserinfo['attachlimit'] != -1 ? true : false;
				default:
					return false;
			}
		}
		else
		{
			$forum_perm = ',' . $forum_perm . ',';
			foreach($forums->perm_id_array as $u_id)
			{
				if (strpos($forum_perm, ",$u_id,") !== false)
				{
					return true;
				}
			}
			return false;
		}
	}

	function fetch_user_link($name, $id = 0)
	{
		global $forums;
		if (!$id)
		{
			return $name;
		}
		return "<a href='profile.php{$forums->sessionurl}u={$id}'>{$name}</a>";
	}

	function fetch_user($user = array())
	{
		global $forums, $bbuserinfo, $bboptions;
		$user['quintessence'] = $user['quintessence'] ? "<a href='findposts.php{$forums->sessionurl}do=getquintessence&amp;userid=" . $user['id'] . "' target='_blank'>" . fetch_number_format($user['quintessence']) . "</a>" : 0;
		$this->check_cache('usergroup');
		$user['name'] = $forums->cache['usergroup'][$user['usergroupid']]['opentag'] . $user['name'] . $forums->cache['usergroup'][ $user['usergroupid'] ]['closetag'];

		$user['avatar'] = $this->get_avatar($user['id'], $user['avatar'], intval($user['avatartype']));
		$ranklevel = 0;
		if (isset($user['posts']))
		{
			$this->check_cache('ranks');
			if (is_array($forums->cache['ranks']))
			{
				foreach($forums->cache['ranks'] as $k => $v)
				{
					if ($user['posts'] >= $v['post'])
					{
						if ($forums->cache['usergroup'][ $user['usergroupid'] ]['groupranks'])
						{
							$user['title'] = $forums->lang[ $forums->cache['usergroup'][ $user['usergroupid'] ]['groupranks'] ];
						}
						else if (!$user['title'])
						{
							$user['title'] = $forums->cache['ranks'][ $k ]['title'];
						}
						$ranklevel = $v['ranklevel'];
						break;
					}
				}
			}
		}

		if ($bbuserinfo['canevaluation'])
		{
			$user['canevaluation'] = true;
		}
		elseif($bbuserinfo['grouppower'] > $user['grouppower'])
		{
			$user['canevaluation'] = true;
		}
		else
		{
			$user['canevaluation'] = false;
		}
		$this->convert_bits_to_array($user, $user['options']);
		if ($forums->cache['usergroup'][ $user['usergroupid'] ]['groupicon'])
		{
			$user['rank'] = 1;
			$user['rank_ext'] = $forums->cache['usergroup'][ $user['usergroupid'] ]['groupicon'];
		}
		else if ($ranklevel)
		{
			if (is_numeric($ranklevel))
			{
				for ($i = 1; $i <= $ranklevel; ++$i)
				{
					$user['rank'] = 2;
					$user['rank_ext'][] = 1;
				}
			}
			else
			{
				$user['rank'] = 3;
				$user['rank_ext'] = $ranklevel;
			}
		}
		$user['joindate'] = $this->get_date($user['joindate'], 3);
		$user['grouptitle'] = $forums->lang[ $forums->cache['usergroup'][ $user['usergroupid'] ]['grouptitle'] ];
		$user['posts'] = fetch_number_format($user['posts']);
		$user['userid'] = fetch_number_format($user['id']);
		$user['pmicon'] = $user['usepm'] ? $user['id'] : 0;
		$user['email_icon'] = $user['hideemail'] ? 0 : $user['id'];
		$user['qq_other']['site'] = urlencode($bboptions['bburl']);
		$user['qq_icon'] = $user['qq'] ? 1 : 0;
		$user['uc_icon'] = $user['uc'] ? 1 : 0;
		$user['popo_icon'] = $user['popo'] ? 1 : 0;
		if (!$user['usercurdo'])
		{
			$user['usercurdo'] = $forums->lang['notfilldowhat'];
		}
		else
		{
			$user['userdocut'] = $this->fetch_trimmed_title($user['usercurdo'], 100);
		}
		$user['userdotime'] =$this->get_date($user['userdotime']);
		return $user;
	}

	function standard_redirect($url = '')
	{
		global $bboptions, $forums;
		$this->do_shutdown();
		if ($url === '')
		{
			$url = $bboptions['forumindex'] . $forums->si_sessionurl;
		}
		$url = str_replace('&amp;', '&', $url);
		if ($bboptions['headerredirect'] == 'refresh')
		{
			@header("Refresh: 0;url=$url");
		}
		else if ($bboptions['headerredirect'] == 'html')
		{
			while (ob_get_length())
			{
				ob_end_flush();
			}
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
		}
		else
		{
			@header("location: $url");
		}
		exit();
	}

	function make_password()
	{
		$pass = '';
		$chars = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J', 'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T', 'u', 'U', 'v', 'V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z');
		$array = array_rand($chars, 8);
		for ($i = 0; $i < 8; $i++)
		{
			$pass .= $chars[$array[$i]];
		}
		return $pass;
	}

	function folder_icon($thread, $last_time, $canopenclose = false)
	{
		global $forums, $bbuserinfo, $bboptions;
		$icons = 'folder.gif';
		$title = $forums->lang['_nonew'];
		if ($thread['open'] == 0)
		{
			$icons = 'closedfolder.gif';
			$title = $forums->lang['_closethread'];
		}
		else if ($thread['open'] == 2)
		{
			$icons = 'movedfolder.gif';
			$title = $forums->lang['_movethread'];
		}
		else if ($thread['post'] + 1 >= $bboptions['hotnumberposts'])
		{
			if ($thread['lastpost'] <= $last_time)
			{
				$icons = 'hotfolder.gif';
				$title = $forums->lang['_nonew'];
			}
			else
			{
				$icons = 'newhotfolder.gif';
				$title = $forums->lang['_new'];
			}
		}
		else if ($last_time && ($thread['lastpost'] > $last_time))
		{
			$icons = 'newfolder.gif';
			$title = $forums->lang['_new'];
		}
		if ($canopenclose || ($thread['postuserid'] == $bbuserinfo['id'] && $bbuserinfo['canopenclose']))
		{
			$click_event = ' onDblClick="open_close_thread(' . $thread['tid'] . ', ' . $thread['postuserid'] . ', ' . $thread['forumid'] . ');"';
		}
		$return['icons'] = '<img id="pic' . $thread['tid'] . '" class="inline" src="images/' . $bbuserinfo['imgurl'] . '/' . $icons . '" alt="' . $title . '"' . $click_event . ' />';
		return $return;
	}

	function build_pagelinks($data)
	{
		global $forums;
		$results['pages'] = ceil($data['totalpages'] / $data['perpage']);
		$results['total_page'] = $results['pages'] ? $results['pages'] : 1;
		$results['current_page'] = $data['curpage'] > 0 ? intval($data['curpage'] / $data['perpage']) + 1 : 1;
		$prevlink = '';
		$nextlink = '';
		if ($results['total_page'] <= 1)
		{
			return '';
		}
		else
		{
			if ($results['current_page'] > 1)
			{
				$start = $data['curpage'] - $data['perpage'];
				$prevlink = "<span><a href='{$data['pagelink']}&amp;pp={$start}' title='" . $forums->lang['_prevpage'] . "'>&lt;</a></span>";
			}
			if ($results['current_page'] < $results['total_page'])
			{
				$start = $data['curpage'] + $data['perpage'];
				$nextlink = "<span><a href='{$data['pagelink']}&amp;pp={$start}' title='" . $forums->lang['_nextpage'] . "'>&gt;</a></span>";
			}
			$pagenav = "<em><a title='" . $forums->lang['_jumppage'] . "' href=\"javascript:multi_page_jump('{$data['pagelink']}','{$data['totalpages']}','{$data['perpage']}');\">Total: {$results['total_page']}</a></em>";
			$minpage = $results['current_page'] - 6;
			$maxpage = $results['current_page'] + 5;
			$minpage = $minpage < 0 ? 0 : $minpage;
			$maxpage = $maxpage > $results['total_page'] ? $results['total_page'] : $maxpage;
			for($i = $minpage; $i < $maxpage; ++$i)
			{
				$numberid = $i * $data['perpage'];
				$pagenumber = $i + 1;
				if ($numberid == $data['curpage'])
				{
					$curpage .= "<strong><span>{$pagenumber}</span></strong>";
				}
				else
				{
					if ($pagenumber < ($results['current_page'] - 4))
					{
						$firstlink = "<span><a href='{$data['pagelink']}' title='" . $forums->lang['_firstpage'] . "'>&laquo;</a></span>";
						continue;
					}
					if ($pagenumber > ($results['current_page'] + 4))
					{
						$url = "{$data['pagelink']}&amp;pp=" . ($results['total_page']-1) * $data['perpage'];
						$lastlink = "<span><a href='$url' title='" . $forums->lang['_lastpage'] . "'>&raquo;</a></span>";
						continue;
					}
					$curpage .= "<span><a href='{$data['pagelink']}&amp;pp={$numberid}' title='$pagenumber'>$pagenumber</a></span>";
				}
			}
			return '<div class="pages">' . $pagenav . $firstlink . $prevlink . $curpage . $nextlink . $lastlink . '</div>';
		}
	}

	function build_threadpages($data)
	{
		global $forums, $bbuserinfo;
		$pages = 1;
		if ($data['totalpost'])
		{
			$totalpages = $pages = (($data['totalpost'] + 1) % $data['perpage']) == 0 ? ($data['totalpost'] + 1) / $data['perpage'] : ceil((($data['totalpost'] + 1) / $data['perpage']));
		}
		if ($data['extra'] && !$this->extra)
		{
			$this->extra = "&amp;extra={$data['extra']}";
		}
		if ($pages > 1)
		{
			$pages = $pages > 7 ? 7 : $pages;
			for ($i = 0 ; $i < $pages ; ++$i)
			{
				$real_no = $i * $data['perpage'];
				$page_no = $i + 1;
				if ($page_no == 6 && $pages > 6)
				{
					$real_no = ($totalpages - 1) * $data['perpage'];
					$pagelink .= '...';
					$pagelink .= "<span class='minipagelink'><a href='showthread.php{$forums->sessionurl}t=" . $data['id'] . "&amp;pp=" . $real_no . "{$this->extra}'>" . $totalpages . "</a></span>";
					break;
				}
				else
				{
					$pagelink .= "<span class='minipagelink'><a href='showthread.php{$forums->sessionurl}t=" . $data['id'] . "&amp;pp=" . $real_no . "{$this->extra}'>" . $page_no . "</a></span>";
				}
			}
			$thread['ppages'] = $data['totalpost'] + 1;
			$pagelink = "&nbsp;<a href=\"javascript:multi_page_jump('showthread.php{$forums->sessionurl}t=" . $data['id'] . "{$this->extra}'," . $thread['ppages'] . "," . $data['perpage'] . ");\" title='" . $forums->lang['_multipage'] . "'><img src='images/" . $bbuserinfo['imgurl'] . "/multipage.gif' alt='' border='0' /></a> " . $pagelink;
		}
		return $pagelink;
	}

	function construct_forum_jump($html = 1, $override = 0)
	{
		global $forums;
		if ($html == 1)
		{
			$forumjump = "<form onsubmit=\"if(document.jumpmenu.f.value == -1){return false;}\" action='forumdisplay.php' method='get' name='jumpmenu'>
			             <input type='hidden' name='s' value='" . $forums->sessionid . "' />
			             <select name='f' class='select_normal' onchange=\"if(this.options[this.selectedIndex].value != -1){ document.jumpmenu.submit() }\">
			             <optgroup label='" . $forums->lang['_jumpto'] . "'>
			              <option value='home'>" . $forums->lang['_boardindex'] . "</option>
			              <option value='search'>" . $forums->lang['_search'] . "</option>
			              <option value='faq'>" . $forums->lang['_faq'] . "</option>
			              <option value='cp'>" . $forums->lang['_usercp'] . "</option>
			              <option value='wol'>" . $forums->lang['_online'] . "</option>
			             </optgroup>
			             <optgroup label='" . $forums->lang['_boardjump'] . "'>";
		}
		$forumjump .= $forums->forum->forum_jump($html, $override);
		if ($html == 1)
		{
			$forumjump .= "</optgroup>\n</select>&nbsp;<input type='submit' value='" . $forums->lang['_ok'] . "' class='button_normal' /></form>";
		}
		return $forumjump;
	}

	function get_time($date, $method = 'h:i A')
	{
		if ($this->time_offset == 0)
		{
			$this->offset = $this->get_time_offset();
			$this->time_offset = 1;
		}
		return gmdate($method, ($date + $this->offset));
	}

	function mk_time($hour, $minute, $second, $month, $day, $year)
	{
		if ($this->time_offset == 0)
		{
			$this->offset = $this->get_time_offset();
			$this->time_offset = 1;
		}
		return gmmktime($hour, $minute, $second, $month, $day, $year) - $this->offset;
	}

	function get_format_date($date, $method)
	{
		global $bboptions;
		$timeoptions = array(
			1 => $bboptions['standardtimeformat'],
			2 => $bboptions['longtimeformat'],
			3 => $bboptions['registereddateformat'],
			4 => 'm-d',
			5 => 'Y-m-d',
		);
		if (empty($method))
		{
			$method = 2;
		}
		return $this->get_time($date, $timeoptions[$method]);
	}

	/**
	* 显示亲和时间
	*
	*/
	function get_date($date = 0, $method = 2, $type = 0)
	{
		global $forums, $bboptions;
		if (!$date)
		{
			return '';
		}

		if ($type || $bboptions['show_format_time'])
		{
			return $this->get_format_date($date, $method);
		}

		$seconds = TIMENOW - $date;
		$minutes = $seconds / 60;

		if ($minutes < 60)
		{
			if ($minutes < 1)
			{
				$showtime = $seconds . $forums->lang['secondsago'];
			}
			else
			{
				$showtime = intval($minutes) . $forums->lang['minutesago'];
			}
		}
		elseif ($minutes < 1440)
		{
			$showtime = intval($minutes / 60) . $forums->lang['hoursago'];
		}
		elseif ($minutes < 14400)
		{
			$showtime = intval($minutes / 1440) . $forums->lang['somedaysago'];
		}
		else
		{
			$showtime = $this->get_format_date($date, $method);
		}

		return $showtime;
	}

	function get_time_offset()
	{
		global $forums, $bbuserinfo, $bboptions;
		$r = 0;
		$bbuserinfo['timezoneoffset'] = intval($bbuserinfo['timezoneoffset']) ? $bbuserinfo['timezoneoffset'] : $bboptions['timezoneoffset'];
		$r = $bbuserinfo['timezoneoffset'] * 3600;
		if ($bboptions['timeadjust'])
		{
			$r += ($bboptions['timeadjust'] * 60);
		}
		if (isset($bbuserinfo['dstonoff']) && $bbuserinfo['dstonoff'])
		{
			$r += 3600;
		}
		if ($bbuserinfo['timezoneoffset'] > 0 && $bbuserinfo['timezoneoffset'] != 8 && strpos($bbuserinfo['timezoneoffset'], '+') === false)
		{
			$bbuserinfo['timezone'] = $forums->lang['_gmt'] . '+' . $bbuserinfo['timezoneoffset'];
		}
		else if ($bbuserinfo['timezoneoffset'] == 8 && strpos($bbuserinfo['timezoneoffset'], '+') === false)
		{
			$bbuserinfo['timezone'] = $forums->lang['_bjt'];
		}
		else if ($bbuserinfo['timezoneoffset'] < 0)
		{
			$bbuserinfo['timezone'] = $forums->lang['_gmt'] . $bbuserinfo['timezoneoffset'] . $forums->lang['_hours'];
		}
		else
		{
			$bbuserinfo['timezone'] = $forums->lang['_gmt'];
		}
		return $r;
	}

	function get_user_options()
	{
		return array(
			'adminemail' => 4,
			'dstonoff' => 8,
			'hideemail' => 16,
			'usepm' => 32,
			'pmpop' => 64,
			'emailonpm' => 128,
			'usewysiwyg' => 256,
			'invisible' => 512,
			'loggedin' => 1024,
			'redirecttype' => 2048,
			'pmover' => 4096,
			'pmwarn' => 8192,
			'pmwarnmode' => 16384,
		);
	}

	function convert_bits_to_array(&$array, $bitfield)
	{
		$user_options = $this->get_user_options();
		$bitfield = intval($bitfield);
		foreach ($user_options as $field => $bitvalue)
		{
			$array[$field] = ($bitfield &$bitvalue) ? 1 : 0;
		}
	}

	function convert_array_to_bits($arry, $unset = 0)
	{
		$user_options = $this->get_user_options();
		$bits = 0;
		foreach($user_options as $fieldname => $bitvalue)
		{
			if ($arry[$fieldname] == 1)
			{
				$bits += $bitvalue;
			}
			if ($unset)
			{
				unset($arry[$fieldname]);
			}
		}
		return $bits;
	}

	function set_cookie($name, $value = '', $cookiedate = 0)
	{
		global $forums, $bboptions;
		if ($forums->noheader)
		{
			return;
		}
		$expires = ($cookiedate > 0) ? TIMENOW + $cookiedate : null;
		$bboptions['cookiedomain'] = $bboptions['cookiedomain'] == '' ? '' : $bboptions['cookiedomain'];
		$bboptions['cookiepath'] = $bboptions['cookiepath'] == '' ? '/' : $bboptions['cookiepath'];
		$name = $bboptions['cookieprefix'] . $name;
		$value = rawurlencode($value);
		@setcookie($name, $value, $expires, $bboptions['cookiepath'], $bboptions['cookiedomain']);
	}

	function get_cookie($name)
	{
		global $bboptions;
		if (isset($_COOKIE[$bboptions['cookieprefix'] . $name]))
		{
			return rawurldecode($_COOKIE[$bboptions['cookieprefix'] . $name]);
		}
		return false;
	}

	function set_up_guest($name = '')
	{
		global $forums, $bboptions;
		return array(
			'name' => $name ? $name : $forums->lang['_guset'],
			'id' => 0,
			'password' => '',
			'email' => '',
			'title' => $forums->lang['_unregister'],
			'usergroupid' => 2,
			'timezoneoffset' => $bboptions['timezoneoffset'],
		);
	}

	function get_avatar($uid = 0, $avatar = 0, $showtype = 0)
	{
		global $bboptions, $bbuserinfo;
		if (!$bboptions['avatarsenabled'])
		{
			return '';
		}
		$userdir = $bboptions['uploadurl'] . '/user';
		if ($avatar)
		{
			$avatar_path = split_todir($uid, $userdir);
			$return = $avatar_path[0] . '/' . 'a-' . $uid . '-' . $showtype . '.jpg';
		}
		else
		{
			if (!$uid)
			{
				$classname = ' guest';
			}
			$return = $userdir . '/' . 'a-default-' . $showtype . '.jpg';
		}
		$return = '<img class="user_avatar' . $classname . '" src="' . $return . '"';
		if (($bbuserinfo['supermod'] || $bbuserinfo['caneditusers']) && $avatar && !$showtype)
		{
			$return .= ' onmouseover="edit_user_avatar(' . $uid . ', this);" onmouseout="hide_avatar_opt();"';
		}
		$return .= ' alt="" />';
		return $return;
	}

	/**
	 * 处理头像
	 *
	 */
	function bulid_avatars($real_name, $uid)
	{
		global $bboptions;
		$dest_dir = split_todir($uid);
		checkdir($bboptions['uploadfolder'] . '/user' . $dest_dir[0], $dest_dir[1] + 1);
		$path = $bboptions['uploadfolder'] . '/user' . $dest_dir[0];
		$avatarsizeset = explode('|', $bboptions['avatardimension']);
		require_once(ROOT_PATH . 'includes/functions_image.php');
		$image = new functions_image();
		$image->forcemake = 1;  //图片大小不足时强制生成
		$image->maketype = 1;  //裁减头像
		$image->filepath = $path;
		$image->filename = $real_name;

		$image->thumb_filename = 'a-' . $uid . '-1';
		list($p_width, $p_height) = explode('x', $avatarsizeset[1]);
		$image->thumbswidth = $p_width ? $p_width : '48';
		$image->thumbsheight = $p_height ? $p_height : '48';
		$image->generate_thumbnail();

		$image->thumb_filename = 'a-' . $uid . '-2';
		list($p_width, $p_height) = explode('x', $avatarsizeset[2]);
		$image->thumbswidth = $p_width ? $p_width : '18';
		$image->thumbsheight = $p_height ? $p_height : '18';
		$image->generate_thumbnail();

		$image->thumb_filename = 'a-' . $uid . '-0';
		list($p_width, $p_height) = explode('x', $avatarsizeset[0]);
		$image->thumbswidth = $p_width ? $p_width : '120';
		$image->thumbsheight = $p_height ? $p_height : '120';
		$image->generate_thumbnail();
	}

	/**
	 * 基本错误
	 *
	 * @param string $message 错误信息语言键值
	 * @param boolean $nologin 查看错误信息是否不需要登录
	 * @param string $replace 替换语言文字中格式说明符的文字
	 */
	function standard_error($message, $nologin = false, $replace = '')
	{
		global $DB, $forums, $bboptions, $bbuserinfo;
		$this->load_lang('error');
		$message = isset($forums->lang[$message]) ? $forums->lang[$message] : $message;
		if (!empty($replace))
		{
			$message = sprintf($message, $replace);
		}
		list($user, $domain) = explode('@', $bboptions['emailreceived']);

		$DB->shutdown_update(TABLE_PREFIX . 'session', array('badlocation' => 1), "sessionhash = '{$forums->sessionid}'");

		$suffix = $this->errheader ? '_index' : '_body';
		if (!$bbuserinfo['id'] && THIS_SCRIPT != 'register' && THIS_SCRIPT != 'login' && !$nologin)
		{
			$this->load_lang('login');
			$show = array('errors' => true);
			$referer = SCRIPTPATH;
			$nav = array($forums->lang['dologin']);
			$pagetitle = $forums->lang['dologin'] . ' - ' . $bboptions['bbtitle'];
			include $this->load_template('login' . $suffix);
		}
		else
		{
			$nav = array($forums->lang['errorsinfo']);
			$pagetitle = $forums->lang['errorsinfo'] . ' - ' . $bboptions['bbtitle'];
			include $this->load_template('errors' . $suffix);
		}
		exit;
	}

	function redirect_screen($text = '', $url = '', $override = 0)
	{
		global $forums, $bboptions, $bbuserinfo;
		if ($override != 1)
		{
			if ($url == '')
			{
				$url = $bboptions['forumindex'];
			}
		}
		$url = preg_replace('#(.*)(^|\.php)#', '\\1\\2' . $forums->sessionurl, str_replace("?", '', preg_replace('!s=(\w){32}!', '', $url)));
		if ($bboptions['removeredirect'])
		{
			$this->standard_redirect($url);
		}
		include $this->load_template('redirect');
		exit;
	}

	/**
	 * 生成语言选择 options
	 */
	function generate_lang()
	{
		global $forums, $bboptions;
		if (empty($this->lang_list))
		{
			require(ROOT_PATH . 'languages/list.php');
			$this->lang_list = $lang_list;
		}

		$c = '<optgroup label="' . $forums->lang['_selected_lang'] . '">';
		foreach ($this->lang_list as $dir => $name)
		{
			$selected = ($bboptions['language'] == $dir) ? ' selected="selected"' : '';
			$c .= '<option value="' . $dir . '"' . $selected . '>' . $name . '</option>';
		}
		$c .= '</optgroup>';
		return $c;
	}

	/**
	 * 生成风格选择 options
	 */
	function generate_style()
	{
		global $forums, $bbuserinfo;
		$select_style = '<optgroup label="' . $forums->lang['_selected_style'] . '">';
		foreach ($forums->cache['style'] as $id => $style)
		{
			$selected = ($id == $bbuserinfo['style']) ? ' selected="selected"' : '';
			$select_style .= '<option value="' . $id . '"' . $selected . '>';
			$select_style .= depth_mark($style['depth'], '--') . ' ' . $style['title'];
			$select_style .= '</option>';
		}
		$select_style .= '</optgroup>';
		return $select_style;
	}

	/**
	 * 检查当前用户的语言选择
	 */
	function check_lang()
	{
		global $bboptions, $_INPUT;
		if (empty($this->lang_list))
		{
			require(ROOT_PATH . 'languages/list.php');
			$this->lang_list = $lang_list;
		}
		if (isset($_INPUT['lang']) && isset($this->lang_list[$_INPUT['lang']]))
		{
			$this->set_cookie('language', $_INPUT['lang']);
			$bboptions['language'] = $_INPUT['lang'];
		}
		else
		{
			$bboptions['language'] = $this->get_cookie("language");
		}

		if (isset($this->lang_list[$bboptions['language']]))
		{
			$bboptions['language'] = $bboptions['language'];
		}
		else if (isset($this->lang_list[$bboptions['default_lang']]))
		{
			$bboptions['language'] = $bboptions['default_lang'];
		}
		else
		{
			$this->set_cookie('language', 'zh-cn');
			$bboptions['language'] = 'zh-cn';
		}
	}

	/**
	 * 载入语言词条
	 *
	 * @param string $name 语言文件名
	 * @param boolean $return 是否返回语言数组
	 */
	function load_lang($name, $return = false)
	{
		global $forums, $bboptions;
		static $loaded = array();

		if (empty($bboptions['language']))
		{
			$bboptions['language'] = 'zh-cn';
		}

		$hash = $bboptions['language'] . $name;
		if (!$return && isset($loaded[$hash]))
		{
			return;
		}

		@include(ROOT_PATH . 'languages/' . $bboptions['language'] . '/' . $name . '.php');

		if (!isset($forums->lang))
		{
			$forums->lang = array();
		}

		if (isset($lang))
		{
			$loaded[$hash] = true;
			$forums->lang = array_merge($forums->lang, (array) $lang);
			if ($return)
			{
				return $lang;
			}
		}
		else
		{
			trigger_error('Can not found language file: ' . $name . '.php', E_USER_WARNING);
		}
	}

	/**
	 * 载入语言 JavaScript 文件
	 *
	 * @param string $name 语言 JS 文件名
	 */
	function load_lang_js($name)
	{
		global $bboptions;
		if (empty($bboptions['language']))
		{
			$bboptions['language'] = 'zh-cn';
		}

		$file = ROOT_PATH . 'languages/' . $bboptions['language'] . '/' . $name . '.js';
		if (file_exists($file))
		{
			return $file;
		}
		else
		{
			trigger_error('Can not found language file: ' . $name . '.js', E_USER_WARNING);
		}
	}

	/**
	 * 显示广告
	 *
	 * @param string $type 类型
	 * @param intager $fid 版面 ID
	 */
	function check_ad($type = '', $fid = 0)
	{
		global $forums;
		$forums->func->check_cache('ad');
		if (!empty($forums->cache['ad']))
		{
			if (is_null($forums->ads))
			{
				require_once(ROOT_PATH . 'includes/functions_checkad.php');
				$forums->ads = new functions_checkad();
			}
			$forums->ads->check_ad($type, $fid);
		}
	}

	/**
	 * 检查代码类型是否支持
	 */
	function check_code_type($type)
	{
		static $code_types = array();
		if (empty($code_types))
		{
			$code_types = array('php', 'sql', 'xml', 'css', 'javascript', 'java', 'c', 'c#', 'ruby', 'python', 'vb', 'delphi');
		}
		return in_array($type, $code_types);
	}

	/**
	 * 取得当前插入的帖子表名
	 *
	 */
	function getposttable()
	{
		global $forums, $DB;

		$this->check_cache('splittable');
		$splittable = $forums->cache['splittable'];
		$defaulttbl = $splittable['default'];
		if (empty($defaulttbl))
		{
			$defaulttbl = $splittable['all'][1];
		}

		if ($defaulttbl['isempty'])
		{
			$curid = intval(str_replace('post', '', $defaulttbl['name']));
			$id = $curid - 1;
			$preid = $id > 0 ? $id : '';
			$pretable = 'post' . $preid;

			$getpid = $DB->query_first("SELECT max(pid) as maxpid FROM " . TABLE_PREFIX . $pretable);
			if ($getpid['maxpid'] < 0)
			{
				$forums->func->standard_error("cannotmaxpid");
			}
			$defaulttbl['pid'] = $getpid['maxpid'] + 1;
		}

		return $defaulttbl;
	}

	function check_usrext_field($id = 0)
	{
		global $forums, $_INPUT, $DB;
		$forums->func->load_lang('error');
		$this->check_cache('userextrafield');
		$userextrafield = $forums->cache['userextrafield'];
		if (!$userextrafield['a'])
		{
			return '';
		}
		if ($userextrafield['f']) //检测必填项
		{
			foreach ($userextrafield['f'] as $k => $v)
			{
				if (!isset($_INPUT[$k]) || $_INPUT[$k] === '')
				{
					$forums->lang['error_mustfill'] = sprintf($forums->lang['error_mustfill'], $v);
					return array('err' => 'error_mustfill');
				}
			}
		}
		if ($userextrafield['r']) //检测正则项
		{
			foreach ($userextrafield['r'] as $k => $v)
			{
				if ($_INPUT[$k] && preg_match("/{$v[1]}/", $_INPUT[$k]))
				{
					$forums->lang['error_preg'] = sprintf($forums->lang['error_preg'], $v[0]);
					return array('err' => 'error_preg');
				}
			}
		}
		if ($userextrafield['o']) //检测唯一项
		{
			if ($id)
			{
				$extra_cond = ' AND id != ' . $id;
			}
			foreach ($userextrafield['o'] as $k => $v)
			{
				$checkonly = $DB->query_first("SELECT * FROM " . TABLE_PREFIX . $userextrafield['a'][$k]['tablename'] . "
						WHERE {$k}='" . addslashes($_INPUT[$k]) . "'" . $extra_cond);
				if ($checkonly)
				{
					$forums->lang['error_only'] = sprintf($forums->lang['error_only'], $v);
					return array('err' => 'error_only');
				}
			}
		}
		$ret = array();
		foreach ($userextrafield['a'] as $k => $v)
		{
			$len = utf8_strlen($_INPUT[$k]);
			if (($v['minlength'] && $len < $v['minlength']) || ($v['maxlength'] && $len > $v['maxlength']))
			{
				$forums->lang['error_length'] = sprintf($forums->lang['error_length'], $v['fieldname'], $v['minlength'], $v['maxlength']);
				return array('err' => 'error_length');
			}
			$ret[$v['tablename']][$k] = $_INPUT[$k];
		}
		return $ret;
	}

	function fetch_user_digg_exp()
	{
		global $bbuserinfo, $forums, $bboptions;
		if (!$bboptions['diggexps'])
		{
			return 0;
		}
		preg_match_all('/(\w)+/', $bboptions['diggexps'], $diggp);
		if (!$diggp[0])
		{
			return 0;
		}
		$pregfind = $pregreplace = array();
		foreach ($diggp[0] AS $k)
		{
			$pregfind[] = $k;
			if (!is_numeric($k))
			{
				if (intval($bbuserinfo[$k]) < 0)
				{
					$pregreplace[] = 0;
				}
				else
				{
					$pregreplace[] = $bbuserinfo[$k];
				}
			}
			else
			{
				$pregreplace[] = intval($k);
			}
		}
		$exp = str_replace($pregfind, $pregreplace, $bboptions['diggexps']);
		@eval("\$exp = $exp;");
		$exp = $exp > 0 ? $exp : 0;
		return floatval($exp);
	}
}

/**
 * 编码转换
 *
 * @param string $str 要转换的字符串
 * @param string $from 原编码
 * @param string $to 目的编码
 * @return string 转换结果
 */
function convert_encoding($str, $from = '', $to = '')
{
	static $convert = null;
	if (is_null($convert))
	{
		require_once(ROOT_PATH . 'includes/class_encoding.php');
		$convert = new encoding();
	}
	return $convert->convert($str, $from, $to);
}

/**
 * 生成用户加密干扰码
 *
 * @param intager $length 干扰码长度
 */
function generate_user_salt($length = 5)
{
	$salt = '';
	for ($i = 0; $i < $length; $i++)
	{
		$salt .= chr(mt_rand(32, 126));
	}
	return $salt;
}

/**
 * 写文件
 *
 * @return intager 写入数据的字节数
 */
function file_write($filename, $content, $mode = 'rb+')
{
	$length = strlen($content);
	@touch($filename);
	if (!is_writeable($filename))
	{
		@chmod($filename, 0666);
	}

	if (($fp = @fopen($filename, $mode)) === false)
	{
		trigger_error('file_write() failed to open stream: Permission denied', E_USER_WARNING);
		return false;
	}

	flock($fp, LOCK_EX | LOCK_NB);

	$bytes = 0;
	if (($bytes = @fwrite($fp, $content)) === false)
	{
		$errormsg = sprintf('file_write() Failed to write %d bytes to %s', $length, $filename);
		trigger_error($errormsg, E_USER_WARNING);
		return false;
	}

	if ($mode == 'rb+')
	{
		@ftruncate($fp, $length);
	}

	@fclose($fp);

	// 检查是否写入了所有的数据
	if ($bytes != $length)
	{
		$errormsg = sprintf('file_write() Only %d of %d bytes written, possibly out of free disk space.', $bytes, $length);
		trigger_error($errormsg, E_USER_WARNING);
		return false;
	}

	// 返回长度
	return $bytes;
}

/**
 * 检查目录是否存在, 不存在则建立
 *
 * @param string $dir 要检查的路径
 * @param integer $layer 检查层级, 从后向前算
 * @param boolean $is_file 是否是文件的路径
 */
function checkdir($dir, $layer = 0, $is_file = false)
{
	static $stats = array();
	$hash = $dir . ':' . $layer . ':' . $is_file;
	if (!isset($stats[$hash]))
	{
		$dir = format_path($dir);
		$root = format_path(ROOT_DIR . '/');

		$check = '';
		if (strpos($dir, $root) === 0)
		{
			$dir = substr($dir, strlen($root));
			$check = $root;
		}

		if (strrchr($dir, '/') == '/')
		{
			$dir = substr($dir, 0, -1);
		}

		$dir = explode('/', $dir);
		if ($is_file)
		{
			$file = array_pop($dir);
		}
		$n = count($dir);
		$x = $n - $layer;

		$stats[$hash] = true;
		for ($i = 0; $i < $n; $i++)
		{
			if ($dir[$i] == '')
			{
				continue;
			}

			$check .= $dir[$i] . '/';
			if (($layer && $i < $x) || in_array(substr($check, -2), array('./', ':/')))
			{
				continue;
			}

			if (!is_dir($check))
			{
				if (!@mkdir($check, 0777))
				{
					$stats[$hash] = false;
				}
			}
			else if (!is_writable($check))
			{
				if (!@chmod($check, 0777))
				{
					$stats[$hash] = false;
				}
			}
		}

		if ($is_file)
		{
			$file = $check . $file;
			if (file_exists($file) && !is_writable($check . $file) && !@chmod($file, 0666))
			{
				$stats[$hash] = false;
			}
		}
	}
	return $stats[$hash];
}

/**
 * 检查 Email 格式是否正确
 */
function clean_email($email = '')
{
	$email = trim($email);
	$email = str_replace(' ', '', $email);
	$email = preg_replace('#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#', '', $email);
	if (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/', $email))
	{
		return $email;
	}
	return '';
}

/**
 * 转换所有的 & 为 &amp;
 */
function convert_andstr($text = '')
{
	return str_replace(array('&amp;', '&'), array('&', '&amp;'), $text);
}

/**
 * 浏览器检查
 *
 * @param string $browser 类型
 * @param string $version 最低版本
 */
function is_browser($browser, $version = 0)
{
	static $is;
	if (!is_array($is))
	{
		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$is = array(
			'opera' => 0,
			'ie' => 0,
			'mozilla' => 0,
			'firebird' => 0,
			'firefox' => 0,
			'camino' => 0,
			'konqueror' => 0,
			'safari' => 0,
			'webkit' => 0,
			'webtv' => 0,
			'netscape' => 0,
			'mac' => 0
		);
		$regs = array();

		if (strpos($useragent, 'opera') !== false)
		{
			preg_match('#opera(/| )([0-9\.]+)#', $useragent, $regs);
			$is['opera'] = $regs[2];
		}
		else if (strpos($useragent, 'msie ') !== false)
		{
			preg_match('#msie ([0-9\.]+)#', $useragent, $regs);
			$is['ie'] = $regs[1];
		}

		if (strpos($useragent, 'mac') !== false)
		{
			$is['mac'] = 1;
			if (strpos($useragent, 'applewebkit') !== false)
			{
				preg_match('#applewebkit/(\d+)#', $useragent, $regs);
				$is['webkit'] = $regs[1];

				if (strpos($useragent, 'safari') !== false)
				{
					preg_match('#safari/([0-9\.]+)#', $useragent, $regs);
					$is['safari'] = $regs[1];
				}
			}
		}

		if (strpos($useragent, 'konqueror') !== false)
		{
			preg_match('#konqueror/([0-9\.-]+)#', $useragent, $regs);
			$is['konqueror'] = $regs[1];
		}
		else if (strpos($useragent, 'gecko') !== false && !$is['safari'])
		{
			preg_match('#gecko/(\d+)#', $useragent, $regs);
			$is['mozilla'] = $regs[1];

			if (strpos($useragent, 'firefox') !== false || strpos($useragent, 'firebird') !== false || strpos($useragent, 'phoenix') !== false)
			{
				preg_match('#(phoenix|firebird|firefox)( browser)?/([0-9\.]+)#', $useragent, $regs);
				$is['firebird'] = $regs[3];

				if ($regs[1] == 'firefox')
				{
					$is['firefox'] = $regs[3];
				}
			}

			if (strpos($useragent, 'chimera') !== false || strpos($useragent, 'camino') !== false)
			{
				preg_match('#(chimera|camino)/([0-9\.]+)#', $useragent, $regs);
				$is['camino'] = $regs[2];
			}
		}

		if (strpos($useragent, 'webtv') !== false)
		{
			preg_match('#webtv/([0-9\.]+)#', $useragent, $regs);
			$is['webtv'] = $regs[1];
		}

		if (preg_match('#mozilla/([1-4]{1})\.([0-9]{2}|[1-8]{1})#', $useragent, $regs))
		{
			$is['netscape'] = $regs[1] . $regs[2];
		}
	}

	$browser = strtolower($browser);
	if (strncmp($browser, 'is_', 3) === 0)
	{
		$browser = substr($browser, 3);
	}

	if ($is[$browser])
	{
		if ($version)
		{
			if ($is[$browser] >= $version)
			{
				return $is[$browser];
			}
		}
		else
		{
			return $is[$browser];
		}
	}

	return 0;
}

/**
 * 统一过滤 _GET 和 _POST 数组 或者指定数组
 */
function init_input($spec_array = array())
{
	if (empty($_GET) && empty($_POST) && empty($spec_array))
	{
		return array();
	}

	$return = array();
	foreach(array($_GET, $_POST, $spec_array) as $type)
	{
		if (is_array($type))
		{
			foreach ($type as $k => $v)
			{
				$k = clean_key($k);
				if (is_array($v))
				{
					foreach ($v as $k1 => $v1)
					{
						$k1 = clean_key($k1);
						$return[$k][$k1] = clean_value($v1);
					}
				}
				else
				{
					$return[$k] = clean_value($v);
				}
			}
		}
	}
	return $return;
}

/**
 * 过滤数组索引
 */
function clean_key($key)
{
	if (is_numeric($key))
	{
		return $key;
	}
	else if (empty($key))
	{
		return '';
	}

	if (strpos($key, '..') !== false)
	{
		$key = str_replace('..', '', $key);
	}

	if (strpos($key, '__') !== false)
	{
		$key = preg_replace('/__.+?__/', '', $key);
	}

	return preg_replace('/^([\w\.\-_]+)$/', '\\1', $key);
}

/**
 * 过滤输入的数据
 *
 * @param unknown_type $val
 * @return unknown
 */
function clean_value($val)
{
	if (is_numeric($val))
	{
		return $val;
	}
	else if (empty($val))
	{
		return is_array($val) ? array() : '';
	}

	$val = preg_replace('/&(?!#[0-9]+;)/si', '&amp;', $val);
	$val = preg_replace("/<script/i", "&#60;script", $val);

	$val = str_replace(
		array('&#032;', '<!--', '-->', '>', '<', '"', '!', "'", "\n", '$', "\r"),
		array(' ', '&#60;&#33;--', '--&#62;', '&gt;', '&lt;', '&quot;', '&#33;', '&#39;', '<br />', '&#036;', ''),
		$val
	);

	return preg_replace('/\\\(&amp;#|\?#)/', '&#092;', $val);
}

/**
 * 恢复被过滤得字符, 用于计算未过滤的字符串长度
 */
function unclean_value($val)
{
	$val = str_replace(
		array('&gt;', '&lt;', '&quot;', '&#33;', '&#39;', '&#60;', '&#62;', '&#036;', '&#092;', '&amp;'),
		array('>', '<', '"', '!', '\'', '<', '>', '$', '\\', '&'),
		$val
	);
	return $val;
}

/**
 * 把所有换行符转换成 \n
 */
function br2nl($text = '')
{
	return preg_replace("#(?:\n|\r)?<br.*>(?:\n|\r)?#", "\n", $text);
}

/**
 * 格式化数字
 *
 * @param boolean $bytesize 是否带字节单位
 */
function fetch_number_format($number, $bytesize = false)
{
	global $bboptions;
	$decimals = 0;
	$type = '';
	if ($bytesize)
	{
		if ($number >= 1073741824)
		{
			$decimals = 2;
			$number = round($number / 1073741824 * 100) / 100;
			$type = ' GB';
		}
		else if ($number >= 1048576)
		{
			$decimals = 2;
			$number = round($number / 1048576 * 100) / 100;
			$type = ' MB';
		}
		else if ($number >= 1024)
		{
			$decimals = 1;
			$number = round($number / 1024 * 100) / 100;
			$type = ' KB';
		}
		else
		{
			$decimals = 0;
			$type = ' Bytes';
		}
	}

	if ($bboptions['numberformat'] != 'none')
	{
		$number = str_replace('_', '&nbsp;', number_format($number , $decimals, '.', $bboptions['numberformat']));
	}

	return $number . $type;
}

/**
 * 构造深度标记
 *
 * @param intager $depth 深度
 * @param string $depthchar 深度重复字符
 * @param string $leftmark 重复字符前的标记
 * @param string $leftmark 重复字符后的标记
 */
function depth_mark($depth, $depthchar, $leftmark = '', $rightmark = '')
{
	$depthchar = str_repeat($depthchar, (int) $depth);
	return $leftmark . $depthchar . $rightmark;
}

/**
 * 生成 select 的 options
 */
function select_options($array, $key = '')
{
	$return = '';
	foreach ($array as $k => $v)
	{
		$return .= "<option value=\"$k\"";
		$return .= ($k == $key) ? " selected=\"selected\"" : '';
		$return .= ">$v</option>";
	}
	return $return;
}

/**
 * 屏蔽时间检查
 */
function banned_detect($bline)
{
	if (is_array($bline))
	{
		$factor = ($bline['unit'] == 'd') ? 86400 : 3600;
		$date_end = ($bline['timespan'] == -1) ? -1 : TIMENOW + ($bline['timespan'] * $factor);
		return TIMENOW . ':' . $date_end . ':' . $bline['timespan'] . ':' . $bline['unit'] . ':' . $bline['groupid'] . ':' . $bline['banuser'] . ':' . $bline['banposts'] . ':' . $bline['forumid'];
	}
	else
	{
		$arr = array();
		list($arr['date_start'], $arr['date_end'], $arr['timespan'], $arr['unit'], $arr['groupid'], $arr['banuser'], $arr['banposts'], $arr['forumid']) = explode(':', $bline);
		return $arr;
	}
}

/**
 * 页面缓存结束
 */
function end_cache_page()
{
	global $forums;
	if (!is_null($forums->page_cache))
	{
		$forums->page_cache->end();
	}
}

/**
 * 在 Head 加载 JavaScript 和 CSS
 *
 * @param string $type js 表示 JavaScript 文件, css 表示 CSS 文件, js-c 表示 JS 脚本
 */
function add_head_element($type = 'js', $filename = '')
{
	static $return = '';
	if ($type != 'echo')
	{
		if (empty($filename))
		{
			return;
		}

		switch ($type)
		{
			case 'js':
				$return .= "<script src=\"" . $filename . "\" type=\"text/javascript\"></script>\n";
			break;
			case 'css':
				$return .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $filename . "\" />\n";
			break;
			case 'js-c':
				$return .= "<script type=\"text/javascript\">\n//<![CDATA[\n" . $filename . "\n//]]>\n</script>\n";
			break;
		}
	}
	else
	{
		return $return;
	}
}

/**
 * 在 foot 加载 JavaScript 和 CSS
 *
 * @param string $type js 表示 JavaScript 文件, css 表示 CSS 文件, js-c 表示 JS 脚本
 */
function add_foot_element($type = 'js', $filename = '')
{
	static $return = '';
	if ($type != 'echo')
	{
		if (empty($filename))
		{
			return;
		}

		switch ($type)
		{
			case 'js':
				$return .= "<script src=\"" . $filename . "\" type=\"text/javascript\"></script>\n";
			break;
			case 'css':
				$return .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $filename . "\" />\n";
			break;
			case 'js-c':
				$return .= "<script type=\"text/javascript\">//<![CDATA[\n" . $filename . "\n//]]>\n</script>\n";
			break;
		}
	}
	else
	{
		return $return;
	}
}

/**
* 同步更新用户表
*/
function update_user_view($user)
{
	global $DB;

	$user_view = $DB->query_first('SELECT UserID
			FROM ' . TABLE_PREFIX . "userinfo
			WHERE UserID = '{$user['id']}'");

	$uinfo = array(
		'UserID'  => $user['id'],
		'UserName'  => convert_encoding($user['name'], 'utf-8', 'gbk'),
		'UserSex'  => intval($user['gender']),
		'UserOicq'  => intval($user['qq']),
		//'UserResume'  => $user['UserResume'],
		);
	if ($user['password'])
	{
		$uinfo['UserPassword'] = $user['password'];
	}
	if ($user['email'])
	{
		$uinfo['UserEmail'] = $user['email'];
	}
	$DB->query("SET NAMES latin1");
	if ($user_view['UserID'])
	{
		$DB->update(TABLE_PREFIX . 'userinfo', $uinfo, 'UserID=' . $user['id']);
	}
	else
	{
		$uinfo['UserID'] = $user['id'];
		$DB->insert(TABLE_PREFIX . 'userinfo', $uinfo);
	}
	$DB->query("SET NAMES utf8");
}

function array_filter_func($var) //数组过滤
{
	return (trim($var) != '');
}

/**
 * 将数组中为空的数据过滤掉
 *
 * @param array $array
 * @return array
 */
function filter_arr_null_ele($array)
{
	return array_filter($array, 'array_filter_func');
}

/**
* 二元分词，且转换为unicode编码
* $str 需要进行分词的字符串
* 返回经过转换后的分词词组
*/
function duality_word($str, $return = 0)
{
	$search = array(",", "/", "\\", ".", ";", ":", "\"", "!", "~", "`", "^", "(", ")", "?", "-", "\t", "\n", "'", "<", ">", "\r", "\r\n", "$", "&", "%", "#", "@", "+", "=", "{", "}", "[", "]", "：", "）", "（", "．", "。", "，", "！", "；", "“", "”", "‘", "’", "［", "］", "、", "—", "　", "《", "》", "－", "…", "【", "】","|"," ");
	//替换所有的分割符为空格
	$str = str_replace($search,' ',$str);
	//用正则匹配半角单个字符或者全角单个字符,存入数组$ar
	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/",$str,$ar);
	$ar = $ar[0];
	$new_ar = array();
	//把连续的半角存成一个数组下标,或者全角的每2个字符存成一个数组的下标
	$i = 0;
	foreach ($ar AS $k => $v)
	{
		$sbit  =  ord($v);
		if($sbit  <  128)
		{
			if (trim($v) || $v == 0)
			{
				$new_ar[$i] .= $v;
			}
			else
			{
				$i++;
			}
		}
		elseif($sbit  >  223  &&  $sbit  <  240)
		{
			$i++;
			if (ord($ar[($k + 1)]) < 128)
			{
				//do nothing
			}
			else
			{
				if (!$return)
				{
					$new_ar[$i] = utf8_unicode($ar[$k]) . utf8_unicode($ar[($k + 1)]);
				}
				elseif ($return == 1)
				{
					$new_ar[$i] = $ar[$k] . $ar[($k + 1)];
				}
			}
		}
	}
	return filter_arr_null_ele($new_ar);
}

/**
* 将utf8字符转换为unicode编码
* $c 需要转换的字符
* 返回转换后的编码
*/
function utf8_unicode($c)
{
	switch(strlen($c))
	{
		case 1:
			$n = ord($c);
		break;
		case 2:
			$n = (ord($c[0]) & 0x3f) << 6;
			$n += ord($c[1]) & 0x3f;
		break;
		case 3:
			$n = (ord($c[0]) & 0x1f) << 12;
			$n += (ord($c[1]) & 0x3f) << 6;
			$n += ord($c[2]) & 0x3f;
		break;
		case 4:
			$n = (ord($c[0]) & 0x0f) << 18;
			$n += (ord($c[1]) & 0x3f) << 12;
			$n += (ord($c[2]) & 0x3f) << 6;
			$n += ord($c[3]) & 0x3f;
		break;
	}
	return dechex($n);
}

function split_todir($userid, $subfolder = '')
{
	$userid_path = number_format($userid);
	$userid_path = explode(',', $userid_path);
	$userid_path[0] = sprintf("%03s",$userid_path[0]);
	$count = count($userid_path);
	$filepath = SAFE_MODE ? '' : implode('/', $userid_path);
	$filepath = $subfolder . '/' . $filepath;
	return array($filepath, $count);
}

function load_editor_js($extrabuttons = '',$type = '')
{
	global $forums, $bboptions;
	if ($extrabuttons)
	{
		add_head_element('js-c', "var mEBut = {$extrabuttons};");
	}
	add_head_element('js', $forums->func->load_lang_js('editor'));
	add_head_element('js', ROOT_PATH . 'editor/mxe.js');
	add_head_element('js', ROOT_PATH . 'editor/getxhtml.js');
	add_head_element('js-c', '
		var postmaxchars = "' . $bboptions['maxpostchars'] . '";
		var postminchars = "' . $bboptions['minpostchars'] . '";
		initmxe("' . ROOT_PATH . 'editor/", ' . $bboptions['mxemode'] . ');');
	if ($type)
	{
		if ($bboptions['quickeditorloadmode'] == 2)
		{
			add_head_element('js-c', '
				var quickmxemode = "' . $bboptions['quickeditorloadmode'] . '";
				function load_qmxe()
				{
					mxeditor("post", qmxemenu);
					$("submitform").disabled=false;
				}
			');
		}
		else
		{
			add_foot_element('js-c', 'mxeditor("post", qmxemenu, false)');
		}
	}
	else
	{
		add_foot_element('js-c', 'mxeditor("post");');
	}
}
?>