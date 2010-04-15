var canwMode = false, wMode = false, rng, eDir, iDir, mxe, mxeWin, mxeDoc, mxeTxa, mxeTxH, mxeFocus = false, mxeEbox, mxeStatus, mxeWidth, mxeHeight, boxH, boxW, mMode, popOpen, dBody,
mButQ = [["removeformat","|","fontname","fontsize","|","bold","italic","underline","|","forecolor","hilitecolor","|","insertlink","unlink","insertimage","quote"]],
mButLQ =[[lang_e['bu_rmformat'],"|",lang_e['bu_ftname'],lang_e['bu_ftsize'],"|",lang_e['bu_bold'],lang_e['bu_italic'],lang_e['bu_unline'],"|",lang_e['bu_foolor'],lang_e['bu_hicolor'],"|",lang_e['bu_inlink'],lang_e['bu_unlink'],lang_e['bu_inimage'],lang_e['bu_quote']]],
mBut =[ ['removeformat','|','fontname','fontsize','|','bold','italic','underline','|','subscript','superscript','|','justifyleft','justifycenter','justifyright','|','insertorderedlist','insertunorderedlist','outdent','indent','|','forecolor','hilitecolor'],['undo','redo','|','cut','copy','paste','|','inserthorizontalrule','specialchar','|','insertlink','unlink','insertimage','inserttable','|','code','quote','emule','hide']],
mButL =[ [lang_e['bu_rmformat'],'|',lang_e['bu_ftname'],lang_e['bu_ftsize'],'|',lang_e['bu_bold'],lang_e['bu_italic'],lang_e['bu_unline'],'|',lang_e['bu_sbscript'],lang_e['bu_spscript'],'|',lang_e['bu_juleft'],lang_e['bu_jucenter'],lang_e['bu_juright'],'|',lang_e['bu_iolist'],lang_e['bu_iulist'],lang_e['bu_outdent'],lang_e['bu_indent'],'|',lang_e['bu_foolor'],lang_e['bu_hicolor'],'|',lang_e['bu_help']],[lang_e['bu_undo'],lang_e['bu_redo'],'|',lang_e['bu_cut'],lang_e['bu_copy'],lang_e['bu_paste'],'|',lang_e['bu_ihrule'],lang_e['bu_spchar'],'|',lang_e['bu_inlink'],lang_e['bu_unlink'],lang_e['bu_inimage'],lang_e['bu_intable'],'|',lang_e['bu_code'],lang_e['bu_quote'],lang_e['bu_emule'],lang_e['bu_hide']]],
popMQ = ['fontname', 'fontsize', 'forecolor', 'hilitecolor'],
popM = ['fontname', 'fontsize', 'forecolor', 'hilitecolor', 'specialchar', 'code'],
fonts = [lang_e['bu_st'],lang_e['bu_ht'],lang_e['bu_gt'],lang_e['bu_yy'],lang_e['bu_fs'],lang_e['bu_xm'],'Arial','Courier New','Times New Roman','Verdana'],
fontSize = [1,2,3,4,5,6,7],
arrColors = [['#800000','#8b4513','#006400','#2f4f4f','#000080','#4b0082','#800080','#000000'],['#ff0000','#daa520','#6b8e23','#708090','#0000cd','#483d8b','#c71585','#696969'],['#ff4500','#ffa500','#808000','#4682b4','#1e90ff','#9400d3','#ff1493','#a9a9a9'],['#ff6347','#ffd700','#32cd32','#87ceeb','#00bfff','#9370db','#ff69b4','#dcdcdc'],['X','#ffffe0','#98fb98','#e0ffff','#87cefa','#e6e6fa','#dda0dd','#ffffff']],
arrChars = [['&le;','&ge;','&oplus;', '&yen;','&#133;','&plusmn;','&times;','&divide;'],['&copy;','&reg;','&trade;','&#151;','&amp;','&deg;','&#149;', '&permil;'],['&ne;','&equiv;','&larr;','&uarr;','&rarr;','&darr;','&harr;','&radic;'], ['&prop;','&infin;','&ang;','&and;','&or;','&cap;','&cup;','&Oslash;'],['&int;','&there4;','&asymp;','&yen;','&cent;','&micro;','&szlig;','&pound;']],
arrCode = ['PHP', 'SQL', 'XML/HTML', 'CSS', 'JavaScript', 'Java', 'C/C++', 'C#', 'Ruby', 'Python', 'VB'];

