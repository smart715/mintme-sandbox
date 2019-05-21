import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';

export default {
    props: {
        precision: Number,
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
            if (!this.getUseSellMarketPrice) {
                this.setSellPriceInput(order.price);
            }

            if (!this.getUseBuyMarketPrice) {
                this.setBuyPriceInput(order.price);
            }

            this.setSellAmountInput(
                parseFloat(order.amount) > parseFloat(this.getQuoteBalance) ? this.getQuoteBalance : order.amount
            );

            this.setBuyAmountInput(
                new Decimal(order.amount).mul(order.price).greaterThan(this.getBaseBalance)
                    ? toMoney(new Decimal(this.getBaseBalance).div(order.price).toString(), this.precision)
                    : order.amount
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
