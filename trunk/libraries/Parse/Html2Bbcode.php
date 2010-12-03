<?php
class Parse_Html2Bbcode extends Parse_Base
{
	private $block = array();

	public function convert($text)
	{
		$text = preg_replace("/<br>|<br[ ]+\/>\n?/", "\n", $text);
		$text = $this->buildParseArray($text);
		$this->is_html = false;
		return str_replace(
			array('&lt;', '&gt;', '&quot;', '&amp;'),
			array('<', '>', '"', '&'),
			$this->stripSmilies($this->parseArray($text))
		);
	}

	/**
	 * 检查标签是否可以解释
	 *
	 * @param string $tag_name 标签名
	 * @return boolean 标签是否通过验证
	 */
	protected function isValidTag($tag_name)
	{
		if ($tag_name[0] === '/')
		{
			$tag_name = substr($tag_name, 1);
		}

		if (!isset($this->tag_list[$tag_name]['callback']) || $this->tag_list[$tag_name]['callback'] !== 'handleTag')
		{
			$this->tag_list[$tag_name]['callback'] = 'handleTag';
			$this->tag_list[$tag_name]['disable_smilies'] = true;
		}

		return true;
	}

	protected function isValidOption($tag_name, &$tag_option)
	{
		foreach (array('style', 'color') as $v)
		{
			if (isset($tag_option[$v]))
			{
				$tag_option[$v] = $this->rgb2Hex($tag_option[$v]);
			}
		}
		return true;
	}

	protected function handleTag($text, $option)
	{
		$current_tag = &$this->current_tag;

		if ($current_tag['name'] === 'br')
		{
			return "\n";
		}

		$this->block = array();
		if (isset($option['align']) && in_array($option['align'], array('left', 'center', 'right')))
		{
			array_unshift($this->block, array(
				'name' => $option['align'],
				'option' => ''
			));
		}

		if (isset($option['style']))
		{
			$this->style2Bbcode($option['style']);
		}

		$this->block2Bbcode('quote', $option);

		if ($current_tag['name'] === 'li')
		{
			return "[*]" . $this->stripFrontBackWhitespace($text) . "\n";
		}
		else if ($current_tag['name'] === 'ul')
		{
			array_unshift($this->block, array(
				'name' => 'list',
				'option' => ''
			));
		}
		else if ($current_tag['name'] === 'ol')
		{
			$type = preg_replace('/list-style-type:\s*([a-zA-Z\-]+);?/', '\\1', $option['style']);
			switch ($type)
			{
				case 'upper-alpha':
					$type = 'A';
					break;
				case 'lower-alpha':
					$type = 'a';
					break;
				case 'upper-roman':
					$type = 'I';
					break;
				case 'lower-roman':
					$type = 'i';
					break;
				case 'decimal': //break missing intentionally
				default:
					$type = '1';
				break;
			}
			array_unshift($this->block, array(
				'name' => 'list',
				'option' => $type
			));
		}
		else if (in_array($current_tag['name'], array('strong', 'b', 'i', 'u', 's', 'sub', 'sup', 'em', 'blockquote', 'hr')))
		{
			if ($current_tag['name'] === 'strong')
			{
				$current_tag['name'] = 'b';
			}
			else if ($current_tag['name'] === 'blockquote')
			{
				$current_tag['name'] = 'indent';
			}

			array_unshift($this->block, array(
				'name' => $current_tag['name'],
				'option' => ''
			));
		}
		else if ($current_tag['name'] === 'font')
		{
			foreach (array('color', 'size', 'face') as $v)
			{
				if (!empty($option[$v]))
				{
					array_unshift($this->block, array(
						'name' => ($v === 'face') ? 'font' : $v,
						'option' => $option[$v]
					));
				}
			}
		}
		else if ($current_tag['name'] === 'a' && !empty($option['href']))
		{
			$link = trim($option['href']);
			$link = $this->stripSmilies($link);

			if (!preg_match('#^[a-z0-9]+(?<!about|javascript|vbscript|data):#si', $link))
			{
				$link = "http://$link";
			}

			array_unshift($this->block, array(
				'name' => (strpos($link, 'mailto:') === 0) ? 'email' : 'url',
				'option' => ($link === $text) ? '' : $link
			));
		}
		else if ($current_tag['name'] === 'pre' && check_code_type($option['class']))
		{
			array_unshift($this->block, array(
				'name' => 'code',
				'option' => $option['class']
			));
		}
		else if ($current_tag['name'] === 'img' && !empty($option['src']))
		{
			array_unshift($this->block, array(
				'name' => 'img',
				'option' => ''
			));
			$text = $option['src'];
		}
		else if ($current_tag['name'] === 'p' && !empty($text))
		{
			$text = "\n" . $this->stripFrontBackWhitespace($text) . "\n";
		}

		if (!empty($this->block))
		{
			foreach ($this->block as $v)
			{
				$text = $this->fetchNode($v['name'], $text, $v['option']);
			}
		}
		return $text;
	}

	protected function style2Bbcode($style)
	{
		static $search_list = array(
			array(
				'tag' => 'left',
				'option' => false,
				'regex' => '#text-align:\s*(left);?#i'
			),
			array(
				'tag' => 'center',
				'option' => false,
				'regex' => '#text-align:\s*(center);?#i'
			),
			array(
				'tag' => 'right',
				'option' => false,
				'regex' => '#text-align:\s*(right);?#i'
			),
			array(
				'tag' => 'color',
				'option' => true,
				'regex' => '#(?<![a-z0-9-])color:\s*([^;]+);?#i',
				'match' => 1
			),
			array(
				'tag' => 'font',
				'option' => true,
				'regex' => '#font-family:\s*([^;]+);?#i',
				'match' => 1
			),
			array(
				'tag' => 'bgcolor',
				'option' => true,
				'regex' => '#(?<![a-z0-9-])background-color:\s*([^;]+);?#i',
				'match' => 1
			),
			array(
				'tag' => 'b',
				'option' => false,
				'regex' => '#font-weight:\s*(bold);?#i'
			),
			array(
				'tag' => 'i',
				'option' => false,
				'regex' => '#font-style:\s*(italic);?#i'
			),
			array(
				'tag' => 'u',
				'option' => false,
				'regex' => '#text-decoration:\s*(underline);?#i'
			)
		);

		foreach ($search_list as $search_tag)
		{
			if (preg_match($search_tag['regex'], $style, $matches))
			{
				array_unshift($this->block, array(
					'name' => $search_tag['tag'],
					'option' => $search_tag['option'] == true ? $matches[$search_tag['match']] : ''
				));
			}
		}
	}

	protected function block2Bbcode($block_name, &$option)
	{
		if (isset($option['class']) && strpos(" {$option['class']} ", " $block_name ") !== false)
		{
			$block_option = '';
			if (isset($option[$block_name]))
			{
				$block_option = $option[$block_name];
				unset($option[$block_name]);
			}
			array_unshift($this->block, array(
				'name' => $block_name,
				'option' => $block_option
			));

			if ($option['class'] === $block_name)
			{
				unset($option['class']);
			}
			else
			{
				$option['class'] = trim(str_replace(" $block_name ", '', " {$option['class']} "));
			}
		}
	}
}