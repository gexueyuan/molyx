// $Id$
var activeElementId = 0;
var colorPickerType = 0;
var pickerReady = false;
var colors = ['00', '33', '66', '99', 'CC', 'FF'];
var specialColors = new Array('#000000', '#333333', '#666666', '#999999', '#CCCCCC', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#00FFFF', '#FF00FF');

function init_color_preview()
{
	if (typeof(numcolors) != 'undefined')
	{
		for (var i = 0; i < numcolors; i++) preview_color(i);
		if (colorPickerType != 0) init_color_picker(colorPickerType);
		pickerReady = true;
	}
}

function preview_color(elm)
{
	var colorElement = $("color_" + elm);
	var previewElement = $("preview_" + elm);
	var cssRegExp = new RegExp(/url\(('|"|)((http:\/\/|\/)?)(.*)\1\)/i);
	if (is_transparent(colorElement.value))
	{
		previewElement.style.background = "none";
		previewElement.style.borderStyle = "dashed";
		previewElement.title = window.status = "";
	}
	else
	{
		var cssValue = colorElement.value;
		var matches;
		if (matches = colorElement.value.match(cssRegExp))
			if (matches[3] == '') cssValue = colorElement.value.replace((matches[3] + matches[4]), (bburl + matches[3] + matches[4]));
		try
		{
			previewElement.style.background = cssValue;
			previewElement.style.borderStyle = "inset";
			previewElement.title = window.status = "";
		}
		catch(csserror)
		{
			previewElement.style.borderStyle = "dashed";
			previewElement.title = window.status = "Error: '" + cssValue + "' is not a valid value for a CSS entry.";
		}
	}
}

function set_swatch_color(x, y, color)
{
	$("sw" + x + "-" + y).style.backgroundColor = color;
}

function init_color_picker(setPickerType)
{
	colorPickerType = setPickerType;
	$("colorPickerType").value = setPickerType;

	var y, x, i, j;
	if (setPickerType < 2)
	{
		for (y = 0; y < 12; y++)
		{
			set_swatch_color(0, y, '#000000');
			set_swatch_color(1, y, specialColors[y]);
			set_swatch_color(2, y, '#000000');
		}
	}

	switch(setPickerType)
	{
		case 0:
			green = [5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5];
			blue = [0, 0, 0, 5, 4, 3, 2, 1, 0, 0, 1, 2, 3, 4, 5, 5, 4, 3, 2, 1, 0];
			for (y = 0; y < 12; y++)
			{
				for (x = 3; x < 21; x++)
				{
					r = Math.floor((20 - x) / 6) * 2 + Math.floor(y / 6);
					g = green[y];
					b = blue[x];
					set_swatch_color(x, y, "#" + colors[r] + colors[g] + colors[b]);
				}
			}
			break;
		case 1:
			green = [0, 0, 0, 0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5];
			blue = [0, 1, 2, 3, 4, 5, 0, 1, 2, 3, 4, 5];
			for (y = 0; y < 12; y++)
			{
				for (x = 3; x < 21; x++)
				{
					r = Math.floor((x - 3) / 6) + Math.floor(y / 6) * 3;
					g = green[x];
					b = blue[y];
					set_swatch_color(x, y, "#" + colors[r] + colors[g] + colors[b]);
				}
			}
			break;
		case 2:
			i = 255;
			j = -1;
			for (y = 0; y < 12; y++)
			{
				for (x = 0; x < 21; x++)
				{
					set_swatch_color(x, y, "rgb(" + i + "," + i + "," + i + ")");
					i--;
					if (i == 4) i = 0;
				}
			}
			break;
		case 3:
		case 4:
		case 5:
		case 6:
		case 7:
		case 8:
			i = 255;
			j = 255;
			for(y = 0; y < 12; y++)
			{
				for (x = 0; x < 21; x++)
				{
					acolor = Math.round(j);
					bcolor = Math.round(i);
					if (acolor < 0) acolor = 0;
					switch(setPickerType)
					{
						case 3: r = acolor; g = bcolor; b = bcolor; break;
						case 4: r = bcolor; g = acolor; b = bcolor; break;
						case 5: r = bcolor; g = bcolor; b = acolor; break;
						case 6: r = acolor; g = acolor; b = bcolor; break;
						case 7: r = acolor; g = bcolor; b = acolor; break;
						case 8: r = bcolor; g = acolor; b = acolor; break;
					}
					set_swatch_color(x, y, "rgb(" + r + "," + g + "," + b + ")");
					if (i > 1) i -= 2.03174;
					else
					{
						i = 0;
						if (j > 1.03) j -= 2.03174;
					}
				}
			}
			break;
		default: return false;
	}
	pickerReady = true;
	return true;
}

function switch_color_picker(direction)
{
	if (direction > 0)
	{
		if (colorPickerType < 8) colorPickerType++;
		else colorPickerType = 0;
	}
	else
	{
		if (colorPickerType > 0) colorPickerType--;
		else colorPickerType = 8;
	}
	init_color_picker(colorPickerType);
}

function open_color_picker(clickedElementId, e)
{
	if (isSafari) return;
	if (!pickerReady)
	{
		alert('The color picker is still loading, please wait.');
		return;
	}
	pickerElement = $("colorPicker");
	if (activeElementId == clickedElementId && pickerElement.style.display != "none")
		pickerElement.style.display = "none";
	else
	{
		activeElementId = clickedElementId;
		colorElement = $("color_" + clickedElementId);
		previewElement = $("preview_" + clickedElementId);
		$("oldColor").style.background = previewElement.style.background;
		$("newColor").style.background = previewElement.style.background;
		$("txtColor").value = colorElement.value;
		if (!e) e = window.event;
		var scrollX = 0;
		var scrollY = 0;
		if (document.documentElement && document.documentElement.scrollLeft)
			scrollX = document.documentElement.scrollLeft;
		else if (document.body && document.body.scrollLeft)
			scrollX = document.body.scrollLeft;
		else if (window.pageXOffset)
			scrollX = window.pageXOffset;
		else if (window.scrollX)
			scrollX = window.scrollX;
		if (document.documentElement && document.documentElement.scrollTop)
			scrollY = document.documentElement.scrollTop;
		else if (document.body && document.body.scrollTop)
			scrollY = document.body.scrollTop;
		else if (window.pageYOffset)
			scrollY = window.pageYOffset;
		else if (window.scrollY)
			scrollY = window.scrollY;
		if (typeof(e.pageX) == "number")
		{
			xpos = e.pageX;
			ypos = e.pageY;
		}
		else if (typeof(e.clientX) == "number")
		{
			xpos = e.clientX + scrollX;
			ypos = e.clientY + scrollY;
		}
		xpos += 10;
		ypos += 5;
		if ((xpos + colorPickerWidth) >= document.body.clientWidth)
			xpos = document.body.clientWidth - colorPickerWidth - 5;
		pickerElement.style.left = xpos + "px";
		pickerElement.style.top = ypos + "px";
		pickerElement.style.display = "";
	}
}

function close_color_picker()
{
	activeElementId = 0;
	$("colorPicker").style.display = "none";
}

function swatch_over(e)
{
	col_over(this);
}

function swatch_click(e)
{
	col_click(this);
}

function col_over(element)
{
	var color = fetch_hex_color(element.style.backgroundColor);
	$("newColor").style.background = color;
	$("txtColor").value = color;
}

function col_click(element)
{
	if (element == "transparent") color = element;
	else var color = fetch_hex_color(element.style.backgroundColor);
	$("color_" + activeElementId).value = color;
	preview_color(activeElementId);
	close_color_picker();
}

function fetch_hex_color(color)
{
	if (color.substr(0, 1) == "r")
	{
		colorMatch = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/i);
		for (var i = 1; i < 4; i++)
		{
			colorMatch[i] = parseInt(colorMatch[i]).toString(16);
			if (colorMatch[i].length < 2) colorMatch[i] = "0" + colorMatch[i];
		}
		color = "#" + (colorMatch[1] + colorMatch[2] + colorMatch[3]).toUpperCase();
	}

	return color.toUpperCase();
}

function is_transparent(value)
{
	if (value == '' || value == 'none' || value == 'transparent') return true;
	else return false;
}