import Wallet from './components/wallet/Wallet';
import TradingHistory from './components/wallet/TradingHistory';
import ActiveOrders from './components/wallet/ActiveOrders';
import DepositWithdrawHistory from './components/wallet/DepositWithdrawHistory';
import tableSortPlugin from './table_sort_plugin.js';
import store from './storage';
import i18n from './utils/i18n/i18n';

// load the tables sorting plugin
Vue.use(tableSortPlugin);

new Vue({
  el: '#wallet',
  i18n,
  data() {
    return {
      tabIndex: 0,
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
    let urlPath = window.location;
    let parser = document.createElement('a');
    parser.href = urlPath;
    if (parser.pathname === this.$routing.generate('wallet', {tab: 'dw-history'})) {
      this.goToDepositWithdrawalHistoryTab();
      window.history.replaceState({}, '', this.$routing.generate('wallet'));
    }
    this.depositMore = this.$refs.depositMore.getAttribute('value');
  },
  computed: {
    expandedTab: function() {
      return this.tabIndexsWithoutPadding.indexOf(this.tabIndex) > -1;
    },
    depositMoreCurrency: function() {
      return this.depositMore;
    },
  },
  components: {
    Wallet,
    TradingHistory,
    ActiveOrders,
    DepositWithdrawHistory,
  },
  methods: {
    tabUpdated: function() {
      this.depositMore = '';
    },
    goToDepositWithdrawalHistoryTab() {
      this.tabIndex = 2;
    },
  },
  store,
});
