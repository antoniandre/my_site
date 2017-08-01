<?php
//======================= VARS ========================//
define('ROOT', __DIR__.'/');

//===================== INCLUDES ======================//
include 'kernel/backstage/functions/core.php';


//============================================= MAIN ===================================================//
// @todo: write a Cache class.
/*if ($settings->useCache && !Userdata::is_set('post'))
{
    include(ROOT."kernel/backstage/cache/$page->path$page->page.html");
}*/


include mainRouter();
?>