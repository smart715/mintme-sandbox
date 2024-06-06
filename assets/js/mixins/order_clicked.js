import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import TradeCheckInput from './trade_check_input';
import {TRADE_ORDER_INPUT_FLAGS} from '../utils/constants';

export default {
    props: {
        basePrecision: Number,
        quotePrecision: Number,
        loggedIn: Boolean,
    },
    mixins: [TradeCheckInput],
    computed: {
        ...mapGetters('tradeBalance', [
            'getBaseBalance',
            'getQuoteBalance',
            'getUseSellMarketPrice',
            'getUseBuyMarketPrice',
            'getSellPriceInput',
            'getSellAmountInput',
            'getBuyPriceInput',
            'getBuyAmountInput',
        ]),
    },
    methods: {
        orderClicked: function(order) {
            if (!this.loggedIn) return;

            if (!this.getUseSellMarketPrice) {
                this.setSellPriceInput(toMoney(order.price, this.basePrecision));
                this.setSellPriceManuallyEdited(true);
            }

            if (!this.getUseBuyMarketPrice) {
                this.setBuyPriceInput(toMoney(order.price, this.basePrecision));
                this.setBuyPriceManuallyEdited(true);
            }

            this.setSellAmountInput(
                parseFloat(order.amount) > parseFloat(this.getQuoteBalance)
                    ? toMoney(this.getQuoteBalance, this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );

            this.setSellTotalPriceInput(this.getTotalPrice(
                this.getSellPriceInput,
                this.getSellAmountInput,
                this.basePrecision
            ));

            this.setBuyAmountInput(
                new Decimal(order.amount).mul(order.price).greaterThan(this.getBaseBalance)
                    ? toMoney(new Decimal(this.getBaseBalance).div(order.price).toString(), this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );

            this.setBuyTotalPriceInput(this.getTotalPrice(
                this.getBuyPriceInput,
                this.getBuyAmountInput,
                this.basePrecision
            ));

            this.setInputFlag(TRADE_ORDER_INPUT_FLAGS.buyPrice);
            this.setInputFlag(TRADE_ORDER_INPUT_FLAGS.buyAmount);
            this.setInputFlag(TRADE_ORDER_INPUT_FLAGS.sellPrice);
            this.setInputFlag(TRADE_ORDER_INPUT_FLAGS.sellAmount);
        },
        ...mapMutations('tradeBalance', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setSellTotalPriceInput',
            'setBuyPriceInput',
            'setBuyAmountInput',
            'setBuyTotalPriceInput',
            'setSellPriceManuallyEdited',
            'setBuyPriceManuallyEdited',
        ]),
    },
};
