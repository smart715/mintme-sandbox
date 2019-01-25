<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Sell Order
                <guide>
                    <template slot="header">
                        Sell Order
                    </template>
                    <template slot="body">
                        Form used to create  an order so you can sell {{ currency }} or make offer.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row">
                    <div v-if="balance"
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0"
                        >
                        Your Tokens:
                        <span class="text-primary">
                            {{ immutableBalance | toMoney  }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Your Tokens
                                </template>
                                <template slot="body">
                                    Your {{ currency }} balance.
                                </template>
                            </guide>
                        </span>
                    </div>
                    <div v-if="balance"
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        text-sm-right text-md-left text-xl-right">
                        <label class="custom-control custom-checkbox">
                            <input
                                v-model.number="useMarketPrice"
                                step="0.00000001"
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
                                        Market Price
                                    </template>
                                    <template slot="body">
                                        Checking this box fetches current best market price
                                        for which you can sell {{ currency }}.
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
                                    Price in WEB
                                </template>
                                <template slot="body">
                                    The price at which you want to sell one {{ currency }}.
                                </template>
                            </guide>
                        </label>
                        <input
                            v-model.number="sellPrice"
                            step="0.00000001"
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
                            step="0.00000001"
                            type="number"
                            id="sell-price-amount"
                            class="form-control"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: {{ totalPrice | toMoney }} WEB
                        <guide>
                            <font-awesome-icon
                                icon="question"
                                slot='icon'
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"/>
                            <template slot="header">
                                Total Price
                            </template>
                            <template slot="body">
                                Total amount to pay, including exchange fee.
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button
                            v-if="loggedIn"
                            class="btn btn-primary"
                            :disabled="!fieldsValid"
                            @click="placeOrder"
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
import WebSocketMixin from '../../../js/mixins/websocket';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeSellOrder',
    components: {
        Guide,
        OrderModal,
    },
    mixins: [WebSocketMixin],
    props: {
        containerClass: String,
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        marketName: Object,
        sell: Object,
        balance: String,
        tokenHiddenName: String,
        currency: String,
    },
    data() {
        return {
            immutableBalance: this.balance,
            sellPrice: 0,
            sellAmount: 0,
            useMarketPrice: false,
            action: 'sell',
            showModal: false,
            modalSuccess: false,
        };
    },
    methods: {
        placeOrder: function() {
            if (this.sellPrice && this.sellAmount) {
                let data = {
                    tokenName: this.tokenName,
                    amountInput: toMoney(this.sellAmount),
                    priceInput: toMoney(this.sellPrice),
                    marketPrice: this.useMarketPrice,
                    action: this.action,
                };
                axios.post(this.placeOrderUrl, data)
                    .then((response) => this.showModalAction(response.data.result))
                    .catch((error) => this.showModalAction());
            }
        },
        showModalAction: function(result) {
            this.modalSuccess = 1 === result;
            this.showModal = true;
        },
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.sellPrice || 0).times(this.sellAmount || 0).toString();
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
            return Boolean(this.sellPrice && this.sellAmount);
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
        if (!this.balance) {
            return;
        }

        this.authorize(() => {
              this.sendMessage(JSON.stringify({
                  method: 'asset.subscribe',
                  params: [this.tokenHiddenName],
                  id: parseInt(Math.random()),
              }));
        }, (response) => {
          if ('asset.update' === response.method) {
              this.immutableBalance = response.params[0][this.tokenHiddenName].available;
          }
        });
    },
    filters: {
        toMoney: function(val) {
            return toMoney(val);
        },
    },
};
</script>
