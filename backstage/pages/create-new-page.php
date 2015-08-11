<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
include __DIR__.'/../classes/form.php';
//=====================================================//


//======================================================================================================//
//================================================ MAIN ================================================//
$tpl = new Template('.');
$availableLanguages = Language::allowedLanguages;

foreach (getPagesFromDB() as $id => $thePage) if ($thePage->page !== $page->page)
{
	$options[$thePage->page]= $thePage->title->$language;
}

$form = new Form();
$form->addElement('wrapper',
				  ['class' => 'panes'],
				  ['numberElements' => 28]);
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
                  ['name' => 'page[type]', 'value' => ''],
                  ['validation' => 'required', 'options' => ['php' => 'PHP', 'article' => 'Article'], 'label' => text(14)]);
$form->addElement('text',
                  ['name' => 'page[name]', 'value' => '', 'placeholder' => text(10)],
                  ['validation' => 'required', 'label' => text(9)]);
$form->addElement('wrapper',
				  ['class' => 'languageBlock'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'En']);
$form->addElement('text',
                  ['name' => 'page[url][en]', 'value' => '', 'placeholder' => text('A nice url for the new page')],
                  ['validation' => 'required', 'label' => text(5)]);
$form->addElement('text',
                  ['name' => 'page[title][en]', 'value' => '', 'placeholder' => text('The page title')],
                  ['validation' => 'required', 'label' => text(6)]);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][en]', 'value' => '', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['validation' => 'required', 'label' => text(7)]);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][en]', 'value' => '', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['validation' => 'required', 'label' => text(8)]);
$form->addElement('wrapper',
				  ['class' => 'languageBlock'],
				  ['numberElements' => 5]);
$form->addElement('header',
				  [],
				  ['level' => 3, 'text' => 'Fr']);
$form->addElement('text',
                  ['name' => 'page[url][fr]', 'value' => '', 'placeholder' => text('A nice url for the new page')],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'page[title][fr]', 'value' => '', 'placeholder' => text('The page title')],
                  ['validation' => 'required']);
$form->addElement('textarea',
                  ['name' => 'page[metaDesc][fr]', 'value' => '', 'placeholder' => text('Some sentences describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['validation' => 'required']);
$form->addElement('textarea',
                  ['name' => 'page[metaKey][fr]', 'value' => '', 'placeholder' => text('Some coma separated words describing the content at stake.'), 'cols' => 30, 'rows' => 10],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'page[path]', 'placeholder' => text(12)],
                  ['validation' => 'required', 'label' => text(11), 'rowClass' => 'clear']);
$form->addElement('select',
                  ['name' => 'page[parent]'],
                  ['options' => $options, 'label' => text(13)]);

$form->addElement('wrapper',
				  ['class' => 'newArticle pane'],
				  ['numberElements' => 7]);
$form->addElement('header',
				  ['class' => 'title'],
				  ['level' => 2, 'text' => 'Create a new article']);
$form->addElement('wrapper',
				  ['class' => 'inner'],
				  ['numberElements' => 5]);
$form->addElement('paragraph',
				  ['class' => 'intro'],
				  ['text' => text(1)]);
$form->addElement('upload',
                  ['name' => 'article[upload]', 'value' => ''],
                  ['label' => text('upload')]);
$form->addElement('textarea',
                  ['name' => 'article[content][en]',
                   'class' => 'articleContent',
                   'value' => isset($posts->article->content->en) ? stripslashes($posts->article->content->en) : '',
                   'placeholder' => text('Some coma separated words describing the content at stake.'),
                   'cols' => 50,
                   'rows' => 30],
                  ['label' => textf(20, 'En')/*,
                   'validation' => 'required'*/]);
$form->addElement('textarea',
                  ['name' => 'article[content][fr]',
                   'class' => 'articleContent',
                   'value' => isset($posts->article->content->fr) ? stripslashes($posts->article->content->fr) : '',
                   'placeholder' => text('Some coma separated words describing the content at stake.'),
                   'cols' => 50,
                   'rows' => 30],
                  ['label' => textf(20, 'Fr')/*,
                   'validation' => 'required'*/]);
$form->addElement('checkbox',
                  ['name' => 'article[published]', 'value' => ''],
                  [/*'validation' => 'required',
                   */'options' => ['published' => 'published'],
                   'checked' => isset($posts->article->published) && $posts->article->published]);

$form->addButton('cancel', text(17));
$form->addButton('validate', text(18));

$form->validate();


$tpl->set_var(['form' => $form->render()]);
$content = $tpl->parse('display', "$page->page-page");
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
function validate($result, $form)
{
	if (!$result->invalid)
	{
		$db = database::getInstance();
		$q = $db->query();
		$pageName = text($form->getPostedData('page[name]'), ['formats' => ['sef']]);

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
			if ($form->getPostedData('page[type]') === 'php') createPhpFile($pageName, $form->getPostedData('page[path]'));
			elseif ($form->getPostedData('page[type]') === 'article')
			{
				$q = $db->query();
				$q->insert('articles', ['content_en' => $form->getPostedData('article[content][en]', true),
		                                'content_fr' => $form->getPostedData('article[content][fr]', true),
		                                'author' => User::getInstance()->getId(),
		                                'published' => (bool)$form->getPostedData('article[published]')]);
				$q->run();
				$articleId = $q->info()->insertId;
			}

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

			if ($q->info()->affectedRows) new Message(textf(23, $pageName), 'valid', 'success', 'content');
			else new Message('There was a problem.', 'error', 'error', 'content');
		}
	}
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
				  ."\$tpl= new Template('.');\n"
				  ."\$tpl->set_file(\"\$page->page-page\", \"{$backstage}templates/\$page->page.html\");\n"
				  ."\$tpl->set_var('content', \"The page content goes here for page \\\"\$page->page\\\".\");\n"
				  ."\$content= \$tpl->parse('display', \"\$page->page-page\");\n"
				  ."//============================================ end of MAIN =============================================//\n"
				  ."//======================================================================================================//\n?>";
	$path = __DIR__."/../../$path";
	if (!is_dir($path)) mkdir($path);
	file_put_contents("$path/$fileName.php", $fileContents);
	file_put_contents(__DIR__."/../templates/$fileName.html", '{content}');
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>