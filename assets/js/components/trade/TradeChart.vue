<template>
    <div>
        <div class="row justify-content-between mb-2">
            <div :class="chartInfoClass" class="mr-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.last_price.header') }}</span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.last_price.guide_header') }}
                        </template>
                        <template slot="body">
                            <span v-html="this.$t('trade.chart.last_price.guide_body', translationsContext)" />
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ toMoneyWithTrailingZeroes(marketStatus.last) }}
                        <coin-avatar
                            :symbol="market.base.symbol"
                            :is-crypto="true"
                        />
                        {{ market.base.symbol | rebranding }}
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
            <div :class="chartInfoClass" class="mx-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.change.header') }}</span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.change.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.chart.change.guide_body') }}
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ marketStatus.change }}%/{{ marketStatus.monthChange }}%
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
            <div :class="chartInfoClass" class="mx-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.volume_token.header') }}</span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.volume_token.guide_header') }}
                        </template>
                        <template slot="body">
                            <span v-html="this.$t('trade.chart.volume_token.guide_body', translationsContext)" />
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ marketStatus.volume | numberTruncateWithLetter }}/
                        {{ marketStatus.monthVolume | numberTruncateWithLetter }}
                        <coin-avatar
                            :symbol="market.quote.symbol"
                            :is-crypto="!this.isToken"
                            :is-user-token="this.isToken"
                            :image="market.quote.image"
                        />
                        {{ volumeSymbol }}
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
            <div :class="chartInfoClass" class="mx-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.volume_crypto.header') }}</span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.volume_crypto.guide_header') }}
                        </template>
                        <template slot="body">
                            <span v-html="this.$t('trade.chart.volume_crypto.guide_body', translationsContext)" />
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ marketStatus.amount | numberTruncateWithLetter }}/
                        {{ marketStatus.monthAmount | numberTruncateWithLetter }}
                        <coin-avatar
                            :symbol="market.base.symbol"
                            :is-crypto="true"
                        />
                        {{ market.base.symbol|rebranding }}
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
            <div :class="chartInfoClass" class="mx-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.buy_depth') }} </span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.buy_depth_guide') }}
                        </template>
                        <template slot="body">
                            <span v-html="buyDepthGuide" />
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ buyDepth | numberTruncateWithLetter }}
                        <coin-avatar
                            :symbol="market.base.symbol"
                            :is-crypto="true"
                        />
                        {{ market.base.symbol | rebranding }}
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
            <div v-if="!isToken" :class="chartInfoClass" class="ml-md-1">
                <div class="d-flex align-items-center justify-content-center">
                    <span>{{ $t('trade.chart.market_cap') }} </span>
                    <guide :key="tooltipKey">
                        <template slot="header">
                            {{ $t('trade.chart.market_cap.body') }}
                        </template>
                        <template slot="body">
                            <span v-html="marketCapInfo" />
                        </template>
                    </guide>
                </div>
                <div class="text-primary">
                    <span v-if="serviceUnavailable">
                        -/-
                    </span>
                    <span v-else-if="!isLoading">
                        {{ marketStatus.marketCap | numberTruncateWithLetter }}
                        <template v-if="marketStatus.marketCap !== '-'">
                            <coin-avatar
                                :symbol="market.base.symbol"
                                :is-crypto="true"
                            />
                            {{ market.base.symbol | rebranding }}
                        </template>
                    </span>
                    <span v-else class="icon-loading"></span>
                </div>
            </div>
        </div>
        <div class="row card">
            <div
                class="ve-candle-container col"
                :class="{'p-0': isToken, 'd-flex justify-content-center align-items-center': isLoading}"
            >
                <div
                    v-if="tradesDisabled"
                    class="trade-chart-no-trades-label text-primary
                        d-flex align-items-center justify-content-center px-2"
                >
                    {{ $t('trade.chart.trades_disabled') }}
                </div>
                <span v-if="serviceUnavailable" class="text-center py-4">
                    {{ this.$t('toasted.error.service_unavailable_short') }}
                </span>
                <div v-else-if="isLoading" class="icon-loading"></div>
                <ve-candle
                    v-else
                    :class="{'trade-chart-blurred pointer-events-none': tradesDisabled}"
                    :extend="additionalAttributes"
                    :data="chartData"
                    :settings="chartSettings"
                    :theme="chartTheme(priceSubunits)"
                    :loading="isLoading"
                    :resize-delay="0"
                ></ve-candle>
            </div>
        </div>
    </div>
</template>

