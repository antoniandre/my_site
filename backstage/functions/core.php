<?php
//===================== CONSTANTS =====================//
define('IS_LOCAL', $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.0.33');// desktop localhost or iphone access to localhost
//ob_start(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')? 'ob_gzhandler' : null);
define('SELF', $_SERVER['PHP_SELF']{0} == '/' ? substr($_SERVER['PHP_SELF'], 1) : $_SERVER['PHP_SELF']);
$qs = $_SERVER['QUERY_STRING'];
define('QUERY_STRING', @$qs{0} == '&' ? substr($qs, 1) : $qs/*preventing pb*/);
define('URI', QUERY_STRING ? SELF.'?'.QUERY_STRING : SELF);
define('CONFIG_DIR', __DIR__.'/../config');
define('CONFIG_FILE', 'config.ini');
//=====================================================//


//======================= INCLUDES ====================//
include __DIR__.'/../classes/error.php';
include __DIR__.'/../classes/debug.php';
include __DIR__.'/../classes/userdata.php';
include __DIR__.'/../classes/utility.php';
include __DIR__.'/../classes/message.php';
include __DIR__.'/../classes/page.php';
include __DIR__.'/../classes/language.php';
include __DIR__.'/../classes/database.php';
include __DIR__.'/../classes/text.php';
include __DIR__.'/../classes/encryption.php';
include __DIR__.'/../classes/user.php';
include __DIR__.'/../libraries/template.inc';
//=====================================================//


//======================= VARS ========================//
//=====================================================//


//======================================================================================================//
//=============================================== MAIN =================================================//
// First of all, set the error handler!
//if ($user->isAdmin())
error_reporting(E_ALL);//display errors only for admin (for remote site)
$error = Error::getInstance()->errorHandler();

// By default, the secured vars are converted to objects and they do not allow HTML.
UserData::getInstance();
$posts = UserData::$post;
$gets = UserData::$get;
$cookies = UserData::$cookie;
$settings = fetchSettings();
$languageObject = Language::getInstance();
$language = $languageObject->getCurrent();
$pages = getPagesFromDB();
$aliases = getPagesAlias($pages);
$page = Page::getInstance();
$page->setLanguage($language);
if ($languageObject->target) $page->refresh();

$db = database::getInstance();
$user = User::getInstance();
// TODO: remove following query trials:
/*$where = $db->query()->where("column_name1 ='value1'");
$where->and("column_name2='value2'")
      ->or("column_name3 LIKE 'value3'", $where->or("column_name4<=4"), $where->or("column_name5<=5"), $where->or("column_name6<=6"))
      ->and("column_name7='value7'", $where->or("column_name8<=8", $where->and("column_name9='value9'", $where->and($where->in("column_name10", array(1,2,3,4,5))))))
      ->and($where->concat("column_name1","column_name2"));
dbg($where);*/
/*$q = $db->query();
$q->select('pages', '*')->where()->col('page')->in("sitemap", "home");

$q->run();
dbg($q->info(), $q->loadObjects());*/
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * Fetch all the settings from the ini files in CONFIG_DIR and prepare some vars.
 * First get config from CONFIG_FILE and then overwrite with any other .ini file found in CONFIG_DIR.
 * Handles links (shortcuts) to .ini files elsewhere (for FAT32 partitions that don't handle symlinks), and MAC OSX symlinks.
 * Used to handle multiple dev environments with specific configs.
 * The practice is to put a lnk/symlink -- in CONFIG_DIR --  to a .ini file on the machine.
 */
function fetchSettings()
{
    // First retrieve global settings.
    $settings = parse_ini_file(CONFIG_DIR.'/'.CONFIG_FILE/*, process_sections= true*/);

    // Then retrieve possible env-specific ini files -- in CONFIG_DIR -- to overwrite vars in global settings.
    foreach(scandir(CONFIG_DIR) as $iniFile)
    {
        // only take care of .ini and .ini.lnk
        if ((strpos($iniFile, '.ini') !== false || strpos($iniFile, '.ini.lnk') !== false) && $iniFile !== CONFIG_FILE)
        {
            $iniFile = CONFIG_DIR."/$iniFile";
            if (strpos($iniFile, '.ini.lnk') !== false)
            {
                // Get the content of the .lnk to extract the real target. readlink() only work for symlinks.
                $lnkData = file_get_contents($iniFile);
                $target = preg_replace('@^.*\00([A-Z]:)(?:\\\\.*?\\\\\\\\.*?\00|[\00\\\\])(.*?)\00.*$@s', '$1\\\\$2', $lnkData);
                if (!is_file($target)) continue;
                $iniFile = $target;
            }
            // Overwrite the $settings array with specific configs if any.
            $settings = array_merge($settings, parse_ini_file($iniFile/*, process_sections = true*/));
        }
    }

    // Convert to object.
    $settings = (object)$settings;

    // This var has to be used in templates when the rewrite engine is ON.
    $settings->root = (IS_LOCAL? $settings->root_local : $settings->siteurl).'/';
    $settings->rewriteEngine = isset($settings->rewriteEngine) && $settings->rewriteEngine;
    $_SERVER['SERVER_ADMIN'] = $settings->serverAdmin;
    return $settings;
}

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
            // Convert sth like metaDesc_fr to sth like metaDesc->fr.
            if (preg_match('~^([-_a-zA-Z0-9]+)_([a-z]{2})$~', $attr, $matches) && array_key_exists($matches[2], Language::allowedLanguages))
            {
                if (!isset($pages[$k]->$matches[1])) $pages[$k]->$matches[1] = new StdClass();
                $pages[$k]->$matches[1]->$matches[2] = $val;
                unset($pages[$k]->{"$matches[1]_$matches[2]"});
            }
        }
    }
    return $pages;
}

