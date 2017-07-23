/**
 * This script contains backstage database page behaviors only.
 * The databaseReady() function is called after commonReady() when DOM is ready.
 */

$.fn.selectText = function()
{
    this.find('input').each(function()
    {
        if ($(this).prev().length == 0 || !$(this).prev().hasClass('p_copy'))
        {
            $('<p class="p_copy" style="position: absolute; z-index: -1;"></p>').insertBefore($(this));
        }
        $(this).prev().html($(this).val());
    });
    var doc = document,
    	element = this[0];
    if (doc.body.createTextRange)
    {
        var range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    }
    else if (window.getSelection)
    {
        var selection = window.getSelection(),
        	range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};

var reallyReady = function()
{
	var mainTable = $("#mainTable");

	// Example of scrollbar use:
	//loadScript('perfect-scrollbar', function(){$('#tableWrapper').css({height:1000, overflow:'hidden', position:'relative'}).perfectScrollbar();});

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
	        $('#save').click();
	        return false;
	    }
		// Check for the Ctrl+C key combination when lightbox is open
	    else if ((e.ctrlKey || e.metaKey) && e.which === 67 && $('#lightbox.show').length)
		{
			// setTimeout 200ms for the copy action to be done before changing the content.
			setTimeout(function(){$('#lightbox .content').html('<em class="perfect"><span class="i-accept"></span>Perfect. Good job!</em>');}, 200);
			setTimeout(function(){$('#lightbox').removeClass('show')}, 2000);
		}
		// Check for the echap key when lightbox is open
	    else if (e.which === 27 && $('#lightbox.show').length)
		{
			$('#lightbox').removeClass('show');
		}
	});

	mainTable
		// Syntax to also target future elements, after appending multiple new rows (first append is already in DOM but hidden)
		.on('click', 'tr.compact td.handle ~ td', function()
		{
			$(this).siblings('.handle').find('.toggle').click();
		})
		.on('click', 'label', function()
		{
			if ($(this).prev(':checkbox').length) $(this).prev(':checkbox').click();
			else if ($(this).parent().prev(':checkbox').length) $(this).parent().prev(':checkbox').click();
		})
		.on('change', '.handle .toggle', function(event)
		{
			var tr = $(this).parents('tr'), height = 10;
			if (!tr.hasClass('compact'))
			{
				tr.data('fullHeight', tr.outerHeight());
			}
			else height = tr.data('fullHeight');

			tr.toggleClass('compact').find('td > div').animate({'height': height}, 700, 'easeInOutQuad', function()
			{
				if (!tr.hasClass('compact')) $(this).removeAttr('style');
			});
		})
		.on('input change', '[type="range"]', function(event)
		{
			$(this).next().text(this.value);
		})
		.on('input change', '.devtu input, .devtu_f input', function()
		{
			updateProfit($(this).parents('tr'));
			updateCompletion($(this).parents('tr'));
		})
		.on('change init', '.completion input', function()
		{
			var completion = parseInt($(this).val());
			if (completion == 100) $(this).parents('tr').removeClass('inprogress').addClass('complete');
			else if (completion > 0 && $(this).next().text() !== '-') $(this).parents('tr').removeClass('complete').addClass('inprogress');
			else $(this).parents('tr.complete,tr.inprogress').removeClass('inprogress complete');
			updateProfit($(this).parents('tr'));
		})

		// Tasks cell events
		.on('input change', 'input[type="number"]', function()
		{
			updateDevtu($(this).parents('tr'));
			updateCompletion($(this).parents('tr'));
		})
		.on('change', 'td .tasks input[type="checkbox"]', function()
		{
			var value = 0;
			// Checkbox states are: unchecked, checked, checked gift or checked cancel.
			switch(parseInt($(this).attr('data-value')))
			{
				// Unchecked.
				case 0:value = 1;this.checked = false;break;

				// InProgress.
				case 1:value = 2;/*this.checked= true; Automatic so useless*/break;

				// Checked.
				case 2:value = 3;this.checked= true;break;

				// Checked gift.
				case 3:value = -1;this.checked= true;break;

				// Canceled.
				case -1:value= 0;/*this.checked= false; Automatic so useless*/break;
			}
			$(this).attr('data-value', value);

			updateDevtu($(this).parents('tr'));
			updateCompletion($(this).parents('tr'));
		})
		.on('click', '.newTask', onNewTaskClick)
		.on('click', '.newTitle', function(event)
		{
			event.preventDefault();
			var newTitle = $('<div class="title"><span class=\"handle\"></span><textarea>Title</textarea></div>');
			$(this).siblings('.tasks').append(newTitle);
			onNewTaskClick.call(this);
			newTitle.find('textarea').select().focus();
		})


	// Misc init process
	.find('td.tasks > div').append('<button class="newTask">+</button><button class="newTitle">+</button>').end()
	.find('.completion input').trigger('init').end()

	// Init sortable
	.find("tbody")
		.sortable(
		{
			placeholder: 'placeholder',
			items: "tr:not(:first)",
			connectWith: '#trash',
			handle: "td.handle",
			tolerance: "pointer",
			over: function(event, ui){$('#trash.active').removeClass('active');},
			update: function(event, ui){$('#trash.active').removeClass('active');}
		})
		.disableSelection();
	makeTasksSortable();
	$("#trash")
		.sortable(
		{
			over: function(event, ui){$(this).addClass('active');},
			receive: function(event, ui)
			{
				var count= $(this).children().length-1;
				$(this).find('.count').text(count);
				count? $(this).addClass('full') : $(this).removeClass('full');
			},
			activate: function(event, ui){$('#trashWrapper').addClass('show');},
			deactivate: function(event, ui){$('#trashWrapper').removeClass('show');},
			tolerance: "pointer"
		})
		.disableSelection();

	// Button events
	mainTable.siblings('.buttons')
	.find('#toggleAll')
		.on('click', function()
		{
			$(this).hasClass('i-eye-close')? $('tr.compact').removeClass('compact') : $('tr:not(.compact)').addClass('compact');
			$(this).attr('class', $(this).hasClass('i-eye-close')? 'i-eye' : 'i-eye-close');
			$('.i-minus').click();
		}).end()
	// Add a new row
	.find('#newRow')
		.on('click', function()
		{
			mainTable.find('tr:last-child').removeClass('hidden').clone().addClass('hidden').appendTo('table');
			setTimeout(function(){makeTasksSortable()}, 200);
		}).end()
	// Copy to the clipboard
	.find('#copyToClipboard')
		.on('click', function(event)
		{
			event.preventDefault();
			performCopy();
		}).end()
	// Archive a row or save the document
	.find('#save').add(mainTable.find('button.archive'))
		.on('click', function()
		{
			var i = 0, rows = '[',
				clickedRow = $(this).is('button.archive')? {object:$(this).parents('tr'), data: null} : null;
			if (clickedRow && !confirm('Are you sure you want to '+($('body.archive').length? 'unarchive' : 'archive')+' this row?')) return false;

			// Loop on table rows.
			$('#mainTable tr:not(:first-child):not(.hidden)').each(function(k, currTr)
			{
				var rowTasks = [],
					subject = $(currTr).find('.subject input[type="text"]').val();
				if (subject) rowTasks.push({subject:subject});

				// Loop on table row tasks.
				$(currTr).find('.tasks textarea').each(function(l, currTask)
				{
					if (currTask.value)
					{
						rowTasks.push({done:$(currTask).siblings('[type="checkbox"]').attr('data-value'), text:currTask.value, charge:$(currTask).next().val()});
					}
				});

				// Only save the row if some task is defined in it.
				if (rowTasks.length)
				{
					var row = '';

					row += '[';
					$(currTr).find('td:not(.noContent)').each(function(j, currTd)
					{
						row += j? ',' : '';
						if ($(currTd).hasClass('tasks'))
						{
							row += JSON.stringify(rowTasks);
						}
						else
						{
							row += '"';
							if ($(currTd).find('input[type=range]').length) row+= $(currTd).find('input[type=range] + span').text();
							else row += $(currTd).find('select,input,textarea').length? $(currTd).find('select,input,textarea').val() : $(currTd).text();
							row += '"';
						}
					});
					row += ']';

					if (clickedRow && $(currTr).is(clickedRow.object)) clickedRow.data= row;
					else
					{
						rows += (i? ',' : '')+row;
						i++;
					}
				}
			});
			rows += ']';

			$.post(document.url, {rows:rows, archive:clickedRow? clickedRow.data : ''}, function(data)
			{
				data = JSON.parse(data);
				if (clickedRow)
				{
					clickedRow.object.toggleClass('compact').find('td > div').animate({'height':0}, 700, 'easeInOutQuad', function()
					{
						clickedRow.object.remove();
						showMessage(data.message);
					});
				}
				else showMessage(data.message);
			});
		});
},