function initmxe(eD, wMod)
{
	if (document.designMode && !isSafari && !isKonqueror) canwMode = true;
	eDir = eD;
	iDir = eDir + "images/";
	if (canwMode)
	{
		var cookiemode = getCookie('mxeditor');
		if (cookiemode == 'wysiwyg')  wMode = 1;
		else if (cookiemode == 'bbcode')  wMode = 0;
		else
		{
			wMode = wMod;
			var modeType = wMode ? 'wysiwyg' : 'bbcode';
			setCookie('mxeditor', modeType);
		}
	}
	else wMode = 0;
	if (typeof mEBut == 'object')
	{
		var slip = '|';
		mBut[1] = mBut[1].concat(slip, mEBut[0]);
		mButL[1] = mButL[1].concat(slip, mEBut[1]);
	}
}

function mxeditor(mxeH, qMode, isFocus)
{
	mxe = mxeH + "W";
	mxeTxH = $(mxeH);
	var editDiv, TxHoffetW, TxHoffsetH;
	if (typeof qMode == 'undefined') qMode = 0;
	if (typeof isFocus == 'undefined') mxeFocus = true;
	mMode = qMode;
	TxHoffetW = mxeTxH.offsetWidth;
	TxHoffsetH = mxeTxH.offsetHeight;
	if (mMode == 2)
	{
		mxeWidth = TxHoffetW - 12;
		mxeHeight = TxHoffsetH - 12;
		boxW = mxeWidth - 6;
		boxH = mxeHeight - 23;
		editDiv = [initEbox(), initEstatus()];
	}
	else
	{
		if (mMode == 1)
		{
			mBut = mButQ;
			mButL = mButLQ;
			popM = popMQ;
			mxeWidth = (TxHoffetW > 300 ? TxHoffetW : 300) - 12;
			mxeHeight = (TxHoffsetH > 100 ? TxHoffsetH : 100) - 12;
			boxH = mxeHeight - 45;
		}
		else
		{
			mxeWidth = (TxHoffetW > 500 ? TxHoffetW : 500) - 12;
			mxeHeight = (TxHoffsetH > 350 ? TxHoffsetH : 350) - 12;
			var cookieheight = parseInt(getCookie('mxeHeight'));
			if (cookieheight > 0) mxeHeight = cookieheight;
			else setCookie('mxeHeight', mxeHeight);
			boxH = mxeHeight - 75;
		}
		boxW = mxeWidth - 6;
		editDiv = [initMenu(), initEbox(), initEstatus(), '<div id="', mxe, 'popus">'];
		var n = popM.length, i;
		for (i = 0; i < n; i++)
		{
			var tmp = '';
			switch(popM[i])
			{
				case 'fontname':
					tmp = popuFont('name');
					break;
				case 'fontsize':
					tmp = popuFont('size');
					break;
				case 'forecolor':
					tmp = popuFontcolor("forecolor");
					break;
				case 'hilitecolor':
					tmp = popuFontcolor("hilitecolor");
					break;
				case 'specialchar':
					tmp = popuSpecialchar();
					break;
				case 'code':
					tmp = popuCode();
					break;
			}
			if (tmp) editDiv.push(initPopu(popM[i]), tmp, '</ul>');
		}
		editDiv.push('</div>');
	}
	hideElement(mxeTxH);
	var div = document.createElement('div');
	div.id = 'eDiv_' + mxe;
	div.className = 'div_editor';
	div.style.width = mxeWidth + 'px';
	div.style.height = mxeHeight + 'px';
	div.innerHTML = editDiv.join('');
	mxeTxH.parentNode.appendChild(div);
	mxeEbox = $(mxe);
	mxeStatus = $('editstatus' + mxe);
	initData();
	if(mMode != 1 && mMode != 2 && wMode == 0)
	{
		if(isIE) var needmiss = ['inserttable'];
		else var needmiss = ['inserttable', 'undo', 'redo'];

		for (var n=0; n < needmiss.length; n++)
		{
			var needchange = $(needmiss[n] + '_mxButton_' + mxe);
			needchange.className = 'bu_miss';
		}
	}
}

