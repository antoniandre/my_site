/**
 * This script contains backstage behaviors only.
 * The backstageReady() function is called after commonReady() when DOM is ready.
 */

var backstageReady = function()
{
	if ($('#createNewTextPage').length)
	{
		$('#form1pagecontext8opt15').on('change', function()
		{
			$(this).parent().prev().children().val($(this).val());
		});
	}

	if ($('#createNewPagePage').length)
	{
		$('.panes').on('click', '.pane .title', function()
		{
			$(this).parents('.pane').children('.inner').slideDown(400, 'easeInOutQuad').end()
				   .siblings('.pane').children('.inner').slideUp(300, 'easeInOutQuad');
		}).find('.pane').not(':first').children('.inner').hide();
	}

	else if ($('#editAPagePage').length)
	{
		loadStyleSheet('article');
		$('[name="form1[page][selection]"]').on('change', function(e)
		{
			var el = $(this);
			$.getJSON(document.url, 'fetchPage='+el.val(), function(data)
			{
				el.parents('form').parent().replaceWith(data.html);
				setTimeout(function(){formReady();backstageReady();}, 0);
			});
		});
		$('#form1').on('submit', function(e)
		{
			var form = $(this);
			e.preventDefault();
			cleanFigures(function()
			{
				$('textarea.wysiwyg').redactor('code.sync');
				setTimeout(function(){form.off('submit').trigger('submit')}, 200);
			});
		});
	}
};

cleanFigures = function(callback)
{
	var figs = $('article figure'),
		length = figs.length;
	$('article figure').each(function(i, curr)
	{
		var fig = $(curr).removeAttr('rel'),
			figWidth = fig[0].style.width || '450px',
			figWidthIsPercent = figWidth.indexOf('%') > -1,
			img = fig.find('img'),
			iframe = fig.find('iframe'),
			caption = fig.find('caption'),
			captionText = caption.html();

		// Img/iframe cleanup.
		if (img.length) img.attr({width: figWidthIsPercent ? figWidth : parseInt(figWidth), height: parseInt(img.height())}).removeAttr('rel');
		if (iframe.length) iframe.attr({width: figWidthIsPercent ? figWidth : parseInt(figWidth), height: parseInt(iframe.height())}).removeAttr('rel');

		// Caption cleanup.
		if (!captionText) caption.remove();

		// <br> cleanup.
		fig.children('br').remove();

		if (callback && i+1 >= length) callback();
	});
};


