import Wallet from '../components/Wallet';
import TradingHistory from '../components/TradingHistory';
import ActiveOrders from '../components/ActiveOrders';
import DepositWithdrawHistory from '../components/DepositWithdrawHistory';

new Vue({
  el: '#wallet',
  data() {
    return {
      selectedTab: 'wallet',
    };
  },
  computed: {
    expandedTable: function() {
      let components = [
        'tradingHistory',
        'activeOrders',
        'depositWithdrawHistory',
      ];
      return components.indexOf(this.selectedTab) > -1;
    },
  },
  components: {
    Wallet,
    TradingHistory,
    ActiveOrders,
    DepositWithdrawHistory,
  },
});
