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
class functions_forum
{
	var $forum_cache = array();
	var $foruminfo = array();
	var $forumids = array();
	var $moderator_cache = false;
	var $mod_cache = array();
	var $total = array();

	function functions_forum()
	{
		global $forums;
		$forums->func->check_cache('forum');
		$this->foruminfo = $forums->cache['forum'];
	}

	function forums_init($forumid = null)
	{
		global $forums, $bboptions, $_INPUT;
		if (is_null($forumid))
		{
			$forumid = 0;
			if ($_INPUT['f'])
			{
				$forumid = intval($_INPUT['f']);
			}
		}
		$name = 'forum_' . $forumid;

		$forums->func->check_cache($name, 'forum');
		if ($forumid != 0 && $forums->func->check_cache("sub$name", 'forum', true))
		{
			$forums->cache[$name]['childs'] = &$forums->cache["sub$name"];
		}

		$this->forum_cache = array();
		if ($forumid)
		{
			$this->set_foruminfo($forums->cache[$name]);
		}
		else
		{
			foreach((array) $forums->cache[$name] as $k => $v)
			{
				if (!$this->set_foruminfo($v))
				{
					continue;
				}
			}
		}

		$this->forum_stats();
	}

	function set_foruminfo(&$v)
	{
		if (!$this->_set_foruminfo($v['self']))
		{
			return false;
		}

		if (is_array($v['childs']) && $v['childs'])
		{
			foreach ($v['childs'] as $subv)
			{
				if (!$this->_set_foruminfo($subv))
				{
					continue;
				}
			}
		}
		return true;
	}

	function _set_foruminfo(&$v)
	{
		global $forums;
		if ($this->foruminfo[$v['id']]['canshow'] != '*' && $forums->func->fetch_permissions($this->foruminfo[$v['id']]['canshow'], 'canshow') != true)
		{
			return false;
		}

		if ($v['parentid'] == -1)
		{
			$v['parentid'] = 'root';
		}

		$this->forumids[] = $v['id'];
		if(!is_array($this->foruminfo[$v['id']])) $this->foruminfo[$v['id']] = array();
		$this->foruminfo[$v['id']] = array_merge($this->foruminfo[$v['id']], $v);
		$this->forum_cache[$v['parentid']][$v['id']] = &$this->foruminfo[$v['id']];
		return true;
	}

	function forum_stats()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;

		$this->total = array('thread' => 0, 'post' => 0, 'todaypost' => 0);

		$splittable = array();
		$forums->func->check_cache('splittable');
		$splittable = $forums->cache['splittable']['all'];
		$deftable = $forums->cache['splittable']['default'];

		$sqlQueryStr = "SELECT f.id, f.lastthreadid, f.this_thread, f.thread, f.post, f.todaypost, f.parentid, f.unmodthreads, f.unmodposts,
				t.lastposterid, t.lastposter, t.lastpost, t.title AS lastthread
			FROM " . TABLE_PREFIX . "forum f
			LEFT JOIN " . TABLE_PREFIX . "thread t
			ON f.lastthreadid = t.tid";
		if(!empty($this->forumids)) $sqlQueryStr .= " WHERE " . $DB->sql_in('f.id', $this->forumids);
		$result = $DB->query($sqlQueryStr);
		
		while ($row = $DB->fetch_array($result))
		{
			if ($row['parentid'] == '-1')
			{
				$this->total['thread'] += $row['thread'];
				$this->total['post'] += $row['post'];
				$this->total['todaypost'] += $row['todaypost'];
			}
			if (!$bbuserinfo['supermod'] && (!isset($bbuserinfo['_moderator'][$row['id']]['canmoderateposts']) || !$bbuserinfo['_moderator'][$row['id']]['canmoderateposts']))
			{
				unset($row['unmodthreads'], $row['unmodposts']);
			}

			$row['sort_post'] = intval($row['post']);
			$row['sort_thread'] = intval($row['thread']);
			$row['sort_todaypost'] = intval($row['todaypost']);
			$row['sort_lastpost'] = intval($row['lastpost']);
			$row['lastposttime'] = $forums->func->get_date($row['lastpost'], 2);
			$row['full_lastthread'] = strip_tags($row['lastthread']);
			if ($row['lastthreadid'])
			{
				$row['lastthread'] = str_replace(array('&#33;', '&quot;'), array('!', '"'), $row['lastthread']);
				$row['lastthread'] = $forums->func->fetch_trimmed_title($row['lastthread'], 14);
				if ($this->foruminfo[$row['forumid']]['password'] || ($forums->func->fetch_permissions($this->foruminfo[$row['forumid']]['canread'], 'canread') != true && !$this->foruminfo[$row['forumid']]['showthreadlist']))
				{
					$row['lastthread'] = $forums->lang['_hiddenthread'];
				}
				else
				{
					$row['lastunread'] = 1;
					$row['lastthread'] = "<a href='redirect.php{$forums->sessionurl}t={$row['lastthreadid']}&amp;goto=newpost' title='{$row['full_lastthread']}'>{$row['lastthread']}</a>";
				}
				if ($row['lastposter'])
				{
					$row['lastposter'] = $row['lastposterid'] ? "<a href='profile.php{$forums->sessionurl}u={$row['lastposterid']}'>{$row['lastposter']}</a>" : $row['lastposter'];
				}
				else
				{
					$row['lastposter'] = '----';
				}
			}
			else
			{
				$row['lastthread'] = '';
			}
			unset($row['forumid']);
			$this->foruminfo[$row['id']] = array_merge($this->foruminfo[$row['id']], $row);
		}

