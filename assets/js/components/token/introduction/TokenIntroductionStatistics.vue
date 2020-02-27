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
                        manages his tokens and if he set any restrictions
                        on token release.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <template v-if="loaded">
                    <div class="row">
                        <div class="col pr-1">
                            <div class="font-weight-bold pb-4">
                                Token balance:
                            </div>
                            <div class="pb-1">
                                Wallet on exchange: <br>
                                {{ walletBalance | toMoney(precision, false) | formatMoney }}
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
                                Active orders: <br>
                                {{ activeOrdersSum | toMoney(precision, false) | formatMoney }}
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
                                Withdrawn: <br>
                                {{ withdrawBalance | toMoney(precision, false) | formatMoney }}
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
                                Sold on the market: <br>
                                {{ soldOnMarket | toMoney(precision, false) | formatMoney }}
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
                                <guide>
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
                                Release period: <br>
                                {{ stats.releasePeriod }}
                                <template v-if="stats.releasePeriod !== defaultValue">year(s)</template>
                                <guide>
                                    <template slot="header">
                                        Release period
                                    </template>
                                    <template slot="body">
                                        Total amount of time it will take to release all tokens.
                                        If the release period is 0 years, it means that the creator
                                        released 100% of the tokens during creation.
                                    </template>
                                </guide>
                            </div>
                            <div class="pb-1">
                                Hourly installment: <br>
                                {{ stats.hourlyRate | toMoney(precision, false) | formatMoney }}
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
                                Already released: <br>
                                {{ stats.releasedAmount | toMoney(precision, false) | formatMoney }}
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
                                Not yet released: <br>
                                {{  stats.frozenAmount | toMoney(precision, false) | formatMoney }}
                                <guide>
                                    <template slot="header">
                                        Not yet released
                                    </template>
                                    <template slot="body">
                                        Number of tokens not yet released to token creator
                                        or sold on the market
                                    </template>
                                </guide>
                            </div>
                            <div class="pb-1">
                                created on: <br>
                                {{ tokenCreated }}
                            </div>
                        </div>
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
import Guide from '../../Guide';
import {toMoney} from '../../../utils';
import {LoggerMixin, MoneyFilterMixin, NotificationMixin} from '../../../mixins';
import {mapGetters, mapMutations} from 'vuex';


const defaultValue = '-';

export default {
    name: 'TokenIntroductionStatistics',
    mixins: [MoneyFilterMixin, NotificationMixin, LoggerMixin],
    components: {
        Guide,
    },
    props: {
        tokenCreated: String,
        market: Object,
        precision: Number,
    },
    data() {
        return {
            pendingSellOrders: null,
            soldOnMarket: null,
            isTokenExchanged: true,
            defaultValue: defaultValue,
            tokenWithdrawn: 0,
        };
    },
    mounted: function() {
      this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {name: this.market.quote.symbol}))
            .then((res) => {
              this.$emit('hide-spinner');
              this.isTokenExchanged = res.data;
            })
            .catch((err) => {
                this.$emit('hide-spinner');
                this.notifyError('Can not load token data. Try again later');
                this.sendLogs('error', 'Can not load token data', err);
            });

        this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.symbol}))
            .then((res) => {
              this.$emit('hide-spinner');
              this.stats = res.data || this.stats;
            })
            .catch((err) => {
              this.$emit('hide-spinner');
                this.notifyError('Can not load statistic data. Try again later');
                this.sendLogs('error', 'Can not load statistic data', err);
            });

        this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.market.quote.symbol}))
            .then((res) => {
              this.$emit('hide-spinner');
              this.tokenExchangeAmount = res.data;
            })
            .catch((err) => {
              this.$emit('hide-spinner');
                this.notifyError('Can not load statistic data. Try again later');
                this.sendLogs('error', 'Can not load statistic data', err);
            });
        this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('token_sold_on_market', {
            name: this.market.quote.symbol,
        }))
            .then((res) => {
              this.$emit('hide-spinner');
              this.soldOnMarket = res.data;
            })
            .catch((err) => {
              this.$emit('hide-spinner');
                this.notifyError('Can not load soldOnMarket value. Try again later');
                this.sendLogs('error', 'Can not load soldOnMarket value', err);
            });
        this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('token_withdrawn', {name: this.market.quote.symbol}))
            .then((res) => {
              this.$emit('hide-spinner');
              this.tokenWithdrawn = res.data;
            })
            .catch((err) => {
                this.$emit('hide-spinner');
                this.notifyError('Can not load token withdrawn statistic data. Try again later');
                this.sendLogs('error', 'Can not load token withdrawn value', err);
            });
       this.$emit('show-spinner');
        this.$axios.retry.get(this.$routing.generate('pending_orders', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        }))
            .then((res) => {
              this.$emit('hide-spinner');
              this.pendingSellOrders = res.data.sell;
            })
            .catch((err) => {
                this.$emit('hide-spinner');
                this.notifyError('Can not load statistic data. Try again later');
                this.sendLogs('error', 'Can not load statistic data', err);
            });
    },
    methods: {
        ...mapMutations('tokenStatistics', [
            'setStats',
            'setTokenExchangeAmount',
        ]),
    },
    computed: {
        loaded: function() {
            return this.tokenExchangeAmount !== null && this.pendingSellOrders !== null && this.soldOnMarket !== null;
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
            return toMoney(this.tokenWithdrawn);
        },
        ...mapGetters('tokenStatistics', [
            'getStats',
            'getTokenExchangeAmount',
        ]),
        tokenExchangeAmount: {
            get() {
                return this.getTokenExchangeAmount;
            },
            set(val) {
                this.setTokenExchangeAmount(val);
            },
        },
        stats: {
            get() {
                return this.getStats;
            },
            set(val) {
                this.setStats(val);
            },
        },
    },
    filters: {
        toMoney: function(val, precision, fixedPoint = true) {
            return isNaN(val) ? val : toMoney(val, precision, fixedPoint);
        },
    },
};
</script>
