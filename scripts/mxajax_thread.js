// $Id$
/**
* 开放关闭主题
*/
function open_close_thread(tid, puid, fid)
{
	xajax.setDo('thread');
	if (isNaN(parseInt(tid)))
	{
		var openclose = (tid == 'open') ? 1 : 0;
		mxajax_open_close_thread(xajax.getFormValues('mxbform'), 0, 0, openclose);
	}
	else
	{
		mxajax_open_close_thread(tid, puid, fid);
	}
}

/**
* 修改主题标题
*/
function showthreadin(tid)
{
	if ($("show" + tid)) var oldTN = $("show" + tid);
	else return false;
	var oldTNP = oldTN.parentNode.parentNode;
	for (var i = 0; i < 2; i++)
		if (oldTN.childNodes[0].tagName) oldTN = oldTN.childNodes[0];
	var oldHTML = document.createElement('input');
	oldHTML.id = 'oldhtml'+tid;
	oldHTML.type = 'hidden';
	oldHTML.value = oldTNP.innerHTML;
	oldTHREAD = oldTNP.innerHTML;
	var text = oldTN.innerHTML.replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&');
	oldTNP.innerHTML = "<input type='text' size='50' id='threadname"+tid+"' value='' class='bginput' onkeydown=\"if( event.keyCode==13){change_thread_title("+tid+",this.value);}\" onblur=\"change_thread_title('"+tid+"',this.value)\";>";
	oldTNP.appendChild(oldHTML);
	var textIp = $("threadname" + tid);
	textIp.value = text;
	textIp.focus();
}

/**
* 修改主题标题
*
*/
function change_thread_title(tid, title)
{
	strlen = calculate_byte(title);
	var oldHTML = $("oldhtml" + tid);
	var html = oldHTML.value;
	var text = html.replace(/<[^>]*>/g,"").replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&');
	if (strlen > 0 && text != title)
	{
		xajax.setDo('thread');
		mxajax_change_thread_title(tid, title, html)
	}
	else
	{
		oldHTML.parentNode.innerHTML = html;
	}
}

/**
* 修改主题属性
*
*/
function change_thread_attr(tid)
{
	if ($("title_color_picker")) closeColorSp();
	var oldTN = $("thread_c_b_" + tid);
	var arrColors=[["#800000","#8b4513","#006400","#2f4f4f","#000080","#4b0082","#800080","#000000"],["#ff0000","#daa520","#6b8e23","#708090","#0000cd","#483d8b","#c71585","#696969"],["#ff4500","#ffa500","#808000","#4682b4","#1e90ff","#9400d3","#ff1493","#a9a9a9"],["#ff6347","#ffd700","#32cd32","#87ceeb","#00bfff","#9370db","#ff69b4","#dcdcdc"],["X","#ffffe0","#98fb98","#e0ffff","#87cefa","#e6e6fa","#dda0dd","#ffffff"]];
	if (!$("show" + tid)) return false;
	var title = $("show" + tid).innerHTML;
	var boldck = title.toLowerCase().lastIndexOf('<strong>') == '-1' ? '' : 'checked';
	var colorTableDiv = document.createElement('div');
	colorTableDiv.id = 'title_color_picker';
	var chDiv = document.createElement('div');
	chDiv.style.width = '128px';
	chDiv.style.padding = '0px 2px';
	chDiv.innerHTML = "<span style='float:right;'><a href='javascript:resetTitleColor("+tid+");'>"+lang_a['info_renew']+"</a>&nbsp;<a href='javascript:closeColorSp();'>"+lang_a['info_close']+"</a></span><input id='showbb"+tid+"' style='width:12px;height:12px;' type='checkbox' "+boldck+" />&nbsp;<strong>"+lang_a['info_bold']+"</strong>";
	colorTableDiv.appendChild(chDiv);
	var colorTable = document.createElement('table');
	colorTable.cellPadding = "0";
	colorTable.cellSpacing = "3";
	for (var n = 0; n < arrColors.length; n++)
	{
		var colorTR = colorTable.insertRow(-1);
		for (var m = 0; m < arrColors[n].length; m++)
		{
			var colorTD = colorTR.insertCell(-1);
			colorTD.id = 'forecolor_' + tid + '_sp_' + arrColors[n][m] ;
			var colorDiv = document.createElement('div');
			addEvent(colorDiv, 'click', chTitleColor);
			addEvent(colorDiv, 'mouseover', changeCss1);
			addEvent(colorDiv, 'mouseout', changeCss2);
			if (arrColors[n][m] == 'X')
			{
				colorDiv.innerHTML = 'X';
				colorDiv.style.font = '11px Arial';
				colorDiv.style.textAlign = 'center';
				colorDiv.style.lineHeight = '11px';
			}
			else
			{
				colorDiv.style.background = arrColors[n][m];
			}
			colorTD.appendChild(colorDiv);
		}
	}
	colorTableDiv.appendChild(colorTable);
	oldTN.appendChild(colorTableDiv);
}