function cdSwitchtext(data)
{
	var modeType = wMode ? 'wysiwyg' : 'bbcode';
	setCookie('mxeditor', modeType);
	var editDiv = $('eDiv_' + mxe);
	editDiv.removeChild(mxeEbox);
	editDiv.removeChild(mxeStatus);
	editDiv.innerHTML += [initEbox(), initEstatus()].join('');
	mxeEbox = $(mxe);
	mxeStatus = $('editstatus' + mxe);
	mxeTxH.value = data;
	initData();
}

function changeMxeMode(fid)
{
	if (wMode) mxeTxH.value = getXHTML(mxeWin.document.body);
	else mxeTxH.value = mxeTxa.value;

	var obj = $('allowsmile');
	if (obj)
	{
		if (obj.checked) var allowsmile = 1;
		else var allowsmile = 0;
	}
	else var allowsmile = 1;

	//mxeTxH.value = mxeTxH.value.replace(/%/g, '%25');
	mxeTxH.value = mxeTxH.value.replace(/^\-/g, '%2d');
	xajax.setDo('process');
	mxajax_switch_editor_mode(mxeTxH.value, wMode, fid, allowsmile);
}

function completedChangeMxeMode()
{
	if(mMode == 1 || mMode == 2) var needmiss = [];
	else
	{
		if (isIE) var needmiss = ['inserttable'];
		else var needmiss = ['inserttable', 'undo', 'redo'];
	}

	if (wMode)
	{
		wMode = 0;
		var cName = 'bu_miss';
	}
	else
	{
		wMode = 1;
		var cName = 'b_normal';
	}

	var n = needmiss.length;
	for (var i = 0; i < n; i++)
		$(needmiss[i] + '_mxButton_' + mxe).className = cName;
}

function initEbox()
{
	if (wMode)
	{
		var mxeEdBox = ['<iframe id="', mxe, '" name="', mxe, '" class="box_editor" scrolling="auto" style="width:', (boxW + 4), 'px;height:', (boxH + 2), 'px;'];
		if(isIE) mxeEdBox.push('margin:1px 0;');
		mxeEdBox.push('"></iframe>');
	}
	else
		var mxeEdBox = ['<textarea id="', mxe, '" rows="" cols="" class="box_editor" style="width:', boxW, 'px;height: ', boxH, 'px;" onmouseup="hidePopu(event);"></textarea>'];
	return mxeEdBox.join('');
}

function initEstatus()
{
	var txt = ['<div id="editstatus', mxe, '"><div style="float:right;"><a href="javascript:resizeMxe(1);">&darr;', lang_e['info_kz'], '</a> | <a href="javascript:resizeMxe(-1);">&uarr;', lang_e['info_ss'], '</a></div><div><a href="javascript:checklength();">', lang_e['info_chsize'], '</a>'];
	if (canwMode)
	{
		txt.push(' | <a href="javascript:changeMxeMode(thisforum);">', lang_e['info_qh']);
		if (wMode) txt.push(lang_e['md_bbcode']);
		else txt.push(lang_e['md_wysiwyg']);
		if (typeof thisforum == 'undefined') thisforum = 0;
		txt.push(lang_e['info_edi'], '</a>');
	}
	txt.push('</div></div>')
	return txt.join('');
}