<script>
import VeCandle from '../../utils/candle';
import Guide from '../Guide';
import {
    WebSocketMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    NumberAbbreviationFilterMixin,
} from '../../../js/mixins/';
import {
    toMoney,
    EchartTheme as VeLineTheme,
    getBreakPoint,
    generateMintmeAvatarHtml,
    generateCoinAvatarHtml,
    toMoneyWithTrailingZeroes,
    getPriceAbbreviation,
} from '../../utils';
import moment from 'moment';
import Decimal from 'decimal.js/decimal.js';
import {WEB, GENERAL} from '../../utils/constants.js';
import CoinAvatar from '../CoinAvatar';

export default {
    name: 'TradeChart',
    components: {
        Guide,
        VeCandle,
        CoinAvatar,
    },
    mixins: [
        WebSocketMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        NumberAbbreviationFilterMixin,
    ],
    props: {
        websocketUrl: String,
        market: Object,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        buyDepth: String,
        isToken: Boolean,
        isCreatedOnMintmeSite: Boolean,
        ordersLoaded: Boolean,
        changingMarket: Boolean,
        tradesDisabled: Boolean,
    },
    data() {
        const subunits = this.isToken && this.market.quote.priceDecimals
            ? this.market.quote.priceDecimals
            : this.market.base.subunit;
        const min = 1 / Math.pow(10, subunits);
        return {
            serviceUnavailable: false,
            chartTheme: VeLineTheme,
            tooltipKey: 0,
            chartSettings: {
                labelMap: {
                    '日K': this.$t('trade.chart.indexes'),
                },
                showMA: false,
                showDataZoom: true,
                downColor: '#ff6961',
                upColor: '#77DD77',
                showVol: false,
                start: 0,
                end: 100,
            },
            additionalAttributes: {
                grid: [
                    {
                        top: 20,
                        bottom: 60,
                    },
                    {
                        apply: 'all',
                        left: 75,
                        right: 75,
                    },
                ],
                xAxis: {
                    boundaryGap: true,
                },
                yAxis: [
                    {
                        apply: [0, 1],
                        min,
                        minInterval: min,
                        axisLabel: {
                            formatter: (val) => this.getPriceAbbreviation(val),
                        },
                    },
                    {
                        apply: [1],
                        axisLabel: {
                            show: false,
                        },
                    },
                ],
            },
            marketStatus: {
                volume: '0',
                last: '0',
                change: '0',
                amount: '0',
                monthVolume: '0',
                monthChange: '0',
                monthAmount: '0',
                marketCap: '0',
            },
            lastSymbol: '',
            stats: [],
            maxAvailableDays: 30,
            min,
            monthInfoRequestId: 0,
            marketLoaded: false,
            supply: 1e7,
            volumeSymbol: WEB.symbol === this.market.quote.symbol.toUpperCase()
                ? 'MINTME'
                : 'Tokens',
        };
    },
    computed: {
        priceSubunits() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.market.base.subunit;
        },
        chartInfoClass: function() {
            return 'card px-3 py-2 my-2 font-weight-semibold text-center col-lg-auto col-sm-5';
        },
        translationsContext: function() {
            return {
                quoteSymbol: this.rebrandingFunc(this.market.quote),
                baseSymbol: this.rebrandingFunc(this.market.base.symbol),
                minimumVolumeForMarketcap: this.minimumVolumeForMarketcap,
                baseBlock: generateCoinAvatarHtml({
                    symbol: this.rebrandingFunc(this.market.base.symbol),
                    isCrypto: true,
                }),
                baseAvatarDark: generateCoinAvatarHtml({
                    symbol: this.market.base.symbol,
                    isCrypto: true,
                }),
                quoteBlock: this.isToken
                    ? generateCoinAvatarHtml({
                        image: this.market.quote.image.url,
                        isUserToken: true,
                        symbol: this.rebrandingFunc(this.market.quote.symbol),
                    })
                    : generateCoinAvatarHtml({
                        symbol: this.rebrandingFunc(this.market.quote.symbol),
                        isCrypto: true,
                    }),
                quoteAvatarDark: this.isToken
                    ? generateCoinAvatarHtml({image: this.market.quote.image.url, isUserToken: true})
                    : generateCoinAvatarHtml({
                        symbol: this.rebrandingFunc(this.market.quote.symbol),
                        isCrypto: true,
                    }),
                mintmeBlock: generateMintmeAvatarHtml(),
            };
        },
        isLoading: function() {
            return !this.marketLoaded || !this.ordersLoaded;
        },
        chartRows: function() {
            if (!this.stats || !this.stats.length) {
                return [[new Date().toISOString().slice(0, 10), 0, 0, 0, 0, 0]];
            }

            return this.stats.map((line) => {
                return [
                    this.getDate(line.time),
                    toMoney(line.open, this.priceSubunits),
                    toMoney(line.close, this.priceSubunits),
                    toMoney(line.highest, this.priceSubunits),
                    toMoney(line.lowest, this.priceSubunits),
                    toMoney(line.volume, this.priceSubunits),
                ];
            });
        },
        chartData: function() {
            return {
                columns: [
                    this.$t('trade.chart.date'),
                    this.$t('trade.chart.open'),
                    this.$t('trade.chart.close'),
                    this.$t('trade.chart.highest'),
                    this.$t('trade.chart.lowest'),
                    this.$t('trade.chart.vol'),
                ],
                rows: this.chartRows,
            };
        },
        marketCapInfo: function() {
            return this.isToken
                ? this.$t('trade.chart.market_cap.info.token', this.translationsContext)
                : this.$t('trade.chart.market_cap.info.mintme', this.translationsContext);
        },
        buyDepthGuide: function() {
            return this.isToken
                ? this.$t('trade.chart.buy_depth_guide_body', this.translationsContext)
                : this.$t('trading.coin.buy_depth.help', this.translationsContext);
        },
    },
    watch: {
        chartRows: function(rows) {
            const MIN_RUNGS = 5;

            let max = rows.reduce( (acc, curr) => Decimal.max(acc, ...curr.slice(1, 5)), 0);

            max = max.lessThan(this.min*MIN_RUNGS) ? this.min*MIN_RUNGS : null;

            this.additionalAttributes.yAxis[0].max = max;
        },
        changingMarket: function() {
            // Update market KLine only after buy depth and all values are loaded (to prevent chart flickering)
            if (!this.changingMarket) {
                this.updateMarketKLine();
            }
        },
        market: function() {
            this.tooltipKey += 1;
        },
    },
    mounted() {
        window.addEventListener('resize', this.handleRightLabel);
        this.handleRightLabel();

        if (!this.isToken) {
            if (this.market.quote.symbol.toUpperCase() === WEB.symbol) {
                this.fetchWEBsupply();
            } else {
                this.fetchCirculatingSupply();
            }
        }

        this.updateMarketKLine();
    },
    methods: {
        toMoneyWithTrailingZeroes: function(val) {
            return toMoneyWithTrailingZeroes(val, this.priceSubunits);
        },
        updateMarketKLine: function() {
            this.lastSymbol = this.market.base.symbol;
            this.marketLoaded = false;
            this.$axios.retry.get(this.$routing.generate('market_kline', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
            }))
                .then((res) => {
                    if (this.lastSymbol === this.market.base.symbol) {
                        this.stats = res.data;
                        this.chartSettings.start = this.getStartTradingPeriod();

                        this.addMessageHandler(this.messageHandler.bind(this), 'trade-chart-state', 'TradeChart');

                        this.sendMessage(JSON.stringify({
                            method: 'state.subscribe',
                            params: [this.market.identifier],
                            id: parseInt(Math.random().toString().replace('0.', '')),
                        }));
                        this.sendMessage(JSON.stringify({
                            method: 'kline.subscribe',
                            params: [this.market.identifier, 24 * 60 * 60],
                            id: parseInt(Math.random().toString().replace('0.', '')),
                        }));
                    }
                })
                .catch((err) => {
                    this.serviceUnavailable = true;
                    this.$logger.error('Can not load the chart data', err);
                });
        },
        updateMarketData: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketInfo = marketData.params[1];
            const marketOpenPrice = parseFloat(marketInfo.open);
            const marketLastPrice = parseFloat(marketInfo.last);
            const marketVolume = parseFloat(marketInfo.volume) + parseFloat(marketInfo.volumeDonation);
            const marketAmount = parseFloat(marketInfo.deal) + parseFloat(marketInfo.dealDonation);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            const marketStatus = {
                change: toMoney(changePercentage, 2),
                last: toMoney(marketLastPrice, this.priceSubunits),
                volume: toMoney(marketVolume, this.market.quote.subunit),
                amount: toMoney(marketAmount, this.priceSubunits),
            };

            this.marketStatus = {...this.marketStatus, ...marketStatus};

            this.monthInfoRequestId = parseInt(Math.random().toString().replace('0.', ''));
            this.sendMessage(JSON.stringify({
                method: 'state.query',
                params: [
                    this.market.identifier,
                    30 * 24 * 60 * 60,
                ],
                id: this.monthInfoRequestId,
            }));
        },
        updateMonthMarketData: function(marketData) {
            const marketOpenPrice = parseFloat(marketData.open);
            const marketLastPrice = parseFloat(marketData.last);
            const marketVolume = parseFloat(marketData.volume) + parseFloat(marketData.volumeDonation);
            const marketAmount = parseFloat(marketData.deal) + parseFloat(marketData.dealDonation);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;
            const monthInfo = {
                monthChange: toMoney(changePercentage, 2),
                monthVolume: toMoney(marketVolume, this.market.quote.subunit),
                monthAmount: toMoney(marketAmount, this.priceSubunits),
            };

            if (!this.isToken) {
                if (1e7 === this.supply || 0 === this.supply) {
                    // if fetchWEBsupply() fails
                    this.notifyError(this.$t('toasted.error.can_not_update_market_cap_btc_mintme'));
                    monthInfo.marketCap = '-';
                } else {
                    monthInfo.marketCap = toMoney(
                        Decimal.mul(this.marketStatus.last, this.supply),
                        this.priceSubunits
                    );
                }
            } else if (this.isToken) {
                if (!this.isCreatedOnMintmeSite || marketAmount < this.minimumVolumeForMarketcap) {
                    monthInfo.marketCap = '-';
                } else {
                    this.$axios.retry.get(this.$routing.generate('token_sold_on_market', {
                        name: this.market.quote.symbol,
                    }))
                        .then((res) => {
                            monthInfo.marketCap = toMoney(
                                parseFloat(this.marketStatus.last) * res.data,
                                this.priceSubunits
                            );
                        })
                        .catch((err) => {
                            monthInfo.marketCap = '-';
                            this.$logger.error('Can not load soldOnMarket value', err);
                        })
                        .finally(() => {
                            this.marketStatus = {...this.marketStatus, ...monthInfo};
                        });
                }
            }

            this.marketStatus = {...this.marketStatus, ...monthInfo};
            this.marketLoaded = true;
        },
        getDate: function(timestamp) {
            return moment.utc((timestamp + 3600) * 1000).format('YYYY-MM-DD');
        },
        getStartTradingPeriod: function() {
            if (this.stats.length > this.maxAvailableDays) {
                return Math.floor((this.stats.length - this.maxAvailableDays) / this.stats.length * 100);
            }

            return 0;
        },
        handleRightLabel() {
            this.additionalAttributes.yAxis[1].axisLabel.show = ['lg', 'xl'].includes(getBreakPoint());
        },
        fetchWEBsupply: function() {
            return new Promise((resolve, reject) => {
                const config = {
                    transformRequest: function(data, headers) {
                        delete headers['X-Requested-With'];
                        delete headers['X-CSRF-TOKEN'];
                        delete headers.common;
                    },
                };

                this.$axios.retry.get(this.mintmeSupplyUrl, config)
                    .then((res) => {
                        this.supply = parseFloat(res.data);
                        resolve();
                    })
                    .catch((err) => {
                        this.$logger.error('Can not update WEB circulation supply', err);
                        reject(err);
                    });
            });
        },
        fetchCirculatingSupply: function() {
            this.$axios.retry.get(this.$routing.generate('markets_circulating_supply', {
                symbol: this.market.quote.symbol,
            }))
                .then((res) => {
                    this.supply = res.data.circulatingSupply;
                })
                .catch((err) => {
                    this.supply = 0;
                    this.$logger.error('Can not load circulating supply', err);
                });
        },
        messageHandler: function(result) {
            if ('state.update' === result.method) {
                this.updateMarketData(result);
            }
            if ('kline.update' === result.method && this.lastSymbol === this.market.base.symbol) {
                const lastCandle = this.stats[this.stats.length - 1];

                if (lastCandle && this.getDate(result.params[0][0]) === this.getDate(lastCandle.time)) {
                    this.stats.pop();
                }

                this.stats.push({
                    time: result.params[0][0],
                    open: result.params[0][1],
                    close: result.params[0][2],
                    highest: result.params[0][3],
                    lowest: result.params[0][4],
                    volume: result.params[0][5],
                });
            }
            if (result.id === this.monthInfoRequestId) {
                this.updateMonthMarketData(result.result);
            }
        },
        getPriceAbbreviation: function(val) {
            return this.priceSubunits > GENERAL.precision
                ? getPriceAbbreviation((toMoney(val, this.priceSubunits, false)).toFixed(this.priceSubunits))
                : toMoney(val, this.priceSubunits);
        },
    },
};
</script>
