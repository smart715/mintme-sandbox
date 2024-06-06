import '../../scss/pages/referral.sass';
import CopyLink from '../components/CopyLink';
import {toMoney} from '../utils';
import i18n from '../utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {RebrandingFilterMixin} from '../mixins';
import CoinAvatar from '../components/CoinAvatar';
import store from '../storage';
import CryptoInit from '../components/CryptoInit';
import {mapGetters} from 'vuex';

library.add(faCircleNotch, faCopy);

new Vue({
    el: '#referral',
    i18n,
    store,
    mixins: [
        RebrandingFilterMixin,
    ],
    components: {
        CopyLink,
        FontAwesomeIcon,
        CoinAvatar,
        CryptoInit,
    },
    data() {
        return {
            referralBalances: 0,
        };
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('referral_balance'))
            .then((result) => {
                this.referralBalances = result.data.balances;
            });
    },
    computed: {
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
    },
    methods: {
        parseAmount: function(amount, symbol) {
            return toMoney(amount, this.enabledCryptosMap[symbol].subunit);
        },
    },

});
