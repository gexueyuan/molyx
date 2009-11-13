function isUndefined(variable) 
{
	return typeof variable == 'undefined' ? true : false;
}
function insertunit(text, textend, name) 
{
	thisform = eval('document.cpform.' + name);
	thisform.focus();
	textend = isUndefined(textend) ? '' : textend;
	if(!isUndefined(thisform.selectionStart)) 
	{
		var opn = thisform.selectionStart + 0;
		if(textend != '') 
		{
			text = text + thisform.value.substring(thisform.selectionStart, thisform.selectionEnd) + textend;
		}
		thisform.value = thisform.value.substr(0, thisform.selectionStart) + text + thisform.value.substr(thisform.selectionEnd);
	} 
	else if(document.selection && document.selection.createRange) 
	{
		var sel = document.selection.createRange();
		if(textend != '') 
		{
			text = text + sel.text + textend;
		}
		sel.text = text.replace('/\r?\n/g', '\r\n');
		sel.moveStart('character', text.length);
	} 
	else 
	{
		thisform.value += text;
	}
	replacecontent();
}
