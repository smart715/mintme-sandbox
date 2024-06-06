import RebrandingFilterMixin from './filters/rebranding';
import BnbToBscFilterMixin from './filters/bnbToBsc';

export default {
    mixins: [
        RebrandingFilterMixin,
        BnbToBscFilterMixin,
    ],
    methods: {
        getBlockchainShortName: function(symbol) {
            if (this.$te(`dynamic.blockchain_${symbol}_short_name`)) {
                return this.$t(`dynamic.blockchain_${symbol}_short_name`);
            }

            return this.rebrandingFunc(this.bnbToBscFunc(symbol));
        },
    },
};
