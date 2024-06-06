export default {
    data() {
        return {
            prevInputValues: {},
        };
    },
    methods: {
        checkInput(precision = 4) {
            const selectionStart = event.target.selectionStart;
            const selectionEnd = event.target.selectionEnd;
            const amount = event.target.value;

            const input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            const currentValue = amount.slice(0, selectionStart) + input + amount.slice(selectionEnd);

            if (!this.checkInputValue(currentValue, precision)) {
                event.preventDefault();

                if (!event.cancelable && event.target.id && '' != currentValue) {
                    event.target.value = this.prevInputValues[event.target.id] || '';
                }

                return false;
            }

            if (event.target.id) {
                this.prevInputValues[event.target.id] = currentValue;
            }

            return true;
        },
        checkInputValue(value, precision = 4) {
            const regex = new RegExp(
                `^\\d*(\\.\\d{0,${precision}})?$`
            );

            return regex.test(value);
        },
        checkInputDot() {
            if ('.' === event.target.value) {
                event.target.value = '0.';
            }
        },
    },
};
