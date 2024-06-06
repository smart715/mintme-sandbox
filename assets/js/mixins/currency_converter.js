import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import {mapGetters} from 'vuex';

export default {
    methods: {
        /**
         * @param {string} amount
         * @param {number} rate
         * @param {string} symbol
         * @param {int} subunit
         * @param {int} amountSubunits
         * @param {boolean} hideSymbol
         * @return {string}
         */
        currencyConversion: function(amount, rate, symbol, subunit = 2, amountSubunits = 4, hideSymbol = false) {
            const minOrder = Decimal
                .div(this.minOrder, rate)
                .toDP(amountSubunits, Decimal.ROUND_HALF_UP)
                .toString();

            amount = amount === minOrder ? this.minOrder : Decimal.mul(amount, rate);

            return 1 !== rate && !hideSymbol
                ? symbol + toMoney(amount, subunit)
                : toMoney(amount, subunit);
        },
    },
    computed: {
        ...mapGetters('minOrder', {
            minOrder: 'getMinOrder',
        }),
    },
};
