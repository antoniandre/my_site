<?php
//======================= VARS ========================//
define('ROOT', __DIR__.'/../');
//=====================================================//

//===================== INCLUDES ======================//
include ROOT.'backstage/functions/core.php';
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
//dbg($_SERVER);
include(ROOT."$page->path$page->page.php");
echo Page::getInstance()->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>