		$DB->free_result($result);
	}

	function single_forum($forumid = '')
	{
		global $forums;
		if ($forumid)
		{
			$forums->func->check_cache('forum_' . $forumid, 'forum');
			$this->foruminfo[$forumid] = array_merge($this->foruminfo[$forumid], $forums->cache['forum_' . $forumid]['self']);
			return $this->foruminfo[$forumid];
		}
		else
		{
			return array();
		}
	}

	function forums_moderator_cache()
	{
		global $forums;
		$forums->func->check_cache('moderator');
		foreach((array) $forums->cache['moderator'] as $i => $r)
		{
			$this->mod_cache[$r['forumid']][$r['moderatorid']] = array(
				'name' => $r['username'],
				'userid' => $r['userid'],
				'id' => $r['moderatorid'],
				'isgroup' => $r['isgroup'],
				'usergroupname' => $r['usergroupname'],
				'gid' => $r['usergroupid'],
			);
		}
		$this->moderator_cache = true;
	}

	function forums_moderator($forumid = '')
	{
		global $DB, $forums, $bboptions;
		$this->forums_moderator_cache();
		if ($forumid == '')
		{
			return '';
		}
		$mod_string = '';
		if (isset($this->mod_cache[$forumid]) && is_array($this->mod_cache[$forumid]))
		{
			foreach ((array) $this->mod_cache[ $forumid ] AS $moderator)
			{
				if ($moderator['isgroup'] == 1)
				{
				//	$mod_string .= "<li><a href='memberlist.php{$forums->sessionurl}max_results=30&amp;filter={$moderator['gid']}&amp;order=asc&amp;sortby=name&amp;pp=0&amp;b=1'>" . $forums->func->fetch_trimmed_title($moderator['usergroupname'], 10) . "</a></li>";
				}
				else
				{
					$mod_string .= "<li><a href='profile.php{$forums->sessionurl}u={$moderator['userid']}'>" . $forums->func->fetch_trimmed_title($moderator['name'], 10) . "</a></li>";
				}
			}
			unset($this->mod_cache);
		}
		else
		{
			$mod_string .= '';
		}
		if (!$mod_string)
		{
			$mod_string = '<li>' . $forums->lang['_invitemod'] . '</li>';
		}
		$mod_string = '<ul class="usr_mod">' . $mod_string . '</ul>';
		return $mod_string;
	}

	function check_permissions($fid, $prompt_login = 0, $in = 'forum', $uid = 0)
	{
		global $forums, $bbuserinfo;
		if ($in == 'thread' && $bbuserinfo['id'] == $uid)
		{
			return true;
		}
		$deny_access = true;
		if (isset($this->foruminfo[$fid]) && $forums->func->fetch_permissions($this->foruminfo[$fid]['canshow'], 'canshow') == true)
		{
			if ($forums->func->fetch_permissions($this->foruminfo[$fid]['canread'], 'canread') == true)
			{
				$deny_access = false;
			}
			else
			{
				if ($this->foruminfo[$fid]['showthreadlist'])
				{
					if ($in == 'forum')
					{
						$deny_access = false;
					}
					else
					{
						$this->forums_custom_error($fid);
						$deny_access = true;
					}
				}
				else
				{
					$this->forums_custom_error($fid);
					$deny_access = true;
				}
			}
		}
		else
		{
			$this->forums_custom_error($fid);
			$deny_access = true;
		}
		if (!$deny_access)
		{
			if ($this->foruminfo[$fid]['password'])
			{
				if ($this->check_password($fid) == true)
				{
					$deny_access = false;
				}
				else
				{
					$deny_access = true;
					if ($prompt_login == 1)
					{
						$this->forums_show_login($fid);
					}
				}
			}
		}
		else
		{
			$forums->func->standard_error('cannotviewboard');
		}
	}

	function check_password($fid)
	{
		global $forums;
		$forum_password = $forums->func->get_cookie('forum_' . $fid);
		if (trim($forum_password) == md5($this->foruminfo[$fid]['password']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function forums_custom_error($forumid)
	{
		global $forums, $DB;
		$error = $DB->query_first('SELECT customerror
			FROM ' . TABLE_PREFIX . "forum
			WHERE id = $forumid");
		if ($error['customerror'])
		{
			$forums->lang['customerror'] = $error['customerror'];
			$DB->shutdown_update(TABLE_PREFIX . 'session', array('badlocation' => 1), "sessionhash = '{$forums->sessionid}'");
			$forums->func->standard_error('customerror');
		}
	}

	function forums_show_login($forumid)
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		if (empty($bbuserinfo['id']))
		{
			$forums->func->standard_error('notlogin');
		}
		$pagetitle = $this->foruminfo[$forumid]['name'] . ' - ' . $bboptions['bbtitle'];
		$nav = array("<a href='forumdisplay.php{$forums->sessionurl}f={$forumid}'>" . $this->foruminfo[$forumid]['name'] . "</a>");
		include $forums->func->load_template('forumdisplay_verify');
		exit;
	}

	function forums_get_children($forumid, $ids = array())
	{
		global $forums;
		if (!$this->foruminfo[$forumid])
		{
			$forums->func->check_cache('forums_' . $forumid, 'forum');
			$this->foruminfo[$forumid] = &$forums->cache['forums_' . $forumid]['self'];
		}

		$ids = explode(',', $this->foruminfo[$forumid]['childlist']);
		unset($ids[array_search($forumid, $ids)]);
		return $ids;
	}

	function forums_nav($forumid, $reffer = '')
	{
		global $forums;
		$ids = explode(',', $this->foruminfo[$forumid]['parentlist']);
		if (is_array($ids) && $ids)
		{
			foreach($ids as $id)
			{
				if ($id == -1) continue;
				$data = $this->foruminfo[$id];
				if ($reffer && $data['id'] == $forumid)
				{
					$reffer = rawurldecode($reffer);
					$nav_array[] = "<a href='forumdisplay.php{$forums->sessionurl}f={$data['id']}{$reffer}'>{$data['name']}</a>";
				}
				else
				{
					$nav_array[] = "<a href='forumdisplay.php{$forums->sessionurl}f={$data['id']}'>{$data['name']}</a>";
				}
			}
		}
		return array_reverse($nav_array);
	}

	function forum_jump($html = 0, $override = 0)
	{
		global $forums, $_INPUT;
		foreach((array) $this->foruminfo as $id => $forum)
		{
			if (($forum['canshow'] != '*' && $forums->func->fetch_permissions($forum['canshow'], 'canshow') != true) || $forum['url'])
			{
				continue;
			}

			if ($html == 1 || $override == 1)
			{
				$selected = ($_INPUT['f'] && $_INPUT['f'] == $forum['id']) ? " selected='selected'" : '';
			}
			$forum_jump .= '<option value="' . $forum['id'] . '"' . $selected . '>' . depth_mark($forum['depth'], '--') . ' ' . $forum['name'] . '</option>' . "\n";
		}
		return $forum_jump;
	}

	function forums_format_lastinfo($forum)
	{
		global $forums, $bbuserinfo, $bboptions;
		$show_subforums = true;
		$forum['img_new_post'] = $this->forums_new_post($forum);
		$forum['post'] = fetch_number_format($forum['post']);
		$forum['thread'] = fetch_number_format($forum['thread']);
		$forum['todaypost'] = fetch_number_format($forum['todaypost']);
		$forum['moderator'] = $this->forums_moderator($forum['id']);
		$forum['show_subforums'] = '';
		
		if ($bboptions['showsubforums'] && !$forum['password'])
		{
			$childs = explode(',', $forum['childlist']);
			unset($childs[0]);
			if ($childs)
			{
				$forum['show_subforums'] = "<div><strong>{$forums->lang['_subforums']}</strong>: ";
				//子版块显示不全 fixed 1:31 2010/4/28
				for ($i = 1, $n = count($childs); $i <= $n; $i++)
				{
					$forum['show_subforums'] .= "<a href='forumdisplay.php{$forums->sessionurl}f={$childs[$i]}'>{$this->foruminfo[$childs[$i]]['name']}</a> ";
				}
				$forum['show_subforums'] .= '</div>';
			}
		}
		if (($bbuserinfo['supermod'] || (isset($bbuserinfo['_moderator'][$forum['id']]['canmoderateposts']) && $bbuserinfo['_moderator'][$forum['id']]['canmoderateposts'] == 1)) && ($forum['unmodposts'] || $forum['unmodthreads']))
		{
			$forum['moderateinfo'] = "&nbsp;(<span class='description'>{$forums->lang['_unmoderate']}<a href='forumdisplay.php{$forums->sessionurl}f={$forum['id']}&amp;filter=visible&amp;daysprune=100'>{$forums->lang['_thread']}</a>: {$forum['unmodthreads']}, <a href='findposts.php{$forums->sessionurl}do=findmod&amp;forumlist={$forum['id']}&amp;searchsubs=0'>{$forums->lang['_post']}</a>: {$forum['unmodposts']}</span>)";
		}
		return $forum;
	}

	function forums_new_post($forum)
	{
		global $forums, $_INPUT;
		$lastvisit = isset($_INPUT['lastvisit']) ? $_INPUT['lastvisit'] : 0;
		$fid = (!isset($forum['fid']) || !$forum['fid']) ? $forum['id'] : $forum['fid'];
		$readtime = isset($forums->forum_read[$fid]) ? $forums->forum_read[$fid] : 0;
		$lastvisit = $readtime > $lastvisit ? $readtime : $lastvisit;
		if (! $forum['status'])
		{
			return 'readonly';
		}
		if ($forum['password'])
		{
			return $forum['lastpost'] > $lastvisit ? 'brnew' : 'brnonew';
		}
		return $forum['lastpost'] > $lastvisit ? 'bfnew' : 'bfnonew';
	}

	function load_forum_style($fs_id = '')
	{
		global $forums, $bbuserinfo, $bboptions;
		if (!$fs_id)
		{
			return;
		}
		if ($forums->cache['style'][$fs_id]['userselect'])
		{
			$bbuserinfo['style'] = $fs_id;
			$bbuserinfo['imgurl'] = $forums->cache['style'][$fs_id]['imagefolder'] . '/' . $bboptions['language'];
		}
	}

	function forumread($set = '')
	{
		global $forums;
		if ($set == '')
		{
			if ($fread = $forums->func->get_cookie('forumread'))
			{
				$farray = @unserialize($fread);
				if (is_array($farray) && $farray)
				{
					$forums->forum_read = array_merge($forums->forum_read, $farray);
				}
			}
		}
		else
		{
			$fread = serialize($forums->forum_read);
			$forums->func->set_cookie('forumread', $fread);
		}
	}

	function fetch_forum_guide()
	{
		global $forums, $_INPUT;
		$forum_data = '<ul>';
		$tmp_depth = -1;
		$ul = 0;
		$this_parentid = $this->foruminfo[$_INPUT['f']]['parentid'];
		foreach((array) $this->foruminfo as $id => $forum)
		{
			if (($forum['canshow'] != '*' && $forums->func->fetch_permissions($forum['canshow'], 'canshow') != true) || $forum['url'])
			{
				continue;
			}
			$class = ($_INPUT['f'] && $_INPUT['f'] == $forum['id']) ? ' class="cur"' : '';
			if ($forum['depth'] == 0)
			{
				if ($tmp_depth > $forum['depth'])
				{
					$forum_data .= '</ul></li>';
				}
				$forum_data .= '<li class="item_change"><a href="###" onclick="toggle(\'child_node_' . $forum['id'] . '\');">';
			}
			elseif ($forum['depth']== 1)
			{
				if ($tmp_depth < $forum['depth'])
				{
					if($forum['parentid'] == $this_parentid)
					{
						$style = '';
					}
					else
					{
						$style = ' style="display:none;"';
					}
					$forum_data .= '<li id="child_node_' . $forum['parentid'] . '" class="inn"' . $style . '><ul>';
				}

				$forum_data .= '<li class="item_list">';
				$forum_data .= '<a' . $class . ' href="forumdisplay.php' . $forums->sessionurl . 'f=' . $forum['id'] . '">';
			}
			$forum_data .= $forum['name'] . '</a></li>';
			$tmp_depth = $forum['depth'];
		}
		$forum_data .= '</ul></li></ul>';
		return $forum_data;
	}
}
?>