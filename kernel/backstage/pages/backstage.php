<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
// Get texts form.
//-------------------------------------------------------------------------------//
$form = new Form(['id' => 'getTexts']);
$form->addButton('submit',
                 text('Fetch the texts from the live site'),
                 ['class' => 'i-cloud-download', 'name' => 'getTexts', 'value' => 1]);
$form->validate('getTexts');
//-------------------------------------------------------------------------------//


// Send article Form.
//-------------------------------------------------------------------------------//
$form2 = new Form(['id' => 'sendArticle']);

$pages = Page::getAllPages();
foreach ($pages as $id => $thePage) if ($thePage->page !== $page->page)
{
    $options[$thePage->page] = $thePage->title->$language;
}

$form2->addElement('select',
                  ['name' => 'article'],
                  ['label' => text('Choose an article to send to the live site'), 'options' => $options, 'rowClass' => 'clear', 'validation' => 'required']);
$form2->addButton('submit',
                  text('Send the article'),
                  ['class' => 'i-cloud-upload', 'name' => 'sendArticle', 'value' => 1, 'title' => text('Don\'t forget to upload the pictures!')]);
$form2->validate('sendArticle');
//-------------------------------------------------------------------------------//

$tpl = newPageTpl();
$tpl->set_var(['h2' => text(70),
               'createNewPageUrl' => url('create-new-page'),
               'createNewPageText' => getPageByProperty('page', 'create-new-page')->title->$language,
               'editAPageUrl' => url('edit-a-page'),
               'editAPageText' => getPageByProperty('page', 'edit-a-page')->title->$language,
               'createNewTextUrl' => url('create-new-text'),
               'createNewTextText' => getPageByProperty('page', 'create-new-text')->title->$language,
               'toDoListUrl' => url('todo-list'),
               'toDoListText' => getPageByProperty('page', 'todo-list')->title->$language,
               'manageDatabaseUrl' => url('database-manager'),
               'manageDatabaseText' => getPageByProperty('page', 'database-manager')->title->$language,
               'fetchTextsFromLiveButton' => IS_LOCAL ? $form->render() : '',
               'sendArticleToLive' => IS_LOCAL ? $form2->render() : '']);
$page->setContent($tpl->parse('display', $page->page))->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//========================================== FUNCTIONS =================================================//
/**
 * Get the texts directly from the live site database.
 *
 * @param  Object $info: information on field validity returned by the Form class.
 * @param  Object $form: the current Form instance.
 * @return [void.
 */
function getTexts($info, $form)
{
    if ($form->getPostedData('getTexts') && IS_LOCAL)
    {
        includeClass('webservice');
        new Webservice('get-texts-from-live');
    }
}


/**
 * Send a localhost article to the live site.
 *
 * @param  Object $info: information on field validity returned by the Form class.
 * @param  Object $form: the current Form instance.
 * @return [void.
 */
function sendArticle($info, $form)
{
    if ($form->getPostedData('sendArticle') && IS_LOCAL)
    {
        includeClass('webservice');
        new Webservice('send-article-to-live');
    }
}
//====================================== END of FUNCTIONS ==============================================//
//======================================================================================================//
?>