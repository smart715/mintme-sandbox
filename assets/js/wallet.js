import Wallet from './components/wallet/Wallet';
import TradingHistory from './components/wallet/TradingHistory';
import ActiveOrders from './components/wallet/ActiveOrders';
import DepositWithdrawHistory from './components/wallet/DepositWithdrawHistory';
import PageLoadSpinner from './components/PageLoadSpinner';
import store from './storage';

new Vue({
  el: '#wallet',
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
      spinnerQuantity: 0,
    };
  },
  computed: {
    expandedTab: function() {
      return this.tabIndexsWithoutPadding.indexOf(this.tabIndex) > -1;
    },
  },
  components: {
    Wallet,
    TradingHistory,
    ActiveOrders,
    DepositWithdrawHistory,
    PageLoadSpinner,
  },
  methods: {
    showSpinner: function() {
      if (!this.spinnerQuantity) {
        this.$refs.spinner.show();
      }
      this.spinnerQuantity = this.spinnerQuantity + 1;
    },
    hideSpinner: function() {
      this.spinnerQuantity = this.spinnerQuantity - 1;
      if (!this.spinnerQuantity) {
        this.$refs.spinner.hide();
      }
    },
  },
  store,
});
