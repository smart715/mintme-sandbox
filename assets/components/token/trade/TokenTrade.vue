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
            :websocket-url="websocketUrl"
            :marketName="marketName"
        />
        <token-trade-buy-orders
            container-class="col-12 col-md-6 mt-3"
            :buy-orders="pendingBuyOrders"
        />
        <token-trade-sell-orders
            container-class="col-12 col-md-6 mt-3"
            :sell-orders="pendingSellOrders"
        />
        <token-trade-trade-history
            container-class="col-12 mt-3"
            :orders-history="ordersHistory"
        />
    </div>
</template>

<script>
import TokenTradeBuyOrder from './TokenTradeBuyOrder';
import TokenTradeSellOrder from './TokenTradeSellOrder';
import TokenTradeChart from './TokenTradeChart';
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import TokenTradeTradeHistory from './TokenTradeTradeHistory';

export default {
  name: 'TokenTrade',
  props: {
      websocketUrl: String,
      marketName: String,
      ordersHistory: String,
      pendingBuyOrders: String,
      pendingSellOrders: String,
  },
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
