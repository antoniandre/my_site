<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//================================================ MAIN ================================================//
$language = Language::getCurrent();
$settings = Settings::get();
$pages = getPagesFromDB();
foreach ($pages as $id => $thePage) if ($thePage->page !== $page->page)
{
	$options[$thePage->page] = $thePage->title->$language;
}

// fetch existing tags.
$db = database::getInstance();
$q = $db->query();
$tags = $q->select('tags', [$q->col('id'), $q->col("text$language")->as('text')])->run()->loadObjects('id');
foreach ($tags as $id => $tag) $tags_options[$id] = $tag->text;

// @TODO: new request:
// SELECT id, name, textEn, textFr, GROUP_CONCAT(DISTINCT article) AS articles FROM `tags` as t left join article_tags on t.id = tag group by id.
/*$tags = $q->select('tags',
                   [
                        $q->col('id'),
                        $q->col('name'),
                        $q->col("text$language")->as('text'),
                        $q->groupConcatDistinct('article'->as->('articles')
                    ]
                   )->relateLeft('article_tags')->on('id=tag')->groupBy('id')->run()->loadObjects('id');*/


$form = new Form();
$form->addElement('wrapper',
				  ['class' => 'panes'],
				  ['numberElements' => 30]);
$form->addElement('wrapper',
				  ['class' => 'newPage pane'],
				  ['numberElements' => 19]);
$form->addElement('header',
				  ['class' => 'title'],
				  ['level' => 2, 'text' => 'Create a new page']);
$form->addElement('wrapper',
				  ['class' => 'inner'],
				  ['numberElements' => 17]);
$form->addElement('paragraph',
				  ['class' => 'intro'],
				  ['text' => text(1)]);
$form->addElement('radio',
                  ['name' => 'page[type]', 'tabindex' => 1],
                  ['validation' => 'required', 'inline' => true, 'options' => ['php' => 'PHP', 'article' => 'Article'], 'label' => text(14)]);
$form->addElement('text',
                  ['name' => 'page[name]', 'placeholder' => text(10), 'tabindex' => 2],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(9)]);
$form->addElement('text',
                  ['name' => 'page[path]', 'placeholder' => text(12), 'tabindex' => 3],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(11)]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock first'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'En']);
$form->addElement('text',
                  ['name' => 'page[title][en]', 'placeholder' => text('The page title'), 'tabindex' => 4, 'class' => 'pageTitle'],
                  ['validation' => 'required', 'label' => text(6)]);
$form->addElement('text',
                  ['name' => 'page[url][en]', 'placeholder' => text('A nice url for the new page'), 'tabindex' => 6, 'class' => 'pageUrl'],
                  ['validation' => 'required', 'label' => text(5)]);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][en]', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 8],
                  ['label' => text(7), 'default' => $settings->defaultMetaDesc['en']]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][en]', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 10],
                  ['label' => text(8), 'default' => $settings->defaultMetaKey['en']]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'Fr']);
$form->addElement('text',
                  ['name' => 'page[title][fr]', 'placeholder' => text('The page title'), 'tabindex' => 5, 'class' => 'pageTitle'],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'page[url][fr]', 'placeholder' => text('A nice url for the new page'), 'tabindex' => 7, 'class' => 'pageUrl'],
                  ['validation' => 'required']);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][fr]', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 9],
                  ['default' => $settings->defaultMetaDesc['fr']]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][fr]', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 11],
                  ['default' => $settings->defaultMetaKey['fr']]);



$form->addElement('select',
                  ['name' => 'page[parent]', 'tabindex' => 12],
                  ['options' => $options, 'label' => text(13), 'rowClass' => 'clear', 'validation' => 'required', 'default' => 'home']);
$form->addElement('wrapper',
				  ['class' => 'newArticle pane'],
				  ['numberElements' => 9, 'toggle' => 'showIf(page[type]=article)', 'toggleEffect' => 'slide']);
$form->addElement('header',
				  ['class' => 'title'],
				  ['level' => 2, 'text' => 'Create a new article']);
$form->addElement('wrapper',
				  ['class' => 'inner'],
				  ['numberElements' => 8]);
$form->addElement('paragraph',
				  ['class' => 'intro'],
				  ['text' => text(1)]);
/*$form->addElement('upload',
                  ['name' => 'article[upload][]'],
                  ['label' => text('upload'), 'accept' => ['jpg', 'png', 'gif']]);*/
$form->addElement('wysiwyg',
                  ['name' => 'article[content][en]',
                   'placeholder' => text('The article content in English.'),
                   'cols' => 50,
                   'rows' => 30,
                   'tabindex' => 13],
                  ['label' => textf(20, 'En'),
                   'validation' => 'requiredIf(page[type]=article)']);
