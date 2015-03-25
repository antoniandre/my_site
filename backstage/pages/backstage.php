<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//

//======================================================================================================//
//============================================= MAIN ===================================================//
$tpl = new Template('.');
$tpl->set_file("$page->page-page", "templates/$page->page.html");
$tpl->set_var('h2', text(43));
$tpl->set_var('createNewPageUrl', url('create-new-page'));
$tpl->set_var('createNewPageText', getPageByProperty('page', 'create-new-page')->title->$language);
$tpl->set_var('createNewTextUrl', url('create-new-text'));
$tpl->set_var('createNewTextText', getPageByProperty('page', 'create-new-text')->title->$language);
$tpl->set_var('toDoListUrl', url('todo-list'));
$tpl->set_var('toDoListText', getPageByProperty('page', 'todo-list')->title->$language);
$tpl->set_var('manageDatabaseUrl', url('database-manager'));
$tpl->set_var('manageDatabaseText', getPageByProperty('page', 'database-manager')->title->$language);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>