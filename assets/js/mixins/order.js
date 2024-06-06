import {mapGetters} from 'vuex';
import {WSAPI, tokenDeploymentStatus} from '../utils/constants';

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
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
        ]),
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
        showDepositMoreLink: function() {
            return this.loggedIn
                && (this.isCryptoMarket || this.getDeploymentStatus === tokenDeploymentStatus.deployed);
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
            return Object.keys(this.enabledCryptosMap || {}).includes(this.marketIdentifier);
        },
        getCurrencySymbol: function() {
            const symbols = {
                'buy': this.market.base,
                'sell': this.market.quote,
            };

            return symbols[this.action]?.symbol ?? '';
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
