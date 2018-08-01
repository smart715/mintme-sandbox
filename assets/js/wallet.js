import Wallet from '../components/Wallet';
import TradingHistory from '../components/TradingHistory';
import ActiveOrders from '../components/ActiveOrders';
import DepositWithdrawHistory from '../components/DepositWithdrawHistory';
import Tabs from 'bootstrap-vue/es/components';

Vue.use(Tabs);

new Vue({
  el: '#wallet',
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
