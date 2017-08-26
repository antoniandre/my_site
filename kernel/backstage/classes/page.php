<?php
/**
 * Page Model.
 * Design pattern: Multiton.
 */
class Page
{
	const rewriteEngine       = true;

	private static $current   = null;
	private static $instances = [];// Array of all the available (instanciated) Page instances.
	private static $allPages  = [];// Array of stdCass pages straight out of DB.
	private static $language  = null;

	public $id;// Id of the current page (only one and shared among all languages).
	public $page;// Can be different from a language to another.
	public $url;
	public $path;
	public $title;
	public $h1;
	public $content;

	// A number between 0 and 100 to set the <header> html tag height making the
	// page to start from this same point.
    private $headerHeight;
	private $parallax;// Enable/disable the effect on the header background. Enabled by default.
	private $topZoneContent;
	private $bottomZoneContent;

	private $social;// To activate social networks (Facebook, Twitter, Google plus).
	public $socialImage;// Picture of the page to share on social networks.
	public $icon;// An icon to display on the left of the <h1> title.
	public $metaDescription;
	public $metaKeywords;
	public $parent;// The parent page for breadcrumbs.

	// A special page type [page_type] like 'article' or any other entity that the developer can create.
	// If you create a special page type (not equal to 'page') you have to add a page entry
	// in the database 'pages' table. (Take 'article' as a model).
	// Then your php page that will treat all those specific pages must be named the same and placed in pages folder.
	// E.g. '/pages/[page_type].php'.
	// Then you can use a shared template for all your pages of the same type with:
	//     $tpl = newPageTpl('[page_type]');
	public $type;// Default: 'page'.
	public $typeId;// A page type integer id if any. E.g. article id = 3.
	public $article;// An article id if any.

	private $breadcrumbs;
	private $showBreadcrumbs;


	/**
	 * Class constructor.
	 */
	private function __construct($pageId = null)
	{
		if (!count(self::$allPages)) self::fetchAll();

		$pages = self::$allPages;
		$page  = isset($pages[$pageId]) ? $pages[$pageId] : $pages['not-found'];

		$this->id                = $page->id;
		$this->page              = $page->page;
		$this->url               = isset($page->url) ? $page->url : '';
		$this->path              = $page->path;
		$this->title             = isset($page->title) ? $page->title : '';
		$this->h1                = '';//!\ Empty string is distinct from null in later var use.
        $this->headerHeight      = 60;
		$this->parallax          = true;
		$this->topZoneContent    = null;
		$this->bottomZoneContent = null;
		$this->social            = false;
		$this->socialImage       = null;
		$this->icon              = $page->icon;
		$this->metaDescription   = isset($page->metaDesc) ? $page->metaDesc : '';
		$this->metaKeywords      = isset($page->metaKey) ? $page->metaKey : '';
		$this->parent            = $page->parent;
		$this->type              = $page->type;
		$this->typeId            = $page->typeId;
		$this->article           = $page->type === 'article' ? (object)['id' => $page->typeId] : null;
		self::$language          = Language::getCurrent();
		$this->showBreadcrumbs   = true;
		$this->breadcrumbs       = [];
			$this->breadcrumbs[0]         = new StdClass();
			$this->breadcrumbs[0]->name   = $this->page;
			$this->breadcrumbs[0]->url    = $this->url;
			$this->breadcrumbs[0]->path   = $this->path;
			$this->breadcrumbs[0]->title  = $this->title;
			$this->breadcrumbs[0]->id     = $this->id;
			$this->breadcrumbs[0]->parent = $this->parent;

		self::$instances[$this->page] = $this;
	}