$form->addElement('wysiwyg',
                  ['name' => 'article[content][fr]',
                   'placeholder' => text('The article content in French.'),
                   'cols' => 50,
                   'rows' => 30,
                   'tabindex' => 14],
                  ['label' => textf(20, 'Fr'),
                   'validation' => 'requiredIf(page[type]=article)']);
$form->addElement('select',
                  ['name' => 'article[category]', 'tabindex' => 15],
                  ['validation' => 'requiredIf(page[type]=article)', 'options' => [1 => 'system', 2 => 'travel'], 'value' => 2, 'label' => text('Article category'), 'default' => 2]);
$form->addElement('text',
                  ['name' => 'article[image]', 'placeholder' => text('Article image for home page'), 'tabindex' => 16],
                  ['default' => ['images/gallery/___.jpg', true]]);
$form->addElement('select',
                  ['name' => 'article[tags]', 'tabindex' => 17],
                  ['options' => $tags_options, 'label' => text('Article tags'), 'multiple' => true]);
$form->addElement('textarea',
                  ['name' => 'article[newTags]', 'placeholder' => text('Any new tag.'), 'cols' => 30, 'rows' => 5, 'tabindex' => 18],
                  ['label' => text('Article tags')]);
$form->addElement('checkbox',
                  ['name' => 'article[published]', 'tabindex' => 19],
                  ['inline' => true,
                   'options' => ['published' => 'published'],
                   'checked' => isset($posts->article->published) && $posts->article->published]);

$form->addButton('cancel', text(17));
$form->addButton('validate', text(18));

$form->validate('validateForm1');

$content = $form->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * validate called internally by the form validate() method if the form has no error.
 *
 * @param StdClass Object $result: the result of the validation process provided by the form validate() method.
 * @param Form Object $form: the current $form object, if you need
 * @return void
 */
function validateForm1($result, $form)
{
	$language = Language::getCurrent();
	$return = false;
	$fileCreated = false;
	$articleId = null;
	$db = database::getInstance();
	$q = $db->query();
	$pageName = text(($n = $form->getPostedData('page[name]')) ? $n : $form->getPostedData('page[url][en]'),
					 ['formats' => ['sef']]);

	// Do not perform the insertion in db if page found in DB.
	$q->select('pages', [$q->col('page')])->where()->col('page')->eq($pageName);
	$isInDB = $q->run()->info()->numRows;

	if ($isInDB)
	{
		new Message(textf(22, $pageName), 'error', 'error', 'content');
		$form->unsetPostedData('page[name]');
	}
	else
	{
		// PHP file creation.
		if ($form->getPostedData('page[type]') === 'php')
		{
			$fileCreated = createPhpFile($pageName, $form->getPostedData('page[path]'));
			if (!$fileCreated) new Message('A problem occured while creating the PHP file. Please check you have sufficent privileges and try again.', 'error', 'error', 'content');
		}

        // Article creation.
        elseif ($postingArticle = $form->getPostedData('page[type]') === 'article') $articleId = saveArticleInDB($form);

        // In both cases if no error, save the new page in DB.
		// Store a new page entry in DB only if previous steps were successful.
		if (($postingArticle && $articleId) || $fileCreated)
		{
            $pageId = savePageInDB($form, $pageName, $articleId);

			// If everything went fine.
			if ($pageId)
			{
                $savedTags = saveTagsInDB();// Array of inserted ids.

                // Before redirecting to the 'edit-a-page' edition script, postpone a message to say everything went fine.
                new Message(nl2br(textf(23, $pageName, url($pageName), stripslashes($form->getPostedData('page[title]['.$language.']')))), 'valid', 'success', 'content', true);

                // Now redirect to the edition script. Everything after that will never be executed (due to exit).
                // @TODO: find a way to redirect to edit-a-page.php script (independent of language) + hash part.
                redirectTo('edit-a-page.html#load/'.$pageName);

                // $return = true;
            }
        }
    }

	return $return;
}

