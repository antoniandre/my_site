<?php
/**
 * Message Model.
 * Design pattern: ≈ multiton
 *
 * Example of use:
 * new Message('What a cool content message!', 'error', 'contentError', 'content');
 * new Message('What a cool header message!', 'info', 'info', 'header');
 * new Message('What? Another cool header message!?', 'warning', 'warning', 'header');
 * new Message('What? Another third header message!?', 'error', 'error', 'header', null);
 * new Message('Ah OK! A fourth cool header message!', 'valid', 'success', 'header');
 */
Class Message
{
	public static $instances = [];
	public static $classColors = ['success' => 'green', 'failure' => 'red', 'error' => 'red', 'info' => 'yellow', 'warning' => 'orange'];
	public $text;
	public $icon;// An icon choice among 'error', 'warning', 'info', 'valid'.
	public $class;// Custom class.
	public $position;// Can be 'header' or 'content'.
	public $animation;// Can be null (for fixed) or an array of delays in milliseconds: [timeToSlideDown, timeToSlideUp].

	/**
	 * Class constructor.
	 *
	 * @param string $text:
	 * @param string $icon:
	 * @param string $class:
	 * @param string $position:
	 * @param $animation: null or array of delays in milliseconds: [timeToSlideDown, timeToSlideUp].
	 */
	public function __construct($text, $icon = '', $class = '', $position = '', $animation = [1000, 3000])
	{
		$this->text = $text;
		$this->icon = $icon;
		$this->class = '';
		foreach (explode(' ', $class) as $c)
		{
			$this->class .= " $c";
			if (array_key_exists($c, self::$classColors)) $this->class .= ' '.self::$classColors[$c];
		}
		$this->position = $position;
		$this->animation = $animation;
		self::$instances[] = $this;
	}

	/**
	 * Display a message parsed in page class.
	 * The message is a text if there's only one message to display or list if there are more messages.
	 * If statusIcon is set, the icon will be added in a span before the message.
	 * If postponed=1 the message will be print to the user on the next page load (msg stored in DB n userId in session).
	 *
	 * @param  String $position: the position where to display the message on the page ('header' or 'content').
	 * @return String: the html markup.
	 */
	public static function show($position)
	{
		$output = '';
		foreach (self::$instances as $k => $message) if ($message->position == $position)
		{
			$timeToSlideDown = isset($message->animation[0])? (' data-slidedown="'.(int)$message->animation[0].'"') : '';
			$timeToSlideUp = isset($message->animation[1])? (' data-slideup="'.(int)$message->animation[1].'"') : '';
			$output .= "<div class=\"message$message->class\"$timeToSlideDown$timeToSlideUp>"
						    .($message->icon? "<span class=\"ico i-$message->icon\"></span>" : '')."$message->text
					   </div>\n";
		}

		return $output;
	}

	/**
	 * TODO: finish
	 */
	function postpone()
	{
		// Insert message in DB for current user
	}

	/**
	 * TODO: finish
	 */
	function retrievePending()
	{
		// Retrieve message from DB for current user
		$messageToPrint = loadResult("SELECT `value` FROM `misc` WHERE `var`='printMessageTo$user->id'");
		if ($messageToPrint)
		{
			list($text, $icon, $class, $position) = unserialize($messageToPrint);
			new self($text, $icon, $class, $position);
			delete("misc||`var`='printMessageTo$user->id'");
		}
	}
}
?>