<template>
    <div>
        ( {{ symbol }} {{ convertedAmount }} )
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
    },
    data() {
        return {
            convertedAmount: '0',
        };
    },
    methods: {
        ...mapMutations('rates', [
            'setRates',
            'setRequesting',
            'setLoaded',
        ]),
        convert() {
            this.convertedAmount = toMoney(Decimal.mul(this.amount, ((this.rates[this.from] || [])[this.to] || 1)), this.subunit);
        },
    },
    computed: {
        ...mapGetters('rates', [
            'getLoaded',
            'getRequesting',
            'getRates',
        ]),
        rates: {
            get() {
                return this.getRates;
            },
            set(val) {
                this.setRates(val);
            },
        },
        ratesLoaded: {
            get() {
                return this.getLoaded;
            },
            set(val) {
                this.setLoaded(val);
            },
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
        if (!this.ratesLoaded && !this.requestingRates) {
            this.requestingRates = true;
            this.$axios.retry.get(this.$routing.generate('exchange_rates'))
            .then((res) => {
                this.rates = res.data;
                this.ratesLoaded = true;
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
    },
};
</script>
