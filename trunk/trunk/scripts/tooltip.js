// $Id: tooltip.js 64 2007-09-07 09:19:11Z hogesoft-02 $
function toolTip()
{
	var defaults = {mouseX: 0, mouseY: 0, tts: {}, rootElm: null, onshow: null, onhide: null};
	for (var p in defaults) this[p] = defaults[p];
	var obj = this;
	addEvent(document, 'mouseover', function(e){obj.mouseHandler(e, 1);});
	addEvent(document, 'click', function(e){obj.mouseHandler(e, 2);});
	addEvent(document, 'mouseout', function(e){obj.mouseHandler(e, 0);});
	toolTip.instances = this;
}
toolTip.instances = {};
toolTip.dBody = null;
toolTip.prototype = {
bfun : {manual : function(obj, ttId, nextVis) {	return (!nextVis) ? false : true; }},
pfun : {
	mouse : function(obj, ttId, nextVis, nextAnim)
	{
		var tt = obj.tts[ttId];
		if (nextVis && !tt.processing && !tt.visible)
		{
			tt.ref.style.left = obj.mouseX + 'px';
			tt.ref.style.top = obj.mouseY + 'px';
		}
	},
	trigger : function(obj, ttId, nextVis, nextAnim)
	{
		var tt = obj.tts[ttId];
		if (nextVis && !tt.processing && !tt.visible)
		{
			var e = getPosition(tt.trigRef);
			tt.ref.style.left = e.left + 'px';
			tt.ref.style.top = e.top + 'px';
		}
	}
},

mouseHandler : function(evt, show)
{ with (this) {
	if (!document.documentElement) return true;
	evt = evt || window.event;
	if (show)
	{
		mouseX = getMouseX(evt);
		mouseY = getMouseY(evt);
	}

	var srcElm = evt.target || evt.srcElement,
	targRE = /^tooltip-content-([a-z_\-0-9]+)$/,
	trigRE = /(?:^|\s)tooltip-(hover|click)-([a-z_\-0-9]+)(?:\s|$)/,
	trigFind = 1, foundNotes = {};

	if (srcElm.nodeType != 1) srcElm = srcElm.parentNode;
	var elm = srcElm, trigRECheck;
	while (elm && elm != rootElm)
	{
		trigRECheck = trigRE.test(elm.className)
		if (targRE.test(elm.id) || (trigFind && trigRECheck))
		{
			trigFind = 0;
			var click = (RegExp.$1 == 'click') ? 1 : 0,
			ttId = RegExp.$2,
			ref = $('tooltip-content-' + ttId);

			if (ref)
			{
				trigRef = trigRECheck ? elm : null;
				if (!tts[ttId])
				{
					tts[ttId] = {click: click, ref: ref, trigRef : null, visible: 0, processing: 0, timer: null};
					ref._sn_obj = this;
					ref._sn_id = ttId;
				}
				var tt = tts[ttId];

				if (!tt.click || (trigRef != srcElm)) foundNotes[ttId] = true;

				if (!tt.click || (show == 2))
				{
					if (trigRef) tt.trigRef = tt.ref._sn_trig = elm;
					display(ttId, show);
					if (tt.click && (srcElm == trigRef)) cancelEvent(evt);
				}
			}
		}

		if (elm._sn_trig)
		{
			trigFind = 1;
			elm = elm._sn_trig;
		}
		else elm = elm.parentNode;
	}

	if (show == 2)
	{
		for (var n in tts)
			if (tts[n].click && tts[n].visible && !foundNotes[n]) display(n, 0);

		if ((/(?:^|\s)tooltip-close(?:\s|$)/).test(srcElm.className))
		{
			if ((/(?:^|\s)ttb-pinned(?:\s|$)/).test(ref.className))
			{
				setTimeout('toolTip.instances.setVis("' + ttId + '", false, true)', 100);
				cancelEvent(evt);
			}
		}
	}
}},

display : function(ttId, show)
{ with (this) {
	with (tts[ttId])
	{
		clearTimeout(timer);
		if (!processing || (show ? !visible : visible))
		{
			var tmt = processing ? 1 : (show ? 1 : 500);
			timer = setTimeout('toolTip.instances.setVis("' + ttId + '", ' + show + ', false)', tmt);
		}
	}
}},

checkType : function(ttId, nextVis, nextAnim)
{ with (this) {
	var tt = tts[ttId], bType, pType;
	var className = tt.ref.className;
	if ((/(?:^|\s)ttp-([a-z]+)(?:\s|$)/).test(className)) pType = RegExp.$1;
	if ((/(?:^|\s)ttb-([a-z]+)(?:\s|$)/).test(className)) bType = RegExp.$1;
	if (nextAnim && bType && bfun[bType] && (bfun[bType](this, ttId, nextVis) == false)) return false;
	if (pType && pfun[pType]) pfun[pType](this, ttId, nextVis, nextAnim);
	return true;
}},

setVis : function(ttId, show, now)
{ with (this) {
	var tt = tts[ttId];
	if (tt && checkType(ttId, show, 1) || now)
	{
		tt.visible = show;
		tt.processing = 1;
		if (!tt.animC) tt.animC = 0;
		if (show && !tt.animC)
		{
			if (onshow) this.onshow(ttId);
			if (isIE) ieFrameFix(ttId, 1);
			showElement(tt.ref);
		}

		if (!show)
		{
			tt.animC = 0;
			if (onhide) this.onhide(ttId);
			if (isIE) ieFrameFix(ttId, 0);
			hideElement(tt.ref);
		}
		else tt.animC = 1;

		checkType(ttId, show, 0);
		tt.processing = 0;
	}
}},

ieFrameFix : function(ttId, show)
{ with (this) {
	var tt = tts[ttId], fm = tt.iframe, r = tt.ref;;
	if (!fm)
	{
		var iframeHtml = '<iframe id="tooltip-iframe-' + ttId + '" style="filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0); position: absolute; border-width: 0></iframe>"'
		r.parentNode.innerHTML += iframeHtml;
		fm = tt.iframe = $('tooltip-iframe-' + ttId);
	}

	if (show)
	{
		fm.style.left = r.offsetLeft + 'px';
		fm.style.top = r.offsetTop + 'px';
		fm.style.width = r.offsetWidth + 'px';
		fm.style.height = r.offsetHeight + 'px';
		showElement(fm);
	}
	else hideElement(fm);
}}
};
new toolTip();