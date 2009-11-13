// $Id: controlpanel.js 64 2007-09-07 09:19:11Z hogesoft-02 $
var win = window;
var n = 0;

function change_cell_color(el, cl)
{
	el = $(el);
	if (el) el.className = cl;
}

function pop_win(theUrl, winName, theWidth, theHeight)
{
	if (winName == '')
	{
		winName = 'Preview';
	}
	if (theHeight == '')
	{
		theHeight = 400;
	}
	if (theWidth == '')
	{
		theWidth = 400;
	}
	window.open(theUrl,winName,'width='+theWidth+',height='+theHeight+',resizable=yes,scrollbars=yes');
}

function togglediv( id, s)
{
	if (s) showElement(id);
	else hideElement(id);
	return false;
}

function togglemenucategory(fid, add)
{
	saved = [];
	clean = [];
	if (tmp = getCookie('cpcollapseprefs')) saved = tmp.split(",");
	var n = saved.length;
	for(i = 0 ; i < n; i++) if (saved[i] != fid && saved[i] != '') clean[clean.length] = saved[i];
	if (add)
	{
		clean[clean.length] = fid;
		showElement('fc_'+fid);
		hideElement('fo_'+fid);
	}
	else
	{
		showElement('fo_'+fid);
		hideElement('fc_'+fid);
	}
	setCookie('cpcollapseprefs', clean.join(','), 30);
}

function expandmenu()
{
	saved = [];
	joined = [];
	clean = [];
	if (tmp = getCookie('cpcollapseprefs')) saved = tmp.split(",");
	joined = menu_ids.split(",");
	var n = joined.length;
	for(c = 0 ; c < n; c++) clean[clean.length] = joined[c];
	setCookie('cpcollapseprefs', clean.join(','), 30);
	window.location=window.location;
}

function collapsemenu()
{
	setCookie('cpcollapseprefs', '', 30);
	window.location=window.location;
}

function checkcol(IDnumber,status)
{
	var f = document.cpform;
	str_part = '';
	if (IDnumber == 1) str_part = 'read';
	if (IDnumber == 2) str_part = 'repl';
	if (IDnumber == 3) str_part = 'star';
	if (IDnumber == 4) str_part = 'uplo';
	if (IDnumber == 5) str_part = 'show';
	var n = f.elements.length;
	for (var i = 0 ; i < n; i++) {
		var e = f.elements[i];
		if (e.type == 'checkbox') {
			s = e.name;
			a = s.substring(0, 4);
			if (a == str_part)
				if (status == 1) e.checked = true;
				else e.checked = false;
		}
	}
}

function checkrow(IDnumber,status)
{
	var f = document.cpform;
	str_part = '';
	if (status == 1) mystat = 'true';
	else mystat = 'false';
	eval('f.read_'+IDnumber+'.checked='+mystat);
	eval('f.reply_'+IDnumber+'.checked='+mystat);
	eval('f.start_'+IDnumber+'.checked='+mystat);
	eval('f.upload_'+IDnumber+'.checked='+mystat);
	eval('f.show_'+IDnumber+'.checked='+mystat);
}

