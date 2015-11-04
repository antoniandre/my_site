var formReady = function()
{
	// Using jQuery Dropzone plugin: http://www.dropzonejs.com/#installation
	/*loadScript('dropzone', function()
	{
		Dropzone.autoDiscover = false;
		$(".Dropzone").addClass('dropzone').dropzone(
		{
			url: $(".Dropzone").parents('form').attr('action')+'?upload=1',
			uploadMultiple: true,
			paramName: $(".Dropzone").attr('data-name').replace('[]', ''),
			addRemoveLinks: true
		});
	});*/

	if ($('.wysiwyg').length) loadScript('redactor', function()
	{

		(function($)
		{
			$.Redactor.prototype.linkify().convertVideoLinks = function(html)
			{
				alert('ok ok !!');
				var iframeStart = '<figure><iframe class="redactor-linkify-object" width="500" height="281" src="',
					iframeEnd = '" frameborder="0" allowfullscreen></iframe></figure>';

				if (html.match(this.opts.linkify.regexps.youtube))
				{
					html = html.replace(this.opts.linkify.regexps.youtube, iframeStart + '//www.youtube.com/embed/$1' + iframeEnd);
				}

				if (html.match(this.opts.linkify.regexps.vimeo))
				{
					html = html.replace(this.opts.linkify.regexps.vimeo, iframeStart + '//player.vimeo.com/video/$2' + iframeEnd);
				}

				return html;
			};


			$.Redactor.prototype.youtube = function()
			{
				return {
					init: function()
					{
						var button = this.button.addAfter('image', 'youtube', this.lang.get('video'));
						this.button.addCallback(button, this.youtube.show);
					},
					show: function()
					{
						this.modal.addTemplate('youtube', this.youtube.getTemplate());

						this.modal.load('youtube', this.lang.get('video'), 700);
						this.modal.createCancelButton();

						var button = this.modal.createActionButton(this.lang.get('insert'));
						button.on('click', this.youtube.insert);

						this.selection.save();
						this.modal.show();

						$('#redactor-insert-youtube-area').focus();
					},
					getTemplate: function()
					{
						return String()
						+ '<section id="redactor-modal-youtube-insert">'
							+ '<label>' + this.lang.get('video_html_code') + '</label>'
							+ '<input type="text" id="redactor-insert-youtube-area" placeholder="The unique video id after \'www.youtube.com/embed/\' (E.g: 0TfooXDuttk)"/>'
						+ '</section>';
					},
					insert: function()
					{
						var data = $('#redactor-insert-youtube-area').val(),
						    iframe = '<figure><iframe style="width:500px;height:281px" src="https://www.youtube.com/embed/'
							         + data + '?rel=0&controls=2&showinfo=0" frameborder="0" allowfullscreen></iframe></figure>\n';

						this.selection.restore();
						this.modal.close();

						var current = this.selection.getBlock() || this.selection.getCurrent();

						if (current) $(current).after(iframe);
						else this.insert.html(iframe);

						this.code.sync();
					}
				};
			};
		})(jQuery);

		/*$.Redactor.prototype.figure = function()
		{
		    return
		    {
		        init: function()
		        {
					var button = this.button.addAfter('image', 'Figure');
		            this.button.addCallback(button, this.figure.show);
		 
		            // make your added button as Font Awesome's icon
		            this.button.setAwesome('advanced', 'fa-tasks');
		        },
				show: function()
				{
					this.modal.addTemplate('figure', this.figure.getTemplate());

					this.modal.load('figure', 'Figure', 700);
					this.modal.createCancelButton();

					var button = this.modal.createActionButton(this.lang.get('insert'));
					button.on('click', this.figure.insert);

					this.selection.save();
					this.modal.show();

					$('#redactor-insert-youtube-area').focus();
				},
				getTemplate: function()
				{
					return String()
					+ '<section id="redactor-modal-youtube-insert">'
						+ '<label>' + this.lang.get('video_html_code') + '</label>'
						+ '<input type="text" id="redactor-insert-youtube-area" placeholder="The unique video id after \'www.youtube.com/embed/\' (E.g: 0TfooXDuttk)"/>'
					+ '</section>';
				},
				insert: function()
				{
					var data = $('#redactor-insert-youtube-area').val(),
					    iframe = '<figure><iframe style="width:500px;height:281px" src="https://www.youtube.com/embed/'
						         + data + '?rel=0&controls=2&showinfo=0" frameborder="0" allowfullscreen></iframe></figure>\n';

					this.selection.restore();
					this.modal.close();

					var current = this.selection.getBlock() || this.selection.getCurrent();

					if (current) $(current).after(iframe);
					else this.insert.html(iframe);

					this.code.sync();
				}
			};
		};*/

		/*(function($)
		{
			$.Redactor.prototype.imagemanager = function()
			{
				return {
					init: function()
					{
						if (!this.opts.imageManagerJson) return;

						this.modal.addCallback('image', this.imagemanager.load);
					},
					load: function()
					{
						var $modal = this.modal.getModal();

						this.modal.createTabber($modal);
						this.modal.addTab(1, 'Upload', 'active');
						this.modal.addTab(2, 'Choose');

						$('#redactor-modal-image-droparea').addClass('redactor-tab redactor-tab1');

						var $box = $('<div id="redactor-image-manager-box" style="overflow: auto; height: 300px;" class="redactor-tab redactor-tab2">okok okok').hide();
						$modal.append($box);

						$.ajax({
						  dataType: "json",
						  cache: false,
						  url: this.opts.imageManagerJson,
						  success: $.proxy(function(data)
							{
								$.each(data, $.proxy(function(key, val)
								{
									// title
									var thumbtitle = '';
									if (typeof val.title !== 'undefined') thumbtitle = val.title;

									var img = $('<img src="' + val.thumb + '" rel="' + val.image + '" title="' + thumbtitle + '" style="width: 100px; height: 75px; cursor: pointer;" />');
									$('#redactor-image-manager-box').append(img);
									$(img).click($.proxy(this.imagemanager.insert, this));

								}, this));


							}, this)
						});


					},
					insert: function(e)
					{
						this.image.insert('<img src="' + $(e.target).attr('rel') + '" alt="' + $(e.target).attr('title') + '">');
					}
				};
			};
		})(jQuery);*/

		$('textarea.wysiwyg').redactor(
		{
			// fixed: true,
			imageUpload: '../../uploads/',
			// linebreaks: true,
			paragraphize: false,
			replaceDivs: false,
			focus: true,
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
			plugins: ['youtube'/*, 'figure'*/]
			/*autosave: window.location,
			interval: 30,
			autosaveCallback: function(data, redactor_obj)
			{
				cl(data);
			}*/
		});
	});

	// Handle show/hide state of a form element if a data-toggle attribute is set.
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

		// Walk through the ready 'togglers' array to perform toggle on appropriate elements.
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
}


