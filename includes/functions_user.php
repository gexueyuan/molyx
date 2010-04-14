<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# copyright (c) 2004-2006 HOGE Software.
# official forum : http://molyx.com
# license : MolyX License, http://molyx.com/license
# MolyX2 is free software. You can redistribute this file and/or modify
# it under the terms of MolyX License. If you do not accept the Terms
# and Conditions stated in MolyX License, please do not redistribute
# this file.Please visit http://molyx.com/license periodically to review
# the Terms and Conditions, or contact HOGE Software.
#
# $Id$
# **************************************************************************#
class functions_user
{
	function fetch_timezone()
	{
		global $forums;
		$timezones = array('-12' => '(GMT -12:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-12'],
			'-11' => '(GMT -11:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-11'],
			'-10' => '(GMT -10:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-10'],
			'-9' => '(GMT -9:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-9'],
			'-8' => '(GMT -8:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-8'],
			'-7' => '(GMT -7:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-7'],
			'-6' => '(GMT -6:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-6'],
			'-5' => '(GMT -5:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-5'],
			'-4' => '(GMT -4:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-4'],
			'-3.5' => '(GMT -3:30) &nbsp; &nbsp; ' . $forums->lang['_gmt-35'],
			'-3' => '(GMT -3:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-3'],
			'-2' => '(GMT -2:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-2'],
			'-1' => '(GMT -1:00) &nbsp; &nbsp; ' . $forums->lang['_gmt-1'],
			'0' => '(GMT) &nbsp; &nbsp; ' . $forums->lang['_gmt+0'],
			'1' => '(GMT +1:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+1'],
			'2' => '(GMT +2:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+2'],
			'3' => '(GMT +3:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+3'],
			'3.5' => '(GMT +3:30) &nbsp; &nbsp; ' . $forums->lang['_gmt+35'],
			'4' => '(GMT +4:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+4'],
			'4.5' => '(GMT +4:30) &nbsp; &nbsp; ' . $forums->lang['_gmt+45'],
			'5' => '(GMT +5:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+5'],
			'5.5' => '(GMT +5:30) &nbsp; &nbsp; ' . $forums->lang['_gmt+55'],
			'6' => '(GMT +6:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+6'],
			'7' => '(GMT +7:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+7'],
			'8' => '(GMT +8:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+8'],
			'9' => '(GMT +9:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+9'],
			'9.5' => '(GMT +9:30) &nbsp; &nbsp; ' . $forums->lang['_gmt+95'],
			'10' => '(GMT +10:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+10'],
			'11' => '(GMT +11:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+11'],
			'12' => '(GMT +12:00) &nbsp; &nbsp; ' . $forums->lang['_gmt+12']
			);
		return $timezones;
	}

	function show_gif_img($imagecode = '')
	{
		$img = '';
		$strlen = strlen($imagecode);
		for ($i = 0; $i < $strlen; $i++)
		{
			$char = $imagecode{$i};
			$img .= "<img src='" . ROOT_PATH . "images/antispam/" . $char . ".gif' alt='' />";
		}
		return $img;
	}

	function show_gd_img($content = "", $small_img = 0)
	{
		global $forums, $DB, $bboptions;
		$content = '  ' . preg_replace("/(\w)/", "\\1 ", $content) . ' ';
		ob_end_clean();
		@header("Content-Type: image/jpeg");
		$tmp_x = $small_img ? 95 : 140;
		$tmp_y = 20;
		$image_x = $small_img ? $tmp_x : 210;
		$image_y = $small_img ? $tmp_y : 65;
		$circles = 3;
		if (function_exists('imagecreatetruecolor'))
		{
			$tmp = imagecreatetruecolor($tmp_x, $tmp_y);
			$im = imagecreatetruecolor($image_x, $image_y);
		}
		else
		{
			$tmp = imagecreate($tmp_x, $tmp_y);
			$im = imagecreate($image_x, $image_y);
		}

		$white = ImageColorAllocate($tmp, 255, 255, 255);
		$black = ImageColorAllocate($tmp, 0, 0, 0);
		$grey = ImageColorAllocate($tmp, 210, 210, 210);
		imagefill($tmp, 0, 0, $white);

		imagestring($tmp, 5, 0, 2, $content, $black);
		imagecopyresized($im, $tmp, 0, 0, 0, 0, $image_x, $image_y, $tmp_x, $tmp_y);
		imagedestroy($tmp);

		$random_pixels = $image_x * $image_y / 10;
		for ($i = 0; $i < $random_pixels; $i++)
		{
			ImageSetPixel($im, rand(0, $image_x), rand(0, $image_y), ImageColorAllocate($im, rand(0, 255), rand(0, 255), rand(0, 255)));
		}
		if (!$small_img)
		{
			$no_x_lines = ($image_x - 1) / 5;
			for ($i = 0; $i <= $no_x_lines; $i++)
			{
				ImageLine($im, $i * $no_x_lines, 0, $i * $no_x_lines, $image_y, ImageColorAllocate($im, rand(0, 255), rand(0, 255), rand(0, 255)));
			}
			$no_y_lines = ($image_y - 1) / 5;
			for ($i = 0; $i <= $no_y_lines; $i++)
			{
				ImageLine($im, 0, $i * $no_y_lines, $image_x, $i * $no_y_lines, ImageColorAllocate($im, rand(0, 255), rand(0, 255), rand(0, 255)));
			}
		}
		ImageJPEG($im);
		ImageDestroy($im);
		exit();
	}
}

?>