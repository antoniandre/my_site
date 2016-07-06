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
 * new Message('Super test!', 'valid', 'success', 'content', [1000, 3000], true);
 */
Class Message
{
	public static $classColors = ['success' => 'green', 'failure' => 'red', 'error' => 'red', 'info' => 'yellow', 'warning' => 'orange'];
	public static $instances = [];
	public $text;
	public $icon;// An icon choice among 'error', 'warning', 'info', 'valid'.
	public $class;// Custom class.
	public $position;// Can be 'header' or 'content'.
	public $postponed;// Can be true or false.
	public $animation;// Can be null (for fixed) or an array of delays in milliseconds: [timeToSlideDown, timeToSlideUp].

	/**
	 * Class constructor.
	 *
	 * @param string $text: the message to display.
	 * @param string $icon: a possible icon css class using icon fonts - like fontastic, fontawesome...
	 *                      choose among: 'error', 'warning', 'info', 'valid'.
	 * @param string $class: an optional custom specific class to add to the message.
	 * @param string $position: the position of the message container. can be 'header' or 'content'.
	 * @param string $animation: let you delay the display of message and/or set a display duration before it disappears.
	 *        					 Can be null (for fixed) or an array of delays in milliseconds: [timeToSlideDown, timeToSlideUp].
	 * @param null|array $animation: null or array of delays in milliseconds: [timeToSlideDown, timeToSlideUp].
	 * @param string $postpone: whether you choose to postpone the message to next load or not.
	 * @return void.
	 */
	public function __construct($text, $icon = '', $class = '', $position = '', $animation = [1000, 3000], $postpone = false)
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
		$this->postponed = $postpone;
		$this->animation = $animation;

		// Fetch postponed messages from session.
		$this->retrievePending();

		// Postpone a message if $postpone is set to true.
		if ($postpone) $this->postpone();

		// Only keep instance if not postponed (for print out).
		else self::$instances[] = $this;
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
		foreach (self::$instances as $k => $message) if ($message->position == $position && !$message->postponed)
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
	 * Postpone the message display to the next page load by saving it in session.
	 *
	 * @return void.
	 */
	function postpone()
	{
		$session = Userdata::get('session');

		// Fetch existing postponed message from session to merge array and reserialize.
		// Serialize is needed to prevent '__PHP_Incomplete_Class Object' occuring when
		// the object class is not yet known when session_start().
		$postponedMessages = isset($session->postponedMessages) ? (array)unserialize(stripslashes($session->postponedMessages)) : [];

		// Before saving message in session unpostpone it to prevent postpone forever!
		$message = $this;
		$message->postponed = false;
		$postponedMessages[] = $message;

		// Insert messages in session for current user.
		Userdata::setSession('postponedMessages', serialize($postponedMessages), true);
	}

	/**
	 * Retrieve pending messages from session.
	 *
	 * @return void.
	 */
	function retrievePending()
	{
		$session = Userdata::get('session');

		// If postponed messages are found in session, unserialize them and append to the current message list for later display!
		if (isset($session->postponedMessages))
		{
			self::$instances = array_merge(self::$instances, (array)unserialize(stripslashes($session->postponedMessages)));

			// Remove fetched message from session to prevent showing them on each page load forever.
			Userdata::_unset('session', 'postponedMessages');
		}
	}
}
?>