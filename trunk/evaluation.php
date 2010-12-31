<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('THIS_SCRIPT', 'evaluation');
require_once('./global.php');
class evaluation
{
	var $postid = 0;
	var $threadid = 0;
	var $forumid = 0;
	var $posttable = 'post';
	var $pp = 0;
	var $thread = array();
	var $post = array();
	var $forum = array();

	function show()
	{
		global $forums, $DB, $bbuserinfo;
		$forums->func->load_lang('admin');
		$forums->func->load_lang('evaluation');
		if (!$bbuserinfo['id'])
		{
			$forums->func->standard_error("noperms");
		}
		require_once(ROOT_PATH . "includes/functions_credit.php");
		$this->credit = new functions_credit();

		$this->postid = input::get('p', 0);
		$this->pp = input::get('pp', 0);
		//分表
		cache::update('splittable');
		$splittable = $forums->cache['splittable']['all'];
		$deftable = $forums->cache['splittable']['default'];
		foreach ($splittable as $id => $v)
		{
			if ($this->postid >= $v['minpid'] && $this->postid <= $v['maxpid'])
			{
				$tablename = $v['name'];
			}
		}
		if (!$tablename)
		{
			$this->posttable = $deftable['name'] ? $deftable['name'] : 'post';
		}
		else
		{
			$this->posttable = $tablename;
		}
		$this->post = $DB->queryFirst("SELECT p.*, u.usergroupid FROM " . TABLE_PREFIX . "$this->posttable p
			LEFT JOIN  " . TABLE_PREFIX . "user u ON u.id = p.userid
			WHERE p.pid= $this->postid");
		$this->threadid = input::get('t', 0);
		$this->thread = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "thread WHERE tid= $this->threadid");
		$this->forum = $forums->forum->single_forum($this->thread['forumid']);
		$this->forumid = $this->forum['id'];
		if ($bbuserinfo['id'] == $this->post['userid'])
		{
			$forums->func->standard_error('cannotevalself');
		}
		if ((!$this->threadid) OR (!$this->postid) OR (!$this->forumid))
		{
			$forums->func->standard_error('cannotfindeval');
		}
		cache::get('usergroup');
		$usergroups = $forums->cache['usergroup'];
		//判断用户权重
		if ($usergroups[$bbuserinfo['usergroupid']]['grouppower'] < $usergroups[$this->post['usergroupid']]['grouppower'])
		{
			if (!$usergroups[$bbuserinfo['usergroupid']]['canevaluation'])
			{
				$forums->func->standard_error('cannotevalpower');
			}
		}
		switch (input::get('do', ''))
		{
			case 'doeval':
				$this->doeval();
				break;
			default:
				$this->eval_form();
				break;
		}
	}

	function eval_form()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;

		$creditlists = '';
		$title = $this->thread['title'];
		$pagetitle = $this->thread['title'] . " - " . $forums->lang['evaluationpost'] . " - " . $bboptions['bbtitle'];
		$nav = array_merge($forums->forum->forums_nav($this->forum['id']), array($forums->lang['evaluationpost']));
		$author = $this->post['username'];
		$authorid = $this->post['userid'];

		cache::get('creditlist');
		foreach ($forums->cache['creditlist'] as $creditid => $v)
		{
			if (!$v['used']) continue;
			//积分下拉列表
			$creditlists .= "<option value='{$v['tag']}'>{$v['name']}</option>\n";

			//取得剩余的评价积分数
			$range = $this->getrange($creditid);
			$evalrange = implode('~', $range);
			$leftvalue = $bbuserinfo['eval'.$v['tag']];
			$evalcreditdesc = sprintf($forums->lang['evalcreditdesc'], $v['unit'], $evalrange, $v['name'], $leftvalue);
			$lists .= "lists['{$v['tag']}'] = '" . $evalcreditdesc . "';\n";
		}
		if ($creditlists == '')
		{
			$forums->func->standard_error('cannotevalcredit');
		}

