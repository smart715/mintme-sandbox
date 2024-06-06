export default {
    filters: {
        stripHTML: function(val) {
            return val.replace(/<(?:.|\n)*?>/gm, '');
        },
    },
};
