<?php
define('PARSER_START', 1);
define('PARSER_TEXT', 2);
define('PARSER_TAG_OPENED', 3);

abstract class parse_base
{
	protected $is_html = true;
	protected $tag_list = array();
	protected $error_message = '';
	protected $smilies = array();
	protected $include_code = array();
	protected $current_tag;

	private $stack;

	public function __construct()
	{
		$this->init();
	}

	public function get_errors()
	{
		return $this->error_message;
	}

	private function init()
	{
		$this->smilies = &cache::get('smilies');
		include ROOT_DIR . 'library/parse/' . ($this->is_html ? 'html' : 'bbcode') . 'tag.php';
		$this->tag_list = $tag_list;
	}

	public function convert($text)
	{
		return $this->parse_array($this->build_parse_array($text));
	}

	/**
	 * 将字符串拆分成 HTML 数组
	 *
	 * @param string $text 字符串
	 * @return array
	 */
	protected function build_parse_array($text)
	{
		$start_pos = 0;
		$strlen = strlen($text);
		$output = array();
		$state = PARSER_START;
		if ($this->is_html)
		{
			$start_sign = '<';
			$end_sign = '>';
		}
		else
		{
			$start_sign = '[';
			$end_sign = ']';
		}

		while ($start_pos < $strlen)
		{
			switch ($state)
			{
				case PARSER_START:
					$tag_open_pos = strpos($text, $start_sign, $start_pos);
					if ($tag_open_pos === false)
					{
						$internal_data = array('start' => $start_pos, 'end' => $strlen);
						$state = PARSER_TEXT;
					}
					else if ($tag_open_pos != $start_pos)
					{
						$internal_data = array('start' => $start_pos, 'end' => $tag_open_pos);
						$state = PARSER_TEXT;
					}
					else
					{
						$start_pos = $tag_open_pos + 1;
						if ($start_pos >= $strlen)
						{
							$internal_data = array('start' => $tag_open_pos, 'end' => $strlen);
							$start_pos = $tag_open_pos;
							$state = PARSER_TEXT;
						}
						else
						{
							$state = PARSER_TAG_OPENED;
						}
					}
				break;

				case PARSER_TEXT:
					$end = end($output);
					if ($end['type'] == 'text')
					{
						$key = key($output);
						$output[$key]['data'] .= substr($text, $internal_data['start'], $internal_data['end'] - $internal_data['start']);
					}
					else
					{
						$output[] = array(
							'type' => 'text',
							'data' => substr($text, $internal_data['start'], $internal_data['end'] - $internal_data['start']),
						);
					}

					$start_pos = $internal_data['end'];
					$state = PARSER_START;
				break;

				case PARSER_TAG_OPENED:
					$tag_close_pos = strpos($text, $end_sign, $start_pos);
					if ($tag_close_pos === false)
					{
						$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
						$state = PARSER_TEXT;
						break;
					}

					$closing_tag = ($text[$start_pos] == '/');
					if ($closing_tag)
					{
						++$start_pos;
					}

					$single_tag = ($text[$tag_close_pos - 1] == '/');

					if ($this->is_html)
					{
						$tag_opt_start_pos = strpos($text, ' ', $start_pos);
					}
					else
					{
						$tag_opt_start_pos = strpos($text, '=', $start_pos);
						$opt_start_pos = strpos($text, ' ', $start_pos);
						if (($tag_opt_start_pos !== false && $opt_start_pos !== false && $opt_start_pos < $tag_opt_start_pos) || ($tag_opt_start_pos === false && $opt_start_pos !== false))
						{
							$tag_opt_start_pos = $opt_start_pos;
						}

						if ($single_tag && $tag_opt_start_pos === strpos($text, ' /', $start_pos))
						{
							$tag_opt_start_pos = false;
						}
					}

					if ($closing_tag || $tag_opt_start_pos === false || $tag_opt_start_pos > $tag_close_pos)
					{
						$tag_name = strtolower(substr($text, $start_pos, $tag_close_pos - $start_pos));
						$tag_name = ($single_tag) ? substr($tag_name, 0, -1) : $tag_name;
						$tag_name = trim($tag_name);

						if ($this->is_valid_tag($tag_name))
						{
							$str_start = $start_pos - ($closing_tag ? 2 : 1);
							$source = substr($text, $str_start, $tag_close_pos - $str_start + 1);
							$output[] = array(
								'type' => 'tag',
								'name' => $tag_name,
								'option' => false,
								'closing' => ($closing_tag || $single_tag),
								'single' => $single_tag,
								'source' => $source
							);

							$start_pos = $tag_close_pos + 1;
							$state = PARSER_START;
						}
						else
						{
							$internal_data = array('start' => $start_pos - 1 - ($closing_tag ? 1 : 0), 'end' => $start_pos);
							$state = PARSER_TEXT;
						}
					}
					else
					{
						$tag_name = strtolower(substr($text, $start_pos, $tag_opt_start_pos - $start_pos));

						if (!$this->is_valid_tag($tag_name))
						{
							$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
							$state = PARSER_TEXT;
							break;
						}

						if ($this->is_html || $opt_start_pos === $tag_opt_start_pos)
						{
							$tag_option = '';
							$tag_options = substr($text, $tag_opt_start_pos + 1, $tag_close_pos - $tag_opt_start_pos - 1);
							$tag_options = ($single_tag) ? substr($tag_options, 0, -1) : $tag_options;
							$tag_options = trim($tag_options);
							if (strpos($tag_options, ' ') !== false)
							{
								$matches = array();
								preg_match_all('/[a-zA-Z0-9]+=(&quot;|"|\').*(\\1)/U', $tag_options, $matches);
								$matches = $matches[0];
								$tag_options = trim(str_replace($matches, '', $tag_options));
								if (strpos($tag_options, ' ') !== false)
								{
									$tag_options = explode(' ', $tag_options);
									$tag_options = array_merge($tag_options, $matches);
								}
								else
								{
									$tag_options = $matches;
								}
								$n = count($tag_options);
							}
							else
							{
								$tag_options = array($tag_options);
								$n = 1;
							}

							for ($i = 0; $i < $n; $i++)
							{
								$tag_options[$i] = trim($tag_options[$i]);
								if (empty($tag_options[$i]))
								{
									continue;
								}

								if (strpos($tag_options[$i], '=') === false)
								{
									$tag_option[$tag_options[$i]] = $tag_options[$i];
								}
								else
								{
									$option = explode('=', $tag_options[$i]);
									$option[0] = trim($option[0]);
									if (empty($option[0]))
									{
										continue;
									}

									$match = '';
									if (preg_match('/^(&quot;|"|\')?(.*)(\\1)$/', $option[1], $match))
									{
										$tag_option[$option[0]] = trim($match[2]);
									}
								}
							}
							$delim_len = 1;
						}
						else
						{
							$delimiter = $text[$tag_opt_start_pos + 1];
							if ($delimiter == '&' && substr($text, $tag_opt_start_pos + 2, 5) == 'quot;')
							{
								$delimiter = '&quot;';
								$delim_len = 7;
							}
							else if ($delimiter != '"' && $delimiter != "'")
							{
								$delimiter = '';
								$delim_len = 1;
							}
							else
							{
								$delim_len = 2;
							}

							if ($delimiter != '')
							{
								$close_delim = strpos($text, $delimiter . $end_sign, $tag_opt_start_pos + $delim_len);
								if ($close_delim === false)
								{
									$delimiter = '';
									$delim_len = 1;
								}
								else
								{
									$tag_close_pos = $close_delim;
								}
							}

							$str_start = $tag_opt_start_pos + $delim_len;
							$tag_option = substr($text, $str_start, $tag_close_pos - $str_start);
						}

						if ($this->is_valid_option($tag_name, $tag_option))
						{
							$source = substr($text, $start_pos - 1, $tag_close_pos - $start_pos + 2);
							$output[] = array(
								'type' => 'tag',
								'name' => $tag_name,
								'option' => $tag_option,
								'closing' => $single_tag,
								'single' => $single_tag,
								'source' => $source
							);

							$start_pos = $tag_close_pos + $delim_len;
							$state = PARSER_START;
						}
						else
						{
							$internal_data = array('start' => $start_pos - 1, 'end' => $start_pos);
							$state = PARSER_TEXT;
						}
					}
				break;
			}
		}
		return $this->fix_tags($output);
	}

