<template>
    <div>
        {{ symbol }}{{ convertedAmount }}
    </div>
</template>
<script>
import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import debounce from 'lodash/debounce';

export default {
    name: 'PriceConverter',
    props: {
        amount: [Number, String],
        from: String,
        to: String,
        subunit: Number,
        symbol: String,
        delay: {
            required: false,
            type: Number,
            default: 0,
        },
        convertedAmountProp: String,
    },
    data() {
        return {
            convertedAmount: this.convertedAmountProp,
        };
    },
    methods: {
        ...mapMutations('rates', [
            'setRates',
            'setRequesting',
            'setLoaded',
        ]),
        convert() {
            let amount = !!parseFloat(this.amount) && this.rateLoaded
                ? Decimal.mul(this.amount, this.rate)
                : '0';
            this.convertedAmount = toMoney(amount, this.subunit);
            this.$emit('update:convertedAmountProp',this.convertedAmount);
        },
    },
    computed: {
        ...mapGetters('rates', [
            'getLoaded',
            'getRequesting',
            'getRates',
        ]),
        rate() {
            return (this.getRates[this.from] || [])[this.to];
        },
        rateLoaded() {
            return this.rate !== undefined;
        },
        requestingRates: {
            get() {
                return this.getRequesting;
            },
            set(val) {
                this.setRequesting(val);
            },
        },
    },
    mounted() {
        if (!this.rateLoaded && !this.requestingRates) {
            this.requestingRates = true;
            this.$axios.retry.get(this.$routing.generate('exchange_rates'))
            .then((res) => {
                this.setRates(res.data);
            })
            .catch((err) => {

            })
            .finally(() => {
                this.requestingRates = false;
            });

            this.convertAmount = !!this.delay ? debounce(this.convert, this.delay) : this.convert;
        }
    },
    watch: {
        amount() {
            this.convertAmount();
        },
        rate() {
            this.convertAmount();
        },
    },
};
</script>
