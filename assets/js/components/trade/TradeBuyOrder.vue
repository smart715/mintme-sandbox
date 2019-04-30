<template>
    <div  class="h-100">
        <div class="card h-100">
            <div class="card-header text-left">
                Buy Order
                <span  class="card-header-icon">
                    <guide>
                        <template slot="header">
                            Buy Order
                        </template>
                        <template slot="body">
                            Form used to create  an order so you can
                            buy {{ this.market.base.symbol }} or make offer.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div v-if="immutableBalance"
                         class="col-12 col-sm-6 col-md-12 col-xl-6 pr-0 pb-2 pb-sm-0 pb-md-2 pb-xl-0">
                        Your {{ this.market.base.symbol }}:
                        <span class="text-white">
                            {{ immutableBalance | toMoney(precision) }}
                            <guide>
                                <template slot="header">
                                    Your {{ this.market.base.symbol }}
                                </template>
                                <template slot="body">
                                    Your {{ this.market.base.symbol }} balance.
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
                                step="0.00000001"
                                type="checkbox"
                                id="buy-price"
                                class="custom-control-input"
                                >
                            <label
                                class="custom-control-label"
                                for="buy-price">
                                Market Price
                                <guide>
                                    <template slot="header">
                                        Market Price
                                    </template>
                                    <template slot="body">
                                        Checking this box fetches current best market price
                                        for which you can buy {{ this.market.base.symbol }}.
                                    </template>
                                </guide>
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-2">
                        <label
                            for="buy-price-input"
                            class="text-white">
                            Price in {{ this.market.base.symbol }}:
                            <guide>
                                <template slot="header">
                                    Price in {{ this.market.base.symbol }}
                                </template>
                                <template slot="body">
                                    The price at which you want to buy one {{ this.market.quote.symbol }}.
                                </template>
                            </guide>
                        </label>
                        <input
                            v-model.number="buyPrice"
                            step="0.00000001"
                            type="number"
                            id="buy-price-input"
                            class="form-control"
                            :disabled="useMarketPrice"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        <label
                            for="buy-price-amount"
                            class="text-white">
                            Amount:
                        </label>
                        <input
                            v-model.number="buyAmount"
                            step="0.00000001"
                            type="number"
                            id="buy-price-amount"
                            class="form-control"
                            min="0"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        Total Price: {{ totalPrice | toMoney(precision) }} {{ market.base.symbol }}
                        <guide>
                            <template slot="header">
                                Total Price
                            </template>
                            <template slot="body">
                                Total amount to pay, including exchange fee.
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-3 text-left">
                        <button @click="placeOrder"
                            v-if="loggedIn"
                            class="btn btn-primary"
                            :disabled="!fieldsValid">
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
import Guide from '../Guide';
import OrderModal from '../modal/OrderModal';
import WebSocketMixin from '../../mixins/websocket';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';

export default {
    name: 'TradeBuyOrder',
    mixins: [WebSocketMixin],
    components: {
        Guide,
        OrderModal,
    },
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        placeOrderUrl: String,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
        precision: Number,
    },
    data() {
        return {
            immutableBalance: this.balance,
            buyPrice: 0,
            buyAmount: 0,
            useMarketPrice: false,
            action: 'buy',
            showModal: false,
            modalSuccess: false,
        };
    },
    methods: {
        placeOrder: function() {
            if (this.buyPrice && this.buyAmount) {
                let data = {
                    'amountInput': toMoney(this.buyAmount, this.precision),
                    'priceInput': toMoney(this.buyPrice, this.precision),
                    'marketPrice': this.useMarketPrice,
                    'action': this.action,
                };

                this.$axios.single.post(this.$routing.generate('token_place_order', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                }), data)
                    .then((response) => {
                        if (response.data.result === 1) {
                            this.resetOrder();
                            this.$axios.single.get(this.$routing.generate('update_market_status', {
                                base: this.market.base.symbol,
                                quote: this.market.quote.symbol,
                            }));
                        }
                        this.showModalAction(response.data.result);
                    })
                    .catch((error) => this.showModalAction());
            }
        },
        showModalAction: function(result) {
            this.modalSuccess = 1 === result;
            this.showModal = true;
        },
        resetOrder: function() {
            this.buyPrice = 0;
            this.buyAmount = 0;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.buyPrice = this.price || 0;
            }
        },
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.buyPrice || 0).times(this.buyAmount || 0).toString();
        },
        price: function() {
            return toMoney(this.marketPrice, this.precision) || null;
        },
        fieldsValid: function() {
            return this.buyPrice > 0 && this.buyAmount > 0;
        },
    },
    watch: {
        useMarketPrice: function() {
            this.updateMarketPrice();
        },
        marketPrice: function() {
            this.updateMarketPrice();
        },
    },
    mounted: function() {
        if (!this.balance) {
            return;
        }

        this.addMessageHandler((response) => {
            if (
                'asset.update' === response.method &&
                response.params[0].hasOwnProperty(this.market.base.identifier)
            ) {
                this.immutableBalance = response.params[0][this.market.base.identifier].available;
            }
        });
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
};
</script>
