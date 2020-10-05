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
                            Form used to create  an order so you can sell {{ market.quote | rebranding }} or make offer.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div v-if="balanceLoaded" class="row">
                    <div v-if="loggedIn" class="col-12">
                        <div class="form-group">
                            <label
                                for="sell-price-input"
                                class="text-white">
                                Price in {{ market.base.symbol | rebranding }}:
                            </label>
                            <guide>
                                <template slot="header">
                                    Price in {{ market.base.symbol | rebranding }}
                                </template>
                                <template slot="body">
                                    The price at which you want to sell one {{ market.quote | rebranding }}.
                                </template>
                            </guide>
                        </div>
                        <div class="d-flex">
                            <div class="d-inline-block position-relative h-fit-content" :class="orderInputClass">
                                <input
                                    v-model="sellPrice"
                                    type="text"
                                    id="sell-price-input"
                                    class="form-control"
                                    :class="{ 'trade-price-input': priceInputClass }"
                                    :disabled="useMarketPrice || !loggedIn"
                                    @keypress="checkPriceInput"
                                    @paste="checkPriceInput"
                                    tabindex="8"
                                >
                                <price-converter v-if="loggedIn"
                                    class="position-absolute top-0 right-0 h-100 mr-1 d-flex align-items-center font-size-12"
                                    :class="{ 'trade-price-input-converter': priceInputClass }"
                                    :amount="sellPrice"
                                    :from="market.base.symbol"
                                    :to="USD.symbol"
                                    :subunit="2"
                                    symbol="$"
                                    :delay="1000"
                                    :converted-amount-prop.sync="convertedAmount"
                                />
                            </div>
                            <div v-if="loggedIn && immutableBalance" class="w-50 m-auto pl-4">
                                Your
                                <span>
                                    <span v-if="shouldTruncate" class="c-pointer" @click="balanceClicked"
                                        v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'window', customClass:'tooltip-custom'}">
                                        {{ market.quote | rebranding | truncate(17) }} :
                                    </span>
                                    <span v-else class="c-pointer" @click="balanceClicked">
                                        {{ market.quote | rebranding }} :
                                    </span>
                                    <span class="text-white">
                                        <span class="text-nowrap p-0">
                                            {{ immutableBalance | toMoney(market.quote.subunit) | formatMoney }}
                                                <guide>
                                                    <template slot="header">
                                                        Your {{ tokenSymbol }}
                                                    </template>
                                                    <template slot="body">
                                                        Your {{ market.quote.symbol | rebranding }} balance.
                                                    </template>
                                                </guide>
                                        </span>
                                        <span class="text-nowrap">
                                            <a
                                                v-if="showDepositMoreLink"
                                                :href="depositMoreLink"
                                                tabindex="6"
                                            >Deposit more</a>
                                        </span>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        <label
                            for="sell-price-amount"
                            class="d-flex flex-row flex-nowrap justify-content-start w-50"
                        >
                            <span class="d-inline-block text-nowrap">Amount in </span>
                            <span v-if="shouldTruncate"
                                  v-b-tooltip="{title:market.quote.symbol, boundary:'window', customClass:'tooltip-custom'}"
                                  class="d-inline-block ml-1"
                            >
                                {{ market.quote | rebranding | truncate(17) }}
                            </span>
                            <span v-else class="d-inline-block ml-1">
                                {{ market.quote | rebranding }}
                            </span>
                            <span class="d-inline-block">:</span>
                        </label>
                        <div class="d-flex">
                            <input
                                v-model="sellAmount"
                                type="text"
                                id="sell-price-amount"
                                class="form-control"
                                :class="orderInputClass"
                                :disabled="!loggedIn"
                                @keypress="checkAmountInput"
                                @paste="checkAmountInput"
                                tabindex="9"
                            >
                            <div v-if="loggedIn" class="w-50 m-auto pl-4">
                                <div
                                    v-if="!disabledMarketPrice"
                                    class="form-group custom-control custom-checkbox pb-0">
                                    <input
                                        v-model.number="useMarketPrice"
                                        step="0.00000001"
                                        type="checkbox"
                                        id="sell-price"
                                        class="custom-control-input"
                                        tabindex="7"
                                    >
                                    <label
                                        class="custom-control-label pb-0"
                                        for="sell-price">
                                        Market Price
                                    </label>
                                    <guide>
                                        <template slot="header">
                                            Market Price
                                        </template>
                                        <template slot="body">
                                            Checking this box fetches current best market price
                                            for which you can sell {{ market.quote.symbol | rebranding }}.
                                        </template>
                                    </guide>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        Total Price:
                        {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol | rebranding }}
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
                            tabindex="10"
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
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {
    FiltersMixin,
    PlaceOrder,
    WebSocketMixin,
    MoneyFilterMixin,
    PricePositionMixin,
    RebrandingFilterMixin,
    OrderMixin,
    LoggerMixin,
} from '../../mixins/';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';
import {MINTME, USD} from '../../utils/constants';
import PriceConverter from '../PriceConverter';

