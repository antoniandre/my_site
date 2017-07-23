/**
 * This script contains contact behaviors only.
 * The homeReady() function is called after commonReady() when DOM is ready.
 */

var homeReady = function()
{
	$('.more-articles').on('click', function(e)
	{
		e.preventDefault();

		var lazyloadCount       = $(this).attr('data-load'),
		    hiddenArticles      = $(this).siblings('.hidden'),
		    hiddenArticlesCount = hiddenArticles.length,
			hideButton          = hiddenArticlesCount <= lazyloadCount;

		if (hiddenArticlesCount)
		{
			var filtered = hiddenArticles.filter(function(index, self){return index <= lazyloadCount;});
			filtered.toggleClass('hidden load-hidden');
			setTimeout(function(){filtered.toggleClass('load-hidden')}, 10);
			setTimeout(function()
			{
				$('.latest-articles .bg').lazyload({update: true, load:function(){$(this).addClass('loaded')}});
			}, 1500);
		}
		if (hideButton) $(this).fadeOut(500);

		/*var btn = this;
		$.getJSON(document.url, 'getOldArticles', function(data)
		{
			$(btn).replaceWith(data.html);
			$('.latest-articles .bg').lazyload({update: true, load:function(){$(this).addClass('loaded')}});
		});*/
	});
};
