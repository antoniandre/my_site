<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$options = [];
foreach (Page::getAllPages() as $id => $thePage)
{
	$options[$thePage->page]= $thePage->title->$language;
}


$form = new Form();
$form->addElement('wrapper', ['class' =>'languageBlock first'], ['numberElements' => 2]);
$form->addElement('header', [], ['level' => 2, 'text' => 'En']);
$form->addElement('text',
                  ['name' => 'text[en]', 'value' => '', 'placeholder' => text('Text En')],
                  ['validation' => 'required', 'label' => text('Text En')]);
$form->addElement('wrapper', ['class' =>'languageBlock'], ['numberElements' => 2]);
$form->addElement('header', [], ['level' => 2, 'text' => 'Fr']);
$form->addElement('text',
                  ['name' => 'text[fr]', 'value' => '', 'placeholder' => text('Text Fr')],
                  ['validation' => 'required']);
$form->addElement('text',
                  ['name' => 'context',
                   'placeholder' => text(4)],
                  ['validation' => 'required', 'label' => text(3), 'rowClass' => 'clear', 'rowSpan' => 2]);
$form->addElement('select',
                  ['name' => 'pageContext'],
                  ['options' => $options, 'rowClass' => 'clear']);
$form->addButton('cancel', text(17));
$form->addButton('validate', text(18));

$form->validate('validateNewText');
$page->setContent($form->render())->render();
//============================================ end of MAIN =============================================//
//======================================================================================================//



//======================================================================================================//
//=========================================== FUNCTIONS ================================================//
function validateNewText($result, $form)
{
	$posts = $form->getPostedData();

	if (isset($posts->text))
	{
		// Check posted data.
		if ($form->checkElements(['text[en]', 'text[fr]', 'context']))
		{
			$db = database::getInstance();
			$q = $db->query();

			// Do not perform the insertion in db if last insert is same (prevent send twice on page refresh).
			$isInDB = $q->checkLastInsert('texts', [$q->col('text_en'), $q->col('text_fr'), $q->col('context')], $posts->text->en.$posts->text->fr.$posts->context);
			if ($isInDB)
			{
				new Message(text(42), 'error', 'error', 'content');
				unset($posts->text);
			}
			else
			{
				$q->insert('texts', ['text_en' => $posts->text->en,
					                 'text_fr' => $posts->text->fr,
					                 'context' => $posts->context]);
				$q->run();
				if ($q->info()->affectedRows)
				{
					new Message(textf(16, $q->info()->insertId), 'valid', 'success', 'content');
					$form->unsetPostedData();
				}
			}
		}
		else new Message(text(41), 'error', 'error', 'content');
	}
}
//========================================== end of FUNCTIONS ==========================================//
//======================================================================================================//
?>