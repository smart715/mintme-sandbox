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
                         class="col-12 col-sm-8 col-md-12 col-xl-8 pr-0 pb-2 pb-sm-0 pb-md-2 pb-xl-0">
                        Your <span class="c-pointer" @click="balanceClicked">{{ this.market.base.symbol }}</span>:
                        <span class="text-white word-break">
                            {{ immutableBalance | toMoney(market.base.subunit) | formatMoney }}
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
                        class="col-12 col-sm-4 col-md-12 col-xl-4
                        text-sm-right text-md-left text-xl-right">
                        <label class="custom-control custom-checkbox">
                            <input
                                v-model="useMarketPrice"
                                step="0.00000001"
                                type="checkbox"
                                id="buy-price"
                                class="custom-control-input"
                                :disabled="disabledMarketPrice"
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
                            Price in {{ market.base.symbol }}:
                            <guide>
                                <template slot="header">
                                    Price in {{ market.base.symbol }}
                                </template>
                                <template slot="body">
                                    The price at which you want to buy one {{ this.market.quote.symbol }}.
                                </template>
                            </guide>
                        </label>
                        <input
                            v-model="buyPrice"
                            type="text"
                            id="buy-price-input"
                            class="form-control"
                            @keypress="$emit('check-input', market.base.subunit)"
                            @paste="$emit('check-input', market.base.subunit)"
                            :disabled="useMarketPrice"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        <label
                            for="buy-price-amount"
                            class="text-white">
                            Amount:
                        </label>
                        <input
                            v-model="buyAmount"
                            type="text"
                            id="buy-price-amount"
                            @keypress="$emit('check-input', market.quote.subunit)"
                            @paste="$emit('check-input', market.quote.subunit)"
                            class="form-control"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        Total Price: {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol }}
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
            :title="modalTitle"
            :visible="showModal"
            @close="showModal = false"
        />
    </div>
</template>

<script>
import Guide from '../Guide';
import OrderModal from '../modal/OrderModal';
import {WebSocketMixin, PlaceOrder, MoneyFilterMixin} from '../../mixins';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';

export default {
    name: 'TradeBuyOrder',
    mixins: [WebSocketMixin, PlaceOrder, MoneyFilterMixin],
    components: {
        Guide,
        OrderModal,
    },
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
    },
    data() {
        return {
            action: 'buy',
        };
    },
    methods: {
        placeOrder: function() {
            if (this.buyPrice && this.buyAmount) {
                let data = {
                    'amountInput': toMoney(this.buyAmount, this.market.quote.subunit),
                    'priceInput': toMoney(this.buyPrice, this.market.base.subunit),
                    'marketPrice': this.useMarketPrice,
                    'action': this.action,
                };

                this.$axios.single.post(this.$routing.generate('token_place_order', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                }), data)
                    .then(({data}) => {
                        if (data.result === 1) {
                            this.resetOrder();
                        }
                        this.showModalAction(data);
                    })
                    .catch((error) => this.handleOrderError(error));
            }
        },
        resetOrder: function() {
            this.buyPrice = 0;
            this.buyAmount = 0;
            this.useMarketPrice = false;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.buyPrice = this.price || 0;
            }
            if (this.disabledMarketPrice) {
                this.useMarketPrice = false;
            }
        },
        balanceClicked: function() {
            if (this.price > 0) {
                this.buyPrice = this.price;
                this.buyAmount = toMoney(
                    new Decimal(this.immutableBalance).div(this.price).toString(),
                    this.market.quote.subunit
                );
            }
        },
        ...mapMutations('makeOrder', [
            'setBuyPriceInput',
            'setBuyAmountInput',
            'setBaseBalance',
            'setUseBuyMarketPrice',
        ]),
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.buyPrice || 0).times(this.buyAmount || 0).toString();
        },
        price: function() {
            return toMoney(this.marketPrice, this.market.base.subunit) || null;
        },
        fieldsValid: function() {
            return this.buyPrice > 0 && this.buyAmount > 0;
        },
        disabledMarketPrice: function() {
            return !this.marketPrice > 0;
        },
        ...mapGetters('makeOrder', [
            'getBuyPriceInput',
            'getBuyAmountInput',
            'getBaseBalance',
            'getUseBuyMarketPrice',
        ]),
        buyPrice: {
            get() {
                return this.getBuyPriceInput;
            },
            set(val) {
                this.setBuyPriceInput(val);
            },
        },
        buyAmount: {
            get() {
                return this.getBuyAmountInput;
            },
            set(val) {
                this.setBuyAmountInput(val);
            },
        },
        immutableBalance: {
            get() {
                return this.getBaseBalance;
            },
            set(val) {
                this.setBaseBalance(val);
            },
        },
        useMarketPrice: {
            get() {
                return this.getUseBuyMarketPrice;
            },
            set(val) {
                this.setUseBuyMarketPrice(val);
            },
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
        this.immutableBalance = this.balance;

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
        }, 'trade-buy-order-asset');
    },
};
</script>
