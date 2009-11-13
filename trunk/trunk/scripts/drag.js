// $Id: drag.js 64 2007-09-07 09:19:11Z hogesoft-02 $
var drag = {
dragging: false, obj: null, areas: [], els: [], layout: null,
lastX: 0, lastY: 0, placeholder: null, obj: null,

start: function(evt, disable)
{
	if (drag.obj) drag.obj.parentNode.removeChild(drag.obj);
	evt = evt || window.event;
	if ((evt.button || evt.which) != 1) return;
	var obj = evt.target || evt.srcElement;

	var id, aId, disableArea, allowArea, className = ' ' + obj.className + ' ';
	if (className.indexOf(' drag_remove ') > -1) return;
	if (obj.nodeType != 1 || (className.indexOf(' drag_bar ') < 0 && (' ' + obj.parentNode.className + ' ').indexOf(' drag_bar ') > -1)) obj = obj.parentNode;
	if (!(id = obj.getAttribute('drag_box_id')) ||
		!(obj = $(id)) ||
		!(aId = obj.getAttribute('drag_area_id')) ||
		!(disableArea = obj.getAttribute('disable_area')) ||
		!(allowArea = obj.getAttribute('allow_area'))
	) return;

	drag.dragging = true;
	if(isIE) document.body.onselectstart = function() {return false;};

	var p = getPosition(obj);
	drag.placeholder.style.width = p.width + 'px';
	drag.placeholder.style.height = p.height + 'px';
	obj.parentNode.insertBefore(drag.placeholder, obj);

	drag.obj = obj.cloneNode(true);
	drag.obj.setAttribute('id', 'drag_clone_box');
	drag.obj.style.width = p.width + 'px';
	drag.obj.style.height = p.height + 'px';
	drag.obj.style.top = p.top + 'px';
	drag.obj.style.left = p.left + 'px';
	drag.obj.eId = id;
	drag.obj.aId = aId;
	drag.obj.disableArea = disableArea;
	drag.obj.allowArea = allowArea;
	document.body.appendChild(drag.obj);

	hideElement(obj);
	showElement(drag.placeholder);
	drag.lastX = p.left - getMouseX(evt);
	drag.lastY = p.top - getMouseY(evt);
	cancelEvent(evt);
},

move: function(evt)
{
	if (!drag.dragging || !drag.obj) return;
	hideElement(drag.placeholder);
	var x = getMouseX(evt);
	var y = getMouseY(evt);

	var obj = drag.obj;
	obj.style.left = (drag.lastX + x) + 'px';
	obj.style.top = (drag.lastY + y) + 'px';

	var insertType = '', pattern = /(?:^|\s)drag-insert-(top|bottom|)(?:\s|$)/;
	var found = null;
	for (var i in drag.areas)
	{
		var area = drag.areas[i];
		var aPos = getPosition(area);
		if (x >= aPos.left && x <= (aPos.left + aPos.width) && y >= aPos.top && y <= (aPos.top + aPos.height))
		{
			if ((obj.disableArea != '--' && obj.disableArea.indexOf('-' + i + '-') > -1) ||
				(obj.allowArea != '--' && (obj.allowArea == '-none-' || obj.allowArea.indexOf('-' + i + '-') < 0))) break;

			if (pattern.test(area.className)) insertType = RegExp.$1;
			switch (insertType)
			{
				case 'top':
					drag.placeholder.style.width = '';
					area.insertBefore(drag.placeholder, area.firstChild);
				break;
				case 'bottom':
					drag.placeholder.style.width = '';
					area.appendChild(drag.placeholder);
				break;
				default:
					for (var j in drag.els[i])
					{
						var el = drag.els[i][j];
						var ePos = getPosition(el);
						if (x >= ePos.left && x <= (ePos.left + ePos.width) && y >= ePos.top && y <= (ePos.top + ePos.height))
						{
							found = el;
							break;
						}
					}
					if (!found)
					{
						drag.placeholder.style.width = '';
						area.appendChild(drag.placeholder);
					}
					else if (drag.placeholder.nextSibling != found)
					{
						drag.placeholder.style.width = el.style.width;
						found.parentNode.insertBefore(drag.placeholder, found);
					}
			}
			drag.obj.newAId = i;
			break;
		}
	}
	showElement(drag.placeholder);
},

end: function()
{
	if (isIE) document.body.onselectstart = function() {return true;};
	if(!drag.dragging || !drag.obj) return;
	drag.dragging = false;

	var aId = drag.obj.aId;
	var eId = drag.obj.eId;
	var newAId = drag.obj.newAId;
	var obj = $(eId);

	drag.placeholder.parentNode.insertBefore(obj, drag.placeholder);
	showElement(obj);
	hideElement(drag.placeholder);
	document.body.appendChild(drag.placeholder);
	drag.obj.parentNode.removeChild(drag.obj);
	drag.obj = null;
	if (newAId && newAId != aId)
	{
		obj.setAttribute('drag_area_id', newAId);
		drag.els[newAId][eId] = drag.els[aId][eId];
		delete drag.els[aId][eId];
	}
},

remove: function(evt)
{
	evt = evt || window.event;
	var ids, obj = evt.target || evt.srcElement;
	if (!(ids = obj.getAttribute('drag_all_id'))) return;
	ids = ids.split('-');
	var aId = ids[0], eId = ids[1];
	obj = drag.els[aId][eId];
	obj.parentNode.removeChild(obj);
	delete drag.els[aId][eId];
},

getSort: function()
{
	var sortAr = [];
	var pattern = /(?:^|\s)drag_box (?:a|d)-(?:[^\s]+)(?:\s|$)/;
	for (var i in drag.areas)
	{
		var area = drag.areas[i];
		var els = area.getElementsByTagName('*');
		var n = els.length;
		var ar = [];
		for (var j = 0; j < n; j++)
		{
			var el = els[j];
			if (!(eId = el.id)) continue;
			else if (el.className && el.className.match(pattern))
				ar.push(eId);
		}
		sortAr.push(i + ':' + ar.join(','));
	}
	return sortAr.join(';');
},

init: function()
{
	if (!document.getElementsByTagName) return;
	var pattern = /(?:^|\s)drag_box (a|d)-([^\s]+)(?:\s|$)/;
	var allowArea, disableArea, area, eId;

	if (!dragAreas) return;
	if (!(drag.placeholder = $('drag_placeholder'))) return;
	var i, m = dragAreas.length;
	for (i = 0; i < m; i++)
	{
		var aId = dragAreas[i];
		if (!(area = $(aId))) continue;
		drag.areas[aId] = area;
		drag.els[aId] = [];
		var els = area.getElementsByTagName('*');
		var j, n = els.length;
		for (j = 0; j < n; j++)
		{
			var el = els[j];
			if (!(eId = el.id)) continue;
			else if (el.className && pattern.test(el.className))
			{
				if (RegExp.$1 == 'a')
				{
					if (RegExp.$2 == 'none') allowArea = 'none';
					else allowArea = RegExp.$2;
					disableArea = '';
				}
				else if (RegExp.$1 == 'd')
				{
					if (RegExp.$2 == 'none') disableArea = '';
					else disableArea = RegExp.$2;
					allowArea = '';
				}
				el.setAttribute('disable_area', '-' + disableArea + '-');
				el.setAttribute('allow_area', '-' + allowArea + '-');
				el.setAttribute('drag_area_id', aId);
				drag.els[aId][eId] = el;
				var cls = el.getElementsByTagName('*');
				var k, o = cls.length;
				for (k = 0; k < o; k++)
				{
					var cl = cls[k];
					var className = ' ' + cl.className + ' ';
					if (className)
					{
						if (className.indexOf(' drag_bar ') > -1)
						{
							cl.setAttribute('drag_box_id', eId);
							addEvent(cl, 'mousedown', drag.start);
						}
						else if (className.indexOf(' drag_remove ') > -1)
						{
							cl.setAttribute('drag_all_id', aId + '-' + eId);
							addEvent(cl, 'click', drag.remove);
						}
					}
				}
			}
		}
	}
	addEvent(document, 'mousemove', drag.move);
	addEvent(document, 'mouseup', drag.end);
}};

domReady(drag.init);