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
class cron_promotion
{
	var $class;
	var $cron = '';

	function docron()
	{
		global $DB, $forums;
		$forums->func->load_lang('cron');
		$result = $DB->query('SELECT u.joindate, u.id, u.membergroupids, u.posts, u.usergroupid, u.name, up.joinusergroupid, up.posts AS jumpposts, up.reputation AS jumpreputation, up.date AS jumpdate, up.type, up.strategy, up.date_sign, up.posts_sign, up.reputation_sign 
			FROM ' . TABLE_PREFIX . 'user u
				LEFT JOIN ' . TABLE_PREFIX . 'userpromotion up
					ON (u.usergroupid = up.usergroupid)
			WHERE u.lastactivity >= ' . (TIMENOW - 86400));
		$primaryupdates = $secondaryupdates = $titleupdates = $primarynames = $secondarynames = $titles = array();
		if ($DB->num_rows($result))
		{
			while ($row = $DB->fetch_array($result))
			{
				if ((strpos(",{$row['membergroupids']},", ",{$row['joinusergroupid']},") === false && $row['type'] == 2) || ($row['usergroupid'] != $row['joinusergroupid'] && $row['type'] == 1))
				{
					$daysregged = intval((TIMENOW - $row['joindate']) / 86400);
					$joinusergroupid = $row['joinusergroupid'];
					$dojoin = false;
					$posts = $row['posts'] . $row['posts_sign'] . $row['jumpposts'];
					$reputation = $row['reputation'] . $row['reputation_sign'] . $row['jumpreputation'];
					$joindate = $daysregged . $row['date_sign'] . $row['jumpdate'];
					eval('$posts = ' . $posts . ';$reputation = ' . $reputation . ';$joindate = ' . $joindate . ';');
					switch ($row['strategy'])
					{
						case '17':
							$dojoin = $posts ? true : false;
						break;

						case '18':
							$dojoin = $joindate ? true : false;
						break;

						case '19':
							$dojoin = $reputation ? true : false;
						break;

						case '1':
							if ($posts && $joindate && $reputation)
							{
								$dojoin = true;
							}
						break;

						case '2':
							if ($posts || $joindate || $reputation)
							{
								$dojoin = true;
							}
						break;

						case '3':
							if (($posts && $joindate) || $reputation)
							{
								$dojoin = true;
							}
						break;

						case '4':
							if (($posts || $joindate) && $reputation)
							{
								$dojoin = true;
							}
						break;

						case '5':
							if ($posts && ($joindate || $reputation))
							{
								$dojoin = true;
							}
						break;

						case '6':
							if ($posts || ($joindate && $reputation))
							{
								$dojoin = true;
							}
						break;

						case '7':
							if (($posts || $reputation) && $joindate)
							{
								$dojoin = true;
							}
						break;

						case '8':
							if (($posts && $reputation) || $joindate)
							{
								$dojoin = true;
							}
						break;
					}
					if ($dojoin)
					{
						if ($row['type'] == 1)
						{
							$primaryupdates[$joinusergroupid] .= ",{$row['id']}";
							$primarynames[$joinusergroupid] .= $primarynames[$joinusergroupid] ? ", {$row['name']}" : $row['name'];
						}
						else
						{
							$secondaryupdates[$joinusergroupid] .= ",{$row['id']}";
							$secondarynames[$joinusergroupid] .= $secondarynames[$joinusergroupid] ? ", {$row['name']}" : $row['name'];
						}
					}
				}
			}
		}
		foreach($primaryupdates as $joinusergroupid => $ids)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET usergroupid = $joinusergroupid WHERE id IN (0$ids)");
		}
		foreach($secondaryupdates as $joinusergroupid => $ids)
		{
			$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "user SET membergroupids = IF(membergroupids= '', '$joinusergroupid', CONCAT(membergroupids, ',$joinusergroupid')) WHERE id IN (0$ids)");
		}
		$this->class->cronlog($this->cron, $forums->lang['updatepromotion']);
	}

	function register_class(&$class)
	{
		$this->class = &$class;
	}

	function pass_cron($this_cron)
	{
		$this->cron = $this_cron;
	}
}

?>