	/**
	 * 遍历数组修正嵌套和搭配错误的标签
	 *
	 * @param array $preparsed 由 build_parse_array 生成的数组
	 * @return array
	 */
	private function fix_tags($preparsed)
	{
		$output = array();
		$stack = array();
		$noparse = null;

		foreach ($preparsed as $node_key => $node)
		{
			if ($noparse !== null && $node['type'] == 'tag' && ($node['single'] == true || $node['closing'] == false))
			{
				$output[] = array(
					'type' => 'text',
					'data' => $this->fetch_tag($node)
				);
				continue;
			}

			if ($node['type'] == 'text' || $node['single'] == true)
			{
				$output[] = $node;
			}
			else if ($node['closing'] == false)
			{
				$output[] = $node;
				end($output);

				$node['added_list'] = array();
				$node['my_key'] = key($output);
				array_unshift($stack, $node);

				if (!empty($this->tag_list[$node['name']]['stop_parse']))
				{
					$noparse = $node_key;
				}
			}
			else
			{
				if (($key = $this->find_first_tag($node['name'], $stack)) !== false)
				{
					if (!empty($this->tag_list[$node['name']]['stop_parse']))
					{
						if ($key != 0)
						{
							for ($i = 0; $i < $key; $i++)
							{
								$output[] = $stack[$i];
								unset($stack[$i]);
							}
						}

						$output[] = $node;

						unset($stack[$key]);
						$stack = array_values($stack);

						$noparse = null;

						continue;
					}
					else if ($noparse !== null)
					{
						$output[] = $node;

						unset($stack[$key]);
						$stack = array_values($stack);

						continue;
					}

					if ($key != 0)
					{
						end($output);
						$max_key = key($output);

						for ($i = 0; $i < $key; $i++)
						{
							$output[] = array(
								'type' => 'tag',
								'name' => $stack[$i]['name'],
								'closing' => true,
								'single' => false,
							);
							$max_key++;
							$stack[$i]['added_list'][] = $max_key;
						}
					}

					$output[] = $node;

					if ($key != 0)
					{
						$max_key++;

						for ($i = $key - 1; $i >= 0; $i--)
						{
							$output[] = $stack[$i];
							$max_key++;
							$stack[$i]['added_list'][] = $max_key;
						}
					}

					unset($stack[$key]);
					$stack = array_values($stack);
				}
				else
				{
					$output[] = array(
						'type' => 'text',
						'data' => $this->fetch_tag($node)
					);
				}
			}
		}

		foreach ($stack as $open)
		{
			foreach ($open['added_list'] as $node_key)
			{
				unset($output[$node_key]);
			}
			$output[$open['my_key']] = array(
				'type' => 'text',
				'data' => $this->fetch_tag($open)
			);
		}

		return $output;
	}

