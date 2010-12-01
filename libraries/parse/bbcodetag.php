<?php
$tag_list = array();

// quote, quote=xxx
$tag_list['quote'] = array(
	'callback' => 'handle_quote',
	'parse_option' => true,
	'strip_space_after' => 1
);

// STRONG
$tag_list['strong'] = array(
	'html' => '<strong>%1$s</strong>',
);

// B
$tag_list['b'] = array(
	'html' => '<strong>%1$s</strong>',
);

// I
$tag_list['i'] = array(
	'html' => '<i>%1$s</i>',
);

// U
$tag_list['u'] = array(
	'html' => '<u>%1$s</u>',
);

// S
$tag_list['s'] = array(
	'html' => '<s>%1$s</s>',
);

// SUB
$tag_list['sub'] = array(
	'html' => '<sub>%1$s</sub>',
);

// SUP
$tag_list['sup'] = array(
	'html' => '<sup>%1$s</sup>',
);

// EM
$tag_list['em'] = array(
	'html' => '<em>%1$s</em>',
);

// INDENT
$tag_list['indent'] = array(
	'html' => '<blockquote>%1$s</blockquote>',
);

// BGCOLOR
$tag_list['bgcolor'] = array(
	'html' => '<span style="background-color: %2$s;">%1$s</span>',
	'option' => true
);

// HR
$tag_list['hr'] = array(
	'html' => '<hr style="width: 100%; height: 2px;" />',
	'can_empty' => true
);

// BR
$tag_list['br'] = array(
	'html' => '<br />',
	'can_empty' => true
);

// COLOR=XXX
$tag_list['color'] = array(
	'html' => '<font color="%2$s">%1$s</font>',
	'option' => array(
		'default' => 'color',
		'color' => array('regex' => '#^\#?\w+$#')
	),
);

// SIZE=XXX
$tag_list['size'] = array(
	'html' => '<font size="%2$s">%1$s</font>',
	'option' => array(
		'default' => 'size',
		'size' => array('regex' => '#^[0-9\+\-]+$#')
	),
);

// FONT=XXX
$tag_list['font'] = array(
	'html' => '<font face="%2$s">%1$s</font>',
	'option' => array(
		'default' => 'face',
		'face' => array('regex' => '#^[^["`\':]+$#')
	),
);

// LEFT
$tag_list['left'] = array(
	'html' => '<div align="left">%1$s</div>',
	'strip_space_after' => 1
);

// CENTER
$tag_list['center'] = array(
	'html' => '<div align="center">%1$s</div>',
	'strip_space_after' => 1
);

// RIGHT
$tag_list['right'] = array(
	'html' => '<div align="right">%1$s</div>',
	'strip_space_after' => 1
);

// LIST, LIST=XXX
$tag_list['list'] = array(
	'callback' => 'handle_list',
	'option' => true,
	'strip_space_after' => 1
);

// EMAIL, EMAIL=XXX
$tag_list['email'] = array(
	'callback' => 'handle_email',
	'option' => true
);

// URL, URL=XXX
$tag_list['url'] = array(
	'callback' => 'handle_url',
	'option' => true
);

// IMG
$tag_list['img'] = array(
	'callback' => 'handle_img',
);

//CODE
$tag_list['code'] = array(
	'callback' => 'handle_code',
	'stop_parse' => true,
	'disable_smilies' => true,
	'option' => true,
	'strip_space_after' => 1
);