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
class functions_showthread
{
	function parse_attachment($postids, $type = 'postid',$threadid = '', $posttable = '')
	{
		global $DB, $forums, $bbuserinfo, $bboptions;
		$final_attachment = $attachments_inpost = $return = array();
		require_once(ROOT_PATH . 'includes/xfunctions_hide.php');
		$this->hidefunc = new hidefunc();

		if (count($postids))
		{
			$sql_condition = $bbuserinfo['candownload'] ? ' AND inpost=0' : '';
			$sql_condition .= $posttable ? " AND posttable='" . $posttable ."'" : '';
			$attach = $DB->query("SELECT * FROM " . TABLE_PREFIX . "attachment WHERE $type IN (" . implode(",", $postids) . ")$sql_condition");

			while ($a = $DB->fetch_array($attach))
			{
				if ($a['inpost'] == 0)
				{
					$final_attachment[$a[$type]][$a['attachmentid']] = $a;
				}
				else
				{   
					$attachments_inpost[$a[$type]][$a['attachmentid']] = $a;
				}
			}
			
			$forums->func->check_cache('attachmenttype');

			foreach ($final_attachment AS $pid => $data)
			{
				$temp = '';
				$attachment = array();
			    $i = 0;
				
				foreach ($data AS $aid => $row)
				{
					$link_name = urlencode($row['filename']);
					$i++;
					
					if ($this->hidefunc->hide_attachment($row['userid'], $row["hidetype"], $threadid, $row['postid']))
					{
						if ($bboptions['viewattachedimages'] AND $row['image'] AND $bbuserinfo['candownload'])
						{
							if ($row['thumblocation'] AND $bboptions['viewattachedthumbs'])
							{
								if ($bboptions['remoteattach'])
								{
									$subpath = implode('/', preg_split('//', intval($row['userid']), -1, PREG_SPLIT_NO_EMPTY));
									$subpath = $bboptions['remoteattach'] . "/" . $subpath;
									$row['location'] = str_replace("\\", "/", $row['location']);
									$row['location'] = str_replace("/", "", substr($row['location'], strrpos($row['location'], '/')));
									$showfile = $subpath . "/" . $row['location'];
									$row['thumblocation'] = str_replace("\\", "/", $row['thumblocation']);
									$row['thumblocation'] = str_replace("/", "", substr($row['thumblocation'], strrpos($row['thumblocation'], '/')));
									$showthumb = $subpath . "/" . $row['thumblocation'];
								}
								else
								{
									$showfile = "attachment.php{$forums->sessionurl}id={$row['attachmentid']}&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['location']}&amp;filename={$link_name}&amp;attachpath={$row['attachpath']}";
									$showthumb = "attachment.php{$forums->sessionurl}do=showthumb&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['thumblocation']}&amp;attachpath={$row['attachpath']}";
								}
								if ($row['hidetype'] && $bboptions['hideattach'])
								{
									$hideinfo = $this->hidefunc->hideattachcondition(array(),$row['hidetype'],'',2);
									$hideinfo = join('<br />',$hideinfo);
									$temp['thumb'] .= "<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div><div class='hidemain'>";
									$temp['thumb'] .= "<a href='{$showfile}' title='{$row['filename']} -  " . $forums->lang['_filesize'] . fetch_number_format($row['filesize'], true) . ", " . $forums->lang['_clicknums'] . ": {$row['counter']}' target='_blank'><img src='{$showthumb}' width='{$row['thumbwidth']}' height='{$row['thumbheight']}' class='attach' alt='{$row['filename']} - " .$forums->lang['_filesize'] . fetch_number_format($row['filesize'], true) . ", " .$forums->lang['_clicknums'] . ": {$row['counter']} (" . $forums->lang['_largeviews'] . ")' /></a> </div></div>";
								}
								else
								{
									$temp['thumb'] .= "<a href='{$showfile}' title='{$row['filename']} -  " . $forums->lang['_filesize'] . fetch_number_format($row['filesize'], true) . ", " . $forums->lang['_clicknums'] . ": {$row['counter']}' target='_blank'><img src='{$showthumb}' width='{$row['thumbwidth']}' height='{$row['thumbheight']}' class='attach' alt='{$row['filename']} - " . $forums->lang['_filesize'] . fetch_number_format($row['filesize'], true) . ", " .$forums->lang['_clicknums'] . ": {$row['counter']} (" . $forums->lang['_largeviews'] . ")' /></a>";
								}						
							}
							else
							{
								if ($bboptions['remoteattach'])
								{
									$subpath = implode('/', preg_split('//', intval($row['userid']), -1, PREG_SPLIT_NO_EMPTY));
									$subpath = $bboptions['remoteattach'] . "/" . $subpath;
									$row['location'] = str_replace("\\", "/", $row['location']);
									$row['location'] = str_replace("/", "", substr($row['location'], strrpos($row['location'], '/')));
									$showfile = $subpath . "/" . $row['location'];
								}
								else
								{
									$showfile = "attachment.php{$forums->sessionurl}id={$row['attachmentid']}&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['location']}&amp;filename={$link_name}&amp;attachpath={$row['attachpath']}";
								}
								if ($row['hidetype'] && $bboptions['hideattach'])
								{
									$hideinfo = $this->hidefunc->hideattachcondition(array(),$row['hidetype'],'',2);
									$hideinfo = join('<br />',$hideinfo);
									$temp['image'] .="<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div><div class='hidemain'>";
									$temp['image'] .= "<img src='{$showfile}' alt='" . $forums->lang['_uploadimages'] . "' onload='javascript:if(this.width>screen.width-500)this.style.width=screen.width-500;' onclick='javascript:window.open(this.src);' style='CURSOR: pointer' /> 
									</div></div>";
								}
								else
								{	
									$temp['image'] .= "<img src='{$showfile}' alt='" . $forums->lang['_uploadimages'] . "' onload='javascript:if(this.width>screen.width-500)this.style.width=screen.width-500;' onclick='javascript:window.open(this.src);' style='CURSOR: pointer' />  </div>";
								}																
							}
						}
						else
						{		
							if ($row['hidetype']&&$bboptions['hideattach'])
							{
								$hideinfo = $this->hidefunc->hideattachcondition(array(),$row['hidetype'],'',2);
							    $hideinfo = join('<br />',$hideinfo);
								$temp['attach'] .= "<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div><div class='hidemain'>";
								$temp['attach'] .= "<img src='images/{$forums->cache['attachmenttype'][ $row['extension'] ]['attachimg']}' border='0' alt='" . $forums->lang['_uploadattachs'] . "' />&nbsp;<a href='attachment.php{$forums->sessionurl}id={$row['attachmentid']}&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['location']}&amp;filename={$link_name}&amp;attachpath={$row['attachpath']}' title='' class='edit' target='_blank'>{$row['filename']}</a><span class='edit'>( " . $forums->lang['_filesize'] . ": " . fetch_number_format($row['filesize'], 1) . " " . $forums->lang['_clicknums'] . ": {$row['counter']} )</span></div></div>";
							}
							else
							{
								$temp['attach'] .= "<br /><img src='images/{$forums->cache['attachmenttype'][ $row['extension'] ]['attachimg']}' border='0' alt='" . $forums->lang['_uploadattachs'] . "' />&nbsp;<a href='attachment.php{$forums->sessionurl}id={$row['attachmentid']}&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['location']}&amp;filename={$link_name}&amp;attachpath={$row['attachpath']}' title='' class='edit' target='_blank'>{$row['filename']}</a><span class='edit'>( " . $forums->lang['_filesize'] . ": " . fetch_number_format($row['filesize'], 1) . " " . $forums->lang['_clicknums'] . ": {$row['counter']} )</span><br />";
							}						
						}
					}
					else
					{  
						$hideinfo = $this->hidefunc->hideattachcondition(array(),$row['hidetype'],'',2);
	                    $hideinfo = join('<br />',$hideinfo);
						if ($bboptions['viewattachedimages'] AND $row['image'] AND $bbuserinfo['candownload'])
						{
							if ($bboptions['remoteattach'])
							{
								$temp['thumb'] .="<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadabouthidden']."</div><div class='hidemain'>".$forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div></div>";
							}
							else
							{ 
								$temp['image'] .="<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadabouthidden']."</div><div class='hidemain'>".$forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div></div>";
							}		
						}
						else
						{
							$temp['attach'] .="<div class='postcontent'><div class='hidetop'>" . $forums->lang['_uploadabouthidden']." </div><div class='hidemain'>".$forums->lang['_uploadhidviewcondition'].'<br />'.$hideinfo."</div></div>";
						}
					}
				}
				if ($temp['thumb'])
				{   
					$attachment[$pid] .= "<strong>" . $forums->lang['_uploadthumbs'] . ":</strong></span><br />" . $temp['thumb'] . "<br />";
				}
				if ($temp['image'])
				{
					$attachment[$pid] .= "<strong>" . $forums->lang['_uploadimages'] . ":</strong></span><br />" . $temp['image'];
				}
				if ($temp['attach'])
				{
					$attachment[$pid] .= "<strong>" . $forums->lang['_uploadattachs'] . ":</strong></span><br />" . $temp['attach'];
				}	
				$aids[$pid] .= $attachment[$pid];
			}
			$return['attachments'] = $aids;
			if ($attachments_inpost)
			{
				$attachment = array();
				foreach ($attachments_inpost as $pid => $data)
				{
					foreach ($data as $aid => $row)
					{
						$link_name = urlencode($row['filename']);
						$attachment[$pid][$aid] = "<br /><img src='images/{$forums->cache['attachmenttype'][ $row['extension'] ]['attachimg']}' border='0' alt='" . $forums->lang['_uploadattachs'] . "' />&nbsp;<a href='attachment.php{$forums->sessionurl}id={$row['attachmentid']}&amp;u={$row['userid']}&amp;extension={$row['extension']}&amp;attach={$row['location']}&amp;filename={$link_name}&amp;attachpath={$row['attachpath']}' title='' class='edit' target='_blank'>{$row['filename']}</a><span class='edit'>( " . $forums->lang['_filesize'] . ": " . fetch_number_format($row['filesize'], 1) . " " .	$forums->lang['_clicknums'] . ": {$row['counter']} )</span><br />";
					}
				}
				$return['attachments_inpost'] = $attachment;
			}
		}
		return $return;
	}

