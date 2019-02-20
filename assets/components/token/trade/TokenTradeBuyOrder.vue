<template>
    <div>
        <div class="card h-100">
            <div class="card-header text-center">
                Buy Order
                <guide>
                    <template slot="header">
                        Buy Order
                    </template>
                    <template slot="body">
                        Form used to create  an order so you can
                        buy {{ currency }} or make offer.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row">
                    <div v-if="immutableBalance"
                         class="col-12 col-sm-6 col-md-12 col-xl-6 pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0">
                        Your WEB:
                        <span class="text-primary">
                            {{ immutableBalance | toMoney }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white rounded-circle square blue-question"/>
                                <template slot="header">
                                    Your WEB
                                </template>
                                <template slot="body">
                                    Your {{ currency }} balance.
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
                                    <font-awesome-icon
                                        icon="question"
                                        slot='icon'
                                        class="ml-1 mb-1 bg-primary text-white rounded-circle square blue-question"/>
                                    <template slot="header">
                                        Market Price
                                    </template>
                                    <template slot="body">
                                        Checking this box fetches current best market price
                                        for which you can buy {{ currency }}.
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
                                    class="ml-1 mb-1 bg-primary text-white rounded-circle square blue-question"/>
                                <template slot="header">
                                    Price in WEB
                                </template>
                                <template slot="body">
                                    The price at which you want to but one {{ currency }}.
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
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-amount"
                            class="text-primary">
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
            :modal-title="modalTitle"
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
    name: 'TokenTradeBuyOrder',
    mixins: [WebSocketMixin],
    components: {
        Guide,
        OrderModal,
    },
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        marketName: Object,
        buy: Object,
        balance: [String, Boolean],
        currency: String,
    },
    data() {
        return {
            modalTitle: '',
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
                    'tokenName': this.tokenName,
                    'amountInput': toMoney(this.buyAmount),
                    'priceInput': toMoney(this.buyPrice),
                    'action': this.action,
                };

                this.$axios.single.post(this.placeOrderUrl, data)
                    .then(({data}) => this.showModalAction(data))
                    .catch((error) => this.showModalAction(!error.response ? {result: 2, message: 'Network Error'} : {}));
            }
        },
        showModalAction: function({result = 2, message = ''} = {}) {
            this.modalSuccess = 1 === result;
            this.modalTitle = this.modalSuccess ? '' : message;
            this.showModal = true;
        },
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.buyPrice || 0).times(this.buyAmount || 0).toString();
        },
        amount: function() {
            return this.buy.amount || null;
        },
        price: function() {
            return this.buy.price || null;
        },
        fieldsValid: function() {
            return this.buyPrice > 0 && this.buyAmount > 0;
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
        if (!this.balance) {
            return;
        }

        this.addMessageHandler((response) => {
            if (
                'asset.update' === response.method &&
                response.params[0].hasOwnProperty(this.marketName.currencySymbol)
            ) {
                this.immutableBalance = response.params[0][this.marketName.currencySymbol].available;
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
