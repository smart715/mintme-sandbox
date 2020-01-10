import {WEB_IDENTIFIER, BTC_IDENTIFIER} from '../utils/constants';

export default {
    computed: {
        showDepositMoreLink: function() {
            return this.loggedIn && this.isMarketBTCOrWEB();
        },
        orderInputClass: function() {
            return this.loggedIn ? 'w-50' : 'w-100';
        },
    },
    methods: {
        getDepositMoreLink: function() {
            if (this.isMarketBTCOrWEB()) {
                return this.$routing.generate('wallet', {
                    depositMore: this.getMarketIdentifier(),
                });
            }
        },
        getMarketIdentifier: function() {
            if ('buy' === this.action) {
                return this.market.base.identifier;
            }

            if ('sell' === this.action) {
                return this.market.quote.identifier;
            }

            return '';
        },
        isMarketBTCOrWEB: function() {
            return [WEB_IDENTIFIER, BTC_IDENTIFIER].includes(this.getMarketIdentifier());
        },
    },
};
