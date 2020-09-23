<template>
    <div class="card">
        <div class="card-body p-2">
            <div class="mx-2 d-flex flex-column flex-lg-row justify-content-between">
                <div class="my-1 text-center text-lg-left">
                    <span>Last price: </span>
                    <guide>
                        <template slot="header">
                            Last price
                        </template>
                        <template slot="body">
                            Price per one {{ market.quote|rebranding }} for last transaction.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.last | formatMoney }} {{ market.base.symbol|rebranding }}
                </div>
                <div class="my-1 text-center">
                    <span>24h/30d change: </span>
                    <guide>
                        <template slot="header">
                            24h/30d change
                        </template>
                        <template slot="body">
                            Price change in last 24 hours and last 30 days.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.change }}%/{{ marketStatus.monthChange }}%
                </div>
                <div class="my-1 text-center">
                    <span>24h/30d volume: </span>
                    <guide>
                        <template slot="header">
                            24h/30d volume
                        </template>
                        <template slot="body">
                            The amount of {{ market.quote |rebranding }} that has been traded in the last 24 hours and the last 30 days.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.volume | formatMoney }}/{{ marketStatus.monthVolume | formatMoney }} {{ volumeSymbol }}
                </div>
                <div class="my-1 text-center">
                    <span>24h/30d volume: </span>
                    <guide>
                        <template slot="header">
                            24h/30d volume
                        </template>
                        <template slot="body">
                            The amount of {{ market.base.symbol|rebranding }} that has been traded in the last 24 hours and the last 30 days.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.amount | formatMoney }}/{{ marketStatus.monthAmount | formatMoney }} {{ market.base.symbol|rebranding }}
                </div>
                <div class="my-1 text-center" v-if="isToken">
                    <span>Buy Depth: </span>
                    <guide>
                        <template slot="header">
                            Buy Depth
                        </template>
                        <template slot="body">
                            Buy depth is amount of buy orders in MINTME on each market. This might better represent token market size than marketcap.
                        </template>
                    </guide>
                    <br>
                    {{ buyDepth | formatMoney }} {{ market.base.symbol|rebranding }}
                </div>
                <div class="my-1 text-center text-lg-right">
                    <span>Market Cap: </span>
                    <guide>
                        <template slot="header">
                            Market Cap
                        </template>
                        <template slot="body">
                            Market cap of {{ market.quote|rebranding }} based on 10 million tokens created. To make it simple to compare them between each other, we consider not yet released tokens as already created. Marketcap is not shown if 30d volume is lower than {{ minimumVolumeForMarketcap | formatMoney }} MINTME.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.marketCap | formatMoney | rebranding }}
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <ve-candle
                        class="m-2"
                        :extend="additionalAttributes"
                        :data="chartData"
                        :settings="chartSettings"
                        :theme="chartTheme(market.base.subunit)"
                        :loading="isKlineEmpty"
                        :resize-delay="0">
                    </ve-candle>
                </div>
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
    LoggerMixin,
} from '../../../js/mixins/';
import {toMoney, EchartTheme as VeLineTheme, getBreakPoint} from '../../utils';
import moment from 'moment';
import Decimal from 'decimal.js/decimal.js';
import {WEB, webBtcSymbol} from '../../utils/constants.js';

