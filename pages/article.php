<?php
//======================= VARS ========================//
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$db = database::getInstance();
$q = $db->query();
$q->select('articles', [$q->col("content_$language"), $q->col('created'), $q->col('author'), $q->col('published')])
  ->where()->col('id')->eq($page->article);
$article = $q->run()
            ->loadObject();
$tpl = new Template('.');
$tpl->set_file("$page->page-page", "backstage/templates/article.html");
if ($article && $article->published)
{
	$created = new DateTime($article->created);
	$tpl->set_var(['content'=> $article->{"content_$language"},
				   'created'=> text(21,
				   					[
				   					    'contexts' => 'article',
				   						'formats' =>
				   						[
				   							'sprintf' =>
				   							[
		   										'author',
											  	$created->format('Y-m-d'),
											 	$created->format($language == 'fr' ? 'H\hi' : 'H:i')
											]
										]
									])
				   ]);
}
else
{
	if (!$article->published) new Message(text(37), 'info', 'info', 'content');
	$tpl->set_var(['content' => 'No content.',
				   'created' => '']);
}
$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//
?>