function initMenu()
{
	var mButHtml = ['<div id="menubutton', mxe, '" onmouseover="buttonover(event);" onmouseout="buttonnormal(event);" onmousedown="buttondown(event);" onmouseup="buttonover(event);">'];
	var m, n, bl, mButn, mButnm, mButLnm, ml = mBut.length;
	for (n = 0; n < ml; n++)
	{
		mButHtml.push('<ul class="e_menu" id="', mxe, '_buttons_', n, '">');
		mButn = mBut[n];
		var mnl = mButn.length;
		for (m = 0; m < mnl; m++)
		{
			mButnm = mButn[m];
			if (!mButnm) continue;
			if(mButnm == '|')
			{
				if (isIE || (!isIE && mButn[m+1] != 'cut'))
					mButHtml.push('<li><img src="', iDir, 'sep.gif" alt=""></li>');
			}
			else if (isIE || (!isIE && mButnm != 'cut' && mButnm != 'copy' && mButnm != 'paste'))
			{
				mButLnm = mButL[n][m];
				mButHtml.push('<li id="', mButnm, '_mxButton_', mxe, '" class="b_normal" title="', mButLnm, '" onclick="mxeCmd(event);"><img src="', iDir, mButnm, '.gif" alt="', mButLnm, '"></li>');
			}
		}
		mButHtml.push('</ul>');
	}
	mButHtml.push('</div>');
	return mButHtml.join('');
}

function initPopu(cmd)
{
	return ['<ul id="', mxe, 'pop', cmd, '" class="pop_editor" onmouseover="buttonover(event);" onmouseout="buttonnormal(event);" onmousedown="buttondown(event);" onmouseup="buttonover(event);">'].join('');
}

function popuFont(ftype)
{
	var ar, fontStyle, fontStyle, menu = [];
	switch(ftype)
	{
		case 'name':
			ar = fonts;
			fontStyle = ' face="';
		break;
		case 'size':
			ar = fontSize;
			fontStyle = ' size="';
		break;
		default: return '';
	}
	var i, n = ar.length;
	for (i = 0; i < n; i++)
	{
		var ari = ar[i]
		menu.push('<li id="font', ftype, '_sp_', ari, '_sp_', mxe, '" class="b_normal" title="', ari, '" onclick="changeFont(event);"><font', fontStyle, ari, ';">', ari, '</font></li>');
	}
	return menu.join('');
}

function popuFontcolor(color)
{
	var menu = [];
	var n, m, cl, acnm, al = arrColors.length;
	for (n = 0; n < al; n++)
	{
		menu.push('<li><ul>');
		cl = arrColors[n].length;
		for (m = 0; m < cl; m++)
		{
			acnm = arrColors[n][m];
			menu.push('<li id="', color, '_sp_', acnm, '_sp_', mxe, '" class="b_normal" onclick="changeFont(event);"><div');
			if (acnm == 'X')
				menu.push(' style="font:11px Arial;">X</div></li>');
			else
				menu.push(' style="background:', acnm, ';"></div></li>');
		}
		menu.push('</ul></li>');
	}
	return menu.join('');
}

function popuSpecialchar()
{
	var menu = [];
	var n, m, cl, acnm, al = arrChars.length;
	for (n = 0; n < al; n++)
	{
		menu.push('<li><ul>');
		cl = arrChars[n].length;
		for (m = 0; m < cl; m++)
		{
			acnm = arrChars[n][m];
			menu.push('<li id="specialchar_sp_', acnm, '_sp_', mxe, '" class="b_normal" onclick="changeFont(event);"><span>', acnm, '</span></li>');
		}
		menu.push('</ul></li>');
	}
	return menu.join('');
}

function popuCode()
{
	var menu = [];
	var ari, acId, n = arrCode.length;
	for (i = 0; i < n; i++)
	{
		aci = arrCode[i];
		acId = aci.split('/')[0].toLowerCase();
		menu.push('<li id="code_sp_', acId, '_sp_', mxe, '" class="b_normal" title="', aci, '" onclick="changeFont(event);"><font>', aci, '</font></li>');
	}
	return menu.join('');
}

function initData()
{
	var	html = mxeTxH.value;
	if (wMode)
	{
		if (isIE)  mxeWin = frames[mxe];
		else mxeWin = $(mxe).contentWindow;
		mxeDoc = mxeWin.document;
		mxeTxa = null;
		enableDesignMode(html);
		if (mxeFocus) mxeWin.focus();
	}
	else
	{
		mxeWin = window;
		mxeDoc = mxeWin.document;
		mxeTxa = $(mxe);
		mxeTxa.value = html;
		if (mxeFocus) mxeTxa.focus();
		addEvent(mxeTxa, 'keypress', noKeyPress);
	}
}

