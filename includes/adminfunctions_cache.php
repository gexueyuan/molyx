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
class adminfunctions_cache
{
	function all_recache()
	{
		$this->forum_recache();
		$this->recycle_recache();
		$this->usergroup_recache();
		$this->style_recache();
		$this->moderator_recache();
		$this->stats_recache();
		$this->ranks_recache();
		$this->birthdays_recache();
		$this->bbcode_recache();
		$this->settings_recache();
		$this->smile_recache();
		$this->icon_recache();
		$this->badword_recache();
		$this->banfilter_recache();
		$this->attachmenttype_recache();
		$this->announcement_recache();
		$this->league_recache();
		$this->realjs_recache();
		$this->st_recache();
		$this->ad_recache();
		$this->cron_recache();
		$this->creditlist_recache();
		$this->creditrule_recache();
		$this->creditevent_recache();
		$this->splittable_recache();
		$this->userextrafield_recache();
		return;
	}

	function cron_recache()
	{
		global $forums;
		$forums->func->update_cache(array('name' => 'cron', 'value' => TIMENOW));
	}

	function announcement_recache()
	{
		global $forums, $DB;
		$forums->cache['announcement'] = array();
		$DB->query("SELECT a.*, u.id AS userid, u.name, u.avatar
				    FROM " . TABLE_PREFIX . "announcement a
				   		LEFT JOIN " . TABLE_PREFIX . "user u on (a.userid=u.id)
				    WHERE a.active != 0 ORDER BY startdate DESC, enddate DESC");
		while ($r = $DB->fetch_array())
		{
			$start_ok = false;
			$end_ok = false;
			if (! $r['startdate'])
			{
				$start_ok = true;
			}
			else if ($r['startdate'] < TIMENOW)
			{
				$start_ok = true;
			}
			if (! $r['enddate'])
			{
				$end_ok = true;
			}
			else if ($r['enddate'] > TIMENOW)
			{
				$end_ok = true;
			}
			if ($start_ok && $end_ok)
			{
				$forums->cache['announcement'][$r['id']] = array(
					'id' => $r['id'],
					'title' => $r['title'],
					'notagtitle' => strip_tags($r['title']),
					'titlecut' => $forums->func->fetch_trimmed_title($r['title'], 20),
					'startdate' => $r['startdate'],
					'enddate' => $r['enddate'],
					'forumid' => $r['forumid'],
					'views' => $r['views'],
					'userid' => $r['userid'],
					'avatar' => $r['avatar'],
					'username' => $r['name']
				);
			}
		}
		$forums->func->update_cache(array('name' => 'announcement'));
	}

	function attachmenttype_recache()
	{
		global $forums, $DB;
		$forums->cache['attachmenttype'] = array();
		$result = $DB->query('SELECT extension, mimetype, maxsize, usepost, useavatar, attachimg
			FROM ' . TABLE_PREFIX . 'attachmenttype
			WHERE usepost = 1 OR useavatar = 1');
		while ($r = $DB->fetch_array($result))
		{
			$forums->cache['attachmenttype'][$r['extension']] = $r;
		}
		$forums->func->update_cache(array('name' => 'attachmenttype'));
	}

	function badword_recache()
	{
		global $forums, $DB;
		$forums->cache['badword'] = array();
		$DB->query("SELECT badbefore,badafter,type FROM " . TABLE_PREFIX . "badword");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['badword'][] = $r;
		}
		$forums->func->update_cache(array('name' => 'badword'));
	}

	function banfilter_recache()
	{
		global $forums, $DB;
		$forums->cache['banfilter'] = array();
		$DB->query("SELECT content FROM " . TABLE_PREFIX . "banfilter WHERE type='ip'");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['banfilter'][] = $r['content'];
		}
		$forums->func->update_cache(array('name' => 'banfilter'));
	}

