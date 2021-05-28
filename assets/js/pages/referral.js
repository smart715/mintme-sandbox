import CopyLink from '../components/CopyLink';
import {toMoney} from '../utils';
import i18n from '../utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faCircleNotch, faCopy);

new Vue({
    el: '#referral',
    i18n,
    components: {
        CopyLink,
        FontAwesomeIcon,
    },
    data() {
        return {
            referralBalance: 0,
            precision: null,
        };
    },
    computed: {
        balance: function() {
            return toMoney(this.referralBalance, this.precision);
        },
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('referral_balance'))
            .then((result) => {
                this.referralBalance = result.data.balance;
                this.precision = result.data.token.subunit;
            });
    },
});
