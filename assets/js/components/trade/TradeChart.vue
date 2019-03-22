<template>
    <div>
        <div class="card">
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-lg-2">
                        <div class="text-left">
                            <div class="pt-2">
                                Last price: {{ marketStatus.last }}
                                <guide>
                                    <template slot="header">
                                        Last price
                                    </template>
                                    <template slot="body">
                                        Price per one {{ market.base.symbol }} for last transaction.
                                    </template>
                                </guide>
                            </div>
                            <div class="pt-4">
                                <div class="d-inline-block">
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
                                <div class="d-inline-block pt-4">
                                    <div>
                                        24h volume
                                        <guide>
                                            <template slot="header">
                                                24h volume
                                            </template>
                                            <template slot="body">
                                                The amount of {{ market.base.symbol }} that has been traded in the last 24 hours.
                                            </template>
                                        </guide>
                                    </div>
                                    <div>{{ marketStatus.volume }} Tokens</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="pt-2">
                            [Volume] WEB ({{ marketStatus.change }}%)
                        </div>
                    </div>
                    <div class="col-lg-4 pt-2">
                        <line-chart :data="chartData" :options="chartOptions"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LineChart from '../../line-chart';
import Guide from '../Guide';
import WebSocketMixin from '../../mixins/websocket';

export default {
    name: 'TradeChart',
    mixins: [WebSocketMixin],
    props: {
        websocketUrl: String,
        market: Object,
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
                    params: [this.market.identifier],
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
