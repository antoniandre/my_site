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
	 * Get 1 article by its id in a given language or current language by default.
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
	 *     [status] => published
	 *     [url] => singapore-2
	 *     [title] => Singapore 2
	 * )
	 */
	public static function get($articleId, $language = null)
	{
        $params =
        [
            'language'     => $language,
            'idList'       => [$articleId],
            'fetchTags'    => true,
            'fetchContent' => true
        ];
		$articles = self::getMultiple($params);
		return count($articles) ? $articles[$articleId] : null;
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
	 * @todo test this method.
     * @param int $articleId: the id of the article you want to fetch.
	 * @param  array  $language:
	 * @return string: the article content in the given/current language.
	 */
	public static function getContent($articleId, $language = null)
	{
        $db = Database::getInstance();
        $language = $language && Language::exists($language) ? $language : Language::getCurrent();

        $q = $db->query();
        $q->select('articles', $q->col("content_$language")->as('content'));
        $w = $q->where();
        $w->col('id')->eq($articleId);

        return $q->run()->loadObject();
    }

    /**
	 * Get the articles from database.
     * Fetching tags and content is optional so the query can be as light as possible.
     * You can also optionally fetch articles by date range.
     *
     * @params Array $params: see $defaults inside function.
	 * @return Array: an array of objects.
     */
	public static function getMultiple($params = [])
	{
		$defaults =
		[
            'limit'        => 0,// Limit of articles to fetch can be a simple number or array of [from, to].
			'language'     => Language::getCurrent(),
			'idList'       => [],
            'dateRange'    => [null, null],// [dateBegin, dateEnd].
            'tags'         => [],// @todo: develop filter articles by tags.
			'fetchStatus'  => 'published',// Only fetch articles if in the list of status. Provide value or array of strings.
			'fetchTags'    => false,
			'fetchContent' => false// By default don't fetch content to lighten the query.
			// You should not carry all the content of every articles in a var unless you purposely want to display multiple
			// contents on same page.
			// Use getContent() method to get the content of one article at a time.
		];
		$params   = array_merge($defaults, $params);
		if (!$params['dateRange'][1]) $params['dateRange'][1] = 'NOW()';
		$db       = Database::getInstance();
		$language = $params['language'] && Language::exists($params['language']) ? $params['language'] : Language::getCurrent();

		$q      = $db->query();
		$fields = [$q->colIn('id', 'articles'),
				   $q->colIn('created', 'articles'),
				   $q->colIn('firstName', 'users')->as('author'),
				   $q->col('page'),
				   $q->col('image'),
                   $q->col('status'),
				   $q->colIn("url_$language", 'pages')->as('url'),
				   $q->colIn("title_$language", 'pages')->as('title'),
				   $q->colIn('name', 'article_categories')->as('category')];

        // If content fetching.
        if ($params['fetchContent']) $fields[] = $q->col("content_$language")->as('content');

        $q->select('articles', $fields)
		  ->relate('articles.author', 'users.id')
          ->relate('pages.typeId', 'articles.id')
          ->relate('articles.category', 'article_categories.id')
		  ->orderBy('articles.created', 'desc');

		$w = $q->where()->colIn('type', 'pages')->eq('article');

		// If a list of article ids is provided.
		if (is_array($params['idList']) && count($params['idList']))
		{
			$w->and($w->colIn('id', 'articles')->in(...$params['idList']));
		}

		$params['fetchStatus'] = (array)$params['fetchStatus'];
		if (count($params['fetchStatus']) && $params['fetchStatus'][0])
		{
			$w->and($w->col('status')->in(...$params['fetchStatus']));
		}

        // If date range provided and at least one of the end is set.
		if (is_array($params['dateRange']) && count($params['dateRange'])
            && $params['dateRange'][0] && $params['dateRange'][1])
		{
			$w->and($w->colIn('created', 'articles')->between(...$params['dateRange']));
		}

        if ($params['limit'])
		{
			// if (is_array($params['limit'])) $q->limit($params['limit']);
			// First convert $params['limit'] to an array (can be one or 2 items),
			// then extract the values into args.
			$q->limit(...(array)$params['limit']);
		}

        // Index articles list by id.
        $articles = $q->run()->loadObjects('id');

        // If tags fetching. First check $articles is not empty array if no match.
        if (count($articles) && $params['fetchTags'])
        {
            $tags = self::getTags(array_keys($articles));

            foreach ($tags as $k => $tag)
            {
                if (!isset($articles[$tag->article]->tags)) $articles[$tag->article]->tags = [];
                $articles[$tag->article]->tags[$tag->id] = $tag;
            }
        }

		return $articles;
    }

    public static function getTags($idList = [])
    {
        $language = Language::getCurrent();
        $db       = Database::getInstance();
        $q        = $db->query();

        $fields = [$q->colIn('id', 'tags'),
                   $q->colIn('article', 'article_tags'),
                   $q->colIn('name', 'tags'),
                   $q->colIn("text$language", 'tags')];

        $q->select('article_tags', $fields)
          ->relate('tags.id', 'article_tags.tag');

        if (is_array($idList) && count($idList))
        {
            $w = $q->where();
            $w->colIn('article', 'article_tags')->in(...$idList);
        }

        return $q->run()->loadObjects();
	}

	public static function getPrevNext($articleId, $categoryName = null, $returnHtml = true)
	{
		$prev = null;
		$next = null;
		$db   = Database::getInstance();
		$q    = $db->query();

		$q->select('articles', [$q->colIn('id', 'articles'), $q->col('page')])
		  ->relate('pages.typeId', 'articles.id')
		  ->relate('articles.category', 'article_categories.id')
		  ->orderBy('articles.created', 'desc');
		$w = $q->where()->col('status')->eq('published');

		if ($categoryName) $w->and()->colIn('name', 'article_categories')->eq($categoryName);

		$articles = $q->run()->loadObjects();

		// The previous query may return no article.
		if ($articles) foreach ($articles as $k => $article)
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
			$prev = $prev ? '<a href="' . url($prev->page) . '">' . text(73) . '</a>' : '';
			$next = $next ? '<a href="' . url($next->page) . '">' . text(74) . '</a>' : '';
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