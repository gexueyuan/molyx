<?php
class Parse_Bbcode extends Parse_Base
{
	public function __construct()
	{
		$this->is_html = false;
		parent::__construct();
	}

	public function convert($text)
	{
		$text = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $text);
		return $this->parseArray($this->buildParseArray($text));
	}

	/**
	 * 检查标签是否可以解释
	 *
	 * @param string $tag_name 标签名
	 * @return boolean 标签是否通过验证
	 */
	protected function isValidTag($tag_name)
	{
		if ($tag_name === '')
		{
			return false;
		}

		if ($tag_name[0] === '/')
		{
			$tag_name = substr($tag_name, 1);
		}

		return isset($this->tag_list[$tag_name]);
	}

	/**
	 * Checks if the specified tag option is valid (matches the regex if there is one)
	 *
	 * @param string Name of the tag
	 * @param string Value of the option
	 * @return boolean Whether the option is valid
	 */
	protected function isValidOption($tag_name, $tag_option)
	{
		if (!isset($this->tag_list[$tag_name]['option']) || !$this->tag_list[$tag_name]['option'])
		{
			return false;
		}

		if ($this->tag_list[$tag_name]['option'] === true)
		{
			return true;
		}

		if (!is_array($tag_option))
		{
			if (!isset($this->tag_list[$tag_name]['option']['default']))
			{
				return false;
			}
			$info = $this->tag_list[$tag_name]['option'][$this->tag_list[$tag_name]['option']['default']];
			if (empty($info['regex']))
			{
				return true;
			}
			return preg_match($info['regex'], $tag_option);
		}
		else
		{
			foreach ($tag_option as $k => $v)
			{
				if (!isset($this->tag_list[$tag_name]['option'][$k]))
				{
					return false;
				}
				else
				{
					$info = $this->tag_list[$tag_name]['option'][$k];
					if (!empty($info['regex']) && !preg_match($info['regex'], $v))
					{
						return false;
					}
				}
			}
			return true;
		}
	}

	/**
	 * [quote]
	 *
	 * @param string 引用内容
	 * @param string 引用自
	 * @return string HTML representation of the tag.
	 */
	protected function handleQuote($message, $from)
	{
		$from = $this->stripSmilies($from);
		$html = '<div class="quote" quote="' . $from . '">' . $this->stripFrontBackWhitespace($message, 1, true, false) . '</div>';
		return $html;
	}

	/**
	 * [email] Creates a link to email an address.
	 *
	 * @param	string	If tag has option, the displayable email name. Else, the email address.
	 * @param	string	If tag has option, the email address.
	 *
	 * @return	string	HTML representation of the tag.
	 */
	function handleEmail($text, $link = '')
	{
		$rightlink = trim($link);
		if (empty($rightlink))
		{
			$rightlink = trim($text);
		}
		$rightlink = str_replace(
			array('`', '"', "'", '['),
			array('&#96;', '&quot;', '&#39;', '&#91;'),
			$this->stripSmilies($rightlink)
		);

		if (!trim($link) || $text == $rightlink)
		{
			$tmp = utf8::unhtmlspecialchars($rightlink);
			if (mb_strlen($tmp) > 55)
			{
				$text = utf8::htmlspecialchars(substr($tmp, 0, 36) . '...' . substr($tmp, -14));
			}
		}
		$rightlink = validate_var($rightlink, 'email');

		if ($rightlink !== false)
		{
			return '<a href="mailto:' . $rightlink . '">' . $text . '</a>';
		}
		else
		{
			return $this->fetchNode('email', $text);
		}
	}

	/**
	 * [list] Makes a bulleted or ordered list.
	 *
	 * @param	string	The body of the list.
	 * @param	string	If tag has option, the type of list (ordered, etc).
	 *
	 * @return	string	HTML representation of the tag.
	 */
	function handleList($text, $type = '')
	{
		if ($type)
		{
			switch ($type)
			{
				case 'A':
					$listtype = 'upper-alpha';
					break;
				case 'a':
					$listtype = 'lower-alpha';
					break;
				case 'I':
					$listtype = 'upper-roman';
					break;
				case 'i':
					$listtype = 'lower-roman';
					break;
				case '1': //break missing intentionally
				default:
					$listtype = 'decimal';
					break;
			}
		}
		else
		{
			$listtype = '';
		}

		$text = preg_replace('#^(\s|<br>|<br />)+#si', '', $text);

		$bullets = preg_split('#\s*\[\*\]#s', $text, -1, PREG_SPLIT_NO_EMPTY);
		if (empty($bullets))
		{
			return "\n\n";
		}

		$output = '';
		foreach ($bullets as $bullet)
		{
			$output .= '<li>' . $this->stripFrontBackWhitespace($bullet) . "</li>\n";
		}

		if ($listtype)
		{
			return '<ol style="list-style-type: ' . $listtype . '">' . $output . '</ol>';
		}
		else
		{
			return "<ul>$output</ul>";
		}
	}

	/**
	 * [url] Creates a link to another web page.
	 *
	 * @param	string	If tag has option, the displayable name. Else, the URL.
	 * @param	string	If tag has option, the URL.
	 *
	 * @return	string	HTML representation of the tag.
	 */
	function handleUrl($text, $link)
	{
		$rightlink = trim($link);
		if (empty($rightlink))
		{
			$rightlink = trim($text);
		}
		$rightlink = str_replace(
			array('`', '"', "'", '['),
			array('&#96;', '&quot;', '&#39;', '&#91;'),
			$this->stripSmilies($rightlink)
		);

		if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $rightlink))
		{
			$rightlink = "http://$rightlink";
		}

		if (!trim($link) || $text === $rightlink)
		{
			$tmp = utf8::unhtmlspecialchars($rightlink);
			if (mb_strlen($tmp) > 55)
			{
				$text = utf8::htmlspecialchars(substr($tmp, 0, 36) . '...' . substr($tmp, -14));
			}
		}

		return '<a href="' . $rightlink . '" target="_blank">' . $text . '</a>';
	}

	protected function handleImg($text, $link)
	{
		static $image_ext = array('gif', 'jpg', 'jpeg', 'jpe', 'png', 'bmp', 'tiff', 'tif', 'psd', 'pdf');
		$link = trim($link);
		if (empty($link))
		{
			$link = trim($text);
		}

		// 检查是否是图片, 不允许将动态链接当作图片
		$not_image = false;
		if (preg_match('/[?&;]/', $link))
		{
			$not_image = true;
		}
		else
		{
			$ext = strtolower(preg_replace('#^.*\.([a-zA-Z]+)$#', '\1', $link));
			if (!in_array($ext, $image_ext))
			{
				$not_image = true;
			}
		}

		$option['border'] = '0';

		if ($not_image)
		{
			return $text;
		}
		else
		{
			return '<img src="' . $link . '" border="0" alt="" />';
		}
	}

	protected function handleCode($text, $type)
	{
		$type = strtolower(trim($type));
		$code = check_code_type($type);
		if (!$code)
		{
			$type = 'php';
			$code = 'Php';
		}
		$this->include_code[] = $code;
		return '<pre name="code" class="' . $type . '">' . $text . '</pre>';
	}

	protected function convertBr($text)
	{
		return str_replace('<br>', '<br />', nl2br($text));
	}
}