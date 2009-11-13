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
# $Id: functions_image.php 347 2007-11-04 14:19:48Z develop_tong $
# **************************************************************************#
class functions_image
{
	var $filepath = '.';
	var $thumb_file = '';
	var $filename = '';
	var $thumbswidth = 0;
	var $thumbsheight = 0;
	var $thumb_filename = '';
	var $forcemake = 0; //是否在图片大小不足时仍旧生成
	var $maketype = 0; //生成方式，默认等比， 1为裁减，2为右上角裁减，3位中间裁减
	var $cutpos = 3; //裁减位置， 1为左上角裁减，2为右上角裁减，3位中间裁减

	function generate_thumbnail()
	{
		$return = array();
		$image = '';
		$thumb = '';
		$this->filepath = preg_replace("#/$#", '', $this->filepath);
		if ($this->filepath AND $this->filename)
		{
			$this->thumb_file = $this->filepath . '/' . $this->filename;
		}
		else
		{
			$this->thumb_file = $this->filename;
		}
		$remap = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
		if ($this->thumbswidth AND $this->thumbsheight)
		{
			$filesize = @GetImageSize($this->thumb_file);
			if (!$this->forcemake AND $filesize[0] < 1 AND $filesize[1] < 1)
			{
				$filesize = array();
				$filesize[0] = $this->thumbswidth;
				$filesize[1] = $this->thumbsheight;
				$return['thumbwidth'] = $this->thumbswidth;
				$return['thumbheight'] = $this->thumbsheight;
				$return['thumblocation'] = $this->filename;
				return $return;
			}
			if ($this->forcemake OR $filesize[0] > $this->thumbswidth OR $filesize[1] > $this->thumbsheight)
			{
				$im = $this->scale_image(array('max_width' => $this->thumbswidth,
						'max_height' => $this->thumbsheight,
						'cur_width' => $filesize[0],
						'cur_height' => $filesize[1],
						)
					);
				$return['thumbwidth'] = $im['img_width'];
				$return['thumbheight'] = $im['img_height'];
				if ($remap[ $filesize[2] ] == 'gif')
				{
					if (function_exists('imagecreatefromgif'))
					{
						$image = @imagecreatefromgif($this->thumb_file);
						$type = 'gif';
					}
				}
				else if ($remap[ $filesize[2] ] == 'png')
				{
					if (function_exists('imagecreatefrompng'))
					{
						$image = @imagecreatefrompng($this->thumb_file);
						$type = 'png';
					}
				}
				else if ($remap[ $filesize[2] ] == 'jpg')
				{
					if (function_exists('imagecreatefromjpeg'))
					{
						$image = @imagecreatefromjpeg($this->thumb_file);
						$type = 'jpg';
					}
				}
				if ($image)
				{
					if (function_exists('imagecreatetruecolor'))
					{
						$thumb = @imagecreatetruecolor($im['img_width'], $im['img_height']);
						$alpha = imageColorAllocateAlpha($thumb, 255, 255, 255, 0); 
						ImageFilledRectangle($thumb, 0, 0, $im['img_width'], $im['img_height'], $alpha); 
						@imagecopyresampled($thumb, $image, 0, 0, $im['start_x'], $im['start_y'], $im['img_width'], $im['img_height'], $im['src_x'], $im['src_y']);
					}
					else
					{
						$thumb = @imagecreate($im['img_width'], $im['img_height']);
						@imagecopyresized($thumb, $image, 0, 0, $im['start_x'], $im['start_y'], $im['img_width'], $im['img_height'], $im['src_x'], $im['src_y']);
					}

					if (PHP_VERSION != '4.3.2')
					{
						$this->UnsharpMask($thumb);
					}
					if (! $this->thumb_filename)
					{
						$this->thumb_filename = 'thumb_' . preg_replace("/^(.*)\..+?$/", "\\1", $this->filename);
					}
					if (function_exists('imagejpeg'))
					{
						$this->file_extension = 'jpg';
						@imagejpeg($thumb, $this->filepath . "/" . $this->thumb_filename . '.jpg');
						@imagedestroy($thumb);
					}
					else if (function_exists('imagepng'))
					{
						$this->file_extension = 'png';
						@imagepng($thumb, $this->filepath . "/" . $this->thumb_filename . '.png');
						@imagedestroy($thumb);
					}
					else
					{
						$return['thumblocation'] = $this->filename;
						return $return;
					}
					$return['thumblocation'] = $this->thumb_filename . '.' . $this->file_extension;
					return $return;
				}
				else
				{
					$return['thumbwidth'] = $im['img_width'];
					$return['thumbheight'] = $im['img_height'];
					$return['thumblocation'] = $this->filename;
					return $return;
				}
			}
			else
			{
				$return['thumbwidth'] = $filesize[0];
				$return['thumbheight'] = $filesize[1];
				$return['thumblocation'] = $this->filename;
				return $return;
			}
		}
	}

