<?php
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
	'callback' => 'handleDiv',
	'strip_space_after' => 1
);

// SPAN
$tag_list['span'] = array(
	'callback' => 'handleSpan',
);

// P
$tag_list['p'] = array(
	'callback' => 'handleP',
	'strip_space_after' => 1
);

// INDENT
$tag_list['blockquote'] = array(
	'html' => '<blockquote>%1$s</blockquote>',
	'strip_space_after' => 1
);

// URL
$tag_list['a'] = array(
	'callback' => 'handleUrl',
);

// CODE
$tag_list['pre'] = array(
	'callback' => 'handlePre',
	'stop_parse' => true,
	'disable_smilies' => true,
	'do_entity' => false,
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
	'callback' => 'handleImg',
);