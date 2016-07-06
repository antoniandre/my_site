<?php
/**
 * Minimum required core.
 * The minimum for js/, css/, images/.
 * /!\ WARNING: the debug class is not loaded here. So you won't be abble to use dbg() or dbgd().
 */

//===================== CONSTANTS =====================//
define('IS_LOCAL', $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.0.33');// desktop localhost or iphone access to localhost
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
ob_start(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'ob_gzhandler' : null);

// First of all, set the error handler:
// First use of the Error class triggers the singleton instanciation and sets the error handler.
$error = Error::getInstance();

// By default, the secured vars are converted to objects and they do not allow HTML.
UserData::getInstance();
$user = User::getInstance();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * Shortcut function to simply include a php class.
 *
 * @param string $class: the php class to include.
 * @return void.
 */
function includeClass($class)
{
    if (!include ROOT."backstage/classes/$class.php")
        Error::add("The class '$class' was not found in '".ROOT."backstage/classes/$class.php'.", 'NOT FOUND');
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

            if (Error::getCount()) $object->message .= "\n\nPHP SAID:\n" . Error::get();
            die(json_encode($object));
        }
    }
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>