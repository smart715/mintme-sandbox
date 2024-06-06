export default {
    methods: {
        parseFloatInput: function(value) {
            if ('.' === value) {
                value = `0${value}`;
            }
            return value;
        },
    },
};
