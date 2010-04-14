// $Id$
function editcolor()
{
	if ($("title_color_picker")) closeColorSp();
	else if (typeof arrColors == 'object')
	{
		var colorTableDiv = document.createElement('div');
		colorTableDiv.id = 'title_color_picker';
		var colorTable = document.createElement('table');
		colorTable.cellPadding = '0';
		colorTable.cellSpacing = '3';
		for (var n=0; n< arrColors.length - 1; n++)
		{
			var colorTR = colorTable.insertRow(-1);
			for (var m = 0; m < arrColors[n]. length - 1; m++)
			{
				var colorTD = colorTR.insertCell(-1);
				colorTD.id = 'titlecolor_sp_' + arrColors[n][m] ;
				var colorDiv = document.createElement('div');
				addEvent(colorDiv, 'mouseover', changeCss1);
				addEvent(colorDiv, 'mouseout', changeCss2);
				addEvent(colorDiv, 'click', chTitleColor);
				if (arrColors[n][m] == 'X')
				{
					colorDiv.innerHTML = 'X';
					colorDiv.style.font = '11px Arial';
					colorDiv.style.textAlign = 'center';
					colorDiv.style.lineHeight = '11px';
				}
				else colorDiv.style.background = arrColors[n][m];
				colorTD.appendChild(colorDiv);
			}
		}
		colorTableDiv.appendChild(colorTable);
		$("title_color").parentNode.appendChild(colorTableDiv);
	}
}

function changeCss1(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el;
	eventid.style.borderColor = '#000080';
}

function changeCss2(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el;
	eventid.style.borderColor = '#fff';
}

function chTitleColor(e)
{
	var el;
	if (isIE) el = window.event.srcElement;
	else el = e.target;
	eventid = el.parentNode;
	var text = eventid.id;
	var color = text.substr(text.lastIndexOf('titlecolor_sp_') + 14);
	var cbutton = $("title_color");
	var chidden = $("titlecolor");
	if (color == 'X') chidden.value = cbutton.style.backgroundColor = '';
	else chidden.value = cbutton.style.backgroundColor = color;
	closeColorSp();
}

function closeColorSp()
{
	if ($("title_color_picker")) $("title_color_picker").parentNode.removeChild($("title_color_picker"));
}

function showiconDiv()
{
	$("div_icon").style.visibility = $("div_icon").style.visibility=='visible'?'hidden':'visible';
}

function closeiconDiv()
{
	$("div_icon").style.visibility = 'hidden';
}

function chicon(iconid, image)
{
	$("icon_change").src = 'images/icons/'+image;
	$("iconid").value = iconid;
	closeiconDiv();
}