import {webSymbol, btcSymbol} from '../utils/constants';

export default {
    props: {
        loggedIn: Boolean,
        market: Object,
    },
    data() {
        return {
            action: '',
        };
    },
    computed: {
        showDepositMoreLink: function() {
            return this.loggedIn && this.isMarketBTCOrWEB;
        },
        orderInputClass: function() {
            return this.loggedIn ? 'w-50' : 'w-100';
        },
        depositMoreLink: function() {
            if (this.isMarketBTCOrWEB) {
                return this.$routing.generate('wallet', {
                    depositMore: this.rebrandingFunc(this.marketIdentifier),
                });
            }
        },
        marketIdentifier: function() {
            if ('buy' === this.action) {
                return this.market.base.identifier;
            }

            if ('sell' === this.action) {
                return this.market.quote.identifier;
            }

            return '';
        },
        isMarketBTCOrWEB: function() {
            return [webSymbol, btcSymbol].includes(this.marketIdentifier);
        },
    },
};
