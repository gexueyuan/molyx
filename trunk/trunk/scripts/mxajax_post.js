// $Id: mxajax_post.js 361 2007-11-12 02:42:52Z develop_tong $
function quick_reply()
{
	if ($('submitform'))
	{
		var submitform = $('submitform');
		submitform.disabled = true;
	}
	if ($('quick_preview'))
	{
		var quick_preview = $('quick_preview');
		quick_preview.disabled = true;
	}
	var showredirect = $("redirect").checked;
	var showform = $("mxbform");
	if (showredirect == true)
	{
		mxeGet();
		showform.submit();
		return false;
	}
	mxeGet();
	var content = mxeTxH.value;
	initData();
	mxeTxH.value = '';
	var modetype = wMode ? 'wysiwyg' : 'bbcode';
	xajax.setDo('post');
	mxajax_quick_reply(xajax.getFormValues("mxbform"), content, modetype);
}

function edit_post_event(pid, fid, uid, tid, dateline)
{
	if (typeof mxe != 'undefined' && mxe)
	{
		if (typeof mxeDoc == 'object' &&  mxe.substr(0, 8) == 'showarea')
		{
			var mxe_pid = mxeTxH.id.substr(8);
			if (mxe_pid == pid)
			{
				return;
			}
			editorreset(mxe_pid,oldType);
		}
		else
		{
			closedquick();
		}
	}
	oldType = 'show';
	if ($('submitform'))
	{
		if ($('submitform').disabled == true)
		{
			removeEvent($('post'), 'click', load_qmxe);
			$('post').onclick = null;
		}
		else
		{
			$('submitform').disabled = true;
		}
	}
	xajax.setDo('post');
	mxajax_returnpagetext(pid, fid, uid, tid, dateline);
}

function show_post_text_editor(pid, uid, tid, dateline, post_text)
{
	var html1 = post_text;
	var showDiv = $('show' + pid);
	var html2 = showDiv.innerHTML;
	oldHTML = document.createElement('input');
	oldHTML.type = 'hidden';
	oldHTML.id = 'oldHTML' + pid;
	oldHTML.value = html2;
	wMode = 1;
	showDiv.innerHTML = '';
	showDiv.appendChild(oldHTML);
	showTxa = document.createElement('textarea');
	showTxa.id = 'showarea' + pid;
	showTxa.style.width = '600px';
	showTxa.style.height = '300px';
	showTxa.value = html1;
	showDiv.appendChild(showTxa);
	mxeditor('showarea' + pid);
	showSubmit = document.createElement('div');
	showSubmit.style.padding = '3px 0';
	var click_se_nd = 'do_edit_post(' + pid + ', ' + uid + ', ' + tid + ', ' + dateline + ')';
	var type = 'text';
	showSubmit.innerHTML = '<input type="button" value="&nbsp;&nbsp;'+lang_a['info_refer']+'&nbsp;&nbsp;" class="button_normal" onclick="'+click_se_nd+';" />&nbsp;<input type="button" value="&nbsp;&nbsp;'+lang_a['info_centre']+'&nbsp;&nbsp;" class="button_normal" onclick="editorreset('+pid+',\'show\');openquick();" />';
	showDiv.appendChild(showSubmit);
	mxeWin.focus();
}

function do_edit_post(pid, uid, tid, dateline)
{
	var fid = $('forum_id').value;
	mxeGet();
	var content = mxeTxH.value;
	xajax.setDo('post');
	mxajax_do_edit_post(pid, fid, uid, tid, content, wMode, dateline);
}

function preview_post(fid)
{
	mxeGet();
	var obj = $('allowsmile');
	if (obj)
	{
		if (obj.checked)
		{
			var allowsmile = 1;
		}
		else
		{
			var allowsmile = 0;
		}
	}
	else
	{
		var allowsmile = 1;
	}
	var content = mxeTxH.value;
	xajax.setDo('post');
	mxajax_dopreview_post(content, fid, allowsmile);
}

function smiles_page(num, p)
{
	xajax.setDo('post');
	mxajax_smiles_page(num, p);
}

function send_mailto_friend(tid, form)
{
	xajax.setDo('post');
	if(typeof form != 'undefined' && form)
	{
		var formdata = xajax.getFormValues(form);
		mxajax_send_mailto_friend(tid, formdata);
	}
	else
	{
		mxajax_send_mailto_friend(tid);
	}
}