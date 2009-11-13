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
# $Id: install_lang_zh-cn.php 443 2007-11-20 12:43:26Z develop_tong $
# **************************************************************************#
$a_lang = array(
	'install' => array(),
	'mysql' => array()
);

$a_lang['install']['versiontoolow'] = '由于 PHP 版本太低( < 4.3.3 )，MolyX BOARD 无法正常安装。<br>请联系服务器管理员升级PHP。';
$a_lang['install']['script'] = '安装脚本';
$a_lang['install']['studios'] = '魔力论坛魔力体验 - MolyX Magic Experience';
$a_lang['install']['customsupport'] = '版权所有: 南京厚建软件有限责任公司';
$a_lang['install']['next'] = '下一步';
$a_lang['install']['back'] = '返回';
$a_lang['install']['error'] = '错误';
$a_lang['install']['login'] = '登录';
$a_lang['install']['adminpwtooshort'] = '管理员密码设置太短了，请将密码设置在 5 个字符以上!';
$a_lang['install']['passwordnowmatch'] = '两次输入的密码不一致，请返回重试。';
$a_lang['install']['chmoderror'] = '无法写入配置文件！<br />请将 ../includes/config.php 文件属性设置为 CHMOD 0755, CHMOD 0775 或 CHMOD 0777 (如果无法确认的话，设置为 0777) 并刷新本页面。. 或者你可以在上一步下载一份程序生成的配置文件，放置到此目录内。点击后退按钮选择你要操作的其他方式。';
$a_lang['install']['mysqlerror'] = '无法创建数据库 "%s": <b>%s</b>';
$a_lang['install']['connecterror'] = '无法连接数据库，请确认填写了正确的用户名和密码。<br />MySQL 返回结果: <b>%s</b>';
$a_lang['install']['queryerror'] = '执行查询错误: <i>%s</i><br /><br />MySQL 返回结果: <b>%s</b>';
$a_lang['install']['installation'] = '安装脚本';
$a_lang['install']['licagreement'] = '使用协议声明';
$a_lang['install']['licread'] = '我已阅读并完全同意以上声明';
$a_lang['install']['licaccept'] = '必须同意我们的协议声明才能继续进行下一步的安装。';
$a_lang['install']['canwrite'] = '可写';
$a_lang['install']['cannotwrite'] = '不可写';
$a_lang['install']['dircontinue'] = '系统目录检查无误，您可以进行下一步的安装。';
$a_lang['install']['dirnotcontinue'] = '系统中的一个或多个目录不可写，您需要将上面出现的红色目录属性设置为0777模式，安装无法继续。请检查目录属性后重新刷新此页面。';
$a_lang['install']['directory'] = '目录';
$a_lang['install']['file'] = '文件';
$a_lang['install']['mysqldata'] = '数据库信息';
$a_lang['install']['entermysqldata'] = '请在下表填写你的数据库信息。';
$a_lang['install']['dbtype'] = '数据库类型';
$a_lang['install']['dbhost'] = '数据库主机名/IP地址';
$a_lang['install']['dbuser'] = '数据库用户名';
$a_lang['install']['dbpass'] = '数据库密码';
$a_lang['install']['dbcharset'] = '请设定数据库连接方式';
$a_lang['install']['set_dbcharset'] = '由于您的MySQL数据库版本大于 4.1 且您的默认数据库字符集不为UTF-8，因此请在下面设定您的数据库字符集连接方式。注意！不正确的设定可能会无法成功安装程序！';
$a_lang['install']['current_char'] = '当前字符集类型';
$a_lang['install']['recommend_char'] = '推荐填写';
$a_lang['install']['selectdb'] = '请选择数据库';
$a_lang['install']['choosedb'] = '请在下面菜单中选择用于安装本系统的数据库，或者在下面直接输入数据库名称。如果数据库不存在的话，系统将尝试创建新库。';
$a_lang['install']['existingdb'] = '现有库';
$a_lang['install']['usefield'] = '使用填写的数据库';
$a_lang['install']['orname'] = '<b>或者</b>填写数据库名称:';
$a_lang['install']['chooseprefix'] = '请选择 MySQL 数据表前缀';
$a_lang['install']['tablelist'] = '数据库 <b>"%s"</b> 当前包含以下数据表:';
$a_lang['install']['enterprefix'] = '<p>请输入用于每个数据表开始部分的前缀。如果使用前缀的话，你就可以在同一数据库内安装多个相同的 MolyX BOARD。</p> 数据表前缀:';
$a_lang['install']['dontchange'] = '如果不清楚的话，不要更改此设定';
$a_lang['install']['deleteexisting'] = '覆盖(删除)已有数据表';
$a_lang['install']['importstyles'] = '下一步将开始导入论坛风格设定，请确认在安装目录内存在 MolyX-style.xml 文件';
$a_lang['install']['importstyles'] = '下一步将进行论坛风格设定和风格缓存生成。';
$a_lang['install']['styleexists'] = '风格 MolyX-style.xml 文件存在，可以执行导入。';
$a_lang['install']['stylenotexists'] = '风格 MolyX-style.xml 文件不存在，无法完成导入';
$a_lang['install']['createadmin'] = '创建管理员账号';
$a_lang['install']['sitesettings'] = '论坛参数设置';
$a_lang['install']['hometitle'] = '<b>网站名称</b>';
$a_lang['install']['homeurl'] = '<b>网站地址</b>';
$a_lang['install']['bbtitle'] = '<b>论坛名称</b>';
$a_lang['install']['bburl'] = '<b>论坛地址</b>';
$a_lang['install']['emailreceived'] = '<b>用于接收信件的邮箱地址</b>';
$a_lang['install']['emailsend'] = '<b>用于发送信件的邮箱地址</b>';
$a_lang['install']['cookiedomain'] = '<b>Cookie 作用域</b><br />即你想使本论坛的cookie起作用的范围。如果你想将此效果作用于你的网站“yourhost.com”，而不仅仅是“forums.yourhost.com”，那请在这里输入“.yourhost.com”(注意有两个点！)。本项可以留空。';
$a_lang['install']['cookieprefix'] = '<b>Cookie 前缀</b><br />可以此设置在单一主机上安装多个论坛。';
$a_lang['install']['cookiepath'] = '<b>保存 Cookies 的路径</b><br />保存cookies的路径。如果在同一个域名里运行不止一个论坛，最好各个论坛设置不同的路径。';
$a_lang['install']['cantexec'] = '升级程序无法正确执行。(版本号不匹配)';
$a_lang['install']['updatenotes'] = '请核对安装文件是否与当前论坛版本相匹配后再决定是否升级<br />不正确的升级版本文件可能会造成系统损坏。';
$a_lang['install']['defaultstyle'] = '默认风格';
$a_lang['install']['xmlparseerror'] = 'XML解析错误';
$a_lang['install']['atlines'] = '于行号';
$a_lang['install']['styleerror'] = '无效的风格文件。';
$a_lang['install']['username'] = '用户名';
$a_lang['install']['email'] = '邮件';
$a_lang['install']['password'] = '密码';
$a_lang['install']['twopassword'] = '确认密码';
$a_lang['install']['completing'] = '安装已成功完成！';
$a_lang['install']['completingtxt'] = '在完成安装前，需要将数据库配置文件保存在服务器内。创建这个配置文件需要服务器的 <b>../includes/</b> 目录有写入权限。请再次确认此目录权限后继续。如果不清楚如何操作的话，请将此目录权限设置为 0777 模式，Windows 系统不用考虑权限设置。<br /><br /><b>注意:</b> 你也可以下载一份程序创建的 config.php 文件并上传到 <b>../includes/</b> 目录内。请注意在你上传了这个文件后，安装程序已经全部执行完成，本安装程序将不可用。<br /><br /><a href="install.php?action=generate_config&amp;lang=zh-cn&amp;hostname=%s&amp;user=%s&amp;pass=%s&amp;db=%s&amp;prefix=%s&amp;superadmin=%s">下载配置文件</a>';
$a_lang['install']['denied'] = '论坛已安装';
$a_lang['install']['deniedtxt'] = '论坛已经安装！%s<br /><br />如果你需要重新安装论坛，请删除 <b>../includes/config.php</b> 后再重新尝试。';
$a_lang['install']['selectupdate'] = '请从下面的列表中选择要更新的信息。';
$a_lang['install']['updateinfo'] = '更新信息';
$a_lang['install']['enteradminname'] = '请输入正确的管理员账号！！';
$a_lang['install']['reqver'] = '所需版本';
$a_lang['install']['nowupdatetable'] = '正在更新数据表';
$a_lang['install']['newver'] = '更新后版本';
$a_lang['install']['author'] = '作者';
$a_lang['install']['date'] = '日期';
$a_lang['install']['executable'] = '执行？';
$a_lang['install']['notes'] = '注释';
$a_lang['install']['yes'] = '是';
$a_lang['install']['no'] = '否';
$a_lang['install']['na'] = 'N/A';
$a_lang['install']['infotxt'] = '
                      <p>
                      在接下来的安装过程中本脚本将帮助您将程序包完整搭建在您的服务器内。请您先确认以下安装配置:                      </p>
                    <ul>
                      <li>MySQL 主机名称/IP 地址</li>
                      <li>MySQL 用户名和密码</li>
                      <li>MySQL 数据库名称 (如果没有创建新数据库的权限)</li>
					  <li>./CACHE 目录及子目录权限为 0777 (UNIX系统)</li>
					  <li>./DATA 目录及子目录权限为 0777 (UNIX系统)</li>
					  <li>./includes/config.php 文件权限为 0777 (UNIX系统)</li>
                    </ul>
					以上的配置信息您可以在 includes/config.php 文件内直接配置；更改目录，文件权限请自行在FTP内更改。如果您无法确认以上的配置信息，请与您的服务商联系，我们无法为您提供任何帮助。
					<br />
                    <br />
					如果在安装的过程中出现了任何问题，请您访问我们的<a href="http://www.molyx.com/" target="_blank">技术支持论坛</a>
					<br />
                    <br />
					点击下一步，将开始进行安装 MolyX Board。';
