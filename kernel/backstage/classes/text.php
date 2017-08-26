<?php

/**
 * Class Text.
 * Each text string is identified by a numeric ID and is contain in a context which represent a semantic group of texts.
 * For instance each page of the site represents a context holding multiple strings.
 */
class Text
{
	const NONE = 'NONE';

    // An array of text strings.
	private static $texts    = null;
    // An array of contexts each containing a text id list.
	private static $contexts = null;

    private $id;
    private $string;
    private $context;
    private $formats;
	private $requested;


	private function __construct($textRow, $language = null)
	{
		$language = Language::check($language);

		$this->string     = new stdClass();
		$this->formats    = [];
		$this->requested  = 'string';

		if (is_string($textRow))
		{
			$this->id                = self::NONE;
			$this->string->$language = $textRow;
			$this->context           = self::NONE;
		}
		elseif (is_object($textRow))
		{
			$this->id                = $textRow->id;
			$this->string->$language = $textRow->{"text_$language"};
			$this->context           = $textRow->context;

			// Add the new created text to the static array for reuse without DB query.
			self::$texts[$this->id]  = $this;
			self::$contexts[$this->context][] = $this->id;
		}

	}

	// Add the current page context + general context to the array.
    private static function init()
    {
        self::$texts = [];
		self::getContext(['general', Page::getCurrent()->page]);
	}

    /**
     * Get a whole context (group) of text strings. Then store them in $texts static array.
     */
    public static function getContext($contexts)
    {
        if (self::$texts === null) self::init();

		$db       = database::getInstance();
		$q        = $db->query();
		$language = Language::getInstance()->getCurrent();

		// Retrieve from DB.
		$q->select('texts', [$q->col('id'), $q->col('context'), $q->col('text_'.$language)]);

		// Set the Where clause to `context` IN ($contexts).
		$w = $q->where()->col('context')->in(...$contexts);

		$q->run();
		if ($q->info()->numRows)
		{
			$textsFromDB = $q->loadObjects();
			// Store in Text::$texts the texts for each context.
			foreach ($textsFromDB as $text)
			{
                new self($text, $language);
			}
		}
	}

    /**
     * Get a single text string. Then store it in $texts static array.
     */
    public static function get($idList, $language = null)
    {
        if (self::$texts === null) self::init();

		$idList       = (array)$idList;
		$knownTexts   = [];
		$unknownTexts = [];

		if (count($idList))
		{
			foreach ($idList as $id)
			{
				if (isset(self::$texts[$id])) $knownTexts[$id] = self::$texts[$id];
				else $unknownTexts[] = $id;
			}
		}

		if (count($unknownTexts))
		{
			$db       = database::getInstance();
			$q        = $db->query();
			$language = Language::check($language);

			// Retrieve from DB.
			$q->select('texts', [$q->col('id'), $q->col('context'), $q->col('text_'.$language)]);

			// Set the Where clause to `id` IN ([id_list]).
			$q->where()->col('id')->in(...$unknownTexts);

			$q->run();
			if ($q->info()->numRows)
			{
				$textsFromDB = $q->loadObjects();
				// Store in Text::$texts the texts for each context.
				foreach ($textsFromDB as $text)
				{
					$text = new self($text, $language);
					$knownTexts[$text->id] = $text;

					// Now remove found text from unfound array.
					unset($unknownTexts[array_flip($unknownTexts)[$text->id]]);
				}
			}

			if ($cnt = count($unknownTexts))
			{
				if ($cnt === 1) Cerror::add("The text id #$unknownTexts[0] is not found in database.", 'WRONG DATA', true);
				else
				{
					$texts = implode(', #', $unknownTexts);
					Cerror::add("The texts #$texts are not found in database.", 'WRONG DATA', true);
				}
			}
		}

        return count($knownTexts) === 1 ? array_values($knownTexts)[0] : $knownTexts;
    }

	// Use the given text to create a text object and benefit from the class methods.
    public static function _use($text)
	{
		return new self($text);
	}

    /**
     * Format a text string.
     * Possible formats:
	 *     htmlentities
	 *     sprintf
	 *     urlize
	 *     bb2html
	 *     html2bb
	 *     striptags
	 *     stripbbtags
     */
    public function format($format)
    {
		$languages = array_keys(get_object_vars($this->string));
		$this->requested = is_array($format) ? array_keys($format)[0] : $format;
		$args = is_array($format) ? $format[$this->requested] : null;

		foreach($languages as $language)
		{
			if (!isset($this->formats[$this->requested]->$language)) $this->formats[$this->requested] = new stdClass();

			switch($this->requested)
			{
				case 'htmlentities':
					$this->formats[$this->requested]->$language = htmlentities($this->string->$language, ENT_NOQUOTES, 'utf-8');
					break;
				case 'url':
					$this->formats[$this->requested]->$language = $this->urlize($this->string->$language);
					break;
				case 'sprintf':
					if ($args) $this->formats[$this->requested]->$language = sprintf($this->string->$language, ...$args);
					break;
			}

		};

        return $this;
    }

	private function urlize($str)
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

		// Replace spaces with dashes and lower the case.
		$str = strtolower(str_replace(' ', '-', $str));

		// Replace '&', remove special chars, reduce any space length to 2 max.
		$str = preg_replace(['/-?&-?/', '/[^a-z0-9-]/', '/-{3,}/'], ['-and-', '', '--'], $str);

		// Remove preceding and trailing dashes after the previous cleanup.
		return trim($str, '-');
	}

    /**
     * Return the string from the Text object.
     */
    public function toString($language = null)
    {
		// return foreachLang(function($lang){if (isset($this->string->$lang)) return $this->string->$lang;});
		$language = Language::check($language);

		return $this->requested === 'string' ? $this->string->$language : $this->formats[$this->requested]->$language;
    }
}
?>