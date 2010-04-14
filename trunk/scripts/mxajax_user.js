// $Id$
var signature_a_height = 0;
function edit_signature_event(pid, uid, width, editor_mode)
{
	if (typeof width == 'undefined' || !width)
	{
		width = 300;
	}
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
	oldType = 'signature';

	var showDiv = $('signature' + pid);

	if (showDiv.offsetHeight)
	{
		signature_a_height = showDiv.offsetHeight;
		showDiv.style.height = '185px';
	}
	var html = showDiv.innerHTML;
	oldHTML = document.createElement('input');
	oldHTML.type = 'hidden';
	oldHTML.id = 'oldHTML' + pid;
	oldHTML.value = html;
	wMode = 1;
	showDiv.innerHTML = '';
	showDiv.appendChild(oldHTML);
	showTxa = document.createElement('textarea');
	showTxa.id = 'showarea' + pid;
	showTxa.style.width = width + 'px';
	showTxa.style.height = '150px';
	showTxa.value = html;
	showDiv.appendChild(showTxa);
	mxeditor('showarea' + pid, editor_mode);
	showSubmit = document.createElement('div');
	showSubmit.style.padding = '3px 0';
	var click_se_nd = 'do_change_signature('+pid+','+uid+')';
	showSubmit.innerHTML = '<input type="button" value="&nbsp;&nbsp;'+lang_a['info_refer']+'&nbsp;&nbsp;" class="button_normal" onclick="'+click_se_nd+';" />&nbsp;<input type="button" value="&nbsp;&nbsp;'+lang_a['info_centre']+'&nbsp;&nbsp;" class="button_normal" onclick="editorreset('+pid+',\'signature\');openquick();" />';
	showDiv.appendChild(showSubmit);
	mxeWin.focus();
}

function do_change_signature(pid, uid)
{
	mxeGet();
	var content = mxeTxH.value;
	xajax.setDo('user');
	mxajax_do_change_signature(pid, uid, content, wMode);
}

function report_post(form, pid)
{
	xajax.setDo('user');
	mxajax_report_post(xajax.getFormValues(form), pid);
}

function do_report_post(form)
{
	xajax.setDo('user');
	mxajax_do_report_post(xajax.getFormValues(form));
}

function evaluation_post(form, pid)
{
	xajax.setDo('user');
	mxajax_evaluation_post(xajax.getFormValues(form), pid);
}

function do_evaluation_post(form)
{
	xajax.setDo('user');
	mxajax_do_evaluation_post(xajax.getFormValues(form));
}

var lists_value = lists_key = [];
function changecredit()
{
	var tag = $("actcredit").value;
	for(var i = 0; i < lists_key.length; i++)
	{
		if (lists_key[i] == tag)
		{
			break;
		}
	}
	$('evalcreditdesc').innerHTML = lists_value[i];
}

function doban_user_post(form, uid, doban)
{
	xajax.setDo('user');
	mxajax_ban_user_post(xajax.getFormValues(form),uid, doban);
}