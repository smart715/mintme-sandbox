<template>
    <div v-if="loaded" class="card">
        <div class="card-body p-2">
            <div class="row mx-2">
                <div class="col text-left">
                    Last price: {{ marketStatus.last }}
                    <guide>
                        <template slot="header">
                            Last price
                        </template>
                        <template slot="body">
                            Price per one {{ market.quote.symbol }} for last transaction.
                        </template>
                    </guide>
                </div>
                <div class="col text-center">
                    24h change: {{ marketStatus.change }}%
                    <guide>
                        <template slot="header">
                            24h change
                        </template>
                        <template slot="body">
                            Price change in last 24h
                        </template>
                    </guide>
                </div>
                <div class="col text-center">
                    24h volume: {{ marketStatus.volume }} Tokens
                    <guide>
                        <template slot="header">
                            24h volume
                        </template>
                        <template slot="body">
                            The amount of {{ market.quote.symbol }} that has been traded in the last 24 hours.
                        </template>
                    </guide>
                </div>
                <div class="col text-right">
                    [Volume] {{ market.base.symbol }} ({{ marketStatus.change }}%)
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <ve-candle
                        class="m-2"
                        :extend="additionalAttributes"
                        :data="chartData"
                        :settings="chartSettings"
                        :theme="chartTheme(precision)"
                        :not-set-unchange="['dataZoom']"
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
import VeLineTheme from '../../../js/utils/echart-theme';
import VeLine from 'v-charts';
import Guide from '../Guide';
import WebSocketMixin from '../../../js/mixins/websocket';
import {toMoney} from '../../../js/utils';
import moment from 'moment';

Vue.use(VeLine);

export default {
    name: 'TradeChart',
    mixins: [WebSocketMixin],
    props: {
        websocketUrl: String,
        market: Object,
        precision: Number,
    },
    data() {
        return {
            chartTheme: VeLineTheme,
            chartSettings: {
                labelMap: {
                    '日K': 'Indexes',
                },
                showMA: true,
                showDataZoom: true,
                start: 70,
                end: 100,
                downColor: '#ff6961',
                upColor: '#77DD77',
                showVol: false,
            },
            additionalAttributes: {
                grid: {
                    top: 55,
                    bottom: 60,
                    left: '8%',
                    right: '8%',
                },
            },
            chartOptions: {},
            marketStatus: {
                volume: 0,
                last: 0,
                change: 0,
            },
            stats: null,
        };
    },
    computed: {
        isKlineEmpty: function() {
            return this.chartRows.length === 0;
        },
        chartRows: function() {
            if (!this.stats.length) {
                return [[new Date().toISOString().slice(0, 10), 0, 0, 0, 0, 0]];
            }

            return this.stats.map((line) => {
                 return [
                    this.getDate(line.time),
                    toMoney(line.open, this.precision),
                    toMoney(line.close, this.precision),
                    toMoney(line.highest, this.precision),
                    toMoney(line.lowest, this.precision),
                    toMoney(line.volume, this.precision),
                 ];
              });
        },
        chartData: function() {
            return {
                columns: ['date', 'open', 'close', 'lowest', 'highest', 'vol'],
                rows: this.chartRows,
            };
        },
        loaded: function() {
            return this.stats !== null;
        },
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('market_kline', {
            base: this.market.base.symbol,
            quote: this.market.quote.symbol,
        })).then((res) => {
            this.stats = res.data;

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
                        lowest: result.params[0][3],
                        highest: result.params[0][4],
                        volume: result.params[0][5],
                    });
                }
            });

            this.addOnOpenHandler(() => {
                this.sendMessage(JSON.stringify({
                    method: 'state.subscribe',
                    params: [this.market.identifier],
                    id: parseInt(Math.random().toString().replace('0.', '')),
                }));
                this.sendMessage(JSON.stringify({
                    method: 'kline.subscribe',
                    params: [this.market.identifier, 24*60*60],
                    id: parseInt(Math.random().toString().replace('0.', '')),
                }));
            });
        }).catch((err) => {
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
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            this.marketStatus = {
                change: changePercentage.toFixed(2),
                last: toMoney(marketLastPrice, this.precision),
                volume: marketVolume.toFixed(2),
            };
        },
        getDate: function(timestamp) {
            return moment.utc((timestamp + 3600) * 1000).format('YYYY-MM-DD');
        },
    },
    components: {
        Guide,
    },
};
</script>
