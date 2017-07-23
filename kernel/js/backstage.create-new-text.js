/**
 * This script contains backstage createnewtext behaviors only.
 * The createnewtextReady() function is called after commonReady() when DOM is ready.
 */

var createnewtextReady = function()
{
	$('#form1pagecontext8opt14').on('change', function()
	{
		$(this).parent().prev().children().val($(this).val());
	});
};


