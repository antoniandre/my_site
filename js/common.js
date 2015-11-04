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
	cl = function(){console.log.apply(console, [arguments]);},// Shortcut function for console.log().
	// Check if requested JS file is loaded or not and if it needs a dedicated css. Then load the required files accordingly.

	loadScript = function(scriptName, callback)
	{
		if (!g.scripts[scriptName].loaded)
		{
			if (g.scripts[scriptName].css) loadStyleSheet(scriptName);
			$.getScript(ROOT+'js/'+scriptName+'.js', function(){g.scripts[scriptName].loaded = true;callback();});
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
				$('#contentWrapper').children('.content').add('#topBar').addClass('show');
			});
		};

		this.init();
	},

	/**
	 * Set a message.
	 *
	 * @param String message: the message to display.
	 * @param String icon: the icon to show with the message among valid, info, warning, invalid.
	 * @param String Class: the class to apply to the message container.
	 * @param String position: the position of the message among header or content. default header.
	 * @param Array animation: an array of [delay_before_display, display_duration] in milliseconds. Default [1000, 3000]
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
			if (timeToSlideUp !== null) $message.delay(timeToSlideUp).slideUp(500, 'easeInOutQuad');
		}, 100);
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
		$('#spinner').fadeOut(600, function()
		{
			$('#contentWrapper').children('.content').add('#topBar').addClass('show');
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
	// new imagePreloader(['vietnam-map.png', 'visa-approved.png', 'logo.jpg']);

    if ($('#lightbox').length)               handleLightbox();
    if ($('#cookieNotice').length)           handleCookieNotice();
	if ($('.social').length && !localhost)   handleSocials();
    if ($('.comments').length)               handleComments();
};
//================================= end of  M A I N ================================//
//==================================================================================//




//================================= F U N C T I O N S ==============================//
//==================================================================================//
//==================================================================================//
//==================================================================================//
