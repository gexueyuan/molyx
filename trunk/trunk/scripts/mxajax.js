/**
* 修改正在干什么时反馈修改框
*
*/
function changemedotext()
{
	var showcurdo = $('usercurdo');
	var curdotitle = "";
	var curem = showcurdo.getElementsByTagName('em');
	var curcite = showcurdo.getElementsByTagName('cite');
	if (curem[0] && curcite[0] && curcite[0].innerHTML)
	{
		curdotitle = curem[0].innerHTML;
		curdotitle = curdotitle.replace(/&gt;/g,'>').replace(/&lt;/g,'<').replace(/&amp;/g,'&');
	}
	showcurdo.innerHTML = '<input type="text" name="currentdo" value="" id="currentdo" size="30" />';
	$('currentdo').focus();
	$('currentdo').value = curdotitle;
	addEvent($('currentdo'), 'blur', changemedo);
	addEvent($('currentdo'), 'keydown', keydownit);
	removeEvent($('usercurdo'), 'click', changemedotext);
	showcurdo.onclick = null;
}

function keydownit(e) {
	if(e.keyCode==13)
	{
		changemedo();
	}
}

/**
*  修改提交到ajax
*
*/
function changemedo()
{
	var text = "";
	if($('currentdo'))
	{
		text = $('currentdo').value;
	}
	xajax.setDo('process');
	mxajax_changemedo(text);
}

function editorreset(pid,type)
{
	if (typeof mxeDoc == 'object' && typeof mxe != 'undefined' && mxe == 'showarea' + pid +'W')
	{
		var oldHTML = $('oldHTML' + pid);
		if (oldHTML)
		{
			var html = oldHTML.value;
		}
		else
		{
			return;
		}
		var showDiv = $(type + pid);
		if(type == 'signature' && signature_a_height)
		{
			showDiv.style.height = signature_a_height;
		}
		showDiv.innerHTML = html;
		mxe = mxeWin = mxeDoc = mxeTxa = mxeTxH = mxeEbox = mxeStatus = mxeWidth = mxeHeight = eWidth = null;
	}
}

function openquick()
{
	if (canwMode)
	{
		var cookiemode = getCookie('mxeditor');
		if (cookiemode == 'wysiwyg')
		{
			wMode = 1;
		}
		else if (cookiemode == 'bbcode')
		{
			wMode = 0;
		}
	}
	else
	{
		wMode = 0;
	}
	if ($('post'))
	{
		if (typeof(load_qmxe) == "function")
		{
			addEvent($('post'), 'click', load_qmxe);
		}
		var quickREdit = $('eDiv_postW');
		if (quickREdit)
		{
			quickREdit.parentNode.removeChild(quickREdit);
			showElement('post');
			mxeditor('post', qmxemenu);
			$('submitform').disabled = false;
		}
		else
		{
			mxe = mxeWin = mxeDoc = mxeTxa = mxeTxH = mxeEbox = mxeStatus = mxeWidth = mxeHeight = eWidth = null;
		}
		if ($('submitform'))
		{
			addEvent($('submitform'), 'click', quick_reply);
		}
	}
}

function closedquick()
{
	if (typeof mxeDoc == 'object' && typeof mxe != 'undefined' && mxe == 'postW')
	{
		if (wMode)
		{
			if (isIE)
			{
				mxeWin.document.open();
				mxeWin.document.close();
			}
			mxeWin.document.designMode = "off";
		}
		else
		{
			mxeTxa.readOnly = 'readonly';
		}
		var quickREdit = $('eDiv_' + mxe);
		if (qmxemenu == "1")
		{
			var ncBu;
			for (var m = 0; m < mBut.length; m++)
			{
				for (var n = 0; n < mBut[m].length; n++)
				{
					if(mBut[m][n] != '|')
					{
						ncBu = $(mBut[m][n] + '_mxButton_' + mxe);
						ncBu.className = 'bu_miss';
						ncBu.onmouseover = ncBu.onmouseout  = ncBu.onmousedown = ncBu.onmouseup = null;
					}
				}
			}
		}
		mxeStatus.innerHTML = lang_a['info_closequ'];
		quickREdit.onmouseover = quickREdit.onmouseout  = quickREdit.onmousedown = quickREdit.onmouseup = null;
		mxe = mxeWin = mxeDoc = mxeTxa = mxeTxH = mxeEbox = mxeStatus = mxeWidth = mxeHeight = eWidth = null;
		$('submitform').onclick = null;
	}
}

var fixed_timer, avatar_no = 0;
function edit_user_avatar(uid, e)
{
	var timeout = 500;
	if (!$('avatar_opt'))
	{
		var avatar_opt = document.createElement('div');
		avatar_opt.id = 'avatar_opt';
		avatar_opt.innerHTML = '<input type="button" name="del_avatar" value="' + lang_a['delete_avatar'] + '" class="button_normal" onmouseover="clearTimeout(fixed_timer);" onmouseout="hide_avatar_opt();" onclick="delete_user_avatar();" /><input type="hidden" id="avatar_user_id" name="avatar_user_id" value="" />';
		avatar_opt.style.position = 'absolute';
		e.parentNode.appendChild(avatar_opt);
	}
	else
	{
		showElement('avatar_opt');
		avatar_opt = $('avatar_opt');
	}
	var epos = getPosition(e);
	avatar_opt.style.left = epos.left;
	var height = epos.top;
	if (isIE)
	{
		height = height + epos.height;
	}
	else
	{
	}
	avatar_opt.style.top = height;
	avatar_no++;
	$('avatar_user_id').value = uid + '_' + avatar_no;
	e.setAttribute('id', 'avatar_temp_id' + avatar_no);
}

function hide_avatar_opt()
{
	fixed_timer = setTimeout("hideElement('avatar_opt');", 500);
}
function delete_user_avatar()
{
	if (confirm(lang_a['confirm_delete_avatar']))
	{
		var uid_no = $('avatar_user_id').value;
		uid_no = uid_no.split('_');
		avatar_no = uid_no[1];
		uid = uid_no[0];
		xajax.setDo('user');
		mxajax_delete_user_avatar(uid, avatar_no);
	}
}

function send_mailto_user(uid, form)
{
	xajax.setDo('process');
	if(typeof form != 'undefined' && form)
	{
		mxajax_send_mailto_user(xajax.getFormValues(form), uid);
	}
	else
	{
		mxajax_send_mailto_user('', uid);
	}
}

function digg_thread(tid)
{
	if (confirm(lang_a['confirm_digg_thread']))
	{
		xajax.setDo('process');
		mxajax_digg_thread(tid);
	}
}