	function scale_image($arg)
	{
		$ret = array('img_width' => $arg['cur_width'], 'img_height' => $arg['cur_height']);
		$ret['src_x'] = $arg['cur_width'];
		$ret['src_y'] = $arg['cur_height'];
		$ret['start_x'] = 0;
		$ret['start_y'] = 0;
		switch ($this->maketype)
		{
			case 0:
				if ($arg['cur_width'] > $arg['max_width'])
				{
					$ret['img_width'] = $arg['max_width'];
					$ret['img_height'] = ceil(($arg['cur_height'] * (($arg['max_width'] * 100) / $arg['cur_width'])) / 100);
					$arg['cur_height'] = $ret['img_height'];
					$arg['cur_width'] = $ret['img_width'];
				}
				if ($arg['cur_height'] > $arg['max_height'])
				{
					$ret['img_height'] = $arg['max_height'];
					$ret['img_width'] = ceil(($arg['cur_width'] * (($arg['max_height'] * 100) / $arg['cur_height'])) / 100);
				}
				break;
			case 1:
			{			
				$image_new_ratio = $arg['max_width'] / $arg['max_height'];
				$image_ratio = $arg['cur_width'] / $arg['cur_height'];
				if ($image_new_ratio > $image_ratio) 
				{
					$ret['src_y'] = $ret['src_y'] * ($image_ratio / $image_new_ratio);
				}
				else
				{
					$ret['src_x'] = $ret['src_x'] * ($image_new_ratio / $image_ratio);
				}
				switch ($this->cutpos)
				{
					case 2:
						$ret['start_x'] = $arg['cur_width'] - $ret['src_x'];
					break;
					case 3:
						$ret['start_x'] = ($arg['cur_width'] - $ret['src_x']) / 2;
						$ret['start_y'] = ($arg['cur_height'] - $ret['src_y']) / 2;
					break;
				}				
				$ret['img_height'] = $arg['max_height'];
				$ret['img_width'] = $arg['max_width'];
			}
		}
		return $ret;
	}

	function UnsharpMask($img, $amount = 100, $radius = .5, $threshold = 3)
	{
		$amount = min($amount, 500);
		$amount = $amount * 0.016;
		if ($amount == 0) return true;
		$radius = min($radius, 50);
		$radius = $radius * 2;
		$threshold = min($threshold, 255);
		$radius = abs(round($radius));
		if ($radius == 0) return true;
		$w = ImageSX($img);
		$h = ImageSY($img);
		$imgCanvas = ImageCreateTrueColor($w, $h);
		$imgCanvas2 = ImageCreateTrueColor($w, $h);
		$imgBlur = ImageCreateTrueColor($w, $h);
		$imgBlur2 = ImageCreateTrueColor($w, $h);
		ImageCopy($imgCanvas, $img, 0, 0, 0, 0, $w, $h);
		ImageCopy($imgCanvas2, $img, 0, 0, 0, 0, $w, $h);
		for ($i = 0; $i < $radius; $i++)
		{
			ImageCopy($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1);
			ImageCopyMerge($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50);
			ImageCopyMerge($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333);
			ImageCopyMerge($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25);
			ImageCopyMerge($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333);
			ImageCopyMerge($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25);
			ImageCopyMerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20);
			ImageCopyMerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // dow
			ImageCopyMerge($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50);
			ImageCopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);
			ImageCopy($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 33.33333);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 25);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 20);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 16.666667);
			ImageCopyMerge($imgBlur2, $imgCanvas2, 0, 0, 0, 0, $w, $h, 50);
			ImageCopy($imgCanvas2, $imgBlur2, 0, 0, 0, 0, $w, $h);
		}
		for ($x = 0; $x < $w; $x++)
		{
			for ($y = 0; $y < $h; $y++)
			{
				$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
				$rOrig = (($rgbOrig >> 16) &0xFF);
				$gOrig = (($rgbOrig >> 8) &0xFF);
				$bOrig = ($rgbOrig &0xFF);
				$rgbBlur = ImageColorAt($imgCanvas, $x, $y);
				$rBlur = (($rgbBlur >> 16) &0xFF);
				$gBlur = (($rgbBlur >> 8) &0xFF);
				$bBlur = ($rgbBlur &0xFF);
				$rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
				$gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
				$bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;
				if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
				{
					$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
					ImageSetPixel($img, $x, $y, $pixCol);
				}
			}
		}
		ImageDestroy($imgCanvas);
		ImageDestroy($imgCanvas2);
		ImageDestroy($imgBlur);
		ImageDestroy($imgBlur2);
		return true;
	}
}

?>