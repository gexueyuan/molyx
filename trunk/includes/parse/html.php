<?php
class parse_html extends parse_base
{
	private $is_block = false;
	public function __construct()
	{
		parent::__construct('html');
	}

	/**
	 * 检查标签是否可以解释
	 *
	 * @param string $tag_name 标签名
	 * @return boolean 标签是否通过验证
	 */
	protected function is_valid_tag($tag_name)
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
	protected function is_valid_option($tag_name, &$tag_option)
	{
		if (is_array($tag_option))
		{
			$novalue_attribute = array('noshade' => true, 'checked' => true, 'selected' => true, 'disabled' => true, 'readonly' => true, 'alt' => true, 'multiple' => true, 'nowrap' => true);
			foreach ($tag_option as $k => $v)
			{
				parse_command::option($tag_name, $k, $v);

				if ($k === 'style' || $k === 'color')
				{
					$v = $this->rgb2hex($v);
				}

				if (empty($v) && !isset($novalue_attribute[$k]))
				{
					unset($tag_option[$k]);
					continue;
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
	 * @param unknown_type $text
	 * @param unknown_type $option
	 * @return unknown
	 */
	protected function handle_pre($text, $option)
	{
		if (!empty($text))
		{
			// 如果 name="code" class="语言" 不能同时匹配则去掉这些属性
			if (isset($option['name']) && $option['name'] === 'code')
			{
				if (!isset($option['class']))
				{
					$option['class'] = 'php';
				}
				$option['class'] = parse_command::code($option['class']);
			}
			$text = $this->do_entity(preg_replace('/<br>|<br[ ]+\/>/', "\n", $text));
			$text = $this->fetch_node('pre', $text, $option);
		}
		return $text;
	}

	protected function handle_url($text, $option)
	{
		if (isset($option['href']))
		{
			$link = trim($option['href']);
			if (!empty($link))
			{
				$link = $this->strip_smilies($link);

				if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $link))
				{
					$link = "http://$link";
				}

				// 如果 a 标签的文字就是链接本身, 而且链接长度超过 55 字符那么将文字进行缩略处理
				if ($text == $link)
				{
					$tmp = utf8_unhtmlspecialchars($link);
					if (mb_strlen($tmp) > 55)
					{
						$text = utf8_htmlspecialchars(mb_substr($tmp, 0, 36) . '...' . mb_substr($tmp, -14));
					}
				}

				$option['href'] = $link;
				$option['target'] = '_blank';
			}
		}

		$text = $this->fetch_node('a', $text, $option);
		if (!isset($link))
		{
			$text = $this->do_entity($text);
		}
		return $text;
	}

	protected function handle_img($text, $option)
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

		$return = $this->fetch_tag($tag);
		if ($not_image)
		{
			$return = $this->do_entity($return);
		}
		return $return;
	}

	protected function handle_div($text, $option)
	{
		if (!empty($text))
		{
			$this->is_block = false;
			$text = $this->fetch_block('div', 'quote', $option, $text);
			$text = $this->fetch_block('div', 'attach_left', $option, $text);
			$text = $this->fetch_block('div', 'attach_center', $option, $text);
			$text = $this->fetch_block('div', 'attach_right', $option, $text);
			if (!$this->is_block || ($this->is_block && !empty($option)))
			{
				$text = $this->fetch_node('div', $text, $option);
			}
		}
		return $text;
	}

	protected function handle_span($text, $option)
	{
		if (!empty($text))
		{
			$text = $this->fetch_block('span', 'quote', $option, $text);
			if (!empty($option))
			{
				$text = $this->fetch_node('span', $text, $option);
			}
		}
		return $text;
	}

	protected function handle_p($text, $option)
	{
		if (!empty($text))
		{
			$this->is_block = false;
			$text = $this->fetch_block('p', 'quote', $option, $text);
			if (!$this->is_block || ($this->is_block && !empty($option)))
			{
				$text = $this->fetch_node('p', $text, $option);
			}
		}
		return $text;
	}

	protected function fetch_block($tag_name, $block_name, &$option, $text)
	{
		if (isset($option['class']) && strpos(" {$option['class']} ", " $block_name ") !== false)
		{
			$class = trim(str_replace(" $block_name ", '', " {$option['class']} "));
			if (!empty($class))
			{
				$block_option = array('class' => $block_name);

				$attribute = 'name';
				switch ($block_name)
				{
					case 'quote':
						$attribute = 'title';
				}

				if (isset($option[$attribute]))
				{
					$block_option[$attribute] = $option[$attribute];
					unset($option[$attribute]);
				}

				$text = $this->fetch_node($tag_name, $text, $block_option);
				$this->is_block = true;
				$option['class'] = $class;
			}
			else
			{
				unset($block_option['class']);
			}
		}

		return $text;
	}
}
?>