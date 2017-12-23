// This function will be called each time you change the page to edit in dropdown.
var contentEditor,
onContentEditorSave = function(ev)
{
    var name, payload, regions, xhr;

    // Check that something changed
    regions = ev.detail().regions;
    if (Object.keys(regions).length == 0) {
        return;
    }

    // Set the editor as busy while we save our changes
    this.busy(true);

    // Collect the contents of each region into a FormData instance
    payload = new FormData();
    for (name in regions) {
        if (regions.hasOwnProperty(name)) {
            payload.append(name, regions[name]);
        }
    }

    // Send the update content to the server to be saved
    function onStateChange(ev) {
        // Check if the request is finished
        if (ev.target.readyState == 4) {
            contentEditor.busy(false);
            if (ev.target.status == '200') {
                // Save was successful, notify the user with a flash
                new ContentTools.FlashUI('ok');
            } else {
                // Save failed, notify the user with a flash
                new ContentTools.FlashUI('no');
            }
        }
    };

    xhr = new XMLHttpRequest();
    xhr.addEventListener('readystatechange', onStateChange);
    xhr.open('POST', '/save-my-page');
    xhr.send(payload);
},

formReady = function()
{
	// Using jQuery Dropzone plugin: http://www.dropzonejs.com/#installation
    if ($(".Dropzone").length) loadScript('dropzone:v', function()
	{
        loadStyleSheet('dropzone:v')
		var formAction = $(".Dropzone").parents('form').attr('action');
		Dropzone.autoDiscover = false;

		$(".Dropzone").addClass('dropzone').dropzone(
		{
			url: formAction+'?upload=1',
			method: 'post',
			uploadMultiple: true,
			// maxFiles: 1,
			// acceptedFiles: "image/*,application/pdf,.psd",
			// acceptedFiles: ".doc,.docx",// Also work.
			// maxFilesize: 0,// in mb
			paramName: $(".Dropzone").attr('data-name').replace('[]', ''),
			addRemoveLinks: true,
			dictRemoveFile: '',
			// dictDefaultMessage: 'Drop files here',
			removedfile: function(file){$(file._removeLink).trigger('click');}
		});

		$('.dropzone').on('click', '.dz-remove', discardUpload);
		$('.discard-all').on('click', function()
		{
			$.get(formAction, 'discardAllUploads', function(response)
			{
				$('.dz-preview').remove();
			});
		});
		$('.add-images-to-article').on('click', function()
		{
			$.ajax(
			{
			    type: 'GET',
			    // dataType: 'json',
			    url: formAction,
			    data: 'addImagesToArticle',
			    cache: false,
				/*complete: function(response)
				{
				},*/
				success: function(response)
				{
					$('textarea.wysiwyg').redactor('insert.html', response.html);
					$('.dz-preview').remove();
				},
				beforeSend: function(response)
				{
					$('.dropzone').after('<div id="progress" style="display: none;">'
										 +'<div class="progress-bar">'
											+'<div class="inner" style="width:0"/>'
											+'<div class="percentage">0%</div>'
										 +'</div></div>');
					$('#progress').slideDown(400);
					setTimeout(trackProgress, 700);
				}
			});
		});
	});

	if ($('.wysiwyg').length)
	{
        loadScript('content-tools/content-tools.min:v', function()
        {
            loadStyleSheet('content-tools/content-tools.min:v');
            contentEditor = ContentTools.EditorApp.get();
            ContentTools.StylePalette.add([
                new ContentTools.Style('Author', 'author', ['p'])
            ]);
            contentEditor.init('*[data-editable]', 'data-name');
            contentEditor.addEventListener('saved', onContentEditorSave);
        });
		/*loadScript('redactor:v', function()
		{
            loadStyleSheet('redactor:v');
			new editPanel();

            $('textarea.wysiwyg').each(function(i, curr)
            {
                $(curr).redactor(
                {
                    // fixed: true,
                    imageUpload: ROOT + 'uploads/',
                    imageEditable: false,
                    // linebreaks: true,
                    paragraphize: false,
                    replaceDivs: false,
                    focus: true,
                    toolbarFixedTopOffset: $('#sticky-bar').height(),
                    formatting: ['p', 'blockquote', 'h2', 'h3'],
                    formattingAdd: [
                    {
                        tag: 'p',
                        title: 'Paragraph: force align left',
                        class: 'left'
                    },
                    {
                        tag: 'mark',
                        title: 'marked',
                        class: 'marked'
                    },
                    {
                        tag: 'ul',
                        title: 'ul glyph',
                        class: 'glyph'
                    }],
                    plugins: ['youtube']
                    /*autosave: window.location,
                    interval: 30,
                    autosaveCallback: function(data, redactor_obj)
                    {
                        cl(data);
                    }*\/
                });
            });
		});*/
	}

	// Handle show/hide state of a form element if a data-toggle attribute is set (in form definition in PHP file).
	if ($('[data-toggle]').length)
	{
		// Indexed array of elements that will trigger a show/hide on another element on onChange event.
		var togglers = {};

		// First look at each element that has a data-toggle attribute to fill an indexed array with conditions,
		// elements to toggle, and toggler attribute name.
		// The purpose of first creating an array is to get only one onChange event per toggler.
		$('[data-toggle]').each(function(i, el)
		{
			var toggle = $(el).attr('data-toggle'),// The toggle state: show/hide.
				cond = $(el).attr('data-toggle-cond').split('='),// Condition to show/hide the element.
				togglerNameAttr = cond[0],
				condition = cond[1],
				effect = $(el).attr('data-toggle-effect') || null;

			// Save togglers and associated conditions in 'togglers' indexed array.
			// A toggler may have multiple conditions (one per element to toggle).
			if (!togglers[togglerNameAttr]) togglers[togglerNameAttr] = [];
			togglers[togglerNameAttr].push({conditionValue:condition, element: $(el), toggle: toggle, effect: effect});
		});

		// Walk through the ready 'togglers' array (set in the php form) to perform toggle on appropriate elements.
		for (var togglerName in togglers)
		{
			$('[name="'+togglerName+'"]').on('change', function(e)
			{
				toggle(togglerName);
			})
			toggle(togglerName);
		};
		function toggle(togglerName)
		{
			var toggler = $('[name="'+togglerName+'"]:checked').val();

			// Loop through the indexed array.
			$(togglers[togglerName]).each(function(j, el)
			{
				var $el = $(el.element);

				var effectIn,
					effectOut,
					effectDuration = 0;

				switch (el.effect)
				{
					case 'slide':
						effectIn = 'slideDown';
						effectOut = 'slideUp';
						effectDuration = 500;// ms.
					break;
					case 'fade':
						effectIn = 'fadeIn';
						effectOut = 'fadeOut';
						effectDuration = 500;// ms.
					break;
					default:
						effectIn = 'show';
						effectOut = 'hide';
					break;
				}

				// Apply the toggle if the condition is fulfilled.
				// String(toggler) to also match the 'undefined' case.
				if (String(toggler) == el.conditionValue) $el[el.toggle == 'show' ? effectIn : effectOut](effectDuration);
				else $el[el.toggle == 'hide' ? effectIn : effectOut](effectDuration);
			});
		}
	}

    if ($('.robot-check').length)
    {
		robotCheck();
    }
},
trials = 0,
lastProgress = 0,
trackProgress = function()
{
	$.getJSON(window.location, 'ajaxTrackProgress=1', function(response)
	{
		var progress = response.progress;
		$('#progress .progress-bar .inner').css('width', progress+'%').siblings('.percentage').text(progress+'%');

		if (progress < 100 && (lastProgress != progress || (lastProgress == progress && trials < 10)))
		{
			setTimeout(trackProgress, 700);
			trials = lastProgress != progress ? 0 : (trials + 1);
		}

		else $('#progress').delay(1000).slideUp(400);
	});
},
discardUpload = function()
{
	var clicked = this,
		formAction = $(".Dropzone").parents('form').attr('action');

	$.get(formAction, 'discardUpload='+$(this).parents('.dz-preview').find('.dz-filename span').text(), function(response)
	{
		$(clicked).parents('.dz-preview').remove();
	});
};


