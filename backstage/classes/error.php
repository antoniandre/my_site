<?php
/**
 * Error model.
 * Design pattern: singleton.
 *
 * @dependencies: Settings.
 */
Class Error
{
	private static $instance = null;
	private $stack;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
    	$this->stack = array();
    	set_error_handler(array($this, 'errorHandler'));
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @return the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Get the current number of errors.
	 *
	 * @return integer: number of errors.
	 */
	public function getCount()
	{
		return count($this->stack);
	}

	/**
	 * 2  	E_WARNING  	Non-fatal run-time errors. Execution of the script is not halted
	 * 8 	E_NOTICE 	Run-time notices. The script found something that might be an error, but could also happen when running a script normally
	 * 256 	E_USER_ERROR 	Fatal user-generated error. This is like an E_ERROR set by the programmer using the PHP function trigger_error()
	 * 512 	E_USER_WARNING 	Non-fatal user-generated warning. This is like an E_WARNING set by the programmer using the PHP function trigger_error()
	 * 1024 	E_USER_NOTICE 	User-generated notice. This is like an E_NOTICE set by the programmer using the PHP function trigger_error()
	 * 4096 	E_RECOVERABLE_ERROR 	Catchable fatal error. This is like an E_ERROR but can be caught by a user defined handle (see also set_error_handler())
	 * 8191 	E_ALL 	All errors and warnings, except level E_STRICT (E_STRICT will be part of E_ALL as of PHP 6.0)
	 */
	public function errorHandler($errno = 0, $errstr = '', $errfile = '', $errline = 0)
	{
		if (!(error_reporting() & $errno)) return;// Don't display error if no error number

		$error = new StdClass();
		$error->number = $errno;
	    if ($error->number == E_USER_ERROR) $error->type = 'ERROR';
	    elseif ($error->number == E_WARNING) $error->type = 'WARNING';
	    elseif ($error->number == E_NOTICE) $error->type = 'NOTICE';
	    else $error->type = "UNKNOWN ERROR ($error->number)";
		$error->file = $errfile;
		$error->line = $errline;
		$error->text = $errstr;
		$this->stack[] = $error;

		return true;// True disables internal PHP error handler
	}

	/**
	 * Set a custom user-triggered error.
	 *
	 * @param string $errorMessage: the message to display.
	 * @param string $errorType: the kind of error.
	 */
	public function add($errorMessage, $errorType = 'USER CUSTOM', $backtrace = false)
	{
		$trace = debug_backtrace();
		$error = new StdClass();
		$error->number = null;
		$error->type = "$errorType ERROR";
		$error->file = isset($trace[0]['file']) ? $trace[0]['file'] : '';
		$error->line = isset($trace[0]['line']) ? $trace[0]['line'] : '';
		$error->backtrace = $backtrace ? $trace : null;
		$error->text = $errorMessage;
		$this->stack[] = $error;

		return $this;
	}

	/**
	 * Show function.
	 * Generates and return the HTML markup of the error that was detected during execution
	 * of the script.
	 * The error is appended to the file with the current date and time.
	 *
	 * @param  boolean $hidden: whether the error should be displayed in HTML or shown within
	 *                          an HTML comment.
	 * @return the generated HTML markup of the error.
	 */
	public function show($hidden = false)
	{
		$output = '';
		foreach ($this->stack as $i => $error)
		{
		    if ($hidden) $output .= "- $error->type in file $error->file at line $error->line:\n  $error->text\n\n";
			else
			{
				$output.= "<div><p><strong>$error->type</strong> in file <em>$error->file</em> at line $error->line:</p><code style=\"white-space:pre-wrap;\">$error->text";
				// Show the error backtrace if it was requested when error was added.
				if ($error->backtrace)
				{
					$output .= "\n\n<div class=\"backtrace\"><strong>BACKTRACE</strong>\n<ol reversed=\"reversed\">";
						foreach($error->backtrace as $k => $step) if ($k)
						{
							$what= '';
							$step['file'] = $step['file'] ? $step['file'] : '?';
							$previousCaller = $k+1< count($error->backtrace) ? $error->backtrace[$k+1]['file'] : null;
							$caller = $step['file'] == $previousCaller ? 'the same file' : "$step[file]";
							$line = $step['line'] ? $step['line'] : '#?';
							if (isset($step['function']))
							{
								$function = $step['function'];
								if (isset($step['class'])) $class = "$step[class]::";
								if (isset($step['args']))
								{
									$args = '';
									foreach ($step['args'] as $k => $arg)
									{
										if (is_string($arg)) $args .= ($k ? ', ' : '')."\"$arg\"";
										else $args .= ($k? ', ' : '').(string)$arg;
									}
								}
								$what = "$class$function($args);";
							}

							$output .= "<li>Called from <strong>$caller</strong> at <strong>line $line</strong>: <em>$what</em></li>";
						}
						$output .= "</ol></div>";
				}
				$output .= "</code></div>\n";
			}
		}
		return $hidden ? "<!-- $output -->" : $output;
	}

	/**
	 * Log function.
	 * Writes in the error log file any error that was detected during execution of the script.
	 * The error is appended to the file with the current date and time.
	 *
	 * @return void.
	 */
	public function log()
	{
		$settings = Settings::get();

		$output = date('Y-m-d H:i:s')."\n";
		foreach ($this->stack as $i => $error)
		{
		    $output .= "- $error->type in file $error->file at line $error->line:\n  $error->text\n";
		}
		$output .= "\n";

		error_log($output, 3, __DIR__."/../../$settings->errorLogFile");
	}

	/**
	 * Private clone method to prevent cloning the instance of the Singleton.
	 *
	 * @return void.
	 */
	private function __clone()
	{
	}
}
?>