function updatepreview()
{
	var formobj = document.cpform;
	var dd_weekday = new Array();
	dd_weekday[0] = lang_c['cp_sunday'];
	dd_weekday[1] = lang_c['cp_monday'];
	dd_weekday[2] = lang_c['cp_tuesday'];
	dd_weekday[3] = lang_c['cp_wednesday'];
	dd_weekday[4] = lang_c['cp_thursday'];
	dd_weekday[5] = lang_c['cp_friday'];
	dd_weekday[6] = lang_c['cp_saturday'];
	var output = '';
	chosen_min = formobj.minute.options[formobj.minute.selectedIndex].value;
	chosen_hour = formobj.hour.options[formobj.hour.selectedIndex].value;
	chosen_weekday = formobj.weekday.options[formobj.weekday.selectedIndex].value;
	chosen_monthday = formobj.monthday.options[formobj.monthday.selectedIndex].value;
	var output_min = '';
	var output_hour = '';
	var output_day = '';
	var timeset = 0;
	if (chosen_monthday == -1 && chosen_weekday == -1) output_day = '';
	if (chosen_monthday != -1) output_day = sprintf(lang_c['cp_chosen_monthday'], chosen_monthday);
	if (chosen_monthday == -1 && chosen_weekday != -1)
		output_day = sprintf(lang_c['cp_dd_weekday'], dd_weekday[chosen_weekday]);
	if (chosen_hour != -1 && chosen_min != -1)
		output_hour = sprintf(lang_c['cp_chosen_time'], chosen_hour, formatnumber(chosen_min));
	else
	{
		if (chosen_hour == -1)
		{
			if (chosen_min == 0) output_hour = lang_c['cp_run_pre_hour'];
			else
			{
				if ( output_day == '' )
				{
					if ( chosen_min == -1 ) output_min = lang_c['cp_run_pre_min'];
					else output_min = sprintf(lang_c['cp_chosen_min'], chosen_min);
				}
				else output_min = sprintf(lang_c['cp_chosen_first_min'], formatnumber(chosen_min));
			}
		}
		else if (output_day != '') output_hour = sprintf(lang_c['cp_run_at_time'], chosen_hour);
		else output_hour = sprintf(lang_c['cp_run_per_hours'], chosen_hour);
	}
	output = output_day + ' ' + output_hour + ' ' + output_min;
	formobj.showcron.value = output;
}

function formatnumber(num)
{
	if (num == -1) return '00';
	if (num < 10) return '0' + num;
	else return num;
}

function confirmupload(tform, filefield)
{
	if (filefield.value == '')
		return confirm(sprintf(lang_c['cp_confirmupload'], tform.fromserver.value));
	return true;
}

function moz_txtarea_scroll(input, txtpos)
{
	var newarea = input.cloneNode(true);
	newarea.setAttribute('id', 'moo');
	newarea.value = input.value.substr(0, txtpos);
	document.body.appendChild(newarea);
	if (newarea.scrollHeight <= input.scrollTop || newarea.scrollHeight >= input.scrollTop + input.offsetHeight)
	{
		if (newarea.scrollHeight == newarea.clientHeight) input.scrollTop = 0;
		else input.scrollTop = newarea.scrollHeight - 40;
	}
	document.body.removeChild($('moo'));
}

var startpos = 0;
function findInPage(tid, str)
{
	var txt, i, found;
	if (str == '')
	{
		return false;
	}
	var obj = $(tid);
	if (isMoz)
	{
		txt = obj.value;
		if (!startpos || startpos + str.length >= txt.length)
		{
			startpos = 0;
		}
		var x = 0;
		var matchfound = false;
		var n = txt.length;
		for (i = startpos; i < n; i++)
		{
			if (txt.charAt(i) == str.charAt(x)) x++;
			else x = 0;
			if (x == str.length)
			{
				i++;
				startpos = i;
				obj.focus();
				obj.setSelectionRange(i - str.length, i);
				moz_txtarea_scroll(obj, i);
				matchfound = true;
				break;
			}
			if (i == txt.length - 1 && startpos > 0)
			{
				i = 0;
				startpos = 0;
			}
		}
		if (!matchfound) alert(lang_c['cp_string_not_found']);
	}
	else if (isIE)
	{
		txt = obj.createTextRange();
		for (i = 0; i <= startpos && (found = txt.findText(str)) != false; i++)
		{
			txt.moveStart('character', 1);
			txt.moveEnd('textedit');
		}
		if (found)
		{
			txt.moveStart('character', -1);
			txt.findText(str);
			txt.select();
			txt.scrollIntoView(true);
			startpos++;
		}
		else
		{
			if (startpos > 0)
			{
				startpos = 0;
				findInPage(tid, str);
			}
			else alert(lang_c['cp_string_not_found']);
		}
	}
	return false;
}