$a_lang['install']['finished'] = '
<b>安装已成功完成！</b><br />
<br />
如果使用的是UNIX 系统，请将 <b>../includes/</b> 目录的访问权限设置为 0755.<br />
<br />
安装已成功完成。你可以从下面的链接访问论坛:<br />
<ul>
<li><a target="_blank" href="%s">MolyX Board 主目录</a></li>
<li><a target="_blank" href="%s">MolyX Board 管理面板</a></li>
<li><a target="_blank" href="http://www.molyx.com/">MolyX Board 技术支持论坛</a></li>
</ul>';
$a_lang['install']['updatefinished'] = '
<b>升级已成功完成！</b><br />
<br />
<b>请在升级后重新建立缓存，并重新生成论坛全部模板，否则系统改动可能无法生效。<br /><br />为确保系统安全，请在升级后删除 install 文件夹！</b><br />
<br />
你可以从下面的链接访问论坛:<br />
<ul>
<li><a target="_blank" href="%s">MolyX Board 主目录</a></li>
<li><a target="_blank" href="%s">MolyX Board 管理面板</a></li>
<li><a target="_blank" href="http://www.molyx.com/">MolyX Board 技术支持论坛</a></li>
</ul>';
$a_lang['install']['noupdates'] = '
<b>更新信息</b><br />
<br />
目前无可用更新<br />
请访问 <a href="http://www.molyx.com/">MolyX 主页</a> 获取更多更新脚本。';
$a_lang['mysql']['banfilter']['admin'] = '管理员';
$a_lang['mysql']['banfilter']['mod'] = '版主';
$a_lang['mysql']['bbcode']['tagforthread'] = '此标签方便你快速定位一个主题的位置。';
$a_lang['mysql']['bbcode']['tagforpost'] = '此标签方便你快速定位一个帖子的位置。';
$a_lang['mysql']['bbcode']['viewthread'] = '查看主题';
$a_lang['mysql']['bbcode']['viewpost'] = '查看帖子';
$a_lang['mysql']['bbcode']['tagforallmovie'] = '标签允许贴各种格式的电影。';
$a_lang['mysql']['bbcode']['tagforallrmmovie'] = '标签允许贴各种RM格式的电影。';
$a_lang['mysql']['bbcode']['clickdown'] = '点此下载';
$a_lang['mysql']['bbcode']['tagforallmusic'] = '标签允许贴各种格式的音乐。';
$a_lang['mysql']['cron']['cleanout'] = '每小时数据清理';
$a_lang['mysql']['cron']['cleanoutdesc'] = '删除无效的验证数据，Sessions，搜索数据';
$a_lang['mysql']['cron']['rebuildstats'] = '每日重建论坛统计';
$a_lang['mysql']['cron']['rebuildstatsdesc'] = '重建论坛统计';
$a_lang['mysql']['cron']['dailycleanout'] = '每日数据清理';
$a_lang['mysql']['cron']['dailycleanoutdesc'] = '清除无效的主题订阅';
$a_lang['mysql']['cron']['birthdays'] = '每日生日会员';
$a_lang['mysql']['cron']['birthdaysdesc'] = '建立每天过生日会员缓存';
$a_lang['mysql']['cron']['announcements'] = '更新公告';
$a_lang['mysql']['cron']['announcementsdesc'] = '清除超过公告终止时间的公告';
$a_lang['mysql']['cron']['renameupload'] = '更改上传文件夹名称';
$a_lang['mysql']['cron']['renameuploaddesc'] = '防止用户盗链文件地址，定时更改上传文件夹目录名称';
$a_lang['mysql']['cron']['promotion'] = '用户晋级';
$a_lang['mysql']['cron']['promotiondesc'] = '每小时根据晋级策略更新用户的所在用户组';
$a_lang['mysql']['cron']['cleantoday'] = '更新每日发帖信息';
$a_lang['mysql']['cron']['cleantodaydesc'] = '每天0点重置今日发帖信息';
$a_lang['mysql']['cron']['refreshjs'] = '刷新JS生成代码';
$a_lang['mysql']['cron']['refreshjsdesc'] = '在这里刷新 JavaScript 调用设定的参数';
$a_lang['mysql']['faq']['faqtitle_1'] = "用户维护";
$a_lang['mysql']['faq']['faqdesc_1'] = "这部分是关于如何维护你的个人资料、头像以及浏览设置等等的信息。";
$a_lang['mysql']['faq']['faqtitle_2'] = "论坛常规使用方法";
$a_lang['mysql']['faq']['faqtitle_3'] = "阅读和发布信息";
$a_lang['mysql']['faq']['faqtitle_4'] = "我为什么要注册？";
$a_lang['mysql']['faq']['faqtext_4'] = "管理员可能要求你必须注册为会员才可以使用论坛的全部功能。注册是免费的，同时注册后你可以获得下列权利:<br /><br /><ul><br />	<li>发布新主题</li><br /><li>回复其他人的主题</li><br />	<li>编辑你的帖子</li><br />	<li>发送短消息给其他会员</li><br />	<li>下载论坛附件</li><br /></ul><br /><br />如果要注册的话，你需要指定一个用户名、密码以及一个有效的邮箱地址。请放心，你所输入的邮箱地址在未经许可的情况下并不会给你发送任何“垃圾邮件”，你可以选择隐藏你的邮箱地址，那样在任何时候其他人给你发送邮件都不会显示你的邮箱地址。（如果要核实，你可以尝试给其他会员发送一封邮件。） 管理员可能也要求通过给你发送邮件才能完成你在论坛的注册，因此请一定确认你所提供的邮箱地址是有效并且正在使用的邮箱。";
$a_lang['mysql']['faq']['faqtitle_5'] = "这个论坛使用了Cookies？";
$a_lang['mysql']['faq']['faqtext_5'] = "在论坛是否使用cookies是可选的，但是使用可能会提高你在本站的经验值。Cookies主要用于字你上次访问以来是否在主题或各个论坛内有新贴发表，或者当你离开后再次返回论坛时自动记录你的信息。<br /><p>当你注册的时候，有一个选项“下次返回论坛时自动登录”供你选择。它将通过cookie存储你的用户名和密码保存到你的电脑上。如果你正在使用的是一台共用的计算机，例如在图书馆、学校或在网吧内，或者你你放心其他人可能会使用这台电脑，我们建议你就不要使用这个功能。</p><br /><p>论坛同样提供你选项通过cookie来记录你当前的session变量，确保你在访问论坛时一直保持在登录状态。如果你不通过cookie存储这些信息，论坛session数据会通过你点击的链接来发送。如果你的计算机是通过代理服务器访问互联网，并且其他用户也通过这个代理访问同样的链接的时候，当选择不使用cookie可能会造成问题。</p>";
$a_lang['mysql']['faq']['faqtitle_6'] = "什么是表情符号？";
$a_lang['mysql']['faq']['faqtext_6'] = "<p>表情符号是一个小图标，你可以插入到你的帖子内容中表达你的情绪，是高兴还是困惑等等。例如，如果你输入的是一个讽刺意见，然而你所表达的仅仅“是一个玩笑”的意思，那你可以很简单的插入一个“wink”符号。</p><br /><p>如果你使用过邮件或网上聊天系统，或许你对表情符号的概念已经司空见惯了。某几个文字字符相互组合就可以转换为一个表情符号。例如: <b>:) </b> 会被转换为一个笑脸。解释这个表情符号代码，将你的脑袋向左转看这个字符，你会发现 <b>:) </b> 变成了一双眼睛和一张正在微笑的嘴。</p><br /><p>有关论坛可以使用的全部表情符号列表，请<a href=\"misc.php?do=icon\"><b>点击这里</b></a>。</p><br /><p>在某些情况下，你可能希望在你的文字内容中不要将文本自动转换为表情符号。那当你发表一个新帖子的时候，可以看到一个复选框，允许你“禁用表情符号”。</p>";
$a_lang['mysql']['faq']['faqtitle_7'] = "如何清除我的 Cookies？";
$a_lang['mysql']['faq']['faqtext_7'] = "你可以点击<a href=\"login.php?do=logout\">这里</a>清除你在论坛内的全部cookie记录。如果你再次返回论坛的时候仍在登录状态中，你可能必须要通过手动删除你的cookies。<br /><br /><br /><br />这是用于 Internet Explorer 5 的说明:<br /><br /><ol><br />	<li>关闭所有的 Internet Explorer 窗口。</li><br />	<li>点击“开始”按钮。</li><br />	<li>移动到“搜索”上并从出现的菜单中点击“文件和文件夹”。</li><br />	<li>在出现的新窗口中，在“内容文字”区域内，输入论坛地址，注意不要带“http://”以及“www.”部分。例如，如果论坛地址是“http://www.molyx.com/index.php”，你需要输入“molyx.com”（不要带引号）</li><br />	<li>在“查找目录”对话框内，输入“C:\Windows\Cookies\”（不要带引号）并点击“搜索”</li><br />	<li>在完成搜索后，选中所有文件（点击一个文件，再按 CTRL+A）并将它们全部删除（点击“删除”键或者使用 SHIFT+DEL 组合键）</li><br /></ol><br /><br />你的cookies现在已被全部删除。你应该重新启动计算机再次访问论坛确认一下。";
$a_lang['mysql']['faq']['faqtitle_8'] = "如何在个人资料中更改我的信息？";
$a_lang['mysql']['faq']['faqtext_8'] = "保持个人资料是最新的记录是每个会员的责任。尤其要确保你的邮箱地址现在仍在使用。你可以在你的个人资料中更改任何项目，当然你的用户名除外。一旦你注册了你的用户名，你将永远使用它登录。如果有极特别情况，你可以请求管理员更改你的用户名，但请给出一个充足的理由。<br /><br /><br /><br />通过 <a href=\"usercp.php?do=editprofile\">这里</a> 编辑你的个人资料。";
$a_lang['mysql']['faq']['faqtitle_9'] = "什么是个人签名？";
$a_lang['mysql']['faq']['faqtext_9'] = "当你注册以后，你就可以设定你自己的签名。这些文本将出现在所有你所发表的帖子的下方，看起来有点像便签的抬头。<br /><br /><br /><br />如果管理员允许会员使用个人签名，那么在你所发表的任何帖子中都会出现是否显示个人签名的选项。如果你设定了一个签名，那么论坛会在你所发表的信息中自动添加你的签名。当你撰写新的帖子时，你也可以不点选“显示个人签名”复选框，禁止在这个帖子中出现个人签名。<br /><br /><br /><br />你可以在编辑你所发表过的帖子时重新选择是否“显示个人签名”的选项来改变个人签名在帖子中的状态。<br /><br /><br /><br />你可以通过编辑你的个人资料设定你的个人签名。";
$a_lang['mysql']['faq']['faqtitle_10'] = "我忘记了我的密码，我要怎么做？";
$a_lang['mysql']['faq']['faqtext_10'] = "如果你忘记了你的密码，你可以在任何要求填入密码的页面点击“忘了密码”链接。<br /><br /><br />这时将会转入一个页面，在这里你只要填入你注册时使用的邮箱地址，你的密码就会马上自动发送过去。";
$a_lang['mysql']['faq']['faqtitle_11'] = "如何在个人资料中添加自定义头衔？";
$a_lang['mysql']['faq']['faqtext_11'] = "如果管理员允许使用自定义头衔，你可以通过编辑你的个人资料指定一段文字，输入的文字将会出现在自定义用户头衔中。";
$a_lang['mysql']['faq']['faqtitle_12'] = "如何在我的用户名下方添加一个头像？";
$a_lang['mysql']['faq']['faqtext_12'] = "这些小的图片称为头像。这些图片会出现在所有你所发表的帖子中你的用户名下面。可以使用两种方式选择头像：管理员预先提供的头像和你自己上传的头像。<br /><br /><br /><br />如果管理员提供了一组头像并且头像功能可用的话，你可以选择一个头像用于描述你的个性。<br /><br /><br /><br />管理员也可能同样允许使用自定义头像，将允许你你你自己的计算机中上传一个头像。";
$a_lang['mysql']['faq']['faqtitle_13'] = "我如何在论坛使用搜索功能？";
$a_lang['mysql']['faq']['faqtext_13'] = "<p>你可以通过用户名，帖子内或主题中正确的关键词，通过日期，在指定的论坛内搜索帖子。</p><br /><p>使用搜索功能，请在页面上方点击“搜索”链接。</p><br /><p>你可以在任何有搜索权限的论坛内搜索 - 如果你没有查看隐私论坛的权限时，你将无法在这些论坛内执行搜索。</p>";
$a_lang['mysql']['faq']['faqtitle_14'] = "我可以通过论坛给其他会员发送邮件吗？";
$a_lang['mysql']['faq']['faqtext_14'] = "<p>当然可以！给其他会员发送一封邮件，你可以在<a href=\"memberlist.php?\"><b>会员列表</b></a>中查找任何你希望联系的会员，或者在任何其他会员所发表的帖子中点击邮件图标来给其他会员发送联系邮件</p><br /><p>通常情况下会打开一个表单页面，你可以在这里输入信息内容。当你打完你的信息内容后，点击［发送邮件］按钮，你的邮件就会立刻发送出去。出于隐私考虑，接收方的邮箱地址在发送过程中不会显示。</p><br /><p>注意，如果你无法找到一个会员的邮件链接，那么有可能是管理员禁止了论坛邮件功能，或者这个会员不希望接受来自论坛的其他会员给他发送邮件。</p><br /><p>另一个常用的邮件功能有，你可以将你认为你的好朋友会感兴趣的页面链接的地址通过邮件发送给他。当你查看主题的时候，你可以在这个页面中找到一个链接，可以允许你发送一个简短的说明。你的推荐id会出现在你发送的信息的链接中，因此如果你的朋友通过你所发送的链接查看注册论坛的话，你的推荐总人数将会被自动记录！</p><br /><p>已注册的会员也可以使用<a href=\"private.php?\">短消息系统</a>给其他会员发送私人信息。</p>";
$a_lang['mysql']['faq']['faqtitle_15'] = "什么是短消息？";
$a_lang['mysql']['faq']['faqtext_15'] = "<p>如果管理员允许使用<a href=\"private.php?\"><b>短消息</b></a>系统，已注册会员就可以给其他会员发送短消息。</p><br /><p><b>发送短消息</b></p><br /><p>短消息的工作方式有点像邮件系统，但是仅限于论坛的注册会员才能使用。你可以在你发送的短消息中使用BBCODE代码，表情符号和贴图。</p><br /><p>你可以点击控制面板中短消息内的“<a href=\"private.php?do=newpm\"><b>撰写新短消息</b></a>”链接给论坛会员发送一条短消息，或者点击在会员发表的帖子内的短消息图标来发送短消息。</p><br /><p>在你发送短消息的时候，你可以选择保存一个副本在你的发件箱文件夹内。</p><br /><p><b>短消息文件夹</b></p><br /><p>默认状态下，你在短消息内有两个文件夹可供使用，它们是收件箱和发件箱。</p><br /><p><a href=\"private.php?\"><b>收件箱</b></a>文件夹包含了任何你收取的信息，你可以在这里查看所有你收到的信息内容，连同给你发送私人信息的会员名，以及私人信息的发送时间和日期。</p><br /><p>The <a href=\"private.php?folderid=-1\"><b>发件箱</b></a>文件夹包含了所有你发送的短消息的副本，你所指定的需要保留副本的信息保留在这里以备将来使用。</p><br /><p>你可以点击“<a href=\"private.php?do=editfolders\"><b>编辑文件夹</b></a>”链接创建一个新的用于短消息的文件夹。</p><br /><p>每一个文件夹都允许你控制所选择的不同短消息，可以将它们移动到你自定义的文件夹内，或者将它们完全删除。</p><br /><p>管理员或许限定了你的文件夹内可保留的短消息存储数量，因此你需要定时删除一些旧的短消息。如果你的短消息数量超出了管理员限定数量，在你删除旧的短消息以前，你将无法发送和收取短消息。</p><br /><p>在你阅读短消息的时候，你可以随时回复这条短消息，或者将这条短消息转发给其他会员。你也可以将这条消息转发给好友列表中的多个会员。</p><br /><p><b>短消息跟踪</b></p><br /><p>当你发送一条新的短消息的时候，你可能需要收取这条消息的阅读回执。你可以通过<a href=\"private.php?do=showtrack\"><b>私人信息跟踪</b></a>页面检查你所发送的信息是否被接收方阅读。</p><br /><p>这个页面分为两个部分：未阅读的短消息和已阅读的短消息。</p><br /><p><b>未阅读的短消息</b>部分显示的所有信息表示你已经发出了阅读回执请求，然而接收方还没有阅读这些信息。接收方在论坛中的最后活动时间也将被显示。如果你认为这些信息在最近一段时间内不会被阅读，或者有其他原因的话，此部分的信息也可以取消发送。如果管理员允许此功能，被取消的私人信息也可以随时被激活。</p><br /><p><b>已阅读的短消息</b>部分显示的所有信息表示你所发出了阅读回执请求已被阅读且已从接收方确认。短消息的阅读时间也将被显示。</p><br /><p>你可以通过选择一些短消息并点击［结束跟踪］按钮来将结束跟踪任何短消息。</p>";
$a_lang['mysql']['faq']['faqtitle_16'] = "如何使用会员列表？";
$a_lang['mysql']['faq']['faqtext_16'] = "<p><a href=\"memberlist.php?\"><b>会员列表</b></a>包含了论坛中的所有已注册会员。你可以按照用户名的字母顺序，会员注册时间，或者按照会员的发帖数量来查看会员列表。</p><br /><p>会员列表中也可以使用<a href=\"memberlist.php?do=search\"><b>搜索功能</b></a>，可以按照你所指定的范围内快速定位会员记录，例如搜索在过去一周内新注册的所有会员等等。</p>";
$a_lang['mysql']['faq']['faqtitle_17'] = "什么是公告？";
$a_lang['mysql']['faq']['faqtext_17'] = "<p>公告是由管理员或版主所发表的特殊信息。这个只是单方面给用户传达论坛通知。由于你无法回复公告内容，如果你希望参与公告的讨论，你可以在论坛内创建一个新的主题。</p>";
$a_lang['mysql']['faq']['faqtitle_18'] = "我可以在帖子和信息中使用指定的代码或标签吗？";
$a_lang['mysql']['faq']['faqtext_18'] = "<p>在多数情况下，你的帖子可能只包含纯文本，但在一些特殊时候，你可能需要强调某些单词或者使用代码来定义（例如粗体字或斜体字）。</p><br /><p>依据论坛规定，你可以使用HTML语法来实现这些效果。然而，更多的时候这些是被禁止的，管理员可能会禁止使用HTML代码，而仅可以使用BBCode代码：这是一个特别的标签系统，你可以用它来创建出大多数的文字效果。BBCode代码可以非常方便的使用，而且可以避免恶意脚本的使用，并且不会打乱页面布局，因而更有优势。</p><br /><p>你可以查找是否论坛允许使用<b>表情符号</b>，使用表情符号可以表达你的情绪，以及<b>贴图代码[img]</b>，允许你在信息中添加图片。</p><br /><p>有关BBCode代码的更多信息，<a href=\"misc.php?do=bbcode\"><b>点击这里</b></a>。</p>";
$a_lang['mysql']['faq']['faqtitle_19'] = "如何创建和参与投票？";
$a_lang['mysql']['faq']['faqtext_19'] = "<p>你可能注意到了论坛中有一些主题内还包含了一个可以参与投票的问题部分。这些主题称之为“投票”并按照下列方式创建的:</p><br /><p><b>创建一个新投票</b></p><br /><p>当你发表一个新主题的时候，你可以使用创建一个投票的选项。</p><br /><p>这个功能允许你提出一个问题，并设置一些可能的答案。其他会员可以通过备选的答案参与投票，投票结果会被显示在这个主题内。</p><br /><p>这是一个投票的例子:</p><br /><blockquote><br />  <p>你喜爱的颜色是哪个？</p><br />  <ol><br />    <li>红色</li><br />    <li>蓝色</li><br />    <li>黄色</li><br />    <li>绿色</li><br />    <li>带黄点的天蓝色</li><br />  </ol><br /></blockquote><br /><p>只有在你发表新主题的时候才能创建投票，只需要点选页面下方的“是！我要发表投票”复选框，并设定好你准备包括的答案数量就可以了。</p><br /><p>当你点击提交按钮后，你将会转到投票创建页面，在这里你可以提出问题，并在答案列表中写入你准备包括的答案。</p><br /><p>你也可以指定一个用于这个投票的时限，比如将投票进行一个星期就停止投票。</p><br /><b>参与投票并查看投票结果</b><br /><p>参与一个投票，只需要选择你希望投票的选项，并点击［投票］按钮。你可以在你投票前点击“查看结果”链接查看当前这个投票的结果。是否参与投票是完全可选的，你可以选择任何一个选项参与投票或者根本不投票。</p><br /><p>通常情况下，你参与了一个投票后就不能对这个投票再次投票了，因此在你投票前请慎重考虑！</p>";
$a_lang['mysql']['faq']['faqtitle_20'] = "什么是多重引用？";
$a_lang['mysql']['faq']['faqtext_20'] = "<p>多用引用是为了能更方便的与会员进行交流而设计的一套引用系统，你可以很方便的在同一个主题内答复多位会员发表的主题</p><br /><p>你只需要在主题页面将\"引用\"按钮选为高亮状态就可以通过页面中的任何回复按钮，或者直接通过页面下方的快速回复框输入信息来回复多人的话题。</p>";
$a_lang['mysql']['faq']['faqtitle_21'] = "什么是附件？";
$a_lang['mysql']['faq']['faqtext_21'] = "<p>管理员可能允许你使用上传附件的功能，允许你在帖子中上传一些文件。它可以是一个图片，一个文本文档，一个压缩文件等等。论坛上传的附件并不一定支持你硬盘中的文件类型！</p><br /><p>在一个新的帖子中上传一个文件，只需要选择好你电脑内的附件，并点击上传按钮就可以上传完成一个附件。</p><br /><p>在发贴之后，附件会显示在你所发表的信息中。查看附件内容（如果没有被直接显示），只需要点击在附件图标后的文件名链接就可以了。</p>";
$a_lang['mysql']['faq']['faqtitle_22'] = "我可以编辑自己的帖子吗？";
$a_lang['mysql']['faq']['faqtext_22'] = "<p>如果你已经注册，你就可以编辑和删除你的帖子。但请注意管理员按照自己的意愿决定是否禁止这个功能。你编辑帖子可能受到时间限制，这些取决于管理员对论坛的设定。</p><br /><p>编辑或者删除你的帖子，在你想编辑的帖子中点击\"编辑\"图标。</p><br /><p>在你完成修改以后，可能会出现一个注释，用于告知其他会员你已经编辑过你的帖子。管理员和版主可能不会受这个条件的限制，而不会出现这个注释。</p>";
$a_lang['mysql']['faq']['faqtitle_23'] = "什么是标题图标？";
$a_lang['mysql']['faq']['faqtext_23'] = "<p>管理员可能允许在主题、帖子和私人信息中使用标题图标。标题图标允许你指定一个小图标随帖子一同发表，使用它可以直接表达你这个帖子所表达的情绪。</p>";
$a_lang['mysql']['faq']['faqtitle_24'] = "什么是版主？";
$a_lang['mysql']['faq']['faqtext_24'] = "<p>版主负责监控指定论坛中信息内容。通常可以编辑和删除帖子，移动主题，并处理其他事务。一个论坛的版主通常对会员而言可以有明显的帮助而并对他所管理的论坛内容有独到的见解。</p>";
$a_lang['mysql']['faq']['faqtitle_25'] = "为什么在我的帖子中有一些单词不会显示？";
$a_lang['mysql']['faq']['faqtext_25'] = "<p>管理员会对某些单词进行过滤。如果你的帖子中包含了这些被过滤的单词，这部分内容就会变为这样: ****。</p><br /><p>这些单词过滤对所有用户都适用，计算机会自动查找并替换那些单词为替代的文字。但这种过滤并不精确。</p>";
$a_lang['mysql']['faq']['faqtitle_26'] = "积分策略扩展";
$a_lang['mysql']['faq']['faqtitle_27'] = "什么是积分策略扩展？";
$a_lang['mysql']['faq']['faqtext_27'] = "积分策略扩展是论坛的对用户贡献度的一种扩充管理手段。当用户在“发布新主题”，“回复帖子”，“上传或下载附件”，“获得精华主题评分及获得勋章”，“发送短消息”时自动根据管理员设定的相关参数而得到的相关附加用户参数。这个参数不但表明了用户对论坛的相关贡献度大小，而且，一定的参数下限会影响到用户使用论坛的一些相关模块的权限。";
$a_lang['mysql']['faq']['faqtitle_28'] = "当前论坛设定了哪些积分扩展模块？";
$a_lang['mysql']['faq']['faqtext_28'] = "论坛管理员已设定了以下模块用于用户积分扩展设定：<br /><br /><#show_credit#><br /><br />说明：<br />如果该参数为正数，则用户在执行相关操作时，会相应对此设定的数值进行累加。<br />如果该参数为负数，则用户在执行相关操作时，会相应对此设定的数值进行减法操作。<br />如果该参数不存在，则用户所做操作对此积分模块不存在影响。<br />如果用户的某积分达到管理员设定的积分下限，则相应涉及到减法操作的项目将无法完成。";
$a_lang['mysql']['faq']['faqtitle_29'] = "为什么我的一些操作无法执行？";
$a_lang['mysql']['faq']['faqtext_29'] = "如果你的积分扩展参数低于管理员设定的某些功能限定时，那么可能你的一些相关操作将被限制。直到你的这个积分值高于管理员设定的标准才可以继续执行这个操作。";