/**
 * find the wanted page informations from only one property
 *
 * @param string $property: the property (page/id/path/title) on which to make comparison to get the wanted page
 * @param string $propertyValue: the page/id/path/title of the wanted page
 * @param string $language: the target language for the wanted page
 * @return object: the wanted page informations (page/id/path/title)
 */
function getPageByProperty($property, $propertyValue, $language = null)
{
    global $pages, $aliases;

    if (!$language) $language = Language::getInstance()->getCurrent();

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
    $aliases= array();
    foreach($pages as $page) if (isset($page->alias)) $aliases[$page->alias] = $page->id;
    return $aliases;
}


/**
 * Calculate the correct route of a URL.
 *
 * @param string $url: accept a full URL (http://), a page name like 'home.php' or 'backstage.php' ("$page->page.php"), or case insensitive 'self'
 * @param array $data: some data to add in the URL. If rewriteEngine is on, the added data may be recognized to change the URL path accordingly
 * urlPath is before '?' and $data is after
 */
function url($url, $data = array())
{
    global $language, $settings, $gets, $page;
    // As $page is global, create another var $matchedPage to not overwrite $page
    $matchedPage = $page;

    // First get URL vars and store in $urlData.
    $urlParts = parse_url($url);
    $queryString = isset($urlParts['query'])? $urlParts['query'] : '';
    $urlData = array();// Useful if !$queryString.
    if ($queryString) parse_str($queryString, $urlData);// Put every url var in $data array

    // Merge arrays to reinject data provided in parameter in the $data array.
    // Note: array_merge overwrites $urlData with $data in case of common key.
    $data = array_merge($urlData, $data);

    //-------------------------- full url ---------------------------//
    $pos = strpos($url, 'http://');
    if ($pos !== false && !$pos)// Found at the beginning
    {
        $urlPath= $urlParts['path'];
    }
    //---------------------------------------------------------------//

    //---------------------------- images ---------------------------//
    $pos = strpos($url, 'images/');
    if ($pos !== false && !$pos)// Found at the beginning
    {
        $urlPath = $settings->root.$urlParts['path'];
    }
    //---------------------------------------------------------------//

    //---------------------- Rewrite Engine OFF ---------------------//
    elseif (!$settings->rewriteEngine)
    {
        $urlPath = $settings->root;
        if (!isset($data['lang'])) $data['lang'] = $language;
        if (strtolower($urlParts['path']) == 'self') $data['page'] = $matchedPage->page;
        else
        {
            $basename = str_replace('.php', '', $url);
            // Access to the wanted page from the $pages array via getPageByProperty() and set the url from the retrieved page object.
            $matchedPage = getPageByProperty('page', $basename, $language);
            $data['page'] = $matchedPage->page;
        }
    }
    //---------------------------------------------------------------//

    //----------------------- Rewrite Engine ON ---------------------//
    elseif ($settings->rewriteEngine)
    {
        if (strtolower($urlParts['path']) == 'self') $urlPath = "$settings->root$language/{$matchedPage->url->$language}.html";
        else
        {
            $basename = str_replace('.php', '', $url);
            // Access the wanted page from the $pages array via getPageByProperty() and set the url from the retrieved page object.
            $matchedPage = getPageByProperty('page', $basename, $language);
        }
        list($matchedPage->url->$language, $data) = seo($matchedPage->url->$language, $data, $language);
        $urlPath = "$settings->root$language/{$matchedPage->url->$language}.html";
    }
    //---------------------------------------------------------------//

    return $urlPath.(count($data)? '?'.http_build_query($data/*, '', '&amp;'*/) : '');
}

function seo($url, $data, $language)
{
    unset($data['lang']);
    return [$url, $data];
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