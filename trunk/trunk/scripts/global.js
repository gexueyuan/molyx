var uagent = navigator.userAgent.toLowerCase();
var isOpera = (uagent.indexOf('opera') != -1 || typeof(window.opera) != 'undefined');
var isSafari = (uagent.indexOf('applewebkit') != -1 || navigator.vendor == 'Apple Computer, Inc.');
var isWebtv = (uagent.indexOf('webtv') != -1);
var isIE = (uagent.indexOf('msie') != -1 && !isOpera && !isSafari && !isWebtv);
var isGecko = (navigator.product == 'Gecko' && !isSafari);
var isMoz = isGecko;
var isKonqueror = (uagent.indexOf('konqueror') != -1);
var isNs = (uagent.indexOf('compatible') == -1 && uagent.indexOf('mozilla') != -1 && !isOpera && !isWebtv && !isSafari);
var isMac = (uagent.indexOf('mac') != -1);
var uaVers = parseInt(navigator.appVersion);

function appendSid(uri)
{
	if (uri.indexOf('s=') < 0 && typeof(sessionid) != 'undefined' && sessionid)
		uri += ((uri.indexOf('?') > -1) ? '&' : '?') + 's=' + sessionid;
	return uri;
}

function $()
{
	var elements = [];
	var len = arguments.length;
	if (len == 1) return getbyId(arguments[0]);
	else for (var i = 0; i < len; i++) elements.push(getbyId(arguments[i]));
	return elements;
}

function getbyId(el)
{
	if (typeof el == 'string') el = document.getElementById(el);
	return el;
}

function insertAfter(newElm, targetElm)
{
	if(!(newElm = $(newElm))) return;
	if(!(targetElm = $(targetElm))) return;
	var parent = newElm.parentNode;
	if (parent.lastChild == targetElm) parent.appendChild(newElm);
	else parent.insertBefore(newElm, targetElm.nextSibling);
}

var addEvent = function(o, t, f)
{
	var d = 'addEventListener', n = 'on' + t, rO = o, clean = (t != 'unload');
	if (o[d]) o[d](t, f, false);
	else if (o.attachEvent) o.attachEvent(n, f);
	else
	{
		clean = false;
		if (!o._evts) o._evts = {};
		if (!o._evts[t])
		{
			o._evts[t] = o[n] ? { b: o[n] } : {};
			o[n] = new Function('e', 'var r = true, o = this, a = o._evts["' + t + '"], i; for (i in a) { o._f = a[i]; r = o._f(e||window.event) != false && r; o._f = null; } return r');
			if (t != 'unload') clean = true;
		}
		if (!f._i) f._i = addEvent._i++;
		o._evts[t][f._i] = f;
	}
	if (clean) addEvent(window, 'unload', function(){removeEvent(rO, t, f);});
};
addEvent._i = 1;

var removeEvent = function(o, t, f)
{
	var d = 'removeEventListener';
	try
	{
		if (o[d]) o[d](t, f, false);
		else if (o.detachEvent) o.detachEvent('on' + t, f);
		else if (o._evts && o._evts[t] && f._i) delete o._evts[t][f._i];
	} catch (e) {}
};

function cancelEvent(e, c)
{
	e.returnValue = false;
	if (e.preventDefault) e.preventDefault();
	if (c)
	{
		e.cancelBubble = true;
		if (e.stopPropagation) e.stopPropagation();
	}
}

var domFunc = [], domTimer = null, domCount = 0;;
function domReady(f)
{
	domFunc.push(f);
	if (domTimer == null)
	{
		domTimer = setInterval(function()
		{
			var c = true;
			domCount++;
			if (document.getElementsByTagName && (document.getElementsByTagName('body')[0] != null || document.body != null))
			{
				var n = domFunc.length;
				try { for (var i = 0; i < n; i++) domFunc[i](); }
				catch(e) { alert(e); }
				clearInterval(domTimer);
			}
			if (domCount >= 60) clearInterval(domTimer);
		}, 250);
	}
}

function getStyle(el, prop)
{
	if (!(el = $(el))) return;
	if (el.currentStyle) return el.currentStyle[prop];
	else if (document.defaultView.getComputedStyle) return document.defaultView.getComputedStyle(el, '')[prop];
	return null;
}

