window.onload = function ()
{
    $('.password-generator-button').on('click' ,function ()
    {
        var input = $('.password-generator-input');
        var password = $.passGen({
            'length' : 9,
            'numeric' : true,
            'lowercase' : true,
            'uppercase' : true,
            'special' : true
        });
        input.val(password);
    });
}
