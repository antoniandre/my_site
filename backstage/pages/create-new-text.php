<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
include __DIR__.'/../classes/form.php';
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
handlePostedData();

$availableLanguages = Language::allowedLanguages;

$tpl = new Template('.');
$tpl->set_file("$page->page-page", "templates/$page->page.html");
$tpl->set_block("$page->page-page", 'languageBlock', 'theLanguageBlock');
foreach ($availableLanguages as $lang => $fullLang)
{
	$tpl->set_var(['Language' => ucfirst($lang),
				   'language' => $lang,
				   'textLabel' => text(2),
				   'textValue' => isset($posts->text->$lang) ? stripslashes($posts->text->$lang) : '']);
	$tpl->parse('theLanguageBlock', 'languageBlock', true);
}

$pageOptions = '';
$options = [];
foreach (getPagesFromDB() as $id => $thePage)
{
	$pageOptions .= '<option value="'.$thePage->page.'">'.$thePage->title->$language.'</option>';


	$options[$thePage->page]= $thePage->title->$language;
}




$form = new Form();
$form->addElement('wrapper', 2, 'languageBlock');
$form->addElement('header', 2, 'En');
$form->addElement('text',
                  ['name' => 'text[en]', 'value' => ''],
                  ['validation' => 'required', 'label' => 'Text En']);
$form->addElement('wrapper', 2, 'languageBlock');
$form->addElement('header', 2, 'Fr');
$form->addElement('text',
                  ['name' => 'text[fr]', 'value' => ''],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'text[context]',
                   'value' => isset($posts->text->context) ? stripslashes($posts->text->context) : '',
                   'placeholder' => text(4)],
                  ['validation' => 'required', 'label' => text(3), 'rowClass' => 'clear', 'rowSpan' => 2]);
$form->addElement('select',
                  ['name' => 'text[pageContext]'],
                  ['options' => $options]);

$tpl->set_var(['contextLabel' => text(3),
			   'contextValue' => isset($posts->text->context) ? stripslashes($posts->text->context) : '',
			   'pageContextOptions' => $pageOptions,
			   'pageContextPlaceholder' => text(4),
			   'cancelText' => text(17),
			   'validateText' => text(18),
			   'form' =>  $form->render()]);
$content= $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function handlePostedData()
{
	$posts = Userdata::$post;

	if (!count((array)$posts)) return false;// If no posted data do not go further.
	if (isset($posts->text))
	{
		// Check posted data.
		if (Userdata::checkFields(['text', 'text->context'], 'post', true))
		{
			$db = database::getInstance();
			$q = $db->query();

			// Do not perform the insertion in db if last insert is same (prevent send twice on page refresh).
			$isInDB = $q->checkLastInsert('texts', [$q->col('text_en'), $q->col('text_fr'), $q->col('context')], $posts->text->en.$posts->text->fr.$posts->text->context);
			if ($isInDB)
			{
				new Message(text(42), 'error', 'error', 'content');
				unset($posts->text);
			}
			else
			{
				$q->insert('texts', ['text_en' => $posts->text->en,
					                 'text_fr' => $posts->text->fr,
					                 'context' => $posts->text->context]);
				$q->run();
				if ($q->info()->affectedRows)
				{
					new Message(textf(16, $q->info()->insertId), 'valid', 'success', 'content');
					unset($posts->text);
				}
			}
		}
		else
			new Message(text(41), 'error', 'error', 'content');
	}
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>