$a_lang['mysql']['forum']['testsort'] = "测试分类";
$a_lang['mysql']['forum']['testforum'] = "测试分区";
$a_lang['mysql']['forum']['testdesc'] = "默认安装板块";

$a_lang['mysql']['league']['molyxteam'] = "魔力论坛 MolyX Board";
$a_lang['mysql']['league']['molyxdesc'] = "魔力论坛魔力体验 - MolyX Magic Experience";
$a_lang['mysql']['league']['hogesoftitle'] = "厚建软件 HOGE Software";
$a_lang['mysql']['league']['hogesofdesc'] = "创新凸显实力 - 我们的产品体系 LivCMS / MolyX Board / LivSearch / LivBlog";

$a_lang['mysql']['setting']['uploadurl'] = "上传文件的URL地址";
$a_lang['mysql']['setting']['uploadurldesc'] = "你的上传文件夹的URL地址";
$a_lang['mysql']['setting']['uploadfolder'] = "上传文件夹的路径";
$a_lang['mysql']['setting']['uploadfolderdesc'] = "用于存放上传文件的绝对路径，注意不是URL链接。";
$a_lang['mysql']['setting']['remoteattach'] = "远程附件URL地址";
$a_lang['mysql']['setting']['remoteattachdesc'] = "如果设定此选项，则此论坛作为镜像服务器使用，用户无法通过此论坛上传附件。请注意关闭主服务器的计划任务更改附件文件夹地址功能";
$a_lang['mysql']['setting']['headerredirect'] = "自动跳转方式";
$a_lang['mysql']['setting']['headerredirectdesc'] = "这个选项用于不使用跳转页面的转向方式。请根据所使用的服务器系统选择最佳方式。";
$a_lang['mysql']['setting']['headerredirectextra'] = "location=location 方式 (*nix 系统)\nrefresh=Refresh 刷新 (Windows 系统)\nhtml=HTML META 跳转 (如果以上方式失败的话...)";
$a_lang['mysql']['setting']['removeredirect'] = "删除全部转向页面链接？";
$a_lang['mysql']['setting']['removeredirectdesc'] = "如果设置为“是”的话，将会删除所有跳转页面。";
$a_lang['mysql']['setting']['numberformat'] = "数字格式化方式";
$a_lang['mysql']['setting']['numberformatdesc'] = "你可以在这里选择一个用于千位符的分割字符";
$a_lang['mysql']['setting']['numberformatextra'] = "none=不做格式化\n,=,\n.=.";
$a_lang['mysql']['setting']['showrelatedthread'] = "是否在帖子列表显示相关主题";
$a_lang['mysql']['setting']['showrelatedthreaddesc'] = "全文检索相关主题。";
$a_lang['mysql']['setting']['default_lang'] = "默认使用的语言包";
$a_lang['mysql']['setting']['default_langdesc'] = "默认用户访问网站使用的语言包";
$a_lang['mysql']['setting']['diggexps'] = "推荐指数算法表达式";
$a_lang['mysql']['setting']['diggexpsdesc'] = "设定会员推荐影响值得计算方法<br />grouppower[+-*/]积分标识[+-*/]某值";
$a_lang['mysql']['setting']['diggshowtype'] = "首页显示推荐主题的条件类型";
$a_lang['mysql']['setting']['diggshowtypedesc'] = "时间单位为天";
$a_lang['mysql']['setting']['diggshowtypeextra'] = "lastpost=主题更新时间\ndateline=主题发表时间\ndigg_time=主题被推荐时间";
$a_lang['mysql']['setting']['diggshowcondition'] = "推荐显示条件值设置";
$a_lang['mysql']['setting']['diggshowconditiondesc'] = "数字有效";
$a_lang['mysql']['setting']['showtoday'] = "是否在页面显示当日发帖统计？";
$a_lang['mysql']['setting']['showtodaydesc'] = "如果使用的话，则前台板块及统计列表内会显示当日的发帖数量";
$a_lang['mysql']['setting']['miibeian'] = "网站备案信息代码";
$a_lang['mysql']['setting']['miibeiandesc'] = "请在这里填写你的网站备案信息ID。<br />详细参考：<a href=\'http://www.miibeian.gov.cn\' target=\'_blank\'>信产部备案网站</a>";
$a_lang['mysql']['setting']['cookietimeout'] = "用户在线超时时限 [单位: 分钟]";
$a_lang['mysql']['setting']['loadlimit'] = "*NIX 系统负载限制";
$a_lang['mysql']['setting']['loadlimitdesc'] = "对于某些 *NIX 系统，可以通过这里限制用户访问论坛，一旦服务器超过了负载，其他用户将禁止访问论坛。<br />留空将不作限制";
$a_lang['mysql']['setting']['bbtitle'] = "论坛名称";
$a_lang['mysql']['setting']['bbtitledesc'] = "你的论坛名称。出现在每一页的标题、导航菜单中。";
$a_lang['mysql']['setting']['bburl'] = "论坛地址";
$a_lang['mysql']['setting']['bburldesc'] = "你的论坛地址。出现在每一页的标题、导航菜单中。";
$a_lang['mysql']['setting']['hometitle'] = "网站名称";
$a_lang['mysql']['setting']['hometitledesc'] = "你的网站名称。将出现在所有页面的底部。";
$a_lang['mysql']['setting']['homeurl'] = "网站地址";
$a_lang['mysql']['setting']['homeurldesc'] = "你的网站地址。将出现在所有页面的底部。";
$a_lang['mysql']['setting']['top_digg_thread_num'] = "首页显示推荐主题的条数";
$a_lang['mysql']['setting']['top_digg_thread_numdesc'] = "按照推荐指数的高低排序";
$a_lang['mysql']['setting']['adminurl'] = "管理控制面板目录";
$a_lang['mysql']['setting']['adminurldesc'] = "在这里可以设置管理控制面板的目录地址，可以使用相对目录方式。注意结尾不要加反斜杠。更改此设定还需要手工在FTP中更改相应目录。";
$a_lang['mysql']['setting']['cookiedomain'] = "Cookie 作用域";
$a_lang['mysql']['setting']['cookiedomaindesc'] = "即你想使本论坛的cookie起作用的范围。如果你想将此效果作用于你的网站“yourhost.com”，而不仅仅是“forums.yourhost.com”，那请在这里输入“.yourhost.com”(注意有两个点！)。本项可以留空。";
$a_lang['mysql']['setting']['cookieprefix'] = "Cookie 前缀";
$a_lang['mysql']['setting']['cookieprefixdesc'] = "可以此设置在单一主机上安装多个论坛。";
$a_lang['mysql']['setting']['cookiepath'] = "保存 Cookies 的路径";
$a_lang['mysql']['setting']['cookiepathdesc'] = "保存cookies的路径。如果在同一个域名里运行不止一个论坛，最好各个论坛设置不同的路径。";
$a_lang['mysql']['setting']['gzipoutput'] = "开启 GZIP 页面压缩输出？";
$a_lang['mysql']['setting']['gzipoutputdesc'] = "选择是将允许论坛通过 gzip 输出页面，可以很明显的降低带宽需求。但只有在客户端支持的情况下才可使用，并且对 HTTP 1.1 兼容，并会加大服务器系统开销。";
$a_lang['mysql']['setting']['timezoneoffset'] = "服务器所在时区";
$a_lang['mysql']['setting']['timezoneoffsetdesc'] = "<span style=\'color:red\'>请在这里正确的设置服务器所在时区，如果设置正确但是出现了一个小时时差的话，说明服务器启用了夏令时设置，需要用户自行在个人控制面板中修正。</span>";
$a_lang['mysql']['setting']['show_format_time'] = '使用绝对时间';
$a_lang['mysql']['setting']['show_format_timedesc'] = '打开本选项会把发帖信息中的相对时间改为绝对时间';
$a_lang['mysql']['setting']['show_format_timeextra'] = "0=否\r\n1=是";
$a_lang['mysql']['setting']['timeadjust'] = "服务器时差调整 (单位: 分钟)";
$a_lang['mysql']['setting']['timeadjustdesc'] = "你可以通过这个选项更精确地调节服务器时间设置。如果服务器时间过快的话，请在输入的数字起始部分使用 \'-\' 号扣除相应时差。";
$a_lang['mysql']['setting']['standardtimeformat'] = "标准时间格式";
$a_lang['mysql']['setting']['standardtimeformatdesc'] = "查看更多参数: <a href=\'http://www.php.net/manual-lookup.php?function=date\' target=\'_blank\'>http://www.php.net/manual-lookup.php?function=date</a>";
$a_lang['mysql']['setting']['longtimeformat'] = "扩充的时间格式";
$a_lang['mysql']['setting']['longtimeformatdesc'] = "查看更多参数: <a href=\'http://www.php.net/manual-lookup.php?function=date\' target=\'_blank\'>http://www.php.net/manual-lookup.php?function=date</a>";
$a_lang['mysql']['setting']['registereddateformat'] = "注册日期格式";
$a_lang['mysql']['setting']['registereddateformatdesc'] = "查看更多参数: <a href=\'http://www.php.net/manual-lookup.php?function=date\' target=\'_blank\'>http://www.php.net/manual-lookup.php?function=date</a>";
$a_lang['mysql']['setting']['allowselectstyles'] = "用户可以选择风格？";
$a_lang['mysql']['setting']['signaturemaxlength'] = "用户在“个人签名”部分允许输入的最大字数";
$a_lang['mysql']['setting']['signatureallowhtml'] = "允许在签名中使用HTML代码？";
$a_lang['mysql']['setting']['signatureallowhtmldesc'] = "出于安全考虑，强烈建议你不要开启此选项。";
$a_lang['mysql']['setting']['signatureallowbbcode'] = "允许在签名中使用BBCODE代码？";
$a_lang['mysql']['setting']['allowuploadsigimg'] = "允许从本地上传图片到签名内？";
$a_lang['mysql']['setting']['sigimgdimension'] = "从本地上传图片的最大尺寸";
$a_lang['mysql']['setting']['sigimgdimensiondesc'] = "(宽度 <b>x</b> 高度)<br />超过此大小的图片将自动缩放";
$a_lang['mysql']['setting']['avatarsenabled'] = "允许用户使用头像？";
$a_lang['mysql']['setting']['avatarurl'] = "允许用户远程上传头像？";
$a_lang['mysql']['setting']['avatamaxsize'] = "可上传头像的最大字节数 (单位: KB)";
$a_lang['mysql']['setting']['avatardimension'] = "头像的尺寸";
$a_lang['mysql']['setting']['avatardimensiondesc'] = "(宽度 <b>x</b> 高度, 允许3个大小用 | 隔开)";
$a_lang['mysql']['setting']['maxpostchars'] = "每个帖子最大的字数";
$a_lang['mysql']['setting']['minpostchars'] = "每个帖子最小的字数";
$a_lang['mysql']['setting']['smilenums'] = "在表情符号表单中显示的表情符号个数";
$a_lang['mysql']['setting']['smilenumsdesc'] = "这个表单显示在在发帖页面中。";
$a_lang['mysql']['setting']['stripquotes'] = "去除嵌入的引用信息？";
$a_lang['mysql']['setting']['stripquotesdesc'] = "将删除所有引用帖子中带有引用标签的内容。";
$a_lang['mysql']['setting']['imageextension'] = "合法的帖图扩展名";
$a_lang['mysql']['setting']['imageextensiondesc'] = "定义在帖图标签 [img][/img] 可以使用的扩展名。\n<br />请用逗号分开每个名称 (gif,jpeg,jpg)";
$a_lang['mysql']['setting']['guesttag'] = "游客标记符号";
$a_lang['mysql']['setting']['guesttagdesc'] = "当游客发帖的时候，在这里设置用于游客的标示名称，以防止同注册用户名称混淆";
$a_lang['mysql']['setting']['enablepolltags'] = "在投票中允许使用 [IMG] 和 [URL] 标签？";
$a_lang['mysql']['setting']['maxpolloptions'] = "每个投票允许使用的最大结果数量";
$a_lang['mysql']['setting']['disablenoreplypoll'] = "禁止会员发表 “仅为投票的主题” ？";
$a_lang['mysql']['setting']['floodchecktime'] = "防灌水控制 (单位: 秒)";
$a_lang['mysql']['setting']['floodchecktimedesc'] = "用户在多少秒之后才可以再次发帖<br />留空将禁用灌水控制。";
$a_lang['mysql']['setting']['watermark'] = "开启图片水印功能";
$a_lang['mysql']['setting']['watermarkdesc'] = "是否在发帖时使用上传附件水印功能。如果images根目录下有 watermark.png 图片的话，将优先使用图片水印。";
$a_lang['mysql']['setting']['watermarkextra'] = "1=左上\n2=左下\n3=右上\n4=右下\n5=中间";
$a_lang['mysql']['setting']['markposition'] = "水印图片出现的位置";
$a_lang['mysql']['setting']['markpositiondesc'] = "如果开启了上面的水印功能，并且使用图片水印的话，请在这里设定图片水印出现的位置。";
$a_lang['mysql']['setting']['useantispam'] = "使用验证码";
$a_lang['mysql']['setting']['useantispamdesc'] = "用户在发帖时必须填写验证码后才能正常发布信息，可以防止一些恶意灌水机的使用。";
$a_lang['mysql']['setting']['mxemode'] = "编辑器默认初始化模式";
$a_lang['mysql']['setting']['mxemodedesc'] = "如果用户第一次登录，或Cookies未设定编辑器的时候，系统分配的默认编辑器为BBCODE模式还是WYSIWYG模式";
$a_lang['mysql']['setting']['matchbrowser'] = "验证用户浏览器？";
$a_lang['mysql']['setting']['matchbrowserdesc'] = "这个功能可以防止一些其他通过 Session 进行尝试的恶意操作，但同一用户在同一时间内将无法通过其它浏览器登录论坛。";
$a_lang['mysql']['setting']['allowdynimg'] = "允许在[IMG]标签内使用动态URL链接？";
$a_lang['mysql']['setting']['allowdynimgdesc'] = "将此选项设置为“否”，如果图像路径包含动态URL地址(例如 ? 和 & 符号)时，[IMG]标签将不做使用。这样可以防止用户使用一些恶意[IMG]标签代码。";
$a_lang['mysql']['setting']['allowimages'] = "允许使用[IMG]贴图代码？";
$a_lang['mysql']['setting']['allowimagesdesc'] = "如果设置此选项为“否”的话，用户的贴图将以链接形式显示。";
$a_lang['mysql']['setting']['forcelogin'] = "强制用户登录后才能访问论坛？";
$a_lang['mysql']['setting']['forcelogindesc'] = "如果此选项设置为“是”的话，游客只有登录后才可以访问论坛。";
$a_lang['mysql']['setting']['WOLenable'] = "允许用户查看看谁在线列表？";
$a_lang['mysql']['setting']['enablesearches'] = "用户可以使用搜索功能？";
$a_lang['mysql']['setting']['minsearchlength'] = "搜索关键词的最小长度";
$a_lang['mysql']['setting']['postsearchlength'] = "帖子搜索显示页面将过长的帖子自动裁切所保留的字符数";
$a_lang['mysql']['setting']['postsearchlengthdesc'] = "任何超过此数值的搜索结果都将被自动裁切。<br />留空将显示全部帖子内容";
$a_lang['mysql']['setting']['forumindex'] = "论坛首页名称";
$a_lang['mysql']['setting']['forumindexdesc'] = "你可以在这里设置论坛首页的名称，请注意，如果更改了此设置，请在FTP中更改为对应的文件名称。";
$a_lang['mysql']['setting']['showloggedin'] = "在首页显示在线会员？";
$a_lang['mysql']['setting']['showstatus'] = "在首页显示论坛统计？";
$a_lang['mysql']['setting']['showbirthday'] = "在论坛首页显示当天过生日的会员？";
$a_lang['mysql']['setting']['forum_active_user'] = "显示版面活跃会员数";
$a_lang['mysql']['setting']['forum_active_userdesc'] = "版面内显示活跃会员数";
$a_lang['mysql']['setting']['commend_thread_num'] = "版面推荐主题数目限制";
$a_lang['mysql']['setting']['commend_thread_numdesc'] = "每个版面允许推荐主题的数目";
$a_lang['mysql']['setting']['showbirthdaydesc'] = "设置为“是”的话，将在论坛首页显示当天过生日的会员列表。";
$a_lang['mysql']['setting']['maxonlineusers'] = "论坛列表状态显示的最大人数";
$a_lang['mysql']['setting']['maxonlineusersdesc'] = "如果论坛在线人数超过了此设定，将自动关闭在线列表详细资料显示。";
$a_lang['mysql']['setting']['showguest'] = "是否在在线列表内显示游客";
$a_lang['mysql']['setting']['showguestdesc'] = "如果开启了这个选项，那么在在线列表内将一并显示游客列表";
$a_lang['mysql']['setting']['birthday_send'] = "是否对当日生日用户发送祝贺信？";
$a_lang['mysql']['setting']['birthday_senddesc'] = "请在后边的对话框内填写生日祝贺信，此功能对数据库负荷有一定影响，因此不建议10万用户以上论坛使用。<br />在文字内容中可以使用以下标签:<br />{name}:用户名; {money=xxx}:金钱; {reputation=xxx}:声望; <br />如果使用了自定义积分策略，请使用 {唯一标签=xxx} 的方式添加内容。<br />其中xxx是你希望给予的货币价值";
$a_lang['mysql']['setting']['birthday_send_type'] = "祝贺信发送方式";
$a_lang['mysql']['setting']['birthday_send_typedesc'] = "使用哪种方式发送用户生日祝贺信";
$a_lang['mysql']['setting']['perpagepost'] = "在主题查看页面用户可选择每页显示的帖子数量";
$a_lang['mysql']['setting']['perpagepostdesc'] = "请使用逗号分开每个数值。<br />例如: 5,15,20,25,30";
$a_lang['mysql']['setting']['maxposts'] = "主题查看页面每页显示的帖子数";
$a_lang['mysql']['setting']['viewattachedimages'] = "在帖子中直接显示上传的图片？";
$a_lang['mysql']['setting']['viewattachedimagesdesc'] = "如果设置为“是”的话，所有上传的图片都将在帖子中直接显示。";
$a_lang['mysql']['setting']['viewattachedthumbs'] = "显示附件图片缩略图？";
$a_lang['mysql']['setting']['viewattachedthumbsdesc'] = "如果上面的选项开启并选择此选项为“是”的话，附件图片将以缩略图方式显示在帖子内，这样做可以有效减少页面输出带宽。";
$a_lang['mysql']['setting']['thumbswidth'] = "附件图片缩略图大小 [宽度]";
$a_lang['mysql']['setting']['thumbswidthdesc'] = "如果上面的选项开启，你可以在这里定义缩略图的最大宽度。如果上传的图片小于此宽度的话，将不以缩略图方式显示。";
$a_lang['mysql']['setting']['thumbsheight'] = "附件图片缩略图大小 [高度]";
$a_lang['mysql']['setting']['thumbsheightdesc'] = "如果上面两个选项都开启的话，你可以在这里定义缩略图的最大高度。如果上传的图片小于此宽度的话，将不以缩略图方式显示。";
$a_lang['mysql']['setting']['allowviewresults'] = "允许用户直接查看投票结果？";
$a_lang['mysql']['setting']['allowviewresultsdesc'] = "用户可以在投票前查看当前投票的结果数量";
$a_lang['mysql']['setting']['onlyonesignatures'] = "每个主题页面仅显示一次会员签名？";
$a_lang['mysql']['setting']['onlyonesignaturesdesc'] = "如果开启了这个选项，则在同一个主题内，如果会员使用了签名，并且发表了多个帖子的话，则每个页面仅显示一次签名。";
$a_lang['mysql']['setting']['maxthreads'] = "每页显示的主题数量";
$a_lang['mysql']['setting']['hotnumberposts'] = "作为 “热门主题” 的最低发帖数";
$a_lang['mysql']['setting']['showforumusers'] = "显示 “正在浏览此论坛的会员” ？";
$a_lang['mysql']['setting']['showforumusersdesc'] = "如果开启此项，将增加一次数据库查询";
$a_lang['mysql']['setting']['perpagethread'] = "在论坛列表页面用户可选择每页显示的主题数量";
$a_lang['mysql']['setting']['perpagethreaddesc'] = "请使用逗号分开每个数值。<br />例如: 5,15,20,25,30";
$a_lang['mysql']['setting']['showsubforums'] = "显示子论坛链接？";
$a_lang['mysql']['setting']['showsubforumsdesc'] = "是否在论坛列表内显示子论坛链接";
$a_lang['mysql']['setting']['threadpreview'] = "主题内容预览方式";
$a_lang['mysql']['setting']['threadpreviewdesc'] = "是否在鼠标移动到主题链接的时候查看主题内的帖子信息？不建议用在访问量很高的网站";
$a_lang['mysql']['setting']['pmallowbbcode'] = "允许在短消息中使用 BBCODE 代码？";
$a_lang['mysql']['setting']['pmallowhtml'] = "允许在短消息中使用 HTML 代码？";
$a_lang['mysql']['setting']['pmallowhtmldesc'] = "如果设置为“是”的话，用户可以在短消息中使用 HTML 代码。出于安全考虑，强烈建议你不要开启此选项。";
$a_lang['mysql']['setting']['emailreceived'] = "用于接收信件的邮箱地址";
$a_lang['mysql']['setting']['emailreceiveddesc'] = "此邮箱地址将出现在所有论坛邮件链接中。";
$a_lang['mysql']['setting']['emailsend'] = "用于发送信件的邮箱地址";
$a_lang['mysql']['setting']['emailsenddesc'] = "将使用此邮箱发送所有论坛邮件。";
$a_lang['mysql']['setting']['sesureemail'] = "使用安全表单发送自论坛发出的邮件？";
$a_lang['mysql']['setting']['sesureemaildesc'] = "如果这个选项设置为“是”，那么用户必须使用在线填写表单来给其他用户发送邮件，可以防止恶意收集会员邮件的程序。";
$a_lang['mysql']['setting']['emailtype'] = "邮件发送方式";
$a_lang['mysql']['setting']['emailtypedesc'] = "如果 PHP 的 mail() 函数无法使用的话，可以选择一个不使用用户认证的邮局。如果你不清楚服务器的配置，请与服务商联系。";
$a_lang['mysql']['setting']['smtphost'] = "SMTP 主机名称";
$a_lang['mysql']['setting']['smtphostdesc'] = "默认值为 \'localhost\'";
$a_lang['mysql']['setting']['smtpport'] = "SMTP 端口";
$a_lang['mysql']['setting']['smtpportdesc'] = "默认值为 25";
$a_lang['mysql']['setting']['smtpuser'] = "SMTP 用户名";
$a_lang['mysql']['setting']['smtpuserdesc'] = "在多数情况下，使用 \'localhost\' 不需添加此参数。";
$a_lang['mysql']['setting']['smtppassword'] = "SMTP 密码";
$a_lang['mysql']['setting']['smtppassworddesc'] = "在多数情况下，使用 \'localhost\' 不需添加此参数。";
$a_lang['mysql']['setting']['emailwrapbracket'] = "在地址 \'from\' 和 \'to\' 中添加 \'&lt;\' 和 \'&gt;\' ？";
$a_lang['mysql']['setting']['emailwrapbracketdesc'] = "部分 SMTP 系统需要在邮件地址中添加 \'<\' address \'>\' (不带引号) 的参数才能正常工作。如果你在发送邮件的过程中出现了错误的话，请开启这个选项。";
$a_lang['mysql']['setting']['disablereport'] = "禁用 “向版主报告此帖” 的功能？";
$a_lang['mysql']['setting']['disablereportdesc'] = "禁止用户向版主反映有问题的帖子。";
$a_lang['mysql']['setting']['reporttype'] = "向版主报告帖子的方式";
$a_lang['mysql']['setting']['enablerecyclebin'] = "启用回收站功能？";
$a_lang['mysql']['setting']['enablerecyclebindesc'] = "如果设置为“否”的话，此功能及下面的设置将不起作用。";
$a_lang['mysql']['setting']['recycleforumid'] = "指定作为回收站使用的论坛";
$a_lang['mysql']['setting']['recycleforumiddesc'] = "请将所设置的论坛关闭相应读取权限。";
$a_lang['mysql']['setting']['recycleforadmin'] = "对管理员使用回收站功能？";
$a_lang['mysql']['setting']['recycleforadmindesc'] = "如果设置为“否”的话，将在管理中直接删除帖子和主题。";
$a_lang['mysql']['setting']['recycleforsuper'] = "对超级版主使用回收站功能？";
$a_lang['mysql']['setting']['recycleforsuperdesc'] = "如果设置为“否”的话，将在管理中直接删除帖子和主题。";
$a_lang['mysql']['setting']['recycleformod'] = "对分论坛版主使用回收站功能？";
$a_lang['mysql']['setting']['recycleformoddesc'] = "如果设置为“否”的话，将在管理中直接删除帖子和主题。";
$a_lang['mysql']['setting']['allowregistration'] = "允许新用户注册？";
$a_lang['mysql']['setting']['allowregistrationdesc'] = "如果此选项设置为“否”的话，任何尝试注册的行为均会被告知此时论坛已不接受新用户注册。";
$a_lang['mysql']['setting']['enableantispam'] = "开启恶意注册控制系统";
$a_lang['mysql']['setting']['enableantispamdesc'] = "为了防止某些恶意操作，开启此选项将在用户注册时强制用户输入验证码。";
$a_lang['mysql']['setting']['moderatememberstype'] = "用户注册的验证方式";
$a_lang['mysql']['setting']['moderatememberstypedesc'] = "通过管理员验证新用户注册的信息或者验证用户注册的邮箱地址。";
$a_lang['mysql']['setting']['removemoderate'] = "自动删除 x 天前未验证的注册信息";
$a_lang['mysql']['setting']['removemoderatedesc'] = "将自动删除你所指定的天数外仍未激活的用户信息。输入 0 将不删除未激活的帐号。";
$a_lang['mysql']['setting']['registerrule'] = "注册声明";
$a_lang['mysql']['setting']['newuser_pm'] = "新注册用户的短消息欢迎信息";
$a_lang['mysql']['setting']['newuser_pmdesc'] = "可以使用HTML代码及BBCODE代码";
$a_lang['mysql']['setting']['reg_ip_time'] = "同IP用户注册时限限制";
$a_lang['mysql']['setting']['reg_ip_timedesc'] = "在设定时间内，同IP需间隔以下时间才可以重复注册;设置为0不限制。（单位: 分钟）";
$a_lang['mysql']['setting']['showprivacy'] = "显示隐私声明的链接？";
$a_lang['mysql']['setting']['privacyurl'] = "隐私声明调用的 http:// 链接地址";
$a_lang['mysql']['setting']['privacyurldesc'] = "如果你有单独的隐私声明页面的话，请在这里输入隐私声明的URL地址。";
$a_lang['mysql']['setting']['privacytitle'] = "隐私声明的标题";
$a_lang['mysql']['setting']['privacytext'] = "如果未使用上面的链接地址的话，请在这里输入相关隐私声明的文字。";
$a_lang['mysql']['setting']['privacytextdesc'] = "可以使用 HTML 代码";
$a_lang['mysql']['setting']['bbactive'] = "论坛开放状态？";
$a_lang['mysql']['setting']['bbactivedesc'] = "如果选择“否”的话，将关闭论坛的开放状态，仅对有权限的用户开放浏览。";
$a_lang['mysql']['setting']['bbclosedreason'] = "论坛关闭后显示的信息";
$a_lang['mysql']['setting']['exchangeexcost'] = "兑换手续费";
$a_lang['mysql']['setting']['exchangeexcostdesc'] = "用户兑换积分收取的手续费。计算单位：‰";
$a_lang['mysql']['setting']['exchangelimit'] = "兑换数额限制";
$a_lang['mysql']['setting']['exchangelimitdesc'] = "用户一次性可兑换积分的数额范围,请用 | 将上下限分开例如:1|2000";
$a_lang['mysql']['setting']['exchangeexcostlimit'] = "兑换手续费限制";
$a_lang['mysql']['setting']['exchangeexcostlimitdesc'] = "通过比率计算出的手续费若大于限制上限则默认上限,若小于限制下限则默认下限,请用 | 将上下限分开例如:1|100";
$a_lang['mysql']['setting']['version'] = "";
$a_lang['mysql']['setting']['spider_roup'] = "对搜索引擎Spider使用的用户组";
$a_lang['mysql']['setting']['spider_roupdesc'] = "对于不开放游客访问的论坛而言，设定搜索引擎蜘蛛访问时的用户组，可以更方便的让搜索引擎索引论坛资源";
$a_lang['mysql']['setting']['spiderid'] = "搜索引擎BOT设定";
$a_lang['mysql']['setting']['spideriddesc'] = "请使用 | 分隔开每个BOT参数";
$a_lang['mysql']['setting']['mxemodeextra'] = "0=标准编辑器（bbcode）\r\n1=所见即所得编辑器";
$a_lang['mysql']['setting']['birthday_send_typeextra'] = "1=站内邮件和短消息\r\n2=短消息\r\n3=站内邮件";
$a_lang['mysql']['setting']['threadpreviewextra'] = "0=不使用主题预览\r\n1=查看主题内容信息\r\n2=查看最后回帖信息";
$a_lang['mysql']['setting']['reporttypeextra'] = "email=邮件方式\r\npm=短消息";
$a_lang['mysql']['setting']['enableantispamextra'] = "0=关闭\r\ngd=高级方式 (需要 GD 库支持)\r\ngif=标准方式 (无特别要求)";
$a_lang['mysql']['setting']['moderatememberstypextra'] = "user=验证用户的邮件\r\nadmin=管理员验证帐号\r\n0=不需要验证";
$a_lang['mysql']['setting']['registerruledval'] = "注册声明\r\n\r\n在这个论坛注册是完全免费的！我们希望您遵守以下的论坛规定。如果你接受这个条件，请选中“我同意”对话框并点击下面的“注册”按钮。如果你不想继续注册，点击这里返回论坛首页。\r\n\r\n虽然{bbtitle}管理员和版主已经尽力避免所有令人讨厌的信息在此论坛出现，但仍无法顾及到论坛会员所发表的全部信息。所有信息均代表作者的观点，{bbtitle}不对任何信息内容所引发的争议承担责任。\r\n\r\n点击同意按钮，就表示你保证不会发布任何有关政治、色情、宗教、迷信等违法信息。\r\n\r\n{bbtitle}的管理人员有权在任何时刻移动、编辑、删除、关闭任何主题。\r\n";
$a_lang['mysql']['setting']['bbclosedreasondval'] = "论坛调整中...";
$a_lang['mysql']['setting']['modreptypextra'] = "1=积分\r\n2=社区币";
$a_lang['mysql']['setting']['adcolumns'] = "页面广告平排的列数";
$a_lang['mysql']['setting']['adcolumnsdesc'] = "超过设定值则自动换行";
$a_lang['mysql']['setting']['adinpost'] = "贴内广告每页显示的条数";
$a_lang['mysql']['setting']['adinpostdesc'] = "设定主题每页内显示的广告条数，超过设定条数的帖子将不显示广告，设置为 0 将在每个帖子内显示广告";
$a_lang['mysql']['setting']['userdolenlimit'] = "用户状态长度限制";
$a_lang['mysql']['settinggroup']['generalsetting'] = "常规设置";
$a_lang['mysql']['settinggroup']['generalsettingdesc'] = "有关论坛的访问信息、联系方式、论坛基本配置等常规信息。";
$a_lang['mysql']['settinggroup']['forumoptimize'] = "论坛优化、节能处理";
$a_lang['mysql']['settinggroup']['forumoptimizedesc'] = "调整论坛性能等相关参数。";
$a_lang['mysql']['settinggroup']['sitenameurl'] = "站点名称 / URL地址 / 联系信息";
$a_lang['mysql']['settinggroup']['sitenameurldesc'] = "在这里设置网站的基本信息。";
$a_lang['mysql']['settinggroup']['cookieoption'] = "Cookies 和 HTTP header 输出选项";
$a_lang['mysql']['settinggroup']['cookieoptiondesc'] = "调整Cookies的相关参数及服务器页面输出设置。";
$a_lang['mysql']['settinggroup']['datetimeoption'] = "时间和日期选项";
$a_lang['mysql']['settinggroup']['datetimeoptiondesc'] = "论坛使用的时间及日期参数控制。";
$a_lang['mysql']['settinggroup']['userpara'] = "用户参数";
$a_lang['mysql']['settinggroup']['userparadesc'] = "在这里调整用户的权限及其它参数。";
$a_lang['mysql']['settinggroup']['postoption'] = "发帖选项";
$a_lang['mysql']['settinggroup']['postoptiondesc'] = "在这里控制发帖、阅读主题及投票的参数。";
$a_lang['mysql']['settinggroup']['securityctrl'] = "安全 &amp; 隐私控制";
$a_lang['mysql']['settinggroup']['securityctrldesc'] = "在这里调整论坛的安全及隐私的相关参数。";
$a_lang['mysql']['settinggroup']['searchoption'] = "搜索选项";
$a_lang['mysql']['settinggroup']['indexsetting'] = "论坛首页设定";
$a_lang['mysql']['settinggroup']['indexsettingdesc'] = "在这里指定用于显示论坛新闻的论坛ID";
$a_lang['mysql']['settinggroup']['showthread'] = "主题显示选项[showthread.php]";
$a_lang['mysql']['settinggroup']['forumdisplay'] = "论坛显示选项[forumdisplay.php]";
$a_lang['mysql']['settinggroup']['emailpmsetting'] = "邮件 &amp; 短消息设置";
$a_lang['mysql']['settinggroup']['emailpmsettingdesc'] = "在这里设定短消息和发送邮件的相关参数。";
$a_lang['mysql']['settinggroup']['recyclesetting'] = "回收站设定";
$a_lang['mysql']['settinggroup']['recyclesettingdesc'] = "在这里指定用于将待删除主题所移动的论坛ID位置。";
$a_lang['mysql']['settinggroup']['userregoption'] = "用户注册选项";
$a_lang['mysql']['settinggroup']['privacyance'] = "隐私声明";
$a_lang['mysql']['settinggroup']['privacyancedesc'] = "你可以在这里设置论坛隐私声明部分。如果开启此设置，将会在页面出现隐私声明链接。";
$a_lang['mysql']['settinggroup']['opencloseforum'] = "开放或关闭论坛";
$a_lang['mysql']['settinggroup']['opencloseforumdesc'] = "在这里设定论坛关闭/开启状态。";
$a_lang['mysql']['settinggroup']['olranksetting'] = "在线时长等级设置";
$a_lang['mysql']['settinggroup']['olranksettingdesc'] = "统计用户在线时间长短，同时进行相应的等级判定。这里是一些相关的设置。";
$a_lang['mysql']['settinggroup']['invitesetting'] = "邀请注册条件设定";
$a_lang['mysql']['settinggroup']['invitesettingdesc'] = "设定用户具有邀请注册权限的条件。";
$a_lang['mysql']['settinggroup']['searchenginesetting'] = "搜索引擎设定";
$a_lang['mysql']['settinggroup']['searchenginesettingdesc'] = "在这里设定搜索引擎 Spider 相关参数";
$a_lang['mysql']['settinggroup']['adsetting'] = "广告中心设置";
$a_lang['mysql']['settinggroup']['adsettingdesc'] = "在这里设定广告中心的相关参数";

