import '../../scss/pages/wallet.sass';
import {BTabs, BTab} from 'bootstrap-vue';
import Wallet from '../components/wallet/Wallet';
import TradingHistory from '../components/wallet/TradingHistory';
import ActiveOrders from '../components/wallet/ActiveOrders';
import DepositWithdrawHistory from '../components/wallet/DepositWithdrawHistory';
import PromotionHistory from '../components/wallet/PromotionHistory';
import tableSortPlugin from '../table_sort_plugin.js';
import store from '../storage';
import i18n from '../utils/i18n/i18n';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faLongArrowAltLeft} from '@fortawesome/free-solid-svg-icons';
import CryptoInit from '../components/CryptoInit';
import {WALLET_TABS} from '../utils/constants';

library.add(faLongArrowAltLeft);

// load the tables sorting plugin
Vue.use(tableSortPlugin);

new Vue({
    el: '#wallet',
    components: {
        BTabs,
        BTab,
        Wallet,
        TradingHistory,
        ActiveOrders,
        DepositWithdrawHistory,
        FontAwesomeIcon,
        PromotionHistory,
        CryptoInit,
    },
    i18n,
    data() {
        return {
            activeTab: 0,
            tabIndexsWithoutPadding: [1, 2, 3],
            depositAddresses: null,
            tokens: null,
            predefinedTokens: null,
            executedHistory: null,
            depositWithdrawHistory: null,
            markets: null,
            orders: null,
            depositMore: '',
        };
    },
    mounted: function() {
        this.depositMore = this.$refs.depositMore.getAttribute('value');
    },
    computed: {
        expandedTab: function() {
            return -1 < this.tabIndexsWithoutPadding.indexOf(this.activeTab);
        },
        depositMoreCurrency: function() {
            return this.depositMore;
        },
    },
    methods: {
        tabUpdated: function() {
            this.depositMore = '';
        },
    },
    watch: {
        activeTab: function() {
            if (!window.history.replaceState) {
                return;
            }

            window.history.replaceState(
                {},
                '',
                this.$routing.generate('wallet', {
                    tab: WALLET_TABS[this.activeTab] || WALLET_TABS[0],
                })
            );
        },
    },
    store,
});