export default {
    name: 'TradeChart',
    mixins: [WebSocketMixin, MoneyFilterMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin],
    props: {
        websocketUrl: String,
        market: Object,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        buyDepth: String,
        isToken: Boolean,
    },
    data() {
        let min = 1 / Math.pow(10, this.market.base.subunit);
        return {
            chartTheme: VeLineTheme,
            chartSettings: {
                labelMap: {
                    '日K': 'Indexes',
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
                            formatter: (val) => toMoney(val, this.market.base.subunit),
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
                marketCap: '0 ' + this.market.base.symbol,
            },
            stats: [],
            maxAvailableDays: 30,
            min,
            monthInfoRequestId: 0,
            supply: 1e7,
            volumeSymbol: WEB.symbol === this.market.quote.symbol.toUpperCase()
                ? 'MINTME'
                : 'Tokens',
        };
    },
    computed: {
        isKlineEmpty: function() {
            return this.chartRows.length === 0;
        },
        chartRows: function() {
            if (!this.stats || !this.stats.length) {
                return [[new Date().toISOString().slice(0, 10), 0, 0, 0, 0, 0]];
            }

            return this.stats.map((line) => {
                 return [
                    this.getDate(line.time),
                    toMoney(line.open, this.market.base.subunit),
                    toMoney(line.close, this.market.base.subunit),
                    toMoney(line.highest, this.market.base.subunit),
                    toMoney(line.lowest, this.market.base.subunit),
                    toMoney(line.volume, this.market.base.subunit),
                 ];
              });
        },
        chartData: function() {
            return {
                columns: ['date', 'open', 'close', 'highest', 'lowest', 'vol'],
                rows: this.chartRows,
            };
        },
    },
    watch: {
        chartRows: function(rows) {
            const MIN_RUNGS = 5;

            let max = rows.reduce( (acc, curr) => Decimal.max(acc, ...curr.slice(1, 5)), 0);

            max = max.lessThan(this.min*MIN_RUNGS) ? this.min*MIN_RUNGS : null;

            this.additionalAttributes.yAxis[0].max = max;
        },
    },
    mounted() {
        window.addEventListener('resize', this.handleRightLabel);
        this.handleRightLabel();

        if (webBtcSymbol === this.market.identifier) {
            this.fetchWEBsupply().then(() => {
                this.marketStatus.marketCap = toMoney(Decimal.mul(this.marketStatus.last, this.supply), this.market.base.subunit);
            });
        }

        this.$axios.retry.get(this.$routing.generate('market_kline', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        })).then((res) => {
            this.stats = res.data;
            this.chartSettings.start = this.getStartTradingPeriod();

            this.addMessageHandler(this.messageHandler.bind(this), 'trade-chart-state');

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
        }).catch((err) => {
            this.notifyError('Service unavailable now. Can not load the chart data');
            this.sendLogs('error', 'Can not load the chart data', err);
        });
    },
    methods: {
        updateMarketData: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketInfo = marketData.params[1];
            const marketOpenPrice = parseFloat(marketInfo.open);
            const marketLastPrice = parseFloat(marketInfo.last);
            const marketVolume = parseFloat(marketInfo.volume);
            const marketAmount = parseFloat(marketInfo.deal);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            const marketStatus = {
                change: toMoney(changePercentage, 2),
                last: toMoney(marketLastPrice, this.market.base.subunit),
                volume: toMoney(marketVolume, this.market.quote.subunit),
                amount: toMoney(marketAmount, this.market.base.subunit),
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
            const marketVolume = parseFloat(marketData.volume);
            const marketAmount = parseFloat(marketData.deal);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;
            let marketCap;

            if (webBtcSymbol === this.market.identifier && 1e7 === this.supply) {
                this.notifyError('Can not update the market cap for BTC / MINTME');
                marketCap = 0;
            } else {
                marketCap = WEB.symbol === this.market.base.symbol && marketAmount < this.minimumVolumeForMarketcap
                    ? '-'
                    : toMoney(parseFloat(this.marketStatus.last) * this.supply, this.market.base.subunit) + ' ' + this.market.base.symbol;
            }

            const monthInfo = {
                monthChange: toMoney(changePercentage, 2),
                monthVolume: toMoney(marketVolume, this.market.quote.subunit),
                monthAmount: toMoney(marketAmount, this.market.base.subunit),
                marketCap: marketCap,
            };

            this.marketStatus = {...this.marketStatus, ...monthInfo};
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
                let config = {
                    transformRequest: function(data, headers) {
                        headers.common = {};
                        return data;
                    },
                };

                this.$axios.retry.get(this.mintmeSupplyUrl, config)
                    .then((res) => {
                        this.supply = parseFloat(res.data);
                        resolve();
                    })
                    .catch((err) => {
                        this.$toasted.error('Can not update WEB circulation supply. Market Cap might not be accurate.');
                        this.sendLogs('error', 'Can not update WEB circulation supply', err);
                        reject(err);
                    });
            });
        },
        messageHandler: function(result) {
            if (result.method === 'state.update') {
                this.updateMarketData(result);
            }
            if (result.method === 'kline.update') {
                let lastCandle = this.stats[this.stats.length - 1];

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
    },
    components: {
        Guide,
        VeCandle,
    },
};
</script>
