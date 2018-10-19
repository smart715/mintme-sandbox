<template>
    <div class="row">
        <token-trade-buy-order
            container-class="buy-order col-12 col-md-6 col-lg-4"
            :websocket-url="websocketUrl"
            :login-url="loginUrl"
            :signup-url="signupUrl"
            :logged-in="loggedIn"
            :token-name="tokenName"
            :place-order-url="placeOrderUrl"
        />
        <token-trade-sell-order
            container-class="sell-order mt-3 mt-md-0 col-12 col-md-6 col-lg-4"
            :websocket-url="websocketUrl"
            :login-url="loginUrl"
            :signup-url="signupUrl"
            :logged-in="loggedIn"
            :token-name="tokenName"
            :place-order-url="placeOrderUrl"
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
        <order-modal 
            :visible="isModalVisible"
            @showModal="showModal"
            @close="hideModal"
        >
        </order-modal> 
    </div>
</template>

<script>
import TokenTradeBuyOrder from './TokenTradeBuyOrder';
import TokenTradeSellOrder from './TokenTradeSellOrder';
import TokenTradeChart from './TokenTradeChart';
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import TokenTradeTradeHistory from './TokenTradeTradeHistory';
import OrderModal from '../../modal/OrderModal';
export default {
  name: 'TokenTrade',
  props: {
      websocketUrl: String,
      loginUrl: String,
      signupUrl: String,
      loggedIn: Boolean,
      tokenName: String,
      placeOrderUrl: String,
  },
  data() {
    return {
        isModalVisible: false
    };
  },
  components: {
      TokenTradeBuyOrder,
      TokenTradeSellOrder,
      TokenTradeChart,
      TokenTradeBuyOrders,
      TokenTradeSellOrders,
      TokenTradeTradeHistory,
      OrderModal
  },
  methods: {
      showModal: function() {
          this.isModalVisible = true;
      },
      hideModal: function() {
          this.isModalVisible = false;
      }
  }
};
</script>
