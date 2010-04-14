// $Id$
var leftPanelWidth = 150;
var moveMainContent = true;

var leftPanelDiv = null, scrollPos = false, bodyMargin = '0px';
function leftPanel(evt, expandOnly)
{
	evt = evt || window.event;
	var init = false;
	if (!leftPanelDiv)
	{
		if (!(leftPanelDiv = $('mx_left_panel'))) return;
		leftPanelDiv.style.left = '-' + leftPanelWidth + 'px';
		leftPanelDiv.style.top = '0px';
		leftPanelDiv.style.width = leftPanelWidth + 'px';
		if (!isIE) leftPanelDiv.style.position = 'fixed';
		init = true;
	}

	resizeLeftPanel();
	var leftPos = leftPanelDiv.style.left.replace(/[^0-9\-]/g, '') / 1;

	showElement(leftPanelDiv);
	scrollPos = getScrollX();
	if (leftPos < (0 + scrollPos))
	{
		bodyMargin = getStyle(document.body, 'marginLeft');
		document.body.style.marginLeft = leftPanelWidth + 'px';
		leftPanelDiv.style.left = '0px';
	}
	else if (!expandOnly)
	{
		if (moveMainContent) document.body.style.marginLeft = bodyMargin;
		leftPanelDiv.style.left = (leftPanelWidth * -1) + 'px';
	}

	if (init)
	{
		if (isIE)
		{
			addEvent(window, 'scroll', repositionLeftPanel);
			repositionLeftPanel();
		}
		addEvent(window, 'resize', resizeLeftPanel);
	}
	cancelEvent(evt);
}

function resizeLeftPanel()
{
	leftPanelDiv.style.height = getBodyHeight() + 'px';
}

function repositionLeftPanel()
{
	leftPanelDiv.style.top = getScrollY();
}

if (!isIE) addEvent(document.documentElement, 'keypress', function(e){if (e.keyCode == 112) leftPanel(e);});
addEvent(document.documentElement, 'help', leftPanel);