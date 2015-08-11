/**
 * This script contains backstage behaviors only.
 * The backstageReady() function is called after commonReady() when DOM is ready.
 */

var backstageReady = function()
{
	if ($('#createNewTextPage').length)
	{
		$('#pageContext').on('change', function()
		{
			cl($(this).val());
			$(this).prev().val($(this).val());
		});
	}

	if ($('#createNewPagePage').length)
	{
		loadScript('dropzone', function()
		{
			Dropzone.autoDiscover = false;
			$(".Dropzone").addClass('dropzone').dropzone({ url: $(".Dropzone").parents('form').attr('action')});
		});

		$('#pageTypeArticle').on('change', function()
		{
			if ($(this).is(':checked')) $('.pane:eq(1) .title').trigger('click');
		});
		$('.panes').on('click', '.pane .title', function()
		{
			$(this).parents('.pane').children('.inner').slideDown(400, 'easeInOutQuad').end()
				   .siblings('.pane').children('.inner').slideUp(300, 'easeInOutQuad');
		}).find('.pane').not(':first').children('.inner').hide();
		loadScript('redactor', function()
		{
			$('.articleContent').redactor(
			{
				fixed: true,
				/*autosave: window.location,
				interval: 30,
				autosaveCallback: function(data, redactor_obj)
				{
					cl(data);
				}*/
			});
		});
	}
};