<?php
//===================== CONSTANTS =====================//
// Constants in minicore.php.
//=====================================================//


//======================= INCLUDES ====================//
// Minimum required core. (the min for js/, css/, images/).
require __DIR__.'/minicore.php';

includeClass('debug');
includeClass('page');
includeClass('language');
includeClass('database');
//=====================================================//


//======================================================================================================//
//=============================================== MAIN =================================================//
$language = Language::getCurrent();
$page     = Page::getCurrent();

// If required file is JS or CSS or Font, include it and die.
if (isJS())                  include ROOT . 'kernel/js/index.php';
elseif (isCss() || isFont()) include ROOT . 'kernel/css/index.php';
else
{
    if (Language::getTarget()) $page->refresh();

    includeClass('message');
    includeClass('utility');
    includeClass('form');
    includeClass('text');
    includeClass('encryption');
    includeClass('article');
    includeFunction('sitemap');
    includeClass('template');
}
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function mainRouter()
{
	$page = Page::getCurrent();

	// Webservice call from Localhost. We are now on distant side.
	// So run the code to emit and return to localhost.
	if (strpos($_SERVER['QUERY_STRING'], 'ws='))
	{
		includeClass('webservice');
		new Webservice();
	}

    // Following code is similar to this commented code (for article case) but extended to any type of page.
    //     elseif ($page->isArticle())
	//     {
	//     	   $article     = Page::get('article');
	//     	   $includePath = checkInTheme("kernel/$article->path$article->page.php");
	//     }
	elseif ($page->type !== 'page')
	{
		$type        = Page::get($page->type);
		$includePath = checkInTheme("kernel/$type->path$type->page.php");
	}

	else// All the other pages (ending with .php).
	{
        $pageIsNotFound    = false;
		$includePathKernel = "kernel/$page->path$page->page.php";
		$includePath       = checkInTheme($includePathKernel);

		// Fallback to 'not-found' if no matching page was found in theme.
		if ($includePath === $includePathKernel && !is_file(ROOT . $includePathKernel)) $pageIsNotFound = true;

        // If the requested page is in the backstage but the user is not an admin, pretend the page is not found.
        if ($page->isBackstage())
        {
            $user = User::getCurrent();
            if (!$user->isAdmin()) $pageIsNotFound = true;
        }

        if ($pageIsNotFound)
        {
			$page        = Page::set('not-found');
			$includePath = "kernel/$page->path$page->page.php";

			// Also check if the not-found page has an override.
			$includePath = checkInTheme($includePath);
		}
	}

    return $includePath;
}

function isJs()
{
    return isset(UserData::get()->js);
}

function isCss()
{
    return isset(UserData::get()->css);
}

function isFont()
{
    return isset(UserData::get()->font);
}

function newPageTpl($tplName = '')
{
    $page    = Page::getCurrent();
    $tpl     = new Template();
    $tplName = $tplName ? $tplName : $page->page;
	$tpl->set_file($page->page, checkInTheme(ROOT . "kernel/backstage/templates/$tplName.html"));

    return $tpl;
}

/**
 * Fetch the article from db and return the full row.
 * @param integer $articleId: the article id.
 * @return object: the article row from db.
 */
function getArticleInfo($articleId)
{
    $return = false;
    if (is_numeric($articleId))
    {
        $db = database::getInstance();
        $q = $db->query()
                ->select('articles', '*');
        $w = $q->where();
        $w->col('id')->eq($articleId);
        $return = $q->run()->loadObject();
    }
    return $return;
}

/**
 * Calculate the correct route of a URL.
 *
 * @param string $url: accept a full URL (http://), a page name like 'home.php' or 'backstage.php' ("$page->page.php"), or case insensitive 'self'
 * @param array $data: some data to add in the URL. If rewriteEngine is on, the added data may be recognized to change the URL path accordingly
 *                     urlPath is before '?' and $data is after.
 * @param boolean $fullUrl: set to true to force the use of distant URL. Useful for mails. Default to false.
 */