export default {
    name: 'TradeSellOrder',
    components: {
        PriceConverter,
        Guide,
    },
    mixins: [
        WebSocketMixin,
        PlaceOrder,
        FiltersMixin,
        MoneyFilterMixin,
        PricePositionMixin,
        RebrandingFilterMixin,
        OrderMixin,
        LoggerMixin,
    ],
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
        isOwner: Boolean,
        balanceLoaded: Boolean,
    },
    data() {
        return {
            action: 'sell',
            placingOrder: false,
            balanceManuallyEdited: false,
            USD,
            mediaMatches: false,
            convertedAmount: '0',
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
        checkAmountInput() {
            this.$emit('check-input', this.market.quote.subunit);
        },
        placeOrder: function() {
            if (this.sellPrice && this.sellAmount) {
                if ((new Decimal(this.sellPrice)).times(this.sellAmount).lessThan(this.minTotalPrice)) {
                    let symbol = this.rebrandingFunc(this.market.base.symbol);
                    this.showNotification({
                        result: 2,
                        message: `Total amount has to be at least ${this.minTotalPrice} ${symbol}`,
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
                        this.showNotification(data);
                        this.placingOrder = false;
                    })
                    .catch((error) => {
                        this.handleOrderError(error);
                        this.sendLogs('error', 'Can not get place order', error);
                    })
                    .then(() => this.placingOrder = false);
            }
        },
        resetOrder: function() {
            if (!this.useMarketPrice) {
                this.sellPrice = 0;
            }
            this.sellAmount = 0;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.sellPrice = this.price || 0;
            } else {
                this.sellPrice = 0;
            }

            if (this.disabledMarketPrice) {
                this.useMarketPrice = false;
            }
        },
        balanceClicked: function(event) {
            // Skip "Deposit more" link
            if ('a' === event.target.tagName.toLowerCase()) {
                return;
            }

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
        tokenSymbol: function() {
            return this.rebrandingFunc(this.market.quote) === MINTME.symbol ? MINTME.symbol : 'Token';
        },
        shouldTruncate: function() {
            return this.market.quote.symbol.length > 17;
        },
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
        priceInputClass: function() {
            return this.mediaMatches || (this.convertedAmount.replace('.', '').length + this.sellPrice.toString().replace('.', '').length) > 24;
        }
    },
    watch: {
        useMarketPrice: function() {
            this.updateMarketPrice();
        },
        marketPrice: function() {
            this.updateMarketPrice();
        },
        balance: function() {
            this.immutableBalance = this.balance;
        },
    },
    mounted: function() {
        this.addMessageHandler((response) => {
            if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.market.quote.identifier)) {
                if (!this.isOwner || this.market.quote.identifier.slice(0, 3) !== 'TOK') {
                    this.immutableBalance = response.params[0][this.market.quote.identifier].available;
                    return;
                }

                this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.name}))
                    .then((res) => this.immutableBalance = res.data ?
                        new Decimal(response.params[0][this.market.quote.identifier].available).sub(
                            res.data.frozenAmountWithReceived
                        ) : response.params[0][this.market.quote.identifier].available
                    )
                    .catch((err) => {
                        this.sendLogs('error', 'Can not get immutable balance', err);
                    });
            }
        }, 'trade-sell-order-asset');

        window.matchMedia('(min-width: 992px) and (max-width: 1199px), (max-width: 575px)').addEventListener('change', (e) => {
                this.mediaMatches = e.matches;
        });
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
};
</script>
