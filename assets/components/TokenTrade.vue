<template>
    <div class="row">
        <token-trade-buy-order
            container-class="buy-order col-12 col-md-6 col-lg-4"
        />
        <token-trade-sell-order
            container-class="sell-order mt-3 mt-md-0 col-12 col-md-6 col-lg-4"
        />
        <token-trade-chart
            container-class="chart mt-3 mt-lg-0 col-12 col-lg-4"
        />
        <token-trade-buy-orders
            container-class="col-12 col-md-6 mt-3"
        />
        <token-trade-sell-orders
            container-class="col-12 col-md-6 mt-3"
        />
        <token-trade-trade-history
            container-class="col-12 mt-3"
        />
    </div>
</template>

<script>
import TokenTradeBuyOrder from '../components/TokenTradeBuyOrder';
import TokenTradeSellOrder from '../components/TokenTradeSellOrder';
import TokenTradeChart from '../components/TokenTradeChart';
import TokenTradeBuyOrders from '../components/TokenTradeBuyOrders';
import TokenTradeSellOrders from '../components/TokenTradeSellOrders';
import TokenTradeTradeHistory from '../components/TokenTradeTradeHistory';

export default {
  name: 'TokenTrade',
  data() {
    return {};
  },
  components: {
      TokenTradeBuyOrder,
      TokenTradeSellOrder,
      TokenTradeChart,
      TokenTradeBuyOrders,
      TokenTradeSellOrders,
      TokenTradeTradeHistory,
  },
};
</script>
