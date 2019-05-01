window.onload = function() {
    $('.password-generator-button').on('click', function() {
        let input = $('.password-generator-input');
        let password = $.passGen({
            'length': 9,
            'numeric': true,
            'lowercase': true,
            'uppercase': true,
            'special': true,
        });
        input.val(password);
    });
};
