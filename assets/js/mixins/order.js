import {webSymbol, btcSymbol, ethSymbol, usdcSymbol, bnbSymbol, WSAPI} from '../utils/constants';

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
            return this.loggedIn && this.isCryptoMarket;
        },
        orderInputClass: function() {
            return this.loggedIn ? 'w-50' : 'w-100';
        },
        depositMoreLink: function() {
            if (this.isCryptoMarket) {
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
        isCryptoMarket: function() {
            return [webSymbol, btcSymbol, ethSymbol, usdcSymbol, bnbSymbol].includes(this.marketIdentifier);
        },
    },
    methods: {
        getSideByType: function(orderType, isDonationOrder) {
            switch (orderType) {
                case WSAPI.order.type.BUY:
                    return isDonationOrder ? this.$t('donation.order.buy') : this.$t('buy');
                case WSAPI.order.type.SELL:
                    return isDonationOrder ? this.$t('donation.order.sell') : this.$t('sell');
                case WSAPI.order.type.DONATION:
                    return this.$t('donation.order.donation');
            }
        },
    },
};
