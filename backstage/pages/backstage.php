<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$tpl = new Template();
$tpl->set_file("$page->page-page", "templates/$page->page.html");
$tpl->set_var(['h2' => text(45),
               'createNewPageUrl' => url('create-new-page'),
               'createNewPageText' => getPageByProperty('page', 'create-new-page')->title->$language,
               'editAPageUrl' => url('edit-a-page'),
               'editAPageText' => getPageByProperty('page', 'edit-a-page')->title->$language,
               'createNewTextUrl' => url('create-new-text'),
               'createNewTextText' => getPageByProperty('page', 'create-new-text')->title->$language,
               'toDoListUrl' => url('todo-list'),
               'toDoListText' => getPageByProperty('page', 'todo-list')->title->$language,
               'manageDatabaseUrl' => url('database-manager'),
               'manageDatabaseText' => getPageByProperty('page', 'database-manager')->title->$language]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>