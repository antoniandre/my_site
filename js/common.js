/**
 * This script contains general behaviors only.
 * The commonReady function is called on every page when DOM is ready.
 * To add a specific page script, create js named like the page on which you want
 * to add the behavior and add a function named {pageName}Ready().
 */

//====================================== V A R S ===================================//
//==================================================================================//
var // General vars. (g for general)
	g =
	{
		currentPage: null,
		lang: l,// Contains the current page language.
		scripts: scripts,// Contains an array of available scripts and for each, the loaded state and the need of a dedicated css.
		loadScreenWidth: $(window).width(),// The width of the screen on page load.
		loadScreenHeight: $(window).height(),// The height of the screen on page load.
		screenWidth: 0// The width of the screen at any moment.
	},
	// Css3 capabilities. (s for support)
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
			js.async = 'true';
			fjs.parentNode.insertBefore(js,fjs);
		}(document, 'script', 'facebook-jssdk'));
		//=============end of FACEBOOK============//

		if (!$('.IE7').length)
		{
			$('.social').append(
            '<div id="g-plusone" class="wrapper">\
                <g:plusone align="right" size="medium" data-href="'+location+'"></g:plusone>\
            </div>');

			var gp = document.createElement('script');
			gp.type = 'text/javascript';
			gp.src = 'https://apis.google.com/js/platform.js';
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

	handleMobileMenu = function()
	{
		var mobileMenu = $('#mobileMenu'),
			toggler = $('.hamburger');

        // SlideDown submenu.
		mobileMenu.on('click', '.lvl1 > li.parent > a, .lvl1 > li.parent > h4', function(e)
		{
		    e.preventDefault();
		    var submenu = $(this).siblings('ul');

		    mobileMenu.find('.parent ul:visible').not(submenu).not('.home > ul').slideUp(400, 'easeOutQuad');
		    submenu.stop(true, true)[submenu.is(':visible') ? 'slideUp' : 'slideDown'](400, 'easeOutQuad');
		}).find('li ul').not('.home > ul').hide();

		$(window).on('click', function(e)
		{
		    // Hide the submenu when user clicks anywhere out of the mobile menu.
		    // (have to check it is not a click inside menu or toggler)
		    if (!$(e.target).is(mobileMenu) && !mobileMenu.find(e.target).length
		    	&& !$(e.target).is(toggler) && !$(toggler).find(e.target).length
		        && $('#mobileMenuOpen').is(':checked'))
		    {
		        toggler.trigger('click');
		    }
		});
	},

	scrollHandler = function()
	{
		var self = this,
			bar = $('#stickyBar'),
			barThreshold = null,
			barInitialOffsetTop = bar.parent().offset().top,
			barSticky = false,
			page = $('#page'),
			footer = $('#footer'),
			parallaxObj = [],

			// Define the window dimension vars outside the loop for faster results. BUT UPDATE ON RESIZE EVENT.
            documentHeight = $(document).outerHeight(),
			winHeight = window.screen.availHeight || $(window).height(),// For mobiles (IOS) to prevent parallax jumps due to system bars.
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

				// For mobile elastic scroll - do not calculate parallax outside bounds.
				// Upper than top check not needed with current parallax.
                if (/*documentScroll < 0 || */($(document).height() - documentScroll - winHeight) < 0) return false;

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
				if (!barSticky && barInitialOffsetTop <= documentScroll)// Check boolean faster than check element class.
				{
					barSticky = true;
					bar.addClass('sticky');
				}
				else if (barSticky && barInitialOffsetTop > documentScroll)
				{
					barSticky = false;
					bar.removeClass('sticky');
				}
				//----------------------------------------------------------------//
			})
			.on('resize', function()
			{
				barInitialOffsetTop = bar.parent().offset().top;// Force recalculation.
	            documentHeight = $(document).outerHeight();
				winHeight = window.screen.availHeight || $(window).height();
				$(window).trigger('scroll', this);

			    $('header .bg').css('height', winHeight);
			});
		}();
	},

	resizeHandler = function()
	{
		$(window).on('resize init', function()
		{
			g.screenWidth = $(this).width();
			$('html')[g.screenWidth <= 550 ? 'addClass' : 'removeClass']('smallWidth');
		}).trigger('init');
	},

	lazyload = function()
	{
		$("[data-original]").each(function()
		{
			/* @todo: handle smaller image sizes for mobile.
			if ($('#homePage').length)
			{
				switch(true)
				{
					case g.loadScreenWidth < 300:
						size = 's';
						break;
					case g.loadScreenWidth < 550:
						size = 'm';
						break;
					case g.loadScreenWidth < 900:
						size = 'l';
						break;
					case g.loadScreenWidth < 1400:
						size = 'xl';
						break;
				}
				$(this).attr('data-original', $(this).data('original').replace(/_(xs|s|m|l|xl)\.(jpg|jpeg|png|gif)/, '_'+size+'.$2'));
			}*/
		});
		$("[data-original]").lazyload({effect:"fadeIn", threshold:800, load: function(){$(this).addClass('loaded')}});
	},

	toLatinChars =
	{
		latin_map: {
		"Á":"A","Ă":"A","Ắ":"A","Ặ":"A","Ằ":"A","Ẳ":"A","Ẵ":"A","Ǎ":"A","Â":"A","Ấ":"A","Ậ":"A","Ầ":"A","Ẩ":"A","Ẫ":"A","Ä":"A","Ǟ":"A",
		"Ȧ":"A","Ǡ":"A","Ạ":"A","Ȁ":"A","À":"A","Ả":"A","Ȃ":"A","Ā":"A","Ą":"A","Å":"A","Ǻ":"A","Ḁ":"A","Ⱥ":"A","Ã":"A","Ꜳ":"AA",
		"Æ":"AE","Ǽ":"AE","Ǣ":"AE","Ꜵ":"AO","Ꜷ":"AU","Ꜹ":"AV","Ꜻ":"AV","Ꜽ":"AY","Ḃ":"B","Ḅ":"B","Ɓ":"B","Ḇ":"B","Ƀ":"B","Ƃ":"B",
		"Ć":"C","Č":"C","Ç":"C","Ḉ":"C","Ĉ":"C","Ċ":"C","Ƈ":"C","Ȼ":"C","Ď":"D","Ḑ":"D","Ḓ":"D","Ḋ":"D","Ḍ":"D","Ɗ":"D","Ḏ":"D","ǲ":"D",
		"ǅ":"D","Đ":"D","Ƌ":"D","Ǳ":"DZ","Ǆ":"DZ","É":"E","Ĕ":"E","Ě":"E","Ȩ":"E","Ḝ":"E","Ê":"E","Ế":"E","Ệ":"E","Ề":"E","Ể":"E",
		"Ễ":"E","Ḙ":"E","Ë":"E","Ė":"E","Ẹ":"E","Ȅ":"E","È":"E","Ẻ":"E","Ȇ":"E","Ē":"E","Ḗ":"E","Ḕ":"E","Ę":"E","Ɇ":"E","Ẽ":"E",
		"Ḛ":"E","Ꝫ":"ET","Ḟ":"F","Ƒ":"F","Ǵ":"G","Ğ":"G","Ǧ":"G","Ģ":"G","Ĝ":"G","Ġ":"G","Ɠ":"G","Ḡ":"G","Ǥ":"G","Ḫ":"H","Ȟ":"H",
		"Ḩ":"H","Ĥ":"H","Ⱨ":"H","Ḧ":"H","Ḣ":"H","Ḥ":"H","Ħ":"H","Í":"I","Ĭ":"I","Ǐ":"I","Î":"I","Ï":"I","Ḯ":"I","İ":"I","Ị":"I",
		"Ȉ":"I","Ì":"I","Ỉ":"I","Ȋ":"I","Ī":"I","Į":"I","Ɨ":"I","Ĩ":"I","Ḭ":"I","Ꝺ":"D","Ꝼ":"F","Ᵹ":"G","Ꞃ":"R","Ꞅ":"S","Ꞇ":"T",
		"Ꝭ":"IS","Ĵ":"J","Ɉ":"J","Ḱ":"K","Ǩ":"K","Ķ":"K","Ⱪ":"K","Ꝃ":"K","Ḳ":"K","Ƙ":"K","Ḵ":"K","Ꝁ":"K","Ꝅ":"K","Ĺ":"L","Ƚ":"L",
		"Ľ":"L","Ļ":"L","Ḽ":"L","Ḷ":"L","Ḹ":"L","Ⱡ":"L","Ꝉ":"L","Ḻ":"L","Ŀ":"L","Ɫ":"L","ǈ":"L","Ł":"L","Ǉ":"LJ","Ḿ":"M","Ṁ":"M",
		"Ṃ":"M","Ɱ":"M","Ń":"N","Ň":"N","Ņ":"N","Ṋ":"N","Ṅ":"N","Ṇ":"N","Ǹ":"N","Ɲ":"N","Ṉ":"N","Ƞ":"N","ǋ":"N","Ñ":"N","Ǌ":"NJ",
		"Ó":"O","Ŏ":"O","Ǒ":"O","Ô":"O","Ố":"O","Ộ":"O","Ồ":"O","Ổ":"O","Ỗ":"O","Ö":"O","Ȫ":"O","Ȯ":"O","Ȱ":"O","Ọ":"O","Ő":"O",
		"Ȍ":"O","Ò":"O","Ỏ":"O","Ơ":"O","Ớ":"O","Ợ":"O","Ờ":"O","Ở":"O","Ỡ":"O","Ȏ":"O","Ꝋ":"O","Ꝍ":"O","Ō":"O","Ṓ":"O","Ṑ":"O",
		"Ɵ":"O","Ǫ":"O","Ǭ":"O","Ø":"O","Ǿ":"O","Õ":"O","Ṍ":"O","Ṏ":"O","Ȭ":"O","Ƣ":"OI","Ꝏ":"OO","Ɛ":"E","Ɔ":"O","Ȣ":"OU","Ṕ":"P",
		"Ṗ":"P","Ꝓ":"P","Ƥ":"P","Ꝕ":"P","Ᵽ":"P","Ꝑ":"P","Ꝙ":"Q","Ꝗ":"Q","Ŕ":"R","Ř":"R","Ŗ":"R","Ṙ":"R","Ṛ":"R","Ṝ":"R","Ȑ":"R",
		"Ȓ":"R","Ṟ":"R","Ɍ":"R","Ɽ":"R","Ꜿ":"C","Ǝ":"E","Ś":"S","Ṥ":"S","Š":"S","Ṧ":"S","Ş":"S","Ŝ":"S","Ș":"S","Ṡ":"S","Ṣ":"S",
		"Ṩ":"S","Ť":"T","Ţ":"T","Ṱ":"T","Ț":"T","Ⱦ":"T","Ṫ":"T","Ṭ":"T","Ƭ":"T","Ṯ":"T","Ʈ":"T","Ŧ":"T","Ɐ":"A","Ꞁ":"L","Ɯ":"M",
		"Ʌ":"V","Ꜩ":"TZ","Ú":"U","Ŭ":"U","Ǔ":"U","Û":"U","Ṷ":"U","Ü":"U","Ǘ":"U","Ǚ":"U","Ǜ":"U","Ǖ":"U","Ṳ":"U","Ụ":"U","Ű":"U",
		"Ȕ":"U","Ù":"U","Ủ":"U","Ư":"U","Ứ":"U","Ự":"U","Ừ":"U","Ử":"U","Ữ":"U","Ȗ":"U","Ū":"U","Ṻ":"U","Ų":"U","Ů":"U","Ũ":"U",
		"Ṹ":"U","Ṵ":"U","Ꝟ":"V","Ṿ":"V","Ʋ":"V","Ṽ":"V","Ꝡ":"VY","Ẃ":"W","Ŵ":"W","Ẅ":"W","Ẇ":"W","Ẉ":"W","Ẁ":"W","Ⱳ":"W","Ẍ":"X",
		"Ẋ":"X","Ý":"Y","Ŷ":"Y","Ÿ":"Y","Ẏ":"Y","Ỵ":"Y","Ỳ":"Y","Ƴ":"Y","Ỷ":"Y","Ỿ":"Y","Ȳ":"Y","Ɏ":"Y","Ỹ":"Y","Ź":"Z","Ž":"Z",
		"Ẑ":"Z","Ⱬ":"Z","Ż":"Z","Ẓ":"Z","Ȥ":"Z","Ẕ":"Z","Ƶ":"Z","Ĳ":"IJ","Œ":"OE","ᴀ":"A","ᴁ":"AE","ʙ":"B","ᴃ":"B","ᴄ":"C","ᴅ":"D",
		"ᴇ":"E","ꜰ":"F","ɢ":"G","ʛ":"G","ʜ":"H","ɪ":"I","ʁ":"R","ᴊ":"J","ᴋ":"K","ʟ":"L","ᴌ":"L","ᴍ":"M","ɴ":"N","ᴏ":"O","ɶ":"OE",
		"ᴐ":"O","ᴕ":"OU","ᴘ":"P","ʀ":"R","ᴎ":"N","ᴙ":"R","ꜱ":"S","ᴛ":"T","ⱻ":"E","ᴚ":"R","ᴜ":"U","ᴠ":"V","ᴡ":"W","ʏ":"Y","ᴢ":"Z",
		"á":"a","ă":"a","ắ":"a","ặ":"a","ằ":"a","ẳ":"a","ẵ":"a","ǎ":"a","â":"a","ấ":"a","ậ":"a","ầ":"a","ẩ":"a","ẫ":"a","ä":"a",
		"ǟ":"a","ȧ":"a","ǡ":"a","ạ":"a","ȁ":"a","à":"a","ả":"a","ȃ":"a","ā":"a","ą":"a","ᶏ":"a","ẚ":"a","å":"a","ǻ":"a","ḁ":"a",
		"ⱥ":"a","ã":"a","ꜳ":"aa","æ":"ae","ǽ":"ae","ǣ":"ae","ꜵ":"ao","ꜷ":"au","ꜹ":"av","ꜻ":"av","ꜽ":"ay","ḃ":"b","ḅ":"b","ɓ":"b",
		"ḇ":"b","ᵬ":"b","ᶀ":"b","ƀ":"b","ƃ":"b","ɵ":"o","ć":"c","č":"c","ç":"c","ḉ":"c","ĉ":"c","ɕ":"c","ċ":"c","ƈ":"c","ȼ":"c",
		"ď":"d","ḑ":"d","ḓ":"d","ȡ":"d","ḋ":"d","ḍ":"d","ɗ":"d","ᶑ":"d","ḏ":"d","ᵭ":"d","ᶁ":"d","đ":"d","ɖ":"d","ƌ":"d","ı":"i",
		"ȷ":"j","ɟ":"j","ʄ":"j","ǳ":"dz","ǆ":"dz","é":"e","ĕ":"e","ě":"e","ȩ":"e","ḝ":"e","ê":"e","ế":"e","ệ":"e","ề":"e","ể":"e",
		"ễ":"e","ḙ":"e","ë":"e","ė":"e","ẹ":"e","ȅ":"e","è":"e","ẻ":"e","ȇ":"e","ē":"e","ḗ":"e","ḕ":"e","ⱸ":"e","ę":"e","ᶒ":"e",
		"ɇ":"e","ẽ":"e","ḛ":"e","ꝫ":"et","ḟ":"f","ƒ":"f","ᵮ":"f","ᶂ":"f","ǵ":"g","ğ":"g","ǧ":"g","ģ":"g","ĝ":"g","ġ":"g","ɠ":"g",
		"ḡ":"g","ᶃ":"g","ǥ":"g","ḫ":"h","ȟ":"h","ḩ":"h","ĥ":"h","ⱨ":"h","ḧ":"h","ḣ":"h","ḥ":"h","ɦ":"h","ẖ":"h","ħ":"h","ƕ":"hv",
		"í":"i","ĭ":"i","ǐ":"i","î":"i","ï":"i","ḯ":"i","ị":"i","ȉ":"i","ì":"i","ỉ":"i","ȋ":"i","ī":"i","į":"i","ᶖ":"i","ɨ":"i",
		"ĩ":"i","ḭ":"i","ꝺ":"d","ꝼ":"f","ᵹ":"g","ꞃ":"r","ꞅ":"s","ꞇ":"t","ꝭ":"is","ǰ":"j","ĵ":"j","ʝ":"j","ɉ":"j","ḱ":"k","ǩ":"k",
		"ķ":"k","ⱪ":"k","ꝃ":"k","ḳ":"k","ƙ":"k","ḵ":"k","ᶄ":"k","ꝁ":"k","ꝅ":"k","ĺ":"l","ƚ":"l","ɬ":"l","ľ":"l","ļ":"l","ḽ":"l",
		"ȴ":"l","ḷ":"l","ḹ":"l","ⱡ":"l","ꝉ":"l","ḻ":"l","ŀ":"l","ɫ":"l","ᶅ":"l","ɭ":"l","ł":"l","ǉ":"lj","ſ":"s","ẜ":"s","ẛ":"s",
		"ẝ":"s","ḿ":"m","ṁ":"m","ṃ":"m","ɱ":"m","ᵯ":"m","ᶆ":"m","ń":"n","ň":"n","ņ":"n","ṋ":"n","ȵ":"n","ṅ":"n","ṇ":"n","ǹ":"n",
		"ɲ":"n","ṉ":"n","ƞ":"n","ᵰ":"n","ᶇ":"n","ɳ":"n","ñ":"n","ǌ":"nj","ó":"o","ŏ":"o","ǒ":"o","ô":"o","ố":"o","ộ":"o","ồ":"o",
		"ổ":"o","ỗ":"o","ö":"o","ȫ":"o","ȯ":"o","ȱ":"o","ọ":"o","ő":"o","ȍ":"o","ò":"o","ỏ":"o","ơ":"o","ớ":"o","ợ":"o","ờ":"o",
		"ở":"o","ỡ":"o","ȏ":"o","ꝋ":"o","ꝍ":"o","ⱺ":"o","ō":"o","ṓ":"o","ṑ":"o","ǫ":"o","ǭ":"o","ø":"o","ǿ":"o","õ":"o","ṍ":"o",
		"ṏ":"o","ȭ":"o","ƣ":"oi","ꝏ":"oo","ɛ":"e","ᶓ":"e","ɔ":"o","ᶗ":"o","ȣ":"ou","ṕ":"p","ṗ":"p","ꝓ":"p","ƥ":"p","ᵱ":"p","ᶈ":"p",
		"ꝕ":"p","ᵽ":"p","ꝑ":"p","ꝙ":"q","ʠ":"q","ɋ":"q","ꝗ":"q","ŕ":"r","ř":"r","ŗ":"r","ṙ":"r","ṛ":"r","ṝ":"r","ȑ":"r","ɾ":"r",
		"ᵳ":"r","ȓ":"r","ṟ":"r","ɼ":"r","ᵲ":"r","ᶉ":"r","ɍ":"r","ɽ":"r","ↄ":"c","ꜿ":"c","ɘ":"e","ɿ":"r","ś":"s","ṥ":"s","š":"s",
		"ṧ":"s","ş":"s","ŝ":"s","ș":"s","ṡ":"s","ṣ":"s","ṩ":"s","ʂ":"s","ᵴ":"s","ᶊ":"s","ȿ":"s","ɡ":"g","ᴑ":"o","ᴓ":"o","ᴝ":"u",
		"ť":"t","ţ":"t","ṱ":"t","ț":"t","ȶ":"t","ẗ":"t","ⱦ":"t","ṫ":"t","ṭ":"t","ƭ":"t","ṯ":"t","ᵵ":"t","ƫ":"t","ʈ":"t","ŧ":"t",
		"ᵺ":"th","ɐ":"a","ᴂ":"ae","ǝ":"e","ᵷ":"g","ɥ":"h","ʮ":"h","ʯ":"h","ᴉ":"i","ʞ":"k","ꞁ":"l","ɯ":"m","ɰ":"m","ᴔ":"oe","ɹ":"r",
		"ɻ":"r","ɺ":"r","ⱹ":"r","ʇ":"t","ʌ":"v","ʍ":"w","ʎ":"y","ꜩ":"tz","ú":"u","ŭ":"u","ǔ":"u","û":"u","ṷ":"u","ü":"u","ǘ":"u",
		"ǚ":"u","ǜ":"u","ǖ":"u","ṳ":"u","ụ":"u","ű":"u","ȕ":"u","ù":"u","ủ":"u","ư":"u","ứ":"u","ự":"u","ừ":"u","ử":"u","ữ":"u",
		"ȗ":"u","ū":"u","ṻ":"u","ų":"u","ᶙ":"u","ů":"u","ũ":"u","ṹ":"u","ṵ":"u","ᵫ":"ue","ꝸ":"um","ⱴ":"v","ꝟ":"v","ṿ":"v","ʋ":"v",
		"ᶌ":"v","ⱱ":"v","ṽ":"v","ꝡ":"vy","ẃ":"w","ŵ":"w","ẅ":"w","ẇ":"w","ẉ":"w","ẁ":"w","ⱳ":"w","ẘ":"w","ẍ":"x","ẋ":"x","ᶍ":"x",
		"ý":"y","ŷ":"y","ÿ":"y","ẏ":"y","ỵ":"y","ỳ":"y","ƴ":"y","ỷ":"y","ỿ":"y","ȳ":"y","ẙ":"y","ɏ":"y","ỹ":"y","ź":"z","ž":"z",
		"ẑ":"z","ʑ":"z","ⱬ":"z","ż":"z","ẓ":"z","ȥ":"z","ẕ":"z","ᵶ":"z","ᶎ":"z","ʐ":"z","ƶ":"z","ɀ":"z","ﬀ":"ff","ﬃ":"ffi",
		"ﬄ":"ffl","ﬁ":"fi","ﬂ":"fl","ĳ":"ij","œ":"oe","ﬆ":"st","ₐ":"a","ₑ":"e","ᵢ":"i","ⱼ":"j","ₒ":"o","ᵣ":"r","ᵤ":"u","ᵥ":"v","ₓ":"x"}
	};

String.prototype.toLatinChars = function(){return this.replace(/[^A-Za-z0-9\[\] ]/g,function(a){return toLatinChars.latin_map[a]||a})};
String.prototype.isLatin = function(){return this==this.toLatinChars()};
//==================================================================================//
//==================================================================================//


//====================================== M A I N ===================================//
//==================================================================================//
var commonReady = function()
{
    $('header .bg').css('height', g.loadScreenHeight);
    $('#footer .bg').css('height', $('#footer .bg').css('height'));

	handleOldBrowsers();
	initBasics();
	initForm();
	new scrollHandler();
	// resizeHandler();
	// new imagePreloader(['vietnam-map.png', 'logo.jpg']);

    if ($('#lightbox').length)               handleLightbox();
    if ($('#cookieNotice').length)           handleCookieNotice();
    if ($('[data-original]').length)         lazyload();
    if ($('.slideshow').length)              handleSlideshow();
	if ($('.social').length && !localhost)   handleSocials();
    if ($('.comments').length)               handleComments();
    if (g.screenWidth <= 550)                handleMobileMenu();
};
//================================= end of  M A I N ================================//
//==================================================================================//




//================================= F U N C T I O N S ==============================//
//==================================================================================//
//==================================================================================//
//==================================================================================//