	/**
	 * 把数组转换成 HTML
	 *
	 * @param array Parse array
	 * @param bool Whether to parse smilies
	 *
	 * @return	string	Final HTML
	 */
	protected function parse_array($preparsed, $do_smilies = true)
	{
		$output = '';

		$this->stack = array();
		$stack_size = 0;

		$parse_options = array(
			'no_parse' => 0,
			'no_smilies' => 0,
			'do_entity' => 1,
			'strip_space_after' => 0
		);

		foreach ($preparsed as $node)
		{
			$pending_text = '';
			if ($node['type'] === 'text')
			{
				$pending_text = &$node['data'];

				// remove leading space after a tag
				if ($parse_options['strip_space_after'] && !$this->is_html)
				{
					$pending_text = $this->strip_front_back_whitespace($pending_text, $parse_options['strip_space_after'], true, false);
					$parse_options['strip_space_after'] = 0;
				}

				// parse smilies
				if ($do_smilies && !$parse_options['no_smilies'])
				{
					$pending_text = $this->parse_smilies($pending_text);
				}

				if ($parse_options['do_entity'])
				{
					$pending_text = $this->do_entity($pending_text);
				}

				$pending_text = $this->convert_br($pending_text);
			}
			else if ($node['closing'] == false)
			{
				$parse_options['strip_space_after'] = 0;

				if ($parse_options['no_parse'] == 0)
				{
					// opening a tag
					// initialize data holder and push it onto the stack
					$node['data'] = '';
					array_unshift($this->stack, $node);
					++$stack_size;

					$tag_info = &$this->tag_list[$node['name']];

					// setup tag options
					if (!empty($tag_info['stop_parse']))
					{
						$parse_options['no_parse'] = 1;
					}
					if (!empty($tag_info['disable_entity']))
					{
						$parse_options['do_entity'] = 1;
					}
					if (!empty($tag_info['disable_smilies']))
					{
						$parse_options['no_smilies']++;
					}
				}
				else
				{
					$pending_text = $this->do_entity($this->fetch_tag($node));
				}
			}
			else
			{
				$parse_options['strip_space_after'] = 0;

				// closing a tag
				// look for this tag on the stack
				if (($key = $this->find_first_tag($node['name'], $this->stack)) !== false)
				{
					// found it
					$open = &$this->stack[$key];
					$this->current_tag = &$open;

					// check to see if this version of the tag is valid
					if (isset($this->tag_list[$open['name']]))
					{
						$tag_info = &$this->tag_list[$open['name']];

						// make sure we have data between the tags
						if (!empty($this->tag_list[$open['name']]['can_empty']) || trim($open['data']) != '')
						{
							// make sure our data matches our pattern if there is one
							if (empty($tag_info['data_regex']) || preg_match($tag_info['data_regex'], $open['data']))
							{
								// see if the option might have a tag, and if it might, run a parser on it
								if (!empty($tag_info['parse_option']) && strpos($open['option'], '[') !== false)
								{
									$old_stack = $this->stack;
									$open['option'] = $this->convert($open['option']);
									$this->stack = $old_stack;
									unset($old_stack);
								}

								// now do the actual replacement
								if (isset($tag_info['html']))
								{
									// this is a simple HTML replacement
									$pending_text = sprintf($tag_info['html'], $open['data'], $open['option']);
								}
								else if (isset($tag_info['callback']))
								{
									// call a callback function
									$pending_text = $this->$tag_info['callback']($open['data'], $open['option']);
								}
							}
							else
							{
								$pending_text = $this->do_entity($this->fetch_tag($open) . $open['data'] . $this->fetch_tag($node));
							}
						}

						if (!empty($tag_info['strip_space_after']))
						{
							$parse_options['strip_space_after'] = $tag_info['strip_space_after'];
						}
						if (!empty($tag_info['stop_parse']))
						{
							$parse_options['no_parse'] = 0;
						}
						if (!empty($tag_info['disable_entity']))
						{
							$parse_options['do_entity'] = 0;
						}
						if (!empty($tag_info['disable_smilies']))
						{
							$parse_options['no_smilies']--;
						}
					}

					if ($this->is_html && empty($pending_text))
					{
						$open['source'] = $node['source'] = '';
						$pending_text = $this->fetch_tag($open) . $open['data'] . $this->fetch_tag($node);
					}

					unset($this->stack[$key]);
					--$stack_size;
					$this->stack = array_values($this->stack); // this is a tricky way to renumber the stack's keys
				}
				else
				{
					$this->current_tag = &$node;
					$tag_info = &$this->tag_list[$node['name']];
					if (isset($tag_info['html']))
					{
						$pending_text = sprintf($tag_info['html'], $node['option']);
					}
					else if (isset($tag_info['callback']))
					{
						// call a callback function
						$pending_text = $this->$tag_info['callback']('', $node['option']);
					}
					else
					{
						$pending_text = $this->fetch_tag($node);
					}
				}
			}

			if ($stack_size == 0)
			{
				$output .= $pending_text;
			}
			else
			{
				$this->stack[0]['data'] .= $pending_text;
			}
		}

		return trim($output);
	}