function createPhpFile($fileName, $path)
{
	if ($path{strlen($path)-1} === '/') $path = substr($path, 0, -1);
	if ($path{0} === '/') $path= substr($path, 1);
	$backstage = strpos($path, 'backstage') !== false && !strpos($path, 'backstage') ? 'backstage/' : '';
	// Count slashes and climb tree up with '../': str_repeat('../', substr_count($path, '/')+1)

	$fileContents = "<?php\n//======================= VARS ========================//\n"
				  ."//=====================================================//\n\n\n"
				  ."//===================== INCLUDES ======================//\n"
				  ."//=====================================================//\n\n\n"
				  ."//======================================================================================================//\n"
				  ."//============================================= MAIN ===================================================//\n"
				  ."\$tpl = new Template();\n"
				  ."\$tpl->set_file(\"\$page->page-page\", \"{$backstage}templates/\$page->page.html\");\n"
				  ."\$tpl->set_var('content', \"The page content goes here for page \\\"\$page->page\\\".\");\n"
				  ."\$content = \$tpl->parse('display', \"\$page->page-page\");\n"
				  ."//============================================ end of MAIN =============================================//\n"
				  ."//======================================================================================================//\n?>";
	$path = ROOT."$path";
	if (!is_dir($path)) mkdir($path);
	file_put_contents("$path/$fileName.php", $fileContents);
	file_put_contents(ROOT."backstage/templates/$fileName.html", '{content}');

	return is_file(ROOT."backstage/templates/$fileName.html");
}

function saveArticleInDB($form)
{
    $articleId = null;
    $contentEn = preg_replace('~(?<=src=\\\")(?:\.\.\/)*\/?(uploads[^"]+|images[^"]+)(?=\\\")~i', '$1', $form->getPostedData('article[content][en]', true));
    $contentFr = preg_replace('~(?<=src=\\\")(?:\.\.\/)*\/?(uploads[^"]+|images[^"]+)(?=\\\")~i', '$1', $form->getPostedData('article[content][fr]', true));

    $db = database::getInstance();
    $q = $db->query();
    $q->insert('articles', ['content_en' => $contentEn,
                            'content_fr' => $contentFr,
                            'author' => User::getInstance()->getId(),
                            'category' => (int)$form->getPostedData('article[category]'),
                            'image' => $form->getPostedData('article[image]'),
                            'published' => (string)$form->getPostedData('article[published]')]);
    $q->run();
    $articleId = $q->info()->insertId;

    if (!$articleId) new Message('A problem occured while saving the article in database. Please check your data and try again.', 'error', 'error', 'content');

    return $articleId;
}

function savePageInDB($form, $pageName, $articleId)
{
    $pageId = null;

    $db = database::getInstance();
    $q = $db->query();
    $q->insert('pages', ['page' => $pageName,
                         'path' => $form->getPostedData('page[path]') ? text($form->getPostedData('page[path]'), ['formats' => ['sef']])                                         : '',
                         'url_en' => text($form->getPostedData('page[url][en]'), ['formats' => ['sef']]),
                         'url_fr' => text($form->getPostedData('page[url][fr]'), ['formats' => ['sef']]),
                         'title_en' => $form->getPostedData('page[title][en]'),
                         'title_fr' => $form->getPostedData('page[title][fr]'),
                         'metaDesc_en' => $form->getPostedData('page[metaDesc][en]'),
                         'metaDesc_fr' => $form->getPostedData('page[metaDesc][fr]'),
                         'metaKey_en' => $form->getPostedData('page[metaKey][en]'),
                         'metaKey_fr' => $form->getPostedData('page[metaKey][fr]'),
                         'parent' => $form->getPostedData('page[parent]'),
                         'article' => isset($articleId) ? $articleId : null]);
    $q->run();
    if (!$q->info()->affectedRows) new Message('A problem occured while saving the article in database. Please check your data and try again.', 'error', 'error', 'content');
    else $pageId = $q->info()->insertId;

    return $pageId;
}

function saveTagsInDB($form, $articleId)
{
    $tagIdList = [];

    /* Create a new tag:
    // @todo: finish implementing.
    $q = $db->query();
    $q->insert('article_tags', ['article' => isset($articleId) ? $articleId : null,
                                'tag' => $form->getPostedData('article[tags]')]);
    $q->run();
    if (!$q->info()->affectedRows) new Message('The tag could not be associated to this article. Please try again.', 'error', 'error', 'content');*/

    // Add an existing tag.
    $db = database::getInstance();
    $q = $db->query();
    foreach ((array)$form->getPostedData('article[tags]') as $tagId)
    {
        // before inserting row check if not already in database.
        $q->select('article_tags', $q->count('tag'));
        $w = $q->where()->col('tag')->eq($tagId)->and()->col('article')->eq($articleId);
        $existing = $q->run()->loadResult();

        if (!$q->info()->affectedRows) new Message('A problem occured while saving the article tags in database. Please try again.', 'error', 'error', 'content');

        if (!$existing)
        {
            $q->insert('article_tags', ['article' => $articleId, 'tag' => $tagId], true);
            $q->run();
            $tagIdList[] = $q->info()->insertId;
        }
    }

    return $tagIdList;
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>