function url($url, $data = [], $fullUrl = false)
{
    global $page;
    $settings    = Settings::get();
    $gets        = Userdata::get();
    $language    = isset($data['language']) && Language::exists($data['language']) ? $data['language']
                                                                                : Language::getCurrent();
    unset($data['language']);
    $root        = $fullUrl ? "$settings->siteUrl/" : $settings->root;

    // As $page is global, create another var $matchedPage to not overwrite $page.
    $matchedPage = $page;

    // First separate the URL into semantic pieces and create the $urlObj object.
    $urlParts    = parse_url($url);

    $urlObj =
    [
        'scheme'   => null,
        'host'     => null,
        'port'     => null,
        'path'     => null,
        'query'    => null,
        'fragment' => null
    ];
    $urlObj = (object)array_merge($urlObj, $urlParts);

    //------------------------- query string ------------------------//
    // Convert a given query string into indexed array.
    // E.g. 'js=1&article=1' becomes ['js' => 1, 'article' => 1].
    if (is_string($data)) parse_str($data, $data);

    if ($urlObj->query)
    {
        // Convert query string into indexed array.
        parse_str($urlObj->query, $urlData);

        // Merge arrays to reinject data provided in parameter in the $data array.
        // Note: array_merge overwrites $urlData with $data in case of common key.
        $data = array_merge($urlData, $data);
    }
    //---------------------------------------------------------------//


    //-------------------------- full url ---------------------------//
    if ($urlObj->scheme)
    {
        $urlPath = "$urlObj->scheme://$urlObj->host/" . ($urlObj->port ? ":$urlObj->port" : '')
                 . ($urlObj->path ? "/$urlObj->path" : '');
    }
    //---------------------------------------------------------------//

    //---------------------------- images ---------------------------//
    if (strpos($urlObj->path, 'images/') === 0)// Found at the beginning.
    {
        $urlPath = $root.$urlObj->path;
    }
    //---------------------------------------------------------------//

    //---------------------- Rewrite Engine OFF ---------------------//
    elseif (!$settings->rewriteEngine)
    {
        $urlPath = $root;
        if (!isset($data['lang'])) $data['lang'] = $language;
        if (strtolower($urlObj->path) === 'self') $data['page'] = $matchedPage->page;
        else
        {
            // Get the wanted page from the $pages array via Page::getByProperty() and set the url from the retrieved page object.
            $matchedPage = Page::getByProperty('page', str_replace('.php', '', $urlObj->path), $language);
            $data['page'] = $matchedPage->page;
        }
    }
    //---------------------------------------------------------------//

    //----------------------- Rewrite Engine ON ---------------------//
    elseif ($settings->rewriteEngine)
    {
        if (strtolower($urlObj->path) == 'self') $urlPath = "$root$language/{$matchedPage->url->$language}.html";

        // Get the wanted page from the $pages array via Page::getByProperty() and set the url from the retrieved page object.
        else $matchedPage = Page::getByProperty('page', str_replace('.php', '', $urlObj->path), $language);

        list($matchedPage->url->$language, $seoData) = seo($matchedPage->url->$language, $data, $language);
        $data = array_merge($seoData, $data);
        $urlPath = "$root$language/{$matchedPage->url->$language}.html";
    }
    //---------------------------------------------------------------//

    return $urlPath
          . (count($data)? '?'.http_build_query($data/*, '', '&amp;'*/) : '')
          . ($urlObj->fragment ? "#$urlObj->fragment" : '');
}

/**
 * If $settings->rewriteEngine is on, perform a replacement of a few vars.
 * Used by the url() function.
 *
 * @param string $url: the url to check.
 * @param array $data: the array of data to check.
 * @param string $language: the language to check the url for.
 * @return array.
 */
function seo($url, $data, $language)
{
    unset($data['lang']);
    return [$url, $data];
}

/**
 * Redirect to a given page using php header location.
 *
 * @todo: finish functions  to treat http codes in a switch.
 * @param string $url: the url you want to redirect to. Use url identidiers.
 *                     @see: function url().
 * @param integer $httpCode: an optional http code to return to the browser.
 * @return void.
 */
function redirectTo($url, $httpCode = 200)
{
    // header('HTTP/1.1 503 Service Temporarily Unavailable', true);
    header('Location: '.url($url));
    exit;
}

