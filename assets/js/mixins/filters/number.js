export default {
    numberFormat: function(value) {
        if (!value) return '0';
        value = new Intl.NumberFormat('en-US').format(value.toFixed(0));

        return value;
    },
};
