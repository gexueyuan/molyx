// $Id$
var modalMessage = function()
{
	var url, message, transparentDiv, contentDiv, shadowDiv, iframe, width, height, messageBoxCss, shadowOffset;
	this.url = this.message = '';
	this.height = this.width = this.messageBoxCss = false;
	this.shadowOffset = 5;
}

modalMessage.prototype = {
setUrl : function(url) {this.url = url;},
setContent : function(content) {this.message = content;},
setMessageBoxClass : function(className) {this.messageBoxCss = className;},

setSize : function(width, height)
{
	if (width) this.width = width;
	else this.width = false;
	if (height) this.height = height;
	else this.height = false;
},

display : function()
{
	if(!this.contentDiv) this.initDivs();
	if (this.transparentDiv) showElement(this.transparentDiv);
	showElement(this.contentDiv);
	if (this.shadowDiv) showElement(this.shadowDiv);
	if (isIE) showElement(this.iframe);
	if (this.url) getHtml(this.url, this.contentDiv);
	else this.contentDiv.innerHTML = this.message;
	this.resizeDivs();
	var obj = this;
	setTimeout('obj.resizeDivs()', 150);
},

close : function()
{
	hideElement(this.contentDiv);
	if (this.shadowDiv) hideElement(this.shadowDiv);
	if (this.transparentDiv) hideElement(this.transparentDiv);
	if (isIE) hideElement(this.iframe);
},

initDivs : function()
{
	if (!(this.transparentDiv = $('modal_message_transparent'))) return;
	if (!(this.contentDiv = $('modal_message_content'))) return;
	this.shadowDiv = $('modal_message_shadow');
	if (isIE) this.iframe = $('modal_message_iframe');
	var obj = this;
	addEvent(window, 'scroll', function(e){obj.resizeDivs()});
	addEvent(window, 'resize', function(e){obj.resizeDivs()});
},

resizeDivs : function()
{
	if(!this.transparentDiv) return;
	if (this.messageBoxCss) this.contentDiv.className = this.messageBoxCss;
	else this.contentDiv.className = 'modal_message_content';

	var st = getScrollY();
	var sl = getScrollX();

	window.scrollTo(sl, st);
	setTimeout('window.scrollTo(' + sl + ',' + st + ');', 10);

	this.transparentDiv.style.top = st + 'px';
	this.transparentDiv.style.left = sl + 'px';

	var bodyWidth = getBodyWidth();
	var bodyHeight = getBodyHeight();

	this.transparentDiv.style.width = bodyWidth + 'px';
	this.transparentDiv.style.height = bodyHeight + 'px';

	if (this.width) this.contentDiv.style.width = this.width + 'px';
    if (this.height) this.contentDiv.style.height = this.height + 'px';

	var divWidth = this.contentDiv.offsetWidth;
	var divHeight = this.contentDiv.offsetHeight;

	var contentLeft = Math.ceil((bodyWidth - divWidth) / 2);
	var contentTop = (Math.ceil((bodyHeight - divHeight) / 2) +  st);
	this.contentDiv.style.left = contentLeft + 'px';
	this.contentDiv.style.top = contentTop + 'px';

	if (isIE)
	{
		this.iframe.style.left = contentLeft + 'px';
		this.iframe.style.top = contentTop + 'px';
		this.iframe.style.width = divWidth + 'px';
		this.iframe.style.height = divHeight + 'px';
	}

	if (this.shadowDiv)
	{
		this.shadowDiv.style.left = (contentLeft + this.shadowOffset) + 'px';
		this.shadowDiv.style.top = (contentTop + this.shadowOffset) + 'px';
		this.shadowDiv.style.width = divWidth + 'px';
		this.shadowDiv.style.height = divHeight + 'px';
	}
}};

var modalMessageObj;
function displayMessage(content, className, width, height)
{
	if (!modalMessageObj) modalMessageObj = new modalMessage();
	if (content['url']) modalMessageObj.setUrl(content['url']);
	else
	{
		if (content['message']) content = content['message'];
		else if ((content['id'] && (content = $(content['id']))) || (contentc && content.innerHTML))
			content = content.innerHTML;
		else return;
		modalMessageObj.setContent(content);
		modalMessageObj.setUrl(false);
	}

	modalMessageObj.setMessageBoxClass(className);
	modalMessageObj.setSize(width, height);
	modalMessageObj.display();
}

function closeMessage() { modalMessageObj.close(); }