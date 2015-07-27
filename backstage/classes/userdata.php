<?php
/**
 * Userdata class to handle user related data such as POST, GET, COOKIES, SESSIONS.
 * Design pattern: singleton.
 */
class Userdata
{
	const DATA_NOT_SET = -1;
	const DATA_EMPTY = 0;
	const DATA_FILLED = 1;
	const DATA_VALID = 2;
	const knownSources = ['post', 'get', 'cookie', 'session'];
	private static $instance = null;
	public static $post = [];
	public static $get = [];
	public static $cookie = [];
	public static $session = [];

	/**
	 * Class constructor
	 */
	private function __construct()
	{
		self::$post = $this->secureVars($_POST);
		self::$get = $this->secureVars($_GET);
		self::$cookie = $this->secureVars($_COOKIE);

		// First and only time to start session in the whole page.
		session_start();
		self::$session = $this->secureVars($_SESSION);
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
	 * Recursively secure any given var.
	 * This is required security, preventing mysql injections, XSS crack and others.
	 *
	 * @param mixed $inputVar: the var to secure, can be string, array or object
	 * @param boolean $asObject: return the var converted to array or object
	 * @param boolean $acceptHTML: if set to false, strip all the html tags but keep the inner text, if true accept tags
	 *
	 * all unsafe vars, coming from $_COOKIE, $_GET, $_POST are treated by the secureVars() function to replace the
	 * dangerous chars ' and " by equivalent htmlentities (&#039; and &quot;).
	 *
	 * With this system, everything is always safe even without mysql_real_escape_string.
	 * besides, there is no problem if a var is safed twice whereas mysql_real_escape_string does not know if backslashes
	 * are already added or not.
	 */
	public static function secureVars($inputVar, $asObject = true, $acceptHTML = false)
	{
	    $var = null;
	    if (is_object($inputVar)) $inputVar = (array)$inputVar;
	    if (is_array($inputVar))
	    {
	        $var = $asObject? new StdClass() : array();
	        foreach($inputVar as $key => $value)
	        {
	            if (is_string($value)) $tmp = self::secureString($value, $acceptHTML);
	            elseif (is_object($value) || is_array($value)) $tmp = self::secureVars($value, $asObject, $acceptHTML);
	            if ($asObject) $var->$key = $tmp;
	            else $var[$key] = $tmp;
	        }
	    }
	    else $var = self::secureString($inputVar, $acceptHTML);

	    return $var;
	}


	public static function secureString($string, $acceptHTML = false)
	{
	    $string = $acceptHTML? $string : strip_tags($string);
	    $string = str_replace(array("\r", '<script', '</script'), array('', '&lt;script', '&lt;/script'), $string);
	    return get_magic_quotes_gpc()? $string : addslashes($string);
	    /*return str_ireplace(array('PHNjcmlw', '`', '<script', 'base64', '/', '"', '\''),
	                        array('', '&#96;', '&lt;script', '', '&#x2F;', '&quot;', '&#039;'), $string);*/
	}


	/*
	  if we don't want htmlentities
	*/
	public static function unsecureString($string)
	{
	    return str_replace(array('&quot;', '&#039;', '&#x2F;'), array('"', '\'', '/'), $string);
	}

	/**/
	public static function checkFields($dataNameList, $dataSource = 'post')
	{
		$countData = count($dataNameList);
		$filledFields = 0;

		// First check the data source.
		if (!in_array($dataSource, self::knownSources))
		{
			Error::getInstance()->add(__CLASS__.'::'.__FUNCTION__."(): The requested data source is unknown: \"$dataSource\". Please choose among: ".implode(', ', self::knownSources).'.', null, true);
			return false;
		}

		$dataSource = self::$$dataSource;
		foreach ($dataNameList as $k => $dataName)
		{
			// Case of subdata like $_POST['text']['context'].
			if (strstr($dataName, '->'))
			{
				foreach (explode("->", $dataName) as $bit) $dataSource = $dataSource->$bit;
				if ((string)$dataSource !== '') $filledFields++;
			}
			// Case of language data like $_POST['text'] containing ['en'] ['fr'].
			elseif (isset($dataSource->$dataName) && is_object($dataSource->$dataName)) foreach (array_keys(Language::allowedLanguages) as $k => $lang)
			{
				// Foreach language, increment the data count, but not for the first which is already counted in $countData.
				if ($k) $countData++;

				if (isset($dataSource->$dataName->$lang) && (string)$dataSource->$dataName->$lang !== '') $filledFields++;
			}
			// Simple case like $_POST['name'].
			elseif ((string)$dataSource->$dataName) $filledFields++;
		}

		// Then compare $filledFields with $countData and if different then not all fields are provided.
		return $filledFields == $countData;
	}

	/**/
	public static function info($dataName, $dataSource = 'post', $isLanguageData = false)
	{
		if (!in_array($dataSource, self::knownSources))
		{
			Error::getInstance()->add(__CLASS__.'::'.__FUNCTION__."(): The requested data source is unknown: \"$dataSource\". Please choose among: ".implode(', ', self::knownSources).'.', null, true);
			return;
		}
		$return = null;
		$dataSource = self::$$dataSource;
		if (is_object($dataSource->$dataName))
		{
			$return = new StdClass();
			$return->count_unset = 0;
			$return->count_empty = 0;
			$return->count_filled = 0;
			if ($isLanguageData)
			{

				foreach (array_keys(Language::allowedLanguages) as $lang)
				{
					if (!isset($dataSource->$dataName->$lang)) $return->count_unset++;
					elseif (!$dataSource->$dataName->$lang) $return->count_empty++;
					else $return->count_filled++;
					/*$arr = ['language' => $lang,
						    'value' => !isset($dataSource->$dataName->$lang) ? self::DATA_NOT_SET
																	    : ($dataSource->$dataName->$lang ? self::DATA_FILLED
																								    : self::DATA_EMPTY)];
					$return[] = (object)$arr;*/
				}
			}
			else foreach ($dataSource->$dataName as $k => $data)
			{
			}
		}
		else
		{
			$return = self::DATA_FILLED;
			if (!isset($dataSource->$dataName)) $return = self::DATA_NOT_SET;
			elseif (!$dataSource->$dataName) $return = self::DATA_EMPTY;
		}

		return $return;
	}

	/**
	 * Check posted data for each expected language and return a summary.
	 * @param string $postName: the name of the posted data to analyze.
	 * @return StdClass Object.
	 */
	/*public static function getLanguageData($dataName)
	{
		$array = [];

		foreach (array_keys(Language::allowedLanguages) as $lang)
		{
			$arr = ['language' => $lang,
				    'value' => !isset($posts->$postName->$lang) ? self::DATA_NOT_SET
															    : ($posts->$postName->$lang ? self::DATA_FILLED
																						    : self::DATA_EMPTY)];
			$array[] = (object)$arr;
		}

		return $array;
	}*/
}
?>