	/**
	 * Find the first instance of a tag in an array
	 *
	 * @param string Name of tag
	 * @param array Array to search
	 * @return int/false Array key of first instance; false if it does not exist
	 */
	private function find_first_tag($tag_name, &$stack)
	{
		foreach ($stack as $key => $node)
		{
			if ($node['name'] == $tag_name)
			{
				return $key;
			}
		}
		return false;
	}

	/**
	 * 将标签数组形式变为字符串形式
	 *
	 * @param array $array 标签
	 * @return string
	 */
	protected function fetch_tag($array)
	{
		$return = '';
		if (is_array($array))
		{
			if (!empty($array['source']))
			{
				$return = $array['source'];
			}
			else
			{
				$return = $this->is_html ? '<' : '[';
				if ($array['closing'] && !$array['single'])
				{
					$return .= '/';
					$array['option'] = '';
				}
				$return .= $array['name'];
				if (!empty($array['option']))
				{
					if (is_array($array['option']))
					{
						$default_optione = false;
						if (isset($this->tag_list[$array['name']]['option']['default']) && count($array['option']) == 1)
						{
							$default_optione = true;
						}

						foreach ($array['option'] as $k => $v)
						{
							if ($default_optione && $k === $this->tag_list[$array['name']]['option']['default'])
							{
								$return .= '=' . $v;
								break;
							}
							$return .= ' ' . $k . '="' . $v . '"';
						}
					}
					else if (!$this->is_html)
					{
						$return .= '=' . $array['option'];
					}
				}
				$return .= $array['single'] ? ' /' : '';
				$return .= $this->is_html ? '>' : ']';
			}
		}

		return $return;
	}