	function paste_emule($text = '')
	{
		global $forums;
		$stamp = substr(md5(rand (0, 15) . microtime()), 0, 16);
		$text = preg_replace(array('#{emule_(\S+?)}#isU', '#{size_(.*)}#isU'), array("<input type='checkbox' name='$stamp' value='\\1' onclick=\"em_size('$stamp');\" checked='checked' />", "size_" . $stamp . ""), $text);
		$text .= "<cite>{$this_file_size}</cite><input type=\"checkbox\" id=\"checkall_$stamp\" onclick=\"checkAll('$stamp',this.checked)\" checked=\"checked\"/> <input type=\"button\" value=\"{$forums->lang['download_link']}\" onclick=\"download('$stamp',0,1)\" class=\"button_normal\"> <input type=\"button\" value=\"{$forums->lang['copy_link']}\" onclick=\"copy('$stamp')\" class=\"button_normal\"><div id=\"ed2kcopy_$stamp\" style=\"position:absolute;height:0px;width:0px;overflow:hidden;\"></div>\r\n";
		$text .= "<cite><a href='http://www.emule.org.cn/download/' target='_blank'>{$forums->lang['emule_down']}</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href='http://www.emule.org.cn/guide/' target='_blank'>{$forums->lang['emule_tech']}</a></cite>\r\n";
		return $text;
	}

	function paste_attachment($aid = '', $text)
	{
		return '<!--attachid::' . $aid . '-->' . $text . '<!--attachid-->';
	}
}
