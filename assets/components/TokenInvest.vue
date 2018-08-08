<template>
    <div class="row">
        <token-invest-buy-order
            container-class="buy-order col-12 col-md-6"
        />
        <token-invest-sell-order
            container-class="sell-order mt-3 mt-md-0 col-12 col-md-6"
        />
        <token-invest-chart
            container-class="chart mt-3 mt-lg-0 col-12"
        />
        <token-invest-buy-orders
            container-class="col-12 col-md-6 mt-3"
        />
        <token-invest-sell-orders
            container-class="col-12 col-md-6 mt-3"
        />
        <token-invest-trade-history
            container-class="col-12 mt-3"
        />
    </div>
</template>

<script>
import TokenInvestBuyOrder from '../components/TokenInvestBuyOrder';
import TokenInvestSellOrder from '../components/TokenInvestSellOrder';
import TokenInvestChart from '../components/TokenInvestChart';
import TokenInvestBuyOrders from '../components/TokenInvestBuyOrders';
import TokenInvestSellOrders from '../components/TokenInvestSellOrders';
import TokenInvestTradeHistory from '../components/TokenInvestTradeHistory';

export default {
  name: 'TokenInvest',
  data() {
    return {};
  },
  components: {
      TokenInvestBuyOrder,
      TokenInvestSellOrder,
      TokenInvestChart,
      TokenInvestBuyOrders,
      TokenInvestSellOrders,
      TokenInvestTradeHistory,
  },
};
</script>
