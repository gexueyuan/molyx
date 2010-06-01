<?php
class parse_htmltag
{
	public static function get_tag_list()
	{
		static $tag_list = array();

		if (empty($tag_list))
		{
			$tag_list = array();

			// FONT
			$tag_list['font'] = array(
				'option' => array(
					'face' => array('regex' => '#^[0-9\+\-]+$#',),
					'color' => array('regex' => '#^\#?\w+$#'),
					'size' => array('regex' => '#^[0-9\+\-]+$#'),
				),
			);

			// DIV
			$tag_list['div'] = array(
				'callback' => 'handle_div',
				'strip_space_after' => 1
			);

			// SPAN
			$tag_list['span'] = array(
				'callback' => 'handle_span',
			);

			// P
			$tag_list['p'] = array(
				'callback' => 'handle_p',
				'strip_space_after' => 1
			);

			// INDENT
			$tag_list['blockquote'] = array(
				'html' => '<blockquote>%1$s</blockquote>',
				'strip_space_after' => 1
			);

			// URL
			$tag_list['a'] = array(
				'callback' => 'handle_url',
			);

			// CODE
			$tag_list['pre'] = array(
				'callback' => 'handle_pre',
				'stop_parse' => true,
				'disable_smilies' => true,
				'disable_entity' => true,
				'strip_space_after' => 1
			);

			//TEXTAREA
			$tag_list['textarea'] = array(
				'stop_parse' => true,
				'disable_entity' => true,
				'disable_smilies' => true,
				'strip_space_after' => 1
			);

			// IMG
			$tag_list['img'] = array(
				'callback' => 'handle_img',
			);
		}

		return $tag_list;
	}
}
?>