/**
* 关闭主题颜色面板
*
*/
function closeColorSp()
{
	if ($("title_color_picker")) $("title_color_picker").parentNode.removeChild($("title_color_picker"));
}

/**
* 颜色面板鼠标事件
*
*/
function changeCss1(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el;
	eventid.style.borderColor = '#000080';
}

/**
* 颜色面板鼠标事件
*
*/
function changeCss2(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el;
	eventid.style.borderColor = '#fff';
}

/**
* 点击颜色面板中的颜色触发事件
*
*/
function chTitleColor(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el.parentNode;
	var text = eventid.id;
	var color = text.substr(text.lastIndexOf('_sp_') + 4);
	var tid = text.substring(10,text.lastIndexOf('_sp_'));
	var bold = $("showbb" + tid).checked ? 1 : 0;
	closeColorSp();
	xajax.setDo('thread');
	mxajax_change_thread_attr(tid,color,bold);
}

/**
* 重置标题
*
*/
function resetTitleColor(tid)
{
	closeColorSp();
	xajax.setDo('thread');
	mxajax_change_thread_attr(tid, 'reset', 0);
}

function change_forumrule(fid, width)
{
	if (typeof width == 'undefined' || !width)
	{
		width = 144;
	}
	if (typeof mxe != 'undefined' && mxe)
	{
		if (typeof mxeDoc == 'object' &&  mxe.substr(0, 8) == 'showarea')
		{
			return;
		}
	}
	var forum_rule = $('forum_rule');
	var html = forum_rule.innerHTML;
	oldHTML = document.createElement('input');
	oldHTML.type = 'hidden';
	oldHTML.id = 'oldHTML';
	oldHTML.value = html;
	wMode = 1;
	forum_rule.innerHTML = '';
	forum_rule.appendChild(oldHTML);
	showTxa = document.createElement('textarea');
	showTxa.id = 'showarea';
	showTxa.style.width = width + 'px';
	showTxa.style.height = '230px';
	showTxa.value = html;
	forum_rule.appendChild(showTxa);
	mxeditor('showarea', 2);
	showSubmit = document.createElement('div');
	showSubmit.style.padding = '3px 0';
	var click_se_nd = 'do_change_forumrule(' + fid + ')';
	showSubmit.innerHTML = '<input type="button" value="&nbsp;&nbsp;'+lang_a['info_refer']+'&nbsp;&nbsp;" class="button_normal" onclick="'+click_se_nd+';" />&nbsp;<input type="button" value="&nbsp;&nbsp;'+lang_a['info_centre']+'&nbsp;&nbsp;" class="button_normal" onclick="editorreset(\'\',\'forum_rule\');" />';
	forum_rule.appendChild(showSubmit);
	mxeWin.focus();
}

function do_change_forumrule(fid)
{
	mxeGet();
	var content = mxeTxH.value;
	xajax.setDo('thread');
	mxajax_do_change_forumrule(fid, content, wMode);
}


/**
*
* 主题帖子操作的事件
*/
function ajax_submit_form(form, action, obj, pid)
{
	var ischeck = false;
	if (typeof pid == 'undefined')
	{
		pid = 0;
	}
	else if (pid)
	{
		ischeck = true;
	}
	if (!ischeck)
	{
		eval("var modform = document." + form);
		var input = modform.getElementsByTagName('input');

		for (var i = 0; i < input.length; i++)
		{
			if (input[i].name == obj && input[i].value)
			{
				ischeck = true;
				break;
			}
			if (input[i])
			{
				if (input[i].type == 'checkbox' && input[i].checked)
				{
					if (input[i].name.lastIndexOf(obj) != -1)
					{
						ischeck = true;
						break;
					}
				}
			}
		}
	}
	if(ischeck)
	{
		if (obj != 'pid')
		{
			xajax.setDo('thread');
			mxajax_process_form(xajax.getFormValues(form), action);
		}
		else
		{
			xajax.setDo('post');
			mxajax_process_post_form(xajax.getFormValues(form), action, pid);
		}
	}
	else
	{
		alert(lang_a['sure_choice_item']);
	}
}

function do_quintessence(tid, type, pic)
{
	if (type == 0)
	{
		$('quintessence_pic' + tid).removeNode();
	}
	else
	{
		if($('t_suffix_' + tid))
		{
			$('t_suffix_' + tid).innerHTML += '<li>' + pic + '</li>';
		}
		else
		{
			$('ttid' + tid).innerHTML = '<ul id="t_suffix_' + tid + '"><li>' + pic + '</li></ul>' + $('ttid' + tid).innerHTML;
		}
	}
}