		include $forums->func->load_template('evaluation_post');
	}

	function doeval()
	{
		global $forums, $DB, $bbuserinfo, $bboptions;

		$actcredit = input::get('actcredit', '');
		$amount = input::get('amount', 0);
		$evalmessage = input::get('evalmessage', '');
		$allrep = unserialize($this->thread['allrep']);
		$thiscredit = $DB->queryFirst("SELECT * FROM " . TABLE_PREFIX . "credit WHERE tag='$actcredit'");
		//判断用户用该积分进行的评价活动是否超出评价默认值
		if ($bbuserinfo['eval'.$actcredit]<=0)
		{
			if (intval($thiscredit['initevalvalue'])<=0)
			{
				$forums->func->standard_error('notusedcrediteval', false, $thiscredit['name']);
			}
			else
			{
				$forums->func->standard_error('evaloverflow', false, $thiscredit['initevaltime']);
			}
		}
		else
		{
			$left = $bbuserinfo['eval'.$actcredit]-abs($amount);
			if ($left < 0)
			{
				$forums->func->standard_error('evalrangemax', false, $bbuserinfo['eval'.$actcredit]);
			}
		}
		//取得单主题评价中得到的最高收入
		$scorearrs = $this->credit->getactioncredit('evalthreadscore', $this->post['usergroupid'], $this->forumid);
		$threadscore = $scorearrs[$thiscredit['creditid']]['action'];

		$log = $DB->queryFirst("SELECT *
			FROM " . TABLE_PREFIX . "evaluationlog
			WHERE postid = $this->postid AND actionuserid = {$bbuserinfo['id']}");
		if ($log['evaluationid'] && !$bbuserinfo['canevalsameuser'])
		{
			$forums->func->standard_error('cannotrepeateval');
		}
		$range = $this->getrange($thiscredit['creditid']);
		if ($amount == 0 || $amount < $range['min'] || $amount > $range['max'])
		{
			$forums->func->standard_error('evalamounterror');
		}
		$allrep[$actcredit] = $allrep[$actcredit] + abs($amount);
		if ($allrep[$actcredit] > $threadscore)
		{
			$forums->func->standard_error('evalupsinglethread');
		}
		//添加评价日志
		$logarray = array('forumid' => $this->forumid,
						  'threadid' => $this->threadid,
						  'postid' => $this->postid,
						  'postuserid' => $this->forumid,
						  'actionusername' => $bbuserinfo['name'],
						  'actionuserid' => $bbuserinfo['id'],
						  'affect' => $amount,
						  'creditid' => $thiscredit['creditid'],
						  'creditname' => $thiscredit['name'],
						  'reason' => $evalmessage,
						  'dateline' => TIMENOW,
		);
		$DB->insert(TABLE_PREFIX . "evaluationlog", $logarray);
		//更新被评价的用户积分
		$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $actcredit = $actcredit + ( $amount ) WHERE id = {$this->post['userid']}");
		//更新评价的用户积分
		$evalcredit = 'eval'.$actcredit;
		$DB->queryUnbuffered("UPDATE " . TABLE_PREFIX . "userexpand SET $evalcredit = $evalcredit - " . abs($amount) . " WHERE id = {$bbuserinfo['id']}");
		$DB->udpate(TABLE_PREFIX . "thread", array('allrep' => serialize($allrep)), "tid = $this->threadid", SHUTDOWN_QUERY);

		//发送短消息
		if (input::get('sendpm', '') =='yes')
		{
			input::set('title', sprintf($forums->lang['evalpmtitle'], $bbuserinfo['name']));
			if ($this->thread['firstpostid'] != $this->postid)
			{
				$evaluationinfo = sprintf($forums->lang['evalpmcontentposter'], $bbuserinfo['name'], $this->thread['title'], $this->creditinfos[$actcredit]['name'], $amount, $evalmessage);
			}
			else
			{
				$evaluationinfo = sprintf($forums->lang['evalpmcontentauthor'], $bbuserinfo['name'], $this->thread['title'], $this->creditinfos[$actcredit]['name'], $amount, $evalmessage);
			}
			input::set('post', $evaluationinfo);
			input::set('username', $this->post['username']);
			require_once(ROOT_PATH . 'includes/functions_private.php');
			$pm = new functions_private();
			input::set('noredirect', 1);
			$bboptions['usewysiwyg'] = 1;
			$bboptions['pmallowhtml'] = 1;
			$pm->sendpm();
		}
		$this->update_neweval();
		$forums->func->standard_redirect("showthread.php{$forums->sessionurl}f=" . $this->forumid . "&amp;t=" . $this->threadid . "&amp;pp=" . $this->pp . "");
	}

	//取得评价的积分范围
	function getrange($id=0)
	{
		global $forums, $DB, $bbuserinfo, $bboptions;
		$range = array();
		if (!$id)
		{
			$forums->admin->print_cp_error($forums->lang['noids']);
		}
		$minarrs = $this->credit->getactioncredit('evaluationmin', $bbuserinfo['usergroupid'], $this->forumid);
		$range['min'] = $minarrs[$id]['action'];
		$maxarrs = $this->credit->getactioncredit('evaluationmax', $bbuserinfo['usergroupid'], $this->forumid);
		$range['max'] = $maxarrs[$id]['action'];

		return $range;
	}

	/**
	 * 获取最新评分记录冗余记录
	 *
	 */
	function update_neweval()
	{
		global $DB, $forums;
		$evallog = array();
		$rs = $DB->query('SELECT actionusername, affect, dateline, reason, creditid, creditname
			FROM ' . TABLE_PREFIX . "evaluationlog
			WHERE postid={$this->postid}
			ORDER BY dateline DESC
			LIMIT 0,5");
		while ($r = $DB->fetch($rs))
		{
			$split = array();
			if (substr($r['affect'], 0, 1) != '-')
			{
				$r['affect'] = '+' . $r['affect'];
			}
			$evallog[] = array($r['actionusername'], $r['creditname'], $r['affect'], $r['reason'], $r['dateline']);
		}

		$rs = $DB->query('SELECT SUM(affect) AS credit, creditid, creditname
			FROM ' . TABLE_PREFIX . "evaluationlog
			WHERE postid={$this->postid}
			GROUP BY creditid");
		$postcredit = array();
		while ($r = $DB->fetch($rs))
		{
			$postcredit[] = $r;
		}
		$evallog['ac'] = $postcredit;
		$DB->update(TABLE_PREFIX . $this->posttable, array('reppost' => serialize($evallog)), 'pid=' . $this->postid, SHUTDOWN_QUERY);
	}
}

$output = new evaluation();
$output->show();