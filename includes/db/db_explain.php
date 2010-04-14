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
class db_explain
{
	var $type;
	var $connect_id;
	var $db;
	var $time = array();
	var $report = '';
	var $shutdown = false;
	var $count = 0;

	function db_explain($db)
	{
		$this->db = $db;
		$mtime = explode(' ', STARTTIME);
		$this->time['start'] = $mtime[0] + $mtime[1];
		define('USE_SHUTDOWN', false);
	}

	function start($sql)
	{
		$this->count++;
		$this->report .= '
			<table cellspacing="1" width="100%">
				<thead><tr><th> ' . ($this->shutdown ? 'Shutdown ' : '') . 'Query #' . $this->count . '</th></tr></thead>
				<tbody><tr><td class="row3">' . preg_replace(array('/\t(AND|OR)(\W)/', '/\n/'), array("\$1\$2", '<br />'), htmlspecialchars(preg_replace('/[\s]*[\n\r\t]+[\n\r\s\t]*/', "\n", $sql))) . '</td></tr></tbody>
			</table>';
		$this->explain($sql);
		$mtime = explode(' ', microtime());
		$this->time['last'] = $mtime[0] + $mtime[1];
		$this->shutdown = false;
	}

	function html($html, $row)
	{
		if (!$html && count($row))
		{
			$html = true;
			$this->report .= '<table cellspacing="1" width="100%"><tr>';

			foreach (array_keys($row) as $val)
			{
				$this->report .= '<th>' . (($val) ? ucwords(str_replace('_', ' ', $val)) : '&nbsp;') . '</th>';
			}
			$this->report .= '</tr>';
		}
		$this->report .= '<tr>';

		$class = 'row1';
		foreach (array_values($row) as $val)
		{
			$class = ($class == 'row1') ? 'row2' : 'row1';
			$this->report .= '<td class="' . $class . '">' . (($val) ? $val : '&nbsp;') . '</td>';
		}
		$this->report .= '</tr>';

		return $html;
	}

	function stop($sql)
	{
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1];

		$this->report .= '
			<p style="text-align: center;">
			Before: ' . sprintf('%.6f', $this->time['last'] - $this->time['start']) . 's | After: ' . sprintf('%.6f', $endtime - $this->time['start']) . 's | Elapsed: <b>' . sprintf('%.6f', $endtime - $this->time['last']) . 's</b></p><br /><br />';

		$this->time['sql'] += $endtime - $this->time['last'];
	}

	function show()
	{
		if (empty($this->time))
		{
			return '';
		}

		$mtime = explode(' ', microtime());
		$totaltime = $mtime[0] + $mtime[1] - $this->time['start'];

		return '
			<div style="padding: 10px 50px 15px 50px;">
				<p>MySQL: <b>' . sprintf('%.6f', $this->time['sql']) . 's</b> | PHP: <b>' . sprintf('%.6f', $totaltime - $this->time['sql']) . 's</b> | Memory: <b>' . ceil(memory_get_usage()/1024) . ' KB</b></p>
				<br /><br />
				' . $this->report . '
			</div>';
	}

	function explain($sql)
	{
		static $test_prof = null;

		if ($test_prof === null)
		{
			$test_prof = false;
			$version = $this->db->version;
			if (strpos($version, 'community') !== false)
			{
				$ver = substr($version, 0, strpos($version, '-'));
				if (version_compare($ver, '5.0.37', '>=') && version_compare($ver, '5.1', '<'))
				{
					$test_prof = true;
				}
			}
		}

		if (preg_match('/UPDATE ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))
		{
			$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
		}
		else if (preg_match('/DELETE FROM ([a-z0-9_]+).*?WHERE(.*)/s', $query, $m))
		{
			$explain_query = 'SELECT * FROM ' . $m[1] . ' WHERE ' . $m[2];
		}
		else
		{
			$explain_query = $sql;
		}

		if (preg_match('/^SELECT/', $explain_query))
		{
			$html = false;

			switch ($this->db->type)
			{
				case 'pdo':
					if ($test_prof)
					{
						$this->db->pdo->exec('SET profiling = 1;');
					}
					if ($stmt = $this->db->pdo->query("EXPLAIN $explain_query"))
					{
						while ($row = $stmt->fetch())
						{
							$html = $this->html($html, $row);
						}
					}
					$stmt = null;
				break;

				case 'mysqli':
					if ($test_prof)
					 {
					 	@mysqli_query($this->db->connect_id, 'SET profiling = 1;');
					 }
					if ($result = @mysqli_query($this->db->connect_id, "EXPLAIN $explain_query"))
					{
						while ($row = @mysqli_fetch_assoc($result))
						{
							$html = $this->html($html, $row);
						}
					}
					@mysqli_free_result($result);
				break;

				case 'mysql':
				default:
					if ($test_prof)
					{
						@mysql_query('SET profiling = 1;', $this->db->connect_id);
					}
					if ($result = @mysql_query("EXPLAIN $explain_query", $this->db->connect_id))
					{
						while ($row = @mysql_fetch_assoc($result))
						{
							$html = $this->html($html, $row);
						}
					}
					@mysql_free_result($result);
					break;
			}

			if ($html)
			{
				$this->report .= '</table>';
			}

			if ($test_prof)
			{
				$html = false;

				$this->report .= '<br />';
				switch ($this->db->type)
				{
					case 'pdo':
						if ($stmt = $this->db->pdo->query('SHOW PROFILE ALL;'))
						{
							while ($row = $stmt->fetch())
							{
								$this->clean_profiling($row);
								$html = $this->html($html, $row);
							}
						}
						$stmt = null;
						$this->db->pdo->exec('SET profiling = 0;');
					break;

					case 'mysqli':
						if ($test_prof)
						{
							$html = false;

							if ($result = @mysqli_query($this->db->connect_id, 'SHOW PROFILE ALL;'))
							{
								while ($row = @mysqli_fetch_assoc($result))
								{
									$this->clean_profiling($row);
									$html = $this->html($html, $row);
								}
							}
							@mysqli_free_result($result);
							@mysqli_query($this->db->connect_id, 'SET profiling = 0;');
						}
					break;

					case 'mysql':
					default:
						if ($result = @mysql_query('SHOW PROFILE ALL;', $this->db->connect_id))
						{
							while ($row = @mysql_fetch_assoc($result))
							{
								$this->clean_profiling($row);
								$html = $this->html($html, $row);
							}
						}
						@mysql_free_result($result);
						@mysql_query('SET profiling = 0;', $this->db->connect_id);
					break;
				}

				if ($html)
				{
					$this->report .= '</table>';
				}
			}
		}
	}

	function clean_profiling(&$row)
	{
		if (!empty($row['Source_function']))
		{
			$row['Source_function'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $row['Source_function']);
		}

		foreach ($row as $key => $val)
		{
			if ($val === null)
			{
				unset($row[$key]);
			}
		}
	}
}

if (!function_exists('memory_get_usage'))
{
	function memory_get_usage()
	{
		if (function_exists('exec') && function_exists('getmypid'))
		{
			if (IS_WIN)
			{
				$output = array();
				exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output);
				return preg_replace('/[\D]/', '', $output[5]) * 1024;
			}
			else
			{
				$pid = getmypid();
				exec("ps -eo%mem,rss,pid | grep $pid", $output);
				$output = explode('  ', $output[0]);
				return $output[1] * 1024;
			}
		}
		else
		{
			return false;
		}
	}
}
?>