$a_lang['mysql']['usertitle']['newuser'] = "新手上路";
$a_lang['mysql']['usertitle']['mediatemember'] = "中级会员";
$a_lang['mysql']['usertitle']['highermember'] = "高级会员";
// add 2.6.0
$a_lang['mysql']['setting']['usernameminlength'] = '用户名最小字数';
$a_lang['mysql']['setting']['usernameminlengthdesc'] = '注册时输入的用户名必须达到的最小长度';
$a_lang['mysql']['setting']['usernamemaxlength'] = '用户名最大字数';
$a_lang['mysql']['setting']['usernamemaxlengthdesc'] = '注册时输入的用户名的最大长度（不要超过20）';
$a_lang['mysql']['setting']['quickeditorloadmode'] = '快速回复编辑器载入模式';
$a_lang['mysql']['setting']['quickeditorloadmodedesc'] = '快速回复编辑器以何种方式载入。';
$a_lang['mysql']['setting']['quickeditorloadmodeextra'] = "1=直接载入\r\n2=点击后载入";
$a_lang['mysql']['setting']['quickeditordisplaymenu'] = '快速回复编辑器菜单';
$a_lang['mysql']['setting']['quickeditordisplaymenuextra'] = "0=完整菜单\r\n1=简单菜单\r\n2=不显示";
$a_lang['mysql']['setting']['quickeditordisplaymenudesc'] = '快速回复编辑器上排操作按钮显示样式。当选择“完整菜单”时编辑器载入速度最慢，“不显示”载入速度最快。';
$a_lang['install']['selectlanguage'] = '请选择语言';
$a_lang['mysql']['setting']['guest'] = '未注册用户';
$a_lang['install']['welcome'] = '欢迎使用 MolyX Board 安装脚本!';
// add 2.6.1
$a_lang['mysql']['setting']['rewritestatus'] = 'URL 静态化';
$a_lang['mysql']['setting']['rewritestatusdesc'] = '选择“是”将对常用页面进行 URL 静态化转换，以提高搜索引擎收录，本功能需要 Web 服务器打开 URLRewrite 功能，因此需要服务器权限才可使用，相应规则请参考<a href="http://www.molyx.cn/index.php/%E8%AF%A6%E7%BB%86%E5%B8%B8%E8%A7%84%E8%AE%BE%E7%BD%AE#.E6.90.9C.E7.B4.A2.E5.BC.95.E6.93.8E.E8.AE.BE.E5.AE.9A" target="_blank">《用户使用说明书》</a>。注意: 当访问量很大时，本功能会轻微加重服务器负担。';
$a_lang['mysql']['setting']['threadviewsdelay'] = '主题查看数量延迟更新';
$a_lang['mysql']['setting']['threadviewsdelaydesc'] = '选择“是”，将每过一个小时（实际时间以计划任务“更新主题查看数量”的设置为准）更新一次主题查看数量。<br />如果您的论坛访问量很大，建议开启本功能以降低服务器负担。';
$a_lang['mysql']['setting']['attachmentviewsdelay'] = '附件查看数量延迟更新';
$a_lang['mysql']['setting']['attachmentviewsdelaydesc'] = '选择“是”，将每过一个小时（实际时间以计划任务“更新附件查看数量”的设置为准）更新一次附件查看数量。<br />如果您的论坛有很多的附件，特别是插入帖子中的图片附件，建议开启本功能以降低服务器负担。';
$a_lang['mysql']['cron']['threadviews'] = '更新主题查看数量';
$a_lang['mysql']['cron']['threadviewsdesc'] = '每小时更新主题查看数量，打开“主题查看数量延迟更新”后才会执行';
$a_lang['mysql']['cron']['attachmentviews'] = '更新附件查看数量';
$a_lang['mysql']['cron']['attachmentviewsdesc'] = '每小时更新附件查看数量，打开“附件查看数量延迟更新”后才会执行';
$a_lang['mysql']['cron']['forum_active_user'] = '更新版面活跃会员';
$a_lang['mysql']['cron']['forum_active_userdesc'] = '更新版面活跃会员';
$a_lang['mysql']['cron']['top_digg_thread'] = '更新首页推荐主题';
$a_lang['mysql']['cron']['top_digg_threaddesc'] = '更新首页推荐主题';
$a_lang['mysql']['cron']['forum_active_user'] = '更新版面活跃会员';
$a_lang['mysql']['cron']['forum_active_userdesc'] = '更新版面活跃会员';
//add 2.7.0
$a_lang['mysql']['setting']['hideattach'] = '开启附件隐藏功能';
$a_lang['mysql']['setting']['hideattachdesc'] = '开启附件隐藏功能';
$a_lang['mysql']['setting']['notallowenusreg'] = '注册名不允许英文字符？';
$a_lang['mysql']['setting']['notallownumreg'] = '注册名不允许数字字符？';
$a_lang['mysql']['setting']['notallowspereg'] = '注册名不允许特殊字符？';
$a_lang['mysql']['setting']['notallowsperegdesc'] = '请用"|"符号隔开';
$a_lang['mysql']['setting']['quoteslengthlimit'] = '引用的文字长度限制';
$a_lang['mysql']['setting']['quoteslengthlimitdesc'] = '被引用的文字部分截取的字符数';
$a_lang['install']['importlangs'] = '下一步将开始导入论坛语言，请确认在安装目录内存在 MolyX-language.xml 文件';
$a_lang['install']['langexists'] = '语言 MolyX-language.xml 文件存在，可以执行导入。';
$a_lang['install']['langnotexists'] = '语言 MolyX-language.xml 文件不存在，无法完成导入';
$a_lang['mysql']['cron']['cleanrecycle'] = '清空回收站';
$a_lang['mysql']['cron']['cleanrecycledesc'] = '清空回收站中的主题和帖子';

