<template>
    <div class="position-relative h-fit-content">
        <input
            ref="input"
            v-model="newValue"
            type="text"
            class="form-control price-converter-input__input pr-1"
            :class="computedInputClass"
            :id="inputId"
            :disabled="disabled"
            :tabindex="tabindex"
            @keypress="$emit('keypress', $event)"
            @paste="$emit('paste', $event)"
            @input="onInput"
            @change="$emit('change', $event)"
            @keyup="$emit('keyup', $event)"
            @focus="$emit('focus', $event)"
            @focusout="$emit('focusout', $event)"
            placeholder="0"
        >
        <price-converter
            v-if="showConverter"
            ref="priceConverter"
            class="position-absolute top-0 right-0 h-100 d-flex align-items-center text-white"
            :class="{ 'price-converter-input__converter--overflow': overflow }"
            :amount="amountToConvert"
            :from="from"
            :to="to"
            :symbol="symbol"
            :subunit="subunit"
            :is-token="isToken"
            :hasParentheses="true"
        />
    </div>
</template>

<script>
import PriceConverter from './PriceConverter';

export default {
    name: 'PriceConverterInput',
    components: {
        PriceConverter,
    },
    props: {
        value: [String, Number],
        disabled: Boolean,
        tabindex: String,
        inputId: String,
        from: String,
        to: String,
        symbol: String,
        subunit: Number,
        convert: {
            type: [String, Number],
            default: null,
        },
        showConverter: {
            type: Boolean,
            default: true,
        },
        inputClass: Object,
        overflowClass: Object,
        isToken: Boolean,
    },
    data() {
        return {
            newValue: this.value,
            inputWidth: 100,
            resizeObserver: null,
            inputOverflowClass: {
                'price-converter-input__input--overflow': true,
            },
        };
    },
    mounted() {
        this.resizeObserver = new ResizeObserver(this.updateInputWidth.bind(this));
        this.resizeObserver.observe(this.$refs.input);
    },
    beforeDestroy() {
        this.resizeObserver.disconnect();
    },
    computed: {
        amountToConvert() {
            return null !== this.convert
                ? this.convert
                : this.newValue;
        },
        computedInputClass() {
            if (this.overflow) {
                return {
                    ...this.inputClass,
                    ...this.inputOverflowClass,
                    ...this.overflowClass,
                };
            }

            return this.inputClass;
        },
        overflow() {
            const convertedValue = this.$refs.priceConverter?.convertedAmount ?? '';
            const max = this.inputWidth / 12;

            // maximum amount of characters in a single line, 12 is the width of every number (when font-size is 15px)
            return this.newValue.toString().length + convertedValue.toString().length + this.symbol.length > max;
        },
    },
    methods: {
        onInput() {
            this.$emit('input', this.newValue);
        },
        updateInputWidth() {
            const styles = window.getComputedStyle(this.$refs.input, null);
            const width = parseFloat(styles.getPropertyValue('width'));
            const rightPadding = parseFloat(styles.getPropertyValue('padding-right'));
            const leftPadding = parseFloat(styles.getPropertyValue('padding-left'));
            this.inputWidth = width - rightPadding - leftPadding;
        },
    },
    watch: {
        value() {
            this.newValue = this.value;
        },
    },
};
</script>
