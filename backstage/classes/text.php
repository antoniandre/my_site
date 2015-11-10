<?php

/**
 * Class Text.
 */
Class Text
{
	const NOT_FOUND = 'NOT_FOUND_IN_DB';
 
	// An array of contexts in which are stored "ID => String" pairs.
	// (Context = place of a text in the site E.g. 'general', 'sitemap')
	// That array is extended each time a new text is requested from an ID.
	public static $texts = null;

	// Array of ID => 'context' pairs, to look for a text in the static $texts array from its ID.
	private static $textContexts = null;

	// Store the formatted strings until they are outputed with ->get();
	private $tempStrings = [];

	/**
	 * Class constructor.
	 * Construct the object with a list of text IDs to retrieve or direct strings to work on.
	 * Retrieve the texts of the current page and the 'general' context from the database.
	 *
	 * @param  array $parameters: an array of options to perform extra tasks on string if any:
	 *         [
	 *         	   'id' => [],       Array: list of text IDs to retrieve from DB.
	 *         	   'context' => [],  Array: The multiple contexts to look into.
	 *             'language' => [], Array: the array of languages codes you want to retrieve the text in.
	 *                               Allowed languages are set in Language class.
	 *                               Defaults to the current language only if none is provided.
	 *         ]
	 */
	public function __construct($mixed)
	{
		// If only using new Text($string).
		if ($mixed && is_string($mixed)) $this->tempStrings[] = (object)[Language::getInstance()->getCurrent() => $mixed];
		
		// If only using new Text($text_id).
		elseif ($mixed && is_numeric($mixed)) $this->getTextFromId($mixed);
		
		// If using new Text((array)$parameters).
		elseif ($mixed && is_array($mixed) && (isset($mixed['id']) || isset($mixed['contexts'])))
		{
			$language = isset($mixed['languages']) ? $mixed['languages'] : [];
			if (isset($mixed['contexts'])) $this->getContext($mixed['contexts'], $language);
			if (isset($mixed['id'])) $this->getTextFromId($mixed['id'], $language);
		}

		// Add the current page context + general context to the array.
		if (Text::$texts === null) $this->getContext(['general', Page::getInstance()->page]);
	}

	/**
	 * If some contexts are given in parameters, also fetch the texts of those contexts from the database.
	 * Store the multidimensional array in static attribute Text::$texts.
	 *
	 * Usage: $t = new Text();$t->getContext('create-new-text');
	 *
	 * @param  array/string $contextList: the context list you want to look the text into.
	 *         Can provide a simple string if only one context.
	 * @param  string $languages: the optional languages list you want the text in.
	 * @return void
	 */
	public function getContext($contextList, $languages = [])
	{
		$db = database::getInstance();
		$q = $db->query();

		// Check the requested context list.
		// Convert possible one-string to an array of string.
		$contextList = (array)$contextList;
		if (!count($contextList)) return;

		// Convert possible one-string to an array of string.
		$languages = (array)$languages;

		// Set the requested language to currentLanguage if none.
		$currentLanguage = Language::getInstance()->getCurrent();
		if (!count($languages)) $languages = [$currentLanguage];

		// Check each requested language to see if it exists.
		foreach ($languages as $lang) if (array_key_exists($lang, Language::allowedLanguages)) $textFields[] = $q->col("text_$lang")->as($lang);

		// Retrieve from DB.
		$q->select('texts', array_merge([$q->col('id'), $q->col('context')], $textFields));

		// Set the Where clause to `context` IN ($contextList).
		$w = $q->where()->col('context');
		// PHP5.6+
		// $w->in(...$contextList);
		// PHP5.5-
		call_user_func_array([$w, 'in'], $contextList);

		$q->run();
		if ($q->info()->numRows)
		{
			$textsFromDB = $q->loadObjects();
			// Store in Text::$texts the texts for each context.
			foreach ($textsFromDB as $text)
			{
				$context = $text->context;
				$id = $text->id;

				// Remove unwanted information from the final array.
				unset($text->context, $text->id);

				Text::$texts[$context][$id] = $text;
				Text::$textContexts[$id] = $context;
			}
		}
	}

	/**
	 * Function getTextFromId()
	 * @param array/int $idList: the id list of the texts you want to retrieve. can be an array of ID or one Integer.
	 * @param array $languages: the array of languages codes you want to retrieve the text in.
	 *                          Allowed languages are set in Language class.
	 *                          Defaults to the current language only if none is provided.
	 * @return (Object) the current instance of this.
	 */
	private function getTextFromId($idList, $languages = [])
	{
		$db = database::getInstance();
		$q = $db->query();

		// Convert possible one-integer to an array of integer.
		$idList = (array)$idList;
		// Convert possible one-string to an array of string.
		$languages = (array)$languages;

		// Set the requested language to currentLanguage if none.
		$currentLanguage = Language::getInstance()->getCurrent();
		if (!count($languages)) $languages = [$currentLanguage];

		// Check each requested language to see if it exists.
		foreach ($languages as $lang) if (array_key_exists($lang, Language::allowedLanguages)) $textFields[] = $q->col("text_$lang")->as($lang);

		// Retrieve from DB.
		$q->select('texts', array_merge([$q->col('id'), $q->col('context')], $textFields));
		$w = $q->where()->col('id')->in(implode(', ', $idList));// Set the Where clause to `idList` = $idList.
		$q->run();

		if ($q->info()->numRows)
		{
			$textsFromDB = $q->loadObjects('id');
		}

			// Store in Text::$texts the texts for each context.
			/*foreach ($textsFromDB as $text)
			{
				$context = $text->context;
				$id = $text->id;

				// Remove unwanted information from the final array.
				unset($text->context, $text->id);

				Text::$texts[$context][$id] = $text;
				Text::$textContexts[$id] = $context;
				$this->tempStrings[$id] = $text;
			}*/
			foreach ($idList as $id)
			{
				dbg($id);
				$text = self::NOT_FOUND;
				if (isset($textsFromDB[$id]))
				{
					$text = $textsFromDB[$id];
					$context = $text->context;

					Text::$texts[$context][$id] = $text;
					Text::$textContexts[$id] = $context;

					// Remove unwanted information from the final array.
					unset($text->context, $text->id);
				}
				else Error::getInstance()->add("The text id #$id is not found in database.", 'WRONG DATA', true);

				$this->tempStrings[$id] = $text;
			}
	}

	/**
	 * Function get(): returns the treated final text.
	 *
	 * @see classes/language.php for allowed languages.
	 * @param int $id (optionnnal): the id of the text you want to retrieve.
	 * @param array $languages (optionnnal): the array of languages codes you want to retrieve the text in.
	 *                                       Allowed languages are set in Language class.
	 *                                       Defaults to the current language only if none is provided.
	 * @return StdClass Object/string: the object of strings or one string if only one language requested.
	 */
	public function get()// Expect at most 2 params: $id, $languages = [].
	{
		$object = null;

		if (func_num_args() == 2) list($id, $languages) = func_get_args();
		elseif (func_num_args() == 1 && is_integer(func_get_arg(0))) list($id, $languages) = [func_get_arg(0), []];
		elseif (func_num_args() == 1) list($id, $languages) = [null, func_get_arg(0)];
		if (!func_num_args()) list($id, $languages) = [null, []];
		// list($id, $languages) = func_num_args() == 2 ? func_get_args() : [null, func_get_arg(0)];


		// If no id is provided, look into $this->tempStrings and take the first found.
		// The purpose is to allow only $t = new Text(33);$t->get();
		$id = $id ? $id : array_keys($this->tempStrings)[0];

		// if (!count($this->tempStrings)) dbg($id, debug_backtrace());
		if ($id !== null)
		{
			if ($this->tempStrings[$id] === self::NOT_FOUND) {dbg($this->tempStrings);}
			else
			{
				if (!isset($this->tempStrings[$id])) $context = Text::$textContexts[$id];
				$textObject = isset($this->tempStrings[$id]) ? $this->tempStrings[$id] : Text::$texts[$context][$id];

				// Convert possible one-string to an array of strings.
				$languages = (array)$languages;
				// Set the requested language to currentLanguage if none.
				$currentLanguage = Language::getInstance()->getCurrent();
				if (!count($languages)) $languages = [$currentLanguage];

				// Check each requested language to see if it exists.
				if (count($languages) > 1)
				{
					$object = new StdClass();
					foreach ($languages as $lang) if (array_key_exists($lang, Language::allowedLanguages)) $object->$lang = $textObject->$lang;
				}
				else {$object = $textObject->{$languages[0]};}

				$this->tempStrings[$id] = null;// Empty the current text Id
			}
		}

		return $object;
	}

	/**
	 * Format method.
	 * Usage:
	 *     $t = new Text(33);
	 *     echo $t->format(['htmlentities' => false, 'sprintf' => ['hello', 'word!'], 'sef' => true])
	 *       	  ->get();
	 *
	 * @param  int $id the ID of the string to format
	 * @param  array $formats: An array of format-params pairs to apply to the string.
	 *                         possible pairs:
	 *                             htmlentities => true/false,
	 *                             sprintf => [params,...],
	 *                             sef => true/false,
	 *                             bb2html => true/false,
	 *                             html2bb => true/false,
	 *                             striptags => true/false,
	 *                             stripbbtags => true/false.
	 * @return Object: the current instance of this.
	 */
	public function format()// Expect at most 2 params: $id, $formats = []. at least one param: $formats.
	{
		list($id, $formats) = func_num_args() == 2 ? func_get_args() : [null, func_get_arg(0)];
		// If no id is provided, look into $this->tempStrings and take the first found.
		// The purpose is to allow only $t = new Text(33);$t->get();
		$id = $id ? $id : array_keys($this->tempStrings)[0];

		// Convert possible one-integer to an array of integer.
		$formats = (array)$formats;

		if ($id !== null && count($formats))
		{
			// If tempString is set then we are currently treating a direct string with no context.
			if (!isset($this->tempStrings[$id])) $context = Text::$textContexts[$id];

			// Check requested formats and set htmlentities to true if no format is specified.
			$formats = (array)$formats;
			if (!count($formats)) $formats = ['htmlentities' => true];

			foreach ($formats as $formatName => $formatArg)
			{
				// If in the format array strings are found (e.g 'sef') they are converted to key-value pairs (e.g 'sef' => true)
				if (is_integer($formatName))
				{
					$formatName = $formatArg;
					$formatArg = true;
				}

				// Get each string to convert (in each available language).
				$textObject = isset($this->tempStrings[$id]) ? $this->tempStrings[$id] : Text::$texts[$context][$id];

				// Check each requested language to see if it exists.
				foreach ($textObject as $lang => $str)
				{
					switch ($formatName)
					{
						case 'htmlentities':
							if ($formatArg) $str = htmlentities($str, ENT_NOQUOTES, 'utf-8');
							break;
						case 'sef':
							if ($formatArg)
							{
								/* NEW WAY: (does not work on OVH)
								@todo: must investigate why and use it.
								// exemple string:
								// $str = 'お早うございます A æ      Übérmensch på høyeste nivå! И я люблю PHP! есть. ﬁ ¦ yé måß∂ƒà sœur & sœur&sœurîüýçñ∑´éèàûôêï`~ !µ³Ø Žluťoučký kůň !'
								// 		.'¦∑´®†¥„´‰ˇØ∏”’˝»¸˛◊ı˜¯˘¿¡™£¢¤∞§¶•ªº`øπ“‘ß∂ƒ˙∆˚¬…«`Ω≈√∫˜µ≤≥÷⁄€‹›ﬁﬂ‡°·‚±~¼½¾×/|\\\'"()[]{}#$%@!?.,;:+=<>';
								$str = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower(); [\u0080-\u7fff] remove', $str);*/

								/* SAFER OLD WAY : */
								if (mb_strlen($str) !== strlen($str))
								{
									$pattern = '/&(\w{1,2})(?:grave|acute|circ|cedil|uml|ring|lig|tilde);/';
									$str = preg_replace($pattern, '$1', htmlentities($str, ENT_NOQUOTES, 'UTF-8'));
								}

								// Replace '&', remove special chars, reduce any space length to 2 max.
								$str = preg_replace(['/ ?& ?/', '/[^A-Za-z0-9 -]/', '/[- ]{3,}/'], [' and ', '', '  '], $str);

								// Remove preceding and trailing dashes after the previous cleanup.
								$str = rtrim(trim($str));
								
								// Finally replace spaces with dashes and lower the case.
								$str = strtolower(str_replace(' ', '-', $str));
							}
							break;
						case 'sprintf':
							// PHP5.6+
							// $str = sprintf($str, ...$formatArg);
							// PHP5.5-
							$str = call_user_func_array('sprintf', array_merge([$str], $formatArg));
							break;
						case 'bb2html':
							$str = $this->BBcode2html($str);
							break;
						/*case 'html2bb':
							$str = $this->html2BBcode($str);
							break;*/
						case 'striptags':
							$str = strip_tags($str);
							break;
						case 'stripbbtags':
							$str = $this->stripBBtags($str);
							break;
					}
					$this->tempStrings[$id]->$lang = $str;
				}
			}
		}

		return $this;
	}

	/*
		BBcode syntax:  [tag=value 1|param2=value 2]innerHTML[/tag]
						[tag=value 1|param2=value 2]
						[tag param1=value 1|param2=value 2] for auto-ending tags like img
	*/
	private function BBcode2html($str)
	{
		$str= preg_replace_callback("~\[(/?)([^=\s|\]]+)(?:=?([^|\]]+))?(\|?[^\]]*)\]~", [$this, 'BBcode2html_callback'], $str);
		// nl2br does not remove \n, it only adds <br />, the bbcode js code also checks <br /> and \n to make sure,
		// So if we don't do str_replace(["\r", "\n"], ''... we will have 2 line breaks!
		$str= stripslashes(str_replace("\n", '', nl2br(str_replace("\r", '', $str))));

		return $str;
	}

	private function BBcode2html_callback($matches)
	{
		$closingTag = @$matches[1];// Detects if it is the closing tag or not. E.g. '/' in '[/b]'
		$tag =		  @$matches[2];// Detects the tag. E.g. 'b' in '[b]'
		$param1 =	  @$matches[3];// Detects the first param if any. E.g 'src' in '[img=src|alt=image]'
		$rawParams =  @$matches[4];// Detects all the other parameters

		$params= $autoEndingTag= $forceClosingTag= '';
		if ($rawParams) foreach(explode('|', $rawParams) as $v) if ($v) $params.= ' '.str_replace('=', '="', $v).'"';

		switch($tag)
		{
			case 'b': $tag= 'strong';break;
			case 'i': $tag= 'em';break;
			case 'u': $tag= 'span';$params.=' style="text-decoration:underline"';break;
			case 'url': $tag= 'a';if (!$closingTag) $params.=' href="'.url($param1).'"';break;
			case 'img':
				$params.= ' src="'.url($param1).'"'.(strpos($params, ' alt="')===false ? ' alt="image"' : '');
				$autoEndingTag= 1;
				break;
			case 'color': $tag= 'span';$params.=" style=\"color:$param1\"";break;
			case 'emo': $tag= 'span';$params.=" class=\"emo $param1\"";$forceClosingTag= 1;break;
			case 'br': break;
			case 'div': break;
			case 'span': break;
			case 'br':
				$autoEndingTag= 1;
				break;
			case 'h2':
			case 'h3':
			case 'h4': break;
			default:$tag= '';// If the tag is not listed above it means it is not allowed so remove it.
		}
		if ($autoEndingTag) $htmlOutput= "<$tag$params />";
		else $htmlOutput= !$closingTag?"<$tag$params>":"</$tag>";
		if ($forceClosingTag) $htmlOutput= "<$tag$params></$tag>";
		if ($tag) return $htmlOutput;
	}


	private function stripBBtags($string)
	{
		return preg_replace('/\[(.*?)\]/i', '', $string);
	}


	/*private function str_split_unicode($str, $l= 0)
	{
	    if ($l > 0)
		{
	        $ret= array();
	        $len= mb_strlen($str, "UTF-8");
	        for ($i= 0; $i< $len; $i+= $l) $ret[]= mb_substr($str, $i, $l, "UTF-8");
	        return $ret;
	    }
	    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	}*/
}
?>