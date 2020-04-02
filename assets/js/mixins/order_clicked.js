import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';

export default {
    props: {
        basePrecision: Number,
        quotePrecision: Number,
        loggedIn: Boolean,
    },
    computed: {
        ...mapGetters('makeOrder', [
            'getBaseBalance',
            'getQuoteBalance',
            'getUseSellMarketPrice',
            'getUseBuyMarketPrice',
        ]),
    },
    methods: {
        orderClicked: function(order) {
            if (!this.loggedIn) return;

            if (!this.getUseSellMarketPrice) {
                this.setSellPriceInput(toMoney(order.price, this.basePrecision));
            }

            if (!this.getUseBuyMarketPrice) {
                this.setBuyPriceInput(toMoney(order.price, this.basePrecision));
            }

            this.setSellAmountInput(
                parseFloat(order.amount) > parseFloat(this.getQuoteBalance)
                    ? toMoney(this.getQuoteBalance, this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );

            this.setBuyAmountInput(
                new Decimal(order.amount).mul(order.price).greaterThan(this.getBaseBalance)
                    ? toMoney(new Decimal(this.getBaseBalance).div(order.price).toString(), this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );
        },
        ...mapMutations('makeOrder', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setBuyPriceInput',
            'setBuyAmountInput',
        ]),
    },
};
