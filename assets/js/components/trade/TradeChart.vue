<template>
    <div v-if="loaded" class="card">
        <div class="card-body p-2">
            <div class="row mx-2">
                <div class="col text-left">
                    <span>Last price: </span>
                    <guide>
                        <template slot="header">
                            Last price
                        </template>
                        <template slot="body">
                            Price per one {{ market.quote.symbol }} for last transaction.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.last | formatMoney }} {{ market.base.symbol }}
                </div>
                <div class="col text-center">
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
                <div class="col text-center">
                    <span>24h/30d volume: </span>
                    <guide>
                        <template slot="header">
                            24h/30d volume
                        </template>
                        <template slot="body">
                            The amount of {{ market.quote.symbol }} that has been traded in the last 24 hours and the last 30 days.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.volume | formatMoney }}/{{ marketStatus.monthVolume | formatMoney }} Tokens
                </div>
                <div class="col text-right">
                    <span>24h/30d volume: </span>
                    <guide>
                        <template slot="header">
                            24h/30d volume
                        </template>
                        <template slot="body">
                            The amount of {{ market.base.symbol }} that has been traded in the last 24 hours and the last 30 days.
                        </template>
                    </guide>
                    <br>
                    {{ marketStatus.amount | formatMoney }}/{{ marketStatus.monthAmount | formatMoney }} {{ market.base.symbol }}
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <ve-candle
                        class="m-2"
                        :extend="additionalAttributes"
                        :right-label="rightLabel"
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
    <div v-else class="p-5 text-center text-white">
        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
    </div>
</template>

<script>
import VeCandle from '../../utils/candle';
import Guide from '../Guide';
import {WebSocketMixin, MoneyFilterMixin} from '../../../js/mixins';
import {toMoney, EchartTheme as VeLineTheme, getBreakPoint} from '../../utils';
import moment from 'moment';
import Decimal from 'decimal.js/decimal.js';

export default {
    name: 'TradeChart',
    mixins: [WebSocketMixin, MoneyFilterMixin],
    props: {
        websocketUrl: String,
        market: Object,
    },
    data() {
        let min = 1 / Math.pow(10, this.market.base.subunit);
        return {
            rightLabel: true,
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
                    },
                    {
                        apply: 'all',
                        axisLabel: {
                            formatter: (val) => parseFloat(toMoney(val, this.market.base.subunit)).toString(),
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
            },
            stats: null,
            maxAvailableDays: 30,
            min,
            monthInfoRequestId: 0,
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
        loaded: function() {
            return this.stats !== null;
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

        this.$axios.retry.get(this.$routing.generate('market_kline', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        })).then((res) => {
            this.stats = res.data;
            this.chartSettings.start = this.getStartTradingPeriod();

            this.addMessageHandler((result) => {
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
            }, 'trade-chart-state');

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
        }).catch(() => {
            this.$toasted.error('Service unavailable now. Can not load the chart data');
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
                method: 'kline.query',
                params: [
                    this.market.identifier,
                    Math.round(Date.now() / 1000) - 30 * 24 * 60 * 60,
                    Math.round(Date.now() / 1000),
                    7 * 24 * 60 * 60,
                ],
                id: this.monthInfoRequestId,
            }));
        },
        updateMonthMarketData: function(marketData) {
            const marketOpenPrice = marketData[0] ? marketData[0][1] || 0 : 0;
            const marketLastPrice = marketData[marketData.length -1] ? marketData[marketData.length -1][2] || 0 : 0;
            const marketVolume = marketData.reduce(function(acc, cur) {
                return Decimal.add(acc, cur[5]);
            }, 0);
            const marketAmount = marketData.reduce(function(acc, cur) {
                return Decimal.add(acc, cur[6]);
            }, 0);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            const monthInfo = {
                monthChange: toMoney(changePercentage, 2),
                monthVolume: toMoney(marketVolume, this.market.quote.subunit),
                monthAmount: toMoney(marketAmount, this.market.base.subunit),
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
            this.rightLabel = ['lg', 'xl'].includes(getBreakPoint());
        },
    },
    components: {
        Guide,
        VeCandle,
    },
};
</script>
