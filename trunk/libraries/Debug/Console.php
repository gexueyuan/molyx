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

/**
 * 打开一个额外的 Javascript 窗口显示 debug 信息
 *
 * 修改自 debugConsole 1.3 <http://www.debugconsole.de>
 * 原作者 Andreas Demmer <info@debugconsole.de>
 */

$_debugConsoleConfig = array();

/**
 * focus debugConsole at end of debug-run
 */
$_debugConsoleConfig['focus'] = true;

/**
 * logfile configuration
 */
$_debugConsoleConfig['logfile'] = array(
	'enable' => false,
	'path' => ROOT_DIR,
	'filename' => 'log.txt',
	'disablePopup' => false
);

/**
 * popup dimensions in px
 */
$_debugConsoleConfig['dimensions'] = array(
	'width' => 300,
	'height' => 525
);

class Debug_Console
{
	/**
	 * events which are shown in debug console
	 */
	protected $filters;

	/**
	 * all watched variables with their current content
	 */
	protected $watches;

	/**
	 * debugConsole configuration values
	 */
	protected $config;

	/**
	 * URL where template can be found
	 */
	protected $template;

	/**
	 * javascripts to control popup
	 */
	protected $javascripts;

	/**
	 * html for popup
	 */
	protected $html;

	/**
	 * time of debugrun start in milliseconds
	 */
	protected $starttime;

	/**
	 * time of timer start in milliseconds
	 */
	protected $timers;
	protected $timer_count = 0;

	/**
	 * constructor, opens popup window
	 */
	public function __construct()
	{
		/* initialize class vars */
		$this->starttime = STARTTIME;
		$this->watches = array();
		$this->config = $GLOBALS['_debugConsoleConfig'];
		$this->html = array(
			'header' => '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Debug Console</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
	* {
		margin: 0px;
		padding: 0px;
	}
	body {
		font-family: arial, sans-serif;
		font-size: 0.7em;
		color: black;
		background-color: white;
		padding: 5px;
	}
	h1 {
		background-color: #888;
		color: white;
		font-size: 100%;
		padding: 3px;
		margin: 0px 0px 5px 0px;
		border: 0px;
		border-bottom: 4px solid #ccc;
	}
	p {
		border: 1px solid #888;
		border-left: 5px solid #888;
		background-color: white;
		padding: 3px;
		margin: 5px 3px;
	}
	div {
		background-color: #eee;
		border: 1px solid #888;
		margin: 0px 0px 25px 0px;
	}
	.dump {
	}
	.dump .source {
		font-family: courier, sans-serif;
	}
	.backtrace {
		color: #aaa;
	}
	.watch {
		border-color: #BF1CBF;
	}
	.checkpoint {
		border-color: #00E600;
	}
	.timer {
		border-color: blue;
	}
	.notice, .suggestion {
		border-color: orange;
	}
	.warning {
		border-color: red;
	}
	.notice strong, .warning strong, .suggestion strong {
		font-weight: bold;
		display: block;
	}
	.notice strong, .suggestion strong {
		color: orange;
	}
	.warning strong {
		color: red;
	}
	.runtime {
		margin: 0px;
		padding: 0px;
		border: 0px;
		width: 100%;
		text-align: center;
		background-color: transparent;
		color: #666;
	}
	</style>
</head>
<body>',
			'footer' => '</body></html>'
		);

		$this->html['header'] = str_replace(array("\r", "\n"), '', $this->html['header']);

		$this->javascripts = array(
			'openTag' => '<script language="JavaScript" type="text/javascript">',
			'closeTag' => '</script>',
			'openPopup' => 'debugConsole = window.open',
			'closePopup' => 'debugConsole.close()',
			'write' => 'debugConsole.document.write',
			'scroll' => 'debugConsole.scrollBy',
			'focus' => 'debugConsole.focus()'
		);

		/* replace PHP's errorhandler */
		set_error_handler(array(
			$this, 'errorHandlerCallback'
		));

		/* open popup */
		$popupOptions = "', 'debugConsole', 'width=" . $this->config['dimensions']['width'] . ",height=" . $this->config['dimensions']['height'] . ',scrollbars=yes';

		$this->sendCommand('openPopup', $popupOptions);
		$this->sendCommand('write', $this->html['header']);

		$this->startDebugRun();
	}

