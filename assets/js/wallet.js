import Wallet from '../components/wallet/Wallet';
import TradingHistory from '../components/wallet/TradingHistory';
import ActiveOrders from '../components/wallet/ActiveOrders';
import DepositWithdrawHistory from '../components/wallet/DepositWithdrawHistory';
import Tabs from 'bootstrap-vue/es/components';

new Vue({
  el: '#wallet',
  data() {
    return {
      tabIndex: 0,
      tabIndexsWithoutPadding: [1, 2, 3],
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
    Tabs,
  },
});
