import CopyLink from './components/CopyLink';
import {toMoney} from './utils';
import {NestedSpinner} from './mixins/';
import store from './storage';

new Vue({
    el: '#referral',
    components: {
        CopyLink,
    },
    mixins: [
      NestedSpinner,
    ],
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
        this.showSpinner();
        this.$axios.retry.get(this.$routing.generate('referral_balance'))
            .then((result) => {
                this.referralBalance = result.data.balance;
                this.precision = result.data.token.subunit;
                this.hideSpinner();
            });
    },
    store,
});
