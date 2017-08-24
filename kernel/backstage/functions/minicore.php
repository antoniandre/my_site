<?php
/**
 * Minimum required core.
 * The minimum for js/, css/, images/.
 * /!\ WARNING: the debug class is not loaded here. So you won't be abble to use dbg() or dbgd().
 */

//===================== CONSTANTS =====================//
define('SELF', $_SERVER['PHP_SELF']{0} == '/' ? substr($_SERVER['PHP_SELF'], 1) : $_SERVER['PHP_SELF']);
$qs = $_SERVER['QUERY_STRING'];
define('QUERY_STRING', @$qs{0} == '&' ? substr($qs, 1) : $qs/*preventing pb*/);
define('URI', QUERY_STRING ? SELF.'?'.QUERY_STRING : SELF);
//=====================================================//


//======================= INCLUDES ====================//
includeClass('error');
includeClass('settings');
includeClass('userdata');
includeClass('user');
//=====================================================//


//======================= VARS ========================//
//=====================================================//


//======================================================================================================//
//=============================================== MAIN =================================================//
ob_start(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'ob_gzhandler' : null);

// First of all, set the error handler:
// First use of the Error class triggers the singleton instanciation and sets the error handler.
$error = Cerror::getInstance();

// By default, the secured vars are converted to objects and they do not allow HTML.
UserData::getInstance();
$user = User::getInstance();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function checkInTheme($path)
{
    // When including the Settings class, the Settings class itself is not yet known.
    $settings  = class_exists('Settings') ? Settings::get() : null;
    if (!$settings) return $path;

    $theme     = $settings->theme;
    $themePath = str_replace(['kernel/backstage/', 'kernel/'], "themes/$theme/", $path);

    return is_file($themePath) ? $themePath : $path;
}
/**
 * Shortcut function to simply include a php class.
 *
 * @param string $class: the php class to include.
 * @return void.
 */
function includeClass($class)
{
    $ok = is_file($file = checkInTheme(ROOT."kernel/backstage/classes/$class.php"));

    if ($ok) include $file;
    else Cerror::add("The class '$class' was not found in '$file'.", 'NOT FOUND');

    return $ok;
}

/**
 * Shortcut function to simply include a php function.
 *
 * @param string $function: the php function to include.
 * @return void.
 */
function includeFunction($function, $haltOnError = true)
{
    $ok = is_file($file = checkInTheme(ROOT."kernel/backstage/functions/$function.php"));

    if ($ok) include $file;
    elseif ($haltOnError) Cerror::add("The function '$function' was not found in '$file'.", 'NOT FOUND');

    return $ok;
}


/**
 * Shortcut function to simply include a php web service.
 *
 * @param string $ws: the php web service to include.
 * @return void.
 */
function includeWebservice($ws)
{
    $ok = is_file($file = checkInTheme(ROOT."kernel/backstage/webservices/$ws.php"));

    if ($ok) include $file;
    else Cerror::add("The web service '$ws' was not found in '$file'.", 'NOT FOUND');

    return $ok;
}
function includeOnceWebservice($ws)
{
    $ok = is_file($file = checkInTheme(ROOT."kernel/backstage/webservices/$ws.php"));

    if ($ok) include_once $file;
    else Cerror::add("The web service '$ws' was not found in '$file", 'NOT FOUND');

    return $ok;
}

/**
 * handle the user posted data.
 *
 * @param callable $callback: a function to execute when user has posted some data.
 * @return void.
 */
function handlePosts($callback)
{
    if (is_callable($callback) && (Userdata::is_set('post'))) $callback();
}

/**
 * Handle ajax requests.
 * /!\ Can only be called once per page load if the callback returns not null - since there is a die.
 *
 * Ex.
 * handleAjax(function()
 * {
 *     $articles = Article::getMultiple(['limit'       => [12, 100],
 *     								     'fetchStatus' => ['coming soon', 'published'],
 *     								     'fetchTags'   => true]);
 *     return ["html" => renderArticles($articles)];
 * });
 *
 * @param  callable $callback: a function to execute when an ajax request is made.
 *                             The callback function must return an object or an indexed array to send back to JS.
 * @return String: a json string to send back to JS.
 */
function handleAjax($callback)
{
    if (Userdata::isAjax() && is_callable($callback))
    {
        $object = $callback();

        // If $object is null no task has been performed so don't die to let a possible other AJAX handler treat unmatched task.
        if ($object)
        {
            $object = (object)$object;// Cast an acceptable indexed array to object.
            header('Content-Type: application/json;charset=utf-8');

            if (Cerror::getCount()) $object->message .= "\n\nPHP SAID:\n" . Cerror::get();
            die(json_encode($object));
        }
    }
}

function updateAjaxProgress($progress)
{
    session_start();
    Userdata::setSession('ajaxProgressUpdate', round($progress, 2));
    session_write_close();
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>