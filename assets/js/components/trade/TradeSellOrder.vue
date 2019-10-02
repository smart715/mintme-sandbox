<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header text-left">
                Sell Order
                <span  class="card-header-icon">
                    <guide>
                        <template slot="header">
                            Sell Order
                        </template>
                        <template slot="body">
                            Form used to create  an order so you can sell {{ market.quote.symbol }} or make offer.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div v-if="immutableBalance"
                        class="col-12 col-sm-8 col-md-12 col-xl-8 pr-0 pb-2 pb-sm-0 pb-md-2 pb-xl-0 word-break-all"
                        >
                        Your
                        <span class="c-pointer" @click="balanceClicked"
                              v-b-tooltip="{title: market.quote.symbol, boundary:'viewport'}">
                            {{ market.quote.symbol | truncate(7) }}:
                            <span class="text-white  word-break">
                                {{ immutableBalance | toMoney(market.quote.subunit) | formatMoney }}
                                <guide>
                                    <template slot="header">
                                        Your Tokens
                                    </template>
                                    <template slot="body">
                                        Your {{ market.quote.symbol }} balance.
                                    </template>
                                </guide>
                            </span>
                        </span>
                    </div>
                    <div class="col-12 col-sm-4 col-md-12 col-xl-4 text-md-left" :class="marketPricePositionClass">
                        <label class="custom-control custom-checkbox">
                            <input
                                v-model.number="useMarketPrice"
                                step="0.00000001"
                                type="checkbox"
                                id="sell-price"
                                class="custom-control-input"
                                :disabled="disabledMarketPrice"
                            >
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
                                        for which you can sell {{ market.quote.symbol }}.
                                    </template>
                                </guide>
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-2">
                        <label
                            for="sell-price-input"
                            class="text-white">
                            Price in {{ market.base.symbol }}:
                            <guide>
                                <template slot="header">
                                    Price in {{ market.base.symbol }}
                                </template>
                                <template slot="body">
                                    The price at which you want to sell one {{ market.quote.symbol }}.
                                </template>
                            </guide>
                        </label>
                        <input
                            v-model="sellPrice"
                            type="text"
                            id="sell-price-input"
                            class="form-control"
                            :disabled="useMarketPrice || !loggedIn"
                            @keypress="checkPriceInput"
                            @paste="checkPriceInput"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        <label
                            for="sell-price-amount"
                            class="text-white">
                            Amount in {{ market.quote.symbol }}:
                        </label>
                        <input
                            v-model="sellAmount"
                            type="text"
                            id="sell-price-amount"
                            class="form-control"
                            @keypress="$emit('check-input', market.quote.subunit)"
                            @paste="$emit('check-input', market.quote.subunit)"
                            :disabled="!loggedIn"
                        >
                    </div>
                    <div class="col-12 pt-2">
                        Total Price:
                        {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol }}
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
                            :disabled="!buttonValid"
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
            :title="modalTitle"
            :visible="showModal"
            @close="showModal = false"
        />
    </div>
</template>

<script>
import Guide from '../Guide';
import OrderModal from '../modal/OrderModal';
import {FiltersMixin, PlaceOrder, WebSocketMixin, MoneyFilterMixin, PricePositionMixin} from '../../mixins';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';

export default {
    name: 'TradeSellOrder',
    components: {
        Guide,
        OrderModal,
    },
    mixins: [WebSocketMixin, PlaceOrder, FiltersMixin, MoneyFilterMixin, PricePositionMixin],
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
        isOwner: Boolean,
    },
    data() {
        return {
            action: 'sell',
            placingOrder: false,
            balanceManuallyEdited: false,
        };
    },
    methods: {
        setBalanceManuallyEdited: function(val = true) {
            this.balanceManuallyEdited = val;
        },
        checkPriceInput() {
            this.$emit('check-input', this.market.base.subunit);
            this.setBalanceManuallyEdited(true);
        },
        placeOrder: function() {
            if (this.sellPrice && this.sellAmount) {
                if ((new Decimal(this.sellPrice)).times(this.sellAmount).lessThan(this.minTotalPrice)) {
                    this.showModalAction({
                        result: 2,
                        message: `Total amount has to be at least ${this.minTotalPrice} ${this.market.base.symbol}`,
                    });
                    return;
                }

                this.placingOrder = true;
                let data = {
                    'amountInput': toMoney(this.sellAmount, this.market.quote.subunit),
                    'priceInput': toMoney(this.sellPrice, this.market.base.subunit),
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
                        this.placingOrder = false;
                    })
                    .catch((error) => this.handleOrderError(error))
                    .then(() => this.placingOrder = false);
            }
        },
        resetOrder: function() {
            this.sellPrice = 0;
            this.sellAmount = 0;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.sellPrice = this.price || 0;
            }
            if (this.disabledMarketPrice) {
                this.useMarketPrice = false;
            }
        },
        balanceClicked: function() {
            if (!this.balanceManuallyEdited || !parseFloat(this.sellPrice)) {
                this.sellPrice = toMoney(this.price || 0, this.market.base.subunit);
                this.setBalanceManuallyEdited(false);
            }

            this.sellAmount = toMoney(this.immutableBalance, this.market.quote.subunit);
        },
        ...mapMutations('makeOrder', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setQuoteBalance',
            'setUseSellMarketPrice',
        ]),
    },
    computed: {
        totalPrice: function() {
            return new Decimal(this.sellPrice && !isNaN(this.sellPrice) ? this.sellPrice : 0)
                .times(this.sellAmount && !isNaN(this.sellAmount) ? this.sellAmount : 0)
                .toString();
        },
        price: function() {
            return toMoney(this.marketPrice, this.market.base.subunit) || null;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.market.base.subunit, this.market.base.subunit);
        },
        fieldsValid: function() {
            return this.sellPrice > 0 && this.sellAmount > 0;
        },
        buttonValid: function() {
            return this.fieldsValid && !this.placingOrder;
        },
        disabledMarketPrice: function() {
            return !this.marketPrice > 0 || !this.loggedIn;
        },
        ...mapGetters('makeOrder', [
            'getSellPriceInput',
            'getSellAmountInput',
            'getQuoteBalance',
            'getUseSellMarketPrice',
        ]),
        sellPrice: {
            get() {
                return this.getSellPriceInput;
            },
            set(val) {
                this.setSellPriceInput(val);
            },
        },
        sellAmount: {
            get() {
                return this.getSellAmountInput;
            },
            set(val) {
                this.setSellAmountInput(val);
            },
        },
        immutableBalance: {
            get() {
                return this.getQuoteBalance;
            },
            set(val) {
                this.setQuoteBalance(val);
            },
        },
        useMarketPrice: {
            get() {
                return this.getUseSellMarketPrice;
            },
            set(val) {
                this.setUseSellMarketPrice(val);
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
            if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.market.quote.identifier)) {
                if (!this.isOwner || this.market.quote.identifier.slice(0, 3) !== 'TOK') {
                    this.immutableBalance = response.params[0][this.market.quote.identifier].available;
                    return;
                }

                this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.name}))
                    .then((res) => this.immutableBalance = res.data ?
                            new Decimal(response.params[0][this.market.quote.identifier].available).sub(
                                res.data.frozenAmount
                            ) : response.params[0][this.market.quote.identifier].available
                    )
                    .catch(() => {});
            }
        }, 'trade-sell-order-asset');
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
};
</script>
