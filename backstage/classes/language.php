<?php
/**
 * Language Model.
 * Design pattern: singleton.
 */
Class Language
{
	const siteDefault = 'fr';
	const allowedLanguages = ['en'=>'en_US', 'fr'=>'fr_FR'];
	private static $instance = null;
	public $browserDefault;
	private $current;
	// The target language is set if the current language has changed (post or
	// not allowed) and page must refresh.
	public $target = null;


	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->browserDefault = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
		$this->setLanguage();
	}


	/**
	 * Get the only instance of this class.
	 *
	 * @return the only instance of this class
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}


	/**
	 * Set language in this order of priority: $posts, $gets, $cookies, $browserDefault, $siteDefault.
	 * set to default site language if no querystring or anything else above (home for the first time).
	 *
	 * @param string $language: the language locale of the language you want to display the site in.
	 * @return (Object) this.
	 */
	public function setLanguage($language = '')
	{
		$gets = Userdata::get();
		$posts = Userdata::get('post');
		$cookies = Userdata::get('cookies');

		if (!$this->isAllowed($language) && isset($posts->lang)) $language = $posts->lang;
		if (!$this->isAllowed($language) && isset($gets->lang)) $language = $gets->lang;
		if (!$this->isAllowed($language) && isset($cookies->lang)) $language = $cookies->lang;
		if (!$this->isAllowed($language)) $language = $this->browserDefault;
		elseif (!$this->isAllowed($language)) $language = self::siteDefault;
		$this->current = $language;

		// Set a cookie if the post language is allowed.
		if (isset($posts->lang) && $this->current === $posts->lang) setcookie('lang', $posts->lang, time()+3600*24*365, '/');

		// The current language has changed (post or not allowed) and page must refresh.
		if (isset($gets->lang) && $this->current !== $gets->lang) $this->target = $this->current;

		return $this;
	}


	/**
	 * getCurrent function.
	 * Get the current language of the page. The current language is always
	 * a key among the self::allowedLanguages array.
	 *
	 * @return (string) the current language.
	 */
	public static function getCurrent()
	{
		return self::getInstance()->current;
	}


	/**
	 * getCurrentFull function.
	 * Get the current full language locale code.
	 *
	 * @return the full current language (E.g. en_US).
	 */
	public static function getCurrentFull()
	{
		return self::allowedLanguages[self::getInstance()->current];
	}


	/**
	 * getTarget function.
	 * Get the target language.
	 *
	 * @return the target language.
	 */
	public static function getTarget()
	{
		return self::getInstance()->target;
	}


	/**
	 * Simply check if a given language is known from the system or not.
	 *
	 * @param (string) $language: a 2 char length string to test if known language.
	 * @return boolean true if language is allowed false otherwise.
	 */
	public static function isAllowed($language)
	{
		return array_key_exists($language, self::allowedLanguages);
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