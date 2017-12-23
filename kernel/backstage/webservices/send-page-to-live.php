<?php
// @todo: also send article tags.
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//========================================== FUNCTIONS =================================================//
/**
 * Called by Webservice->consume().
 * This function will be run from the live site on distant server.
 * Put here the action you want the live site to perform with the given data coming from localhost.
 *
 * @param object $data: the data from localhost you want to send to the live site for processing.
 * @return string: a status or info message to send back. You can also die directly here.
 */
function distantCode($data)
{
    $return = '';

    //!\ We want to addslashes and secure content before insert in DB.
    $data = Userdata::secureVars($data, true, true);

    $page    = $data->page;
    $article = $data->article;

    if (is_object($article))
    {
        Cerror::logAndDie("ERROR: Wrong task. If you want to upload an article press the appropriate button.\n"
                         . print_r(['page' => $page, 'article' => $article], 1),
                         'ERROR: Missing data. Please check that the page and article data are sent and try again.');
    }

    if (!is_object($page) || !is_object($article))
    {
        Cerror::logAndDie("ERROR: Missing data. Here are the page and article received data: \n"
                         . print_r(['page' => $page, 'article' => $article], 1),
                         'ERROR: Missing data. Please check that the page and article data are sent and try again.');
    }

    $db = database::getInstance();

    // First check if the article page exists in DB if so stop the process.
    $q = $db->query();
    $q->select('pages', ['page'])->where()->col('page')->eq($page->page);
    $pageExists = $q->run()->numRows();

    if ($pageExists) $return = 'ERROR: The article is already in database.';

    // Article.
    $articleId = null;

    $q = $db->query();
                            // Force the ID to be the same as localhost for css classes.
    $q->insert('articles', ['id'         => $article->id,
                            'content_en' => $article->content_en,
                            'content_fr' => $article->content_fr,
                            'created'    => $article->created,
                            'author'     => $article->author,
                            'category'   => (int)$article->category,
                            'image'      => $article->image,
                            'status'     => $article->status]);
    $q->run();
    $articleId = $q->info()->insertId;

    if (!$articleId)
    {
        Cerror::logAndDie("ERROR: A problem occured while saving the article in database. More info from SQL query:\n"
                         . print_r($q->info(), 1),
                         'ERROR: A problem occured while saving the article in database. Please check your data and try again.');
    }

    // Page.
    $q = $db->query();
    $q->insert('pages', ['page'        => $page->page,
                         'path'        => $page->path ? text($page->path, ['formats' => ['sef']]) : '',
                         'url_en'      => text($page->url_en, ['formats' => ['sef']]),
                         'url_fr'      => text($page->url_fr, ['formats' => ['sef']]),
                         'title_en'    => $page->title_en,
                         'title_fr'    => $page->title_fr,
                         'metaDesc_en' => $page->metaDesc_en,
                         'metaDesc_fr' => $page->metaDesc_fr,
                         'metaKey_en'  => $page->metaKey_en,
                         'metaKey_fr'  => $page->metaKey_fr,
                         'parent'      => $page->parent,
                         'aliases'     => $page->aliases,
                         'icon'        => $page->icon,
                         'type'        => 'article',
                         'typeId'      => isset($articleId) ? $articleId : null]);
    $q->run();

    if (!$q->info()->affectedRows)
    {
        Cerror::logAndDie("ERROR: A problem occured while saving the page in database. More info from SQL query:\n"
                        . print_r($q->info(), 1),
                        'ERROR: A problem occured while saving the page in database. Please check your data and try again.');
    }

    // Everything went fine.
    else
    {
        $pageId = $q->info()->insertId;
        $q = $db->query();

        // Here we want to force the article ID to be the same as localhost for css classes.
        $q->insert('articles_tags', ['article' => $article->id,
                                     'tag'     => $article->content_en]);
        $q->run();
        $articleId = $q->info()->insertId;

        $return = "SUCCESS! The page and article data was inserted successfully to the live site database (Article ID = $articleId).";
    }

    return $return;
}


/**
 * Called by Webservice->consume().
 * Actions to perform just before webservice consume().
 * You may remove the function or leave it empty if you don't need to send data or perform action prior consume().
 * But if you want to send data to the distant server, the function must return an array like [$data, $method].
 * The data can be anything as it will be json_encoded.
 *
 * @return array: [$data, $method].
 */
function beforeConsume()
{
    $data = prepareData();

    return [$data, 'post'];
}


/**
 * Called by Webservice->consume().
 * Actions to perform just after webservice consume().
 * You may want to use the return data from the distant server accessible through the $data param.
 * The webservice ends at this point.
 *
 * @return void.
 */
function afterConsume($data)
{
    $messageType = 'info';
    if (strpos($data, 'SUCCESS') === 0) $messageType = 'success';
    if (strpos($data, 'ERROR') === 0) $messageType = 'error';

    new Message("Distant page said:<br>" . $data, $messageType, $messageType, 'header', true);
}


/**
 * This function fetch the requested page and article and return it to beforeConsume() to send to live site.
 *
 * @return Array: the page and the article to inject in live site.
 */
function prepareData()
{
    $posts  = Userdata::get('post');
    $pageId = $posts->{"send-page"}->page;

    // Fetch page from DB.
    $db = database::getInstance();
    $q = $db->query();
    $q->select('pages', '*')->where()->col('page')->eq($articleId);
    $page = $q->run()->loadObject();

    return ['page' => $page, 'article' => $article];
}
//====================================== END of FUNCTIONS ==============================================//
//======================================================================================================//
?>