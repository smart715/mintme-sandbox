export default {
    props: {
        value: {
            type: [String, Number],
        },
        counter: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        valueLength() {
            if (!this.counter) { // avoid unnecessary calculations
                return 0;
            }

            if (!this.value) {
                return 0;
            }

            if (undefined !== this.value?.length) {
                return this.value.length;
            } else {
                return this.value.toString().length;
            }
        },
    },
};
