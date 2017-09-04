<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//================================================ MAIN ================================================//
$language = Language::getCurrent();
$pages    = Page::getAllPages();

foreach ($pages as $id => $thePage) if ($thePage->page !== $page->page)
{
	$options[$thePage->page] = $thePage->title->$language;
}

// fetch existing tags.
$db = database::getInstance();
$q = $db->query();
$tags_options = [];
$tags = $q->select('tags', [$q->col('id'), $q->col("text$language")->as('text')])->run()->loadObjects('id');
foreach ($tags as $id => $tag) $tags_options[$id] = $tag->text;

// Create new form.
$form = new Form();

$form->addElement('header',
				  ['class' => 'title'],
				  ['level' => 2, 'text' => 'Select a page to edit', 'rowClass' => 'inline']);
$form->addElement('select',
                  ['name'    => 'page[selection]'],
                  ['options' => $options, 'rowClass' => 'clear', 'validation' => 'required']);
$form->addElement('radio',
                  ['name' => 'page[type]', 'tabindex' => 1],
                  ['validation' => 'required', 'inline' => true, 'options' => ['php' => 'PHP', 'article' => 'Article'], 'label' => text(14)]);


$form->addElement('wrapper',
				  ['class' => 'pageEdition', 'tabindex' => 2],
				  ['numberElements' => 24, 'toggle' => 'hideIf(page[type]=undefined)', 'toggleEffect' => 'slide']);

$form->addElement('hidden',
                  ['name' => 'page[nameInDB]']);
$form->addElement('text',
                  ['name' => 'page[name]', 'placeholder' => text(10), 'tabindex' => 3],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(9)]);
$form->addElement('text',
                  ['name' => 'page[path]', 'placeholder' => text(12), 'tabindex' => 4],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(11)]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock first'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'En']);
$form->addElement('text',
                  ['name' => 'page[title][en]', 'placeholder' => text('The page title'), 'tabindex' => 5, 'class' => 'pageTitle'],
                  ['validation' => 'required', 'label' => text(6)]);
$form->addElement('text',
                  ['name' => 'page[url][en]', 'placeholder' => text('A nice url for the new page'), 'tabindex' => 7, 'class' => 'pageUrl'],
                  ['validation' => 'required', 'label' => text(5)]);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][en]', 'placeholder' => text('Some sentences describing the content of the current page.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 9],
                  ['label' => text(7)]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][en]', 'placeholder' => text('Some coma separated words describing the content of the current page.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 11],
                  ['label' => text(8)]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'Fr']);
$form->addElement('text',
                  ['name' => 'page[title][fr]', 'placeholder' => text('The page title'), 'tabindex' => 6, 'class' => 'pageTitle'],
                  ['validation' => 'required', 'label' => text(6)]);
$form->addElement('text',
                  ['name' => 'page[url][fr]', 'placeholder' => text('A nice url for the new page'), 'tabindex' => 8, 'class' => 'pageUrl'],
                  ['validation' => 'required', 'label' => text(5)]);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][fr]', 'placeholder' => text('Some sentences describing the content of the current page.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 10],
                  ['label' => text(7)]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][fr]', 'placeholder' => text('Some coma separated words describing the content of the current page.'), 'cols' => 30, 'rows' => 10, 'tabindex' => 12],
                  ['label' => text(8)]);

$form->addElement('select',
                  ['name'        => 'page[parent]'],
                  ['options'     => $options, 'label' => text(13), 'rowClass' => 'clear', 'validation' => 'required', 'default' => 'home', 'tabindex' => 13]);


$form->addElement('wrapper',
				  ['class'       => 'articleStuff'],
				  ['numberElements' => 7, 'toggle' => 'showIf(page[type]=article)', 'toggleEffect' => 'slide']);
$form->addElement('upload',
                  ['name'        => 'page[uploads]'],
                  []);
$form->addElement('wysiwyg',
                  ['name'        => 'article[content][en]',
                   'placeholder' => text('Some coma separated words describing the content of the current page.'),
                   'cols'        => 50,
                   'rows'        => 30,
                   'tabindex'    => 14],
                  ['label'       => textf(20, 'En')]);
$form->addElement('wysiwyg',
                  ['name'        => 'article[content][fr]',
                   'placeholder' => text('Some coma separated words describing the content of the current page.'),
                   'rows'        => 30,
                   'cols'        => 50,
                   'tabindex'    => 15],
                  ['label'       => textf(20, 'Fr')]);
$form->addElement('select',
                  ['name'        => 'article[category]'],
                  ['options'     => [1 => 'system', 2 => 'travel'],
                   'value'       => 2,
                   'label'       => text('Article category'),
                   'tabindex'    => 16]);
