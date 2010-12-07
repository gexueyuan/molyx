<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2012 MolyX Group.
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
?>
<fieldset style="margin-left: 1%; margin-right: 1%; border: 4px double #000; font-size: 12px; font-family: Tahoma, Verdana, Georgia, Courier, Simsun;">
<legend style="color: #22229C; font-weight: bold; font-size: 14px;"><?php echo $errtype[$errno][0]; ?></legend>
<pre style="margin-left: 1%; margin-right: 1%;">
<strong>Type:</strong> <?php echo $errtype[$errno][1]; ?>
<?php
echo "\n<strong>Error Message:</strong> " . $errstr;
echo "\n";
echo '<strong>File:</strong> ';
echo $errfile;
if (self::$debug)
{
	echo ' (<a onmouseout="this.style.color=\'#007700\'" onmouseover="this.style.color=\'#FF6600\'" style="color: #007700; text-decoration: none;" target="_blank" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($errfile) . '&line=' . $errline . '"> Line: ' . $errline . '</a>) ';
}
echo "\n\n";

$count_trace = count($trace);
if ($count_trace)
{
	echo 'Backtrace: ' . $count_trace . ' ';
	echo '<span style="cursor: pointer;" onclick="showDetails('.$count_trace.')">[SHOW]</span> ';
	echo '<span style="cursor: pointer;" onclick="hideDetails('.$count_trace.')">[HIDE]</span>';
	echo "\n\n";

	echo '<ul>';
	$current_param = -1;

	foreach ($trace as $k => $v)
	{
		$current_param++;
		echo '<li style="list-style-type: square;">';
		if (isset($v['class']))
		{
			echo '<span onmouseover="this.style.color=\'#0000ff\'" onmouseout="this.style.color=\'' . $c['keyword'] . '\'" style="color: ' . $c['keyword'] . '; cursor: pointer;" onclick="showFile(' . $k . ')"     >';
			echo $v['class'];
			echo '.';
		}
		else
		{
			echo '<span onmouseover="this.style.color=\'#0000ff\'" onmouseout="this.style.color=\'' . $c['keyword'] . '\'" style="color: ' . $c['keyword'] . '; cursor: pointer;" onclick="showFile(' . $k . ')">';
		}

		echo $v['function'];
		echo '</span>';
		echo ' (';

		$sep = '';
		$v['args'] = isset($v['args']) ? (array) $v['args'] : array();
		foreach ($v['args'] as $arg)
		{
			$current_param++;

			echo $sep;
			$sep = ', ';
			$color = '#404040';

			switch (true)
			{
				case is_bool($arg):
					$param = $arg ? 'TRUE' : 'FALSE';
					$string = $param;
				break;

				case is_int($arg):
				case is_float($arg):
					$param = $arg;
					$string = $arg;
					$color = $c['number'];
				break;

				case is_null($arg):
					$param = 'NULL';
					$string = $param;
				break;

				case is_string($arg):
					$param = $arg;
					$string = 'String[' . strlen($arg) . ']';
				break;

				case is_array($arg):
					$param = var_export($arg, true);
					$string = 'Array[' . count($arg) . ']';
				break;

				case is_object($arg):
					$param = get_class($arg);
					$string = 'Object: ' . $param;
				break;

				case is_resource($arg):
					$param = 'Resource: ' . get_resource_type($arg);
					$string = 'Resource';
				break;

				default:
					$param = 'Unknown';
					$string = $param;
				break;
			}

			echo '<span style="cursor: pointer; color: ' . $color . ';" onclick="showOrHideParam(' . $current_param . ')" onmouseout="this.style.color=\'' . $color . '\'" onmouseover="this.style.color=\'#dd0000\'">';
			echo $string;
			echo '</span>';
			echo '<span id="param'.$current_param.'" style="display: none;">' . $param . '</span>';
		}

		echo ")\n";

		if (
			empty($v['file']) ||
			(!isset($v['line']) && is_numeric($v['line']))
		)
		{
			$v['file'] = 'Unknown';
		}

		echo '<span id="file' . $k . '" style="display: none; color: gray;">';
		if ($v['file'] != 'Unknown' && $v['line'] != 'Unknown')
		{
			echo 'File: <a onmouseout="this.style.color=\'#007700\'" onmouseover="this.style.color=\'#FF6600\'" style="color: #007700; text-decoration: none;" target="_blank" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($v['file']) . '&line=' . $v['line'] . '">' . basename($v['file']) . '</a>';
		}
		else
		{
			echo 'File: <span style="color: #007700">' . basename($v['file']) . '</span>';
		}
		echo "\n";
		echo 'Line: <span style="color: #007700">' . $v['line'] . '</span>' . "\n";
		echo 'Path:  <span style="color: #007700">' . dirname($v['file']) . '</span>';
		echo '</span>';

		echo '</li>';
	}

	echo '</ul>';
	echo '<span id="paramHide" style="display: none; cursor: pointer;" onclick="hideParam()">[HIDE PARAM]</span>' . "\n";
	echo '<span id="paramSpace" style="display: none;"></span>' . "\n";
	echo '<div id="param" perm="0" style="background-color: #FFFFE1; padding: 2px; display: none;"></div>';
}
?>
</pre>
</fieldset>