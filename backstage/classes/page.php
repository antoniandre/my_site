<?php
/**
 * Page Model.
 * Design pattern: singleton
 */
Class Page
{
	private static $instance= null;
	public $id;
	public $page;
	public $url;
	public $path;
	public $title;
	public $icon;
	public $metaDescription;
	public $metaKeywords;
	public $parent;
	public $article;
	private $breadcrumbs;
	private $showBreadcrumbs;
	private $language;
	private $rewriteEngine;

	/**
	 * Class constructor
	 */
	private function __construct()
	{
		$this->id= null;
		$this->page= null;
		$this->url= new StdClass();
		$this->path= null;
		$this->title= new StdClass();
		$this->icon= null;
		$this->metaDescription= new StdClass();
		$this->metaKeywords= new StdClass();
		$this->parent= null;
		$this->article= null;
		$this->breadcrumbs= array();
		$this->showBreadcrumbs= true;
		$this->language= null;
		$this->rewriteEngine= true;
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
	 * Set the language
	 * @param [type] $language [description]
	 */
	public function setLanguage($language)
	{
		$this->language= $language;
		$this->detectCurrentPage();
	}

	/**
	 *
	 */
	public function getTitle()
	{
		return $this->title->{$this->language};
	}

	/**
	 *
	 */
	private function detectCurrentPage()
	{
		global $settings, $gets, $aliases;
		$allowedLanguages= array_keys(Language::allowedLanguages);
		$page= null;

		// REQUEST_URI has the whole path including the query string
		// REDIRECT_URL has the whole path except the query string but is not accessible without rewrite engine on.
		// QUERY_STRING has only the query string

		if ($settings->rewriteEngine)
		{
			// First get the path without query string
			// $_SERVER['REDIRECT_URL'] Not set when rewrite engine is off.
			$path= str_replace($settings->root, '', $_SERVER['REDIRECT_URL']);

			if (!$path) $page= getPageByProperty('id', 'home', $this->language);
			elseif (preg_match('~^('.implode('|', $allowedLanguages).')/?~', $path, $match))
			{
				$this->language= $match[1];
				$remainingUrl= str_replace(array("$this->language/", '.html'), '', $path);
				// Detect if nothing is after the language in the path
				if ($path== "$this->language/" || $path== $this->language) $page= getPageByProperty('id', 'home', $this->language);
				elseif (strrpos($path, '.html')!== false) $page= getPageByProperty('url', $remainingUrl, $this->language);
				elseif (array_key_exists($remainingUrl, $aliases)) $page= getPageByProperty('id', $aliases[$remainingUrl], $this->language);
			}
		}
		else
		{
			$urlParts= parse_url($_SERVER['REQUEST_URI']);
			$path= str_replace('/'.dirname(SELF).'/', '', $urlParts['path']);

			// Get the page from $gets if any
			if (isset($gets->page)) $page= getPageByProperty('page', $gets->page, $this->language);
			elseif (!$path || $path== 'index.php') $page= getPageByProperty('id', 'home', $this->language);
		}
		if (!$page) $page= getPageByProperty('id', 'notFound', $this->language);
		$this->page= $page->page;
		$this->url= $page->url;
		$this->path= $page->path;
		$this->title= $page->title;
		$this->icon= $page->icon;
		$this->id= $page->id;
		$this->parent= $page->parent;
		$this->article= $page->article;

		$this->breadcrumbs[0]= new StdClass();
		$this->breadcrumbs[0]->name= $this->page;
		$this->breadcrumbs[0]->url= $this->url;
		$this->breadcrumbs[0]->path= $this->path;
		$this->breadcrumbs[0]->title= $this->title;
		$this->breadcrumbs[0]->id= $this->id;
		$this->breadcrumbs[0]->parent= $this->parent;

		// TODO: finish.
		/*//------------------------------ REDIRECTIONS -------------------------------//
		// - - - - - - - - - - - - redirection to maintenance.php - - - - - - - - - -//
		if ($settings->maintenance && !$user->isAdmin() && $this->page!= 'maintenance')
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable', true);
			//header('Retry-After: Sat, 8 Oct 2011 18:27:00 GMT');//<== optional but better to provide
			header('location: '.url('maintenance'));
			exit;
		}
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
		// - - - - - - - - - - - redirection to page-maintenance.php - - - - - - - - //
		$pagesInMaintenance= explode(',', loadResult("SELECT `value` FROM `misc` WHERE `var`='pageMaintenance'"));
		if (in_array($pageId, $pagesInMaintenance) && !$user->isAdmin())
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable', true);
			//header('Retry-After: Sat, 8 Oct 2011 18:27:00 GMT');//<== optional but better to provide
			header('location: '.url('links/page-maintenance.php'));
			exit;
		}
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
		//---------------------------------------------------------------------------//*/
	}

	/**
	 * update the URL if the lang is not allowed or has changed.
	 */
	public function refresh()
	{
		header('location:'.url($this->page));
		exit;
	}

	/**
	 * Render the current page.
	 * @return the HTML of the full page
	 */
	public function render()
	{
		global $settings, $cookies, $page, $content, $jsonTree, $debugMessage;

		$languageObject = Language::getInstance();
		$language = $languageObject->getCurrent();
		//FINAL RENDER
		$expires = 60*60*24*7;//1 week
		header("Pragma: public");
		header("Cache-Control: maxage=$expires");
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		header('Content-Type: text/html; charset=utf-8');
		header('Content-language: '.strtolower($language));

		$tpl = new Template(__DIR__.'/../templates');
		$tpl->set_file('page-tpl', 'page.html');

		//-------------------- SEO metas --------------------//
		$tpl->set_block('page-tpl', 'seoMetas', 'theSeoMetas');
		$tpl->set_block('page-tpl', 'googleAnalytics', 'theGoogleAnalytics');
		// Search engines must not crawl the backstage:
		$tpl->set_var(['metaDesc' => 'metadesc',
						'metaKey' => 'metakey',
						'siteName' => $settings->siteName,
						'languageFull' => $languageObject->getCurrentFull(),
						'author' => $settings->author,
						'siteUrl' => $settings->siteurl,
						'pageTitle' => htmlentities($page->title->{$this->language}, ENT_NOQUOTES, 'utf-8').$settings->titleSuffix,
						'noCrawl', strpos(SELF, 'backstage') !== false ? 'no' : '']);
		if (isset($noCrawl)) $tpl->set_var('theSeoMetas', '');
		else $tpl->parse('theSeoMetas', 'seoMetas', true);
		if (!IS_LOCAL && isset($settings->GAtracker) && $settings->GAtracker)
		{
			$tpl->set_var('GAtracker', $settings->GAtracker);
			$tpl->parse('theGoogleAnalytics', 'googleAnalytics', true);
		}
		else $tpl->set_var('theGoogleAnalytics', '');
		//---------------------------------------------------//

		if ($this->showBreadcrumbs) $this->calculateBreadcrumbs($this);
		$tpl->set_var(['content' => $content,
					   'ROOT' => $settings->root,
					   'SELF' => url('SELF'),
					   'language' => $language,
					   'pageId' => $page->id,
					   'page' => $page->page,
					   // Used for js and css.
					   'backstage' => strpos($page->path, 'backstage/') !== false ? '&amp;backstage=1' : '',
					   'h1' => $page->title->$language,
					   'icon' => $page->icon ? " class=\"$page->icon\"" : '',
					   'homeUrl' => url(getPageByProperty('id', 'home', $language)->page.'.php'),
					   'homeText' => getPageByProperty('id', 'home', $language)->title->$language,
					   'contactUrl' => url(getPageByProperty('id', 'contact', $language)->page.'.php'),
					   'contactText' => getPageByProperty('id', 'contact', $language)->title->$language,
					   'classEn' => $language == 'en' ? ' active' : '',
					   'classFr' => $language == 'fr' ? ' active' : '',
					   'error' => Error::getInstance()->getCount() ? "<div id=\"error\"><p><span class=\"i-alert\"></span> ERROR</p>".Error::getInstance()->show()."</div>" : '',
					   'debug' => Debug::getInstance()->getCount() ? "<div id=\"debug\"><p><span class=\"i-bug\"></span> DEBUG </p>".Debug::getInstance()->show()."</div>" : '',
					   'headerMessage' => ($headerMessage = Message::show('header')) ? "<div id=\"headerMessage\">$headerMessage</div>" : '',
					   'contentMessage' => ($contentMessage = Message::show('content')) ? "<div id=\"contentMessage\">$contentMessage</div>" : '',
					   'copyright' => textf(19, $settings->siteName, date('Y')),
					   'sitemapUrl' => url(getPageByProperty('id', 'sitemap', $language)->page.'.php'),
					   'sitemapText' => getPageByProperty('id', 'sitemap', $language)->title->$language,
					   'legalTermsUrl' => url(getPageByProperty('id', 'legalTerms', $language)->page.'.php'),
					   'legalTermsText' => getPageByProperty('id', 'legalTerms', $language)->title->$language,
					   'breadcrumbs' => $this->showBreadcrumbs? "<div id=\"breadcrumbs\">{$this->renderBreadcrumbs()}</div><br class=\"clear\"/>" : ''
					  ]);

		$tpl->set_block('page-tpl', 'cookieNotice', 'theCookieNotice');
		if (!isset($cookies->cookie_consent))
		{
			$t = new Text(38);
			$tpl->set_var(['cookieNoticeText' => $t->format(['bb2html' => true])->get(),
						   'iAgree' => text(39),
						   'learnMore' => text(40)]);
			$tpl->parse('theCookieNotice', 'cookieNotice', true);
		}
		else $tpl->set_var('theCookieNotice', '');

		if (Error::getInstance()->getCount()) Error::getInstance()->log();
		if (Debug::getInstance()->getCount()) Debug::getInstance()->log();

		return $tpl->parse('display', 'page-tpl');
	}

	/**
	 * Toggle the visibility of the breadrcumbs
	 *
	 * @param boolean $bool: visible if true, hidden and not processed if false from beginning.
	 */
	public function setBreadcrumbsVisiblity($bool)
	{
		$this->showBreadcrumbs = $bool;
	}

	/**
	 *
	 */
	function calculateBreadcrumbs($page)
	{
		if ($page->parent)
		{
			$matchedPage = getPageByProperty('id', $page->parent, $this->language);
			array_unshift($this->breadcrumbs, $matchedPage);
			if (count($this->breadcrumbs) >= 10) Error::getInstance()->add("The breadcrumbs has ".count($this->breadcrumbs)." pages.");
			if ($matchedPage->parent) $this->calculateBreadcrumbs($matchedPage);
		}
	}

	/**
	 *
	 */
	function renderBreadcrumbs()
	{
		$output= '';
		foreach ($this->breadcrumbs as $k => $page)
		{
			if ($k !== count($this->breadcrumbs)-1) $output .= ($k? '<span class="separator i-play"> </span>' : '').'<a href="'.url("$page->page.php").'" class="'.$page->id.'"><span>'.$page->title->{$this->language}.'</span></a>';
			else $output .= ($k? '<span class="separator i-play"> </span>' : '').'<span class="current '.$page->id.'"><span>'.$page->title->{$this->language}.'</span></span>';
		}
		return $output;
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