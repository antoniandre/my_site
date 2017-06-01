<?php
//===================== CONSTANTS =====================//
// Constants in minicore.php.
//=====================================================//


//======================= INCLUDES ====================//
require ROOT.'backstage/functions/minicore.php';// Minimum required core. (the min for js/, css/, images/).

includeClass('debug');

includeClass('utility');
includeClass('message');
includeClass('form');
includeClass('page');
includeClass('language');
includeClass('database');
includeClass('text');
includeClass('encryption');
includeClass('article');
includeFunction('sitemap');

include ROOT.'backstage/libraries/template.inc';
//=====================================================//


//======================= VARS ========================//
//=====================================================//


//======================================================================================================//
//=============================================== MAIN =================================================//
$language = Language::getCurrent();
$pages = getPagesFromDB();
$aliases = getPagesAlias($pages);
$page = Page::getInstance();
$page->setLanguage($language);
if (Language::getTarget()) $page->refresh();

// todo: write a Cache class.
/*if ($settings->useCache && !Userdata::is_set('post'))
{
    include(ROOT."backstage/cache/$page->path$page->page.html");
}*/
if (isset(UserData::get()->js)) include ROOT.'js/index.php';
if (isset(UserData::get()->css)) include ROOT.'css/index.php';
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * getPagesFromDB retrieve all the pages tables from the database.
 *
 * @return array: the pages onject as they are stored in DB.
 */
function getPagesFromDB()
{
    $db = database::getInstance();
    $q = $db->query();
    $pagesFromDB = $q->select('pages', '*')->run()->loadObjects('page');
    foreach ($pagesFromDB as $k => $p)
    {
        $pages[$k] = $p;
        $pages[$k]->id = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $p->page))));
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
    return $pages;
}

/**
 * find the wanted page informations from only one property.
 * The most common way to look for a page is from the 'page' property which is unique simple and in lowercase.
 *
 * @param string $property: the property (page/id/path/title) on which to make comparison to get the wanted page
 * @param string $propertyValue: the page/id/path/title of the wanted page.
 * @param string $language: the target language for the wanted page.
 * @return object: the wanted page informations (page/id/path/title).
 */
function getPageByProperty($property, $propertyValue, $language = null)
{
    global $pages, $aliases;

    if (!$language) $language = Language::getCurrent();

    foreach($pages as $page)
    {
        if (is_object($page->$property) && $page->$property !== 'id' && $page->$property->$language == Userdata::unsecureString($propertyValue)) return $page;
        elseif ($page->$property == Userdata::unsecureString($propertyValue)) return $page;
    }

    // If not found, look in aliases
    if (array_key_exists($propertyValue, $aliases)) return getPageByProperty('id', $aliases[$propertyValue], $language);

    // Fallback if the page does not exist: return the 404 page
    foreach($pages as $page) if ($page->id == 'notFound') return $page;
}

/**
 * Get the available page aliases for the current language.
 *
 * @return array of aliases made of '$page->alias => $page->id' pairs.
 */
function getPagesAlias($pages)
{
    $aliases = array();
    foreach($pages as $page) if (isset($page->alias)) $aliases[$page->alias] = $page->id;
    return $aliases;
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
    $settings = Settings::get();
    $gets     = Userdata::get();
    $language = isset($data['language']) && Language::exists($data['language']) ? $data['language']
                                                                                : Language::getCurrent();
    unset($data['language']);
    $root = $fullUrl ? "$settings->siteUrl/" : $settings->root;

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
            // Get the wanted page from the $pages array via getPageByProperty() and set the url from the retrieved page object.
            $matchedPage = getPageByProperty('page', str_replace('.php', '', $urlObj->path), $language);
            $data['page'] = $matchedPage->page;
        }
    }
    //---------------------------------------------------------------//

    //----------------------- Rewrite Engine ON ---------------------//
    elseif ($settings->rewriteEngine)
    {
        if (strtolower($urlObj->path) == 'self') $urlPath = "$root$language/{$matchedPage->url->$language}.html";

        // Get the wanted page from the $pages array via getPageByProperty() and set the url from the retrieved page object.
        else $matchedPage = getPageByProperty('page', str_replace('.php', '', $urlObj->path), $language);

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
 *             'contexts' => [],   Array: The multiple contexts to look into.
 *             'formats' => [],   Array: An array of format-params pairs to apply to the string, among: htmlentities=>true/false, sprintf=>[params,...], sef=>true/false.
 *             'languages' => [], Array: the array of languages codes you want to retrieve the text in.
 *                                Allowed languages are set in Language class.
 *                                Defaults to the current language only if none is provided.
 *         ]
 * @return string
 */
function text($id, $parameters = ['htmlentities' => 1, 'contexts'=> [], 'languages'=> []])
{
    $text = new Text($id, $parameters);

    if (isset($parameters['formats'])) $text->format($parameters['formats']);
    return $text->get();
}

/**
 * Shorthand function to retrieve a text from DB and apply a sprintf on it.
 *
 * @param string/int [first param]: The id of the text to retrieve from DB or a string to apply sprintf on.
 * @param mixed [any following param]: A param to provide to the sprintf function.
 * @return string: the sprintf-formated string.
 */
function textf()
{
    $parameters = func_get_args();
    unset($parameters[0]);
    return text(func_get_arg(0), ['formats' => ['sprintf' => $parameters]]);
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
    // 'Apply' concept: apply the arguments 'func_get_args()' to the method 'add' of the object 'Debug::getInstance()'
    // Doing only 'Debug::getInstance()->add(func_get_args())' would wrap the args into an array...
    call_user_func_array(array(Debug::getInstance(), 'add'), func_get_args());
    Debug::getInstance()->setLevel3caller();
}

/**
 * Shorthand function to create a debug message and then die.
 *
 * @param mixed [any param]: A param to explore with the debug function.
 * @return void
 */
function dbgd()
{
    // 'Apply' concept: apply the arguments 'func_get_args()' to the method 'add' of the object 'Debug::getInstance()'
    // Doing only 'Debug::getInstance()->add(func_get_args())' would wrap the args into an array...
    call_user_func_array(array(Debug::getInstance(), 'add'), func_get_args());
    die(Debug::getInstance()->setLevel3caller()->show());
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>