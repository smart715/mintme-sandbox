import '../../scss/pages/wallet.sass';
import {BTabs, BTab} from 'bootstrap-vue';
import Wallet from '../components/wallet/Wallet';
import TradingHistory from '../components/wallet/TradingHistory';
import ActiveOrders from '../components/wallet/ActiveOrders';
import DepositWithdrawHistory from '../components/wallet/DepositWithdrawHistory';
import tableSortPlugin from '../table_sort_plugin.js';
import store from '../storage';
import i18n from '../utils/i18n/i18n';

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
  },
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
    let segmentArray = window.location.pathname.split( '/' );
    let lastSegmentPath = segmentArray.pop();
    this.changeTab(lastSegmentPath);
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
  methods: {
    tabUpdated: function() {
      this.depositMore = '';
    },
    changeTab(tab) {
      if ('dw-history' === tab) {
        this.tabIndex = 2;
      }
      if ('trade-history' === tab) {
        this.tabIndex = 1;
      }
      if ('active-orders' === tab) {
        this.tabIndex = 3;
      }
      window.history.replaceState({}, '', this.$routing.generate('wallet'));
    },
  },
  store,
});