updateProfit = function(tr)
{
	if (tr.hasClass('complete'))
	{
		var devtu = tr.find('.devtu input').val(),
			devtu_f = tr.find('.devtu_f input').val(),
			profit = Math.max(Math.round((1-devtu_f/devtu)*100), -100),
			profitClass = profit < 0 ? 'red' : (profit < 15 ? 'orange' : 'green');
		if (profit)
		{
			tr.filter('.complete').find('td.handle')
				.removeClass('green orange red')
				.attr('data-profit', (profit> 0 ? '+'+profit : profit)+'%')
				.addClass('profit i-tag '+profitClass);
			return;
		}
	}
	tr.find('td.profit').removeClass('profit i-tag green orange red');
},


updateCompletion = function(tr)
{
	var devtu = parseFloat(tr.find('.devtu input').val()),
		tasksSum = 0,
		completion = 0;
	devtu = isNaN(devtu) || !devtu ? 0 : devtu;
	tr.find('.tasks :checked[data-value="2"] ~ input[type="number"]:visible').each(function(i, curr)
	{
		tasksSum += parseFloat(isNaN(curr.value) || !curr.value ? 0 : curr.value);

	});
	// If dev/tu is undefined, Set completion to 0 to prevent division by 0.
	completion = devtu ? Math.round(  (1-parseFloat(devtu-parseFloat(tasksSum))/devtu)  *100) : 0;
	tr.find('.completion input').val(completion).trigger('change')
		.siblings('span').html(completion);
},


