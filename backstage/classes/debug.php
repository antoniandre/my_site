<?php
/**
 * Design pattern: singleton
 */
Class Debug
{
	private static $instance = null;
	private $stack;
	public $showLevel2caller;
	public $showLevel3caller;

	/**
	 * Class constructor
	 */
	private function __construct()
	{
    	$this->stack = array();
    	$this->showLevel2caller = false;
    	$this->showLevel3caller = false;
	}

	/**
	 * Get the only instance of this class
	 *
	 * @return the only instance of this class
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Get the current number of Debug messages
	 *
	 * @return integer: number of debug messages
	 */
	public function getCount()
	{
		return count($this->stack);
	}

	/**
	 *
	 */
	public function add()
	{
		$trace = debug_backtrace();// print_r($trace);die;
		$message = new StdClass();
		$message->file = isset($trace[0]['file']) ? $trace[0]['file'] : '';
		$message->line = isset($trace[0]['line']) ? $trace[0]['line'] : '';
		$message->file2 = $trace[1]['file'];
		$message->line2 = $trace[1]['line'];
		$message->file3 = $trace[2]['file'];
		$message->line3 = $trace[2]['line'];
		$message->text = '';

		foreach(func_get_args() as $k => $mixed)
		{
			$message->text .= ($k ? "<hr>" : '').print_r($mixed, true);
		}

		$this->stack[] = $message;

		return $this;
	}

	/**
	 *
	 */
	public function show($hidden = false)
	{
		if ($this->showLevel3caller) return $this->showLevel3caller($hidden);

		$output = '';
		foreach ($this->stack as $i => $message)
		{
			$parentCaller = $this->showLevel2caller ? " (Called by <em>$message->file2</em> at line $message->line2)" : '';
			if ($hidden) $output .= "- Called by $message->file at line $message->line$parentCaller:\n  $message->text\n\n";
			else $output .= "<div><p>Called by <em>$message->file</em> at line $message->line$parentCaller:</p><code style=\"white-space:pre-wrap;\">$message->text</code></div>\n";
		}
		return $hidden ? "<!-- $output -->" : $output;
	}

	/**
	 * For debug function only
	 */
	public function showLevel3caller($hidden = false)
	{
		$output = '';
		foreach ($this->stack as $i => $message)
		{
			if ($hidden) $output .= "- Called by $message->file3 at line $message->line3:\n  $message->text\n\n";
			else $output .= "<div><p>Called by <em>$message->file3</em> at line $message->line3:</p><code style=\"white-space:pre-wrap;\">$message->text</code></div>\n";
		}
		return $hidden ? "<!-- $output -->" : $output;
	}

	/**
	 *
	 */
	public function setLevel2Caller($bool = true)
	{
    	$this->showLevel2caller = $bool;
    	return $this;
	}

	/**
	 *
	 */
	public function setLevel3caller($bool = true)
	{
    	$this->showLevel3caller = $bool;
    	return $this;
	}

	/**
	 *
	 */
	public function log()
	{
		global $settings;

		$output = date('Y-m-d H:i:s')."\n";
		foreach ($this->stack as $i => $message)
		{
			$parentCaller= $this->showLevel2caller ? " (Called by <em>$message->file2</em> at line $message->line2)" : '';
			$output .= "- Called by $message->file at line $message->line$parentCaller:\n  $message->text\n\n";
		}
		$output .= "\n";

		error_log($output, 3, __DIR__."/../../$settings->debugLogFile");
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}
}
?>