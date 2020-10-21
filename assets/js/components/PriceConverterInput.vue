<template>
    <div class="position-relative h-fit-content">
        <input
            ref="input"
            v-model="newValue"
            type="text"
            class="form-control price-converter-input__input"
            :class="{ 'price-converter-input__input--overflow': overflow, ...inputClass }"
            :id="inputId"
            :disabled="disabled"
            :tabindex="tabindex"
            @keypress="$emit('keypress', $event)"
            @paste="$emit('paste', $event)"
            @input="onInput"
            @change="$emit('change', $event)"
            @keyup="$emit('keyup', $event)"
        >
        <price-converter v-if="showConverter"
            class="position-absolute top-0 right-0 h-100 mr-3 d-flex align-items-center text-white"
            :class="{ 'price-converter-input__converter--overflow': overflow }"
            :amount="newValue"
            :converted-amount-prop.sync="convertedValue"
            :from="from"
            :to="to"
            :symbol="symbol"
            :subunit="subunit"
            :delay="1000"
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
        showConverter: {
            type: Boolean,
            default: true,
        },
        inputClass: Object,
    },
    data() {
        return {
            newValue: this.value,
            convertedValue: '0',
            inputWidth: 100,
            resizeObserver: null,
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
        overflow() {
            // maximum amount of characters in a single line, 9 is the width of every number (when font-size is 15px)
            let max = this.inputWidth / 9;
            return this.newValue.toString().length + this.convertedValue.toString().length + this.symbol.length > max;
        },
    },
    methods: {
        onInput() {
            this.$emit('input', this.newValue);
        },
        updateInputWidth() {
            let styles = window.getComputedStyle(this.$refs.input, null);
            let width = parseFloat(styles.getPropertyValue('width'));
            let rightPadding = parseFloat(styles.getPropertyValue('padding-right'));
            let leftPadding = parseFloat(styles.getPropertyValue('padding-left'));
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
