
var generator = require('generate-password'); 

window.onload = function() {
    $('.password-generator-button').on('click', function() {
        let input = $('.password-generator-input');
        let password = generator.generate({
            length: 9,
            numbers: true,
            uppercase: true,
            symbols: true,
        });
        input.val(password);
    });
};
