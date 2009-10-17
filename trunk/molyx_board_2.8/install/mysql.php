<?php
/**
 * $Id: mysql.php 457 2007-11-30 12:49:23Z develop_tong $
 */

$mysql_data['CREATE']['ad'] = "
CREATE TABLE {$prefix}ad (
  id mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(250) NOT NULL default '',
  `type` varchar(30) NOT NULL default '',
  ad_in varchar(255) NOT NULL default '',
  starttime int(10) unsigned NOT NULL default '0',
  endtime int(10) unsigned NOT NULL default '0',
  codetype tinyint(3) unsigned NOT NULL default '0',
  code mediumtext NULL,
  htmlcode mediumtext NULL,
  click mediumint(8) unsigned NOT NULL default '0',
  displayorder smallint(3) NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['administrator'] = "
CREATE TABLE {$prefix}administrator (
  aid mediumint(8) unsigned NOT NULL default '0',
  caneditsettings tinyint(1) NOT NULL default '0',
  caneditforums tinyint(1) NOT NULL default '0',
  caneditusers tinyint(1) NOT NULL default '0',
  caneditusergroups tinyint(1) NOT NULL default '0',
  canmassprunethreads tinyint(1) NOT NULL default '0',
  canmassmovethreads tinyint(1) NOT NULL default '0',
  caneditattachments tinyint(1) NOT NULL default '0',
  caneditbbcodes tinyint(1) NOT NULL default '0',
  caneditimages tinyint(1) NOT NULL default '0',
  caneditbans tinyint(1) NOT NULL default '0',
  caneditstyles tinyint(1) NOT NULL default '0',
  caneditcaches tinyint(1) NOT NULL default '0',
  caneditcrons tinyint(1) NOT NULL default '0',
  caneditmysql tinyint(1) NOT NULL default '0',
  caneditothers tinyint(1) NOT NULL default '0',
  caneditleagues tinyint(1) NOT NULL default '0',
  caneditadmins tinyint(1) NOT NULL default '0',
  canviewadminlogs tinyint(1) NOT NULL default '0',
  canviewmodlogs tinyint(1) NOT NULL default '0',
  caneditads tinyint(1) NOT NULL default '0',
  caneditjs tinyint(1) NOT NULL default '0',
  caneditbank tinyint(1) NOT NULL default '0',
  cansendpms tinyint(1) NOT NULL default '0',
  PRIMARY KEY (aid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['adminlog'] = "
CREATE TABLE {$prefix}adminlog (
  adminlogid int(10) unsigned NOT NULL auto_increment,
  userid mediumint(8) unsigned NOT NULL default '0',
  script varchar(255) NOT NULL default '',
  action varchar(255) NOT NULL default '',
  dateline int(10) unsigned NOT NULL default '0',
  note mediumtext NULL,
  host char(15) NOT NULL default '',
  PRIMARY KEY (adminlogid),
  KEY userid (userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['adminsession'] = "
CREATE TABLE {$prefix}adminsession (
  sessionhash varchar(32) NOT NULL default '',
  userid mediumint(8) unsigned NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  host char(15) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  location varchar(250) NOT NULL default '',
  logintime int(10) unsigned NOT NULL default '0',
  lastactivity int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (sessionhash)
) TYPE=HEAP;
";

$mysql_data['CREATE']['announcement'] = "
CREATE TABLE {$prefix}announcement (
  id int(10) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  pagetext mediumtext NOT NULL,
  forumid mediumtext NOT NULL,
  userid mediumint(8) unsigned NOT NULL default '0',
  allowhtml tinyint(1) NOT NULL default '0',
  views int(10) unsigned NOT NULL default '0',
  startdate int(10) unsigned NOT NULL default '0',
  enddate int(10) unsigned NOT NULL default '0',
  active tinyint(1) NOT NULL default '1',
  PRIMARY KEY (id),
  KEY active (active,enddate,startdate)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['antispam'] = "
CREATE TABLE {$prefix}antispam (
  regimagehash varchar(32) NOT NULL default '',
  imagestamp varchar(8) NOT NULL default '',
  host char(15) NOT NULL default '',
  dateline int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (regimagehash)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['area'] = "
CREATE TABLE {$prefix}area (
  `areaid` smallint(4) NOT NULL auto_increment,
  `areaname` varchar(60) NOT NULL default '',
  `forumid` int(10) NOT NULL default '0',
  `show_record` tinyint(2) NOT NULL default '0',
  `orderid` smallint(4) NOT NULL default '0',
  PRIMARY KEY  (`areaid`),
  KEY forumid (forumid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['area_content'] = "
CREATE TABLE {$prefix}area_content (
  `id` int(10) NOT NULL auto_increment,
  `title` varchar(255) character set utf8 NOT NULL default '',
  `titlelink` varchar(255) character set utf8 default NULL,
  `target` varchar(10) character set utf8 default NULL,
  `orderid` int(10) NOT NULL default '0',
  `areaid` smallint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY areaid (areaid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['attachment'] = "
CREATE TABLE {$prefix}attachment (
  attachmentid int(10) unsigned NOT NULL auto_increment,
  filename varchar(250) NOT NULL default '',
  location varchar(250) NOT NULL default '',
  thumblocation varchar(250) NOT NULL default '',
  userid mediumint(8) NOT NULL default '0',
  counter int(10) unsigned NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  pmid int(10) unsigned NOT NULL default '0',
  postid int(10) unsigned NOT NULL default '0',
  threadid int(10) unsigned NOT NULL default '0',
  blogid int(10) unsigned NOT NULL default '0',
  posthash varchar(32) NOT NULL default '',
  visible tinyint(1) NOT NULL default '1',
  filesize int(10) unsigned NOT NULL default '0',
  thumbwidth smallint(5) unsigned NOT NULL default '0',
  thumbheight smallint(5) unsigned NOT NULL default '0',
  image tinyint(1) NOT NULL default '0',
  temp tinyint(1) NOT NULL default '0',
  extension varchar(10) NOT NULL default '',
  attachpath varchar(13) NOT NULL default '',
  inpost tinyint(1) NOT NULL default '0',
  hidetype varchar(254) default NULL,
  posttable varchar(50) NULL default '',
  PRIMARY KEY (attachmentid),
  KEY posthash (posthash),
  KEY pmid (pmid),
  KEY postid (postid, posttable),
  KEY threadid (threadid),
  KEY userid (userid,dateline)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['attachmenttype'] = "
CREATE TABLE {$prefix}attachmenttype (
  id smallint(5) unsigned NOT NULL auto_increment,
  extension varchar(18) NOT NULL default '',
  mimetype varchar(255) NOT NULL default '',
  usepost tinyint(1) NOT NULL default '1',
  useavatar tinyint(1) NOT NULL default '0',
  maxsize mediumint(8) NOT NULL default '0',
  attachimg mediumtext NOT NULL,
  PRIMARY KEY (id),
  KEY usepost (usepost, useavatar),
  KEY extension (extension)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['attachmenttype'] = "
INSERT INTO {$prefix}attachmenttype (extension, mimetype, usepost, useavatar, attachimg) VALUES
('png', 'image/png', 1, 1, 'attach/quicktime.gif'),
('gif', 'image/gif', 1, 1, 'attach/gif.gif'),
('bmp', 'image/x-ms-bmp', 1, 0, 'attach/gif.gif'),
('jpg', 'image/jpeg', 1, 1, 'attach/gif.gif'),
('jpeg', 'image/jpeg', 1, 1, 'attach/gif.gif'),
('tiff', 'image/tiff', 1, 0, 'attach/quicktime.gif'),
('ico', 'image/ico', 1, 0, 'attach/gif.gif'),
('wav', 'audio/x-wav', 1, 0, 'attach/music.gif'),
('wmv', 'video/x-msvideo', 1, 0, 'attach/win_player.gif'),
('ram', 'audio/x-pn-realaudio', 1, 0, 'attach/real_audio.gif'),
('mov', 'video/quicktime', 1, 0, 'attach/quicktime.gif'),
('mp3', 'audio/x-mpeg', 1, 0, 'attach/music.gif'),
('mpg', 'video/mpeg', 1, 0, 'attach/quicktime.gif'),
('swf', 'application/x-shockwave-flash', 0, 0, 'attach/flash.gif'),
('htm', 'application/octet-stream', 1, 0, 'attach/html.gif'),
('html', 'application/octet-stream', 1, 0, 'attach/html.gif'),
('rtf', 'text/richtext', 1, 0, 'attach/rtf.gif'),
('doc', 'application/msword', 1, 0, 'attach/doc.gif'),
('txt', 'text/plain', 1, 0, 'attach/txt.gif'),
('xml', 'text/xml', 1, 0, 'attach/script.gif'),
('php', 'application/octet-stream', 1, 0, 'attach/php.gif'),
('css', 'text/css', 1, 0, 'attach/script.gif'),
('gz', 'application/x-gzip', 1, 0, 'attach/zip.gif'),
('rar', 'application/rar', 1, 0, 'attach/zip.gif'),
('tar', 'application/x-tar', 1, 0, 'attach/zip.gif'),
('zip', 'application/zip', 1, 0, 'attach/zip.gif'),
('torrent', 'application/x-bittorrent', 1, 0, 'attach/torrent.gif')
";

$mysql_data['CREATE']['splittable'] = "
CREATE TABLE {$prefix}splittable (
  id int(5) NOT NULL auto_increment,
  name varchar(250) NOT NULL default '',
  minpid int(10) NOT NULL default '0',
  maxpid int(10) NOT NULL default '0',
  isdefaulttable tinyint(1) NOT NULL default '0',
  isempty tinyint(1) NOT NULL default '0',
  dateline int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['splittable'] = "
INSERT INTO {$prefix}splittable (id, name, minpid, maxpid, isdefaulttable, isempty, dateline) VALUES
(1, 'post', 0, 0, 1, 1, 0)
";

$mysql_data['CREATE']['creditevent'] = "
CREATE TABLE {$prefix}creditevent (
  eventid int(11) NOT NULL auto_increment,
  eventtag varchar(50) NOT NULL default '',
  eventname varchar(50) NOT NULL default '',
  eventtype tinyint(4) NOT NULL default '0',
  defaultvalue varchar(50) NOT NULL default '',
  PRIMARY KEY  (`eventid`)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['creditevent'] = "
INSERT INTO {$prefix}creditevent (eventtag, eventname, eventtype, defaultvalue) VALUES
('upattach', '" . $a_lang['mysql']['creditevent']['upattach'] . "', 1, '+5'),
('downattach', '" . $a_lang['mysql']['creditevent']['downattach'] . "', 1, '-2'),
('sendpm', '" . $a_lang['mysql']['creditevent']['sendpm'] . "', 1, '-1'),
('sendgrouppm', '" . $a_lang['mysql']['creditevent']['sendgrouppm'] . "', 1, '-5'),
('search', '" . $a_lang['mysql']['creditevent']['search'] . "', 1, '-3'),
('register', '" . $a_lang['mysql']['creditevent']['register'] . "', 1, '+15'),
('uploadavatar', '" . $a_lang['mysql']['creditevent']['uploadavatar'] . "', 1, '-5'),
('addsignature', '" . $a_lang['mysql']['creditevent']['addsignature'] . "', 1, '-5'),
('newthread', '" . $a_lang['mysql']['creditevent']['newthread'] . "', 2, '+3'),
('newpoll', '" . $a_lang['mysql']['creditevent']['newpoll'] . "', 2, '+3'),
('replythread', '" . $a_lang['mysql']['creditevent']['replythread'] . "', 2, '+1'),
('threadpoll', '" . $a_lang['mysql']['creditevent']['threadpoll'] . "', 2, '+1'),
('delthread', '" . $a_lang['mysql']['creditevent']['delthread'] . "', 2, '-5'),
('quintessence', '" . $a_lang['mysql']['creditevent']['quintessence'] . "', 2, '+10'),
('newreply', '" . $a_lang['mysql']['creditevent']['newreply'] . "', 3, '+2'),
('delreply', '" . $a_lang['mysql']['creditevent']['delreply'] . "', 3, '-4'),
('replypoll', '" . $a_lang['mysql']['creditevent']['replypoll'] . "', 3, '+1'),
('hidepostmax', '" . $a_lang['mysql']['creditevent']['hidepostmax'] . "', 4, '500'),
('hidepostmin', '" . $a_lang['mysql']['creditevent']['hidepostmin'] . "', 4, '-500'),
('paypostmax', '" . $a_lang['mysql']['creditevent']['paypostmax'] . "', 4, '+50'),
('paypostmin', '" . $a_lang['mysql']['creditevent']['paypostmin'] . "', 4, '-50'),
('evaluationmax', '" . $a_lang['mysql']['creditevent']['evaluationmax'] . "', 4, '+40'),
('evaluationmin', '" . $a_lang['mysql']['creditevent']['evaluationmin'] . "', 4, '-40'),
('evalthreadscore', '" . $a_lang['mysql']['creditevent']['evalthreadscore'] . "', 4, '+80'),
('editthread', '" . $a_lang['mysql']['creditevent']['editthread'] . "', 2, '-1'),
('editpost', '" . $a_lang['mysql']['creditevent']['editpost'] . "', 3, '-1'),
('editpoll', '" . $a_lang['mysql']['creditevent']['editpoll'] . "', 2, '-1'),
('threadhighlight', '" . $a_lang['mysql']['creditevent']['threadhighlight'] . "', 2, '-2');
";

$mysql_data['CREATE']['badword'] = "
CREATE TABLE {$prefix}badword (
  id smallint(5) unsigned NOT NULL auto_increment,
  badbefore varchar(250) NOT NULL default '',
  badafter varchar(250) NOT NULL default '',
  type tinyint(1) NOT NULL default '0',
  PRIMARY KEY (id),
  KEY badbefore (badbefore, type)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['banklog'] = "
CREATE TABLE {$prefix}banklog (
  id mediumint(8) unsigned NOT NULL auto_increment,
  dateline int(10) unsigned NOT NULL default '0',
  action varchar(250) NOT NULL default '',
  fromuserid mediumint(8) unsigned NOT NULL default '0',
  touserid mediumint(8) unsigned NOT NULL default '0',
  type tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['banfilter'] = "
CREATE TABLE {$prefix}banfilter (
  id smallint(5) unsigned NOT NULL auto_increment,
  type varchar(10) NOT NULL default 'ip',
  content mediumtext NOT NULL,
  PRIMARY KEY (id),
  KEY type (type)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['banfilter'] = "
INSERT INTO {$prefix}banfilter (type, content) VALUES
('title', '" . $a_lang['mysql']['banfilter']['admin'] . "'),
('title', '" . $a_lang['mysql']['banfilter']['mod'] . "')
";

$mysql_data['CREATE']['bbcode'] = "
CREATE TABLE {$prefix}bbcode (
  bbcodeid smallint(5) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description mediumtext NULL,
  bbcodetag varchar(255) NOT NULL default '',
  bbcodereplacement mediumtext NULL,
  twoparams tinyint(1) NOT NULL default '0',
  bbcodeexample mediumtext NULL,
  imagebutton tinyint(1) NOT NULL default '0',
  PRIMARY KEY (bbcodeid),
  KEY imagebutton (imagebutton)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['bbcode'] = "
INSERT INTO {$prefix}bbcode (title, description, bbcodetag, bbcodereplacement, twoparams, bbcodeexample, imagebutton) VALUES
('[THREAD]', '" . $a_lang['mysql']['bbcode']['tagforthread'] . "', 'thread', '<a href=\'showthread.php?t={option}\'>{content}</a>', 1, '[thread=1]" . $a_lang['mysql']['bbcode']['viewthread'] . "[/thread]', '0'),
('[POST]', '" . $a_lang['mysql']['bbcode']['tagforpost'] . "', 'post', '<a href=\'redirect.php?goto=findpost&p={option}\'>{content}</a>', 1, '[post=1]" . $a_lang['mysql']['bbcode']['viewpost'] . "[/post]', '0'),
('[MOVIE]', '[movie] " . $a_lang['mysql']['bbcode']['tagforallmovie'] . "', 'movie', '<div align=\'center\'><object id=\'player\' width=\'400\' height=\'300\' classid=\'clsid:6bf52a52-394a-11d3-b153-00c04f79faa6\' codebase=\'http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#version=6,4,5,715\' standby=\'Loading Microsoft Windows Media player Components...\' type=\'application/x-oleobject\' align=\'center\'><param name=\'url\' value=\'{content}\' /><param name=\'uimode\' value=\'full\' /><param name=\'autostart\' value=\'0\' /><param name=\'transparentatstart\' value=\'1\' /><param name=\'animationatstart\' value=\'1\' /><param name=\'showcontrols\' value=\'1\' /><param name=\'showstatusbar\' value=\'1\' /><embed type=\'application/x-mplayer2\' pluginspage=\'http://www.microsoft.com/windows/downloads/contents/products/mediaplayer/\' src=\'{content}\' align=\'middle\' width=\'400\' height=\'300\' showcontrols=\'1\' showstatusbar=\'1\' autostart=\'0\' showdisplay=\'1\' showstatusbar=\'0\'></embed></object><br /><a href=\'{content}\' target=\'_blank\'>" . $a_lang['mysql']['bbcode']['clickdown'] . "</a></div>', 0, '[movie]http://website/movie.wmv[/movie]', '1'),
('[REAL]', '[real] " . $a_lang['mysql']['bbcode']['tagforallrmmovie'] . "', 'real', '<div align=\'center\'><object id=\'{content}\' classid=\'clsid:cfcdaa03-8be4-11cf-b84b-0020afbbccfa\' height=\'300\' width=\'400\'><param name=\'controls\' value=\'imagewindow\' /><param name=\'nologo\' value=\'1\' /><param name=\'console\' value=\'{content}\' /><param name=\'autostart\' value=\'0\' /><embed type=\'audio/x-pn-realaudio-plugin\' console=\'clip1\' controls=\'imagewindow\' height=\'300\' width=\'400\' nologo=\'true\' autostart=\'0\' /></embed></object><br /><object id=\'{content}\' classid=\'clsid:cfcdaa03-8be4-11cf-b84b-0020afbbccfa\' width=\'400\' height=\'50\'><param name=\'controls\' value=\'controlpanel,statusbar\' /><param name=\'console\' value=\'{content}\' /><param name=\'autostart\' value=\'0\' /><param name=\'src\' value=\'{content}\' /><embed src=\'{content}\' type=\'audio/x-pn-realaudio-plugin\' console=\'clip1\' controls=\'controlpanel\' width=\'400\' height=\'50\' autostart=\'0\' nojava=\'true\'></embed></object><br /><a href=\'{content}\' target=\'_blank\'>" . $a_lang['mysql']['bbcode']['clickdown'] . "</a></div>', 0, '[real]http://website/movie.rm[/real]', '1'),
('[MUSIC]', '[music] " . $a_lang['mysql']['bbcode']['tagforallmusic'] . "', 'music', '<div align=\'center\'><object id=\'player\' width=\'400\' height=\'66\' classid=\'clsid:6bf52a52-394a-11d3-b153-00c04f79faa6\' codebase=\'http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#version=6,4,5,715\' standby=\'Loading Microsoft Windows Media player Components...\' type=\'application/x-oleobject\' align=\'center\'><param name=\'url\' value=\'{content}\' /><param name=\'uimode\' value=\'mini\' /><param name=\'autostart\' value=\'0\' /><param name=\'transparentatstart\' value=\'1\' /><param name=\'showdisplay\' value=\'0\' /><param name=\'showtracker\' value=\'1\' /><param name=\'animationatstart\' value=\'1\' /><param name=\'showcaptioning\' value=\'0\' /><param name=\'allowchangedisplaysize\' value=\'0\' /><param name=\'showcontrols\' value=\'1\' /><param name=\'showstatusbar\' value=\'1\' /><embed type=\'application/x-mplayer2\' pluginspage=\'http://www.microsoft.com/windows/downloads/contents/products/mediaplayer/\' src=\'{content}\' align=\'middle\' width=\'400\' height=\'66\' showcontrols=\'1\' showstatusbar=\'1\' showdisplay=\'0\' showstatusbar=\'0\'></embed></object><br /><a href=\'{content}\' target=\'_blank\'>" . $a_lang['mysql']['bbcode']['clickdown'] . "</a></div>', 0, '[music]http://website/flash.wav[/music]', '1')
";

$mysql_data['CREATE']['birthday'] = "
CREATE TABLE {$prefix}birthday (
  id MEDIUMINT(8) unsigned NOT NULL default '0',
  dateline INT(10) unsigned NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['cache'] = "
CREATE TABLE {$prefix}cache (
  `title` varchar(32) NOT NULL default '',
  `data` mediumtext NULL,
  `is_array` tinyint(1) NOT NULL default '0',
  `time` int(10) NOT NULL default '0',
  PRIMARY KEY (title)
) TYPE=MyISAM;
";
$mysql_data['INSERT']['cache'] = "
INSERT INTO {$prefix}cache (`title` , `data` , `is_array`, `time`) VALUES
('numbermembers', '0', '0', '0'),
('maxonline', '0', '0', '0'),
('maxonlinedate', '0', '0', '0'),
('newusername', '', '0', '0'),
('newuserid', '0', '0', '0')
";

$mysql_data['CREATE']['credit'] = "
CREATE TABLE {$prefix}credit (
  `creditid` int(5) unsigned NOT NULL auto_increment,
  `name` varchar(40) NOT NULL default '',
  `tag` varchar(40) NOT NULL default '',
  `unit` varchar(30) NOT NULL default '',
  `downlimit` smallint(3) NOT NULL default '0',
  `used` tinyint(1) NOT NULL default '0',
  `isdefault` tinyint(1) NOT NULL default '0',
  `initvalue` int(10) NOT NULL default '0',
  `inittime` int(10) NOT NULL default '0',
  `initevalvalue` int(10) NOT NULL default '0',
  `initevaltime` int(10) NOT NULL default '0',
  PRIMARY KEY  (`creditid`),
  KEY tag (tag)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['credit'] = "
INSERT INTO `{$prefix}credit` (`creditid`, `name`, `tag`, `unit`, `downlimit`, `used`, `initvalue`, `inittime`, `initevalvalue`, `initevaltime`, `isdefault`) VALUES
(1, '积分', 'reputation', '分', 0, 1, 10, 0, 20, 24, 1);
";

$mysql_data['CREATE']['creditrule'] = "
CREATE TABLE {$prefix}creditrule (
  `ruleid` int(10) NOT NULL auto_increment,
  `creditid` int(5) NOT NULL default '0',
  `type` tinyint(3) NOT NULL default '0',
  `lists` text NULL,
  `parameters` text NULL,
  PRIMARY KEY  (`ruleid`),
  KEY creditid (creditid)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['creditrule'] = "
INSERT INTO `{$prefix}creditrule` (`ruleid`, `creditid`, `type`, `lists`, `parameters`) VALUES
(1, 1, 0, '', 'a:26:{s:8:\"upattach\";i:5;s:10:\"downattach\";i:-2;s:6:\"sendpm\";i:-1;s:11:\"sendgrouppm\";i:-5;s:6:\"search\";i:-3;s:8:\"register\";i:15;s:12:\"uploadavatar\";i:-5;s:12:\"addsignature\";i:-5;s:9:\"newthread\";i:3;s:7:\"newpoll\";i:3;s:11:\"replythread\";i:1;s:10:\"threadpoll\";i:1;s:9:\"delthread\";i:-5;s:12:\"quintessence\";i:10;s:8:\"newreply\";i:2;s:8:\"delreply\";i:-4;s:9:\"replypoll\";i:1;s:11:\"hidepostmax\";i:500;s:11:\"hidepostmin\";i:-500;s:10:\"paypostmax\";i:50;s:10:\"paypostmin\";i:-50;s:13:\"evaluationmax\";i:40;s:13:\"evaluationmin\";i:-40;s:15:\"evalthreadscore\";i:80;s:10:\"editthread\";i:-1;s:8:\"editpost\";i:-1;}');
";

$mysql_data['CREATE']['digg_log'] = "
CREATE TABLE {$prefix}digg_log (
  `digg_id` int(10) NOT NULL auto_increment,
  `threadid` int(10) NOT NULL default '0',
  `user_id` int(10) NOT NULL default '0',
  `exponent` float(10,3) NOT NULL default '0.000',
  `digg_time` int(10) NOT NULL default '0',
  `ip` varchar(60) NOT NULL default '',
  `username` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`digg_id`),
  KEY `threadid` (`threadid`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['cron'] = "
CREATE TABLE {$prefix}cron (
  cronid int(10) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  filename varchar(255) NOT NULL default '',
  nextrun int(10) unsigned NOT NULL default '0',
  weekday tinyint(1) NOT NULL default '-1',
  monthday tinyint(2) NOT NULL default '-1',
  hour tinyint(2) NOT NULL default '-1',
  minute smallint(2) NOT NULL default '-1',
  cronhash varchar(32) NOT NULL default '',
  loglevel tinyint(1) NOT NULL default '0',
  description mediumtext NULL,
  enabled tinyint(1) NOT NULL default '1',
  PRIMARY KEY (cronid),
  KEY enabled (enabled, nextrun)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['cron'] = "
INSERT INTO {$prefix}cron (title, filename, nextrun, weekday, monthday, hour, minute, cronhash, loglevel, description, enabled) VALUES
('" . $a_lang['mysql']['cron']['cleanout'] . "', 'cleanout.php', 0, -1, -1, 1, -1, '', 1, '" . $a_lang['mysql']['cron']['cleanoutdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['rebuildstats'] . "', 'rebuildstats.php', 0, -1, -1, 1, 0, '', 1, '" . $a_lang['mysql']['cron']['rebuildstatsdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['dailycleanout'] . "', 'dailycleanout.php', 0, -1, -1, 3, 0, '', 1, '" . $a_lang['mysql']['cron']['dailycleanoutdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['birthdays'] . "', 'birthdays.php', 0, -1, -1, 0, 0, '', 1, '" . $a_lang['mysql']['cron']['birthdaysdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['announcements'] . "', 'announcements.php', 0, -1, -1, 2, 0, '', 1, '" . $a_lang['mysql']['cron']['announcementsdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['renameupload'] . "', 'renameupload.php', 0, -1, -1, 2, -1, '', 1, '" . $a_lang['mysql']['cron']['renameuploaddesc'] . "', 0),
('" . $a_lang['mysql']['cron']['promotion'] . "', 'promotion.php', 0, -1, -1, 1, -1, '', 1, '" . $a_lang['mysql']['cron']['promotiondesc'] . "', 1),
('" . $a_lang['mysql']['cron']['cleantoday'] . "', 'cleantoday.php', 0, -1, -1, 0, 0, '', 0, '" . $a_lang['mysql']['cron']['cleantodaydesc'] . "', 1),
('" . $a_lang['mysql']['cron']['refreshjs'] . "', 'refreshjs.php', 0, -1, -1, 0, 0, '', 0, '" . $a_lang['mysql']['cron']['refreshjsdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['threadviews'] . "', 'threadviews.php', 0, -1, -1, 1, -1, '', 1, '" . $a_lang['mysql']['cron']['threadviewsdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['attachmentviews'] . "', 'attachmentviews.php', 0, -1, -1, 1, -1, '', 1, '" . $a_lang['mysql']['cron']['attachmentviewsdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['cleanrecycle'] . "', 'cleanrecycle.php', 0, -1, -1, 0, 0, '', 0, '" . $a_lang['mysql']['cron']['cleanrecycledesc'] . "', 1),
('" . $a_lang['mysql']['cron']['forum_active_user'] . "', 'forum_active_user.php', 0, -1, -1, 4, -1, '', 0, '" . $a_lang['mysql']['cron']['forum_active_userdesc'] . "', 1),
('" . $a_lang['mysql']['cron']['top_digg_thread'] . "', 'top_digg_thread.php', 0, -1, -1, 1, -1, '', 0, '" . $a_lang['mysql']['cron']['top_digg_threaddesc'] . "', 1),
('" . $a_lang['mysql']['cron']['forum_active_user'] . "', 'forum_active_user.php', 0, -1, -1, 4, 0, '', 0, '" . $a_lang['mysql']['cron']['forum_active_userdesc'] . "', 1)
";

$mysql_data['CREATE']['cronlog'] = "
CREATE TABLE {$prefix}cronlog (
  cronlogid int(10) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  dateline int(10) unsigned NOT NULL default '0',
  description mediumtext NULL,
  PRIMARY KEY (cronlogid),
  KEY title (title)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['faq'] = "
CREATE TABLE {$prefix}faq (
  id mediumint(8) NOT NULL auto_increment,
  title varchar(128) NOT NULL default '',
  text mediumtext NOT NULL,
  description mediumtext NOT NULL,
  parentid mediumint(8) NOT NULL default '0',
  displayorder mediumint(8) NOT NULL default '0',
  PRIMARY KEY (id),
  KEY parentid (parentid)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['faq'] = "
INSERT INTO {$prefix}faq (id, title, text, description, parentid, displayorder) VALUES
('1','" . $a_lang['mysql']['faq']['faqtitle_1'] . "','','" . $a_lang['mysql']['faq']['faqdesc_1'] . "','0','0'),
('2','" . $a_lang['mysql']['faq']['faqtitle_2'] . "','','','0','1'),
('3','" . $a_lang['mysql']['faq']['faqtitle_3'] . "','','','0','3'),
('4','" . $a_lang['mysql']['faq']['faqtitle_4'] . "','" . $a_lang['mysql']['faq']['faqtext_4'] . "','','1','0'),
('5','" . $a_lang['mysql']['faq']['faqtitle_5'] . "','" . $a_lang['mysql']['faq']['faqtext_5'] . "','','1','2'),
('6','" . $a_lang['mysql']['faq']['faqtitle_6'] . "','" . $a_lang['mysql']['faq']['faqtext_6'] . "','','1','6'),
('7','" . $a_lang['mysql']['faq']['faqtitle_7'] . "','" . $a_lang['mysql']['faq']['faqtext_7'] . "','','1','3'),
('8','" . $a_lang['mysql']['faq']['faqtitle_8'] . "','" . $a_lang['mysql']['faq']['faqtext_8'] . "','','1','4'),
('9','" . $a_lang['mysql']['faq']['faqtitle_9'] . "','" . $a_lang['mysql']['faq']['faqtext_9'] . "','','1','5'),
('10','" . $a_lang['mysql']['faq']['faqtitle_10'] . "','" . $a_lang['mysql']['faq']['faqtext_10'] . "','','1','7'),
('11','" . $a_lang['mysql']['faq']['faqtitle_11'] . "','" . $a_lang['mysql']['faq']['faqtext_11'] . "','','1','8'),
('12','" . $a_lang['mysql']['faq']['faqtitle_12'] . "','" . $a_lang['mysql']['faq']['faqtext_12'] . "','','1','9'),
('13','" . $a_lang['mysql']['faq']['faqtitle_13'] . "','" . $a_lang['mysql']['faq']['faqtext_13'] . "','','2','1'),
('14','" . $a_lang['mysql']['faq']['faqtitle_14'] . "','" . $a_lang['mysql']['faq']['faqtext_14'] . "','','2','2'),
('15','" . $a_lang['mysql']['faq']['faqtitle_15'] . "','" . $a_lang['mysql']['faq']['faqtext_15'] . "','','2','3'),
('16','" . $a_lang['mysql']['faq']['faqtitle_16'] . "','" . $a_lang['mysql']['faq']['faqtext_16'] . "','','2','4'),
('17','" . $a_lang['mysql']['faq']['faqtitle_17'] . "','" . $a_lang['mysql']['faq']['faqtext_17'] . "','','2','5'),
('18','" . $a_lang['mysql']['faq']['faqtitle_18'] . "','" . $a_lang['mysql']['faq']['faqtext_18'] . "','','3','1'),
('19','" . $a_lang['mysql']['faq']['faqtitle_19'] . "','" . $a_lang['mysql']['faq']['faqtext_19'] . "','','3','2'),
('20','" . $a_lang['mysql']['faq']['faqtitle_20'] . "','" . $a_lang['mysql']['faq']['faqtext_20'] . "','','3','3'),
('21','" . $a_lang['mysql']['faq']['faqtitle_21'] . "','" . $a_lang['mysql']['faq']['faqtext_21'] . "','','3','4'),
('22','" . $a_lang['mysql']['faq']['faqtitle_22'] . "','" . $a_lang['mysql']['faq']['faqtext_22'] . "','','3','5'),
('23','" . $a_lang['mysql']['faq']['faqtitle_23'] . "','" . $a_lang['mysql']['faq']['faqtext_23'] . "','','3','6'),
('24','" . $a_lang['mysql']['faq']['faqtitle_24'] . "','" . $a_lang['mysql']['faq']['faqtext_24'] . "','','3','7'),
('25','" . $a_lang['mysql']['faq']['faqtitle_25'] . "','" . $a_lang['mysql']['faq']['faqtext_25'] . "','','3','8'),
('26','" . $a_lang['mysql']['faq']['faqtitle_26'] . "','','','0','4'),
('27','" . $a_lang['mysql']['faq']['faqtitle_27'] . "','" . $a_lang['mysql']['faq']['faqtext_27'] . "','','26','2'),
('28','" . $a_lang['mysql']['faq']['faqtitle_28'] . "','" . $a_lang['mysql']['faq']['faqtext_28'] . "','','26','2'),
('29','" . $a_lang['mysql']['faq']['faqtitle_29'] . "','" . $a_lang['mysql']['faq']['faqtext_29'] . "','','26','3')
";

$mysql_data['CREATE']['forum'] = "
CREATE TABLE {$prefix}forum (
  id mediumint(5) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  description mediumtext NULL,
  forumicon varchar(255) NOT NULL default '',
  this_thread mediumint(6) unsigned NOT NULL default '0',
  thread mediumint(6) unsigned NOT NULL default '0',
  post mediumint(6) unsigned NOT NULL default '0',
  todaypost mediumint(3) unsigned NOT NULL default '0',
  style smallint(5) unsigned NOT NULL default '0',
  lastthreadid int(10) unsigned NOT NULL default '0',
  allowbbcode tinyint(1) NOT NULL default '1',
  allowhtml tinyint(1) NOT NULL default '0',
  status tinyint(1) NOT NULL default '1',
  password varchar(32) NOT NULL default '',
  sortby varchar(32) NOT NULL default '',
  sortorder varchar(32) NOT NULL default '',
  prune tinyint(3) NOT NULL default '100',
  moderatepost tinyint(1) NOT NULL default '0',
  allowpoll tinyint(1) NOT NULL default '1',
  allowpollup tinyint(1) NOT NULL default '0',
  countposts tinyint(1) NOT NULL default '1',
  parentid mediumint(5) NOT NULL default '-1',
  parentlist varchar(250) NOT NULL default '',
  childlist varchar(250) NOT NULL default '',
  allowposting tinyint(1) default '1',
  customerror mediumtext NULL,
  permissions mediumtext NOT NULL,
  showthreadlist tinyint(1) NOT NULL default '0',
  unmodthreads mediumint(6) NOT NULL default '0',
  unmodposts mediumint(6) NOT NULL default '0',
  displayorder tinyint(3) NOT NULL default '0',
  forumcolumns tinyint(1) unsigned NOT NULL default '0',
  threadprefix varchar(255) NOT NULL default '',
  forcespecial tinyint(1) unsigned NOT NULL default '0',
  specialtopic varchar(255) NOT NULL default '',
  forumrule tinyint(1) NOT NULL default '0',
  lastpostid int(10) NOT NULL default '0',
  url varchar(255) NOT NULL default '',
  PRIMARY KEY (id),
  KEY parentid (parentid)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['forum'] = "
INSERT INTO {$prefix}forum (id, name, description, forumicon, thread, post, allowbbcode, allowhtml, status, password, lastthreadid, sortby, sortorder, prune, moderatepost, allowpoll, allowpollup, countposts, parentid, parentlist, childlist, allowposting, customerror, permissions, showthreadlist, unmodthreads, unmodposts, displayorder, forumcolumns, threadprefix, this_thread) VALUES
(1, '" . $a_lang['mysql']['forum']['testsort'] . "', '', '', 0, 0, 0, 0, 0, '', 0, 'lastpost', 'desc', 100, 0, 1, 0, 1, -1, '1,-1', '1,2', 0, '', 'a:5:{s:8:\\\"canstart\\\";s:7:\\\"4,3,7,6\\\";s:8:\\\"canreply\\\";s:7:\\\"4,3,7,6\\\";s:7:\\\"canread\\\";s:1:\\\"*\\\";s:9:\\\"canupload\\\";s:7:\\\"4,3,7,6\\\";s:7:\\\"canshow\\\";s:1:\\\"*\\\";}', 0, 0, 0, 1, 0, '', 0),
(2, '" . $a_lang['mysql']['forum']['testforum'] . "', '" . $a_lang['mysql']['forum']['testdesc'] . "', '', 0, 0, 1, 0, 1, '', 0, 'lastpost', 'desc', 100, 0, 1, 0, 1, 1, '2,1,-1', '2', 1, '', 'a:5:{s:8:\\\"canstart\\\";s:7:\\\"4,3,7,6\\\";s:8:\\\"canreply\\\";s:7:\\\"4,3,7,6\\\";s:7:\\\"canread\\\";s:1:\\\"*\\\";s:9:\\\"canupload\\\";s:7:\\\"4,3,7,6\\\";s:7:\\\"canshow\\\";s:1:\\\"*\\\";}', 0, 0, 0, 2, 0, '', 0)
";

$mysql_data['CREATE']['forum_attr'] = "
CREATE TABLE {$prefix}forum_attr (
`forumid` INT( 10 ) NOT NULL ,
`forumrule` TEXT NULL ,
PRIMARY KEY ( `forumid` )
) TYPE=MyISAM;
";

$mysql_data['CREATE']['icon'] = "
CREATE TABLE {$prefix}icon (
  id smallint(5) unsigned NOT NULL auto_increment,
  icontext varchar(32) NOT NULL default '',
  image varchar(128) NOT NULL default '',
  displayorder smallint(3) unsigned NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['icon'] = "
INSERT INTO {$prefix}icon (icontext, image, displayorder) VALUES
(':question:', 'question.gif', 0),
(':post:', 'post.gif', 0),
(':photo:', 'photo.gif', 0),
(':music:', 'music.gif', 0),
(':good:', 'good.gif', 0),
(':go:', 'go.gif', 0),
(':bad:', 'bad.gif', 0),
(':attention:', 'attention.gif', 0),
(':surprise:', 'surprise.gif', 0),
(':warter:', 'warter.gif', 0)
";

$mysql_data['CREATE']['javascript'] = "
CREATE TABLE {$prefix}javascript (
  id smallint(6) NOT NULL auto_increment,
  `name` varchar(250) NOT NULL default '',
  description mediumtext NULL,
  `type` tinyint(1) NOT NULL default '0',
  jsname varchar(250) NOT NULL default '',
  nextrun int(10) NOT NULL default '0',
  inids varchar(250) NOT NULL default '',
  numbers smallint(3) NOT NULL default '0',
  perline tinyint(1) NOT NULL default '0',
  selecttype varchar(20) NOT NULL default '',
  daylimit tinyint(1) NOT NULL default '0',
  orderby tinyint(1) NOT NULL default '0',
  trimtitle smallint(5) NOT NULL default '0',
  trimdescription smallint(5) NOT NULL default '0',
  trimpagetext smallint(5) NOT NULL default '-1',
  refresh smallint(5) unsigned NOT NULL default '0',
  export tinyint(1) NOT NULL default '0',
  htmlcode mediumtext NOT NULL,
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['league'] = "
CREATE TABLE {$prefix}league (
  leagueid smallint(5) unsigned NOT NULL auto_increment,
  sitename varchar(250) NOT NULL default '',
  siteurl varchar(255) NOT NULL default '',
  siteimage varchar(250) NOT NULL default '',
  siteinfo mediumtext NULL,
  displayorder smallint(3) unsigned NOT NULL default '0',
  type tinyint(1) NOT NULL default '0',
  PRIMARY KEY (leagueid),
  KEY type (type)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['league'] = "
INSERT INTO {$prefix}league (sitename, siteurl, siteimage, siteinfo, displayorder, type) VALUES
('" . $a_lang['mysql']['league']['hogesoftitle'] . "', 'http://www.hogesoft.com', './images/league/hogesoft.gif', '" . $a_lang['mysql']['league']['hogesofdesc'] . "', 1, 0),
('" . $a_lang['mysql']['league']['molyxteam'] . "', 'http://www.molyx.com', './images/league/molyx_logo.gif', '" . $a_lang['mysql']['league']['molyxdesc'] . "', 2, 0),
('W3C DHTML Valid!', 'http://validator.w3.org/check?uri=referer', 'http://www.w3.org/Icons/valid-xhtml10', 'Valid XHTML 1.0 Transitional', 4, 1),
('W3C CSS Valid!', 'http://jigsaw.w3.org/css-validator/', 'http://www.w3.org/Icons/valid-css', 'Valid CSS!', 5, 1)
";

$mysql_data['CREATE']['moderator'] = "
CREATE TABLE {$prefix}moderator (
  moderatorid smallint(5) NOT NULL auto_increment,
  forumid smallint(5) unsigned NOT NULL default '0',
  userid mediumint(8) NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  usergroupid smallint(3) unsigned NOT NULL default '0',
  usergroupname varchar(200) default NULL,
  isgroup tinyint(1) NOT NULL default '0',
  caneditposts tinyint(1) NOT NULL default '0',
  caneditthreads tinyint(1) NOT NULL default '0',
  candeleteposts tinyint(1) NOT NULL default '0',
  candeletethreads tinyint(1) NOT NULL default '0',
  canviewips tinyint(1) NOT NULL default '0',
  canopenclose tinyint(1) NOT NULL default '0',
  canremoveposts tinyint(1) NOT NULL default '0',
  canstickthread tinyint(1) NOT NULL default '0',
  canqstickthread tinyint(1) NOT NULL default '0',
  cangstickthread tinyint(1) NOT NULL default '0',
  canmoderateposts tinyint(1) NOT NULL default '0',
  canmanagethreads tinyint(1) NOT NULL default '0',
  caneditusers tinyint(1) NOT NULL default '0',
  cansplitthreads tinyint(1) NOT NULL default '0',
  canmergethreads tinyint(1) NOT NULL default '0',
  caneditrule tinyint(1) NOT NULL default '0',
  canquintessence tinyint(1) NOT NULL default '0',
  modcancommend tinyint(1) NOT NULL default '0',
  cansetst tinyint(1) NOT NULL default '0',
  canbanpost tinyint(1) NOT NULL default '0',
  bantimelimit varchar(30) NOT NULL default '',
  canbanuser tinyint(1) NOT NULL default '0',
  sendbanmsg tinyint(1) NOT NULL default '0',
  PRIMARY KEY (moderatorid),
  KEY forumid (forumid, userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['moderatorlog'] = "
CREATE TABLE {$prefix}moderatorlog (
  moderatorlogid int(10) unsigned NOT NULL auto_increment,
  forumid smallint(5) unsigned NOT NULL default '0',
  threadid int(10) unsigned NOT NULL default '0',
  postid int(10) unsigned NOT NULL default '0',
  userid mediumint(8) unsigned NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  host char(15) NOT NULL default '',
  referer varchar(255) NOT NULL default '',
  dateline int(10) unsigned NOT NULL default '0',
  title TEXT NULL,
  action varchar(128) NOT NULL default '',
  PRIMARY KEY (moderatorlogid),
  KEY userid (userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['pm'] = "
CREATE TABLE {$prefix}pm (
  pmid int(10) unsigned NOT NULL auto_increment,
  messageid int(10) unsigned NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  fromuserid mediumint(8) NOT NULL default '0',
  touserid mediumint(8) NOT NULL default '0',
  folderid smallint(3) NOT NULL default '0',
  pmread tinyint(1) NOT NULL default '0',
  attach int(10) unsigned NOT NULL default '0',
  tracking tinyint(1) default '0',
  userid mediumint(8) unsigned NOT NULL default '0',
  usergroupid varchar(255) NOT NULL default '',
  pmreadtime int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (pmid),
  KEY fromuserid (fromuserid,tracking),
  KEY userid (userid),
  KEY touserid (touserid),
  KEY usergroupid (usergroupid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['pmtext'] = "
CREATE TABLE {$prefix}pmtext (
  pmtextid int(10) unsigned NOT NULL auto_increment,
  dateline int(10) unsigned NOT NULL default '0',
  message mediumtext NOT NULL,
  savedcount smallint(5) NOT NULL default '0',
  deletedcount smallint(5) NOT NULL default '0',
  posthash varchar(32) NOT NULL default '0',
  fromuserid mediumint(8) NOT NULL default '0',
  PRIMARY KEY (pmtextid),
  KEY dateline (dateline),
  KEY deletedcount (deletedcount)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['pmuserlist'] = "
CREATE TABLE {$prefix}pmuserlist (
  id mediumint(8) unsigned NOT NULL auto_increment,
  contactid mediumint(8) unsigned NOT NULL default '0',
  userid mediumint(8) unsigned NOT NULL default '0',
  contactname varchar(32) NOT NULL default '',
  allowpm tinyint(1) NOT NULL default '0',
  description varchar(50) NOT NULL default '',
  PRIMARY KEY (id),
  KEY userid (userid,contactid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['poll'] = "
CREATE TABLE {$prefix}poll (
  pollid mediumint(8) unsigned NOT NULL auto_increment,
  tid int(10) unsigned NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  options mediumtext NOT NULL,
  votes smallint(5) NOT NULL default '0',
  forumid smallint(5) NOT NULL default '0',
  question varchar(255) NOT NULL default '',
  voters mediumtext NULL,
  multipoll tinyint(1) NOT NULL default '0',
  addtorecycle int(10) unsigned NOT NULL default '0',
  rawthreadid int(10) NOT NULL default '0',
  rawforumid int(10) NOT NULL default '0',
  PRIMARY KEY (pollid),
  KEY tid (tid),
  KEY forumid (forumid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['post'] = "
CREATE TABLE {$prefix}post (
  pid int(10) unsigned NOT NULL auto_increment,
  pagetext mediumtext NOT NULL,
  userid mediumint(8) unsigned NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  showsignature tinyint(1) NOT NULL default '0',
  allowsmile tinyint(1) NOT NULL default '0',
  host char(15) NOT NULL default '',
  dateline int(10) unsigned NOT NULL default '0',
  iconid smallint(5) unsigned NOT NULL default '0',
  moderate tinyint(1) NOT NULL default '0',
  threadid int(10) unsigned NOT NULL default '0',
  newthread tinyint(1) NOT NULL default '0',
  posthash varchar(32) NOT NULL default '',
  anonymous tinyint(1) NOT NULL default '0',
  updateuid int(10) NOT NULL default '0',
  updateuname varchar(200) NOT NULL default '',
  updatetime int(10) NOT NULL default '0',
  hidepost mediumtext NULL,
  reppost text NULL,
  logtext mediumtext NULL,
  location tinyint(1) NOT NULL default '0',
  state tinyint(1) NOT NULL default '0',
  rawthreadid int(10) NOT NULL default '0',
  displayuptlog TINYINT( 1 ) NOT NULL default '0',
  posttype TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY (pid),
  KEY userid (userid),
  KEY threadid (threadid),
  KEY dateline (dateline)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['search'] = "
CREATE TABLE {$prefix}search (
  searchid varchar(32) NOT NULL default '',
  userid mediumint(8) unsigned default '0',
  dateline int(10) unsigned NOT NULL default '0',
  maxrecord int(10) unsigned NOT NULL default '0',
  sortby varchar(32) NOT NULL default 'lastpost',
  sortorder varchar(4) NOT NULL default 'desc',
  host char(15) NOT NULL default '',
  searchype VARCHAR( 20 ) NOT NULL,
  posttable VARCHAR( 60 ) NULL,
  query mediumtext NOT NULL,
  presearchid varchar(32) NULL,
  PRIMARY KEY (searchid),
  KEY dateline (dateline),
  KEY userid (userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['session'] = "
CREATE TABLE {$prefix}session (
  sessionhash varchar(32) NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  userid mediumint(8) unsigned NOT NULL default '0',
  host char(15) NOT NULL default '',
  useragent varchar(255) NOT NULL default '',
  lastactivity int(10) unsigned NOT NULL default '0',
  invisible tinyint(1) NOT NULL default '0',
  location varchar(250) NOT NULL default '',
  usergroupid smallint(3) unsigned NOT NULL default '0',
  inforum smallint(5) unsigned NOT NULL default '0',
  inthread int(10) unsigned NOT NULL default '0',
  inblog int(10) unsigned NOT NULL default '0',
  mobile tinyint(1) NOT NULL default '0',
  avatar tinyint(1) NOT NULL default '0',
  badlocation tinyint(1) NOT NULL default '0',
  PRIMARY KEY (sessionhash),
  KEY (userid),
  KEY (lastactivity)
) TYPE=HEAP;
";

$mysql_data['CREATE']['setting'] = "
CREATE TABLE {$prefix}setting (
  settingid int(10) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description mediumtext NULL,
  groupid smallint(5) NOT NULL default '0',
  type varchar(255) NOT NULL default '',
  varname varchar(255) NOT NULL default '',
  value mediumtext NULL,
  defaultvalue mediumtext NULL,
  dropextra mediumtext NULL,
  displayorder smallint(3) unsigned NOT NULL default '0',
  addcache tinyint(1) NOT NULL default '1',
  PRIMARY KEY (settingid),
  KEY groupid (groupid)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['setting'] = "
INSERT INTO {$prefix}setting (title, description, groupid, type, varname, value, defaultvalue, dropextra, displayorder, addcache) VALUES
('" . $a_lang['mysql']['setting']['uploadurl'] . "','" . $a_lang['mysql']['setting']['uploadurldesc'] . "','1','input','uploadurl','','','','1','1'),
('" . $a_lang['mysql']['setting']['uploadfolder'] . "','" . $a_lang['mysql']['setting']['uploadfolderdesc'] . "','1','input','uploadfolder','','','uploadfolder','2','1'),
('" . $a_lang['mysql']['setting']['remoteattach'] . "','" . $a_lang['mysql']['setting']['remoteattachdesc'] . "','1','input','remoteattach','','','','3','1'),
('" . $a_lang['mysql']['setting']['headerredirect'] . "','" . $a_lang['mysql']['setting']['headerredirectdesc'] . "','1','dropdown','headerredirect','','location','" . $a_lang['mysql']['setting']['headerredirectextra'] . "','4','1'),
('" . $a_lang['mysql']['setting']['removeredirect'] . "','" . $a_lang['mysql']['setting']['removeredirectdesc'] . "','1','yes_no','removeredirect','','0','','5','1'),
('" . $a_lang['mysql']['setting']['numberformat'] . "','" . $a_lang['mysql']['setting']['numberformatdesc'] . "','1','dropdown','numberformat','',',','" . $a_lang['mysql']['setting']['numberformatextra'] . "','6','1'),
('" . $a_lang['mysql']['setting']['showrelatedthread'] . "','" . $a_lang['mysql']['setting']['showrelatedthreaddesc'] . "','11','yes_no','showrelatedthread','','1','','8','1'),
('" . $a_lang['mysql']['setting']['default_lang'] . "','" . $a_lang['mysql']['setting']['default_langdesc'] . "','1','dropdown','default_lang','','" . $_POST['lang'] . "','#show_lang#','9','1'),
('" . $a_lang['mysql']['setting']['showtoday'] . "','" . $a_lang['mysql']['setting']['showtodaydesc'] . "','1','yes_no','showtoday','','1','','10','1'),
('" . $a_lang['mysql']['setting']['miibeian'] . "','" . $a_lang['mysql']['setting']['miibeiandesc'] . "','1','input','miibeian','','','','11','1'),
('" . $a_lang['mysql']['setting']['diggexps'] . "','" . $a_lang['mysql']['setting']['diggexpsdesc'] . "','1','input','diggexps','','1 + (grouppower / 10) + (reputation / 100)','','11','1'),
('" . $a_lang['mysql']['setting']['diggshowtype'] . "','" . $a_lang['mysql']['setting']['diggshowtypedesc'] . "','1','dropdown','diggshowtype','','lastpost','" . $a_lang['mysql']['setting']['diggshowtypeextra'] . "','11','0'),
('" . $a_lang['mysql']['setting']['diggshowcondition'] . "','" . $a_lang['mysql']['setting']['diggshowconditiondesc'] . "','1','input','diggshowcondition','','7','','11','0'),
('" . $a_lang['mysql']['setting']['cookietimeout'] . "','','2','input','cookietimeout','','15','','1','1'),
('" . $a_lang['mysql']['setting']['loadlimit'] . "','" . $a_lang['mysql']['setting']['loadlimitdesc'] . "','2','input','loadlimit','','','','2','1'),
('" . $a_lang['mysql']['setting']['threadviewsdelay'] . "', '" . $a_lang['mysql']['setting']['threadviewsdelaydesc'] . "', 2, 'yes_no', 'threadviewsdelay', '', '0', '', 3, 1),
('" . $a_lang['mysql']['setting']['attachmentviewsdelay'] . "', '" . $a_lang['mysql']['setting']['attachmentviewsdelaydesc'] . "', 2, 'yes_no', 'attachmentviewsdelay', '', '0', '', 4, 1),
('" . $a_lang['mysql']['setting']['bbtitle'] . "','" . $a_lang['mysql']['setting']['bbtitledesc'] . "','3','input','bbtitle','','MolyX BOARD','','1','1'),
('" . $a_lang['mysql']['setting']['bburl'] . "','" . $a_lang['mysql']['setting']['bburldesc'] . "','3','input','bburl','','http://localhost/_1','','2','1'),
('" . $a_lang['mysql']['setting']['hometitle'] . "','" . $a_lang['mysql']['setting']['hometitledesc'] . "','3','input','hometitle','','','','3','1'),
('" . $a_lang['mysql']['setting']['homeurl'] . "','" . $a_lang['mysql']['setting']['homeurldesc'] . "','3','input','homeurl','','http://localhost','','4','1'),
('" . $a_lang['mysql']['setting']['adminurl'] . "','" . $a_lang['mysql']['setting']['adminurldesc'] . "','3','input','adminurl','','admin','','5','1'),
('" . $a_lang['mysql']['setting']['cookiedomain'] . "','" . $a_lang['mysql']['setting']['cookiedomaindesc'] . "','4','input','cookiedomain','','','','1','1'),
('" . $a_lang['mysql']['setting']['cookieprefix'] . "','" . $a_lang['mysql']['setting']['cookieprefixdesc'] . "','4','input','cookieprefix','','','','2','1'),
('" . $a_lang['mysql']['setting']['cookiepath'] . "','" . $a_lang['mysql']['setting']['cookiepathdesc'] . "','4','input','cookiepath','','','','3','1'),
('" . $a_lang['mysql']['setting']['gzipoutput'] . "','" . $a_lang['mysql']['setting']['gzipoutputdesc'] . "','4','yes_no','gzipoutput','','1','','4','1'),
('" . $a_lang['mysql']['setting']['timezoneoffset'] . "','" . $a_lang['mysql']['setting']['timezoneoffsetdesc'] . "','5','dropdown','timezoneoffset','','8','','1','1'),
('" . $a_lang['mysql']['setting']['show_format_time'] . "','" . $a_lang['mysql']['setting']['show_format_timedesc'] . "','5','dropdown','show_format_time','','0','" . $a_lang['mysql']['setting']['show_format_timeextra'] . "','2','1'),
('" . $a_lang['mysql']['setting']['timeadjust'] . "','" . $a_lang['mysql']['setting']['timeadjustdesc'] . "','5','input','timeadjust','','0','','3','1'),
('" . $a_lang['mysql']['setting']['standardtimeformat'] . "','" . $a_lang['mysql']['setting']['standardtimeformatdesc'] . "','5','input','standardtimeformat','','Y-m-d h:i A','','4','1'),
('" . $a_lang['mysql']['setting']['longtimeformat'] . "','" . $a_lang['mysql']['setting']['longtimeformatdesc'] . "','5','input','longtimeformat','','Y-m-d H:i','','5','1'),
('" . $a_lang['mysql']['setting']['registereddateformat'] . "','" . $a_lang['mysql']['setting']['registereddateformatdesc'] . "','5','input','registereddateformat','','Y-m-d','','6','1'),
('" . $a_lang['mysql']['setting']['allowselectstyles'] . "','','6','yes_no','allowselectstyles','','1','','1','1'),
('" . $a_lang['mysql']['setting']['usernameminlength'] . "', '" . $a_lang['mysql']['setting']['usernameminlengthdesc'] . "', 6, 'input', 'usernameminlength', '', '2', '', '2', '1'),
('" . $a_lang['mysql']['setting']['usernamemaxlength'] . "', '" . $a_lang['mysql']['setting']['usernamemaxlengthdesc'] . "', 6, 'input', 'usernamemaxlength', '', '10', '', '3', '1'),
('" . $a_lang['mysql']['setting']['signaturemaxlength'] . "','','6','input','signaturemaxlength','','500','','6','1'),
('" . $a_lang['mysql']['setting']['signatureallowhtml'] . "','" . $a_lang['mysql']['setting']['signatureallowhtmldesc'] . "','6','yes_no','signatureallowhtml','','0','','7','1'),
('" . $a_lang['mysql']['setting']['signatureallowbbcode'] . "','','6','yes_no','signatureallowbbcode','','1','','8','1'),
('" . $a_lang['mysql']['setting']['allowuploadsigimg'] . "','','6','yes_no','allowuploadsigimg','','1','','9','1'),
('" . $a_lang['mysql']['setting']['sigimgdimension'] . "','" . $a_lang['mysql']['setting']['sigimgdimensiondesc'] . "','6','input','sigimgdimension','','300x500','','10','1'),
('" . $a_lang['mysql']['setting']['avatarsenabled'] . "','','6','yes_no','avatarsenabled','','1','','12','1'),
('" . $a_lang['mysql']['setting']['avatarurl'] . "','','6','yes_no','avatarurl','','1','','12','1'),
('" . $a_lang['mysql']['setting']['avatamaxsize'] . "','','6','input','avatamaxsize','','50','','16','1'),
('" . $a_lang['mysql']['setting']['avatardimension'] . "','" . $a_lang['mysql']['setting']['avatardimensiondesc'] . "','6','input','avatardimension','','120x120|48x48|18x18','','17','1'),
('" . $a_lang['mysql']['setting']['maxpostchars'] . "','','7','input','maxpostchars','','20000','','1','1'),
('" . $a_lang['mysql']['setting']['minpostchars'] . "','','7','input','minpostchars','','4','','2','1'),
('" . $a_lang['mysql']['setting']['smilenums'] . "','" . $a_lang['mysql']['setting']['smilenumsdesc'] . "','7','input','smilenums','','16','','6','1'),
('" . $a_lang['mysql']['setting']['stripquotes'] . "','" . $a_lang['mysql']['setting']['stripquotesdesc'] . "','7','yes_no','stripquotes','','1','','8','1'),
('" . $a_lang['mysql']['setting']['imageextension'] . "','" . $a_lang['mysql']['setting']['imageextensiondesc'] . "','7','input','imageextension','','gif,jpeg,jpg,png','','9','1'),
('" . $a_lang['mysql']['setting']['guesttag'] . "','" . $a_lang['mysql']['setting']['guesttagdesc'] . "','7','input','guesttag','','[" . $a_lang['mysql']['setting']['guest'] . "]*','','10','1'),
('" . $a_lang['mysql']['setting']['enablepolltags'] . "','','7','yes_no','enablepolltags','','1','','11','1'),
('" . $a_lang['mysql']['setting']['maxpolloptions'] . "','','7','input','maxpolloptions','','10','','12','1'),
('" . $a_lang['mysql']['setting']['disablenoreplypoll'] . "','','7','yes_no','disablenoreplypoll','','0','','14','1'),
('" . $a_lang['mysql']['setting']['floodchecktime'] . "','" . $a_lang['mysql']['setting']['floodchecktimedesc'] . "','7','input','floodchecktime','','0','','15','1'),
('" . $a_lang['mysql']['setting']['watermark'] . "','" . $a_lang['mysql']['setting']['watermarkdesc'] . "','7','yes_no','watermark','','0','','16','1'),
('" . $a_lang['mysql']['setting']['markposition'] . "','" . $a_lang['mysql']['setting']['markpositiondesc'] . "','7','dropdown','markposition','','4','" . $a_lang['mysql']['setting']['watermarkextra'] . "','17','1'),
('" . $a_lang['mysql']['setting']['useantispam'] . "','" . $a_lang['mysql']['setting']['useantispamdesc'] . "','7','yes_no','useantispam','','0','','18','1'),
('" . $a_lang['mysql']['setting']['mxemode'] . "','" . $a_lang['mysql']['setting']['mxemodedesc'] . "','8','dropdown','mxemode','','1','" . $a_lang['mysql']['setting']['mxemodeextra'] . "','19','1'),
('" . $a_lang['mysql']['setting']['matchbrowser'] . "','" . $a_lang['mysql']['setting']['matchbrowserdesc'] . "','8','yes_no','matchbrowser','','0','','1','1'),
('" . $a_lang['mysql']['setting']['allowdynimg'] . "','" . $a_lang['mysql']['setting']['allowdynimgdesc'] . "','8','yes_no','allowdynimg','','0','','2','1'),
('" . $a_lang['mysql']['setting']['allowimages'] . "','" . $a_lang['mysql']['setting']['allowimagesdesc'] . "','8','yes_no','allowimages','','1','','3','1'),
('" . $a_lang['mysql']['setting']['forcelogin'] . "','" . $a_lang['mysql']['setting']['forcelogindesc'] . "','8','yes_no','forcelogin','','0','','5','1'),
('" . $a_lang['mysql']['setting']['WOLenable'] . "','','8','yes_no','WOLenable','','1','','6','1'),
('" . $a_lang['mysql']['setting']['enablesearches'] . "', '', '9', 'yes_no', 'enablesearches', '', '1', '', 1, 1),
('" . $a_lang['mysql']['setting']['minsearchlength'] . "','','9','input','minsearchlength','','4','minsearchlength','2','1'),
('" . $a_lang['mysql']['setting']['postsearchlength'] . "','" . $a_lang['mysql']['setting']['postsearchlengthdesc'] . "','9','input','postsearchlength','','500','','3','1'),
('" . $a_lang['mysql']['setting']['forumindex'] . "','" . $a_lang['mysql']['setting']['forumindexdesc'] . "','10','input','forumindex','','index.php','','1','1'),
('" . $a_lang['mysql']['setting']['showloggedin'] . "','','10','yes_no','showloggedin','','1','','4','1'),
('" . $a_lang['mysql']['setting']['showstatus'] . "','','10','yes_no','showstatus','','1','','5','1'),
('" . $a_lang['mysql']['setting']['showbirthday'] . "','" . $a_lang['mysql']['setting']['showbirthdaydesc'] . "','10','yes_no','showbirthday','','1','','6','1'),
('" . $a_lang['mysql']['setting']['maxonlineusers'] . "','" . $a_lang['mysql']['setting']['maxonlineusersdesc'] . "','10','input','maxonlineusers','','300','','8','1'),
('" . $a_lang['mysql']['setting']['top_digg_thread_num'] . "','" . $a_lang['mysql']['setting']['top_digg_thread_numdesc'] . "','10','input','top_digg_thread_num','','20','','4','1'),
('" . $a_lang['mysql']['setting']['showguest'] . "','" . $a_lang['mysql']['setting']['showguestdesc'] . "','10','yes_no','showguest','','0','','9','1'),
('" . $a_lang['mysql']['setting']['birthday_send'] . "','" . $a_lang['mysql']['setting']['birthday_senddesc'] . "','10','textarea','birthday_send','','0','','10','1'),
('" . $a_lang['mysql']['setting']['birthday_send_type'] . "','" . $a_lang['mysql']['setting']['birthday_send_typedesc'] . "','10','dropdown','birthday_send_type','','1','" . $a_lang['mysql']['setting']['birthday_send_typeextra'] . "','11','0'),
('" . $a_lang['mysql']['setting']['perpagepost'] . "','" . $a_lang['mysql']['setting']['perpagepostdesc'] . "','11','input','perpagepost','','5,10,15,20,25,30,35,40','','1','1'),
('" . $a_lang['mysql']['setting']['maxposts'] . "','','11','input','maxposts','','10','','2','1'),
('" . $a_lang['mysql']['setting']['viewattachedimages'] . "','" . $a_lang['mysql']['setting']['viewattachedimagesdesc'] . "','11','yes_no','viewattachedimages','','1','','3','1'),
('" . $a_lang['mysql']['setting']['viewattachedthumbs'] . "','" . $a_lang['mysql']['setting']['viewattachedthumbsdesc'] . "','11','yes_no','viewattachedthumbs','','1','viewattachedthumbs','4','1'),
('" . $a_lang['mysql']['setting']['thumbswidth'] . "','" . $a_lang['mysql']['setting']['thumbswidthdesc'] . "','11','input','thumbswidth','','200','','5','1'),
('" . $a_lang['mysql']['setting']['thumbsheight'] . "','" . $a_lang['mysql']['setting']['thumbsheightdesc'] . "','11','input','thumbsheight','','200','','6','1'),
('" . $a_lang['mysql']['setting']['allowviewresults'] . "','" . $a_lang['mysql']['setting']['allowviewresultsdesc'] . "','11','yes_no','allowviewresults','','1','','7','1'),
('" . $a_lang['mysql']['setting']['onlyonesignatures'] . "','" . $a_lang['mysql']['setting']['onlyonesignaturesdesc'] . "','11','yes_no','onlyonesignatures','','1','','8','1'),
('" . $a_lang['mysql']['setting']['quickeditorloadmode'] . "', '" . $a_lang['mysql']['setting']['quickeditorloadmodedesc'] . "', 11, 'dropdown', 'quickeditorloadmode', '', '2', '" . $a_lang['mysql']['setting']['quickeditorloadmodeextra'] . "', 9, 1),
('" . $a_lang['mysql']['setting']['quickeditordisplaymenu'] . "', '" . $a_lang['mysql']['setting']['quickeditordisplaymenudesc'] . "', 11, 'dropdown', 'quickeditordisplaymenu', '', '1', '" . $a_lang['mysql']['setting']['quickeditordisplaymenuextra'] . "', 10, 1),
('" . $a_lang['mysql']['setting']['maxthreads'] . "','','12','input','maxthreads','','25','','1','1'),
('" . $a_lang['mysql']['setting']['hotnumberposts'] . "','','12','input','hotnumberposts','','15','','6','1'),
('" . $a_lang['mysql']['setting']['showforumusers'] . "','" . $a_lang['mysql']['setting']['showforumusersdesc'] . "','12','yes_no','showforumusers','','0','showforumusers','7','1'),
('" . $a_lang['mysql']['setting']['perpagethread'] . "','" . $a_lang['mysql']['setting']['perpagethreaddesc'] . "','12','input','perpagethread','','5,10,15,20,25,30,35,40','','8','1'),
('" . $a_lang['mysql']['setting']['showsubforums'] . "','" . $a_lang['mysql']['setting']['showsubforumsdesc'] . "','12','yes_no','showsubforums','','1','','9','1'),
('" . $a_lang['mysql']['setting']['threadpreview'] . "','" . $a_lang['mysql']['setting']['threadpreviewdesc'] . "','12','dropdown','threadpreview','','0','" . $a_lang['mysql']['setting']['threadpreviewextra'] . "','13','1'),
('" . $a_lang['mysql']['setting']['commend_thread_num'] . "','" . $a_lang['mysql']['setting']['commend_thread_numdesc'] . "','12','input','commend_thread_num','','10','','13','1'),
('" . $a_lang['mysql']['setting']['forum_active_user'] . "','" . $a_lang['mysql']['setting']['forum_active_userdesc'] . "','12','input','forum_active_user','','10','','13','1'),
('" . $a_lang['mysql']['setting']['pmallowbbcode'] . "','','13','yes_no','pmallowbbcode','','1','','1','1'),
('" . $a_lang['mysql']['setting']['pmallowhtml'] . "','" . $a_lang['mysql']['setting']['pmallowhtmldesc'] . "','13','yes_no','pmallowhtml','','0','','2','1'),
('" . $a_lang['mysql']['setting']['emailreceived'] . "','" . $a_lang['mysql']['setting']['emailreceiveddesc'] . "','13','input','emailreceived','','','','3','1'),
('" . $a_lang['mysql']['setting']['emailsend'] . "','" . $a_lang['mysql']['setting']['emailsenddesc'] . "','13','input','emailsend','','','','4','1'),
('" . $a_lang['mysql']['setting']['sesureemail'] . "','" . $a_lang['mysql']['setting']['sesureemaildesc'] . "','13','yes_no','sesureemail','','1','','5','1'),
('" . $a_lang['mysql']['setting']['emailtype'] . "','" . $a_lang['mysql']['setting']['emailtypedesc'] . "','13','dropdown','emailtype','','mail','mail=PHP Mail()\nsmtp=SMTP','6','1'),
('" . $a_lang['mysql']['setting']['smtphost'] . "','" . $a_lang['mysql']['setting']['smtphostdesc'] . "','13','input','smtphost','','localhost','','7','1'),
('" . $a_lang['mysql']['setting']['smtpport'] . "','" . $a_lang['mysql']['setting']['smtpportdesc'] . "','13','input','smtpport','','25','','8','1'),
('" . $a_lang['mysql']['setting']['smtpuser'] . "','" . $a_lang['mysql']['setting']['smtpuserdesc'] . "','13','input','smtpuser','','','','9','1'),
('" . $a_lang['mysql']['setting']['smtppassword'] . "','" . $a_lang['mysql']['setting']['smtppassworddesc'] . "','13','input','smtppassword','','','','10','1'),
('" . $a_lang['mysql']['setting']['emailwrapbracket'] . "','" . $a_lang['mysql']['setting']['emailwrapbracketdesc'] . "','13','yes_no','emailwrapbracket','','0','','11','1'),
('" . $a_lang['mysql']['setting']['disablereport'] . "','" . $a_lang['mysql']['setting']['disablereportdesc'] . "','13','yes_no','disablereport','','0','','12','1'),
('" . $a_lang['mysql']['setting']['reporttype'] . "','','13','dropdown','reporttype','','pm','" . $a_lang['mysql']['setting']['reporttypeextra'] . "','13','1'),
('" . $a_lang['mysql']['setting']['enablerecyclebin'] . "','" . $a_lang['mysql']['setting']['enablerecyclebindesc'] . "','14','yes_no','enablerecyclebin','','0','','1','1'),
('" . $a_lang['mysql']['setting']['recycleforumid'] . "','" . $a_lang['mysql']['setting']['recycleforumiddesc'] . "','14','dropdown','recycleforumid','','','#show_forums#','2','1'),
('" . $a_lang['mysql']['setting']['recycleforadmin'] . "','" . $a_lang['mysql']['setting']['recycleforadmindesc'] . "','14','yes_no','recycleforadmin','','1','','3','1'),
('" . $a_lang['mysql']['setting']['recycleforsuper'] . "','" . $a_lang['mysql']['setting']['recycleforsuperdesc'] . "','14','yes_no','recycleforsuper','','1','','4','1'),
('" . $a_lang['mysql']['setting']['recycleformod'] . "','" . $a_lang['mysql']['setting']['recycleformoddesc'] . "','14','yes_no','recycleformod','','1','','5','1'),
('" . $a_lang['mysql']['setting']['allowregistration'] . "','" . $a_lang['mysql']['setting']['allowregistrationdesc'] . "','15','yes_no','allowregistration','','1','','1','1'),
('" . $a_lang['mysql']['setting']['enableantispam'] . "','" . $a_lang['mysql']['setting']['enableantispamdesc'] . "','15','dropdown','enableantispam','','gif','" . $a_lang['mysql']['setting']['enableantispamextra'] . "','2','1'),
('" . $a_lang['mysql']['setting']['moderatememberstype'] . "','" . $a_lang['mysql']['setting']['moderatememberstypedesc'] . "','15','dropdown','moderatememberstype','','0','" . $a_lang['mysql']['setting']['moderatememberstypextra'] . "','3','1'),
('" . $a_lang['mysql']['setting']['removemoderate'] . "','" . $a_lang['mysql']['setting']['removemoderatedesc'] . "','15','input','removemoderate','','0','','4','1'),
('" . $a_lang['mysql']['setting']['registerrule'] . "','','15','textarea','registerrule','','" . $a_lang['mysql']['setting']['registerruledval'] . "','','6','0'),
('" . $a_lang['mysql']['setting']['newuser_pm'] . "','" . $a_lang['mysql']['setting']['newuser_pmdesc'] . "','15','textarea','newuser_pm','','','','8','0'),
('" . $a_lang['mysql']['setting']['reg_ip_time'] . "','" . $a_lang['mysql']['setting']['reg_ip_timedesc'] . "','15','input','reg_ip_time','','0','','9','1'),
('" . $a_lang['mysql']['setting']['showprivacy'] . "','','16','yes_no','showprivacy','','0','','1','1'),
('" . $a_lang['mysql']['setting']['privacyurl'] . "','" . $a_lang['mysql']['setting']['privacyurldesc'] . "','16','input','privacyurl','','','','2','1'),
('" . $a_lang['mysql']['setting']['privacytitle'] . "','','16','input','privacytitle','','','','3','1'),
('" . $a_lang['mysql']['setting']['privacytext'] . "','" . $a_lang['mysql']['setting']['privacytextdesc'] . "','16','textarea','privacytext','','','','4','0'),
('" . $a_lang['mysql']['setting']['bbactive'] . "','" . $a_lang['mysql']['setting']['bbactivedesc'] . "','17','yes_no','bbactive','','1','','1','1'),
('" . $a_lang['mysql']['setting']['bbclosedreason'] . "','','17','textarea','bbclosedreason','','" . $a_lang['mysql']['setting']['bbclosedreasondval'] . "','','2','0'),
('" . $a_lang['mysql']['setting']['version'] . "','','-1','','version','','2.8.0 Beta2','','1','1'),
('" . $a_lang['mysql']['setting']['timenotlogin'] . "','" . $a_lang['mysql']['setting']['timenotlogindesc'] . "','20','input','timenotlogin','','30','','10','1'),
('" . $a_lang['mysql']['setting']['spider_roup'] . "','" . $a_lang['mysql']['setting']['spider_roupdesc'] . "','21','dropdown','spider_roup','','3','#show_groups#','1','1'),
('" . $a_lang['mysql']['setting']['spiderid'] . "','" . $a_lang['mysql']['setting']['spideriddesc'] . "','21','input','spiderid','','baiduspider|googlebot|msnbot|Slurp','','2','1'),
('" . $a_lang['mysql']['setting']['spideronline'] . "','" . $a_lang['mysql']['setting']['spideronlinedesc'] . "','21','dropdown','spideronline','','2','" . $a_lang['mysql']['setting']['spideronlineextra'] . "','3','1'),
('" . $a_lang['mysql']['setting']['adcolumns'] . "','" . $a_lang['mysql']['setting']['adcolumnsdesc'] . "','22','input','adcolumns','','4','','1','1'),
('" . $a_lang['mysql']['setting']['adinpost'] . "','" . $a_lang['mysql']['setting']['adinpostdesc'] . "','22','input','adinpost','','0','','2','1'),
('" . $a_lang['mysql']['setting']['rewritestatus'] . "','" . $a_lang['mysql']['setting']['rewritestatusdesc'] . "','21','yes_no','rewritestatus','','0','','4','1'),
('" . $a_lang['mysql']['setting']['quoteslengthlimit'] . "','" . $a_lang['mysql']['setting']['quoteslengthlimitdesc'] . "','11','input','quoteslengthlimit','','200','','13','1'),
('" . $a_lang['mysql']['setting']['hideattach'] . "','" . $a_lang['mysql']['setting']['hideattachdesc'] . "','1','yes_no','hideattach','','1','','30','1'),
('" . $a_lang['mysql']['setting']['userdolenlimit'] . "','','6','input','userdolenlimit','100','100','','24','1')
";

$mysql_data['CREATE']['settinggroup'] = "
CREATE TABLE {$prefix}settinggroup (
  groupid smallint(3) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  description mediumtext NULL,
  groupcount smallint(3) NOT NULL default '0',
  PRIMARY KEY (groupid)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['settinggroup'] = "
INSERT INTO {$prefix}settinggroup (groupid, title, description, groupcount) VALUES
(1, '" . $a_lang['mysql']['settinggroup']['generalsetting'] . "', '" . $a_lang['mysql']['settinggroup']['generalsettingdesc'] . "', 13),
(2, '" . $a_lang['mysql']['settinggroup']['forumoptimize'] . "', '" . $a_lang['mysql']['settinggroup']['forumoptimizedesc'] . "', 4),
(3, '" . $a_lang['mysql']['settinggroup']['sitenameurl'] . "', '" . $a_lang['mysql']['settinggroup']['sitenameurldesc'] . "', 5),
(4, '" . $a_lang['mysql']['settinggroup']['cookieoption'] . "', '" . $a_lang['mysql']['settinggroup']['cookieoptiondesc'] . "', 4),
(5, '" . $a_lang['mysql']['settinggroup']['datetimeoption'] . "', '" . $a_lang['mysql']['settinggroup']['datetimeoptiondesc'] . "', 6),
(6, '" . $a_lang['mysql']['settinggroup']['userpara'] . "', '" . $a_lang['mysql']['settinggroup']['userparadesc'] . "', 13),
(7, '" . $a_lang['mysql']['settinggroup']['postoption'] . "', '" . $a_lang['mysql']['settinggroup']['postoptiondesc'] . "', 13),
(8, '" . $a_lang['mysql']['settinggroup']['securityctrl'] . "', '" . $a_lang['mysql']['settinggroup']['securityctrldesc'] . "', 6),
(9, '" . $a_lang['mysql']['settinggroup']['searchoption'] . "', '', 3),
(10, '" . $a_lang['mysql']['settinggroup']['indexsetting'] . "', '" . $a_lang['mysql']['settinggroup']['indexsettingdesc'] . "', 9),
(11, '" . $a_lang['mysql']['settinggroup']['showthread'] . "', '', 12),
(12, '" . $a_lang['mysql']['settinggroup']['forumdisplay'] . "', '', 8),
(13, '" . $a_lang['mysql']['settinggroup']['emailpmsetting'] . "','" . $a_lang['mysql']['settinggroup']['emailpmsettingdesc'] . "', 13),
(14, '" . $a_lang['mysql']['settinggroup']['recyclesetting'] . "','" . $a_lang['mysql']['settinggroup']['recyclesettingdesc'] . "', 5),
(15, '" . $a_lang['mysql']['settinggroup']['userregoption'] . "', '', 7),
(16, '" . $a_lang['mysql']['settinggroup']['privacyance'] . "', '" . $a_lang['mysql']['settinggroup']['privacyancedesc'] . "', 4),
(17, '" . $a_lang['mysql']['settinggroup']['opencloseforum'] . "', '" . $a_lang['mysql']['settinggroup']['opencloseforumdesc'] . "', 2),
(21, '" . $a_lang['mysql']['settinggroup']['searchenginesetting'] . "', '" . $a_lang['mysql']['settinggroup']['searchenginesettingdesc'] . "', 4),
(22, '" . $a_lang['mysql']['settinggroup']['adsetting'] . "', '" . $a_lang['mysql']['settinggroup']['adsettingdesc'] . "', 2)
";

$mysql_data['CREATE']['smile'] = "
CREATE TABLE {$prefix}smile (
  id smallint(5) unsigned NOT NULL auto_increment,
  smiletext varchar(32) NOT NULL default '',
  image varchar(128) NOT NULL default '',
  displayorder smallint(3) NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['smile'] = "
INSERT INTO {$prefix}smile (smiletext, image, displayorder) VALUES
(':run:', 'run.gif', 0),
(':cry:', 'cry.gif', 0),
(':prejudice:', 'prejudice.gif', 0),
(':laugh:', 'laugh.gif', 0),
(':glad:', 'glad.gif', 0),
(':cool:', 'cool.gif', 0),
(':bother:', 'bother.gif', 0),
(':sweat:', 'sweat.gif', 0),
(':bored:', 'bored.gif', 0),
(':angry:', 'angry.gif', 0),
(':afraid:', 'afraid.gif', 0),
(':shine:', 'shine.gif', 0),
(':smile:', 'smile.gif', 0),
(':surprise:', 'surprise.gif', 0),
(':teeth:', 'teeth.gif', 0)
";

$mysql_data['CREATE']['specialtopic'] = "
CREATE TABLE {$prefix}specialtopic (
  id mediumint(8) NOT NULL auto_increment,
  name varchar(32) NOT NULL default '',
  forumids varchar(255) NOT NULL default '',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['strikes'] = "
CREATE TABLE {$prefix}strikes (
  striketime int(10) unsigned NOT NULL default '0',
  strikeip char(15) NOT NULL default '',
  username varchar(32) NOT NULL default '',
  KEY striketime (striketime),
  KEY strikeip (strikeip, username)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['style'] = "
CREATE TABLE {$prefix}style (
  styleid smallint(5) NOT NULL auto_increment,
  title varchar(150) NOT NULL default '',
  title_en varchar(150) NOT NULL default '',
  imagefolder varchar(200) NOT NULL default '',
  userselect tinyint(1) NOT NULL default '0',
  usedefault tinyint(1) NOT NULL default '0',
  parentid smallint(6) NOT NULL default '0',
  parentlist varchar(250) NOT NULL default '-1',
  css mediumtext NOT NULL,
  csscache mediumtext NOT NULL,
  version varchar(20) NOT NULL default '',
  PRIMARY KEY (styleid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['subscribeforum'] = "
CREATE TABLE {$prefix}subscribeforum (
  subscribeforumid mediumint(8) unsigned NOT NULL auto_increment,
  userid mediumint(8) NOT NULL default '0',
  forumid smallint(5) NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (subscribeforumid),
  KEY userid (userid),
  KEY forumid (forumid, userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['subscribethread'] = "
CREATE TABLE {$prefix}subscribethread (
  subscribethreadid mediumint(8) unsigned NOT NULL auto_increment,
  userid mediumint(8) NOT NULL default '0',
  threadid int(10) NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (subscribethreadid),
  KEY userid (userid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['thread'] = "
CREATE TABLE {$prefix}thread (
  tid int(10) unsigned NOT NULL auto_increment,
  title varchar(250) NOT NULL default '',
  description varchar(250) default NULL,
  open tinyint(1) unsigned default '1',
  post int(10) unsigned NOT NULL default '0',
  postuserid mediumint(8) unsigned NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  lastposterid mediumint(8) unsigned NOT NULL default '0',
  lastpost int(10) unsigned NOT NULL default '0',
  iconid smallint(5) unsigned NOT NULL default '0',
  postusername varchar(32) NOT NULL default '',
  lastposter varchar(32) NOT NULL default '',
  pollstate tinyint(1) NOT NULL default '0',
  lastvote int(10) unsigned NOT NULL default '0',
  views int(10) unsigned NOT NULL default '0',
  forumid smallint(5) unsigned NOT NULL default '0',
  visible tinyint(1) NOT NULL default '1',
  sticky tinyint(1) NOT NULL default '0',
  deleted int(10) NOT NULL default '0',
  moved varchar(64) NOT NULL default '',
  votetotal int(10) unsigned NOT NULL default '0',
  attach smallint(3) unsigned NOT NULL default '0',
  firstpostid int(10) unsigned NOT NULL default '0',
  lastpostid int(10) unsigned NOT NULL default '0',
  modposts smallint(5) unsigned NOT NULL default '0',
  quintessence tinyint(1) NOT NULL default '0',
  mod_commend tinyint(1) NOT NULL default '0',
  digg_users int(10) NOT NULL default '0',
  digg_exps float(10, 3) NOT NULL default '0',
  allrep VARCHAR( 255 ) NOT NULL default '',
  stopic mediumint(8) NOT NULL default '0',
  logtext mediumtext NULL,
  rawforumid mediumint(5) NOT NULL default '0',
  addtorecycle int(10) unsigned NOT NULL default '0',
  stickforumid int(10) unsigned NOT NULL default '0',
  posttable VARCHAR( 50 ) NULL,
  titletext TEXT NOT NULL,
  PRIMARY KEY (tid),
  KEY forumid (forumid,sticky,lastpost),
  KEY lastpost (lastpost,forumid),
  KEY postuserid (postuserid),
  KEY stopic (stopic),
  KEY quintessence (quintessence),
  KEY stickforumid (stickforumid, sticky),
  FULLTEXT KEY titletext (titletext)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['userextrafield'] = "
CREATE TABLE {$prefix}userextrafield (
  fieldid int(5) unsigned NOT NULL auto_increment,
  fieldname varchar(50) NOT NULL default '',
  fieldtag varchar(50) NOT NULL default '',
  fielddesc text NOT NULL,
  showtype varchar(50) NOT NULL default '',
  ismustfill tinyint(1) NOT NULL default '0',
  length int(3) NOT NULL default '0',
  maxlength int(3) NOT NULL default '0',
  minlength int(3) NOT NULL default '0',
  tablename varchar(32) NOT NULL default '',
  datatype varchar(32) NOT NULL default '',
  checkregular text NULL,
  listcontent text NULL,
  type tinyint(1) NOT NULL default '0',
  isonly tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (fieldid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['user'] = "
CREATE TABLE {$prefix}user (
  id mediumint(8) unsigned NOT NULL auto_increment,
  name varchar(60) NOT NULL default '',
  usergroupid smallint(3) NOT NULL default '0',
  membergroupids varchar(255) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  email varchar(60) NOT NULL default '',
  joindate int(10) unsigned NOT NULL default '0',
  host char(15) NOT NULL default '',
  posts mediumint(7) unsigned NOT NULL default '0',
  timezoneoffset varchar(4) default '8',
  style smallint(5) unsigned NOT NULL default '0',
  lastpost int(10) unsigned NOT NULL default '0',
  forbidpost varchar(100) NOT NULL default '0',
  lastvisit int(10) unsigned NOT NULL default '0',
  lastactivity int(10) unsigned NOT NULL default '0',
  viewprefs varchar(64) NOT NULL default '-1&-1',
  moderate varchar(100) NOT NULL default '0',
  liftban varchar(100) NOT NULL default '',
  salt varchar(5) NOT NULL default '',
  birthday varchar(10) NOT NULL default '',
  gender tinyint(1) unsigned default '0',
  avatar tinyint(1) unsigned default '0',
  pmfolders mediumtext NULL,
  pmunread tinyint(2) unsigned default '0',
  pmtotal smallint(5) unsigned NOT NULL default '0',
  signature mediumtext NULL,
  options int(10) unsigned NOT NULL default '0',
  quintessence smallint(5) NOT NULL default '0',
  emailcharset varchar(50) NOT NULL default '',
  usercurdo text NULL,
  userdotime int(10) unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  KEY usergroupid (usergroupid),
  KEY joindate (joindate),
  KEY userdotime (userdotime)
) TYPE=MyISAM;
";
$mysql_data['CREATE']['userdo'] = "
CREATE TABLE {$prefix}userdo (
  did int(10) NOT NULL auto_increment,
  userid int(10) NOT NULL default '0',
  dowhat text NOT NULL,
  time int(10) NOT NULL default '0',
  touserid int(10) NOT NULL default '0',
  tousername varchar(200) NOT NULL default '',
  PRIMARY KEY  (did),
  KEY userid (userid),
  KEY touserid (touserid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['evaluationlog'] = "
CREATE TABLE {$prefix}evaluationlog (
  evaluationid int(10) NOT NULL auto_increment,
  forumid smallint(5) NOT NULL default '0',
  threadid int(10) NOT NULL default '0',
  postid int(10) NOT NULL default '0',
  postuserid mediumint(8) NOT NULL default '0',
  actionuserid mediumint(8) NOT NULL default '0',
  affect INT(10) NOT NULL default '0',
  creditid INT(10) NOT NULL default '0',
  creditname varchar(60) NOT NULL default '',
  reason varchar(255) NOT NULL default '',
  dateline int(10) NOT NULL default '0',
  actionusername VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY (evaluationid),
  KEY postid (postid),
  KEY threadid (threadid),
  KEY postuserid (postuserid),
  KEY creditid (creditid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['useractivation'] = "
CREATE TABLE {$prefix}useractivation (
  useractivationid varchar(32) NOT NULL default '',
  userid mediumint(8) unsigned NOT NULL default '0',
  usergroupid smallint(3) unsigned NOT NULL default '0',
  tempgroup smallint(3) unsigned NOT NULL default '0',
  dateline int(10) unsigned NOT NULL default '0',
  type tinyint(1) NOT NULL default '0',
  host char(15) NOT NULL default '',
  PRIMARY KEY (useractivationid),
  KEY type (type)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['usergroup'] = "
CREATE TABLE {$prefix}usergroup (
  usergroupid smallint(3) unsigned NOT NULL auto_increment,
  grouptitle varchar(32) NOT NULL default '',
  groupranks varchar(100) NOT NULL default '',
  groupicon varchar(250) NOT NULL default '',
  grouppower int(5) NOT NULL default '0',
  onlineicon varchar(250) NOT NULL default '',
  opentag varchar(250) NOT NULL default '',
  closetag varchar(250) NOT NULL default '',
  cananonymous tinyint(1) NOT NULL default '0',
  canview tinyint(1) NOT NULL default '0',
  canviewmember tinyint(1) NOT NULL default '0',
  canviewothers tinyint(1) NOT NULL default '0',
  cansearch tinyint(1) NOT NULL default '0',
  cansearchpost tinyint(1) NOT NULL default '0',
  canemail tinyint(1) NOT NULL default '0',
  caneditprofile tinyint(1) NOT NULL default '0',
  canpostnew tinyint(1) NOT NULL default '0',
  canreplyown tinyint(1) NOT NULL default '0',
  canreplyothers tinyint(1) NOT NULL default '0',
  caneditpost tinyint(1) NOT NULL default '0',
  candeletepost tinyint(1) NOT NULL default '0',
  canopenclose tinyint(1) NOT NULL default '0',
  candeletethread tinyint(1) NOT NULL default '0',
  canpostpoll tinyint(1) NOT NULL default '0',
  canvote tinyint(1) NOT NULL default '0',
  supermod tinyint(1) NOT NULL default '0',
  cancontrolpanel tinyint(1) NOT NULL default '0',
  canappendedit tinyint(1) NOT NULL default '0',
  canviewoffline tinyint(1) NOT NULL default '0',
  passmoderate tinyint(1) NOT NULL default '0',
  passflood tinyint(1) NOT NULL default '0',
  canuseavatar tinyint(1) NOT NULL default '0',
  hidelist tinyint(1) NOT NULL default '0',
  canpostclosed tinyint(1) NOT NULL default '0',
  canposthtml tinyint(1) NOT NULL default '0',
  caneditthread tinyint(1) NOT NULL default '0',
  canpmattach tinyint(1) NOT NULL default '0',
  candownload tinyint(1) NOT NULL default '1',
  canshow tinyint(1) NOT NULL default '1',
  canblog tinyint(1) NOT NULL default '0',
  canuseflash tinyint(1) NOT NULL default '0',
  cansignature tinyint(1) NOT NULL default '1',
  cansigimg tinyint(1) NOT NULL default '0',
  passbadword tinyint(1) NOT NULL default '0',
  perpostattach int(10) NOT NULL default '0',
  pmquota int(5) NOT NULL default '50',
  pmsendmax int(5) NOT NULL default '0',
  searchflood mediumint(6) NOT NULL default '20',
  edittimecut int(10) NOT NULL default '0',
  attachlimit int(10) NOT NULL default '0',
  attachnum int(10) NOT NULL default '4',
  canevaluation tinyint(1) NOT NULL default '0',
  canevalsameuser tinyint(1) NOT NULL default '0',
  displayorder smallint(3) UNSIGNED NOT NULL default '0',
  PRIMARY KEY (usergroupid),
  KEY displayorder (displayorder)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['usergroup'] = "
INSERT INTO {$prefix}usergroup (usergroupid, grouptitle, groupranks, groupicon, grouppower, onlineicon, opentag, closetag, cananonymous, canview, canviewmember, canviewothers, cansearch, canemail, caneditprofile, canpostnew, canreplyown, canreplyothers, caneditpost, candeletepost, canopenclose, candeletethread, canpostpoll, canvote, supermod, cancontrolpanel, canappendedit, canviewoffline, passmoderate, passflood, canuseavatar, hidelist, canpostclosed, canposthtml, caneditthread, canpmattach, candownload, canshow, canuseflash, passbadword, perpostattach, pmquota, pmsendmax, searchflood, edittimecut, attachlimit, attachnum, canblog, canevaluation, canevalsameuser, cansigimg, cansignature, cansearchpost, displayorder) VALUES
(1, 'fieldusergroup1_title', '', '', 2, '', '', '', 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(2, 'fieldusergroup2_title', '', '', 1, 'images/online/guest.gif', '', '', 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3, 'fieldusergroup3_title', '', '', 4, 'images/online/member.gif', '', '', 0, 1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1000, 20, 1, 20, 10, 100000, 4, 0, 1, 0, 0, 1, 0, 3),
(4, 'fieldusergroup4_title', 'fieldusergroup4_rank', '', 7, 'images/online/admin.gif', '', '', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, 1, 1, 1, 0, 500, 5, 0, 5, 0, 4, 1, 1, 1, 1, 1, 1, 4),
(5, 'fieldusergroup5_title', '', '', 3, '', '', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5),
(6, 'fieldusergroup6_title', 'fieldusergroup6_rank', '', 6, 'images/online/smod.gif', '', '', 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 1, 0, 1, 1, 1, 1, 1, 0, 0, 70, 0, 0, 0, 0, 4, 1, 1, 1, 1, 1, 1, 6),
(7, 'fieldusergroup7_title', 'fieldusergroup7_rank', '', 5, 'images/online/mod.gif', '', '', 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 1, 1, 0, 5000, 70, 5, 0, 0, 300000, 4, 1, 1, 1, 1, 1, 1, 7)
";

$mysql_data['CREATE']['userpromotion'] = "
CREATE TABLE {$prefix}userpromotion (
  userpromotionid int(10) unsigned NOT NULL auto_increment,
  usergroupid int(10) unsigned NOT NULL default '0',
  joinusergroupid int(10) unsigned NOT NULL default '0',
  date int(10) unsigned NOT NULL default '0',
  posts int(10) unsigned NOT NULL default '0',
  reputation int(10) NOT NULL default '0',
  strategy smallint(6) NOT NULL default '0',
  type smallint(6) NOT NULL default '2',
  date_sign varchar(2) NOT NULL DEFAULT '>=',
  posts_sign varchar(2) NOT NULL DEFAULT '>=',
  reputation_sign varchar(2) NOT NULL DEFAULT '>=',
  PRIMARY KEY (userpromotionid),
  KEY usergroupid (usergroupid)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['usertitle'] = "
CREATE TABLE {$prefix}usertitle (
  id smallint(5) unsigned NOT NULL auto_increment,
  post int(10) unsigned NOT NULL default '0',
  title varchar(128) NOT NULL default '',
  ranklevel varchar(128) NOT NULL default '',
  PRIMARY KEY (id),
  KEY post (post)
) TYPE=MyISAM;
";

$mysql_data['INSERT']['usertitle'] = "
INSERT INTO {$prefix}usertitle (post, title, ranklevel) VALUES
(0, '" . $a_lang['mysql']['usertitle']['newuser'] . "', '1'),
(100, '" . $a_lang['mysql']['usertitle']['mediatemember'] . "', '2'),
(300, '" . $a_lang['mysql']['usertitle']['highermember'] . "', '3')
";

$mysql_data['CREATE']['userexpand'] = "
CREATE TABLE {$prefix}userexpand (
  id mediumint(8) unsigned NOT NULL default '0',
  reputation float(10,2) NOT NULL default '0.00',
  evalreputation int(5) NOT NULL default '0',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";

$mysql_data['CREATE']['userextra'] = "
CREATE TABLE {$prefix}userextra (
  id mediumint(8) unsigned NOT NULL default '0',
  loanreturn int(10) NOT NULL default '0',
  loanamount int(10) NOT NULL default '0',
  loaninterest smallint(5) NOT NULL default '0',
  question varchar(255) NOT NULL default '',
  answer varchar(255) NOT NULL default '',
  PRIMARY KEY (id)
) TYPE=MyISAM;
";
?>