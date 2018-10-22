<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Buy Order
                <span class="card-header-icon text-white">
                    <font-awesome-icon
                        icon="question"
                        class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                               pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0"
                    >
                        Your WEB:
                        <span class="text-primary">
                            {{webBalance}}
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </span>
                    </div>
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                               text-sm-right text-md-left text-xl-right"
                    >
                        <label class="custom-control custom-checkbox">
                            <input
                                v-model="useMarketPrice"
                                type="checkbox"
                                id="buy-price"
                                class="custom-control-input"                                
                            >
                            <label
                                class="custom-control-label"
                                for="buy-price"
                            >
                                Market Price
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-primary text-white
                                           rounded-circle square blue-question"
                                />
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-input"
                            class="text-primary"
                        >
                            Price in WEB:
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </label>
                        <input
                            v-model.number="buyPrice"
                            type="number"
                            id="buy-price-input"
                            class="form-control"
                            :disabled="useMarketPrice"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-amount"
                            class="text-primary"
                        >
                            Amount:
                        </label>
                        <input
                            v-model.number="buyAmount"
                            type="number"
                            id="buy-price-amount"
                            class="form-control"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: {{totalPrice}} WEB
                        <font-awesome-icon
                            icon="question"
                            class="ml-1 mb-1 bg-primary text-white
                                   rounded-circle square blue-question"
                        />
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button @click="placeOrder" 
                        v-if="loggedIn" 
                        class="btn btn-primary"
                        :disabled="fieldsValid">
                            Create buy order
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
export default {
  name: 'TokenTradeBuyOrder',
  props: {
      containerClass: String,
      websocketUrl: String,
      loginUrl: String,
      signupUrl: String,
      loggedIn: Boolean,
      tokenName: String,
      placeOrderUrl: String,
      marketName: String,
      buy: Object,
      fetchBalanceUrl: String
  },
  data() {
    return {
        buyPrice: 0,
        buyAmount: 0,
        useMarketPrice: false,
        action: 'buy',
        webBalance: ''        
    };
  },
  methods: {
    placeOrder: function() 
    {  if(this.buyPrice && this.buyAmount) {
        let data = {
            tokenName: this.tokenName,
            amountInput: this.buyAmount,
            priceInput: this.buyPrice,
            marketPrice: this.useMarketPrice,
            action: this.action
        };

        axios.post(this.placeOrderUrl, data)
        .then( response => {
           this.$emit('showModal', response.data);
           console.log(response.data.message);
        })
        .catch( error => { 
            console.log('Axios Error: ' + error)
        });
    }
    }
  },
  computed: {
    totalPrice: function() {
        return this.buyPrice * this.buyAmount;
    },
    market: function() {
        return JSON.parse(this.marketName);
    },
    amount: function() {
        return this.buy.amount || null;
    },
    price: function() {
        return this.buy.price || null;
    },
    fieldsValid: function () {
        if ( this.buyPrice && this.buyAmount ){
            return false;
        } else {
            return true;
        }
    }
  },
  watch: {
      useMarketPrice: function() {
          if (this.useMarketPrice) {
              this.buyPrice = this.price || 0;
          }
      }
  },
  mounted: function() {
        axios.get(this.fetchBalanceUrl)
        .then(response => {
          return this.webBalance = response.data["available"];
        });
    }
};
</script>
