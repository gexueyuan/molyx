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
class functions_checkad
{
	function check_ad($type = '', $fid = 0)
	{
		global $forums, $bboptions;
		if ($type == 'thread')
		{
			$this->check_thread_ad($fid);
		}
		else if ($type == 'post')
		{
			$this->check_post_ad($fid);
		}
		else
		{
			$i = 0;
			$forums->func->check_cache('ad');
			if (isset($forums->cache['ad'][$type]['all']) && is_array($forums->cache['ad'][$type]['all']))
			{
				foreach ($forums->cache['ad'][$type]['all'] AS $code)
				{
					$i++;
					$show_type[$i] = $forums->cache['ad']['content'][$code];
				}
			}
			if (THIS_SCRIPT == 'index' && isset($forums->cache['ad'][$type]['index']) && is_array($forums->cache['ad'][$type]['index']))
			{
				foreach ($forums->cache['ad'][$type]['index'] AS $code)
				{
					$i++;
					$show_type[$i] = $forums->cache['ad']['content'][$code];
				}
			}
			else if ($fid AND is_array($forums->cache['ad'][$type][$fid]))
			{
				foreach ($forums->cache['ad'][$type][$fid] AS $code)
				{
					$i++;
					$show_type[$i] = $forums->cache['ad']['content'][$code];
				}
			}
			if ($i > 0)
			{
				$show = mt_rand(1, $i);
				echo "<div id='" . $type . "ad'>" . $show_type[$show] . "</div>";
			}
		}
	}

	function check_thread_ad($fid = 0)
	{
		global $forums, $bboptions;
		$forum_ad = array();
		$forums->func->check_cache('ad');
		if (isset($forums->cache['ad']['thread']['all']))
		{
			foreach ((array) $forums->cache['ad']['thread']['all'] AS $code)
			{
				$forum_ad[] = $forums->cache['ad']['content'][$code];
			}
		}
		if (THIS_SCRIPT == 'index' && isset($forums->cache['ad']['thread']['index']))
		{
			foreach ((array) $forums->cache['ad']['thread']['index'] AS $code)
			{
				$forum_ad[] = $forums->cache['ad']['content'][$code];
			}
		}
		else if ($fid && isset($forums->cache['ad']['thread'][$fid]))
		{
			foreach ((array) $forums->cache['ad']['thread'][$fid] AS $code)
			{
				$forum_ad[] = $forums->cache['ad']['content'][$code];
			}
		}
		$ad_count = count($forum_ad);
		if ($ad_count > 0)
		{
			include $forums->func->load_template("ads_list");
		}
	}

	function check_post_ad($fid = 0)
	{
		global $forums, $bboptions;
		$i = 0;
		if ($bboptions['adinpost'] && $this->post_count >= $bboptions['adinpost'])
		{
			return;
		}
		$forums->func->check_cache('ad');
		if (is_array($forums->cache['ad']['post']['all']))
		{
			foreach ($forums->cache['ad']['post']['all'] AS $code)
			{
				$i++;
				$show_type[$i] = $forums->cache['ad']['content'][$code];
			}
		}
		if ($fid && is_array($forums->cache['ad']['post'][$fid]))
		{
			foreach ($forums->cache['ad']['post'][$fid] AS $code)
			{
				$i++;
				$show_type[$i] = $forums->cache['ad']['content'][$code];
			}
		}
		if ($i > 0)
		{
			$show = mt_rand(1, $i);
			echo "<div class='postad'>" . $show_type[$show] . "</div>";
			$this->post_count++;
		}
	}
}

?>