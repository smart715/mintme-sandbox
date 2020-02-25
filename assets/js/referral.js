import CopyLink from './components/CopyLink';
import PageLoadSpinner from './components/PageLoadSpinner';
import {toMoney} from './utils';

new Vue({
    el: '#referral',
    components: {
        CopyLink,
        PageLoadSpinner,
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
                this.$refs.spinner.hide();
            });
    },
});