	function bbcode_recache()
	{
		global $forums, $DB;
		$forums->cache['bbcode'] = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "bbcode");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['bbcode'][] = $r;
		}
		$forums->func->update_cache(array('name' => 'bbcode'));
	}

	function birthdays_recache()
	{
		require_once(ROOT_PATH . 'includes/functions_cron.php');
		$func = new functions_cron();
		require_once(ROOT_PATH . 'includes/cron/birthdays.php');
		$cron = new cron_birthdays();
		$cron->register_class($func);
		$cron->docron();
	}

	function usergroup_recache()
	{
		global $forums, $DB;
		$forums->cache['usergroup'] = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY displayorder");
		while ($i = $DB->fetch_array())
		{
			$forums->cache['usergroup'][$i['usergroupid']] = $i;
			$forums->func->update_cache(array('name' => 'usergroup_' . $i['usergroupid'], 'value' => $i));
		}
		$forums->func->update_cache(array('name' => 'usergroup'));
	}

	function moderator_recache()
	{
		global $forums, $DB;
		$forums->cache['moderator'] = array();
		$DB->query('SELECT * FROM ' . TABLE_PREFIX . 'moderator');
		while ($i = $DB->fetch_array())
		{
			if ($i['isgroup'])
			{
				$forums->cache['moderator_group'][$i['usergroupid']][$i['moderatorid']] = $i;
			}
			else
			{
				$forums->cache['moderator_user'][$i['userid']][$i['moderatorid']] = $i;
			}
			$forums->cache['moderator_' . $i['forumid']][$i['moderatorid']] = $i;
			$forums->cache['moderator'][$i['moderatorid']] = $i;
		}
		if (is_array($forums->cache["moderator_user"]))
		{
			foreach ($forums->cache["moderator_user"] AS $userid => $data)
			{
				$forums->func->update_cache(array('name' => "moderator_user_$userid", 'value' => $data));
			}
		}
		if (is_array($forums->cache['moderator_group']))
		{
			foreach ($forums->cache['moderator_group'] AS $usergroupid => $data)
			{
				$forums->func->update_cache(array('name' => "moderator_group_$usergroupid", 'value' => $data));
			}
		}
		$forumlist = $DB->query("SELECT * FROM " . TABLE_PREFIX . "forum");
		while ($forum = $DB->fetch_array($forumlist))
		{
			$forums->func->update_cache(array('name' => 'moderator_' . $forum['id'], 'value' => $forums->cache['moderator_' . $forum['id']]));
		}
		$forums->func->update_cache(array('name' => 'moderator'));
	}

	function ranks_recache()
	{
		global $forums, $DB;
		$forums->cache['ranks'] = array();
		$DB->query("SELECT id, title, ranklevel, post FROM " . TABLE_PREFIX . "usertitle ORDER BY post DESC");
		while ($i = $DB->fetch_array())
		{
			$forums->cache['ranks'][ $i['id'] ] = array('title' => $i['title'], 'ranklevel' => $i['ranklevel'], 'post' => $i['post']);
		}
		$forums->func->update_cache(array('name' => 'ranks'));
	}

	function settings_recache()
	{
		global $forums, $DB;
		$forums->cache['settings'] = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "setting WHERE addcache=1");
		while ($r = $DB->fetch_array())
		{
			$value = $r['value'] != "" ? $r['value'] : $r['defaultvalue'];
			if ($value == '{blank}')
			{
				$value = '';
			}
			$forums->cache['settings'][ $r['varname'] ] = $value;
		}
		$forums->func->update_cache(array('name' => 'settings'));
	}

	function smile_recache()
	{
		global $forums, $DB;
		$forums->cache['smile'] = array();
		$smile = $DB->query("SELECT id,smiletext,image FROM " . TABLE_PREFIX . "smile ORDER BY displayorder, id");
		while ($r = $DB->fetch_array($smile))
		{
			$forums->cache['smile'][] = $r;
		}
		$forums->func->update_cache(array('name' => 'smile'));
	}

	function icon_recache()
	{
		global $forums, $DB;
		$forums->cache['icon'] = array();
		$icon = $DB->query("SELECT id,icontext,image FROM " . TABLE_PREFIX . "icon ORDER BY displayorder");
		while ($r = $DB->fetch_array($icon))
		{
			$forums->cache['icon'][$r['id']] = $r;
		}
		$forums->func->update_cache(array('name' => 'icon'));
	}

	function stats_recache()
	{
		global $forums, $DB;
		$forums->cache['stats'] = array();
		$cache = array('numbermembers', 'maxonline', 'maxonlinedate', 'newusername', 'newuserid');
		$forums->cache['stats'] = $DB->read_cache($cache);

		$forums->func->update_cache(array('name' => 'stats'));
	}

	function style_recache($styles = array())
	{
		global $forums, $DB;
		$forums->cache['style'] = array();
		if (is_null($forums->admin))
		{
			require_once(ROOT_PATH . 'includes/adminfunctions.php');
			$forums->admin = new adminfunctions();
		}
		$forums->admin->cache_styles();
		foreach ($forums->admin->stylecache as $style)
		{
			if (!$style['userselect'])
			{
				continue;
			}
			$styleid = intval($style['styleid']);
			$forums->cache['style'][$styleid] = array(
				'styleid' => $styleid,
				'title' => $style['title'],
				'title_en' => $style['title_en'],
				'depth' => $style['depth'],
				'parentid' => $style['parentid'],
				'parentlist' => $style['parentlist'],
				'userselect' => $style['userselect'],
				'usedefault' => $style['usedefault'],
				'imagefolder' => $style['imagefolder'],
			);
		}
		$forums->func->update_cache(array('name' => 'style'));
	}

	function adminforum_recache()
	{
		global $forums;
		$forums->cache['adminforum'] = array();
		if (get_class($forums->adminforum) == 'adminfunctions_forum')
		{
			$forums->cache['adminforum'] = $forums->adminforum->cache_forums('-1', 0, true);
			$forums->func->update_cache(array('name' => 'adminforum'));
		}
	}

	function forum_recache()
	{
		global $forums, $DB;
		$forums->cache['forum'] = $forum_layer = array();
		if (!is_object($forums->adminforum))
		{
			require_once(ROOT_PATH . 'includes/adminfunctions_forum.php');
			$forums->adminforum = new adminfunctions_forum();
		}
		$all_forum = $forums->adminforum->cache_forums('-1', 0, true, true);
		foreach ($all_forum as $fid => $r)
		{
			$forums->cache['forum'][$fid] = array(
				'id' => $r['id'],
				'name' => $r['name'],
				'description' => $r['description'],
				'url' => $r['url'],
				'parentid' => $r['parentid'],
				'depth' => $r['depth'],
				'canshow' => $r['canshow'],
				'canread' => $r['canread'],
				'parentlist' => $r['parentlist'],
				'showthreadlist' => $r['showthreadlist'],
				'password' => $r['password'],
			);

			$forum = array(
				'id' => $r['id'],
				'forumicon' => $r['forumicon'],
				'parentid' => $r['parentid'],
				'parentlist' => $r['parentlist'],
			);

			if (empty($r['url']))
			{
				$forum['style'] = $r['style'];
				$forum['allowbbcode'] = $r['allowbbcode'];
				$forum['allowhtml'] = $r['allowhtml'];
				$forum['status'] = $r['status'];
				$forum['sortby'] = $r['sortby'];
				$forum['sortorder'] = $r['sortorder'];
				$forum['prune'] = $r['prune'];
				$forum['moderatepost'] = $r['moderatepost'];
				$forum['allowpoll'] = $r['allowpoll'];
				$forum['allowpollup'] = $r['allowpollup'];
				$forum['countposts'] = $r['countposts'];
				$forum['childlist'] = $r['childlist'];
				$forum['allowposting'] = $r['allowposting'];
				$forum['displayorder'] = $r['displayorder'];
				$forum['forumcolumns'] = $r['forumcolumns'];
				$forum['threadprefix'] = $r['threadprefix'];
				$forum['forcespecial'] = $r['forcespecial'];
				$forum['specialtopic'] = $r['specialtopic'];
				$forum['forumrule'] = $r['forumrule'];
				$forum['canreply'] = $r['canreply'];
				$forum['canstart'] = $r['canstart'];
				$forum['canupload'] = $r['canupload'];
			}

			if ($r['depth'] < 2)
			{
				if ($forum['parentid'] == '-1')
				{
					$forum_layer[0][$forum['id']]['self'] = $forum;
				}
				else
				{
					$forum_layer[0][$forum['parentid']]['childs'][$forum['id']] = $forum;
				}
			}

			if ($forum['parentid'] != '-1')
			{
				$forum_layer[$forum['parentid']]['childs'][$forum['id']] = $forum;
			}
			$forum_layer[$forum['id']]['self'] = $forum;
		}

		foreach ($forum_layer as $k => $v)
		{
			if ($k != 0 && !empty($v['childs']))
			{
				$forums->func->update_cache(array(
					'name' => 'subforum_' . $k,
					'value' => $v['childs']
				));
				unset($v['childs']);
			}

			$forums->func->update_cache(array(
				'name' => 'forum_' . $k,
				'value' => $v
			));
		}
		$forums->func->update_cache(array('name' => 'forum'));
		$forums->func->rmcache('adminforum');
	}

	//重建回收站缓存
	function recycle_recache()
	{
		global $forums, $DB, $bboptions;
		$forums->cache['recycle'] = array();
		if ($bboptions['enablerecyclebin'] && $bboptions['recycleforumid'])
		{
			$row = $DB->query_first('SELECT id
				FROM ' . TABLE_PREFIX . 'forum
				WHERE id = ' . $bboptions['recycleforumid']);
			$forums->cache['recycle'] = $row;
		}

		$forums->func->update_cache(array('name' => 'recycle'));
	}

	function league_recache()
	{
		global $forums, $DB;
		$forums->cache['league'] = array();
		$leagues = $DB->query("SELECT * FROM " . TABLE_PREFIX . "league WHERE type != 3 ORDER BY type, displayorder");
		while ($r = $DB->fetch_array($leagues))
		{
			$forums->cache['league'][] = $r;
		}
		$forums->func->update_cache(array('name' => 'league'));
	}

	function creditlist_recache()
	{
		global $forums, $DB;
		$forums->cache['creditlist'] = array();
		$result = $DB->query('SELECT *
			FROM ' . TABLE_PREFIX . 'credit');
		while ($r = $DB->fetch_array($result))
		{
			$forums->cache['creditlist'][$r['creditid']] = $r;
		}
		$forums->func->update_cache(array('name' => 'creditlist'));
	}

	function creditevent_recache()
	{
		global $forums, $DB;
		$forums->cache['creditevent'] = array();
		$result = $DB->query('SELECT *
			FROM ' . TABLE_PREFIX . 'creditevent');
		while ($r = $DB->fetch_array($result))
		{
			$forums->cache['creditevent'][$r['eventid']] = $r;
		}
		$forums->func->update_cache(array('name' => 'creditevent'));
	}

	function creditrule_recache()
	{
		global $forums, $DB;
		$array = array();
		$result = $DB->query('SELECT *
			FROM ' . TABLE_PREFIX . 'creditrule');
		while ($row = $DB->fetch_array($result))
		{
			if ($row['type']==0)
			{
				$array[$row['creditid']]['default'] = unserialize($row['parameters']);
				$creditlist[] = $row['creditid'];
			}
			if ($row['lists'] && $row['type'])
			{
				$ids = explode(',', $row['lists']);
				foreach ($ids as $id)
				{
					$array[$row['creditid']][$id][$row['type']] = unserialize($row['parameters']);
				}
			}
		}
		//列出所有版面
		$result = $DB->query('SELECT id
			FROM ' . TABLE_PREFIX . 'forum');
		while ($row = $DB->fetch_array($result))
		{
			$rules = array();
			$fid = intval($row['id']);
			foreach ($creditlist as $creditid)
			{
				$rules[$creditid]['default'] = $array[$creditid]['default'];
				$rules[$creditid]['alter'] = $array[$creditid][$fid][2];
			}
			$forums->func->update_cache(array('name' => "creditruleforum_$fid", 'value' => $rules));
		}
		//列出所有用户组
		$result = $DB->query('SELECT usergroupid
			FROM ' . TABLE_PREFIX . 'usergroup');
		while ($row = $DB->fetch_array($result))
		{
			$rules = array();
			$gid = intval($row['usergroupid']);
			foreach ($creditlist as $creditid)
			{
				$rules[$creditid]['default'] = $array[$creditid]['default'];
				$rules[$creditid]['alter'] = $array[$creditid][$gid][1];
			}
			$forums->func->update_cache(array('name' => "creditrulegroup_$gid", 'value' => $rules));
		}
	}

	function realjs_recache()
	{
		global $forums, $DB;
		$forums->cache['realjs'] = array();
		$DB->query("SELECT id, type, jsname, inids, numbers, perline, selecttype, daylimit, orderby, trimtitle, trimdescription, trimpagetext, export, htmlcode FROM " . TABLE_PREFIX . "javascript ORDER BY id");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['realjs'][$r['id']] = $r;
		}
		$forums->func->update_cache(array('name' => 'realjs'));
	}

	function st_recache()
	{
		global $forums, $DB;
		$forums->cache['st'] = array();
		$DB->query("SELECT id, name, forumids FROM " . TABLE_PREFIX . "specialtopic ORDER BY id");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['st'][$r['id']] = $r;
		}
		$forums->func->update_cache(array('name' => 'st'));
	}

	function blog_cache_recache()
	{
		global $forums, $DB;
		require_once(ROOT_PATH . "includes/adminfunctions_blogcache.php");
		$forums->blogcache = new adminfunctions_blogcache();
		$forums->blogcache->all_recache();
	}

	function ad_recache()
	{
		global $forums, $DB;
		$forums->cache['ad'] = array();
		$DB->query("SELECT * FROM " . TABLE_PREFIX . "ad WHERE (endtime = 0 OR endtime >= " . TIMENOW . ") AND starttime <= " . TIMENOW . " ORDER BY type, displayorder");
		while ($r = $DB->fetch_array())
		{
			$forums->cache['ad']['content'][$r['id']] = $r['htmlcode'];
			if ($r['ad_in'] == '-1')
			{
				$forums->cache['ad'][$r['type']]['all'][] = $r['id'];
			}
			else
			{
				$forumids = explode(',', $r['ad_in']);
				foreach ($forumids as $fid)
				{
					if ($fid == '0')
					{
						$forums->cache['ad'][$r['type']]['index'][] = $r['id'];
					}
					else
					{
						$forums->cache['ad'][$r['type']][$fid][] = $r['id'];
					}
				}
			}
		}
		$forums->func->update_cache(array('name' => 'ad'));
	}

	function register_manage()
	{
        global $forums,$DB;
        $bbs_info = <<<info
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
info;
		$not_mustfill_code = "\n\n".'//check value <not must fill field>';
        $mustfill_code ="\n\n".'//check value <must fill>';
		$confirmation_code = "\n\n".'//check confirmation';
		$strlen_code = "\n\n".'//check strlen';
		$user_array_code ="\n\n". '//string format';
		$is_only_code = "\n\n".'//check only';
		$fixed_value_code = "\n\n".'//fixture';
		$error_function_code = "\n\n".'//check error function';
		$trim_data_str = "\n\n";

        $forums->func->check_cache('new_column');

        if(count($forums->cache['new_column']))
		{
			foreach($forums->cache['new_column'] as $key => $value)
			{
				//用户自定义的注册项 则进行生成
				if(!$value['type'])
				{
					if(!$value['mustfill'])
					{
						if($value["column_regular"])
						{
							$value["column_regular"] = unclean_value($value["column_regular"]);
							$check_regular=<<<str
 && !preg_match("/{$value['column_regular']}/",\$_INPUT['{$value["column_name"]}'])
str;
							$not_mustfill_code.=<<<register_php
\n
if(\$_INPUT['{$value["column_name"]}']{$check_regular})
{
\n	\$forums->lang['error{$value["column_name"]}_style_notmustfill'] = "{$value['column_title']}{\$forums->lang['cache_style_notmustfill']}";
\n  callback_error("error{$value['column_name']}style_notmustfill");

}
register_php;
						}

					}
					else
					{
						if($value["column_regular"])
						{
							$value["column_regular"] = unclean_value($value["column_regular"]);
							$check_regular=<<<str
||!preg_match("/{$value['column_regular']}/",\$_INPUT['{$value["column_name"]}'])
str;
						}
						else
						{
							$check_regular = '';
						}

						$mustfill_code.=<<<register_php
\n
if(empty(\$_INPUT['{$value["column_name"]}']){$check_regular})
{
\n	\$forums->lang['error{$value["column_name"]}_style_mustfill'] = "{$value['column_title']}{\$forums->lang['cache_style_mustfill']}";
\n	callback_error("error{$value['column_name']}_style_mustfill");
}
register_php;

					}

					if($value["confirmation"]&&empty($value['column_value']))
					{
						$confirmation_code.=<<<register_php
\n
if(trim(\$_INPUT['{$value["column_name"]}'])!= \$_INPUT['{$value["column_name"]}_confirmation'])
{
\n	\$forums->lang['error{$value["column_name"]}_confirmation'] = "{$value['column_title']}{\$forums->lang['cache_confirmation']}";
\n	callback_error("error{$value['column_name']}_confirmation");

}
register_php;
					}
					if($value["column_length"] != 0)
					{
						$strlen_code.=<<<register_php
\n
if(\$_INPUT['{$value["column_name"]}'] && utf8_strlen(\$_INPUT['{$value["column_name"]}']) > {$value["column_length"]})
{
\n	\$forums->lang['error{$value["column_name"]}_length'] = "{$value['column_title']}{\$forums->lang['cache_length']}";
\n	callback_error("error{$value['column_name']}_length");
}
register_php;

					}
					//如果非固定值状态则进行值的初始
					//如果值为数组则进行串化化
					if(empty($value["column_value"]))
					{
						if($value['column_type'] == 'checkbox')
						{
							$trim_data_str .= <<<str
\n
//checkbox value
if(is_array(\$_INPUT['{$value["column_name"]}']))
{\n	\$_INPUT['{$value["column_name"]}'] = serialize(\$_INPUT['{$value["column_name"]}']);
\n}
else
{\n	\$_INPUT['{$value["column_name"]}'] = '';
\n}\n
str;
						   if($value['confirmation'])
							{
							   $trim_data_str .= <<<str
if(is_array(\$_INPUT['{$value["column_name"]}_confirmation']))
{\n	\$_INPUT['{$value["column_name"]}_confirmation'] = serialize(\$_INPUT['{$value["column_name"]}_confirmation']);
\n}
else
{\n	\$_INPUT['{$value["column_name"]}_confirmation'] = '';
\n}
str;
							}


						}
						else
						{
							$trim_data_str .= "\$_INPUT['".$value["column_name"]."'] = addslashes(trim(\$_INPUT['".$value["column_name"]."']));\n";

						}
						$user_array_code .= <<<register_php
\n\$user_data['{$value["tablename"]}']['{$value["column_name"]}'] = (\$_INPUT['{$value["column_name"]}']);
register_php;
					}

				}
				if($value["is_only"])
				{
					$is_only_code.=<<<register_php
\nif(!empty(\$_INPUT['{$value["column_name"]}']))
{
\n	\$DB->query("SELECT {$value['column_name']} FROM " . TABLE_PREFIX . "{$value['tablename']} WHERE {$value['column_name']} = '" . \$_INPUT['{$value["column_name"]}'] . "'");
\n	if (\$DB->num_rows() != 0)
	{
\n		\$forums->lang['error{$value["column_name"]}_exists'] = "{$value['column_title']}{\$forums->lang['cache_exists']}";
\n		callback_error("error{$value['column_name']}_exists");
	}
}
register_php;
				}

				if($value["column_value"])
				{
					//字符串还原
					$value["column_value"] = unclean_value($value["column_value"]);

					if(preg_match('/^function/i',trim($value["column_value"])))
					{
						$value["column_value"] = preg_replace("/<br \/>/","\n",$value["column_value"]);
                        //获得函数名
						preg_match("/^function[^\w]+([^(]+)/i",$value["column_value"],$function_name);

						$fixed_value_code.=<<<register_php
\n//function <about fixed value>
{$value["column_value"]}
\$user_data['{$value["tablename"]}']['{$value["column_name"]}'] = {$function_name[1]}();
register_php;

					}
					else
					{
						$value["column_value"] = trim($value["column_value"]);
						$fixed_value_code.=<<<register_php
\n//fixture <about fixed value>
\$user_data['{$value["tablename"]}']['{$value["column_name"]}'] ={$value["column_value"]};
register_php;

					}

				}

			}

			//错误函数
			$error_function_code.=<<<function_php
\nfunction callback_error(\$errstr)
{\n	global \$forums, \$_INPUT;
\n	switch(THIS_SCRIPT)
	{
\n		case "register":
\n			require_once(ROOT_PATH . "includes/functions_showcode.php");
\n			\$tempobj = new register();
\n			\$tempobj->showcode = new functions_showcode();
\n			return \$tempobj->start_register(\$errstr);
\n		break;
\n		case "usercp":
\n			\$tempobj = new usercp();
\n			\$tempobj->edit_profile();

\n		default:
\n			\$tempobj = new user();
\n			\$forums->main_msg = \$forums->lang[\$errstr];
\n			if (\$_INPUT['do'] == 'doedit')
			{
\n				\$tempobj->useredit('edit');
			}
			else
			{
\n				\$tempobj->useredit('add');
			}
\n		break;
\n	}
\n	//where are you form?
\n	exit(0);
}
function_php;

			$content =<<<str
<?php\n{$bbs_info};
\nbasename(__FILE__)==basename(\$_SERVER["PHP_SELF"])?exit(0):"";
\$forums->func->load_lang('defined_userinfo');
str;
			$content.= $trim_data_str . $mustfill_code . $strlen_code . $not_mustfill_code . $confirmation_code . $is_only_code . $fixed_value_code."\n".'$user_data = array();'.$user_array_code . $error_function_code."\n\n".'?>';
		}
		else
		{
			$content = '';
		}
		file_write(ROOT_PATH."cache/cache/register_manage.php",$content , "w");
	}

	/**
	*
	* 用户注册项表单生成
	*/
	function userinfo_form_recache()
	{
        global $forums,$DB;
        $forums->func->check_cache('new_column');

		$userinfo_field = array();
        if($forums->cache['new_column'])
		{
			foreach($forums->cache['new_column'] as $key => $value)
			{
                if($value['column_value'])
				{
					continue;
				}
				$value['mustfill'] = intval($value['mustfill']);
				switch ($value['column_type'])
				{
					case 'text':
						$userinfo_field[$value['mustfill']][] = '<div>' . $value['column_title'] . ':&nbsp;<input type="text" name="' . $value['column_name'] . '" value="{$_POST[\'' . $value['column_name'] . '\']}" size="' . $value['cols'] . '" /></div>';
						break;
					case 'select':
						if ($value['column_list_content'])
						{
							$list = unserialize($value['column_list_content']);
							$str = "\n<php>\n\$selectop = array(\n";
							foreach($list AS $k => $v)
							{
								if (strstr($v, '=='))
								{
									$op = explode('==', $value['column_list_content']);
								}
								else
								{
									$op[0] = $op[1] = $v;
								}
								$str .= "'{$op[0]}' => '{$op[1]}',\n";
							}
							$str .= ');';
							$str .= '</php><div>' . $value['column_title'] . ':&nbsp;';
							$str .= '<select name="' . $value['column_name'] . '"><php>foreach ($selectop AS $k => $v)
							{
								if ($k == $cur_userinfo[\'' . $value['column_name'] . '\'])
								{
									</php><option value="{$k}" selected="selected">{$v}</option><php>
								}
								else
								{
									</php><option value="{$k}">{$v}</option><php>
								}
							}
							</php></select><php>
							';
							$userinfo_field[$value['mustfill']][] = $str . ' </php></div>';
						}
						break;
					case 'checkbox':
						if ($value['column_list_content'])
						{
							$list = unserialize($value['column_list_content']);
							$str = "\n<php>\n\$checkboxop = array(";
							foreach($list AS $k => $v)
							{
								if (strstr($v, '=='))
								{
									$op = explode('==', $value['column_list_content']);
								}
								else
								{
									$op[0] = $op[1] = $v;
								}
								$str .= "'{$op[0]}' => '{$op[1]}',\n";
							}
							$str .= ');';
							$str .= '</php><div>' . $value['column_title'] . '&nbsp;:<php>';
							$str .= 'foreach ($checkboxop AS $k => $v)
							{
								if ($k == $cur_userinfo[\'' . $value['column_name'] . '\'])
								{
									</php><input type="checkbox" name="' . $value['column_name'] . '[]" value="{$k}" checked="checked" />{$v}<php>
								}
								else
								{
									</php><input type="checkbox" name="' . $value['column_name'] . '[]" value="{$k}" />{$v}<php>
								}
							}
							';
							$userinfo_field[$value['mustfill']][] = $str . '</php></div>';
						}
						break;
					case 'radio':
						if ($value['column_list_content'])
						{
							$list = unserialize($value['column_list_content']);
							$str = "\n<php>\n$radioop = array(";
							foreach($list AS $k => $v)
							{
								if (strstr($v, '=='))
								{
									$op = explode('==', $value['column_list_content']);
								}
								else
								{
									$op[0] = $op[1] = $v;
								}
								$str .= "'{$op[0]}' => '{$op[1]}',\n";
							}
							$str .= ");\n";
							$str .= '</php><div>' . $value['column_title'] . ':&nbsp;<php>';
							$str .= 'foreach ($radioop AS $k => $v)
							{
								if ($k == $cur_userinfo[\'' . $value['column_name'] . '\'])
								{
									</php><input type="radio" name="' . $value['column_name'] . '" value="{$k}" checked="checked" />{$v}<php>
								}
								else
								{
									</php><input type="radio" name="' . $value['column_name'] . '" value="{$k}" />{$v}<php>
								}
							}
							';
							$userinfo_field[$value['mustfill']][] = $str . "</php></div>\n";
						}
						break;
					case 'textarea':
						$userinfo_field[$value['mustfill']][] = '<div>' . $value['column_title'] . ':&nbsp;<textarea name="' . $value['column_name'] . '" rows="' . $value['rows'] . '" cols="' . $value['cols'] . '" />{$_POST[\'' . $value['column_name'] . "']}</textarea></div>\n";
						break;
					case 'password':
						$userinfo_field[$value['mustfill']][] = '<div>' . $value['column_title'] . ':&nbsp;<input type="password" name="' . $value['column_name'] . '" size="' . $value['cols'] . "\" /></div>\n";
						break;
					default:
						$userinfo_field[$value['mustfill']][] = '<div>' . $value['column_title'] . ':&nbsp;<input type="text" name="' . $value['column_name'] . '" value="{$_POST[\'' . $value['column_name'] . '\']}" size="' . $value['cols'] . "\" /></div>\n";
						break;
				}
			}
			if ($userinfo_field[1])
			{
				$notneedcontent = implode("\n" ,$userinfo_field[1]);
			}
			file_write(ROOT_PATH."templates/global/automk_userinfoneedfill_form.htm",$notneedcontent , "w");

			if ($userinfo_field[0])
			{
				$content = implode("\n" ,$userinfo_field[0]);
			}
			file_write(ROOT_PATH."templates/global/automk_userinfonotneedfill_form.htm",$content , "w");
		}
	}

	function splittable_recache()
	{
		global $forums, $DB;
		$forums->cache['splittable'] = array();
		$result = $DB->query("SELECT * FROM " . TABLE_PREFIX . "splittable");
		while ($r = $DB->fetch_array($result))
		{
			if ($r['isdefaulttable'])
			{
				$forums->cache['splittable']['default'] = $r;
			}
			$forums->cache['splittable']['all'][$r['id']] = $r;
		}
		$forums->func->update_cache(array('name' => 'splittable'));
	}

	function userextrafield_recache()
	{
		global $forums, $DB;
		$forums->cache['userextrafield'] = array();
		$result = $DB->query("SELECT * FROM " . TABLE_PREFIX . "userextrafield");
		while ($r = $DB->fetch_array($result))
		{
			//全部扩展字段
			$r['listcontent'] = unserialize($r['listcontent']);
			$forums->cache['userextrafield']['a'][$r['fieldtag']] = $r;
			if ($r['ismustfill'])
			{
				//必须填写的项目
				$forums->cache['userextrafield']['f'][$r['fieldtag']] = $r['fieldname'];
			}
			if ($r['checkregular'])
			{
				//需检测正则的项目
				$forums->cache['userextrafield']['r'][$r['fieldtag']] = array($r['fieldname'], unclean_value($r['checkregular']));
			}
			if ($r['isonly'])
			{
				//唯一的项目
				$forums->cache['userextrafield']['o'][$r['fieldtag']] = $r['fieldname'];
			}
		}
		$forums->func->update_cache(array('name' => 'userextrafield'));
	}

	function forum_commend_thread_recache()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forums->cache['forum_commend_thread'] = array();
		$_INPUT['f'] = intval($_INPUT['f']);
		$sql = 'SELECT u.avatar, t.tid, t.mod_commend, t.title, t.postusername, t.postuserid, t.dateline
				FROM ' . TABLE_PREFIX . 'thread t
					LEFT JOIN ' . TABLE_PREFIX . 'user u
						ON u.id = t.postuserid
				WHERE t.forumid = ' . $_INPUT['f'] . '
					AND t.mod_commend > 0
					AND t.visible = 1
				ORDER BY t.mod_commend DESC, t.dateline DESC
				LIMIT ' . intval($bboptions['commend_thread_num']);
		$thread = array();
		$q = $DB->query($sql);
		while ($r = $DB->fetch_array($q))
		{
			$thread[$r['tid']] = $r;
		}
		$forums->func->update_cache(array('name' => 'forum_commend_thread_' . $_INPUT['f'], 'value' => $thread));
	}

	function forum_active_user_recache($fid = 0)
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forums->cache['forum_active_user'] = array();
		$_INPUT['f'] = $fid ? $fid : intval($_INPUT['f']);
		$sql = 'SELECT u.avatar, u.id, u.name, count(tid) AS threads
				FROM ' . TABLE_PREFIX . 'thread t
					LEFT JOIN ' . TABLE_PREFIX . 'user u
						ON u.id = t.postuserid
				WHERE t.forumid = ' . $_INPUT['f'] . '
					AND t.visible = 1
					AND t.postuserid > 0
				GROUP BY u.id
				ORDER BY threads DESC, u.lastactivity DESC
				LIMIT ' . intval($bboptions['forum_active_user']);
		$user = array();
		$q = $DB->query($sql);
		while ($r = $DB->fetch_array($q))
		{
			$user[$r['id']] = $r;
		}
		$forums->func->update_cache(array('name' => 'forum_active_user_' . $_INPUT['f'], 'value' => $user));
	}

	function forum_area_recache()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$forums->cache['forum_area'] = array();
		$_INPUT['f'] = $fid ? $fid : intval($_INPUT['f']);
		$sql = 'SELECT *
				FROM ' . TABLE_PREFIX . 'area
				WHERE forumid IN (0,' . $_INPUT['f'] . ')
				ORDER BY orderid ASC';
		$area = array();
		$q = $DB->query($sql);
		while ($r = $DB->fetch_array($q))
		{
			$area[$r['areaid']] = $r;
		}
		$area_content = array();
		if ($area)
		{
			foreach ($area AS $areaid => $areaset)
			{
				if ($areaset['show_record'])
				{
					$sql = 'SELECT *
							FROM ' . TABLE_PREFIX . 'area_content
							WHERE areaid = ' . $areaid . '
							ORDER BY orderid ASC, id DESC
							LIMIT ' . intval($areaset['show_record']);
					$q = $DB->query($sql);
					$area_content[$areaid]['name'] = $areaset['areaname'];
					while ($r = $DB->fetch_array($q))
					{
						if ($r['titlelink'])
						{
							$r['title'] = '<a href="' . $r['titlelink'] . '" target="' . $r['target'] . '">' . $r['title'] . '</a>';
						}
						unset($r['titlelink'], $r['target']);
						$area_content[$r['areaid']][$r['id']] = $r;
					}
				}
			}
		}
		$forums->func->update_cache(array('name' => 'forum_area_' . $_INPUT['f'], 'value' => $area_content));
	}

	function top_digg_thread_recache()
	{
		global $forums, $DB, $_INPUT, $bboptions;
		$q = $DB->query('SELECT value, defaultvalue, varname
									FROM ' . TABLE_PREFIX . "setting
									WHERE varname IN ('diggshowtype', 'diggshowcondition')");
		$digg_setting = array();
		while ($r = $DB->fetch_array($q))
		{
			$digg_setting[$r['varname']] = $r['value'] ? trim($r['value']) : trim($r['defaultvalue']);
		}
		$orderby = 'ORDER BY t.digg_exps DESC';
		if ($digg_setting['diggshowcondition'])
		{
			if (in_array($digg_setting['diggshowtype'], array('lastpost', 'dateline')))
			{
				$digg_setting['diggshowtype'] ;
				$where = ' AND t.' . $digg_setting['diggshowtype'] . ' >= ' . (TIMENOW - intval($digg_setting['diggshowcondition']) * 86400);
			}
			elseif(in_array($digg_setting['diggshowtype'], array('views', 'digg_exps', 'post', 'views', 'digg_users')))
			{
				$where = ' AND t.' . $digg_setting['diggshowtype'] . ' >= ' . intval($digg_setting['diggshowcondition']);
			}
			elseif ($digg_setting['diggshowtype'] == 'digg_time')
			{
				$field = ', sum(exponent) AS cur_digg_exps ';
				$leftjoin = ' LEFT JOIN ' . TABLE_PREFIX . 'digg_log d
								ON d.threadid=t.tid';
				$orderby = 'GROUP BY t.tid ORDER BY cur_digg_exps DESC';
				$where = ' AND d.digg_time >= ' . (TIMENOW - intval($digg_setting['diggshowcondition']) * 86400);
			}

		}
		$forums->cache['top_digg_thread'] = array();
		$sql = "SELECT u.avatar, t.tid, t.digg_users, t.digg_exps, t.post, t.views, t.lastpost, t.title, t.postusername, t.postuserid, t.dateline{$field}
				FROM " . TABLE_PREFIX . 'thread t
					LEFT JOIN ' . TABLE_PREFIX . 'user u
						ON u.id = t.postuserid
						' . $leftjoin . '
				WHERE t.visible = 1
					AND digg_exps > 0
					' . $where . '
				' . $orderby . '
				LIMIT ' . intval($bboptions['top_digg_thread_num']);
		$q = $DB->query($sql);
		while ($r = $DB->fetch_array($q))
		{
			$forums->cache['top_digg_thread'][$r['tid']] = $r;
		}
		$forums->func->update_cache(array('name' => 'top_digg_thread'));
	}
}
?>