<template>
    <div>
        <textarea
            :name="name"
            :rows="rows"
            :cols="cols"
            class="form-control"
            :title="tooltipMessage"
            v-tippy="tooltipOptions"
            v-model="internalValue"
            @keydown="onKeyDown"
            @mousemove="hideTooltip"
            @focus="$emit('focus', $event)"
        >
        </textarea>
        <div class="left small characters-used">
            {{ $t('form.token.characters_used') }} {{ internalValue.length }} ({{ $t('form.token.min') }} {{ limit }})
        </div>
    </div>
</template>

<script>

export default {
    name: 'LimitedTextarea',
    props: {
        name: {
            type: String,
            default: '',
        },
        value: {
            type: String,
            default: '',
        },
        rows: {
            type: String,
            default: '5',
        },
        cols: {
            type: String,
            default: '30',
        },
        limit: {
            type: String,
            default: '0',
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
            return this.$t('limited_textarea.tooltip', {max: this.max});
        },
        charactersLeft: function() {
            return (parseInt(this.max) - this.internalValue.length);
        },
    },
    watch: {
        internalValue: function(val) {
            this.$emit('input', val);
        },
    },
    methods: {
        onKeyDown(e) {
            if (this.internalValue.length >= parseInt(this.max)) {
                if (48 <= e.keyCode && 90 >= e.keyCode) {
                    this.showTooltip(e);
                    e.preventDefault();
                    return;
                }
            } else {
                this.$emit('get-value', this.internalValue);
            }
        },
        showTooltip(e) {
            if ('undefined' != typeof e.target && 'undefined' != typeof e.target._tippy) {
                e.target._tippy.show();
            }
        },
        hideTooltip(e) {
            if ('undefined' != typeof e.target && 'undefined' != typeof e.target._tippy) {
                e.target._tippy.hide();
            }
        },
    },
};
</script>