function enableDesignMode(html)
{
	var frameHtml = ['<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n<html id="', mxe, '" xmlns="http://www.w3.org/1999/xhtml">\n<head>\n<meta http-equiv="content-type" content="text/html; charset=utf-8" />\n<style type="text/css">@import "', eDir, 'mxebox.css";</style>\n<link rel="stylesheet" type="text/css" href="', eDir, 'mxebox.css" />\n</head>\n<body>\n', html, '\n</body>\n</html>'];
	try
	{
		mxeDoc.designMode = "on";
		mxeDoc.open();
		mxeDoc.write(frameHtml.join(''));
		mxeDoc.close();
		addEvent(mxeDoc, 'keypress', ifkeyPress);
		addEvent(mxeDoc, 'mouseup', hidePopu);
	}
	catch (e) { setTimeout("enableDesignMode('" + html + "');", 10); }
}

function mxeGet()
{
	if (mxeTxH)
	{
		if (mxeTxH.value == null) mxeTxH.value = '';
		if (wMode) mxeTxH.value = getXHTML(mxeWin.document.body);
		else mxeTxH.value = mxeTxa.value;
		if (stripHTML(mxeTxH.value.replace('&nbsp;', ' ')) == '' && mxeTxH.value.toLowerCase().search("<img") < 0) mxeTxH.value = '';
	}
}

function buttonstatus(e, sta)
{
	e = e || window.event;
	var el = e.target || e.srcElement;
	if (!el.className && el.parentNode.className)
		if (el.parentNode.className.substr(0, 2) == 'b_')
			el.parentNode.className = 'b_' + sta;
}

function buttonover(e) {buttonstatus(e, 'over');}
function buttonnormal(e) {buttonstatus(e, 'normal');}
function buttondown(e) {buttonstatus(e, 'down');}

function mxeCmd(e)
{
	if (wMode && !mxeWin.focus()) mxeWin.focus();
	else if (!wMode && !mxeTxa.focus()) mxeTxa.focus();
	e = e || window.event;
	var el = e.target || e.srcElement;
	if (!el.id && el.parentNode.id) el = el.parentNode;
	var cmd = el.id.replace('_mxButton_' + mxe, '');
	hidePopu();
	if(wMode)
	{
		switch(cmd)
		{
			case 'bold':
			case 'italic':
			case 'underline':
			case 'justifyleft':
			case 'justifycenter':
			case 'justifyright':
			case 'insertorderedlist':
			case 'insertunorderedlist':
			case 'outdent':
			case 'indent':
			case 'undo':
			case 'redo':
			case 'cut':
			case 'copy':
			case 'paste':
			case 'unlink':
			case 'inserthorizontalrule':
			case 'subscript':
			case 'superscript':
			case 'removeformat':
				mexcCommand(cmd);
			break;
			case 'fontname':
			case 'fontsize':
			case 'forecolor':
			case 'hilitecolor':
			case 'specialchar':
			case 'code':
				showPopuDiv(cmd);
			break;
			case 'insertlink':
			case 'inserttable':
				showPopuWin(cmd);
			break;
			case 'insertimage':
				var imgpath = prompt(lang_e['info_inimg'],'http://');
				if (imgpath && imgpath != 'http://') mexcCommand('insertImage', false, imgpath);
			break;
			case 'quote':
			case 'emule':
			case 'hide':
				wrapTag(cmd, false);
			break;
			default:
				if (typeof mEBut == 'object')
				{
					for (var n in mEBut[0])
					{
						if (cmd == mEBut[0][n])
						{
							wrapTag(cmd, mEBut[2][n]);
							break;
						}
					}
				}
			break;
		}
	}
	else mxeBBcode(cmd);
}