function addClass(el, name, old)
{
	if(!(el = $(el))) return;
	if (!el.className) return (el.className = name);
	var value, pattern = new RegExp('(^|\s)' + old + '(\s|$)');
	if (old && (value = el.className.match(pattern)))
		el.className = el.className.replace(value[1] + old + value[2], value[1] + name + value[2]);
	else el.className += ' ' + name;
}

function inArray(value, array)
{
	for (var i in array) if (array[i] == value) return i;
	return false;
};

function trim (str)
{
	if (typeof(str) != 'string') return str;
	return str.replace(/(\s+)$/g, '').replace(/^\s+/g, '');
}

function urlencode(text)
{
	if (typeof(encodeURIComponent) != 'undefined')
	{
		text = encodeURIComponent(text);
		text = text.replace(/\!/g, '%21');
		text = text.replace(/\'/g, '%27');
		text = text.replace(/\(/g, '%28');
		text = text.replace(/\)/g, '%29');
		text = text.replace(/\*/g, '%2A');
		text = text.replace(/\~/g, '%7E');
	}
	return text;
}

function sprintf()
{
	var ret = arguments[0];
	if (ret)
	{
		var reg = /%s/g;
		var i = 0, l = 0, arr, n = arguments.length;
		while(arr = reg.exec(ret))
		{
			if (++i > n) return null;
			var tmp = arguments[i].toString();
			ret = ret.replace(arr[0], tmp);
		}
		return ret;
	}
	return null;
}

function setCookie(name, value, expires)
{
	var today = new Date();
	today.setTime(today.getTime());
	if (expires) expires = expires * 86400000;
	var expires_date = new Date(today.getTime() + expires);
	document.cookie = ((cookie_id) ? cookie_id : '') +
		name + '=' + urlencode(value) +
		((expires) ? ';expires=' + expires_date.toGMTString() : '') +
		((cookie_path) ? ';path=' + cookie_path : '') +
		((cookie_domain) ? ';domain=' + cookie_domain : '');
		//+ ((cookie_secure) ? ';secure' : '');
}

function getCookie(name)
{
	name = cookie_id + name;
	var start = document.cookie.indexOf(name + '=');
	var len = name.length;
	if (start == -1 || (!start && name != document.cookie.substring(0, len))) return null;
	len += start + 1;
	end = document.cookie.indexOf(';', len);
	if (end == -1) end = document.cookie.length;
	return unescape(document.cookie.substring(len, end));
}

function toggle(id)
{
	if (!id) return;
	if (el = $(id))
	{
		if (getStyle(el, 'display') == 'none' || getStyle(el, 'visibility') == 'hidden')
			showElement(el);
		else hideElement(el);
	}
}

function showElement(el, dtype, vtype)
{
	if (!(el = $(el))) return;
	el.style.display = (!dtype) ? 'block' : dtype;
	el.style.visibility = (!vtype) ? 'visible' : vtype;
}

function hideElement(el)
{
	showElement(el, 'none', 'hidden');
}

function removeNode(el)
{
	if (!(el = $(el))) return;
	if (isIE) el.removeNode(true);
	else el.parentNode.removeChild(el);
}

function getPosition(e)
{
	if(!(e = $(e))) return;
	var top = e.offsetTop, left = e.offsetLeft, width = e.offsetWidth, height = e.offsetHeight;
	while (e = e.offsetParent)
	{
		if (e.style.position == 'absolute' || e.style.position == 'relative' || (e.style.overflow != 'visible' && e.style.overflow != '')) break;
		top += e.offsetTop;
		left += e.offsetLeft;
	}
	return {top: top, left: left, width: width, height: height};
}

var dBody = null;
function getBody()
{
	if (!dBody)
		dBody = (document.compatMode && document.compatMode.indexOf('CSS') > -1) ? document.documentElement : document.body;
	return dBody;
}
function getScrollX()
{
	return window.pageXOffset || window.scrollX || getBody().scrollLeft || 0;
}
function getScrollY()
{
	return window.pageYOffset || window.scrollY || getBody().scrollTop || 0;
}
function getMouseX(e)
{
	return e.pageX || e.clientX + getScrollX() - document.documentElement.clientLeft - document.body.clientLeft || 0;
}
function getMouseY(e)
{
	return e.pageY || e.clientY + getScrollY() - document.documentElement.clientTop - document.body.clientTop || 0;
}
function getBodyWidth()
{
	return getBody().scrollWidth || window.innerWidth || getBody().clientWidth || 0;
}
function getBodyHeight()
{
	return window.innerHeight || getBody().clientHeight || 0;
}

function PopUp(url, name, width, height, center, resize, scroll, posleft, postop,del)
{
	var showx = '', showy = '';
	var X,Y;
	if(typeof del == 'underfined')
	{
		if (!confirm( lang_g['g_delt']))
		{
			return false;
		}
	}
	if (posleft != 0) X = posleft;
	if (postop  != 0) Y = postop;
	if (!scroll) scroll = 1;
	if (!resize) resize = 1;
	if ((parseInt (navigator.appVersion) >= 4 ) && center)
	{
		X = (screen.width  - width ) / 2;
		Y = (screen.height - height) / 2;
	}
	if (X > 0) showx = ',left='+X;
	if (Y > 0) showy = ',top='+Y;
	if (scroll != 0) scroll = 1;
	var Win = window.open( url, name, 'width='+width+',height='+height+ showx + showy + ',resizable='+resize+',scrollbars='+scroll+',location=no,directories=no,status=no,menubar=no,toolbar=no');
}

function CheckAll(fmobj)
{
	var len = fmobj.elements.length;
	for (var i = 0; i < len; i++)
	{
		var e = fmobj.elements[i];
		if (e.name != 'allbox' && e.type == 'checkbox' && !e.disabled)
			e.checked = fmobj.allbox.checked;
	}
}

function highlightAll(str)
{
    if (isIE)
    {
        var rng = document.body.createTextRange();
        rng.moveToElementText(str);
        rng.scrollIntoView();
        rng.select();
        rng.execCommand("Copy");
        rng.collapse(false);
		setTimeout("window.status=''", 1800);
    }
}

function redirlocate(object)
{
	if (object.options[object.selectedIndex].value != '')
		window.location.href = appendSid(object.options[object.selectedIndex].value);
}

function changeMod(Mode)
{
	if (Mode == 2) return false;
	else if (Mode == 0) setCookie('mxeditor', 'bbcode');
	else setCookie('mxeditor', 'wysiwyg');
	var div_mxeditor = $('mxeditorinfo');
	div_mxeditor.innerHTML = lang_g['g_edv'];
}

function SelectTag(tag)
{
	if(typeof tag == 'undefined')
	{
		tag = 'tr';
	}
	var target = $('selectall').checked;
	if (target == true) SelectAll(tag);
	else NoneAll(tag);
}

function SelectAll(tag)
{
	if(typeof tag == 'undefined') tag = 'tr';
	var rows = document.modform.getElementsByTagName(tag);
	var unique_id, checkbox, marked_row = [];
	var len = rows.length;
	for (var i = 0; i < len; i++)
	{
        checkbox = rows[i].getElementsByTagName('input')[0];
        if (checkbox && checkbox.type == 'checkbox')
        {
            unique_id = checkbox.name + checkbox.value;
            if (checkbox.disabled == false)
            {
                checkbox.checked = true;
                if (typeof(marked_row[unique_id]) == 'undefined' || !marked_row[unique_id])
                {
                    rows[i].className += ' marked';
                    marked_row[unique_id] = true;
                }
            }
	    }
	}
}

function NoneAll(tag)
{
	if(typeof tag == 'undefined') tag = 'tr';
	var rows = document.modform.getElementsByTagName(tag);
    var unique_id, checkbox, marked_row = [];
	var len = rows.length;
	for (var i = 0; i < len; i++)
	{
        checkbox = rows[i].getElementsByTagName( 'input' )[0];
        if (checkbox && checkbox.type == 'checkbox')
        {
            unique_id = checkbox.name + checkbox.value;
            checkbox.checked = false;
            rows[i].className = rows[i].className.replace(' marked', '');
            marked_row[unique_id] = false;
        }
	}

	return true;
}

function calculate_byte(str)
{
	if (typeof(wMode) != 'undefined' && wMode)
	{
		str = str.replace(/<img( ||.*?)smilietext=(\'|\"|)(.*?)(\'|\"|>| )(.*?)>/gi, "$3");
		str = str.replace(/<img( ||.*?)src=(\'|\"|)(.*?)(\'|\"|>| )(.*?)>/gi, "[img]$3[/img]");
		str = str.replace(/<[\/\!]*?[^<>]*?>/g, '');
		str = str.replace(/&amp;/g, '1');
		str = str.replace(/&lt;/g, '1');
		str = str.replace(/&gt;/g, '1');
	}
	return str.length;
}

function multi_page_jump(url_bit, totalposts, perpage)
{
	var pages = 1, curpage = 1, show_page = 1;
	if (totalposts % perpage == 0) pages = totalposts / perpage;
	else pages = Math.ceil(totalposts / perpage);
	var msg = lang_g['g_intm'] + pages;
	if (current_page > 0) curpage = (current_page / perpage) - 1;
	if (curpage < pages) show_page = curpage + 1;
	else show_page = curpage - 1;
	var start, userPage = window.prompt(msg, show_page);
	if (userPage > 0)
	{
		if (userPage < 1) userPage = 1;
		if (userPage > pages) userPage = pages;
		if (userPage == 1) start = 0;
		else start = (userPage - 1) * perpage;
		window.location.href = appendSid(url_bit) + '&pp=' + start;
	}
}

function toCenter(el)
{
	if (!(el = $(el))) return;
	el.style.left = getScrollX() + (getBodyWidth() - el.offsetWidth) / 2 + 'px';
	el.style.top = 50 + getScrollY() + 'px';
}

function begindrag(otarget, e)
{
	otarget = $(otarget);
	e = e || window.event;

	var diffX = getMouseX(e) - getScrollX() - parseInt(otarget.style.left);
	var diffY = getMouseY(e) - getScrollY() - parseInt(otarget.style.top);
	addEvent(document, 'mousemove', movehandler);
	addEvent(document, 'mouseup', uphandler);
	cancelEvent(e, true);

	function movehandler(e)
	{
		e = e || window.event;
		var x = getMouseX(e) - getScrollX() - diffX;
		var y = getMouseY(e) - getScrollY() - diffY;

		if ( !isIE && (x < 0 || x > (getBodyWidth() - otarget.offsetWidth - 15) || y < 0))
			return false;

		otarget.style.left = x + "px";
		otarget.style.top = y + "px";
		cancelEvent(e, true);
	}

	function uphandler(e)
	{
		e = e || window.event;
		removeEvent(document, 'mousemove', movehandler);
		removeEvent(document, 'mouseup', uphandler);
		cancelEvent(e, true);
	}
}

function resizeImage(imgObj, iwidth, iheight)
{
	var image = new Image();
	image.src = imgObj.src;
	if (image.width > 0 && image.height > 0)
	{
		if (typeof iheight != 'undefined')
		{
			if (image.width / image.height >= iwidth / iheight)
			{
				if (image.width > iwidth)
				{
					imgObj.width = iwidth;
					imgObj.height = (image.height * iwidth) / image.width;
				}
				else
				{
					imgObj.width = image.width;
					imgObj.height = image.height;
				}
			}
			else
			{
				if (image.height > iheight)
				{
					imgObj.height = iheight;
					imgObj.width = (image.width * iheight) / image.height;
				}
				else
				{
					imgObj.width = image.width;
					imgObj.height = image.height;
				}
			}
		}
		else if (image.width > iwidth)
		{
			imgObj.width = iwidth;
			imgObj.height = (image.height * iwidth) / image.width;
		}
		imgObj.alt = image.width + 'x' + image.height;
	}
}

function change_forum_style(id)
{
	$('forum_styleid').value = id;
	$('forum_foot_form').submit();
}

function change_style(styleid)
{
	var file = 'images/style_' + styleid + '/style.css';
	load_cssfile('link_css_style', file);
	setCookie('styleid', styleid);
	for (var i=3; i <= 5; i++)
	{
		if (i == styleid) $('switch_style_' + i).style.border = '#000 1px solid';
		else $('switch_style_' + i).style.border = '#888 1px solid';
	}
}
function load_cssfile(id, file)
{
    var head = document.getElementsByTagName('head').item(0);
    var csstag = $(id);
    if (csstag) head.removeChild(csstag);
    var css = document.createElement('link');
    css.href = file;
    css.rel = 'stylesheet';
    css.type = 'text/css';
    css.id = id;
    head.appendChild(css);
}