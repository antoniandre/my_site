<?php
//======================= VARS ========================//
$page = Page::getInstance();
//=====================================================//


//===================== INCLUDES ======================//
//=====================================================//


//======================================================================================================//
//============================================= MAIN ===================================================//
$settings = Settings::get();
$tpl = new Template();
$tpl->set_file("$page->page-page", "backstage/templates/$page->page.html");
$tpl->set_block("$page->page-page", 'latestArticlesBlock', 'theLatestArticlesBlock');
$tpl->set_block('latestArticlesBlock', 'articleBlock', 'theArticleBlock');
$tpl->set_block("$page->page-page", 'latestArticlesBlockImages', 'theLatestArticlesBlockImages');
$tpl->set_block('latestArticlesBlockImages', 'articleBlockImages', 'theArticleBlockImages');
$tpl->set_var('ROOT', $settings->root);

// Disable breadcrumbs on home page only.
// $page->setBreadcrumbsVisiblity(false);

// Disable h1 title on home page only.
$page->setH1(null);

$page->setHeaderHeight(100);

// Get all the articles at once and all but the 5 firsts.
// So loading more with the button does not take a server call.
$articles = Article::getMultiple([/*'limit' => 12, */'fetchStatus' => ['coming soon', 'published'], 'fetchTags' => true]);
renderArticles($articles, 5);

$content = $tpl->parse('display', "$page->page-page");
//============================================ end of MAIN =============================================//
//======================================================================================================//


/**
 * Render the articles list.
 *
 * @param  Array $articles: An array of objects.
 * @param  Array $lazyload: can be null to disable or an integer representing the number to
 							display per click. Useful for large size images.
 * @return String: the output html.
 */
function renderArticles($articles, $lazyload = null)
{
	global $tpl;
	$settings = Settings::get();
	$language = Language::getCurrent();
	$latestArticlesUsePictures = $settings->latestArticlesUsePictures;

	if (count($articles))
	{
		$k = 0;
		foreach ($articles as $article)
		{
			$created = new DateTime($article->created);

            $articleTags = '';

			// If article tags are set.
            if (isset($article->tags))
            {
                $articleTags .= '<div class="article-tags clear">';
                foreach ($article->tags as $tagId => $tag)
                {
                    $articleTags .= "<span class='article-tag' data-id='$tagId'>{$tag->{"text$language"}}</span>";
                }
                $articleTags .= '</div>';
            }

			$tpl->set_var(['view'          => $latestArticlesUsePictures ? 'images' : 'list',
                           'articleLink'   => $article->status === 'published' ? url($article->page) : 'javascript:null;',
                           'hidden'        => $latestArticlesUsePictures && $lazyload && $k >= $lazyload ? ' hidden' : '',
                           'articleTitle'  => $article->title,
                           'comingSoon'    => $article->status === 'coming soon' ? ' data-comingsoon="'.text('Coming soon').'"' : '',
						   'articleTags'   => $articleTags,
						   'publishedByOn' => text(21, [
								   					    'contexts' => 'article',
								   						'formats'  =>
								   						[
								   							'sprintf' =>
								   							[
						   										$article->author,
															  	$created->format($language == 'fr' ? 'd/m/Y' : 'Y-m-d'),
															 	$created->format($language == 'fr' ? 'H\hi' : 'H:i')
															]
														]
													   ])
						   ]);
			if ($latestArticlesUsePictures)
			{
				$imgClass = 'img'.$article->id;
				$image = preg_replace('~(u(?=ploads)|i(?=mages))(?:mages|ploads)%2F~',
									  $settings->root.'images/?$1=',
									  urlencode($article->image));
				$tpl->set_var(['articleImgSrc'  => $image,
					           'imgClass'       => $imgClass,
					           'loadMoreButton' => $lazyload ? '<button class="more-articles i-plus" data-load="'.$lazyload.'">'
							   								   .text('Charger plus d\'articles').'</button>' : '']);
				$tpl->parse('theArticleBlockImages', 'articleBlockImages', true);
			}
			else
			{
				$tpl->set_var('theArticleBlockImages', '');
				$tpl->parse('theArticleBlock', 'articleBlock', true);
			}

			$k++;
		}

		$tpl->set_var(['latestArticlesTitle' => text('Derniers articles'),
					   'theLatestArticlesBlock'.($latestArticlesUsePictures ? '' : 'Images') => '']);
		$tpl->parse('theLatestArticlesBlock'.($latestArticlesUsePictures ? 'Images' : ''),
				    'latestArticlesBlock'.($latestArticlesUsePictures ? 'Images' : ''),
					true);
	}
	else $tpl->set_var(['theLatestArticles' => '', 'theLatestArticlesImages' => '']);

	return $tpl->get_var('theArticleBlockImages');
}
?>