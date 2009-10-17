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
# $Id: adminfunctions_importers.php 64 2007-09-07 09:19:11Z hogesoft-02 $
# **************************************************************************#
class adminfunctions_importers
{
	function cleandb($doclear = 0, $redirect = 0)
	{
		global $DB, $forums, $bboptions;
		$DB->return_die = 1;
		if ($doclear == 1)
		{
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "user WHERE importuserid<>0 AND isnew=1");

			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "forum WHERE importforumid<>0 OR importcategoryid<>0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "subscribeforumid WHERE importsubscribeforumid<>0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "moderator WHERE importmoderatorid<>0");

			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "thread WHERE threadid > 1");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "post WHERE postid > 1");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "poll WHERE importpollid <> ''");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "subscribethread WHERE importsubscribethreadid<>0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "attachment WHERE importattachmentid <> 0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pm WHERE importpmid<>0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "pmtext WHERE importpmtextid<>0");
			$DB->query_unbuffered("DELETE FROM " . TABLE_PREFIX . "usergroup WHERE importusergroupid<>0");
		}
		$DB->query("DROP TABLE " . TABLE_PREFIX . "importtable");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "user DROP importuserid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "user DROP isnew");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "usergroup DROP importusergroupid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum DROP importcategoryid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum DROP importforumid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum DROP isprivate");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "thread DROP importthreadid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "subscribethread DROP importsubscribethreadid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "subscribeforum DROP importsubscribeforumid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "post DROP importpostid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "poll DROP importpollid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "moderator DROP importmoderatorid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "pm DROP importpmid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "pmtext DROP importpmtextid");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "attachment DROP importattachmentid");
		$DB->return_die = 0;
		if ($redirect)
		{
			$forums->cache['cron'] = TIMENOW;
			$forums->cache['settings']['bbactive'] = '1';
			$forums->func->update_cache(array('name' => 'cron', 'array' => 0));
			$forums->func->update_cache(array('name' => 'settings', 'array' => 1));
			$forums->func->recache('all');
			$forums->admin->redirect(ROOT_PATH . "admin/importers.php", $forums->lang['tablecleaned'], $forums->lang['redirectusersetting']);
		}
	}

	function initdb()
	{
		global $forums, $_INPUT, $DB;
		$DB->return_die = 1;
		$DB->query("CREATE TABLE " . TABLE_PREFIX . "importtable (
									importid int(11) NOT NULL auto_increment,
									forumid smallint(6) DEFAULT '0' NOT NULL,
									filename char(255) NOT NULL,
									PRIMARY KEY (importid),
									KEY forumid (forumid)
									  )");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "user ADD importuserid BIGINT UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "user ADD isnew INT (1) UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "user ADD INDEX userimport (importuserid, isnew)");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "usergroup ADD importusergroupid INT (10) UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum ADD importcategoryid SMALLINT (5) UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum ADD importforumid SMALLINT (5) UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "forum ADD isprivate INT (5) UNSIGNED not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "moderator ADD importmoderatorid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "thread ADD importthreadid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "subscribethread ADD importsubscribethreadid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "subscribeforum ADD importsubscribeforumid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "post ADD importpostid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "poll ADD importpollid CHAR(20) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "pm ADD importpmid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "pmtext ADD importpmtextid INT (10) not null");
		$DB->query("ALTER TABLE " . TABLE_PREFIX . "attachment ADD importattachmentid INT (10) not null");
		$DB->free_result();
		$DB->query("SELECT usergroupid FROM " . TABLE_PREFIX . "usergroup WHERE grouptitle='时没'");
		if ($DB->num_rows() == 0)
		{
			$DB->query_unbuffered("INSERT INTO " . TABLE_PREFIX . "usergroup (title,usertitle,importusergroupid) VALUES ('时没','Banned',1)");
		}
		$DB->return_die = 0;
	}

	function redirect($url, $text, $text2, $is_popup = 0, $time = 2)
	{
		global $forums;
		$url = preg_replace("#(.*)(^|\.php)#", '\\1\\2?' . $forums->sessionurl, str_replace("?", '', $url));
		echo "<meta http-equiv='refresh' content=\"{$time}; url={$url}{$extra}\">\n";
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "<tr><td class='tableborder'>\n";
		echo "<div id='catfont'>\n";
		echo "<img src='" . $forums->imageurl . "/arrow.gif'>&nbsp;&nbsp;" . $text . "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "<table width='100%' cellspacing='0' cellpadding='0' align='center' border='0'>\n";
		echo "</td></tr>\n";
		echo "<div class='tdrow1' style='padding:8px'>\n";
		echo "<br />\n";
		echo "<center>" . $text2 . "<br /><a href='{$url}'>" . $forums->lang['redirectinginfo'] . "</a><br /></center>\n";
		echo "<br />\n";
		echo "</div>\n";
		echo "</td></tr>\n";
		echo "</table>\n";
	}

	function echo_flush($string)
	{
		echo $string;
		flush();
	}

	function getuserids()
	{
		global $DB;
		$users = $DB->query("SELECT id,name,importuserid FROM " . TABLE_PREFIX . "user WHERE importuserid<>0");
		while ($user = $DB->fetch_array($users))
		{
			$importuserid = $user['importuserid'];
			$userid[$importuserid] = array('id' => $user['id'], 'name' => $user['name']);
		}
		return $userid;
	}

	function getcategoryids()
	{
		global $DB;
		$forums = $DB->query("SELECT id,name,importcategoryid FROM " . TABLE_PREFIX . "forum WHERE importcategoryid<>0");
		while ($forum = $DB->fetch_array($forums))
		{
			$impforumid = intval($forum['importcategoryid']);
			$categoryid[$impforumid] = $forum['id'];
		}
		return $categoryid;
	}

	function getforumids()
	{
		global $DB;
		$forums = $DB->query("SELECT id,name,importforumid FROM " . TABLE_PREFIX . "forum WHERE importforumid<>0");
		while ($forum = $DB->fetch_array($forums))
		{
			$impforumid = intval($forum['importforumid']);
			$forumid[$impforumid] = $forum['id'];
		}
		return $forumid;
	}

	function getpollids()
	{
		global $DB;
		$polls = $DB->query("SELECT pollid,importpollid FROM " . TABLE_PREFIX . "poll WHERE importpollid<>0");
		while ($poll = $DB->fetch_array($polls))
		{
			$importpollid = $poll['importpollid'];
			$pollid[$importpollid] = $poll['pollid'];
		}
		return $pollid;
	}

	function option2bin($string)
	{
		$string = strtolower(trim($string));
		if ($string == "yes" || $string == "is" || $string == "on" || $string == "true")
		{
			return 1;
		}
		else if ($string == "no" || $string == "is not" || $string == "off" || $$string == "false")
		{
			return 0;
		}
		else
		{
			return $string;
		}
	}

	function clean_avatars($id)
	{
		global $bboptions;
		foreach(array('swf', 'jpg', 'jpeg', 'gif', 'png') as $ext)
		{
			if (@file_exists($bboptions['uploadfolder'] . "/avatar-" . $id . "." . $ext))
			{
				@unlink($bboptions['uploadfolder'] . "/avatar-" . $id . "." . $ext);
			}
		}
	}

	function importuser($user = array(), $md5 = 0, $salt = 1)
	{
		global $DB, $_INPUT, $cachekeys, $forums;
		if ($md5 == "1")
		{
			$user['password'] = md5($user['password']);
		}
		if ($salt == "1")
		{
			$user['salt'] = generate_user_salt(5);
			$user['password'] = md5($user['password']);
		}
		if (!is_array($cachekeys))
		{
			$cachekeys = $this->describe("user");
		}
		if (count($user))
		{
			$iuser = $this->dovalue($user, $cachekeys);
			$DB->insert(TABLE_PREFIX . 'user', $iuser);
		}
		$userid = $DB->insert_id();
		$userimported = sprintf($forums->lang['userimported'], utf8_htmlspecialchars($user['name']));
		$this->echo_flush($userimported . ($_INPUT['pause'] ? "<a href=" . ROOT_PATH . "" . ROOT_PATH . "user.php?" . $forums->sessionurl . "do=doform&amp;u=" . $userid . "' target='_blank'>" . $forums->lang['edit'] . "</a>" : "") . "<br /><br />\n\n");
		return $userid;
	}

	function importpm($pm = array())
	{
		global $DB, $_INPUT, $cachepms, $cachepmtexts, $forums;
		if (!is_array($cachepms))
		{
			$cachepms = $this->describe("pm");
		}
		if (!is_array($cachepmtexts))
		{
			$cachepmtexts = $this->describe("pmtext");
		}
		if (count($pm))
		{
			foreach($pm AS $pmkey => $pmvalue)
			{
				if (in_array($pmkey, $cachepms))
				{
					$ipm[$pmkey] = $pmvalue;
				}
				if (in_array($pmkey, $cachepmtexts))
				{
					$ipmtext[$pmkey] = $pmvalue;
				}
			}
			$DB->insert(TABLE_PREFIX . 'pmtext', $ipmtext);
			$ipm['messageid'] = $DB->insert_id();
			$DB->insert(TABLE_PREFIX . 'pm', $ipm);
		}
		$pmimported = sprintf($forums->lang['pmimported'], $pm['name']);
		$this->echo_flush($pmimported . "<br /><br />");
		return $pmid;
	}

	function importcategory($category = array())
	{
		global $DB, $_INPUT, $ccachekeys, $forums;
		if (!is_array($ccachekeys))
		{
			$ccachekeys = $this->describe("forum");
		}
		if (count($category))
		{
			$icategory = $this->dovalue($category, $ccachekeys);
			$DB->insert(TABLE_PREFIX . 'forum', $icategory);
		}
		$categoryid = $DB->insert_id();
		$categoryimported = sprintf($forums->lang['categoryimported'], utf8_htmlspecialchars($category['name']));
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "forum SET parentlist='$categoryid,-1' WHERE id='$categoryid'");
		$this->echo_flush($categoryimported . "<br /><br />");
		return $categoryid;
	}

	function importforum($forum = array())
	{
		global $DB, $_INPUT, $fcachekeys, $forums;
		if (!is_array($fcachekeys))
		{
			$fcachekeys = $this->describe("forum");
		}
		if (count($forum))
		{
			$iforum = $this->dovalue($forum, $fcachekeys);
			$DB->insert(TABLE_PREFIX . 'forum', $iforum);
		}
		$forumid = $DB->insert_id();
		$parentlist = $forumid . ',' . $forums->adminforum->fetch_forum_parentlist($forum['parentid']);
		$DB->query_unbuffered("UPDATE " . TABLE_PREFIX . "forum SET parentlist='$parentlist' WHERE id='$forumid'");
		$forumimported = sprintf($forums->lang['forumimported'], utf8_htmlspecialchars($forum['name']));
		$this->echo_flush($forumimported . "<br /><br />");
		return $forumid;
	}

	function importmoderator($moderator = array())
	{
		global $DB, $_INPUT, $mcachekeys, $forums;
		if (!is_array($mcachekeys))
		{
			$mcachekeys = $this->describe("moderator");
		}
		if (count($moderator))
		{
			$imoderator = $this->dovalue($moderator, $mcachekeys);
			$DB->insert(TABLE_PREFIX . 'moderator', $imoderator);
		}
		$moderatorid = $DB->insert_id();
		$moderatorimported = sprintf($forums->lang['moderatorimported'], utf8_htmlspecialchars($moderator['username']));
		$this->echo_flush($moderatorimported . "<br /><br />");
		return $moderatorid;
	}

	function importthread($thread = array())
	{
		global $DB, $_INPUT, $tcachekeys, $forums;
		if (!is_array($tcachekeys))
		{
			$tcachekeys = $this->describe("thread");
		}
		if (count($thread))
		{
			$ithread = $this->dovalue($thread, $tcachekeys);
			$DB->insert(TABLE_PREFIX . 'thread', $ithread);
		}
		$threadid = $DB->insert_id();
		$this->echo_flush(" [OK]<br /><br />");
		return $threadid;
	}

	function importpost($post = array())
	{
		global $DB, $_INPUT, $pcachekeys, $forums;
		if (!is_array($pcachekeys))
		{
			$pcachekeys = $this->describe("post");
		}
		if (count($post))
		{
			$ipost = $this->dovalue($post, $pcachekeys);
			$DB->insert(TABLE_PREFIX . 'post', $ipost);
		}
		$postid = $DB->insert_id();
		$postimported = sprintf($forums->lang['postimported'], $postid);
		$this->echo_flush($postimported . "<br />");
		return $postid;
	}

	function importattachment($attachment = array())
	{
		global $DB, $_INPUT, $acachekeys, $forums, $bboptions;
		require_once(ROOT_PATH . 'includes/functions_image.php');
		$image = new functions_image();
		if (!is_array($acachekeys))
		{
			$acachekeys = $this->describe("attachment");
		}
		if ($fp = fopen($bboptions['uploadfolder'] . '/' . $attachment['attachpath'] . '/' . $attachment['location'], 'wb'))
		{
			if (!fwrite($fp, $attachment['filedata'], strlen($attachment['filedata'])))
			{
				$attachnotimported = sprintf($forums->lang['attachnotimported'], $attachment['filename']);
				$this->echo_flush($attachnotimported . "<br />");
			}
			fclose($fp);
			@chmod($bboptions['uploadfolder'] . '/' . $attachment['attachpath'] . '/' . $attachment['location'], 0777);
		}
		if (in_array($attachment['extension'], array('gif', 'jpeg', 'jpg', 'png')))
		{
			$attachment['image'] = 1;
			$thumb_data = array();
			$subpath = SAFE_MODE ? "" : implode('/', preg_split('//', $attachment['userid'], -1, PREG_SPLIT_NO_EMPTY));
			$image->filepath = $bboptions['uploadfolder'] . '/' . $subpath;
			$image->filename = $attachment['location'];
			$image->thumbswidth = $bboptions['thumbswidth'];
			$image->thumbsheight = $bboptions['thumbsheight'];
			$thumb_data = $image->generate_thumbnail();
			$attachment['thumbwidth'] = $thumb_data['thumbwidth'];
			$attachment['thumbheight'] = $thumb_data['thumbheight'];
			$attachment['thumblocation'] = $thumb_data['thumblocation'];
		}
		if (count($attachment))
		{
			foreach($attachment AS $akey => $avalue)
			{
				if (!in_array($akey, $acachekeys)) continue;
				$iattachment[$akey] = $avalue;
			}
			$DB->insert(TABLE_PREFIX . 'attachment', $iattachment);
		}
		$attachmentid = $DB->insert_id();
		$attachimported = sprintf($forums->lang['attachimported'], utf8_htmlspecialchars($attachment['filename']));
		$this->echo_flush($attachimported . "<br />");
		return $attachmentid;
	}

	function importpoll($poll = array())
	{
		global $DB, $_INPUT, $pocachekeys, $forums, $bboptions;
		if (!is_array($pocachekeys))
		{
			$pocachekeys = $this->describe("poll");
		}
		if (count($poll))
		{
			$ipoll = $this->dovalue($poll, $pocachekeys);
			$DB->insert(TABLE_PREFIX . 'poll', $ipoll);
		}
		$pollid = $DB->insert_id();
		$pollimported = sprintf($forums->lang['pollimported'], $pollid);
		$this->echo_flush($pollimported . "<br /><br />");
		return $pollid;
	}

	function describe($table = '')
	{
		global $DB;
		$key = array();
		$keys = $DB->query("DESCRIBE " . TABLE_PREFIX . $table);
		while ($r = $DB->fetch_array($keys))
		{
			$key[$r['Field']] = $r['Field'];
		}
		return $key;
	}

	function dovalue($value, $tarray = array())
	{
		global $DB;
		foreach($value AS $k => $v)
		{
			if (!in_array($k, $tarray)) continue;
			$newvalue[$k] = $v;
		}
		return $newvalue;
	}
}

?>