/**
 * Shorthand function to retrieve a text from DB.
 *
 * @param string/int $id: The id of the text to retrieve from DB or a string to apply text functions on.
 * @param array $parameters: an array of options to perform extra tasks on string if any:
 *         [
 *             'contexts'  => [], Array: The multiple contexts to look into.
 *             'formats'   => [], Array: An array of format-params pairs to apply to the string, among: htmlentities=>true/false, sprintf=>[params,...], sef=>true/false.
 *             'languages' => [], Array: the array of languages codes you want to retrieve the text in.
 *                                Allowed languages are set in Language class.
 *                                Defaults to the current language only if none is provided.
 *         ]
 * @return string
 */
 function text($id, $parameters = ['htmlentities' => 1, 'contexts' => [], 'languages' => []])
 {
     if (is_string($id) && !is_numeric($id)) $text = Text::_use($id);
     else {$text = Text::get($id);}
     if (isset($parameters['formats']) && is_object($text)) $text->format($parameters['formats']);

     return is_object($text) ? $text->toString() : '';
 }

/**
 * Shorthand function to retrieve a text from DB and apply a sprintf on it.
 *
 * @param string/int [first param]: The id of the text to retrieve from DB or a string to apply sprintf on.
 * @param mixed [any following param]: A param to provide to the sprintf function.
 * @return string: the sprintf-formated string.
 */
function textf($text)
{
    $parameters = func_get_args();
    unset($parameters[0]);
    return text($text, ['formats' => ['sprintf' => $parameters]]);
}
function textu($text)
{
    return text($text, ['formats' => 'url']);
}
function textsa($text)
{
    return text($text, ['formats' => 'stripaccents']);
}

/**
 * Perform a callback function Foreach available language.
 *
 * @param Function $callback: the callback function to execute for each known language.
 * @return void
 *
 * Example of use:
 * foreachLang(function($lang, $fullLang)
 * {
 *     echo " $lang=$fullLang!";
 * });
 * Or:
 * foreachLang(function($lang)
 * {
 *     echo " $lang!";
 * });
 */
function foreachLang($callback)
{
    $return = null;

    if (is_callable($callback)) foreach (Language::allowedLanguages as $lang => $fullLang)
    {
        $return = $callback($lang, $fullLang);
    }

    return $return;
}

/**
 * Returns information on the caller of the function.
 *
 * @return array: the debug trace.
 */
function getCaller()
{
    $caller = debug_backtrace(false);
    return $caller[1];
}

/**
 * Shorthand function to create a debug message.
 *
 * @param mixed [any param]: A param to explore with the debug function.
 * @return void
 */
function dbg()
{
    if (!class_exists('Debug')) return;
    // 'Apply' concept: apply the arguments 'func_get_args()' to the method 'add' of the object 'Debug::getInstance()'
    // Doing only 'Debug::getInstance()->add(func_get_args())' would wrap the args into an array...
    call_user_func_array(array(Debug::getInstance(), 'add'), func_get_args());
    Debug::getInstance()->setLevel2caller();
}

/**
 * Shorthand function to create a debug message and then die.
 *
 * @param mixed [any param]: A param to explore with the debug function.
 * @return void
 */
function dbgd()
{
    if (!class_exists('Debug'))
    {
        $trace     = debug_backtrace()[0];
        $fileLines = file($trace['file']);
        $comment   = isJS() || isCss() || isFont() ? ['/*', '*/'] : ['<!--', '-->'];

        echo "$comment[0]\n"
           . "'$trace[function]()' Class Debug not available.\nCalled at line $trace[line] in file $trace[file]:\n"
           . implode("", array_slice($fileLines, $trace['line'] - 5, 10))
           . "\n$comment[1]";

        return;
    }
    // 'Apply' concept: apply the arguments 'func_get_args()' to the method 'add' of the object 'Debug::getInstance()'
    // Doing only 'Debug::getInstance()->add(func_get_args())' would wrap the args into an array...
    call_user_func_array(array(Debug::getInstance(), 'add'), func_get_args());
    die(Debug::getInstance()->setLevel2caller()->show());
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>