function mxeBBcode(cmd)
{
	var tagname;
	switch (cmd)
	{
		case 'bold':
		case 'italic':
		case 'underline':
			wrapTag(cmd.substr(0, 1), false);
		break;
		case 'justifyleft':
		case 'justifycenter':
		case 'justifyright':
			wrapTag(cmd.substr(7), false);
		break;
		case 'subscript':
		case 'superscript':
			wrapTag(cmd.substr(0, 3), false);
		break;
		case 'insertorderedlist':
			wrapTag('list', '1');
		break;
		case 'insertunorderedlist':
			wrapTag('list', false);
		break;
		case 'inserthorizontalrule':
			wrapTag('hr', false, '');
		break;
		case 'indent':
		case 'quote':
		case 'emule':
		case 'hide':
			wrapTag(cmd, false);
		break;
		case 'fontname':
		case 'fontsize':
		case 'forecolor':
		case 'hilitecolor':
		case 'specialchar':
		case 'code':
			showPopuDiv(cmd);
		break;
		case 'insertlink':
			var linkurl = prompt(lang_e['info_inurl'],'http://');
			if (linkurl && linkurl != 'http://') wrapTag('url', linkurl);
		break;
		case 'unlink':
			var sel = getSelection();
			sel = stripBBcode('url', sel);
			sel = stripBBcode('url', sel, true);
			insertText(sel);
		break;
		case 'insertimage':
			var imgpath = prompt(lang_e['info_inimg'],'http://');
			if (imgpath && imgpath != 'http://') wrapTag('img', false, imgpath);
		break;
		case 'outdent':
			var sel = getSelection();
			sel = stripBBcode('indent', sel);
			insertText(sel);
		break;
		case 'removeformat':
			var needstrip = [['b', 'i', 'u'],['font', 'size', 'color', 'bgcolor']];
			var sel = getSelection();
			if (sel)
			{
				var n;
				for(n in needstrip[0]) sel = stripBBcode(needstrip[0][n], sel);
				for(n in needstrip[1]) sel = stripBBcode(needstrip[1][n], sel, true);
				insertText(sel);
			}
		break;
		case 'undo':
		case 'redo':
			if (isIE) mexcCommand(cmd);
		break;
		case 'cut':
		case 'copy':
			mexcCommand(cmd);
		break;
		case 'paste':
			setRange(mxe);
			insertText(mxeWin.clipboardData.getData('Text'));
		break;
		case 'inserttable': break;
		default:
			if (typeof mEBut == 'object')
			{
				for (var n in mEBut[0])
				{
					if (cmd == mEBut[0][n])
					{
						wrapTag(cmd, mEBut[2][n]);
						break;
					}
				}
			}
		break;
	}
}

function mexcCommand(cmd, dialog, option)
{
	try
	{
		mxeWin.focus();
		mxeWin.document.execCommand(cmd, dialog, option);
		mxeWin.focus();
	}
	catch (e) {}
}

function hidePopu()
{
	if (!popOpen) return;
	else
	{
		hideElement(popOpen);
		popOpen = '';
	}
}

function showPopuDiv(cmd)
{
	var popuDiv = $(mxe + 'pop' + cmd);
	if (popOpen)
	{
		hideElement(popuDiv);
		if (popOpen == popuDiv) return (popOpen = '');
	}
	var popBut = $(cmd + '_mxButton_' +mxe);
	var e = getPosition(popBut);
	var tmp = isIE ? 1 : 0;
	popuDiv.style.top = e.top + e.height + tmp + "px";
	popuDiv.style.left = e.left + tmp + "px";
	showElement(popuDiv);
	setRange(mxe);
	popOpen = popuDiv;
}

function showPopuWin(cmd)
{
	setRange(mxe);
	modalDialogShow(cmd,eDir + cmd + '.php','380','150');
}

function modalDialogShow(cmd,url,width,height)
{
	if (isIE)
	{
		var getValue = window.showModalDialog(url,window, "dialogWidth:"+width+"px;dialogHeight:"+height+"px;edge:Raised;center:1;help:0;resizable:1;maximize:1");
		if (getValue)
		{
			rng.select();
			rng.pasteHTML(getValue);
		}
	}
	else
	{
		var ffleft = screen.availWidth / 2 - width / 2;
		var fftop = screen.availHeight / 2 - height / 2;
		height -= 50;
		window.open(url, '', 'width=' + width + 'px,height=' + height + ',left=' + ffleft + ',top=' + fftop);
	}
}

