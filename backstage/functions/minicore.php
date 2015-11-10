<?php
//===================== CONSTANTS =====================//
define('IS_LOCAL', $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.0.33');// desktop localhost or iphone access to localhost
define('SELF', $_SERVER['PHP_SELF']{0} == '/' ? substr($_SERVER['PHP_SELF'], 1) : $_SERVER['PHP_SELF']);
$qs = $_SERVER['QUERY_STRING'];
define('QUERY_STRING', @$qs{0} == '&' ? substr($qs, 1) : $qs/*preventing pb*/);
define('URI', QUERY_STRING ? SELF.'?'.QUERY_STRING : SELF);
//=====================================================//


//======================= INCLUDES ====================//
include ROOT.'backstage/classes/error.php';
include ROOT.'backstage/classes/settings.php';
include ROOT.'backstage/classes/userdata.php';
include ROOT.'backstage/classes/user.php';
//=====================================================//


//======================= VARS ========================//
//=====================================================//


//======================================================================================================//
//=============================================== MAIN =================================================//
ob_start(substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'ob_gzhandler' : null);

// First of all, set the error handler!
//if ($user->isAdmin())
error_reporting(E_ALL);//display errors only for admin (for remote site)
$error = Error::getInstance()->errorHandler();

// By default, the secured vars are converted to objects and they do not allow HTML.
UserData::getInstance();
$user = User::getInstance();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * handle the user posted data.
 *
 * @param  callable $callback: a function to execute when user has posted some data.
 * @return void.
 */
function handlePosts($callback)
{
    if (is_callable($callback) && (Userdata::is_set('post'))) $callback();
}

/**
 * Handle ajax requests.
 *
 * @param  callable $callback: a function to execute when an ajax request is made.
 * @return String: a json string to send back to JS.
 */
function handleAjax($callback)
{
    if (Userdata::isAjax() && is_callable($callback))
    {
        $object = $callback();
        header('Content-Type: application/json;charset=utf-8');
        die(json_encode($object));

    }
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>