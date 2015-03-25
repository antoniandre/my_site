<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//================================================ MAIN ================================================//
handlePostedData();

$availableLanguages = Language::allowedLanguages;
$tpl = new Template('.');
$tpl->set_file("$page->page-page", "templates/$page->page.html");
$tpl->set_block("$page->page-page", 'languageBlock', 'theLanguageBlock');
$tpl->set_block("$page->page-page", 'languageBlock2', 'theLanguageBlock2');
foreach ($availableLanguages as $lang => $fullLang)
{
	$tpl->set_var(['Language' => ucfirst($lang),
					'language' => $lang,
					'pageUrlLabel' => text(5),
					'pageTitleLabel' => text(6),
					'pageMetaDescLabel' => text(7),
					'pageMetaKeyLabel' => text(8),
					'pageUrlValue' => isset($posts->page->url->$lang) ? stripslashes($posts->page->url->$lang) : '',
					'pageTitleValue' => isset($posts->page->title->$lang) ? stripslashes($posts->page->title->$lang) : '',
					'pageMetaDescValue' => isset($posts->page->metaDesc->$lang) ? stripslashes($posts->page->metaDesc->$lang) : '',
					'pageMetaKeyValue' => isset($posts->page->metaKey->$lang) ? stripslashes($posts->page->metaKey->$lang) : '',

					'articleContentLabel' => textf(20, ucfirst($lang)),
					'articleContentValue' => isset($posts->article->content->$lang) ? stripslashes($posts->article->content->$lang) : '',
					'articlePublishedLabel' => text(15),
					'articlePublishedSelected' => isset($posts->article->published) && $posts->article->published]);
	$tpl->parse('theLanguageBlock', 'languageBlock', true);
	$tpl->parse('theLanguageBlock2', 'languageBlock2', true);
}

$pageOptions = '';

foreach (getPagesFromDB() as $id => $thePage) if ($thePage->page !== $page->page)
{
	$pageOptions .= '<option value="'.$thePage->page.'">'.$thePage->title->$language.'</option>';
}
$tpl->set_var(['newPageIntro' => text(1),
			   'pageNameLabel' => text(9),
			   'pageNamePlaceholder' => text(10),
			   'pageNameValue' => isset($posts->page->name) ? stripslashes($posts->page->name) : '',
			   'pagePathLabel' => text(11),
			   'pagePathPlaceholder' => text(12),
			   'pagePathValue' => isset($posts->page->path) ? stripslashes($posts->page->path) : '',
			   'pageParentLabel' => text(13),
			   'pageParentValue' => $pageOptions,
			   'pageTypeLabel' => text(14),
			   'pageTypePHPLabel' => 'PHP',
			   'pageTypeArticleLabel' => 'Article',
			   'cancelText' => text(17),
			   'validateText' => text(18)]);
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function handlePostedData()
{
	global $posts;

	if (!count((array)$posts)) return false;// If no posted data do not go further.

	if (isset($posts->page) && isset($posts->page->name) && $posts->page->name)
	{
		$db = database::getInstance();
		$q = $db->query();

		// Do not perform the insertion in db if page found in DB.
		$q->select('pages', [$q->col('page')])->where()->col('page')->eq($posts->page->name);
		$isInDB = $q->run()->info()->numRows;

		if ($isInDB)
		{
			new Message(textf(22, $posts->page->name), 'error', 'error', 'content');
			unset($posts->page->name);
		}
		else
		{
			if ($posts->page->type === 'php') createPhpFile($posts->page->name, $posts->page->path);
			elseif ($posts->page->type === 'article')
			{
				dbgd($_POST['article']['published']);
				$q = $db->query();
				$q->insert('articles', ['content_en' => secureVars($_POST['article']['content']['en'], true, true),
		                                'content_fr' => secureVars($_POST['article']['content']['fr'], true, true),
		                                'author' => 1/*TODO: $user->id*/,
		                                'published' => $posts->article->published]);
				$q->run();
				$articleId = $q->info()->insertedId;
			}

			$q = $db->query();
			$q->insert('pages', ['page' => $posts->page->name,
		                         'path' => $posts->page->path,
		                         'url_en' => $posts->page->url->en,
		                         'url_fr' => $posts->page->url->fr,
		                         'title_en' => $posts->page->title->en,
		                         'title_fr' => $posts->page->title->fr,
		                         'metaDesc_en' => $posts->page->metaDesc->en,
		                         'metaDesc_fr' => $posts->page->metaDesc->fr,
		                         'metaKey_en' => $posts->page->metaKey->en,
		                         'metaKey_fr' => $posts->page->metaKey->fr,
		                         'parent' => $posts->page->parent,
		                         'article' => isset($articleId) ? $articleId : null]);
			$q->run();

			if ($q->info()->affectedRows) new Message(textf(23, $posts->page->name), 'valid', 'success', 'content');
			else new Message('there was a problem.', 'error', 'error', 'content');
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