function insert_smilies(id,theSmilie,theFile)
{
	if (!mxeTxa) mxeWin.focus();
	else mxeTxa.focus();
	setRange(mxe);
	if(wMode)
	{
		imgpath = ['<img src="images/smiles/', theFile, '" smilietext="', theSmilie, '" border="0" style="vertical-align:middle" alt="', theSmilie, '" /> '].join('');
		if (isIE)
		{
			rng.select();
			rng.pasteHTML(imgpath);
		}
		else mexcCommand('insertHTML', false, imgpath);
	}
	else insertText(' ' + theSmilie + ' ');
}

function insertattach(atid)
{
	if (!mxeTxa) mxeWin.focus();
	else mxeTxa.focus();
	setRange(mxe);
	if(wMode)
	{
		attach = " [aid::"+atid+"] ";
		if (isIE)
		{
			rng.select();
			rng.pasteHTML(attach);
		}
		else mexcCommand('insertHTML', false, attach);
	}
	else insertText(" [aid::"+atid+"] ");
}

function changeFont(e)
{
	e = e || window.event;
	var el = e.target || e.srcElement;
	if (!el.id && el.parentNode.id) el = el.parentNode;
	var cmd = el.id.replace('_sp_' + mxe, '').split('_sp_');
	if (wMode)
	{
		if (cmd[0] == 'code')
		{
			if (isIE) rng.select();
			wrapTag('code', cmd[1]);
			hidePopu();
			return;
		}
		if (isIE)
		{
			rng.select();
			if(cmd[0] == 'hilitecolor') cmd[0] = 'backcolor';
			else if (cmd[0] == 'specialchar')
			{
				rng.pasteHTML(cmd[1]);
				hidePopu();
				return;
			}
		}
		else if (cmd[0] == 'specialchar')  cmd[0] = 'insertHTML';
		if ((cmd[0] == 'forecolor' || cmd[0] == 'hilitecolor') && cmd[1] == 'X') cmd[1] = '';
		mexcCommand(cmd[0], false, cmd[1]);
	}
	else
	{
		if (isIE) rng.select();
		switch (cmd[0])
		{
			case 'fontname': wrapTag('font', cmd[1]); break;
			case 'fontsize': wrapTag('size', cmd[1]); break;
			case 'forecolor':
			case 'hilitecolor':
				var name = (cmd[0] == 'hilitecolor') ? 'bgcolor' : 'color';
				if (cmd[1] == 'X'){
					var sel = getSelection();
					sel = stripBBcode(name, sel, true);
					insertText(sel);
					break;
				}
				else wrapTag(name, cmd[1]);
			break;
			case 'specialchar': insertText(cmd[1]); break;
			case 'code': wrapTag('code', cmd[1]); break;
		}
	}
	hidePopu();
}

function resizeMxe(change)
{
	var newheight = mxeEbox.offsetHeight + change*100;
	if (newheight >= 100)
	{
		mxeEbox.style.height = newheight - 2 + 'px';
		mxeHeight = mxeHeight + change*100;
		$('eDiv_' + mxe).style.height = mxeHeight + 'px';
		setCookie('mxeHeight', mxeHeight);
	}
}

function getSelection()
{
	var selection;
	setRange(mxe);
	if (wMode)
	{
		if (isIE) selection = rng.htmlText;
		else selection = rng.toString();
	}
	else
	{
		if (isIE) selection = rng.text;
		else
		{
			if (mxeTxa.selectionEnd <= 2) mxeTxa.selectionEnd = mxeTxa.textLength;
			selection = (mxeTxa.value).substring(mxeTxa.selectionStart, mxeTxa.selectionEnd);
		}
	}
	if (selection === false) selection = '';
	else selection = new String(selection);
	return selection;
}

function insertText(text)
{
	if (wMode)
	{
		if (isIE)
		{
			rng.select();
			rng.pasteHTML(text);
		}
		else mexcCommand('insertHTML', false, text);
	}
	else
	{
		if (isIE)
		{
			rng.text = text.replace(/\r?\n/g, '\r\n');
			rng.select();
		}
		else
		{
			var start = (mxeTxa.value).substring(0, mxeTxa.selectionStart);
			var end = (mxeTxa.value).substring(mxeTxa.selectionEnd, mxeTxa.textLength);
			mxeTxa.value = start + text + end;
			var newsel = mxeTxa.selectionStart + (text.length);
			mxeTxa.selectionStart = newsel;
			mxeTxa.selectionEnd   = newsel;
		}
	}
}

