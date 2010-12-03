<?php
class Parse_Html extends Parse_Base
{
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

		return !in_array($tag_name, array('script', 'frame', 'iframe', 'head', 'html', 'body', 'title', 'meta', 'applet'));
	}

	/**
	 * option 检查
	 *
	 * @param string 标签名
	 * @param string 属性
	 * @return boolean 是否通过验证
	 */
	protected function isValidOption($tag_name, &$tag_option)
	{
		if (is_array($tag_option))
		{
			foreach ($tag_option as $k => $v)
			{
				if ($k === 'style' || $k === 'color')
				{
					$v = $this->rgb2Hex($v);
				}

				if (isset($this->tag_list['option'][$k]))
				{
					if (!empty($this->tag_list['option'][$k]['regex']))
					{
						if (!preg_match($this->tag_list['option'][$k]['regex'], $v))
						{
							unset($tag_option[$k]);
						}
						continue;
					}
				}
				else if (strpos($k, 'on') === 0)
				{
					unset($tag_option[$k]);
					continue;
				}

				$tag_option[$k] = str_ireplace(
					array('moz-binding', 'javascript', 'vbscript', 'alert', 'about', 'expression'),
					array('moz binding', 'java_script', 'vb_script', '&#097;lert_', '&#097;bout_', 'expression_'),
					$v
				);
			}
		}

		return true;
	}

	/**
	 * pre 解析
	 *
	 * @param string $text
	 * @param array $option
	 */
	protected function handlePre($text, $option)
	{
		// 如果 name="code" class="语言" 不能同时匹配则去掉这些属性
		if (isset($option['name']) && $option['name'] === 'code')
		{
			if (!empty($option['class']))
			{
				$code = check_code_type($option['class']);
				if (!$code)
				{
					$option['class'] = 'php';
					$code = 'Php';
				}
				$this->include_code[] = $code;
			}
			else
			{
				unset($option['name']);
			}
		}
		$text = $this->do_entity(preg_replace('/<br>|<br[ ]+\/>/', "\n", $text));

		return $this->fetchNode('pre', $text, $option);
	}

	protected function handleUrl($text, $option)
	{
		if (isset($option['href']))
		{
			$link = trim($option['href']);
			if (!empty($link))
			{
				$link = $this->stripSmilies($link);

				if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $link))
				{
					$link = "http://$link";
				}

				// 如果 a 标签的文字就是链接本身, 而且链接长度超过 55 字符那么将文字进行缩略处理
				if ($text == $link)
				{
					$tmp = utf8::unhtmlspecialchars($link);
					if (mb_strlen($tmp) > 55)
					{
						$text = utf8::htmlspecialchars(mb_substr($tmp, 0, 36) . '...' . mb_substr($tmp, -14));
					}
				}

				$option['href'] = $link;
				$option['target'] = '_blank';
			}
		}

		$text = $this->fetchNode('a', $text, $option);
		if (!isset($link))
		{
			$text = $this->doEntity($text);
		}
		return $text;
	}

	protected function handleImg($text, $option)
	{
		static $image_ext = array('gif', 'jpg', 'jpeg', 'jpe', 'png', 'bmp', 'tiff', 'tif', 'psd', 'pdf');

		// 检查是否是图片, 不允许将动态链接当作图片
		$not_image = false;
		if (preg_match('/[?&;]/', $option['src']))
		{
			$not_image = true;
		}
		else
		{
			$ext = strtolower(preg_replace('#^.*\.([a-zA-Z]+)$#', '\1', $option['src']));
			if (!in_array($ext, $image_ext))
			{
				$not_image = true;
			}
		}

		$option['border'] = '0';

		$tag = array(
			'name' => 'img',
			'closing' => true,
			'single' => true,
			'option' => $option
		);

		$return = $this->fetchTag($tag);
		if ($not_image)
		{
			$return = $this->doEntity($return);
		}
		return $return;
	}

	protected function handleDiv($text, $option)
	{
		$text = $this->fetchBlock('div', 'quote', $option, $text);
		return $text;
	}

	protected function handleSpan($text, $option)
	{
		$text = $this->fetchBlock('span', 'quote', $option, $text);
		return $text;
	}

	protected function handleP($text, $option)
	{
		$text = $this->fetchBlock('p', 'quote', $option, $text);
		return $text;
	}

	protected function fetchBlock($tag_name, $block_name, $option, $text)
	{
		if (isset($option['class']) && strpos(" {$option['class']} ", " $block_name ") !== false)
		{
			$block_option = array('class' => $block_name);
			if (isset($option[$block_name]))
			{
				$block_option[$block_name] = $option[$block_name];
				unset($option[$block_name]);
			}
			$text = $this->fetchNode($tag_name, $text, $block_option);

			if ($option['class'] === $block_name)
			{
				unset($option['class']);
			}
			else
			{
				$option['class'] = trim(str_replace(" $block_name ", '', " {$option['class']} "));
			}
		}

		if (!empty($option))
		{
			$text = $this->fetchNode($tag_name, $text, $option);
		}
		return $text;
	}
}