updateDevtu = function(tr)
{
	var devtu = tr.find('.devtu input'),
		devtuCalc = 0;
	tr.find('.tasks :checkbox:not([data-value="3"],[data-value="-1"]) ~ input[type="number"]:visible').each(function(i, curr)
	{
		devtuCalc += parseFloat(isNaN(curr.value) || !curr.value ? 0 : curr.value);
	});
	devtu.val(Math.round(devtuCalc*10)/10);
},


performCopy= function()
{
	var tasks= '';
	$('tr:not(.compact) .tasks textarea:visible').each(function(i, curr)
	{
		if ($(curr).parent().hasClass('title') || $(curr).is(':checkbox[data-value="0"] ~ *,:checkbox[data-value="1"] ~ *,:checkbox[data-value="2"] ~ *'))
			tasks += (i? '\n' : '')+($(curr).parent().is(':first-child')? '\n' : '')+'<tr><td class="cell">'
					 +($(curr).parent().hasClass('title') ? '<strong>'+curr.value+'</strong>' : curr.value)
					 +'</td>\t<td class="cell number">'+($(curr).next().val() ? $(curr).next().val() : '')+'</td></tr>';
	});

	$('#lightbox').find('.inner').html('<p class="title">Press Ctrl+C to copy to clipboard!</p><div class="content"><br /><table>'+tasks+'</table></div>').end().addClass('show');
	$('#lightbox .inner table').selectText();
},


onNewTaskClick = function()
{
	var newTask = $(this).siblings('.tasks').children('.hidden').clone().removeClass('hidden');
	$(this).siblings('.tasks').append(newTask);
	newTask.find('textarea').focus();
},


showMessage = function(message)
{
	$('#messageWrapper').addClass('show').children('#message').html(message);
	setTimeout(function(){$('#messageWrapper').removeClass('show')}, 3000);
},


makeTasksSortable = function()
{
	$("#mainTable").find("tr div.tasks").sortable(
	{
		placeholder: 'placeholder',
		//items: ":not(:first)",
		connectWith: '#trash',
		handle: ".handle",
		tolerance: "pointer",
		over: function(){$('#trash.active').removeClass('active');},
		update: function(){$('#trash.active').removeClass('active');}
	}).disableSelection();
},

todolistReady = function(){loadScript('jquery.ui', reallyReady)};
//=====================================================================================================//

