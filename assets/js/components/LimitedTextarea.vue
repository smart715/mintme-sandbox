<template>
    <textarea
        @keydown="onKeyDown"
        :title="tooltipMessage"
        v-tippy="tooltipOptions"
        v-model="internalValue">
    </textarea>
</template>

<script>
export default {
    name: 'LimitedTextarea',
    props: {
        value: {
            type: String,
            default: '',
        },
        max: {
            type: String,
            default: '150',
        },
    },
    data() {
        return {
            internalValue: '',
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'custom',
                delay: [200, 0],
            },
        };
    },
    mounted: function() {
        this.internalValue = this.value;
    },
    computed: {
        tooltipMessage: function() {
            return 'The value can not be more than ' + this.max + ' characters';
        },
        charactersLeft: function() {
            return (parseInt(this.max) - this.internalValue.length);
        },
    },
    methods: {
        onKeyDown(e) {
            if (this.internalValue.length >= parseInt(this.max)) {
                if (e.keyCode >= 48 && e.keyCode <= 90) {
                    this.showTooltip(e);
                    e.preventDefault();
                    return;
                }
            } else {
                this.$emit('get-value', this.internalValue);
            }
        },
        showTooltip(e) {
            if (typeof e.target != 'undefined' && typeof e.target._tippy != 'undefined') {
                e.target._tippy.show();
            }
        },
    },
};
</script>

