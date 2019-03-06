<template>
    <div>
        <div class="card h-100">
            <div class="card-header text-left">
                Sell Order
                <span  class="card-header-icon">
                    <guide>
                        <template slot="header">
                            Sell Order
                        </template>
                        <template slot="body">
                            Form used to create  an order so you can sell {{ currency }} or make offer.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div v-if="immutableBalance"
                        class="col-12 col-sm-6 col-md-12 col-xl-6 pr-0 pb-2 pb-sm-0 pb-md-2 pb-xl-0"
                        >
                        Your Tokens:
                        <span class="text-white">
                            {{ immutableBalance | toMoney  }}
                            <guide>
                                <template slot="header">
                                    Your Tokens
                                </template>
                                <template slot="body">
                                    Your {{ currency }} balance.
                                </template>
                            </guide>
                        </span>
                    </div>
                    <div class="col-12 col-sm-6 col-md-12 col-xl-6 text-sm-right text-md-left text-xl-right">
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
                    <div class="col-12 pt-2">
                        <label
                            for="sell-price-input"
                            class="text-white">
                            Price in WEB:
                            <guide>
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
                    <div class="col-12 pt-2">
                        <label
                            for="sell-price-amount"
                            class="text-white">
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
                    <div class="col-12 pt-2">
                        Total Price: {{ totalPrice | toMoney }} WEB
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
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
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
                    'amountInput': toMoney(this.sellAmount),
                    'priceInput': toMoney(this.sellPrice),
                    'marketPrice': this.useMarketPrice,
                    'action': this.action,
                };
                this.$axios.single.post(this.placeOrderUrl, data)
                    .then((response) => this.showModalAction(response.data.result))
                    .catch((error) => this.showModalAction());
            }
        },
        showModalAction: function(result) {
            this.modalSuccess = 1 === result;
            this.showModal = true;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.sellPrice = this.price || 0;
            }
        },
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.sellPrice || 0).times(this.sellAmount || 0).toString();
        },
        price: function() {
            return toMoney(this.marketPrice) || null;
        },
        fieldsValid: function() {
            return this.sellPrice > 0 && this.sellAmount > 0;
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
            if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.tokenHiddenName)) {
                this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.tokenName}))
                    .then((res) => {
                        this.immutableBalance = res.data ?
                            new Decimal(response.params[0][this.tokenHiddenName].available).sub(
                                res.data.frozenAmount
                            ) :
                            response.params[0][this.tokenHiddenName].available;
                    })
                    .catch(() => {});
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
