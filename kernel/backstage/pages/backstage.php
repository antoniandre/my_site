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
                 text(105),
                 ['class' => 'i-cloud-download', 'name' => 'getTexts', 'value' => 1]);
$form->validate('getTexts');
//-------------------------------------------------------------------------------//


// Send page Form.
//-------------------------------------------------------------------------------//
$form2           = new Form(['id' => 'send-page']);

$pages           = Page::getAllPages();
$pagesOptions    = [];
$articlesOptions = [];

foreach ($pages as $id => $thePage) if ($thePage->page !== $page->page)
{
    if ($thePage->type === 'page') $pagesOptions[$thePage->page]    = $thePage->title->$language;
    else                           $articlesOptions[$thePage->page] = $thePage->title->$language;
}

$form2->addElement('select',
                  ['name'    => 'page'],
                  ['options' => $pagesOptions, 'rowClass' => 'clearfix', 'validation' => 'required']);
$form2->addButton('submit',
                  text(106),
                  ['class' => 'i-cloud-upload', 'name' => 'sendPage', 'value' => 1]);
$form2->validate('sendPage');
//-------------------------------------------------------------------------------//


// Send article Form.
//-------------------------------------------------------------------------------//
$form3 = new Form(['id' => 'send-article']);
$form3->addElement('select',
                  ['name'    => 'article'],
                  ['options' => $articlesOptions, 'rowClass' => 'clearfix', 'validation' => 'required']);
$form3->addButton('submit',
                  text(107),
                  ['class' => 'i-cloud-upload', 'name' => 'sendArticle', 'value' => 1, 'title' => text('Don\'t forget to upload the pictures!')]);
$form3->validate('sendArticle');
//-------------------------------------------------------------------------------//

$tpl = newPageTpl();
$tpl->set_var(['h2'                       => text(70),
               'createNewPageUrl'         => url('create-new-page'),
               'createNewPageText'        => Page::get('create-new-page')->getTitle(),
               'editAPageUrl'             => url('edit-a-page'),
               'editAPageText'            => Page::get('edit-a-page')->getTitle(),
               'createNewTextUrl'         => url('create-new-text'),
               'createNewTextText'        => Page::get('create-new-text')->getTitle(),
               'toDoListUrl'              => url('todo-list'),
               'toDoListText'             => Page::get('todo-list')->getTitle(),
               'manageDatabaseUrl'        => url('database-manager'),
               'manageDatabaseText'       => Page::get('database-manager')->getTitle(),
               'unzipArchivesUrl'         => url('unzip'),
               'unzipArchivesText'        => Page::get('unzip')->getTitle(),
               'fetchTextsFromLiveButton' => IS_LOCAL ? '<li>' . $form->render()  . '</li>' : '',
               'sendPageToLive'           => IS_LOCAL ? '<li>' . $form2->render() . '</li>' : '',
               'sendArticleToLive'        => IS_LOCAL ? '<li>' . $form3->render() . '</li>' : '']);
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
function getTexts($form, $info)
{
    if ($form->getPostedData('getTexts') && IS_LOCAL)
    {
        includeClass('webservice');
        new Webservice('get-texts-from-live');
    }
}


/**
 * Send a localhost page to the live site.
 *
 * @param  Object $info: information on field validity returned by the Form class.
 * @param  Object $form: the current Form instance.
 * @return [void.
 */
function sendPage($form, $info)
{
    if ($form->getPostedData('sendPage') && IS_LOCAL)
    {
        includeClass('webservice');
        new Webservice('send-page-to-live');
    }
}


/**
 * Send a localhost article to the live site.
 *
 * @param  Object $info: information on field validity returned by the Form class.
 * @param  Object $form: the current Form instance.
 * @return [void.
 */
function sendArticle($form, $info)
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