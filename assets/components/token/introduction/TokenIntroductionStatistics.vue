<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <guide class="float-right">
                    <div slot="header">
                        <h5 class="font-bold">Statistics</h5>
                    </div>
                    <div slot="body">
                        <p>
                            Statistics associated with {{ name }},
                            here you can find out how token creator
                            manage his tokens and if he set any restriction
                            on token release.
                        </p>
                    </div>
                </guide>
                <span class="card-header-icon">
                    <font-awesome-icon
                        v-if="editable && !showSettings"
                        class="float-right c-pointer icon-edit"
                        icon="edit"
                        transform="shrink-4 up-1.5"
                        @click="switchAction"
                        />
                </span>
            </div>
            <div class="card-body">
                <template v-if="loaded">
                <div v-if="!showSettings" class="row">
                    <div class="col">
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
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Token release:
                        </div>
                        <div class="pb-1">
                            Release period: {{ stats.releasePeriod }}
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
                            Hourly installment: {{ stats.hourlyRate }}
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
                            Already released: {{ stats.releasedAmount }}
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
                            Remaining: {{ stats.frozenAmount }}
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
                        :csrf="csrf"
                        :release-period-route="releasePeriodRoute"
                        :period="statsPeriod"
                        :released-disabled="releasedDisabled"
                        @cancel="switchAction"
                        @onStatsUpdate="statsUpdated">
                    </release-period-component>
                </div>
                </template>
                <template v-else>
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {Decimal} from 'decimal.js';
import ReleasePeriodComponent from './TokenIntroductionReleasePeriod';
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import {WSAPI} from '../../../js/utils/constants';

const defaultValue = 'xxx';

export default {
    name: 'TokenIntroductionStatistics',
    components: {
        ReleasePeriodComponent,
        Guide,
    },
    props: {
        name: String,
        releasePeriodRoute: String,
        csrf: String,
        editable: Boolean,
        stats: {
            type: Object,
            default: function() {
                return {
                    releasePeriod: defaultValue,
                    hourlyRate: defaultValue,
                    releasedAmount: defaultValue,
                    frozenAmount: defaultValue,
                };
            },
        },
    },
    data() {
        return {
            showSettings: false,
            tokens: null,
            pendingSellOrders: null,
            executedOrders: null,
        };
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('tokens'))
            .then((res) => this.tokens = {...res.data.common, ...res.data.predefined})
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('executed_orders', {tokenName: this.name}))
            .then((res) => this.executedOrders = res.data)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));

        this.$axios.retry.get(this.$routing.generate('pending_orders', {tokenName: this.name}))
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
            return this.tokens !== null && this.pendingSellOrders !== null && this.executedOrders !== null;
        },
        releasedDisabled: function() {
            return this.stats.releasePeriod !== defaultValue;
        },
        statsPeriod: function() {
            return !this.releasedDisabled ? 10 : this.stats.releasePeriod;
        },
        walletBalance: function() {
            let available = new Decimal(0);
            for (let key in this.tokens) {
                if (this.tokens.hasOwnProperty(key)) {
                    let amount = new Decimal(this.tokens[key]['available']);
                    available = available.plus(amount);
                }
            }
            return toMoney(available.toString());
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
};
</script>
