/**
 * This script contains contact behaviors only.
 * The articleReady() function is called after commonReady() when DOM is ready.
 */

var articleReady = function()
{
    if ($('article.content').length)
    {
        handleArticleFontSize();

        if ($("img[data-original]").length) $("img[data-original]").lazyload({effect: "fadeIn", threshold: 800});

        var articleId = $('article').attr('id');
        $('figure').each(function(i){this.id = articleId+'fig'+i});

        new likesHandler();


        $('.click2show').on('click', function()
        {
            $(this).addClass('show');
        });

        setTimeout(function()
        {
            for (var figId in figureEvents)
            {
                var f = figureEvents[figId];
                $('#'+figId)[f.one ? 'one' : 'on'](f.event, f.handler);
            }
        }, 200);
    }
},


handleArticleFontSize = function()
{
    var defaultFontSize = parseInt($('body').css('font-size')),
        currentFontSize = defaultFontSize;
    $('#frame > .inner')
        .prepend('<div class="fontSizeControl floatRight">'
                +'<button id="fz-increase" title="Augmenter la taille de la police">A+</button>'
                +'<button id="fz-reset" title="Réinitialiser la taille de la police">Aº</button>'
                +'<button id="fz-decrease" title="Diminuer la taille de la police">A-</button></div>')
        .on('click', 'button[id^=fz-]', function(e)
        {
            e.preventDefault();
            var action = this.id.replace('fz-', '');
            if (action == 'decrease') currentFontSize--;
            else if (action == 'reset') currentFontSize = defaultFontSize;
            else if (action == 'increase') currentFontSize++;
            $('body').css('font-size', currentFontSize+'px');
        });
},


likesHandler = function()
{
    var self = this;
    this.init = function()
    {
        $.getJSON(document.url, 'getLikes=1', function(items)
        {
            // var articleId = $('article').attr('id');
            $.each($('figure'), function(i, curr)
            {
                var figure = $(curr),
                    // data-original replaces src if lazy-loaded image.
                    pic = figure.find('[src],[data-original]').eq(0),
                    pattern = new RegExp('^'+ROOT.replace(/\//g, '\\\/'), 'g'),
                    src = ($(pic).attr('src')||$(pic).attr('data-original')).replace(pattern, ''),
                    likes = items.hasOwnProperty(src) ? items[src].likes : 0,
                    liked = items.hasOwnProperty(src) ? items[src].liked : 0;

                figure/*.attr('id', articleId+'fig'+i)*/.append('<div class="likes'+(liked ? ' liked' : '')+'"><button class="i-thumbup" title="Like it!"></button><span>'+likes+'</span></div>');
            });
            $.each($('.comment').not(':first'), function(i, curr)
            {
                var comment = $(curr),
                    id = comment.attr('id'),
                    likes = items.hasOwnProperty(id) ? items[id].likes : 0,
                    liked = items.hasOwnProperty(id) ? items[id].liked : 0;

                comment.append('<div class="likes'+(liked ? ' liked' : '')+'"><button class="i-thumbup" title="Like it!"></button><span>'+likes+'</span></div>');
            });
            self.bindEvents();
        });
    };
    this.bindEvents = function()
    {
        // Targets "figure .likes" or ".comment .likes".
        $('#contentWrapper > .content').on('click', '.likes button:not(.justliked button)', function()
        {
            self.doLike(this);
        })
    };
    this.doLike = function(btn)
    {
        var btn = $(btn),
            likesDiv = btn.parent(),
            liked = likesDiv.hasClass('liked'),
            likeItem = null;

        if (btn.parents('figure').length)
        {
            var pattern = new RegExp('^'+ROOT.replace(/\//g, '\\\/'), 'g');
            likeItem = likesDiv.parent().find('[src]').attr('src').replace(pattern, '');
        }

        else if (btn.parents('.comment').length)
        {
            likeItem = likesDiv.parent().attr('id');

        }

        if (likeItem)
        {
            likesDiv.removeClass('disliked liked').toggleClass(liked ? 'disliked' : 'liked justliked');
            btn.on('mouseleave', function(e){likesDiv.removeClass('disliked justliked');})
               .next().html('<div class="spinner-s"/>');
            $.getJSON(document.url, 'like='+encodeURIComponent(likeItem), function(data){btn.next().text(data)});
        }
    };
    this.init();
};