var robotCheck = function()
{
    $('.robot-check label').each(function()
    {
        var form = $(this).parents('form');
    }).one('click', function()
    {
        var form = $(this).parents('form');
        form.append('<input type="hidden" name="'+form[0].id+'[robotCheck]" value="clear" />');
        $(this).parent().addClass('not-robot');
	})
	.parents('form').on('submit', function()
	{
		if (!$('.robot-check').hasClass('not-robot'))
		{
			$('.robot-check').addClass('show');
			return false;
		}
	});
}


var editPanel = function()
{
	var self = this;
	self.panel = null;

	self.createPanel = function()
	{
		return $('<div class="edit-panel">\
				<span class="duplicate i-plus" title="Duplicate"></span>\
				<span class="rotate i-rot-r children_5" title="Rotate">\
					<span class="rotate10ccw i-rot-l" title="Rotate left 10 degrees">10<br>º</span>\
					<span class="rotate5ccw i-rot-l" title="Rotate left 5 degrees">5º</span>\
					<span class="rotate0" title="Rotate left 5 degrees">0º</span>\
					<span class="rotate5cw i-rot-r" title="Rotate right 5 degrees">5º</span>\
					<span class="rotate10cw i-rot-r" title="Rotate right 10 degrees">10º</span>\
				</span>\
				<span class="remove i-x" title="Remove"></span>\
				<span class="addCaption i-pencil" title="Add caption"></span>\
				<span class="likePosition i-thumbup children_4" title="Change likes position">\
					<span class="likeOnTopRight" title="Likes on top right">•</span>\
					<span class="likeOnLeft" title="Likes on left">•</span>\
					<span class="likeOnBottomRight" title="Likes on bottom right">•</span>\
					<span class="noLike" title="Hide likes for this figure">ø</span>\
				</span>\
				<span class="resize i-resize children_5" title="Resize">\
					<span class="size_xs" title="Resize xs">xs</span>\
					<span class="size_s" title="Resize s">s</span>\
					<span class="size_m" title="Resize m">m</span>\
					<span class="size_l" title="Resize l">l</span>\
					<span class="size_xl" title="Resize xl">xl</span>\
				</span>\
				<span class="postcard i-scissors" title="Apply postcard style"/>\
				</div>').hide();
	};

	self.bindEvents = function()
	{
		self.panel.on('click', 'span', function(e)
		{
			var Class = $(e.target).attr('class').replace(/ .*/, ''),// Keep only the first class.
				figure = $(this).parents('figure');
			switch(Class)
			{
				case 'rotate':
					figure.removeClass('rotate10ccw rotate5ccw rotate0 rotate5cw rotate10cw rotate');
					break;
				case 'rotate10ccw':
				case 'rotate5ccw':
				case 'rotate5cw':
				case 'rotate0':
				case 'rotate10cw':
					figure
						.removeClass('rotate10ccw rotate5ccw rotate0 rotate5cw rotate10cw')
						.addClass('rotate '+Class);
					break;
				case 'likeOnTopRight':
				case 'likeOnLeft':
				case 'likeOnBottomRight':
				case 'noLike':
					figure
						.removeClass('likeOnTopRight likeOnLeft likeOnBottomRight noLike')
						.addClass(Class);
					break;
				case 'size_xs':
				case 'size_s':
				case 'size_m':
				case 'size_l':
				case 'size_xl':
					var size = Class.replace('size_', ''),
						image = figure.find('img');

					// First update figure class to new size.
					figure.removeClass('size_xs size_s size_m size_l size_xl').addClass(Class);

					// Then update image source to use the proper size.
					image.attr('src', image.attr('src').replace(/_(xs|s|m|l|xl)\.(jpg|jpeg|png|gif)/, '_'+size+'.$2'));
					image.attr('alt', image.attr('alt').replace(/_(xs|s|m|l|xl)\.(jpg|jpeg|png|gif)/, '_'+size+'.$2'));
					break;
				case 'duplicate':
					figure.after(figure.clone());
					break;
				case 'remove':
					if (confirm('Do you also want to delete the picture file?'))
					{
						cl('TODO: finish this task!');
						$.getJSON(window.location, 'removeImage='+figure.find('img').attr('src'), function(response)
						{
							setMessage(response.message);
						});
					}
					figure.remove();
					break;
				case 'addCaption':
					if (!figure.find('figcaption').length) figure.append('<figcaption>Caption</figcaption>');
					figure.find('figcaption').select();
					break;
				case 'postcard':
					figure.toggleClass('postcard i-scissors');
					break;
			}
		});

		$('article').off()
            .on('mouseenter', 'figure', function()
    		{
    			var figEditPanel = self.panel.clone(true);
    			// bindEditEvents(figEditPanel);
    			$(this).addClass('edit hover').append(figEditPanel);
    			$(this).find('.edit-panel').stop(true, true).toggle('slide', 'easeInOutQuad', {direction:'left'}, 300);
    		})
    		.on('mouseleave', 'figure', function()
    		{
    			var figure = $(this);
    			figure.removeClass('hover').find('.edit-panel').stop(true, true).toggle('slide', 'easeInOutQuad', function()
    			{
    				// Check again if not hover before removing editPanel.
    				if (!figure.hasClass('hover')) $(this).parent().removeClass('edit').end().remove();
    			}, {direction:'left'}, 300);
    		});

        // setTimeout(function(){$('.redactor-editor').sortable({items: 'figure,p', placeholder: 'figure-placeholder', handle: false})}, 1000);
	};

	self.init = function()
	{
		self.panel = self.createPanel();
		self.bindEvents();
	}();
};


