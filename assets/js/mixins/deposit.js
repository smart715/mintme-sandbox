import {WEB_IDENTIFIER, BTC_IDENTIFIER} from '../utils/constants';

export default {
    computed: {
        showDepositMoreLink: function() {
            return this.loggedIn && this.isMarketBTCOrWEB();
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
            if (this.action === 'buy') {
                return this.market.base.identifier;
            }

            if (this.action === 'sell') {
                return this.market.quote.identifier;
            }

            return '';
        },
        isMarketBTCOrWEB: function() {
            return [WEB_IDENTIFIER, BTC_IDENTIFIER].includes(this.getMarketIdentifier());
        },
    },
};
