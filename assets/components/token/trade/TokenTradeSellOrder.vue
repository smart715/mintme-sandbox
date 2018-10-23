<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Sell Order
                <guide>
                    <template slot="header">
                        Sell Order Guide
                    </template>
                    <template slot="body">
                        Lorem psum dolor sit amet, consectetur adipisicing elit.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row">
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0"
                        >
                        Your Tokens:
                        <span class="text-primary">
                            {{webBalance}}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Your Tokens Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </span>
                    </div>
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        text-sm-right text-md-left text-xl-right">
                        <label class="custom-control custom-checkbox">
                            <input
                                v-model.number="useMarketPrice"
                                type="checkbox"
                                id="sell-price"
                                class="custom-control-input">
                            <label
                                class="custom-control-label"
                                for="sell-price">
                                Market Price
                                <guide>
                                    <font-awesome-icon
                                        icon="question"
                                        slot='icon'
                                        class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                    <template slot="header">
                                        Market Price Guide
                                    </template>
                                    <template slot="body">
                                        Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                    </template>
                                </guide>
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="sell-price-input"
                            class="text-primary">
                            Price in WEB:
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Price in WEB Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </label>
                        <input
                            v-model.number="sellPrice"
                            type="number"
                            id="sell-price-input"
                            class="form-control"
                            :disabled="useMarketPrice"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="sell-price-amount"
                            class="text-primary">
                            Amount:
                        </label>
                        <input
                            v-model="sellAmount"
                            type="number"
                            id="sell-price-amount"
                            class="form-control"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: {{totalPrice}} WEB
                        <guide>
                            <font-awesome-icon
                                icon="question"
                                slot='icon'
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"/>
                            <template slot="header">
                                Total Price Guide
                            </template>
                            <template slot="body">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button
                            v-if="loggedIn"
                            class="btn btn-primary"
                            :disabled="fieldsValid"
                        >
                            Create sell order
                        </button>
                        <template v-else>
                            <a :href="loginUrl" class="btn btn-primary">Log In</a>
                            <span class="px-2">or</span>
                            <a :href="signupUrl">Sign Up</a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Guide from '../../Guide';

export default {
  name: 'TokenTradeSellOrder',
  components: {
      Guide,
  },
  props: {
      containerClass: String,
      websocketUrl: String,
      loginUrl: String,
      signupUrl: String,
      loggedIn: Boolean,
      tokenName: String,
      placeOrderUrl: String,
      marketName: String,
      sell: Object,
      fetchBalanceUrl: String,
  },
  data() {
    return {
        sellPrice: 0,
        sellAmount: 0,
        useMarketPrice: false,
        action: 'sell',
        webBalance: '',
    };
  },
  methods: {
    placeOrder: function() {
        if (this.sellPrice && this.sellAmount) {
        let data = {
            tokenName: this.tokenName,
            amountInput: this.sellAmount,
            priceInput: this.sellPrice,
            marketPrice: this.useMarketPrice,
            action: this.action,
        };
        axios.post(this.placeOrderUrl, data)
        .then( (response) => {
            console.log(response);
        })
        .catch( (error) => {
            console.log('Axios Error: ' + error);
        });
    }
    },
  },
  computed: {
    totalPrice: function() {
        return this.sellPrice * this.sellAmount;
    },
    market: function() {
        return JSON.parse(this.marketName);
    },
    amount: function() {
        return this.sell.amount || null;
    },
    price: function() {
        return this.sell.price || null;
    },
    fieldsValid: function() {
        if ( this.sellPrice && this.sellAmount ) {
            return false;
        } else {
            return true;
        }
    },
  },
  watch: {
      useMarketPrice: function() {
          if (this.useMarketPrice) {
              this.sellPrice = this.price || 0;
          }
      },
  },
  mounted: function() {
        axios.get(this.fetchBalanceUrl)
        .then( (response) => {
          return this.webBalance = response.data['available'];
        });
    },
};
</script>
