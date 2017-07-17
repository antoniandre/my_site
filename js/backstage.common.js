/*
 * This script contains backstage behaviors only.
 * The backstageReady() function is called after commonReady() when DOM is ready.
 */

/**
 * backstageReady.
 */
var backstageReady = function()
{
	// Create a new text.
	if ($('#createNewTextPage').length)
	{
		$('#form1pagecontext8opt15').on('change', function()
		{
			$(this).parent().prev().children().val($(this).val());
		});
	}

	// Create a new page.
	else if ($('#createNewPagePage').length)
	{
		updateUrlFromTitle();

		$('.panes').on('click', '.pane .title', function()
		{
			$(this).parents('.pane').children('.inner').slideDown(400, 'easeInOutQuad').end()
				   .siblings('.pane').children('.inner').slideUp(300, 'easeInOutQuad');
		}).find('.pane').not(':first').children('.inner').hide();
	}

	// Edit a page.
	else if ($('#editAPagePage').length)
	{
		loadStyleSheet('article');
		bindEvents();
		saveWithKeyStroke();

		// If url ends with '#load/page-id', load the given page via ajax.
		if (window.location.hash !== undefined && window.location.hash.indexOf('#load/') === 0)
		{
			var page = window.location.hash.replace('#load/', '');
			if (page) $('[name="form1[page][selection]"]').val(page).trigger('init');
		}
	}
};

/**
 * This function is called once on load + every time a new page to edit is selected.
 *
 * @return void.
 */
var bindEvents = function()
{
	updateUrlFromTitle();

	// When selecting a page, load it via ajax.
	//!\\ Actually the whole form is replaced, so rebind events.
	$('[name="form1[page][selection]"]').on('change init', function(e)
	{
		var el = $(this);
		$.getJSON(document.url, 'fetchPage='+el.val(), function(data)
		{
			window.location.hash = '#load/'+el.val();
			el.parents('form').parent().replaceWith(data.html);

			if (data.message) setMessage(data.message, 'info', 'info', 'header', [0, null]);
			setTimeout(function(){formReady();bindEvents();}, 0);
		});
	});

	// Save form via ajax after treating pics if any.
	$('#form1').on('submit', function(e)
	{
		e.preventDefault();

		var form = $(this);
		if (form.find('figure').length)
		{
			cleanFigures(function()
			{
				$('textarea.wysiwyg').redactor('code.sync');
				setTimeout(function()
				{
					submitViaAjax(form);
					// form.off('submit').trigger('submit')
				}, 200);
			});
		}
		else submitViaAjax(form);
	});
};

/**
 *
 */
var updateUrlFromTitle = function()
{
	$('.pageTitle').on('keyup', function()
	{
		var $el = $(this),
			pageTitle = $el.val()
						.toLowerCase()
						.toLatinChars()
						.replace(/ /g, '-')
						.replace(/[^a-z0-9-_]/g, '')
						.replace(/---+/g, '--');
		$(this).parents('.row').next().find('.pageUrl').val(pageTitle);
	});
};

/**
 *
 */
var submitViaAjax = function(form)
{
	var $waitingMessage = setMessage('Saving data...', 'info', 'info', 'header', [0, null]);
	$.post(form.attr('action'), form.serialize()+'&task=save', function(response)
	{
		// In localhost the request rountrip is so short that we need to wait for the message
		// to be first appended before removing it!
		setTimeout(function(){$($waitingMessage).stop(true, true).slideUp(300, function(){$(this).remove();});}, 100);
		setMessage('Succesfully saved! ;)', 'valid', 'success', 'header', [0, 1000]);
	})
};

/**
 *
 */
var saveWithKeyStroke = function()
{
	// Bind events
	$(document).on('keydown', function(e)
	{
		// console.log(e.ctrlKey,e.metaKey,e.which);

	    // Check for the Ctrl+S key combination
	    // On Windows: ctrl pressed implies e.ctrlKey= true & e.metaKey= false,
	    // On Mac: command pressed implies e.ctrlKey= false & e.metaKey= true,
	    //         ctrl pressed implies e.ctrlKey= true & e.metaKey= false
	    // ctrl key= 17
	    if ((e.ctrlKey || e.metaKey) && e.which=== 83)
	    {
	        e.preventDefault();
	        $('#form1').submit();
	        return false;
	    }
	});
};

/**
 *
 */
var cleanFigures = function(callback)
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

		// Leftover editor removal.
		fig.filter('.edit').removeClass('edit').children('.editPanel').remove();

		if (callback && i+1 >= length) callback();
	});
};


