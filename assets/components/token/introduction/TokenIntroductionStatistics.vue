<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <guide>
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
                        class="icon float-right c-pointer"
                        size="2x"
                        icon="edit"
                        transform="shrink-4 up-1.5"
                        @click="switchAction"
                        />
                </span>
            </div>
            <div class="card-body">
                <div v-if="!showSettings" class="row">
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Token balance:
                        </div>
                        <div class="pb-1">
                            Wallet on exchange: {{ walletBalance }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
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
        tokens: {type: Object, required: true},
        pendingSellOrders: {type: Array, required: true},
        executedOrders: {type: Array, required: true},
        releasePeriodRoute: String,
        csrf: String,
        containerClass: String,
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
        };
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