	/**
	 * destructor, shows runtime and finishes html document in popup window
	 */
	public function __destruct()
	{
		$runtime = $this->getMicrotime() - $this->starttime;
		$runtime = number_format((float) $runtime, 4, '.', NULL);

		$info = '<p class="runtime">This debug-run took ' . $runtime . ' seconds to complete.</p>';

		$this->sendCommand('write', $info);
		$this->sendCommand('write', '</div>');
		$this->sendCommand('scroll', "0','100000");
		$this->sendCommand('write', $this->html['footer']);

		if ($this->config['focus'])
		{
			$this->sendCommand('focus');
		}
	}

	/**
	 * show new debug run header in console
	 */

	protected function startDebugRun()
	{
		$info = '<h1>new debug-run (' . date('H:i') . ' hours)</h1>';
		$this->sendCommand('write', '<div>');
		$this->sendCommand('write', $info);
	}

	/**
	 * adds a variable to the watchlist
	 *
	 * Watched variables must be in a declare(ticks=n)
	 * block so that every n ticks the watched variables
	 * are checked for changes. If any changes were made,
	 * the new value of the variable is shown in the
	 * debugConsole with additional information where the
	 * changes happened.
	 */
	public function watchVariable($variableName)
	{
		if (count($this->watches) === 0)
		{
			register_tick_function(array(
				$this, 'watchesCallback'
			));
		}

		if (isset($GLOBALS[$variableName]))
		{
			$this->watches[$variableName] = $GLOBALS[$variableName];
		}
		else
		{
			$this->watches[$variableName] = NULL;
		}
	}

	/**
	 * tick callback: process watches and show changes
	 */
	public function watchesCallback()
	{
		foreach ($this->watches as $variableName => $variableValue)
		{
			if (isset($GLOBALS[$variableName]) && ($GLOBALS[$variableName] !== $this->watches[$variableName]))
			{
				$info = '<p class="watch"><strong>$' . $variableName;
				$info .= '</strong> changed from "';
				$info .= $this->watches[$variableName];
				$info .= '" (' . gettype($this->watches[$variableName]) . ')';
				$info .= ' to "' . $GLOBALS[$variableName] . '" (';
				$info .= gettype($GLOBALS[$variableName]) . ')';
				$info .= $this->getTraceback() . '</p>';

				$this->watches[$variableName] = $GLOBALS[$variableName];
				$this->sendCommand('write', $info);
			}
		}
	}

	/**
	 * sends a javascript command to browser
	 *
	 * @param string $command
	 * @param string $value
	 */
	protected function sendCommand($command, $value = false)
	{
		$value = str_replace('\\', '\\\\', $value);
		$value = nl2br($value);

		if ((bool) $value)
		{
			/* write optionally logfile */
			$this->writeLogfileEntry($command, $value);

			$command = $this->javascripts[$command] . "('" . $value . "');";
		}
		else
		{
			$command = $this->javascripts[$command] . ';';
		}

		$command = str_replace("\n\r", NULL, $command);
		$command = str_replace("\n", NULL, $command);

		if (!$this->config['logfile']['disablePopup'])
		{
			echo $this->javascripts['openTag'], "\n";
			echo $command, "\n";
			echo $this->javascripts['closeTag'], "\n";
		}

		flush();
	}

	/**
	 * writes html output as text entry into logfile
	 *
	 * @param string $command
	 * @param string $value
	 */
	protected function writeLogfileEntry($command, $value)
	{
		if ($this->config['logfile']['enable'])
		{
			$logfile = $this->config['logfile']['path'] . $this->config['logfile']['filename'];
			/* log only useful entries, no html header and footer */
			if ($command === 'write' && !strpos($value, '<html>') && !strpos($value, '</html>'))
			{
				/* convert html to text */
				$value = html_entity_decode($value);
				$value = str_replace('>', '> ', $value);
				$value = strip_tags($value);

				$fp = fopen($logfile, 'a+');
				fputs($fp, $value . "\n\n");
				fclose($fp);
			}
			elseif (strpos($value, '</html>'))
			{
				$fp = fopen($logfile, 'a+');
				fputs($fp, "-----------\n");
				fclose($fp);
			}
		}
	}

	/**
	 * shows in console that a checkpoint has been passed,
	 * additional info is the file and line which triggered
	 * the output
	 *
	 * @param string $message
	 */
	public function passedCheckpoint($message = NULL)
	{
		$message = (bool) $message ? $message : 'Checkpoint passed!';

		$info = '<p class="checkpoint"><strong>' . $message . '</strong>';
		$info .= $this->getTraceback() . '</p>';

		$this->sendCommand('write', $info);
	}

	/**
	 * returns microtime as float value
	 *
	 * @return float
	 */
	protected function getMicrotime()
	{
		return microtime(true);
	}