$a_lang['mysql']['setting']['spideronline'] = '搜索引擎计入在线列表';
$a_lang['mysql']['setting']['spideronlinedesc'] = '设置搜索引擎蜘蛛是否在在线列表中的记录形式。<br />不记录：不在在线列表中记录搜索引擎蜘蛛的信息，所以蜘蛛将不计入在线人数，效率最高。<br />为搜索引擎建立记录：每个搜索引擎当作一个用户，不管该搜索引擎使用了多少个蜘蛛进行抓取在线列表中只记录为一个。<br />为蜘蛛建立记录：每个访问论坛的蜘蛛都当作一个用户，一个搜索引擎会被记录为多个用户。';
$a_lang['mysql']['setting']['spideronlineextra'] = "0=不记录\r\n1=为搜索引擎建立记录\r\n2=为蜘蛛建立记录";
$a_lang['mysql']['creditevent']['upattach'] = '上传附件';
$a_lang['mysql']['creditevent']['downattach'] = '下载附件';
$a_lang['mysql']['creditevent']['sendpm'] = '发送单条短消息';
$a_lang['mysql']['creditevent']['sendgrouppm'] = '群发短消息';
$a_lang['mysql']['creditevent']['search'] = '执行搜索';
$a_lang['mysql']['creditevent']['register'] = '新用户注册';
$a_lang['mysql']['creditevent']['uploadavatar'] = '上传用户头像';
$a_lang['mysql']['creditevent']['addsignature'] = '添加用户签名';
$a_lang['mysql']['creditevent']['newthread'] = '发表主题';
$a_lang['mysql']['creditevent']['newpoll'] = '发表投票';
$a_lang['mysql']['creditevent']['replythread'] = '主题被回复';
$a_lang['mysql']['creditevent']['threadpoll'] = '主题被投票';
$a_lang['mysql']['creditevent']['delthread'] = '删除主题';
$a_lang['mysql']['creditevent']['quintessence'] = '设定精华主题';
$a_lang['mysql']['creditevent']['newreply'] = '发表回复';
$a_lang['mysql']['creditevent']['delreply'] = '删除帖子';
$a_lang['mysql']['creditevent']['replypoll'] = '为主题投票';
$a_lang['mysql']['creditevent']['hidepostmax'] = '隐藏帖最大值';
$a_lang['mysql']['creditevent']['hidepostmin'] = '隐藏帖最小值';
$a_lang['mysql']['creditevent']['paypostmax'] = '消费帖最大值';
$a_lang['mysql']['creditevent']['paypostmin'] = '消费帖最小值';
$a_lang['mysql']['creditevent']['evaluationmax'] = '评价最大值';
$a_lang['mysql']['creditevent']['evaluationmin'] = '评价最小值';
$a_lang['mysql']['creditevent']['evalthreadscore'] = '单主题最高获得评分值';
$a_lang['mysql']['creditevent']['editthread'] = '编辑主题';
$a_lang['mysql']['creditevent']['editpost'] = '编辑回复';
$a_lang['mysql']['creditevent']['editpoll'] = '编辑投票';
$a_lang['mysql']['creditevent']['threadhighlight'] = '主题高亮设置';
?>