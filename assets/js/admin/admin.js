const generator = require('secure-random-password');

window.onload = function() {
    $('.password-generator-button').on('click', function() {
        let input = $('.password-generator-input');
        let password = generator.randomPassword({
            characters: [generator.upper, generator.digits, generator.symbols],
            length: 9,
        });
        input.val(password);
    });
};
