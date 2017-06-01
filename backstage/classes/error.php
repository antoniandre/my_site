<?php
/**
 * Error model.
 * Named Cerror (for Custom error), as the Built-in PHP Error class has been introduced as of PHP7.
 * Design pattern: singleton.
 *
 * @dependencies: Settings.
 */
Class Cerror
{
	private static $instance = null;
	private $stack;


	/**
	 * Class constructor.
	 */
	private function __construct()
	{
    	$this->stack = [];
    	$this->errorHandler();
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
	public static function getCount()
	{
		return count(self::getInstance()->stack);
	}


	/**
	 * PHP error handler.
	 * 2  	E_WARNING  	Non-fatal run-time errors. Execution of the script is not halted
	 * 8 	E_NOTICE 	Run-time notices. The script found something that might be an error, but could also happen when running a script normally
	 * 256 	E_USER_ERROR 	Fatal user-generated error. This is like an E_ERROR set by the programmer using the PHP function trigger_error()
	 * 512 	E_USER_WARNING 	Non-fatal user-generated warning. This is like an E_WARNING set by the programmer using the PHP function trigger_error()
	 * 1024 	E_USER_NOTICE 	User-generated notice. This is like an E_NOTICE set by the programmer using the PHP function trigger_error()
	 * 4096 	E_RECOVERABLE_ERROR 	Catchable fatal error. This is like an E_ERROR but can be caught by a user defined handle (see also set_error_handler())
	 * 8191 	E_ALL 	All errors and warnings, except level E_STRICT (E_STRICT will be part of E_ALL as of PHP 6.0)
	 *
	 * @param integer $errno: error number.
	 * @param string $errstr: error string message.
	 * @param string $errfile: error file.
	 * @param integer $errline: error line.
	 * @return boolean: used only to disable/enable internal PHP error handler.
	 */
	private function errorHandler()
	{
    	error_reporting(E_ALL);// Display all errors.
    	set_error_handler(function($errno = 0, $errstr = '', $errfile = '', $errline = 0)
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

			return true;// True disables internal PHP error handler.
    	});
	}


	/**
	 * Set a custom user-triggered error.
	 *
	 * @param string $errorMessage: the message to display.
	 * @param string $errorType: the kind of error.
	 */
	public static function add($errorMessage, $errorType = 'USER CUSTOM', $backtrace = false)
	{
		$trace = debug_backtrace();
		$error = new StdClass();
		$error->number = null;
		$error->type = "$errorType ERROR";
		$error->file = isset($trace[0]['file']) ? self::getInstance()->pathFromSiteRoot($trace[0]['file']) : '';
		$error->line = isset($trace[0]['line']) ? $trace[0]['line'] : '';
		$error->backtrace = $backtrace ? $trace : null;
		$error->text = $errorMessage;
		self::getInstance()->stack[] = $error;

		return self::getInstance();
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
	public static function show($hidden = false)
	{
		$output = '';
		foreach (self::getInstance()->stack as $i => $error)
		{
		    if ($hidden) $output .= "- $error->type in file $error->file at line $error->line:\n  $error->text\n\n";
			else
			{
				$output.= "<div><p><strong>$error->type</strong> in file <em>/$error->file</em> at line $error->line:</p><code style=\"white-space:pre-wrap;\">$error->text";
				// Show the error backtrace if it was requested when error was added.
				if ($error->backtrace)
				{
					$output .= "\n<div class=\"backtrace\"><strong class=\"i-triangle-r\">BACKTRACE</strong>\n<ol reversed=\"reversed\">";
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
										$args .= ($k ? ', ' : '');
										switch (gettype($arg))
										{
											case 'integer':
												$args .= $arg;
												break;
											case 'string':
												$args .= "\"$arg\"";
												break;
											case 'array':
												// Json string is the nicest & shortest way to see subargs at unknown depth.
												$json = json_encode($arg);
												$args .= '['.substr($json, 1, strlen($json)-2).']';
												break;
											case 'object':
												// Json string is the nicest & shortest way to see subargs at unknown depth.
												$args .= ucfirst(gettype($arg)).json_encode($arg);
												break;
											case 'boolean':
											case 'double':
											default:
												$args .= (string)$arg;
												break;
										}
									}
								}
								$what = str_replace('<', '&lt;', "$class$function($args);");
							}

							$output .= "<li>Called from <strong>/".self::getInstance()->pathFromSiteRoot($caller)."</strong> at <strong>line $line</strong>: <em>$what</em></li>";
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
	public static function log()
	{
		$settings = \Settings::get();

		$output = date('Y-m-d H:i:s')."\n";
		foreach (self::getInstance()->stack as $i => $error)
		{
		    $output .= "- $error->type in file /$error->file at line $error->line:\n  $error->text\n";
		}
		$output .= "\n";

		error_log($output, 3, ROOT.$settings->errorLogFile);
	}


    /**
     * Log the last error that was added to the stack using Cerror::add().
     *
     * @return Error object: the only instance of this class.
     */
	public static function logTheLast()
	{
		$settings = \Settings::get();
        $self = self::getInstance();

		// Extract the last error from the stack.
		$error = $self->stack[count($self->stack)-1];

		$output = date('Y-m-d H:i:s')."\n"
				 ."- $error->type in file /$error->file at line $error->line:\n  $error->text\n\n";

		error_log($output, 3, ROOT.$settings->errorLogFile);

        return $self;
	}


    /**
     * Log the given message without appending to the stack (since we want the script to die) and die with the given message.
     *
     * @param string $logMessage: The message to log.
     * @param string $dieMessage: The only message to display before dying.
     * @return void.
     */
	public static function logAndDie($logMessage, $dieMessage)
	{
		$settings = \Settings::get();
		$self = self::getInstance();

		$trace = debug_backtrace();
		$error = new StdClass();
		$error->type = 'USER CUSTOM ERROR';
		$error->file = isset($trace[0]['file']) ? $self->pathFromSiteRoot($trace[0]['file']) : '';
		$error->line = isset($trace[0]['line']) ? $trace[0]['line'] : '';
		$error->text = $logMessage;

		$output = date('Y-m-d H:i:s')."\n"
				 ."- $error->type in file /$error->file at line $error->line:\n  $error->text\nBacktrace:\n"
				 // Array splice to limit backtrace to last 2 files.
				 .print_r(array_splice($trace, 0, 2), 1)."\n";

		error_log($output, 3, ROOT.$settings->errorLogFile);

		die($dieMessage);
	}


	/**
	 * Get function.
	 * Returns the error if any error was detected during execution of the script.
	 * The error is appended to the file with the current date and time.
	 *
	 * @return string: error message.
	 */
	public static function get()
	{
		$output = '';
		foreach (self::getInstance()->stack as $i => $error)
		{
		    $output .= "- $error->type in file /$error->file at line $error->line:\n  $error->text\n";
		}

		return $output;
	}


	/**
	 * Replace the long absolute path with a shorter path relative to the site root.
	 *
	 * @param string $path: the path to replace.
	 * @return string: the new path relative to the site root.
	 */
	private function pathFromSiteRoot($path)
	{
		return str_replace([ROOT, dirname(dirname(__DIR__)).'/'], '', $path);
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