	protected function fetch_node($tag_name, $text, $option = '')
	{
		$node = array(
			'name' => $tag_name,
			'option' => $option,
			'closing' => false,
			'single' => false,
		);
		$return = $this->fetch_tag($node). $text;
		$node['closing'] = true;
		$return .= $this->fetch_tag($node);

		return $return;
	}

	protected function do_entity($text)
	{
		return str_replace(array('<', '>'), array('&lt;', '&gt;'), $text);
	}

	/**
	 * 将表情字符转换成 HTML
	 *
	 * @param	string	Text with smilie codes
	 * @return	string	Text with HTML images in place of smilies
	 */
	private function parse_smilies($text)
	{
		$quoted = array();
		foreach ($this->smilies as $find => $replace)
		{
			$quoted[] = preg_quote($find, '/');
			if (count($quoted) > 100)
			{
				$text = preg_replace_callback('/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s', array($this, 'replace_smilies'), $text);
				$quoted = array();
			}
		}

		if (count($quoted) > 0)
		{
			$text = preg_replace_callback('/(?<!&amp|&quot|&lt|&gt|&copy|&#[0-9]{1}|&#[0-9]{2}|&#[0-9]{3}|&#[0-9]{4}|&#[0-9]{5})(' . implode('|', $quoted) . ')/s', array($this, 'replace_smilies'), $text);
		}

		return $text;
	}

	private function replace_smilies($matches)
	{
		return $this->smilies[$matches[0]];
	}

	/**
	 * 移除解释后的表情
	 *
	 * @param string Text to search
	 * @return string Text with smilie HTML returned to smilie codes
	 */
	protected function strip_smilies($text)
	{
		return str_replace($this->smilies, array_keys($this->smilies), $text);
	}

	/**
	 * RGB 颜色代码转换成 16 进制颜色代码
	 *
	 * @param string $style RGB 颜色 CSS 代码
	 * @return string 16 进制颜色代码
	 */
	protected function rgb2hex($style)
	{
		if (strpos($style, 'rgb('))
		{
			$style = preg_replace('#rgb\((\d+),\s*(\d+),\s*(\d+)\);?#ie', 'sprintf(\'#%02X%02X%02X$4\', $1, $2, $3)', $style);
		}
		return $style;
	}

	/**
	 * 删除开头和结尾空白符，包括HTML的换行
	 *
	 * @param string	处理的文本
	 * @param int 删除的数量
	 * @param bool 是否处理开头部分
	 * @param bool 是否处理结尾部分
	 */
	function strip_front_back_whitespace($text, $max_amount = 1, $strip_front = true, $strip_back = true)
	{
		$max_amount = intval($max_amount);

		if ($strip_front)
		{
			$text = preg_replace('#^(( |\t)*((<br>|<br />)[\r\n]*)|\r\n|\n|\r){0,' . $max_amount . '}#si', '', $text);
		}

		if ($strip_back)
		{
			$text = strrev(preg_replace('#^(((>rb<|>/ rb<)[\n\r]*)|\n\r|\n|\r){0,' . $max_amount . '}#si', '', strrev(rtrim($text))));
		}

		return $text;
	}

	abstract protected function convert_br($text);
	abstract protected function is_valid_tag($tag_name);
	abstract protected function is_valid_option($tag_name, &$tag_option);
}