var slider = function(targetElement, params)
{
    var self     = this,
        defaults = {value: null, onDrag: null, afterDrag: null, graduation: {}, input: null};

    params = $.extend({}, defaults, params);
    self.target = $(targetElement);
    self.width  = self.target.width();
    self.limits = {left: self.target.offset().left, right: self.target.offset().left + self.width};
    self.handle = null;// Updated on init.
    self.hasGraduation = (params.graduation || {}).from || (params.graduation || {}).to;
    self.position = 0;
    self.graduatedPosition = 0;

    self.bindEvents = function()
    {
        $(window).on('resize', function()
        {
            self.width = self.target.width();
            self.limits = {left: self.target.offset().left, right: self.target.offset().left + self.width};
        });

        if (typeof touchHandler === 'function') self.target.on("touchstart touchmove touchend touchcancel", touchHandler);
        self.target
            .on('click', function(e)
            {
                self.updatePosition(e);
            });
        self.target
            .on('mousedown', function(e)
            {
                self.target.addClass('dragging');
                self.updatePosition(e);

                // Attach mouse move event only when first clicked.
                $(document).on('mousemove', function(e)
                {
                    self.updatePosition(e);
                })
                .one('mouseup', function(e)
                {
                    $(document).off('mousemove');
                    self.target.removeClass('dragging');

                    if (params.input) self.target.find('input').val(params.graduation.to !== undefined ? self.graduatedPosition : self.position);

                    if (typeof params.afterDrag === 'function') params.afterDrag.call(self.target, self.position);
                });
            });
    };

    self.updatePosition = function(e)
    {
        if (e.pageX === undefined && !isNaN(e)) self.position = e;
        else
        {
            var handlePosition = Math.max(Math.min(e.pageX, self.limits.right), self.limits.left);
            self.position = (handlePosition - self.limits.left) / self.width * 100;// Percentage.
        }

        var args = [self.position];

        self.handle.css({left: self.position + '%'});

        // If graduation is set, update the current state.
        if (params.graduation.to !== undefined)
        {
            self.graduatedPosition = Math.round(self.position * params.graduation.to / 100);
            self.handle.find('.graduation').text(self.graduatedPosition);
            args.push(self.graduatedPosition);
        }

        // If a callback function is provided then run it with the self.target as 'this' argument for use in the callback function.
        if (typeof params.onDrag === 'function') params.onDrag.call(self.target, ...args);
        else if (params.onDrag && typeof window[params.onDrag] === 'function') window[params.onDrag].call(self.target, ...args);
    };

    self.init = function()
    {
        self.target.append
        (
            (params.input ? '<input type="hidden" name="' + params.input + '" value="' + (params.value || 0) + '">' : '')
          + (params.graduation.from !== undefined ? '<span class="graduation from">' + params.graduation.from + '</span>' : '')
          + (params.graduation.to !== undefined ? '<span class="graduation to" data-unit="' + (params.graduation.unit || '') + '">' + params.graduation.to + '</span>' : '')
          + '<span class="handle">' + (self.hasGraduation ? '<span class="graduation" data-unit="' + (params.graduation.unit || '') + '">0</span>' : '') + '</span>'
        );
        self.handle = self.target.find('.handle');

        // If a callback function is provided then run it with the self.target as 'this' argument for use in the callback function.
        if (typeof params.onInit === 'function') params.onInit.call(self.target);
        else if (params.onInit && typeof window[params.onInit] === 'function') window[params.onInit].call(self.target);

        if (params.value !== null)
        {
            // Convert from graduated value to percentage for positionning the handle.
            self.position = params.graduation.to ? params.value * 100 / params.graduation.to : params.value;
            self.updatePosition(self.position);
        }

        self.bindEvents();
    }();
};


var touchHandler = function(event)
{
    var touch = event.hasOwnProperty('changedTouches') ? event.changedTouches[0] : event.originalEvent.changedTouches[0];

    var simulatedEvent = document.createEvent("MouseEvent");
        simulatedEvent.initMouseEvent({
        touchstart: "mousedown",
        touchmove: "mousemove",
        touchend: "mouseup"
    }[event.type], true, true, window, 1,
        touch.screenX, touch.screenY,
        touch.clientX, touch.clientY, false,
        false, false, false, 0, null);

    touch.target.dispatchEvent(simulatedEvent);

    event.preventDefault();
};