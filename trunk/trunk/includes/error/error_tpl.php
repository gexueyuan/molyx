<!-- $Id: error_tpl.php 64 2007-09-07 09:19:11Z hogesoft-02 $ -->
<fieldset style="margin-left: 1%; margin-right: 1%; border: 4px double #000; font-size: 12px; font-family: Tahoma, Verdana, Georgia, Courier, Simsun;">
<legend style="color: #22229C; font-weight: bold; font-size: 14px;"><?php echo $lang[$errtype[$errno][0]]; ?></legend>
<pre style="margin-left: 1%; margin-right: 1%;">
<strong><?php echo $lang['error_type']; ?>:</strong> <?php echo $errtype[$errno][1]; ?>
<?php
global $bbuserinfo;
if ($this->debug || $bbuserinfo['usergroupid'] == 4)
{
	echo "\n<strong>" . $lang['error_message'] . ':</strong> ' . $errstr;
}
echo "\n";
echo '<strong>' . $lang['list_file'] . ':</strong> ';
echo $this->replace_dir($errfile);
if ($this->debug || $bbuserinfo['usergroupid'] == 4)
{
	echo ' (<a onmouseout="this.style.color=\'#007700\'" onmouseover="this.style.color=\'#FF6600\'" style="color: #007700; text-decoration: none;" target="_blank" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($errfile) . '&line=' . $errline . '">' . $lang['list_line'] . ': ' . $errline . '</a>) ';
}
echo "\n\n";

$count_trace = count($trace);
if ($count_trace)
{
	echo $lang['error_trace'] . ': ' . $count_trace . ' ';
	echo '<span style="cursor: pointer;" onclick="showDetails('.$count_trace.')">[' . $lang['show_details'] . ']</span> ';
	echo '<span style="cursor: pointer;" onclick="hideDetails('.$count_trace.')">[' . $lang['hide_details'] . ']</span>';
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
					$string = $lang['type_string'] . '[' . strlen($arg) . ']';
				break;

				case is_array($arg):
					$param = var_export($arg, true);
					$string = $lang['type_array'] . '[' . count($arg) . ']';
				break;

				case is_object($arg):
					$param = get_class($arg);
					$string = $lang['type_object'] . ': ' . $param;
				break;

				case is_resource($arg):
					$param = $lang['type_resource'] . ': ' . get_resource_type($arg);
					$string = $lang['type_resource'];
				break;

				default:
					$param = $lang['type_unknown'];
					$string = $param;
				break;
			}

			echo '<span style="cursor: pointer; color: ' . $color . ';" onclick="showOrHideParam(' . $current_param . ')" onmouseout="this.style.color=\'' . $color . '\'" onmouseover="this.style.color=\'#dd0000\'">';
			echo $string;
			echo '</span>';
			echo '<span id="param'.$current_param.'" style="display: none;">' . $param . '</span>';
		}

		echo ")\n";

		if (empty($v['file']))
		{
			$v['file'] = $lang['type_unknown'];
		}
		if (!isset($v['line']) && is_numeric($v['line']))
		{
			$v['line'] = $lang['type_unknown'];
		}

		echo '<span id="file' . $k . '" style="display: none; color: gray;">';
		if ($v['file'] != $lang['type_unknown'] && $v['line'] != $lang['type_unknown'])
		{
			echo $lang['list_file'] . ': <a onmouseout="this.style.color=\'#007700\'" onmouseover="this.style.color=\'#FF6600\'" style="color: #007700; text-decoration: none;" target="_blank" href="' . ROOT_PATH . 'showsource.php?file=' . urlencode($v['file']) . '&line=' . $v['line'] . '">' . basename($v['file']) . '</a>';
		}
		else
		{
			echo $lang['list_file'] . ': <span style="color: #007700">' . basename($v['file']) . '</span>';
		}
		echo "\n";
		echo $lang['list_line'] . ': <span style="color: #007700">' . $v['line'] . '</span>' . "\n";
		echo $lang['list_dir'] . ':  <span style="color: #007700">' . dirname($v['file']) . '</span>';
		echo '</span>';

		echo '</li>';
	}

	echo '</ul>';
	echo '<span id="paramHide" style="display: none; cursor: pointer;" onclick="hideParam()">[' . $lang['hide_param'] . "]</span>\n";
	echo '<span id="paramSpace" style="display: none;"></span>' . "\n";
	echo '<div id="param" perm="0" style="background-color: #FFFFE1; padding: 2px; display: none;"></div>';
}
?>
</pre>
</fieldset>