<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <guide class="float-right">
                    <div slot="header">
                        <h5 class="font-bold">Statistics</h5>
                    </div>
                    <template slot="body">
                        Statistics associated with {{ market.quote.symbol }},
                        here you can find out how token creator
                        manage his tokens and if he set any restriction
                        on token release.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <font-awesome-icon
                    v-if="editable && !showSettings"
                    class="float-right c-pointer icon-edit icon-edit-absolute"
                    icon="edit"
                    transform="shrink-4 up-1.5"
                    @click="switchAction"
                    />
                <template v-if="loaded">
                <div v-if="!showSettings" class="row">
                    <div class="col pr-1">
                        <div class="font-weight-bold pb-4">
                            Token balance:
                        </div>
                        <div class="pb-1">
                            Wallet on exchange: {{ walletBalance }}
                            <guide>
                                <template slot="header">
                                    Wallet on exchange
                                </template>
                                <template slot="body">
                                    The amount of token units being held in
                                    token creator's wallet on exchange.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Active orders: {{ activeOrdersSum }}
                            <guide>
                                <template slot="header">
                                    Active orders
                                </template>
                                <template slot="body">
                                    The amount of token units, that token creator currently is selling.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Withdrawn: {{ withdrawBalance }}
                            <guide>
                                <template slot="header">
                                    Withdrawn
                                </template>
                                <template slot="body">
                                    The amount of token units, that token creator withdrew from exchange.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Sold on the market: {{ soldOrdersSum }}
                            <guide>
                                <template slot="header">
                                    Sold on the market
                                </template>
                                <template slot="body">
                                    The amount of token units currently in circulation.
                                </template>
                            </guide>

                        </div>
                    </div>
                    <div class="col px-1">
                        <div class="font-weight-bold pb-4">
                            Token release:
                            <guide max-width="500px">
                                <font-awesome-icon
                                        icon="question"
                                        slot='icon'
                                        class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Token Release Period
                                </template>
                                <template slot="body">
                                    Period it will take for the full release of your newly created token,
                                    something similar to escrow. Mintme acts as 3rd party that ensure
                                    you won’t flood market with all of your tokens which could lower price
                                    significantly, because unlocking all tokens take time. It’s released hourly
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Release period: {{ stats.releasePeriod }}
                            <template v-if="stats.releasePeriod !== defaultValue">years</template>
                            <guide>
                                <template slot="header">
                                    Release period
                                </template>
                                <template slot="body">
                                    Total amount of time it will take to release 100% of the token.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Hourly installment: {{ stats.hourlyRate| toMoney }}
                            <guide>
                                <template slot="header">
                                    Hourly installment
                                </template>
                                <template slot="body">
                                    Amount of token released per hour.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Already released: {{ stats.releasedAmount | toMoney }}
                            <guide>
                                <template slot="header">
                                    Already released
                                </template>
                                <template slot="body">
                                    The amount of token units released to token creator
                                    at the moment of token creation.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Remaining: {{ stats.frozenAmount| toMoney }}
                            <guide>
                                <template slot="header">
                                    Remaining
                                </template>
                                <template slot="body">
                                    Number of tokens that are circulating in
                                    the market and in the general public's hands.
                                </template>
                            </guide>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <release-period-component
                        :release-period-route="releasePeriodRoute"
                        :period="statsPeriod"
                        :released-disabled="releasedDisabled"
                        @cancel="switchAction"
                        @onStatsUpdate="statsUpdated">
                    </release-period-component>
                </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {Decimal} from 'decimal.js';
import ReleasePeriodComponent from './TokenIntroductionReleasePeriod';
import Guide from '../../Guide';
import {toMoney} from '../../../utils';
import {WSAPI} from '../../../utils/constants';

const defaultValue = '-';

export default {
    name: 'TokenIntroductionStatistics',
    components: {
        ReleasePeriodComponent,
        Guide,
    },
    props: {
        market: Object,
        releasePeriodRoute: String,
        editable: Boolean,
    },
    data() {
        return {
            showSettings: false,
            tokenExchangeAmount: null,
            pendingSellOrders: null,
            executedOrders: null,
            isTokenExchanged: true,
            defaultValue: defaultValue,
            stats: {
                releasePeriod: defaultValue,
                hourlyRate: defaultValue,
                releasedAmount: defaultValue,
                frozenAmount: defaultValue,
            },
        };
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {name: this.market.quote.symbol}))
            .then((res) => this.isTokenExchanged = res.data)
            .catch(() => this.$toasted.error('Can not load token data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.symbol}))
            .then((res) => this.stats = res.data || this.stats)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.market.quote.symbol}))
            .then((res) => this.tokenExchangeAmount = res.data)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('executed_orders', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        }))
            .then((res) => this.executedOrders = res.data)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('pending_orders', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        }))
            .then((res) => this.pendingSellOrders = res.data.sell)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));
    },
    methods: {
        switchAction: function() {
            this.showSettings = !this.showSettings;
        },
        statsUpdated: function(res) {
            this.stats = res.data;
        },
    },
    computed: {
        loaded: function() {
            return this.tokenExchangeAmount !== null && this.pendingSellOrders !== null && this.executedOrders !== null;
        },
        releasedDisabled: function() {
            return this.stats.releasePeriod !== defaultValue && this.isTokenExchanged;
        },
        statsPeriod: function() {
            return !this.releasedDisabled ? 10 : this.stats.releasePeriod;
        },
        walletBalance: function() {
            return toMoney(this.tokenExchangeAmount);
        },
        activeOrdersSum: function() {
            let sum = new Decimal(0);
            for (let key in this.pendingSellOrders) {
                if (this.pendingSellOrders.hasOwnProperty(key)) {
                    let amount = new Decimal(this.pendingSellOrders[key]['amount']);
                    sum = sum.plus(amount);
                }
            }
            return toMoney(sum.toString());
        },
        withdrawBalance: function() {
            return toMoney(0);
        },
        soldOrdersSum: function() {
            let sum = new Decimal(0);
            for (let key in this.executedOrders) {
                if (
                        this.executedOrders.hasOwnProperty(key) &&
                        WSAPI.order.type.SELL === parseInt(this.executedOrders[key]['side'])
                ) {
                    let amount = new Decimal(this.executedOrders[key]['amount']);
                    sum = sum.plus(amount);
                }
            }
            return toMoney(sum.toString());
        },
    },
    filters: {
        toMoney: function(val) {
            return isNaN(val) ? val : toMoney(val);
        },
    },
};
</script>
