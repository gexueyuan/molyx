// $Id: error.js 15 2010-04-14 09:17:33Z flyinghail $
if (typeof($) == 'undefined')
{
	function $(el)
	{
		if (typeof el == 'string') el = document.getElementById(el);
		return el;
	}

	function hideElement(el)
	{
		if (!(el = $(el))) return;
		el.style.display = 'none';
		el.style.visibility = 'hidden';
	}

	function showElement(el, dtype, vtype)
	{
		if (!(el = $(el))) return;
		el.style.display = (!dtype) ? 'block' : dtype;
		el.style.visibility = (!vtype) ? 'visible' : vtype;
	}
}

function showParam(i)
{
	currentParam = i;
	showElement('paramHide');
	showElement('paramSpace');
	showElement('param');
	$('param').innerHTML = '<pre>' + $('param' + i).innerHTML + '</pre>'
}

function hideParam()
{
	currentParam = -1;
	hideElement('paramHide');
	hideElement('paramSpace');
	hideElement('param');
}

function showOrHideParam(i)
{
	if (currentParam == i) hideParam();
	else showParam(i);
}

function showFile(id)
{
	if ($('file' + id).style.display == 'none') showElement('file' + id);
	else hideElement('file' + id);
}
function showDetails(hash, cnt)
{
	for (i = 0; i < cnt; ++i) showElement('file' + hash + '-' + i);
}
function hideDetails(hash, cnt)
{
	for (i = 0; i < cnt; ++i) hideElement('file' + hash + '-' + i);
}
var currentParam = -1;