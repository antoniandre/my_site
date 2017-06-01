<?php
/**
 * Page Model.
 * Design pattern: singleton.
 *
 * @todo: turn it to Multiton and add method getCurrent().
 */
Class Page
{
	private static $instance = null;
	public $id;// Id of the current page.
	public $page;
	public $url;
	public $path;
	public $title;
	public $h1;

	// A number between 0 and 100 to set the <header> html tag height making the
	// page to start from this same point.
	private $headerHeight;
	private $topZoneContent;
	private $bottomZoneContent;

	private $social;// To activate social networks (Facebook, Twitter, Google plus).
	public $socialImage;// Picture of the page to share on social networks.
	public $icon;// An icon to display on the left of the <h1> title.
	public $metaDescription;
	public $metaKeywords;
	public $parent;// The parent page for breadcrumbs.
	public $article;// An article id if any.
	private $breadcrumbs;
	private $showBreadcrumbs;
	private $language;
	private $rewriteEngine;

	/**
	 * Class constructor.
	 */
	private function __construct()
	{
		$this->id = null;
		$this->page = null;
		$this->url = new StdClass();
		$this->path = null;
		$this->title = new StdClass();
		$this->h1 = '';//!\ Empty string is distinct from null in later var use.
		$this->headerHeight = 60;
		$this->topZoneContent = null;
		$this->bottomZoneContent = null;
		$this->social = false;
		$this->socialImage = null;
		$this->icon = null;
		$this->metaDescription = new StdClass();
		$this->metaKeywords = new StdClass();
		$this->parent = null;
		$this->article = null;
		$this->breadcrumbs = array();
		$this->showBreadcrumbs = true;
		$this->language = null;
		$this->rewriteEngine = true;
	}

	/**
	 * Get the only instance of this class.
	 *
	 * @return Page object: the only instance of this class.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Set the language.
	 *
	 * @param String $language: the language to display the texts in.
	 */
	public function setLanguage($language)
	{
		$this->language = $language;
		$this->detectCurrentPage();
	}

	/**
	 * Set the h1 title of the page.
	 *
	 * @param String $h1: the h1 texts to display on the page. Empty string to force no H1.
	 */
	public function setH1($h1)
	{
		$this->h1 = $h1 ? $h1 : null;
	}

	/**
	 * Set the <header> html tag height making the page to start from this same point.
	 *
	 * @param Integer $height: the height of the <header> tag.
	 */
	public function setHeaderHeight($height)
	{
		$this->headerHeight = (int)$height;
	}

	/**
	 * Set the <header> html if any.
	 *
	 * @param String $html: the height of the <header> tag.
	 * @param String $zone: the <header> zone where to display the given html. Among: 'top', 'bottom'.
	 */
	public function setHeaderContent($html, $zone = 'bottom')
	{
		if (!in_array($zone, ['top', 'bottom'])) Cerror::add(__CLASS__.'::'.ucfirst(__FUNCTION__)."(): The given zone \"$zone\" does not exist. Choose among 'top' or 'bottom'.", 'WRONG DATA', true);
		else $this->{$zone.'ZoneContent'} = $html;
	}

	/**
	 * Add social networks to the page.
	 * Set the Facebook image source for the current page if needed.
	 *
	 * @param String $imgSrc: the src of the image you want for the article you share on Facebook.
	 * @return void.
	 */
	public function addSocial($imgSrc = null)
	{
		$this->social = true;
		if ($imgSrc) $this->socialImage = $imgSrc;
	}

	/**
	 * get the title of the current page.
	 *
	 * @param String $language: an optional language to get the title in. If none provided get the current language title.
	 * @return String: the title of the page.
	 */
	public function getTitle($language = null)
	{
		return $language && Language::isAllowed($language) ? $this->title->{$language}
														   : $this->title->{$this->language};
	}

	/**
	 * get the url of the current page.
	 *
	 * @param String $language: an optional language to get the title in. If none provided get the current language title.
	 * @return String: the url of the page.
	 */
	public function getUrl($language = null)
	{
		return $language && Language::isAllowed($language) ? $this->url->{$language}
														   : $this->url->{$this->language};
	}

	/**
	 * detect the current page.
	 *
	 * @return void.
	 */
	private function detectCurrentPage()
	{
		global $aliases;
		$settings = Settings::get();
		$allowedLanguages = array_keys(Language::allowedLanguages);
		$page = null;

		// REQUEST_URI has the whole path including the query string
		// REDIRECT_URL has the whole path except the query string but is not accessible without rewrite engine on.
		// QUERY_STRING has only the query string

		if ($settings->rewriteEngine)
		{
			// First get the path without query string.
			// $_SERVER['REDIRECT_URL'] Not set when rewrite engine is off.
			$path = $_SERVER['REDIRECT_URL'];
			if ($settings->root && $settings->root !== '/') $path = str_replace($settings->root, '', $path);

			// Remove the potential uneeded preceding slash.
			if ($path{0} === '/') $path = trim($path, '/');

			if (!$path) $page = getPageByProperty('id', 'home', $this->language);
			elseif (preg_match('~^('.implode('|', $allowedLanguages).')/?~', $path, $match))
			{
				$this->language = $match[1];
				$remainingUrl = str_replace(array("$this->language/", '.html'), '', $path);
				// Detect if nothing is after the language in the path
				if ($path === "$this->language/" || $path === $this->language) $page = getPageByProperty('id', 'home', $this->language);
				elseif (strrpos($path, '.html') !== false) $page = getPageByProperty('url', $remainingUrl, $this->language);
				elseif (array_key_exists($remainingUrl, $aliases)) $page = getPageByProperty('id', $aliases[$remainingUrl], $this->language);
			}
		}
		else
		{
			$urlParts = parse_url($_SERVER['REQUEST_URI']);
			$path = str_replace('/'.dirname(SELF).'/', '', $urlParts['path']);

			// Get the page from $gets if any.
			$gets = Userdata::get();
			if (isset($gets->page)) $page = getPageByProperty('page', $gets->page, $this->language);
			elseif (!$path || $path == 'index.php') $page = getPageByProperty('id', 'home', $this->language);
		}
		if (!$page) $page = getPageByProperty('id', 'notFound', $this->language);
		$this->page = $page->page;
		$this->url = isset($page->url) ? $page->url : '';
		$this->path = $page->path;
		$this->title = isset($page->title) ? $page->title : '';
		$this->metaKeywords = isset($page->metaKey) ? $page->metaKey : '';
		$this->metaDescription = isset($page->metaDesc) ? $page->metaDesc : '';
		$this->icon = $page->icon;
		$this->id = $page->id;
		$this->parent = $page->parent;
		$this->article = (object)['id' => $page->article];

		$this->breadcrumbs[0] = new StdClass();
		$this->breadcrumbs[0]->name = $this->page;
		$this->breadcrumbs[0]->url = $this->url;
		$this->breadcrumbs[0]->path = $this->path;
		$this->breadcrumbs[0]->title = $this->title;
		$this->breadcrumbs[0]->id = $this->id;
		$this->breadcrumbs[0]->parent = $this->parent;
	}

	/**
	 * Simply returns if the page is an article or not.
	 *
	 * @return boolean.
	 */
	public function isArticle()
	{
		return isset($this->article->id);
	}

	/**
	 * Simply returns if the page is a backstage page or not.
	 *
	 * @return boolean.
	 */
	public function isBackstage()
	{
		return strpos($this->path, 'backstage/') !== false;
	}

	/**/
	public function getArticleInfo()
	{
		return getArticleInfo($this->article->id);
	}

	/**
	 * Redirect to the maintenance page.
	 *
	 * @todo: Finish the code.
	 * @return void.
	 */
	public function redirectToMaintenance()
	{
		// - - - - - - - - - - - - redirection to maintenance.php - - - - - - - - - -//
		if ($settings->maintenance && !$user->isAdmin() && $this->page != 'maintenance')
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable', true);
			//header('Retry-After: Sat, 8 Oct 2011 18:27:00 GMT');//<== optional but better to provide
			header('location: '.url('maintenance'));
			exit;
		}
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
		// - - - - - - - - - - - redirection to page-maintenance.php - - - - - - - - //
		$pagesInMaintenance = explode(',', loadResult("SELECT `value` FROM `misc` WHERE `var`='pageMaintenance'"));
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
	 * Update the URL if the lang is not allowed or has changed.
	 *
	 * @return void.
	 */
	public function refresh()
	{
		header('location:'.url($this->page));
		exit;
	}


	/**
	 * Render the current page.
	 *
	 * @return the HTML of the full page
	 */
	public function render()
	{
		global $page, $content, $user;
		$settings = Settings::get();
		$cookies = Userdata::get('cookie');
		$language = Language::getCurrent();

		// FINAL RENDER
		$expires = 60*60*24*7;//1 week.
		header("Pragma: public");
		header("Cache-Control: maxage=$expires");
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		header('Content-Type: text/html; charset=utf-8');
		header('Content-language: '.strtolower($language));

		$tpl = new Template(ROOT.'backstage/templates');
		$tpl->set_file('page-tpl', 'page.html');
		$tpl->set_block('page-tpl', 'h1Block', 'theH1Block');
		$tpl->set_block('page-tpl', 'googleAnalytics', 'theGoogleAnalytics');


		if ($this->showBreadcrumbs) $this->calculateBreadcrumbs($this);

		// Detect if should show error according to the config choice and the current user type.
		// @see: config.ini [advanced].
		$showErrors = ($settings->debugMode == 1 && $user->isAdmin()) || $settings->debugMode == 2;

		$newsletter = new Form(['class' => 'newsletter']);
		$newsletter->addElement('email',
				                ['name' => 'newsletter', 'value' => '', 'placeholder' => text(80)],// Your email address.
				                ['validation' => 'required', 'label' => text(81)]);// Subscribe to the newsletter.
		$newsletter->addButton('validate', text(85));// Button label: OK.
		$newsletter->validate
		(
			function($result, $form)
			{
				$settings = Settings::get();

				if (!$result->invalid)
				{
					$db = database::getInstance();
					$q = $db->query();
					$q->insert('newsletter_subscribers', ['email' => $form->getPostedData('email')])
					  ->run();

					if ($q->info()->affectedRows)
					{
						$to      = $form->getPostedData('email');
						$subject = textf(86, $settings->siteName);// Newsletter: Activate your email address now.

						// @TODO: have a proper link for email activation and do the activation task in the User class.
						$message = nl2br(textf(87, $settings->siteUrl));
						$headers = 'MIME-Version: 1.0' . "\r\n".
								   'Content-type: text/html; charset=iso-8859-1'."\r\n".
								   "From: $settings->adminEmail\r\n".
						           "Reply-To: $settings->adminEmail\r\n".
					               'X-Mailer: PHP/'.phpversion();

						// Your subscription has been registered.
						if (mail($to, $subject, $message, $headers)) new Message(text(82), 'valid', 'success', 'header');
						else new Message(text(83), 'error', 'error', 'header');// The email could not be delivered.
					}

					else new Message(text(84), 'error', 'error', 'header');// there was a pb.
 				}
			}
		);

		$tpl->set_var(['content' => $content,
					   'ROOT' => $settings->root,
					   'SELF' => url('SELF'),
					   'language' => $language,
					   'pageId' => $page->id,
					   'metaDesc' => $page->metaDescription->$language ? $page->metaDescription->$language : '',
					   'metaKey' => $page->metaKeywords->$language ? $page->metaKeywords->$language : '',
					   'siteName' => $settings->siteName,
					   'languageFull' => Language::getCurrentFull(),
					   'author' => $settings->author,
					   'siteUrl' => $settings->siteUrl,
					   'pageTitle' => htmlentities($page->title->{$this->language}, ENT_NOQUOTES, 'utf-8').$settings->titleSuffix,
					   'socialImgSrc' => $this->socialImage ? $this->socialImage : url('images/?i='.$settings->logoSrc),
					   'noCrawl', strpos(SELF, 'backstage') !== false ? 'no' : '',
				       'page' => $page->page,

					   'newsletter' => $newsletter->render(),

					   'logoSrc' => url('images/?i='.$settings->logoSrc),
					   'headerImgSrc' => url('images/?i=home-slides/sailing_xl.jpg'),
					   'footerImgSrc' => url('images/?i=sunset_l.jpg'),
					   'pageWatermarkSrc' => url('images/?i=vietnam-map.png'),
					   'backToHomeText' => text(45),
					   'homeUrl' => url(getPageByProperty('id', 'home', $language)->page.'.php'),
					   'homeText' => getPageByProperty('id', 'home', $language)->title->$language,
					   'h1' => ucfirst($this->h1 ? $this->h1 : $page->title->$language),
					   // If h1 is explicitly set to null then set an h1 with the site name for SEO.
					   'headerHeight' => $this->headerHeight,
					   'topZoneContent' => $this->topZoneContent ? $this->topZoneContent : '',
					   'bottomZoneContent' => $this->bottomZoneContent ? $this->bottomZoneContent : '',
					   'sitemapTree' => getTree('sitemap', ['[article]']),
					   'articlesListText' => text('Tous les articles'),
					   'articlesList' => $this->renderArticlesList(),
					   'strongOrH1' => $this->h1 === null ? 'h1' : 'strong',
					   'icon' => $page->icon ? " class=\"$page->icon\"" : '',
					   'breadcrumbs' => $this->showBreadcrumbs ? "<div id=\"breadcrumbs\">{$this->renderBreadcrumbs()}</div>" : '',
					   'goDownLink' => $this->headerHeight >= 60 ? "<a href=\"#top\" class=\"goDown i-chevron-d\"></a>" : '',
					   // 'social' => $this->social ? '<div class="social clearfix"></div>' : '',// Moved inside article only.
					   'contactUrl' => url(getPageByProperty('id', 'contact', $language)->page.'.php'),
					   'contactText' => getPageByProperty('id', 'contact', $language)->title->$language,
					   'classEn' => $language == 'en' ? ' active' : '',
					   'classFr' => $language == 'fr' ? ' active' : '',
					   'error' => Cerror::getCount() && $showErrors ? "<div id=\"error\"><p><span class=\"i-alert\"></span> ERROR</p>".Cerror::show()."</div>" : '',
					   'debug' => Debug::getInstance()->getCount() && $showErrors ? "<div id=\"debug\"><p><span class=\"i-bug\"></span> DEBUG </p>".Debug::getInstance()->show()."</div>" : '',
					   'headerMessage' => ($headerMessage = Message::show('header')) ? "<div id=\"headerMessage\">$headerMessage</div>" : '',
					   'contentMessage' => ($contentMessage = Message::show('content')) ? "<div id=\"contentMessage\">$contentMessage</div>" : '',
					   'copyright' => textf(19, $settings->siteName, date('Y')),
					   'sitemapUrl' => url(getPageByProperty('id', 'sitemap', $language)->page.'.php'),
					   'sitemapText' => getPageByProperty('id', 'sitemap', $language)->title->$language,
					   'legalTermsUrl' => url(getPageByProperty('id', 'legalTerms', $language)->page.'.php'),
					   'legalTermsText' => getPageByProperty('id', 'legalTerms', $language)->title->$language
					  ]);

		//----------------- Google Analytics ----------------//
		if (!IS_LOCAL && isset($settings->GAtracker) && $settings->GAtracker)
		{
			$tpl->set_var('GAtracker', $settings->GAtracker);
			$tpl->parse('theGoogleAnalytics', 'googleAnalytics', true);
		}
		else $tpl->set_var('theGoogleAnalytics', '');
		//---------------------------------------------------//

		// If h1 is explicitly set to null then remove the tag.
		if ($this->h1 === null) $tpl->set_var('theH1Block', '');
		else $tpl->parse('theH1Block', 'h1Block', true);

		if (Cerror::getCount()) Cerror::log();
		if (Debug::getInstance()->getCount()) Debug::getInstance()->log();

		return $tpl->parse('display', 'page-tpl');
	}

	/**
	 * Toggle the visibility of the breadrcumbs.
	 *
	 * @param boolean $bool: visible if true, hidden and not processed if false from beginning.
	 */
	public function setBreadcrumbsVisiblity($bool)
	{
		$this->showBreadcrumbs = $bool;
	}

	/**
	 * Calculate the breadcrumbs.
	 * Recursive function that looks for the parent page of a given page until it reaches the top parent.
	 * The generated breadcrumbs is then stored in the current page object attribute: $this->breadcrumbs for later use.
	 *
	 * @param Page Object $page: the current page.
	 * @param Integer $maxDepth: the maximum depth allowed before an error is triggered (to prevent infinite loop).
	 *                           Default to 10.
	 * @return void.
	 */
	function calculateBreadcrumbs($page, $maxDepth = 10)
	{
		if ($page->parent)
		{
			$matchedPage = getPageByProperty('id', $page->parent, $this->language);
			array_unshift($this->breadcrumbs, $matchedPage);

			// Prevent an infinite loop if there is an error (too deep).
			if ($maxDepth && count($this->breadcrumbs) >= $maxDepth) Cerror::add("The breadcrumbs has ".count($this->breadcrumbs)." pages.");

			if ($matchedPage->parent) $this->calculateBreadcrumbs($matchedPage);
		}
	}

	/**
	 * Render the breadcrumbs.
	 *
	 * @return string: The breadcrumbs generated html.
	 */
	function renderBreadcrumbs()
	{
		$output = '';
		foreach ($this->breadcrumbs as $k => $page)
		{
			$title = ucfirst($page->title->{$this->language});
			if ($k !== count($this->breadcrumbs)-1) $output .= ($k ? '<span class="separator i-play"> </span>' : '').'<a href="'.url("$page->page.php").'" class="'.$page->id.'"><span>'.$title.'</span></a>';
			else $output .= ($k ? '<span class="separator i-play"> </span>' : '').'<span class="current '.$page->id.'"><span>'.$title.'</span></span>';
		}
		return $output;
	}

	public function renderArticlesList()
	{
		$output = '<ul class="lvl1 glyph">';
		$currYear = null;
		$i = 0;
		foreach (Article::getMultiple() as $article)
		{
			$year = substr($article->created, 0 ,4);
			if ($currYear !== $year)
			{
				$output .= ( $i ? '</ul></li>' : '')."<li class='parent'><h4>$year</h4><ul class='lvl2'>";
				$currYear = $year;
			}
			$title = ucfirst($article->title);
			$output .= '<li><a href="'.url("$article->page.php").'" class="'.$article->id.'"><span>'.$title.'</span></a></li>';
			$i++;
		}
		$output .= '</ul>';

		return $output;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * Singleton instance.
	 *
	 * @return void.
	 */
	private function __clone()
	{
	}
}
?>