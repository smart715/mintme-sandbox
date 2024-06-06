const generator = require('secure-random-password');

window.onload = function() {
    $('.password-generator-button').on('click', function() {
        const input = $('.password-generator-input');
        const password = generator.randomPassword({
            characters: [generator.upper, generator.digits, generator.symbols],
            length: 9,
        });
        input.val(password);
    });
};

window.onload = function() {
    $.datepicker.setDefaults({
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'],
        dayNamesMin: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
        format: 'dd-mm-yyyy',
        dateFormat: 'dd-mm-yy',
    });

    $('#start_date').datepicker();
    $('#end_date').datepicker();
};
