// $Id$
function divTitles(delay)
{
	var timer, divObj, titleCache = [], isActive = false;
	if (!delay) delay = 600;
	if (!(divObj = $('divtitle_content'))) return;
	if (isIE) iframeObj = $('divtitle_iframe');

	this.addElements = function addElements(tagName, attribute)
	{
		var node, title, i;
		var nodes = document.getElementsByTagName(tagName);
		var n = nodes.length;
		for (i = 0; i < n; i++)
		{
			node = nodes[i];
			title = node.getAttribute(attribute);
			if (title)
			{
				node.setAttribute('divtitle', title.replace(/\n/g, '<br />'));
				node.setAttribute('divtitle_id', tagName + '_'+ i);
				node.removeAttribute(attribute);
				addEvent(node, 'mouseover', show);
				addEvent(node, 'mouseout', hide);
				addEvent(node, 'focus', show);
				addEvent(node, 'blur', hide);
				addEvent(node, 'keypress', hide);
			}
		}
	}

	function show(evt)
	{
		evt = evt || window.event;
		if (isActive) hide();
		var node = evt.target || evt.srcElement;
		if (!node.getAttribute('divtitle'))
		{
			while (node.parentNode && node != document.body)
			{
				node = node.parentNode;
				if (node.getAttribute('divtitle'))
				{
					break;
				}
			}
			if (node == document.body)
			{
				return;
			}
		}

		divObj.innerHTML = parseTitle(node);

		//if (evt.type == 'focus')
		//{
			var aL = getPosition(node);
			var position = {x: aL.left, y: (aL.top + aL.height)};
		//}
		//else var position = {x: getMouseX(evt), y: getMouseY(evt)};

		showElement(divObj, 'block', 'hidden');
		var divWidth = divObj.offsetWidth;
		var divHeight = divObj.offsetHeight;
		hideElement(divObj);

		position.x += 5;
		position.y += 10;
		var maxX = getBodyWidth() - divWidth;
		var maxY = getBodyHeight() - divHeight;
		if (position.x > maxX) position.x = maxX;
		if (position.y > maxY) position.y = maxY;

		divObj.style.left = position.x + 'px';
		divObj.style.top = position.y + 'px';

		if (isIE)
		{
			iframeObj.style.left = position.x + 'px';
			iframeObj.style.top = position.y + 'px';
			iframeObj.style.width = divWidth + 'px';
			iframeObj.style.height = divHeight + 'px';
		}

		if (delay) timer = setTimeout(showDiv, delay);
		else showDiv();

		isActive = true;
		cancelEvent(evt);
	}

	function showDiv()
	{
		showElement(divObj);
		if (isIE) showElement(iframeObj);
	}

	function hide()
	{
		clearTimeout(timer);
		hideElement(divObj);
		if (isIE) hideElement(iframeObj);
		divObj.innerHTML = '';
		isActive = false;
	}

	function parseTitle(node)
	{
		var nodeId = node.getAttribute('divtitle_id');
		if (node.getAttribute('dotitle') != null || isOpera)
		{
			titleCache[nodeId] = '';
			node.removeAttribute('dotitle');
		}
		if (!titleCache[nodeId])
		{
			var attribute, collOptionalAttributes, i, j, n, o;
			var found = {};
			var result = node.getAttribute('divtitle').replace(/&quot;/g,'"').replace(/&#39;/g,'\'').replace(/&/g, '&amp;').replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/&lt;br \/&gt;/g,'<br />');
			result = '<p>' + result + '</p>';
			switch (node.tagName.toLowerCase())
			{
				case 'a':
					result += '<p class="destination">' + node.getAttribute('href') + '</p>';
				break;
			}
			titleCache[nodeId] = result;
		}
		return titleCache[nodeId]
	}
}
divTitles.autoCreation = function()
{
	if (!document.getElementsByTagName) return;
	divTitles.ref = new divTitles();
	divTitles.ref.addElements('a', 'title');
	divTitles.ref.addElements('div', 'title');
	divTitles.ref.addElements('img', 'alt');
	divTitles.ref.addElements('td', 'title');
	divTitles.ref.addElements('span', 'title');
	divTitles.ref.addElements('input', 'title');
}

domReady(divTitles.autoCreation);