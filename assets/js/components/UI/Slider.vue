<template>
    <vue-slider
        v-model="localValue"
        :max="maxValue"
        :disabled="disabled"
        :interval="interval"
        :marks="marks"
    >
        <template v-slot:tooltip>
            <div :class="['slider-tooltip']">
                {{ percentAmountTotal }}
            </div>
        </template>
        <template v-slot:dot>
            <div :class="['trade-slider-dot']"></div>
        </template>
    </vue-slider>
</template>
<script>
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import vueSlider from 'vue-slider-component';

const MARKS_AMOUNT = 5;

export default {
    name: 'm-slider',
    components: {
        vueSlider,
    },
    props: {
        value: {
            type: [String, Number],
        },
        maxValue: {
            type: [String, Number],
        },
        intervalProp: {
            type: Number,
            default: 1,
        },
        disabled: {
            type: Boolean,
        },
        tooltipFormatter: {
            type: String,
            default: '%',
        },
        tabindex: {
            type: String,
        },
        precision: {
            type: Number,
            default: 4,
        },
    },
    mounted() {
        this.setTabindex();
    },
    methods: {
        setTabindex: function() {
            this.$nextTick(() => {
                const slider = this.$el.getElementsByClassName('vue-slider-dot')[0];
                if (slider) {
                    slider.attributes.tabindex.value = Number(this.tabindex);
                }
            });
        },
    },
    computed: {
        localValue: {
            get() {
                return new Decimal(this.value).toString();
            },
            set(value) {
                this.$emit('change', toMoney(value, this.precision));
            },
        },
        marks() {
            const markStep = Decimal.div(this.maxValue, MARKS_AMOUNT - 1).toNumber();
            const marks = [];

            for (let markIndex = 0; markIndex < MARKS_AMOUNT; markIndex++) {
                marks.push(new Decimal(markIndex).mul(markStep).toNumber());
            }

            return marks;
        },
        interval() {
            return Decimal.mul(this.maxValue, Decimal.div(this.intervalProp, 100)).toNumber();
        },
        percentAmountTotal() {
            Decimal.set({rounding: Decimal.ROUND_UP});

            return new Decimal(this.localValue)
                .mul(100)
                .div(this.maxValue)
                .round()
                .toString() + this.tooltipFormatter;
        },
    },
};
</script>
