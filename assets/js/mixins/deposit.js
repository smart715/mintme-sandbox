import {WEB_IDENTIFIER, BTC_IDENTIFIER} from '../utils/constants';

export default {
    computed: {
        showDepositMoreLink:  function() {
            return this.loggedIn && [WEB_IDENTIFIER, BTC_IDENTIFIER].includes(this.market.quote.identifier);
        },
    },
    methods: {
        getDepositMoreLink: function () {
            return this.$routing.generate('wallet', {
                depositMore: this.market.base.symbol,
            });
        },
    },
};
