/**
 * This script contains contact behaviors only.
 * The contactReady() function is called after commonReady() when DOM is ready.
 */

var contactReady = function()
{
	$('#captcha').hide();
    $('#captchaSwitch').prepend('<input type="checkbox" id="switch"/><label class="switch" for="switch"/>');
    /*$('#contactPage form').on('submit', function(e)
    {
        if (validateForm(this))
        {
        	var This= this;
            $(this).find('#captchaSwitch .flatTooltip').removeClass('show');

            if ($('#switch:checked').length)
            {
                if (!$(this).find('#captchaOk').length) $(this).append('<input type="hidden" id="captchaOk" name="captcha" value="passed">');
                $.getJSON($('#menu .contactMe').attr('href'), $(this).serialize(), function(json)
                {
                	// Output the message from PHP and clear fields in case of success.
                    setMessage(json.message, json.error? 'error' : 'valid', json.error? 'error' : 'success', 'content', [0, null]);
                    if (!json.error) $(This).find('.text').val('');
                });
            }
            else
            {
                setTimeout(function(){$('#captchaSwitch .flatTooltip').addClass('show')}, 500);
            }
        }
        e.preventDefault();
    });*/
};

/**
 * Validation of the user inputs. Used in addition to the HTML5 validation for browsers that do not support html5...
 * Returns boolean true if valid or false if not.
 */
function validateForm(form)
{
    var invalidCount = 0,
    validations =
    {
        name:{pattern:/^[a-zéäëïöüàèìòùâêîôûçñœæ '-]+$/i,error:'This field accepts alphabetic characters and accents only.'},
        login:{pattern:/^[a-z0-9-_]+$/i,error:'This field accepts character ranges a-z, 0-9 and the characters \'-\', \'_\' only.'},
        alphabetic:{pattern:/^[a-z -]+$/i,error:'This field accepts alphabetic characters (a-z), space and dash only.'},
        alphanumeric:{pattern:/^[a-z0-9-]+$/i,error:'This field accepts alphanumeric characters only.'},
        alphanumericxtd:{pattern:/^[a-zéäëïöüàèìòùâêîôûçñœæ0-9 _'".,-]+$/i,error:'This field accepts alphanumeric characters, accents and following characters only: -_.,\'"'},
        numeric:{pattern:/^[0-9]+$/i,error:'This field accepts numeric characters only.'},
        //phone: {pattern: /^\+?[\(\)0-9]+$/, error: "This field accepts phone numbers only."},
        email:{pattern:/^[a-z0-9_][a-z0-9_.]+@[a-z0-9][a-z0-9.]+[a-z0-9]$/,error:'This field accepts email addresses only.'}
    };

    $(form).find('span.error').remove().end()
    .find('.textBox').removeClass('invalid').each(function(i, curr)
    {
        if ($(curr).attr('required') && !validations[$(curr).attr('data-validation')].pattern.test(curr.value))
        {
            var Curr = curr;
            if (!validations[$(curr).attr('data-validation')].pattern.test(curr.value))
            {
                $(curr).addClass('invalid').after('<span class="error"'+(validations[$(curr).attr('data-validation')].error.length<45? ' style="border-radius:2px 3px 3px 1px"' : '')+'>'+validations[$(curr).attr('data-validation')].error+'</span>');
                setTimeout(function()
                {
                    $(Curr).next('.error').animate({marginLeft:'15px', opacity:1}, 500, 'easeOutQuad');
                }, invalidCount*500);
                invalidCount++;
            }
        }
    });
    return !invalidCount;
}