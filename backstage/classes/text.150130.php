<?php

/**
 * Class Text.
 */
Class Text
{
	// An array of contexts in which are stored "ID => String" pairs.
	// (Context = place of a text in the site E.g. 'general', 'sitemap')
	// That array is extended each time a new text is requested from an ID.
	public static $texts= null;

	// An array of text IDs or strings to work on or to retrieve.
	public $currentTextList= [];

	private $language= '';


	/**
	 * Class constructor.
	 * Construct the object with a list of text IDs to retrieve or strings to work on.
	 *
	 * @param  array $parameters: an array of options to perform extra tasks on string if any:
	 *         [
	 *         	   'id' => [],// Array: list of text IDs to retrieve from DB.
	 *         	   'context' => [],// Array: The multiple contexts to look into.
	 *         	   'sprintf' => [],// Array: The text will be sprintf-ed with the arguments in the array.
	 *         	   'htmlentities' => true,// Boolean: default to true set to false to desable this encoding.
	 *         ]
	 * @return object: the current instance of Text.
	 */
	public function __construct($parameters)
	{
		$context= isset($parameters['context']) && $parameters['context'] ? (array)$parameters['context'] : [];
		$this->currentTextList= (array)$parameters['id'];
		$this->language= Language::getInstance()->getCurrent();
		$languages= isset($parameters['language']) ? (array)$parameters['language'] : [$this->language];

		// Init the $texts attr with current page texts and general texts.
		if (!Text::$texts) $this->retrieveContextFromDB($context, $languages);
		if (is_numeric($this->currentTextList)) return $this->getTextInContext($this->currentTextList, $context);

		return $this;
	}

	/**
	 * Retrieve the texts of the current page and the 'general' context from the database.
	 * If some contexts are given in parameters, also fetch the texts of those contexts from the database.
	 * Store the multidimensional array in static attribute Text::$texts.
	 *
	 * @param  array $contextList: the extra contexts you want to look text into.
	 * @param  string $languages: the optional languages list you want the text in.
	 * @return void
	 */
	private function retrieveContextFromDB($contextList= [], $languages)
	{
		$contextList= array_merge([Page::getInstance()->page, 'general'], (array)$contextList);
		$db= database::getInstance();
		$q= $db->query();
		foreach ($languages as $lang) $textFields[]= $q->col("text_$lang");
		$q->select('texts', array_merge([$q->col('id'), $q->col('context')], $textFields));
		$w= $q->where()->col('context');
		// PHP5.6+
		// $w->in(...$contextList);
		// PHP5.5-
		call_user_func_array([$w, 'in'], $contextList);
		$q->run();
		if ($q->info()->numRows) $textsFromDB= $q->loadObjects();

		// Store in Text::$texts the texts for each context.
		foreach ($textsFromDB as $text)
		{
			$context= $text->context;
			$id= $text->id;
			unset($text->context, $text->id);
			Text::$texts[$context][$id]= $text;
		}

		return $this;
	}

	/**
	 * Get the text matching the given id from the database.
	 *
	 * @param  int $id: the id of the text to retrieve.
	 * @param  array $parameters: an array of options to perform extra tasks on string if any:
	 *         [
	 *         	   'context' => [],// Array: The multiple contexts to look into.
	 *         	   'sprintf' => [],// Array: The text will be sprintf-ed with the arguments in the array.
	 *         	   'htmlentities' => true,// Boolean: default to true set to false to desable this encoding.
	 *         ]
	 * @return string: the text from database.
	 */
	public function get($id= null, $parameters= [])
	{
		// If the Text object is created with only one id, no need to repeat it in get() param.
		// If the Text object contains an ID list then $id param is required.
		if ($id === null) $text= $this->get($this->id[0], $parameters);
		else
		{
			$text= is_int($id)? $this->getTextInContext([$id], isset($parameters['context'])? $parameters['context'] : [])[$id] : $id;

			// SPRINTF.
			// The (array) cast let the possibility to provide one parameter or an array of params.
			// if (isset($parameters['sprintf']) && count((array)$parameters['sprintf'])) $text= sprintf($text, ...(array)$parameters['sprintf']);// PHP 5.6+
			if (isset($parameters['sprintf']) && count((array)$parameters['sprintf'])) $text= call_user_func_array('sprintf', array_merge((array)$text, (array)$parameters['sprintf']));// OLD PHP way...

			// HTMLENTITIES.
			if (!isset($parameters['htmlentities']) || (isset($parameters['htmlentities']) && $parameters['htmlentities'])) $text= htmlentities($text, HTML_ENTITIES, 'utf-8');
		}

		return $text;
	}

	public function getTextInContext($idList= [], $contextList= [], $language= null)
	{
		$return= null;
		$language= $language ? $language : $this->language;

		// Look into loaded contexts.
		foreach (Text::$texts as $context => $texts)
		{
			if ((count((array)$contextList) && in_array($context, (array)$contextList)) || !count((array)$contextList))
			{
				foreach ($idList as $id)
				{
					if (array_key_exists($id, $texts)) $return[$id]= $texts[$id]->{"text_$language"};
				}
			}
		}

		return $return;
	}

	/*
	 * Returns a search engine friendly string to replace the original
	 * accents are replaced with equivalent letter, "'" becomes "", ' ' becomes '-', '&' becomes 'and'
	*/
	// function sef($id= null)
	// {
	// 	// If the Text object is created with only one id, no need to repeat it in get() param.
	// 	// If the Text object contains an ID list then $id param is required.
	// 	if ($id === null) $text= $this->sef($this->id[0], $parameters);
	// 	else
	// 	{
	// 		$toRemove= str_split('\'"()[]{}#$%@!?.,;:+=');
	// 		$this->string= str_replace($toRemove, '', $this->string);
	// 		$this->string= str_replace(array(' & ', ' ', '&'),array('-and-', '-', '-and-'), $this->string);
	// 		if (strlen($this->string= strtolower($this->string))!==strlen(htmlentities($this->string, ENT_NOQUOTES, 'UTF-8')))
	// 		{
	// 			$this->string= preg_replace('/&(\w)\w*;/', '$1', htmlentities($this->string, ENT_NOQUOTES, 'UTF-8'));
	// 		}
	// 		$this->string= str_replace('---', '--', $this->string);
	// 	}

	// 	return $this;
	// }

	// /*
	// 	BBcode syntax:  [tag=value 1|param2=value 2]innerHTML[/tag]
	// 					[tag=value 1|param2=value 2]
	// 					[tag param1=value 1|param2=value 2] for auto-ending tags like img
	// */
	// public function BBcode2html($id= null, $htmlEncoding= false)
	// {
	// 	// If the Text object is created with only one id, no need to repeat it in get() param.
	// 	// If the Text object contains an ID list then $id param is required.
	// 	if ($id === null) $text= $this->get($this->id[0], $parameters);
	// 	else
	// 	{
	// 		if ($htmlEncoding) $this->string= htmlentities($this->string, ENT_QUOTES, 'utf-8');
	// 		$this->string= preg_replace_callback("~\[(/?)([^=\s|\]]+)(?:=?([^|\]]+))?(\|?[^\]]*)\]~", 'BBcode2html_callback',
	// 									   $this->string);
	// 		//nl2br does not remove \n, it only adds <br />, the bbcode js code also checks <br /> and \n to make sure,
	// 		//so if we don't do str_replace(array("\r","\n"),''... we will have 2 line breaks!
	// 		$this->string= stripslashes(str_replace(array("\r","\n"),'',nl2br($this->string)));
	// 	}

	// 	return $this;
	// }

	// function BBcode2html_callback($matches)
	// {
	// 	$closingTag= @$matches[1];//detects if it is the closing tag or not. E.g. '/' in '[/b]'
	// 	$tag=		 @$matches[2];//detects the tag. E.g. 'b' in '[b]'
	// 	$param1=	 @$matches[3];//detects the first param if any. E.g 'src' in '[img=src|alt=image]'
	// 	$rawParams=	 @$matches[4];//detects all the other parameters

	// 	$params= $autoEndingTag= $forceClosingTag= '';
	// 	if ($rawParams) foreach(explode('|',$rawParams) as $v) if ($v) $params.= ' '.str_replace('=','="',$v).'"';

	// 	switch($tag)
	// 	{
	// 		case 'b': $tag= 'strong';break;
	// 		case 'i': $tag= 'em';break;
	// 		case 'u': $tag= 'span';$params.=' style="text-decoration:underline"';break;
	// 		case 'url': $tag= 'a';if (!$closingTag) $params.=' href="'.url($param1).'"';break;
	// 		case 'img':
	// 			$params.= ' src="'.url($param1).'"'.(strpos($params,' alt="')===false?' alt="image"':'');
	// 			$autoEndingTag= 1;
	// 			break;
	// 		case 'color': $tag= 'span';$params.=" style=\"color:$param1\"";break;
	// 		case 'emo': $tag= 'span';$params.=" class=\"emo $param1\"";$forceClosingTag= 1;break;
	// 		case 'br': break;
	// 		case 'div': break;
	// 		case 'span': break;
	// 		case 'br':
	// 			$autoEndingTag= 1;
	// 			break;
	// 		case 'h2':
	// 		case 'h3':
	// 		case 'h4': break;
	// 		default:$tag= '';// If the tag is not listed above it means it is not allowed so remove it.
	// 	}
	// 	if ($autoEndingTag) $htmlOutput= "<$tag$params />";
	// 	else $htmlOutput= !$closingTag?"<$tag$params>":"</$tag>";
	// 	if ($forceClosingTag) $htmlOutput= "<$tag$params></$tag>";
	// 	if ($tag) return $htmlOutput;
	// }


	// function stripBBtags($string, $htmlEncoding= 1)
	// {
	// 	return preg_replace('/\[(.*?)\]/i', '', $string);
	// }


	// function str_split_unicode($str, $l= 0)
	// {
	//     if ($l > 0)
	// 	{
	//         $ret= array();
	//         $len= mb_strlen($str, "UTF-8");
	//         for ($i= 0; $i< $len; $i+= $l) $ret[]= mb_substr($str, $i, $l, "UTF-8");
	//         return $ret;
	//     }
	//     return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	// }

	// public function encode()
	// {
	// 	return 1;
	// }
}
?>