$form->addElement('checkbox',
                  ['name'        => 'article[tags]',
                   'tabindex'    => 17],
                  ['options'     => $tags_options,
                   'label'       => text('Article tags'),
                   'multiple'    => true
                  ]);
$form->addElement('text',
                  ['name'        => 'article[image]',
				   'placeholder' => text('Article image for home page'),
				   'tabindex'    => 18],
                  ['default'     => 'images/gallery/___.jpg',
				   'label'       => text('Article image'),
				   'ignoreDefaultOnSubmit' => true]);
$form->addElement('radio',
                  ['name'        => 'article[status]',
                   'tabindex'    => 19],
                  ['options'     => ['published'   => text('Published'),
				   				     'draft'       => text('Draft'),
				   				     'coming soon' => text('Coming soon'),
				   				     'deleted'     => text('Deleted')],
				   'label'       => text('Article status')]);


$form->addButton('cancel', text(17), ['toggle' => 'hideIf(page[type]=undefined)']);
$form->addButton('validate', text(18), ['toggle' => 'hideIf(page[type]=undefined)']);

handleAjax(function()
{
	global $form;

	$gets = Userdata::get();
	if (isset($gets->fetchPage)) return ['html' => fetchPage($gets->fetchPage, $form)];

    $posts = Userdata::get('post');
    if (isset($posts->task) && $posts->task === 'save') return $form->validate('afterValidateForm');

    if (Userdata::is_set('files'))
    {
        $files = Userdata::get('files');
        return $form->validate('afterValidateForm');
    }
});

// Append '#' to the form action attribute in case of editing article and posted successfully.
// if ($form->getPostedData('page[type]') === 'article' && ($articleName = $form->getPostedData('page[nameInDB]')))
// $form->addOption(['hash' => "load/$articleName"]);
$form->addOption(['dontClearForm' => true]);

$form->validate('afterValidateForm');

//  @todo: test this.
// $form->modifyElementAttributes('page[metaDesc][en]', ['placeholder' => text('A nice hahaha')]);

$page->setContent($form->render())->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
/**
 * Validation callback is called internally by the form->validate() method if the form has no error.
 * It allows you to perform an extra check and block form with error if fails or validate form for good if everything alright.
 * If you don't need to check extra things, youcan just use this function as a callback to do other things on success.
 *
 * @param StdClass Object $result: the result of the validation process provided by the form validate() method.
 * @param Form Object $form: the current $form object, if you need
 * @return bool: true or false to agree to validate form or block it after an extra check.
 */
