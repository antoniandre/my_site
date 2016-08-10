<?php

Class Article
{
	private static $instance = null;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
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
	 * Get an article by its id in a given language or current language by default.
	 *
	 * @param int $articleId: the id of the article you want to fetch.
	 * @param string $language: the language you want the article content to be displayed in default to current language.
	 * @return StdClass Object: the matched article as an object.
	 * @example:
	 * stdClass Object
	 * (
	 *     [id] => 29
	 *     [content] => 'blah blah...'
	 *     [created] => 2016-01-08 15:17:05
	 *     [author] => Antoni
	 *     [page] => singapore-2
	 *     [image] => images/gallery/2015-11-14_15.54.01_m.jpg
	 *     [published] => 1
	 *     [url] => singapore-2
	 *     [title] => Singapore 2
	 * )
	 */
	public static function get($articleId, $language = null)
	{
		$articles = self::getMultiple(['language' => $language, 'idList' => [$articleId], 'fetchContent' => true]);
		return count($articles) ? $articles[0] : null;
	}

	public static function getByYear($year)
	{
		return self::getByDateRange($year);
	}
	public static function getByDateRange($dateRange = [null, null])
	{
		list($start, $end) = array_pad((array)$dateRange, 2, null);

		// If date is a year or a timestamp.
		if (is_numeric($start)) $start = strlen($start) === 4 ? "$start-01-01 00:00" : date('Y-m-d H:i:s', $start);
		if (is_numeric($end))   $end = strlen($end) === 4 ? "$end-01-01 00:00" : date('Y-m-d H:i:s', $end);

		return self::getMultiple(['dateRange' => [$start, $end]]);
	}

	/**
	 * Get the content of an article.
	 *
	 * @todo : to develop.
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public static function getContent($params = [])
	{}

	public static function getMultiple($params = [])
	{
		$defaults =
		[
			'language' => Language::getCurrent(),
			'idList'   => [],
			// 'tags'     => [],// Not ready yet. @TODO: develop tags.
			'dateRange' => [null, null],// [dateBegin, dateEnd].
			'fetchContent' => false// By default don't fetch content to lighten the query.
			// You should not carry all the content of every articles in a var unless you purposely want to display multiple
			// contents on same page.
			// To get the content of one article at a time, yoyu can use getContent() method.
		];
		$params = array_merge($defaults, $params);
		if (!$params['dateRange'][1]) $params['dateRange'][1] = 'NOW()';
		$db = Database::getInstance();
		$language = $params['language'] && Language::exists($params['language']) ? $params['language'] : Language::getCurrent();

		$q = $db->query();
		$fields = [$q->colIn('id', 'articles'),
				   $q->colIn('created', 'articles'),
				   $q->colIn('firstName', 'users')->as('author'),
				   $q->col('page'),
				   $q->col('image'),
				   $q->col('published'),
				   $q->colIn("url_$language", 'pages')->as('url'),
				   $q->colIn("title_$language", 'pages')->as('title')];
		if ($params['fetchContent']) $fields[] = $q->col("content_$language")->as('content');
		$q->select('articles', $fields)
		  ->relate('articles.author', 'users.id')
		  ->relate('pages.article', 'articles.id')
		  ->relate('articles.category', 'article_categories.id')
		  ->orderBy('articles.created', 'desc');

		if (is_array($params['idList']) && count($params['idList']))
		{
			$w = $q->where();
			$w->colIn('id', 'articles')->in(...$params['idList']);
		}
		if (is_array($params['dateRange']) && count($params['dateRange']))
		{
			if (!isset($w)) $w = $q->where();
			else $w->and();
			$w->colIn('created', 'articles')->between(...$params['dateRange']);
		}

		return $q->run()->loadObjects();
	}

	public static function getPrevNext($articleId, $returnHtml = true)
	{
		$db = Database::getInstance();
		$language = Language::getCurrent();

		$q = $db->query();
		$q  ->select('articles',
				   [$q->colIn('id', 'articles'),
				    $q->col('page')])
			->relate('pages.article', 'articles.id')
			->relate('articles.category', 'article_categories.id')
			->orderBy('articles.created', 'desc')
			->where()
				->colIn('name', 'article_categories')->eq('travel')
		  		->and()->col('published')->eq(1)
		  		->and()->col('published')->dif(0);

		$articles = $q->run()->loadObjects();

		foreach ($articles as $k => $article)
		{
			if ($article->id == $articleId)
			{
				$next = isset($articles[$k-1]) ? $articles[$k-1] : null;
				$prev = isset($articles[$k+1]) ? $articles[$k+1] : null;
				break;
			}
		}

		if ($returnHtml)
		{
			$prev = $prev ? '<a href="'.url($prev->page).'">'.text(73).'</a>' : '';
			$next = $next ? '<a href="'.url($next->page).'">'.text(74).'</a>' : '';
		}

		return [$prev, $next];
	}

	public static function getTranslations($articleId)
	{
		global $availableTranslations;
		$availableTranslations = [];

		foreachLang(function($lang) use($articleId)
		{
			global $availableTranslations;

			if ($lang !== Language::getCurrent())
			{
				$article = self::get($articleId, $lang);
				if ($article->content)
				{
					$availableTranslations[$lang] = (object)['languageLabel' => Language::getLanguageLabel($lang), 'article' => $article];
				}
			}
		});

		return $availableTranslations;
	}
}
?>