	/**
	 * show debug info for variable in debugConsole,
	 * added by custom text for documentation and hints
	 *
	 * @param mixed $variable
	 * @param string $text
	 */
	public function dump($variable, $text)
	{
		@ob_start();

		/* grab current ob content */
		$obContents = ob_get_clean();
		ob_clean();

		/* grap var dump from ob */
		var_dump($variable);
		$variableDebug = ob_get_contents();
		ob_end_clean();

		/* restore previous ob content */
		if (!empty($obContents))
		{
			echo $obContents;
		}

		/* render debug */
		$variableDebug = htmlspecialchars($variableDebug);
		$infos = '<p class="dump">' . $text . '<br />';

		if (is_array($variable))
		{
			$variableDebug = str_replace(' ', '&nbsp;', $variableDebug);
			$infos .= '<span class="source">' . $variableDebug . '</span>';
		}
		else
		{
			$infos .= '<strong>' . $variableDebug . '</strong>';
		}

		$infos .= $this->getTraceback() . '</p>';
		$this->sendCommand('write', $infos);
	}

	/**
	 * callback method for PHP errorhandling
	 *
	 * @TODO implement more errorlevels
	 */
	public function errorHandlerCallback()
	{
		$details = func_get_args();
		$details[1] = str_replace("'", '"', $details[1]);
		$details[1] = str_replace('href="function.', 'target="_blank" href="http://www.php.net/', $details[1]);

		/* determine error level */
		switch ($details[0])
		{
			case 2 :
				$errorlevel = 'warning';
			break;

			case 8 :
				$errorlevel = 'notice';
			break;

			case 2048 :
				$errorlevel = 'suggestion';
			break;
		}

		$fullTraceback = $details[2] . ' on line ' . $details[3];
		$file = $this->cropScriptPath($details[2]);

		$infos = '<p class="' . $errorlevel . '"><strong>';
		$infos .= 'PHP ' . strtoupper($errorlevel) . '</strong>';
		$infos .= $details[1] . '<br /><acronym class="backtrace" title="' . $fullTraceback . '">';
		$infos .= $file . ' on line ';
		$infos .= $details[3] . '</span></p>';

		$this->sendCommand('write', $infos);
	}

	/**
	 * start timer clock, returns timer handle
	 */
	public function startTimer($comment)
	{
		$handler = $this->timer_count++;

		$this->timers[$handler] = array(
			'starttime' => $this->getMicrotime(), 'comment' => $comment
		);

		return $handler;
	}

	/**
	 * stop timer clock
	 */
	public function stopTimer($handler = NULL)
	{
		if ($handler === NULL)
		{
			$handler = $this->last_timer;
		}

		if (isset($this->timers[$handler]))
		{
			$exists = true;
			$timespan = $this->getMicrotime() - $this->timers[$handler]['starttime'];

			$info = '<p class="timer"><strong>' . $this->timers[$handler]['comment'];
			$info .= '</strong><br />The timer ran ';
			$info .= '<strong>' . number_format($timespan, 4, '.', NULL) . '</strong>';
			$info .= ' seconds.' . $this->getTraceback() . '</p>';

			$this->sendCommand('write', $info);
		}
		else
		{
			$exists = false;
		}

		return $exists;
	}

	/**
	 * returns a formatted traceback string
	 *
	 * @return string
	 */
	public function getTraceback()
	{
		$callStack = debug_backtrace();

		$debugConsoleFiles = array(
			'Console.php', 'debug.php'
		);

		$call = array(
			'file' => 'Console.php'
		);

		while (in_array(basename($call['file']), $debugConsoleFiles))
		{
			$call = array_shift($callStack);
		}

		$fullTraceback = $call['file'] . ' on line ' . $call['line'];
		$call['file'] = $this->cropScriptPath($call['file']);

		$traceback = '<acronym class="backtrace" title="' . $fullTraceback . '">';
		$traceback .= $call['file'] . ' on line ';
		$traceback .= $call['line'] . '</acronym>';

		return '<br />' . $traceback;
	}

	/**
	 * crops long script path, shows only the last $maxLength chars
	 *
	 * @param string $path
	 * @param int $maxLength
	 * @return string
	 */
	protected function cropScriptPath($path, $maxLength = 30)
	{
		if (strlen($path) > $maxLength)
		{
			$startPos = strlen($path) - $maxLength - 2;

			if ($startPos > 0)
			{
				$path = '...' . substr($path, $startPos);
			}
		}

		return $path;
	}
}