function afterValidateForm($result, $form)
{
	$language = Language::getCurrent();
	$return = false;
	$db = database::getInstance();
	$q = $db->query();
	$pageNameInDB = $form->getPostedData('page[nameInDB]');

	// Do not perform a page update if page not found in DB.
	$q->select('pages', [$q->col('article')])->relate('pages.typeId', 'articles.id')->where()->col('page')->eq($pageNameInDB);
	$isInDB = $q->run()->info()->numRows;

	if (!$isInDB)
	{
		new Message(textf(69, $pageNameInDB), 'error', 'error', 'content');
		$form->unsetPostedData('page[name]');
	}
	else
	{
		$affectedRows = 0;
		$pageName = text(($n = $form->getPostedData('page[name]')) ? $n : $form->getPostedData('page[url][en]'),
						 ['formats' => ['sef']]);

        // PHP file.
		if ($form->getPostedData('page[type]') === 'php') renamePhpFile($form->getPostedData('page[path]')."/$pageNameInDB", $form->getPostedData('page[path]')."/$pageName");

        // Article.
        elseif ($form->getPostedData('page[type]') === 'article')
		{
			$articleId = $q->loadResult();

			$q = $db->query();

			foreachLang(function($lang)
			{
				global $form;
				$settings = Settings::get();

				// Don't forget there will be backslashes here.
				$GLOBALS["content_$lang"] = preg_replace('~(?<=src=\\\")'.$settings->root.'(images/\?(?:i|u)=[^"]+)(?=\\\")~i', '$1', $form->getPostedData("article[content][$lang]", true));
			});

			$q->update('articles', ['content_en' => $GLOBALS["content_en"],
	                                'content_fr' => $GLOBALS["content_fr"],
	                                'author'     => User::getCurrent()->getId(),
	                                'category'   => (int)$form->getPostedData('article[category]'),
	                                'image'      => $form->getPostedData('article[image]'),
	                                'status'     => $form->getPostedData('article[status]')]);
			$w = $q->where()->col('id')->eq($articleId);
			$q->run();
			$affectedRows = $q->info()->affectedRows;

            //--------------------- Save article tags ---------------------//
            // First delete all the article tags from DB.
            $q = $db->query()
                    ->delete('article_tags');
            $w = $q->where()->col('article')->eq($articleId);
            $q->run();

            // Now for each tag create a new entry in the article_tags database table.
            $tags = $form->getPostedData('article[tags]');
            if ($tags) foreach ($tags as $tag)
            {
                $q = $db->query()
                        ->insert('article_tags', ['article' => $articleId, 'tag' => $tag])
                        ->run();
            }
            //-------------------------------------------------------------//
		}

        // In both cases PHP file and article, save a page entry in database.
		$q = $db->query();
		$q->update('pages', ['page'        => $pageName,
	                         'path'        => $form->getPostedData('page[path]') ? text($form->getPostedData('page[path]'), ['formats' => ['sef']]) : '',
	                         'url_en'      => text($form->getPostedData('page[url][en]'), ['formats' => ['sef']]),
	                         'url_fr'      => text($form->getPostedData('page[url][fr]'), ['formats' => ['sef']]),
	                         'title_en'    => $form->getPostedData('page[title][en]'),
	                         'title_fr'    => $form->getPostedData('page[title][fr]'),
	                         'metaDesc_en' => $form->getPostedData('page[metaDesc][en]'),
	                         'metaDesc_fr' => $form->getPostedData('page[metaDesc][fr]'),
	                         'metaKey_en'  => $form->getPostedData('page[metaKey][en]'),
	                         'metaKey_fr'  => $form->getPostedData('page[metaKey][fr]'),
	                         'parent'      => $form->getPostedData('page[parent]')]);
		$w = $q->where()->col('article')->eq($articleId);
		$q->run();
		$affectedRows += $q->info()->affectedRows;

		if ($affectedRows)
		{
			$pages = Page::getAllPages();
			$GLOBALS['pages'] = $pages;// Update the $pages global var.

			new Message(nl2br(textf(67, $pageName, url($pageName), stripslashes($form->getPostedData('page[title]['.$language.']')))), 'valid', 'success', 'content');
			$return = true;
		}
		else new Message(68, 'info', 'info', 'content');
	}

    // Keep showing the current form edition with user data even after successful submission.
	return false;
}

function renamePhpFile($oldName, $newName)
{
	return rename($oldName, $newName);
}

/**
 * Fetch the selected page info from database.
 *
 * @param String $page: the name of the page to retrieve.
 * @param Object $form: the current form object.
 * @return String: the html output of the rendered form.
 */
function fetchPage($page, $form)
{
	$settings = Settings::get();

	$page = Page::getByProperty('page', $page);
	$array = [
		'page[type]'         => $page->article ? 'article' : 'php',
		'page[selection]'    => $page->page,
		'page[nameInDB]'     => $page->page,
		'page[name]'         => $page->page,
		'page[path]'         => $page->path,
		'page[parent]'       => $page->parent,
		'page[url][en]'      => $page->url->en,
		'page[url][fr]'      => $page->url->fr,
		'page[title][en]'    => $page->title->en,
		'page[title][fr]'    => $page->title->fr,
		'page[metaKey][en]'  => $page->metaKey->en,
		'page[metaKey][fr]'  => $page->metaKey->fr,
		'page[metaDesc][en]' => $page->metaDesc->en,
		'page[metaDesc][fr]' => $page->metaDesc->fr
	];

	if ($page->article)// $page->article = Article id.
	{
		$article = getArticleInfo($page->article);
		$array['article[category]']    = $article->category;
		$array['article[status]']      = $article->status;
		$array['article[content][en]'] = preg_replace('~src="images\/\?(i|u)=~i', 'src="'.$settings->root.'images/?$1=', $article->content_en);
		$array['article[content][fr]'] = preg_replace('~src="images\/\?(i|u)=~i', 'src="'.$settings->root.'images/?$1=', $article->content_fr);
		$array['article[image]']       = $article->image;
		$array['article[created]']     = $article->created;
        $array['article[author]']      = $article->author;

        $db = Database::getInstance();
        $q  = $db->query();
        // $q->select('article_tags', [$q->col('id'), $q->col("text$language")->as('text')])
        $q->select('article_tags', [$q->col('tag')])
          ->relate('tags.id', 'article_tags.tag', true);
        $w = $q->where()->col('article')->eq($page->article);
        $tags = array_keys($q->run()->loadObjects('tag'));
		$array['article[tags]'] = $tags;
	}

	$form->injectValues($array);

	return $form->render();
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>