function stripBBcode(tag, str, option)
{
	if (option == true) var opentag = '[' + tag + '=';
	else var opentag = '[' + tag + ']';
	var closetag = '[/' + tag + ']';
	while ((startindex = stripos(str, opentag)) !== false)
	{
		if ((stopindex = stripos(str, closetag)) !== false)
		{
			if (option == true)
			{
				var openend = stripos(str, ']', startindex);
				if (openend !== false && openend > startindex && openend < stopindex) var text = str.substr(openend + 1, stopindex - openend - 1);
				else break;
			}
			else var text = str.substr(startindex + opentag.length, stopindex - startindex - opentag.length);
			str = str.substr(0, startindex) + text + str.substr(stopindex + closetag.length);
		}
		else break;
	}
	return str;
}

function stripos(str, needle)
{
	var index = str.toLowerCase().indexOf(needle.toLowerCase(), 0);
	return (index == -1 ? false : index);
}

function wrapTag(tagname, useoption, content)
{
	tagname = tagname.toLowerCase();
	if (wMode) if (tagname == 'code') mexcCommand('removeformat');
	var selection = getSelection();
	if(typeof content != 'undefined' && content != true) selection = content;
	if (useoption === true)
	{
		var option = prompt(lang_e['info_ftinfo']+'[' + tagname + ']'+lang_e['info_hdinfo']+':','');
		if (option) var opentag = '[' + tagname + '="' + option + '"' + ']';
		else return false;
	}
	else if (useoption !== false) var opentag = '[' + tagname + '=' + useoption + '' + ']';
	else var opentag = '[' + tagname + ']';

	var closetag = '[/' + tagname + ']';
	if (tagname == 'url' && selection == '') selection = useoption;
	if (!wMode && tagname == 'list')
	{
		selection = selection.replace(/\n/g, '\n[*]');
		selection = '\n[*]'+selection+'\n';
	}
	var text = opentag + selection + closetag;
	insertText(text);
}

function setRange(mxe)
{
	if (isIE)
	{
		var selection = mxeWin.document.selection;
		if (selection != null) rng = selection.createRange();
	}
	else if (wMode)
	{
		var selection = mxeWin.getSelection();
		rng = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
	}
}

function stripHTML(oldString)
{
	return trim(oldString.replace(/(<([^>]+)>)/ig,'').replace(/\r\n/g, ' ').replace(/\n/g, ' ').replace(/\r/g, ' '));
}

function trim(str)
{
	if (typeof(str) != 'string') return str;
	return str.replace(/(\s+)$/g, '').replace(/^\s+/g, '');
}

function ifkeyPress(evt)
{
	var key = evt.keyCode || evt.charCode;
	if (evt.ctrlKey)
	{
		if ((isIE && key == 10) || (!isIE && key == 13))
		{
			var button= $('submitform');
			if (button)
			{
				mxeGet();
				button.click();
			}
		}
		else
		{
			var cmd = '';
			switch (key)
			{
				case 98: cmd = 'bold'; break;
				case 105: cmd = 'italic'; break;
				case 117: cmd = 'underline'; break;
			}

			if (cmd)
			{
				mexcCommand(cmd);
				cancelEvent(evt, true);
			}
		}
	}
	else if (isIE && key == 13)
	{
		setRange(mxe);
		if (rng.parentElement && rng.parentElement().tagName.toLowerCase() != 'li')
		{
			rng.pasteHTML('<br />');
			rng.collapse(false);
			rng.select();
			//cancelEvent(e, true);
			cancelEvent(true);
			return false;
		}
	}
}

function noKeyPress(evt)
{
	var key = evt.keyCode || evt.charCode;
	if (evt.ctrlKey && ((isIE && key == 10) || (!isIE && key == 13)))
		$('submitform').click();
}