	/**
	 * Detect the current page.
	 *
	 * @return void.
	 */
	public static function getCurrent()
	{
		if (self::$current) return self::$current;
		if (!count(self::$allPages)) self::fetchAll();

		global $aliases;
		$settings = Settings::get();
		$language = self::$language;

		$allowedLanguages = array_keys(Language::allowedLanguages);
		$pageId   = null;

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

			if (!$path) $pageId = 'home';
			elseif (preg_match('~^('.implode('|', $allowedLanguages).')/?~', $path, $match))
			{
				$language     = $match[1];
				$remainingUrl = str_replace(array("$language/", '.html'), '', $path);

				// Detect if nothing is after the language in the path.
				if ($path === "$language/" || $path === $language) $pageId = 'home';
				elseif (strrpos($path, '.html') !== false) $page = self::getByProperty('url', $remainingUrl, $language);
				elseif (array_key_exists($remainingUrl, $aliases)) $pageId = $aliases[$remainingUrl];
			}

			// If there is a themeRouter() function found in theme/[current_theme]/router.php,
			// Then override current page with the one matched and returned by the themeRouter().
			if (includeFunctionOnce('router', false) && function_exists('themeRouter'))
			{
				$url    = str_replace('.html', '', $_SERVER['REQUEST_URI']);
				$url    = explode('?', $url)[0];
				$return = themeRouter($url);

				// If url is matched in themeRouter().
				if (!empty($return))
				{
					$pageId = $return;
					$page   = self::get($pageId);
					self::set($pageId);
				}
			}
		}
		else
		{
			$urlParts = parse_url($_SERVER['REQUEST_URI']);
			$path     = str_replace('/'.dirname(SELF).'/', '', $urlParts['path']);

			// Get the page from $gets if any.
			$gets     = Userdata::get();
			if (isset($gets->page)) $page = self::getByProperty('page', $gets->page, $language);
			elseif (!$path || $path == 'index.php') $pageId = 'home';
		}

		if ($pageId) self::$current = new self($pageId);
		elseif (!empty($page)) self::$current = new self($page->page);
		else self::$current = new self('not-found');

