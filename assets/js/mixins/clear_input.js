export default {
    methods: {
        clearInput: function(model, setInputVal) {
            this?.$v?.$reset();
            this[model] = setInputVal;
        },
    },
};
