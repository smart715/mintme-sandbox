<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Buy Order
                <guide>
                    <template slot="header">
                        Buy Order Guide
                    </template>
                    <template slot="body">
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row">
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0">
                        Your WEB:
                        <span class="text-primary">
                            {{ webBalance }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Your WEB Guide
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
                                v-model="useMarketPrice"
                                type="checkbox"
                                id="buy-price"
                                class="custom-control-input"
                                >
                            <label
                                class="custom-control-label"
                                for="buy-price">
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
                            for="buy-price-input"
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
                            class="text-primary">
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
        <order-modal
            :type="modalSuccess"
            :visible="showModal"
            @close="showModal = false"
        />
    </div>
</template>

<script>
import axios from 'axios';
import Guide from '../../Guide';
import OrderModal from '../../modal/OrderModal';

export default {
  name: 'TokenTradeBuyOrder',
  components: {
        Guide,
        OrderModal,
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
      buy: Object,
      fetchBalanceWebUrl: String,
  },
  data() {
    return {
        buyPrice: 0,
        buyAmount: 0,
        useMarketPrice: false,
        action: 'buy',
        webBalance: '',
        showModal: false,
        modalSuccess: false,
    };
  },
  methods: {
    placeOrder: function() {
        if (this.buyPrice && this.buyAmount) {
        let data = {
            tokenName: this.tokenName,
            amountInput: this.buyAmount,
            priceInput: this.buyPrice,
            marketPrice: this.useMarketPrice,
            action: this.action,
        };

        axios.post(this.placeOrderUrl, data)
        .then( (response) => {
           this.showModalAction(response.data.result);
           this.updateBalance();
        })
        .catch( (error) => {
            this.showModalAction();
        });
    }
    },
    showModalAction: function(result) {
        this.modalSuccess = 1 === result;
        this.showModal = true;
    },
    updateBalance: function() {
        axios.get(this.fetchBalanceWebUrl)
        .then( (response) => {
          return this.webBalance = response.data['available'];
        })
        .catch((error) => {
            if (400 === error.response.status) {
                this.$toasted.error(error.response.data.error);
            } else {
                this.$toasted.error('Connection problem. Try again later.');
            }
        });
    },
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
    fieldsValid: function() {
        return this.buyPrice && this.buyAmount;
    },
  },
  watch: {
      useMarketPrice: function() {
          if (this.useMarketPrice) {
              this.buyPrice = this.price || 0;
          }
      },
  },
  mounted: function() {
        this.updateBalance();
    },
};
</script>
