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

$form = new Form();
$form->addElement('wrapper',
				  ['class' => 'panes'],
				  ['numberElements' => 29]);
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
                  ['name' => 'page[type]'],
                  ['validation' => 'required', 'inline' => true, 'options' => ['php' => 'PHP', 'article' => 'Article'], 'label' => text(14)]);
$form->addElement('text',
                  ['name' => 'page[name]', 'placeholder' => text(10)],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(9)]);
$form->addElement('text',
                  ['name' => 'page[path]', 'placeholder' => text(12)],
                  ['validation' => 'requiredIf(page[type]=php)', 'toggle' => 'showIf(page[type]=php)', 'toggleEffect' => 'slide', 'label' => text(11)]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock first'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'En']);
$form->addElement('text',
                  ['name' => 'page[url][en]', 'placeholder' => text('A nice url for the new page')],
                  ['validation' => 'required', 'label' => text(5)]);
$form->addElement('text',
                  ['name' => 'page[title][en]', 'placeholder' => text('The page title')],
                  ['validation' => 'required', 'label' => text(6)]);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][en]', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['label' => text(7), 'default' => $settings->defaultMetaDesc['en']]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][en]', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['label' => text(8), 'default' => $settings->defaultMetaKey['en']]);

$form->addElement('wrapper',
				  ['class' => 'languageBlock'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'Fr']);
$form->addElement('text',
                  ['name' => 'page[url][fr]', 'placeholder' => text('A nice url for the new page')],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'page[title][fr]', 'placeholder' => text('The page title')],
                  ['validation' => 'required']);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][fr]', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['default' => $settings->defaultMetaDesc['fr']]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][fr]', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['default' => $settings->defaultMetaKey['fr']]);



$form->addElement('select',
                  ['name' => 'page[parent]'],
                  ['options' => $options, 'label' => text(13), 'rowClass' => 'clear', 'validation' => 'required', 'default' => 'home']);


$form->addElement('wrapper',
				  ['class' => 'newArticle pane'],
				  ['numberElements' => 8, 'toggle' => 'showIf(page[type]=article)', 'toggleEffect' => 'slide']);
$form->addElement('header',
				  ['class' => 'title'],
				  ['level' => 2, 'text' => 'Create a new article']);
$form->addElement('wrapper',
				  ['class' => 'inner'],
				  ['numberElements' => 6]);
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
                   'rows' => 30],
                  ['label' => textf(20, 'En'),
                   'validation' => 'requiredIf(page[type]=article)']);
$form->addElement('wysiwyg',
                  ['name' => 'article[content][fr]',
                   'placeholder' => text('The article content in French.'),
                   'cols' => 50,
                   'rows' => 30],
                  ['label' => textf(20, 'Fr'),
                   'validation' => 'requiredIf(page[type]=article)']);
$form->addElement('select',
                  ['name' => 'article[category]'],
                  ['validation' => 'requiredIf(page[type]=article)', 'options' => [1 => 'system', 2 => 'travel'], 'value' => 2, 'label' => text('Article category'), 'default' => 2]);
$form->addElement('text',
                  ['name' => 'article[image]', 'placeholder' => text('Article image for home page')],
                  ['default' => ['images/gallery/___.jpg', true]]);
$form->addElement('checkbox',
                  ['name' => 'article[published]'],
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
	$db = database::getInstance();
	$q = $db->query();
	$pageName = text(($n = $form->getPostedData('page[name]')) ? $n : $form->getPostedData('page[url][en]'),
					 ['formats' => ['sef']]);
dbgd($form->getPostedData('article[published]'));
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
		// Article creation.
		if ($postingArticle = $form->getPostedData('page[type]') === 'article')
		{
			$q = $db->query();
			$contentEn = preg_replace('~(?<=src=\\\")(?:\.\.\/)*\/?(uploads[^"]+|images[^"]+)(?=\\\")~i', '$1', $form->getPostedData('article[content][en]', true));
			$contentFr = preg_replace('~(?<=src=\\\")(?:\.\.\/)*\/?(uploads[^"]+|images[^"]+)(?=\\\")~i', '$1', $form->getPostedData('article[content][fr]', true));
			$q->insert('articles', ['content_en' => $contentEn,
	                                'content_fr' => $contentFr,
	                                'author' => User::getInstance()->getId(),
	                                'category' => (int)$form->getPostedData('article[category]'),
	                                'image' => $form->getPostedData('article[image]'),
	                                'published' => (bool)$form->getPostedData('article[published]')]);
			$q->run();
			$articleId = $q->info()->insertId;

			if (!$articleId) new Message('A problem occured while saving the article in database. Please check your data and try again.', 'error', 'error', 'content');
		}

		// PHP file creation.
		elseif ($form->getPostedData('page[type]') === 'php')
		{
			$fileCreated = createPhpFile($pageName, $form->getPostedData('page[path]'));
			if (!$fileCreated) new Message('A problem occured while creating the PHP file. Please check you have sufficent privileges and try again.', 'error', 'error', 'content');
		}

		// Store a new page entry in DB only if previous steps were successful.
		if (($postingArticle && $articleId) || !$fileCreated)
		{
			$q = $db->query();
			$q->insert('pages', ['page' => $pageName,
		                         'path' => $form->getPostedData('page[path]') ? text($form->getPostedData('page[path]'), ['formats' => ['sef']]) : '',
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

			// If everything went fine.
			else
			{
				$pages = getPagesFromDB();
				$GLOBALS['pages'] = $pages;// Update the $pages global var.

				new Message(nl2br(textf(23, $pageName, url($pageName), stripslashes($form->getPostedData('page[title]['.$language.']')))), 'valid', 'success', 'content');
				$return = true;
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
				  ."\$tpl= new Template();\n"
				  ."\$tpl->set_file(\"\$page->page-page\", \"{$backstage}templates/\$page->page.html\");\n"
				  ."\$tpl->set_var('content', \"The page content goes here for page \\\"\$page->page\\\".\");\n"
				  ."\$content= \$tpl->parse('display', \"\$page->page-page\");\n"
				  ."//============================================ end of MAIN =============================================//\n"
				  ."//======================================================================================================//\n?>";
	$path = ROOT."$path";
	if (!is_dir($path)) mkdir($path);
	file_put_contents("$path/$fileName.php", $fileContents);
	file_put_contents(ROOT."backstage/templates/$fileName.html", '{content}');

	return is_file(ROOT."backstage/templates/$fileName.html");
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>