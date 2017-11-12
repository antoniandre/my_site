var lightbox = function(params)
{
    var self = this;

    self.defaults =
    {
        target: $('.lightbox'),
        class: ''
    };
    params = $.extend({}, self.defaults, params);
    self.$target = $(params.target);

    self.open = function(callback)
    {
        self.$target.addClass('show');
        if (typeof params.onOpen === 'function') params.onOpen.call(self.$target);
        if (typeof callback === 'function') callback.call(self.$target);
    };

    self.close = function(callback)
    {
        self.$target.removeClass('show');
        setTimeout(function()
        {
            self.$target.find('.content').html('');
            if (typeof params.onClose === 'function') params.onClose.call(self.$target);
            if (typeof callback === 'function') callback.call(self.$target);
        }, 1000);
    };

    self.bindEvents = function()
    {
		self.$target.on('click', function(e)
		{
			e.preventDefault();
            if ($(e.target).is(self.$target) || $(e.target).is(self.$target.find('.close')))
			{
				self.close();
			}
		});
    };

    self.init = function()
    {
        self.bindEvents();
    }();
};



$.fn.lightbox = function(firstArg)
{
    // The DOM element on which we call the lightbox plugin.
    var lightboxElement = this[0], warn, error;

    // Handle errors and incorrect lightbox calls.
    switch (true)
    {
        case (!lightboxElement || lightboxElement === undefined):
            error = 'Can\'t instantiate the lightbox on an empty jQuery collection.';
            break;
        case (['object', 'undefined', 'string'].indexOf(typeof firstArg) === -1):
            warn = 'Ignoring lightbox call with wrong params.';
            break;
        case (typeof firstArg === 'string' && typeof (lightboxElement).lightbox[firstArg] !== 'function'):
            warn = 'Ignoring unknown lightbox method call "' + firstArg + '".';
            break;
    }
    if (warn || error) console[error ? 'error' : 'warn'](warn || error);

    // Instantiate the lightbox.
    else if (typeof firstArg === 'object' || firstArg === undefined)
    {
        (firstArg || {target: null}).target = lightboxElement;
        lightboxElement.lightbox = new lightbox(firstArg);
    }

    // Call a lightbox method (with params) from its name as a string.
    // E.g. $('.lightbox').lightbox('method', [params]);
    // First check method exists before calling it.
    else if (typeof firstArg === 'string' && lightboxElement !== undefined && typeof (lightboxElement).lightbox[firstArg] === 'function')
    {
        // Extract rest arguments from built-in 'arguments' pseudo-array (no array method avail on arguments).
        var args = [].slice.call(arguments, 1);

        // Call the object method with given args.
        lightboxElement.lightbox[firstArg].apply(this, args);
    }

    return lightboxElement.lightbox;
};
