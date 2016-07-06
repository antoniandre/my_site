/**
 * This script contains general behaviors only.
 * The commonReady function is called on every page when DOM is ready.
 * To add a specific page script, create js named like the page on which you want
 * to add the behavior and add a function named {pageName}Ready().
 */

//====================================== V A R S ===================================//
//==================================================================================//
var // General vars.
	g =
	{
		currentPage: null,
		lang: l,// Contains the current page language.
		scripts: scripts// Contains an array of available scripts and for each, the loaded state and the need of a dedicated css.
	},
	// Css3 capabilities.
	s = document.createElement('p').style, css3supports =
	{
		transition: 'transition' in s || 'WebkitTransition' in s || 'MozTransition' in s || 'msTransition' in s
					|| 'OTransition' in s,
		transform: 'transform' in s || 'WebkitTransform' in s || 'MozTransform' in s || 'msTransform' in s
					|| 'OTransform' in s,
		animation: 'animation' in s || 'WebkitAnimation' in s ||  'MozAnimation' in s || 'msAnimation' in s
				   || 'OAnimation' in s,
		boxShadow: 'boxShadow' in s || 'WebkitBoxShadow' in s ||  'MozBoxShadow' in s || 'msBoxShadow' in s
				   || 'OBoxShadow' in s,
		borderRadius: 'borderRadius' in s || 'WebkitBorderRadius' in s ||  'MozBorderRadius' in s
	},
	cl = function(){console.log.apply(console, arguments);},// Shortcut function for console.log().
	// Check if requested JS file is loaded or not and if it needs a dedicated css. Then load the required files accordingly.

	loadScript = function(scriptName, callback)
	{
		if (!g.scripts[scriptName].loaded && !g.scripts[scriptName].loading)
		{
			if (g.scripts[scriptName].css) loadStyleSheet(scriptName);
			$.getScript(ROOT+'js/'+scriptName+'.js', function()
			{
				g.scripts[scriptName].loaded = true;
				g.scripts[scriptName].loading = false;
				callback();
			});
			g.scripts[scriptName].loading = true;
		}
		else callback();
	},

	// Load a defered css file.
	loadStyleSheet = function(src)
	{
	    if (document.createStyleSheet) document.createStyleSheet('?css=1&o='+src);
	    else $("head").append($('<link rel="stylesheet" href="?css=1&o='+src+'" type="text/css" media="screen" />'));
	},

	imagePreloader = function(arrayOfImages)
	{
		var self = this;
		this.loadedImages = 0;
		this.imagesDir = ROOT+'images/';
		this.init = function()
		{
			var img = null;
			for (var i = 0; i < arrayOfImages.length; i++)
			{
				img = new Image();
				img.onload = function()
				{
					self.loadedImages++;
					if (self.loadedImages == arrayOfImages.length) self.onImagesPreloaded();
				}
				img.src = this.imagesDir+'?i='+arrayOfImages[i];
			}
		};
		this.onImagesPreloaded = function()
		{
			$('#spinner').fadeOut(800, function()
			{
				$('#contentWrapper').children('.content').add('#menu').addClass('show');
			});
		};

		this.init();
	},

	/**
	 * Set a message.
	 * Example of use: setMessage(json.message, json.error? 'error' : 'valid', json.error? 'error' : 'success', 'content', [0, null]);
	 *
	 * @param String message: the message to display.
	 * @param String icon: the icon to show with the message among valid, info, warning, invalid.
	 * @param String Class: the class to apply to the message container.
	 * @param String position: the position of the message among header or content. default header.
	 * @param Array animation: an array of [delay_before_display, delay_before_hidding] in milliseconds. Default [1000, 3000]
	 * @return jQuery object: the appended message.
	 */
	setMessage = function(message, icon, Class, position, animation)
	{
		if (position === undefined) position = 'header';
		if (animation === undefined) animation = [1000, 3000];

		var timeToSlideDown = animation && animation[0] !== null ? animation[0] : null,
			timeToSlideUp = animation && animation[1] !== null ? animation[1] : null,
			classColors = {success: 'green', failure: 'red', error: 'red', info: 'yellow', warning: 'orange'},
			Class = Class+(classColors.hasOwnProperty(Class) ? ' '+classColors[Class] : ''),
			message = '<div class="'+Class+' message">'
					+(icon !== undefined ? '<span class="ico i-'+icon+'"></span>' : '')
					+message+'</div>',
			$message = $(message)[timeToSlideDown !== null ? 'hide' : 'show'](),
			messageContainer = position== 'header' ? 'body' : '#contentWrapper .content';
		if (!$('#'+position+'Message').length)
		{
			$(messageContainer).prepend('<div id="'+position+'Message"/>');
		}
		$message.appendTo('#'+position+'Message');
		setTimeout(function()
		{
			if (timeToSlideDown !== null) $message.hide().delay(timeToSlideDown).slideDown(500, 'easeInOutQuad');
			if (timeToSlideUp !== null) $message.delay(timeToSlideUp).slideUp(500, 'easeInOutQuad', function(){$(this).remove()});
		}, 100);

		return $message;
	},

	handleOldBrowsers = function()
    {
		// Add support for IE8- (http://stackoverflow.com/questions/1744310/how-to-fix-array-indexof-in-javascript-for-internet-explorer-browsers)
		if (!Array.prototype.indexOf)
		{
		    Array.prototype.indexOf= function(obj, start)
		    {
		        for (var i = (start || 0); i < this.length; i++) if (this[i] === obj) return i;
		        return -1;
		    }
		}

		// Css3 pseudo-class :checked is not available in old browsers...
		$(':checkbox').on('change', function(){this.checked ? $(this).addClass('checked') : $(this).removeClass('checked')});

		//css3 capabilities detection
		var classes = '';
		for (var property in css3supports) if (css3supports[property]) classes += ' '+property;
		$('html').addClass(classes);
    },

	initBasics = function()
    {
    	//---------------------------- GoTop ----------------------------//
		if ($('#goTop').length)
		{
			$('#goTop').on('click', function(e)
			{
				$.scrollTo($('body'), 600, {easing: 'easeOutQuad'});
				e.preventDefault();
			});
		}
		if ($('.goDown').length)
		{
			$('.goDown').on('click', function(e)
			{
				var href = $(this).attr('href');
				$.scrollTo(href, 600, {easing: 'easeOutQuad'});
				e.preventDefault();
			});
		}

    	//--------------------------- Messages --------------------------//
		// Messages slide down and slide up animations.
		$('#headerMessage').children()
			.filter(function(){return $(this).is('[data-slidedown]') || $(this).is('[data-slideup]');})
			.each(function(i, curr)
			{
				if ($(curr).is('[data-slidedown]')) $(curr).hide().delay($(curr).attr('data-slidedown')).slideDown(500, 'easeInOutQuad');
				if ($(curr).is('[data-slideup]')) $(curr).delay($(curr).attr('data-slideup')).slideUp(500, 'easeInOutQuad');
			});

    	//--------------------------- Spinner ---------------------------//
		$('#spinner').fadeOut(800, function()
		{
			$('#contentWrapper').children('.content').add('body').addClass('show');
		});

    	//----------------------- Error backtrace -----------------------//
		if ($('#error .backtrace').length)
		{
			$('#error .backtrace').on('click', 'strong', function()
			{
				$(this).next().slideToggle(400)
				.parents('.backtrace').toggleClass('show');
			});
		};

    	//----------------------- Error backtrace -----------------------//
		$('#lang button')
			.on('mouseenter', function()
			{
				$(this).siblings('button').addClass('blur');
			}).on('mouseleave', function()
			{
				$(this).siblings('button').removeClass('blur');
			});
    },

	initForm = function()
    {
		if ($('form').length) formReady();
    },

	handleLightbox = function()
    {
		$('#lightbox > *').on('click', function(e)
		{
			e.preventDefault();

			if ($(e.target).is('#lightbox > .overlay, #lightbox > .wrapper, #lightbox .close'))
			{
				$(this).parent().removeClass('show');
				setTimeout(function()
				{
					$('#lightbox .content').removeAttr('style').children('.content-inner').html('')
					.parents('#lightbox').addClass('hide');
				}, 1000);
			}
		});
    },

	handleCookieNotice = function()
    {
        $('#cookieNotice').delay(500).animate({bottom: 0}, 1000, 'easeInOutQuad')
        .find('a.ok').click(function(e)
        {
            e.preventDefault();
            $.cookie('cookie_consent', "yes", {expires: 365+30, path: '/'});// 13 months.
            $(this).parents('#cookieNotice').animate({bottom: '-32px', opacity: 0}, 700, 'easeInOutQuad');
        });
    },

	handleSocials = function()
	{
		var lang = g.lang == 'Fr' ? 'fr-FR' : 'en-US',
			location = window.location.toString(),
		    facebook = '<div class="wrapper" style="width:140px;"><fb:like send="true" layout="button_count"\
					    width="450" show_faces="true" href="'+location+'"></fb:like></div>';
		$('.social').append(facebook);
		//=============FACEBOOK============//
		window.fbAsyncInit = function(){FB.init({cookie:true, xfbml:true, version:'v2.3'})};

		// Load the SDK Asynchronously
		// TODO: try to remove external parenthesis:
		(function(d,s,id)
		{
			var js,fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s);js.id= id;
			js.src = '//connect.facebook.net/'+lang.replace('-','_')+'/sdk.js';
			fjs.parentNode.insertBefore(js,fjs);
		}(document, 'script', 'facebook-jssdk'));
		//=============end of FACEBOOK============//

		if (!$('.IE7').length)
		{
			$('.social').append('<div id="g-plusone" class="wrapper"/>');
			var po = document.createElement('g:plusone');
			po.id = 'gplusone';
			var wrapper = document.getElementById('g-plusone');
			wrapper.appendChild(po);
			document.getElementById('gplusone').setAttribute('size','medium');
			document.getElementById('gplusone').setAttribute('dataHref',location);

			var gp = document.createElement('script');
			gp.type = 'text/javascript';
			gp.async = true;
			gp.src = 'https://apis.google.com/js/plusone.js';
			gp.text = 'window.___gcfg = {lang: \''+lang+'\'}';
			var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(gp, s);
		}
	},

	handleComments = function()
	{
		$('.comments .radio [type=radio]').change(function(e)
		{
			if ($(this).val()) $(this).parents('.comment').attr('class', 'comment i-'+($(this).val()));
		}).filter(':checked').trigger('change');
	},

	handleSlideshow = function()
	{
		$('.slideshow').each(function(i, curr)
		{
			var self = $(this),
				duration = self.attr('data-duration') || 1000,
				animDuration = self.attr('data-anim-duration') || 1000,
				firstImage = self.find('figure:first').addClass('first').children('img'),
				hasGlobalCaption = self.find('.caption').length;

			self.css({width:firstImage.width(), height:firstImage.height()})
				.wrap('<div class="slideshowWrapper"/>');

			if (hasGlobalCaption) self.after(self.find('.caption'));

			setInterval(function()
			{
				self.find('figure:first').fadeTo(animDuration, 0, function()
				{
					var currFig = $(this);
					currFig.appendTo(currFig.parent());
					currFig.css('opacity', 1);
				})
			}, parseInt(duration)+parseInt(animDuration));
		});
	},

	scrollHandler = function()
	{
		var bar = $('#stickyBar'),
			barThreshold = null,
			page = $('#page'),
			footer = $('#footer'),
            documentHeight = $(document).outerHeight(),
			winHeight = $(window).height(),
			parallaxObj = [],
			self = this,
			// window.pageYOffset undefined in IE8 (http://stackoverflow.com/questions/16618785/ie8-alternative-to-window-scrolly)
            // documentScroll = $(window).documentScroll(),
            documentScroll = window.pageYOffset/*IE9+*/ || document.documentElement.scrollTop;// Cross-browser.


		this.calculateCurrScrollEl = function(el, i)
		{
        	var elTop = el.$el.offset().top,
        		elHeight = parseInt(el.$el.outerHeight()),

        		// If parallax element bellow winHeight, add winHeight to the current document scroll for calculation.
        		// Usefull when reaching document end and can't scroll until the parallax element.
        		docScrollModified = documentScroll + (elTop > winHeight ? winHeight : 0),

				// If scrolled bellow elHeight or scroll above elementTop, do not keep a number out of bounds.
				// Scroll in px.
				outOfBoundsBefore = docScrollModified < elTop,
				outOfBoundsAfter = (docScrollModified - elTop) > (elHeight+1);

			parallaxObj[i].inBounds = !(outOfBoundsBefore) && !(outOfBoundsAfter);
			parallaxObj[i].state = outOfBoundsBefore ? 'before' : (outOfBoundsAfter ? 'after' : 'in');
			parallaxObj[i].currScroll = outOfBoundsAfter ? 100 : (outOfBoundsBefore ? 0 : (docScrollModified - elTop) / elHeight);

        	/*return {
        		inBounds: outOfBoundsBefore || outOfBoundsAfter,
	        	// Scroll in percent.
        		value: outOfBoundsAfter ? 100 : (outOfBoundsBefore ? 0 : (docScrollModified - elTop))
        	};*/

		};

		this.fillArray = function()
		{
			if (!$('.parallax[data-speed]').length) return [];

			$('.parallax[data-speed]').each(function(i, curr)
			{
				var $el = $(curr);

				parallaxObj[i] =
				{
					el: curr,
					$el: $el,
					top: $el.offset().top,
					height: parseInt($el.outerHeight()),
					speed: $el.attr('data-speed'),
					lastState: 'init'
				};

	            if (curr.attributes['data-start-opacity'] && curr.attributes['data-end-opacity'])
	            {
	            	parallaxObj[i].opacity =
	            	{
	            		startValue: curr.attributes['data-start-opacity'].value,
	            		endValue: curr.attributes['data-end-opacity'].value,
	            	};
	            }

	            if (curr.attributes['data-perform-out-of-window'])
	            {
	            	parallaxObj[i].performOutOfWindow = curr.attributes['data-perform-out-of-window'].value;
	            }

	            // Only support rgb() colors for now.
	            if (curr.attributes['data-start-background'] && curr.attributes['data-end-background'])
	            {
	            	var bgStart = curr.attributes['data-start-background'].value,
	            		bgEnd = curr.attributes['data-end-background'].value,
	            		rgbStart = bgStart.match(/rgb ?\((\d+), ?(\d+), ?(\d+)\)/i),
	            		rgbEnd = bgEnd.match(/rgb ?\((\d+), ?(\d+), ?(\d+)\)/i);
	            	parallaxObj[i].background =
	            	{
	            		start:
	            		{
	            			val1: parseInt(rgbStart[1]),
	            			val2: parseInt(rgbStart[2]),
	            			val3: parseInt(rgbStart[3])
	            		},
	            		delta:// Simplify the calculation when scrolling - for better performances.
	            		{
	            			val1: parseInt(rgbEnd[1])-parseInt(rgbStart[1]),
	            			val2: parseInt(rgbEnd[2])-parseInt(rgbStart[2]),
	            			val3: parseInt(rgbEnd[3])-parseInt(rgbStart[3])
	            		},
	            	};
	            }
			})
		};

		this.init = function()
		{
			$(window).on('scroll', function()
			{
				//---------------------------- parallax ----------------------------//
                if (!parallaxObj.length) self.fillArray();

				documentScroll = window.pageYOffset/*IE9+*/ || document.documentElement.scrollTop;// Cross-browser.

				$(parallaxObj).each(function(i, el)
                {
                	var lastState = el.state;
					self.calculateCurrScrollEl(el, i);

                	var $el = el.$el,
                		newState = el.state,
                		currScrollInEl = el.currScroll;

                    // If conditions are verified, proceed to calculations.
                    // Stop calculating when not in screen for performances except if performOutOfWindow is set to true,
                    // then only loop if last state out of bound was different (states: init/before/in/after).
                	if (el.inBounds || (el.performOutOfWindow && el.lastState !== newState)
                		// when page is loaded from bottom of page, prevent black bg to remain when scrolling up the document.
                		&& !($el.is('#day') && newState == 'before'))
                	{
                    	if ($el.hasClass('stop')) $el.removeClass('stop');

	                    var newCss = {};

	                    // Translation.
	                    if (el.speed != 1)
	                    {
		                    var translateY = parseInt(el.height*currScrollInEl*el.speed);
	                    	newCss.transform = 'translate3d(0px, -'+translateY+'px, 0px)';
	                    }

	                    // Opacity.
	                    if (el.opacity !== undefined)
	                    {
	                    	newCss.opacity = parseFloat((el.opacity.endValue - el.opacity.startValue) * currScrollInEl / 100);
	                    }

	                    // Background.
	                    if (el.background !== undefined)
	                    {
	                    	newCss.background = "rgb("
	                    		+parseInt(parseFloat(el.background.delta.val1 * currScrollInEl) + el.background.start.val1)+","
	                    		+parseInt(parseFloat(el.background.delta.val2 * currScrollInEl) + el.background.start.val2)+","
	                    		+parseInt(parseFloat(el.background.delta.val3 * currScrollInEl) + el.background.start.val3)+")";
	                    }

	                    // Moving sub-elements.
	                    if ($el.find('.moving').length)
	                    {
	                    	$el.find('.moving').each(function(j, curr2)
	                    	{
	                    		var movingEl = $(curr2),
	                    			move =
	                    			{
	                    				x:
	                    				{
	                    					raw: movingEl.attr('data-move-from-x') || "0",
	                    					from: parseFloat(movingEl.attr('data-move-from-x') || 0),
	                    					to: parseFloat(movingEl.attr('data-move-to-x')) || 0
	                    				},
	                    				y:
	                    				{
	                    					raw: movingEl.attr('data-move-from-y') || "0",
	                    					from: parseFloat(movingEl.attr('data-move-from-y') || 0),
	                    					to: parseFloat(movingEl.attr('data-move-to-y')) || 0
	                    				}
	                    			},
	                    			translateX = parseFloat(move.x.from + (move.x.to - move.x.from) * currScrollInEl),
	                    			translateY = parseFloat(move.y.from + (move.y.to - move.y.from) * currScrollInEl);

                    			move.x.unit = move.x.raw.replace(move.x.from, '');
                    			move.y.unit = move.y.raw.replace(move.y.from, '');
	                    		movingEl.css({'transform': 'translate3d('+translateX+move.x.unit+', '+translateY+move.y.unit+', 0px)'});
	                    	});
	                    }

	                    if (el.el.attributes['data-target-element'] !== undefined)
	                    	$target = $(el.el.attributes['data-target-element'].value.replace('&gt;', '>'));
	                    else if ($el.find('.bg').length) $target = $el.find('.bg');
	                    else $target = $el;

	                    $target.css(newCss);
                    }
                    else $el.addClass('stop');
                });
				//----------------------------------------------------------------//

				//-------------------------- Sticky bar --------------------------//
				var offsetTop = parseInt(bar.offset().top - documentScroll);

				if (offsetTop <= 0 && !bar.hasClass('sticky'))
				{
					// Calculate it only once.
					if (barThreshold === null) barThreshold = Math.min(documentScroll, bar.offset().top);
					bar.addClass('sticky');
				}
				else if (documentScroll <= barThreshold && bar.hasClass('sticky')) bar.removeClass('sticky');
				//----------------------------------------------------------------//
			})
			.on('resize', function()
			{
				barThreshold = null;// Force recalculation.
	            documentHeight = $(document).outerHeight();
				winHeight = $(window).height();
				$(window).trigger('scroll', this);
			});
		}();
	};
//==================================================================================//
//==================================================================================//


//====================================== M A I N ===================================//
//==================================================================================//
var commonReady = function()
{
	handleOldBrowsers();
	initBasics();
	initForm();
	new scrollHandler();
	// new imagePreloader(['vietnam-map.png', 'visa-approved.png', 'logo.jpg']);

    if ($('#lightbox').length)               handleLightbox();
    if ($('#cookieNotice').length)           handleCookieNotice();
    if ($('[data-original]').length)         $("[data-original]").lazyload({effect:"fadeIn", threshold:800, load: function(){$(this).addClass('loaded')}});
    if ($('.slideshow').length)              handleSlideshow();
	if ($('.social').length && !localhost)   handleSocials();
    if ($('.comments').length)               handleComments();
};
//================================= end of  M A I N ================================//
//==================================================================================//




//================================= F U N C T I O N S ==============================//
//==================================================================================//
//==================================================================================//
//==================================================================================//
