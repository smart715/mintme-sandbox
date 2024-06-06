export default {
    props: {
        value: {
            type: String,
        },
        label: {
            type: String,
            default: '',
        },
        name: {
            type: String,
            default: null,
        },
        hint: {
            type: String,
            default: null,
        },
        invalid: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        labelPointerEvents: Boolean,
    },
    computed: {
        inputName() {
            return this.name || this.label.toLowerCase().replace(/ /g, '_');
        },
        hasErrors() {
            return this.$slots['errors'] && this.$slots['errors'].some((vnode) => vnode.tag !== undefined);
        },
        formControlFieldClass() {
            return {
                'invalid': this.hasErrors || this.invalid,
                'disabled': this.disabled,
                'has-postfix-icon': this.loading,
            };
        },
    },
    methods: {
        onInput(event) {
            this.$emit('input', event.target.value);
        },
        onChange(event) {
            this.$emit('change', event.target.value);
        },
        onClick(event) {
            this.$emit('click', event);
        },
    },
};