		return self::$current;
	}

	/**
	 * Get a page by its id.
	 *
	 * @return Page object.
	 */
	public static function get($pageId)
	{
		$pageId = isset(self::$allPages[$pageId]) ? $pageId : 'not-found';

		return isset(self::$instances[$pageId]) ? self::$instances[$pageId] : new self($pageId);
	}

	/**
	 * Force using a certain page as the current page. Useful if page is different than url
	 * via the themeRouter in functions/router.php.
	 *
	 * @return Page object.
	 */
	public static function set($pageId)
	{
		//!\ Update the global var.
		return $GLOBALS['page'] = self::$current = self::get($pageId);
	}

	public static function exists($pageId)
	{
		return isset(self::$allPages[$pageId]);
	}

	/**
	 * Find the wanted page informations from only one property.
	 * The most common way to look for a page is from the 'page' property which is unique simple and in lowercase.
	 *
	 * @param string $property: the property (page/id/path/title) on which to make comparison to get the wanted page
	 * @param string $propertyValue: the page/id/path/title of the wanted page.
	 * @param string $language: the target language for the wanted page.
	 * @return object: the wanted page informations (page/id/path/title).
	 */
	public static function getByProperty($property, $propertyValue, $language = null)
	{
		// global $aliases;
		$pages = self::$allPages;

		if (!$language) $language = Language::getCurrent();

		foreach($pages as $page)
		{
			if (is_object($page->$property) && $page->$property !== 'id'
				&& $page->$property->$language == Userdata::unsecureString($propertyValue)) return self::get($page->page);
			elseif ($page->$property == Userdata::unsecureString($propertyValue)) return self::get($page->page);
		}

		// If not found, look in aliases.
		// @todo: redo the aliases check.
		// if (array_key_exists($propertyValue, $aliases)) return self::get($aliases[$propertyValue], $language);

		// Fallback if the page does not exist: return the 404 page.
		return self::get('not-found');
	}

	/**
	 * Get all the pages from DB retrieve all the pages from the database.
	 *
	 * @return array: the pages onject as they are stored in DB.
	 */
    private static function fetchAll()
	{
		$db = database::getInstance();
		$pagesFromDB = $db->query()->select('pages', '*')->run()->loadObjects('page');

		foreach ($pagesFromDB as $k => $p)
		{
			$pages[$k] = $p;
			// Camel case.
			// $pages[$k]->id = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $p->page))));
			$pages[$k]->id = $p->page;
			foreach ($p as $attr => $val)
			{
				// Look for language-related vars (texts) and convert sth like metaDesc_fr to sth like metaDesc->fr
				// if recognised language.
				if (preg_match('~^([-_a-zA-Z0-9]+)_([a-z]{2})$~', $attr, $matches) && array_key_exists($matches[2], Language::allowedLanguages))
				{
					if (!isset($pages[$k]->{$matches[1]})) $pages[$k]->{$matches[1]} = new StdClass();
					$pages[$k]->{$matches[1]}->{$matches[2]} = $val;
					unset($pages[$k]->{"$matches[1]_$matches[2]"});
				}
			}
		}

		self::$allPages = $pages;
	}

	public static function getAllPages()
	{
		return self::$allPages;
	}

	/**
	 * Set the language.
	 *
	 * @param String $language: the language to display the texts in.
	 */
	public function setLanguage($language)
	{
		self::$language = $language;
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
	 * Disable the effect on the header background.
	 * The effect is enabled by default.
	 */
	public function disableParallax()
	{
		$this->parallax = false;
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
	 * Get the title of the current page.
	 *
	 * @param String $language: an optional language to get the title in. If none provided get the current language title.
	 * @return String: the title of the page.
	 */
	public function getTitle($language = null)
	{
		return $language && Language::isAllowed($language) ? $this->title->{$language}
														   : $this->title->{self::$language};
	}

	/**
	 * Get the url of the current page.
	 *
	 * @param String $language: an optional language to get the title in. If none provided get the current language title.
	 * @return String: the url of the page.
	 */
	public function getUrl($language = null)
	{
		return $language && Language::isAllowed($language) ? $this->url->{$language}
														   : $this->url->{self::$language};
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

	public function setContent($content = '')
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Render the current page.
	 *
	 * @return the HTML of the full page
	 */
	public function render($echo = true)
	{
		global $page, $user;
		$settings = Settings::get();
		$cookies  = Userdata::get('cookie');
		$language = Language::getCurrent();

        includeFunction('do-menu');
		$mainMenu = includeFunction('menu');

		// FINAL RENDER
		$expires = 60*60*24*7;//1 week.
		header("Pragma: public");
		header("Cache-Control: maxage=$expires");
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).' GMT');
		header('Content-Type: text/html; charset=utf-8');
		header('Content-language: '.strtolower($language));

		$tpl = newPageTpl('page');
		$tpl->set_block($page->page, 'h1Block', 'theH1Block');
		$tpl->set_block($page->page, 'googleAnalytics', 'theGoogleAnalytics');


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

		$tpl->set_var(['content'           => $this->content,
					   'ROOT'              => $settings->root,
					   'SELF'              => url('SELF'),
					   'language'          => $language,
					   'pageId'            => $page->id,
					   'pageClass'         => $page->type,
					   'metaDesc'          => $page->metaDescription->$language ? $page->metaDescription->$language : '',
					   'metaKey'           => $page->metaKeywords->$language ? $page->metaKeywords->$language : '',
					   'siteName'          => $settings->siteName,
					   'languageFull'      => Language::getCurrentFull(),
					   'author'            => $settings->author,
					   'siteUrl'           => $settings->siteUrl,
					   'pageTitle'         => htmlentities($page->getTitle(), ENT_NOQUOTES, 'utf-8').$settings->titleSuffix,
					   'socialImgSrc'      => $this->socialImage ? $this->socialImage : url('images/?i='.$settings->logoSrc),
					   'noCrawl', strpos(SELF, 'backstage') !== false ? 'no' : '',
				       'page'              => $this->page,

					   'newsletter'        => $newsletter->render(),

					   'logoSrc'           => url('images/?i='.$settings->logoSrc),
					   'headerImgSrc'      => url('images/?i=home-slides/sailing_xl.jpg'),
					   'footerImgSrc'      => url('images/?i=sunset_l.jpg'),
					   'backToHomeText'    => text(45),
					   'homeUrl'           => url('home'),
					   'homeText'          => self::get('home', $language)->getTitle(),
					   'h1'                => ucfirst($this->h1 ? $this->h1 : $page->getTitle()),
					   // If h1 is explicitly set to null then set an h1 with the site name for SEO.
                       'headerHeight'      => $this->headerHeight,
					   'parallax'          => $this->parallax ? 'parallax' : '',
					   'topZoneContent'    => $this->topZoneContent ? $this->topZoneContent : '',
					   'mainMenu'          => $mainMenu,
					   'bottomZoneContent' => $this->bottomZoneContent ? $this->bottomZoneContent : '',
					   'sitemapTree'       => getTree('sitemap', ['[article]'], ['showIcons' => $settings->mobileMenuIcons]),
					   'articlesListText'  => text('Tous les articles'),
					   'articlesList'      => $this->renderArticlesList(),
					   'strongOrH1'        => $this->h1 === null ? 'h1' : 'strong',
					   'icon'              => $this->icon ? " class=\"$this->icon\"" : '',
					   'breadcrumbs'       => $this->showBreadcrumbs ? "<div id=\"breadcrumbs\">{$this->renderBreadcrumbs()}</div>" : '',
					   'goDownLink'        => $this->headerHeight >= 60 ? "<a href=\"#top\" class=\"go-down i-chevron-d\">".text(99).'</a>' : '',

					   // 'social'       => $this->social ? '<div class="social clearfix"></div>' : '',// Moved inside article only.
					   'contactUrl'        => url('contact'),
					   'contactText'       => self::get('contact', $language)->getTitle(),
					   'classEn'           => $language == 'en' ? ' active' : '',
					   'classFr'           => $language == 'fr' ? ' active' : '',
					   'error'             => Cerror::getCount() && $showErrors ? "<div id=\"error\"><p><span class=\"i-alert\"></span> ERROR</p>".Cerror::show()."</div>" : '',
					   'debug'             => Debug::getInstance()->getCount() && $showErrors ? "<div id=\"debug\"><p><span class=\"i-bug\"></span> DEBUG </p>".Debug::getInstance()->show()."</div>" : '',
					   'headerMessage'     => ($headerMessage = Message::show('header')) ? "<div id=\"header-message\">$headerMessage</div>" : '',
					   'contentMessage'    => ($contentMessage = Message::show('content')) ? "<div id=\"content-message\">$contentMessage</div>" : '',
					   'copyright'         => textf(19, $settings->siteName, date('Y')),
					   'sitemapUrl'        => url('sitemap'),
					   'sitemapText'       => self::get('sitemap', $language)->getTitle(),
					   'legalTermsUrl'     => url('legalTerms'),
					   'legalTermsText'    => self::get('legalTerms', $language)->getTitle()
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

		$html = $tpl->parse('display', $page->page);
		if ($echo) echo $html;

		return $html;
	}

	/**
	 * Toggle the visibility of the breadrcumbs.
	 *
	 * @param boolean $bool: visible if true, hidden and not processed if false from beginning.
	 */
	public function setBreadcrumbsVisiblity($bool)
	{
		$this->showBreadcrumbs = $bool;
		return $this;
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
			$matchedPage = Page::get($page->parent, self::$language);
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
	public function renderBreadcrumbs()
	{
		$output = '';
		foreach ($this->breadcrumbs as $k => $page)
		{
			$title = ucfirst($page->title->{self::$language});
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