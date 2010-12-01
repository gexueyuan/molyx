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
class Db_Explain
{
	private $db;
	private $start_time;
	private $sql_time = 0;
	private $curtime = 0;
	private $sql_report = '';
	private $html_hold = '';
	private $shutdown = false;

	public function __construct($db)
	{
		$this->db = $db;

		$this->start_time = STARTTIME;
	}

	public function __destruct()
	{
		unset($this->db);
	}

	public function display($args)
	{
		$db = $args[0];
		$totaltime = microtime(true) - $this->start_time;

		$count = $this->db->getCount();
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta http-equiv="imagetoolbar" content="no" />
	<title>SQL Report</title>
	<link href="' . ROOT_PATH . 'style/admin.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body id="errorpage">
<div id="wrap">
	<div id="page-header">
		<a href="' . SCRIPTPATH . '">Return to previous page</a>
	</div>
	<div id="page-body">
		<div id="acp">
		<div class="panel">
			<span class="corners-top"><span></span></span>
			<div id="content">
				<h1>SQL Report</h1>
				<br />
				<p><b>Page generated in ' . round($totaltime, 4) . " seconds with {$count['query']} queries" . (($count['cached']) ? " + {$count['cached']} queries returning data from cache" : '') . '</b></p>

				<p>Time spent on ' . $this->db->getType() . ' queries: <b>' . round($this->sql_time, 5) . 's</b> | Time spent on PHP: <b>' . round($totaltime - $this->sql_time, 5) . 's</b></p>

				<br /><br />
				' . $this->sql_report . '
			</div>
			<span class="corners-bottom"><span></span></span>
		</div>
		</div>
	</div>
	<div id="page-footer">
		Powered by MolyX &copy; 2009 - 2012 <a href="http://www.molyx.com/">MolyX Group</a>
	</div>
</div>
</body>
</html>';
	}

	public function stop($query)
	{
		$end_time = microtime(true);

		$count = $this->db->getCount('total');

		$this->sql_report .= '

			<table cellspacing="1">
			<thead>
			<tr>
				<th>' . ($this->shutdown ? 'Shutdown ' : '') . 'Query #' . $count . '</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td class="row3"><textarea style="font-family:\'Courier New\',monospace;width:99%" rows="5" cols="10">' . preg_replace('/\t(AND|OR)(\W)/', "\$1\$2", htmlspecialchars(preg_replace('/[\s]*[\n\r\t]+[\n\r\s\t]*/', "\n", $query))) . '</textarea></td>
			</tr>
			</tbody>
			</table>

			' . $this->html_hold . '

			<p style="text-align: center;">
		';

		if ($db->query_id)
		{
			if (preg_match('/^(UPDATE|DELETE|REPLACE)/', $query))
			{
				$this->sql_report .= 'Affected rows: <b>' . $this->db->affectedRows() . '</b> | ';
			}
			$this->sql_report .= 'Before: ' . sprintf('%.5f', $this->curtime - $this->starttime) . 's | After: ' . sprintf('%.5f', $end_time - $this->start_time) . 's | Elapsed: <b>' . sprintf('%.5f', $end_time - $this->curtime) . 's</b>';
		}
		else
		{
			$error = $this->db->getError();
			$this->sql_report .= '<b style="color: red">FAILED</b> - ' . $db->type . ' Error : ' . htmlspecialchars($error);
		}

		$this->sql_report .= '</p><br /><br />';

		$this->sql_time += $end_time - $this->curtime;
	}

	public function start($query, $shutdown)
	{
		$this->html_hold = '';
		$this->db->report('start', $query);
		$this->curtime = microtime(true);
		$this->shutdown = $shutdown;
	}

	public function addSelectRow($query, $html_table, $row)
	{
		if (!$html_table && count($row))
		{
			$html_table = true;
			$this->html_hold .= '<table cellspacing="1"><tr>';

			foreach (array_keys($row) as $v)
			{
				$this->html_hold .= '<th>' . (($v) ? ucwords(str_replace('_', ' ', $v)) : '&nbsp;') . '</th>';
			}
			$this->html_hold .= '</tr>';
		}
		$this->html_hold .= '<tr>';

		$class = 'row1';
		foreach (array_values($row) as $v)
		{
			$class = ($class == 'row1') ? 'row2' : 'row1';
			$this->html_hold .= '<td class="' . $class . '">' . (($v) ? $v : '&nbsp;') . '</td>';
		}
		$this->html_hold .= '</tr>';

		return $html_table;
	}

	public function fromCache($query)
	{
		$this->db->report('fromcache', $query);
	}

	public function recordFromCache($query, $end_time, $split_time)
	{
		$time_cache = $end_time - $this->curtime;
		$time_db = $split_time - $end_time;
		$color = ($time_db > $time_cache) ? 'green' : 'red';

		$this->sql_report .= '<table cellspacing="1"><thead><tr><th>Query results obtained from the cache</th></tr></thead><tbody><tr>';
		$this->sql_report .= '<td class="row3"><textarea style="font-family:\'Courier New\',monospace;width:99%" rows="5" cols="10">' . preg_replace('/\t(AND|OR)(\W)/', "\$1\$2", htmlspecialchars(preg_replace('/[\s]*[\n\r\t]+[\n\r\s\t]*/', "\n", $query))) . '</textarea></td></tr></tbody></table>';
		$this->sql_report .= '<p style="text-align: center;">';
		$this->sql_report .= 'Before: ' . sprintf('%.5f', $this->curtime - $this->start_time) . 's | After: ' . sprintf('%.5f', $end_time - $this->start_time) . 's | Elapsed [cache]: <b style="color: ' . $color . '">' . sprintf('%.5f', ($time_cache)) . 's</b> | Elapsed [db]: <b>' . sprintf('%.5f', $time_db) . 's</b></p><br /><br />';

		$this->start_time_time += $time_db;
	}

	public function addHtmlHold($text)
	{
		$this->html_hold .= $text;
	}
}