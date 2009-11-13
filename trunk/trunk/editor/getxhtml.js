var regexComment = new RegExp('^<!--(.*)-->$');
var regexHyphen = new RegExp('-$');

function getXHTML(node)
{
	var i, tagName, text = '', childs = node.childNodes, n = childs.length;
	for (i = 0; i < n; i++)
	{
		var child = childs[i];
		switch (child.nodeType)
		{
			case 1:  //ELEMENT_NODE
				tagName = String(child.tagName).toLowerCase();
				if (tagName == '' || tagName == 'style' || tagName == 'title' || tagName == 'script' || tagName == 'iframe' || tagName == 'frame' || tagName == 'meta') break;
				if (tagName == '!')
				{
					if (parts = regexComment.exec(child.text)) text += fixComment(parts[1]); //COMMENT_NODE
				}
				else
				{
					text += '<' + tagName;
					var attr = child.attributes, m = attr.length, isAlt = false, value;
					for (j = 0; j < m; j++)
					{
						var name = attr[j].nodeName.toLowerCase();
						if (!attr[j].specified && (name != 'selected' || !child.selected) && (name != 'style' || child.style.cssText == '') && name != 'value') continue; //IE 5.0
						if (name == '_moz_dirty' || name == '_moz_resizing' || tagName == 'br' && name == 'type' && child.getAttribute('type') == '_moz') continue;
						var valid = true;
						switch (name)
						{
							case 'style':
								value = child.style.cssText;
							break;
							case 'class':
								value = child.className;
							break;
							case 'http-equiv':
								value = child.httpEquiv;
							break;
							case 'noshade':
							case 'checked':
							case 'selected':
							case 'multiple':
							case 'nowrap':
							case 'disabled':
								value = name;
							break;
							default:
								try {value = child.getAttribute(name, 2);}
								catch (e) {valid = false;}
							break;
						}

						if (valid && !(tagName == 'li' && name == 'value'))
								text += ' ' + name + '="' + fixAttribute(value) + '"';
						if (name == 'alt') isAlt = true;
					}
					if (tagName == 'img' && !isAlt) text += ' alt=""';
					if (child.canHaveChildren || child.hasChildNodes())
					{
						text += '>';
						text += getXHTML(child);
						text += '</' + tagName + '>';
					}
					else
					{
						if (tagName == 'style' || tagName == 'title' || tagName == 'script')
							text += '></'+tagName+'>';
						else text += ' />';
					}
				}
			break;
			case 3: //TEXT_NODE
				text += fixAttribute(child.nodeValue);
			break;
			case 8: //COMMENT_NODE
				text += fixComment(child.nodeValue);
			break;
			default: break;
		}
	}

	text = text
		.replace(/<\/?(?:head|body)>[\n]*/gi, '')
		.replace(/<head \/>[\n]*/gi, '');
	return trimContent(text);
}

function trimContent(html)
{
	html = html.replace(/\r\n/g, '\n')
		.replace(/\r/g, '\n')
		.replace(/<p>\s*(<br\s\/?)+/gi, '<p>')
		.replace(/(<br\s\/?>)+\s*<\/p>/gi, '</p>')
		.replace(/<p><\/p>/gi, '')
		.replace(/<li>\s*<p>/gi, '<li>')
		.replace(/<\/p>\s*<\/li>/gi, '</li>')
		.replace(/<div><\/div>/gi, '')
		.replace(/<span><\/span>/gi, '')
		.replace(/(<br\s\/?>)+\s*$/gi, '');
	return trim(html);
}

function fixComment(text)
{
	text = text.replace(/--/g, '__');
	if (regexHyphen.exec(text)) text += ' ';
	return '<!--' + text + '-->';
}

function fixAttribute(text)
{
	return String(text).replace(/\&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;');
}