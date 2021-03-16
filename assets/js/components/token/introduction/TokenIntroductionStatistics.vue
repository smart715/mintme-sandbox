<template>
    <div>
        <div class="card">
            <template v-if="shouldShowStats && loaded">
                <div class="card-header">
                    {{ $t('token.intro.statistics.header') }}
                    <guide class="float-right">
                        <div slot="header">
                            <h5 class="font-bold">{{ $t('token.intro.statistics.guide_header') }}</h5>
                        </div>
                        <template slot="body">
                            <span v-html="this.statisticGuideTranslation">
                            </span>
                        </template>
                    </guide>
                </div>
                <div class="card-body px-0">
                        <div class="d-flex flex-column px-3">
                            <div v-if="isTokenDeployed">
                                <div>
                                    <strong class="mr-2">{{ $t('token.intro.statistics.token_address') }}</strong>
                                </div>
                                <div class="truncate-address d-flex flex-row flex-nowrap mt-auto">
                                    <span>{{ tokenContractAddress }}</span>
                                    <div  class="token-address-buttons">
                                        <copy-link
                                            class="c-pointer"
                                            :content-to-copy="tokenContractAddress"
                                        >
                                            <font-awesome-icon :icon="['far', 'copy']" class="icon-default"/>
                                        </copy-link>
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.token_address.header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.token_address.body') }}
                                            </template>
                                        </guide>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="pt-3">
                                <div class="font-weight-bold px-3 pb-1">
                                    {{ $t('token.intro.statistics.balance') }}
                                </div>
                                <b-list-group class="flex-nowrap odd-item-bg" horizontal="lg">
                                    <b-list-group-item class="flex-1 odd-item-bg" v-if="isMintmeToken">
                                        {{ $t('token.intro.statistics.exchange.header') }} <br>
                                        {{ walletBalance | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.exchange.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.exchange.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.active.header') }} <br v-if="isMintmeToken">
                                        {{ activeOrdersSum | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.active.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.active.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg" v-if="isMintmeToken">
                                        {{ $t('token.intro.statistics.withdraw.header') }} <br v-if="isMintmeToken">
                                        {{ withdrawBalance | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.withdraw.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.withdraw.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg" >
                                        {{ $t('token.intro.statistics.sold.header') }} <br v-if="isMintmeToken">
                                        {{ soldOnMarket | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.sold.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.sold.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg" >
                                        {{ $t('token.intro.statistics.donation.header') }} <br v-if="isMintmeToken">
                                        {{ donationVolume }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.donation.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.donation.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg" >
                                        {{ $t('token.intro.statistics.holders.header') }}
                                        <br v-if="isMintmeToken">
                                        {{ holdersProp }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.holders.guide_header') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                </b-list-group>
                            </div>
                            <div class="pt-3" v-if="isMintmeToken">
                                <div class="font-weight-bold px-3 pb-1">
                                    {{ $t('token.intro.statistics.token_release.header') }}
                                    <guide>
                                        <template slot="header">
                                            {{ $t('token.intro.statistics.token_release.guide_header') }}
                                        </template>
                                        <template slot="body">
                                            {{ $t('token.intro.statistics.token_release.guide_body') }}
                                        </template>
                                    </guide>
                                </div>
                                <b-list-group class="flex-nowrap odd-item-bg" horizontal="lg">
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.period.header') }} <br>
                                        {{ stats.releasePeriod }}
                                        <template v-if="stats.releasePeriod !== defaultValue">
                                            {{ $t('text.time.year') }}
                                        </template>
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.period.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.period.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.hourly.header') }} <br>
                                        {{ stats.hourlyRate | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.hourly.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.hourly.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.already_released.header') }} <br>
                                        {{ stats.releasedAmount | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.already_released.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.already_released.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.not_yet_released.header') }} <br>
                                        {{  stats.frozenAmount | toMoney(precision, false) | formatMoney }}
                                        <guide>
                                            <template slot="header">
                                                {{ $t('token.intro.statistics.not_yet_released.guide_header') }}
                                            </template>
                                            <template slot="body">
                                                {{ $t('token.intro.statistics.not_yet_released.guide_body') }}
                                            </template>
                                        </guide>
                                    </b-list-group-item>
                                    <b-list-group-item class="flex-1 odd-item-bg">
                                        {{ $t('token.intro.statistics.created') }} <br>
                                        {{ tokenCreated }}
                                    </b-list-group-item>
                                </b-list-group>
                            </div>
                        </div>
                </div>
            </template>
            <template v-else>
                <div class="card-body">
                    <div class="text-center">
                        <template v-if="!shouldShowStats">
                            <b-link @click="showStats">{{ $t('token.intro.statistics.show') }}</b-link>
                        </template>
                        <template v-else>
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import CopyLink from '../../CopyLink';
import Guide from '../../Guide';
import {Decimal} from 'decimal.js';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {toMoney} from '../../../utils';
import {tokenDeploymentStatus} from '../../../utils/constants';
import {mapGetters, mapMutations} from 'vuex';
import {
  LoggerMixin,
  MoneyFilterMixin,
  WebSocketMixin,
} from '../../../mixins';

const defaultValue = '-';

export default {
    name: 'TokenIntroductionStatistics',
    mixins: [
        MoneyFilterMixin,
        LoggerMixin,
        WebSocketMixin,
    ],
    components: {
        CopyLink,
        FontAwesomeIcon,
        Guide,
    },
    props: {
        isMintmeToken: Boolean,
        deploymentStatus: String,
        market: Object,
        precision: Number,
        tokenContractAddress: String,
        tokenCreated: String,
        websocketUrl: String,
        soldOnMarketProp: {
            type: Number,
            default: null,
        },
        donationVolumeProp: {
            type: Number,
            default: null,
        },
        tokenWithdrawnProp: {
            type: Number,
            default: null,
        },
        tokenExchangeProp: {
            type: Number,
            default: null,
        },
        activeOrders: {
            type: Number,
            default: null,
        },
        statsProp: {
          type: Object,
          default: null,
        },
        holdersProp: {
            type: Number,
            default: null,
        },
    },
    data() {
        return {
            pendingSellOrders: null,
            soldOnMarket: this.soldOnMarketProp,
            defaultValue: defaultValue,
            tokenWithdrawn: this.tokenWithdrawnProp,
            donationVolume: this.donationVolumeProp,
            shouldShowStats: false,
        };
    },
    methods: {
        ...mapMutations('tokenStatistics', [
            'setStats',
            'setTokenExchangeAmount',
        ]),
        getTokenWithdrawn: function() {
            this.$axios.retry.get(this.$routing.generate('token_withdrawn', {name: this.market.quote.symbol}))
                .then((res) => this.tokenWithdrawn = res.data)
                .catch((err) => {
                  this.sendLogs('error', 'Can not load token withdrawn value', err);
                });
        },
        getLockPeriod: function() {
            this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.symbol}))
                .then((res) => this.stats = res.data || this.stats)
                .catch((err) => {
                  this.sendLogs('error', 'Can not load statistic data', err);
                });
        },
        getTokExchangeAmount: function() {
            this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.market.quote.symbol}))
                .then((res) => this.tokenExchangeAmount = res.data)
                .catch((err) => {
                  this.sendLogs('error', 'Can not load statistic data', err);
                });
        },
        getTokenSoldOnMarket: function() {
            this.$axios.retry.get(this.$routing.generate('token_sold_on_market', {
              name: this.market.quote.symbol,
            }))
                .then((res) => this.soldOnMarket = res.data)
                .catch((err) => {
                  this.sendLogs('error', 'Can not load soldOnMarket value', err);
                });
        },
        getPendingOrders: function() {
            this.$axios.retry.get(this.$routing.generate('pending_orders', {
              base: this.market.base.symbol,
              quote: this.market.quote.symbol,
            }))
                .then((res) => this.pendingSellOrders = res.data.sell)
                .catch((err) => {
                  this.sendLogs('error', 'Can not load statistic data', err);
                });
        },
        getMarketStatus: function() {
            this.$axios.retry.get(this.$routing.generate('market_status', {
              base: this.market.base.symbol,
              quote: this.market.quote.symbol,
            })).then((res) => {
              this.donationVolume = res.data.volumeDonation || 0;
            }).catch((err) => {
              this.sendLogs('error', 'Can not load market status', err);
            });
        },
        showStats: function() {
            if (!this.loaded) {
                this.fetchAllData();
            }

            this.shouldShowStats = true;
        },
        fetchAllData: function() {
            if (this.isMintmeToken) {
                if (null === this.tokenWithdrawnProp) {
                    this.getTokenWithdrawn();
                }

                if (!this.statsProp) {
                    this.getLockPeriod();
                } else {
                    this.stats = this.statsProp;
                }

                if (null === this.tokenExchangeProp) {
                    this.getTokExchangeAmount();
                } else {
                    this.tokenExchangeAmount = this.tokenExchangeProp;
                }
            }

            if (null === this.soldOnMarket) {
                this.getTokenSoldOnMarket();
            }

            if (!this.activeOrders) {
                this.getPendingOrders();
            }

            if (null === this.donationVolume) {
                this.getMarketStatus();
            }

            this.sendMessage(JSON.stringify({
                method: 'kline.subscribe',
                params: [this.market.identifier, 24 * 60 * 60],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((result) => {
                if ('kline.update' === result.method) {
                    this.donationVolume = result.params[0][8] || 0;
                }
            }, null, 'TokenIntroductionStatistics');
        },
    },
    computed: {
        statisticGuideTranslation: function() {
            return this.$t(
                this.isMintmeToken
                    ? 'token.intro.statistics.guide_body.mintme_token'
                    : 'token.intro.statistics.guide_body.eth_token',
                this.translationsContext
            );
        },
        translationsContext: function() {
            return {
                symbol: this.market.quote.symbol,
            };
        },
        loaded: function() {
            return (!this.isMintmeToken || this.tokenExchangeAmount !== null)
                && this.soldOnMarket !== null && (this.pendingSellOrders !== null || this.activeOrders !== null);
        },
        walletBalance: function() {
            return toMoney(this.tokenExchangeAmount);
        },
        activeOrdersSum: function() {
            if (this.activeOrders) {
                return this.activeOrders;
            }

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
        isTokenDeployed: function() {
            return tokenDeploymentStatus.deployed === this.deploymentStatus;
        },
    },
    filters: {
        toMoney: function(val, precision, fixedPoint = true) {
            return isNaN(val) ? val : toMoney(val, precision, fixedPoint);
        },
    },
};
</script>
