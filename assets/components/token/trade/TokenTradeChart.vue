<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header"></div>
            <div class="card-body p-2">
                <div class="small text-center">
                    <div class="pt-2">
                        Last price: {{ marketStatus.last }}
                        <guide>
                            <template slot="header">
                                Last price
                            </template>
                            <template slot="body">
                                Price per one {{ currency }} for last transaction.
                            </template>
                        </guide>
                    </div>
                    <div class="pt-3">
                        <div class="d-inline-block px-2">
                            <div>
                                24h change
                                <guide>
                                    <template slot="header">
                                        24h change
                                    </template>
                                    <template slot="body">
                                        Price change in last 24h
                                    </template>
                                </guide>
                            </div>
                            <div>{{ marketStatus.change }}</div>
                        </div>
                        <div class="d-inline-block px-2">
                            <div>
                                24h volume
                                <guide>
                                    <template slot="header">
                                        24h volume
                                    </template>
                                    <template slot="body">
                                        The amount of {{ currency }} that has been traded in the last 24 hours.
                                    </template>
                                </guide>
                            </div>
                            <div>{{ marketStatus.volume }} Tokens</div>
                        </div>
                    </div>
                </div>
                <div class="pt-3">
                    [Volume]WEB({{ marketStatus.change }}%)
                </div>
                <line-chart :data="chartData"
                            :options="chartOptions"
                />
            </div>
        </div>
    </div>
</template>

<script>
import LineChart from '../../../js/line-chart';
import Guide from '../../Guide';
import WebSocketMixin from '../../../js/mixins/websocket';

export default {
    name: 'TokenTradeChart',
    mixins: [WebSocketMixin],
    props: {
        websocketUrl: String,
        containerClass: String,
        marketName: Object,
        currency: String,
    },
    data() {
        return {
            chartData: {
                labels: [],
                datasets: [
                    {
                        borderWidth: 0,
                        borderColor: 'white',
                        backgroundColor: '#4d40c6',
                        radius: 0,
                        data: [],
                        lineTension: 0,
                    },
                ],
            },
            chartOptions: {
                layout: {
                    padding: {
                        left: -10,
                        bottom: -10,
                    },
                },
                scales: {
                    xAxes: [
                        {
                            gridLines: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                    ],
                    yAxes: [
                        {
                            gridLines: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                    ],
                },
                legend: {
                    display: false,
                },
            },
            chartXAxesPoints: 10,
            marketStatus: {
                volume: 0,
                last: 0,
                change: 0,
            },
        };
    },
    computed: {
        market: function() {
            return this.marketName;
        },
        chartInitValues: function() {
            let values = [];

            for (let i = 0; i < this.chartXAxesPoints; i++) {
                values.push(0);
            }

            return values;
        },
    },
    mounted() {
        // Init values cause chart stick to xAxes initially.
        this.chartData.labels = this.chartInitValues;
        this.chartData.datasets[0].data = this.chartInitValues;

        if (this.websocketUrl) {
            this.addMessageHandler((result) => {
                if (result.method === 'state.update') {
                    this.updateMarketData(result);
                }
            });
            this.addOnOpenHandler(() => {
                const request = JSON.stringify({
                    method: 'state.subscribe',
                    params: [this.market.hiddenName],
                    id: parseInt(Math.random().toString().replace('0.', '')),
                });
                this.sendMessage(request);
            });
        }
    },
    methods: {
        updateMarketData: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketInfo = marketData.params[1];
            const marketOpenPrice = parseFloat(marketInfo.open);
            const marketLastPrice = parseFloat(marketInfo.last);
            const makretVolume = parseFloat(marketInfo.volume);
            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            this.marketStatus = {
                change: changePercentage.toFixed(2),
                last: marketLastPrice.toFixed(2),
                volume: makretVolume.toFixed(2),
            };

            let data = this.chartData.datasets[0].data;
            data.push(makretVolume);
            if (data.length > this.chartXAxesPoints) {
                data = data.slice(1);
            }
            this.chartData.datasets[0].data = data;
        },
    },
    components: {
        LineChart,
        Guide,
    },
};
</script>
