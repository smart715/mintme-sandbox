<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header"></div>
            <div class="card-body p-2">
                <div class="small text-center">
                    <div class="pt-2">
                        Last price: {{ marketStatus.volume }}
                        <font-awesome-icon
                            icon="question"
                            class="ml-1 mb-1 bg-primary text-white
                                   rounded-circle square blue-question"
                        />
                    </div>
                    <div class="pt-3">
                        <div class="d-inline-block px-2">
                            <div>
                                24h change
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-primary text-white
                                           rounded-circle square blue-question"
                                />
                            </div>
                            <div>{{ marketStatus.change }}</div>
                        </div>
                        <div class="d-inline-block px-2">
                            <div>
                                24h volume
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-primary text-white
                                           rounded-circle square blue-question"
                                />
                            </div>
                            <div>{{ marketStatus.volume }} Tokens</div>
                        </div>
                    </div>
                </div>
                <div class="pt-3">
                    [Volume]WEB({{ marketStatus.change }}%)
                </div>
                <line-chart
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
        </div>
    </div>
</template>

<script>
import LineChart from '../../../js/line-chart';
import WebSocket from '../../../js/websocket';

Vue.use(WebSocket);

export default {
    name: 'TokenTradeChart',
    props: {
        websocketUrl: String,
        containerClass: String,
        marketName: String,
    },
    data() {
        return {
            chartData: {
                labels: [],
                datasets: [
                    {
                        borderWidth: 0,
                        borderColor: 'transparent',
                        backgroundColor: '#8ec63f',
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
            wsClient: null,
            wsResult: {},
            marketStatus: {
                volume: 0,
                last: 0,
                change: 0,
            },
        };
    },
    computed: {
        market: function() {
            return JSON.parse(this.marketName);
        },
        chartInitValues: function() {
            let values = [];
            let i = 0;
            while (i < this.chartXAxesPoints) {
                values.push(0);
                ++i;
            }
            return values;
        },
    },
    mounted() {
        // Init values cause chart stick to xAxes initially.
        this.chartData.labels = this.chartInitValues;
        this.chartData.datasets[0].data = this.chartInitValues;

        if (this.websocketUrl) {
            this.wsClient = this.$socket(this.websocketUrl);
            this.wsClient.onmessage = (result) => {
                if (typeof result.data === 'string') {
                    this.wsResult = JSON.parse(result.data);
                }
            };
            this.wsClient.onopen = () => {
                this.wsClient.send('{' +
                    '"method": "state.subscribe",' +
                    '"params": ["' + this.market.hiddenName + '"],' +
                    '"id": 1' +
                '}');
            };
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
    },
    watch: {
        wsResult: {
            handler(value) {
                this.updateMarketData(value);
            },
            deep: true,
        },
    },
};
</script>
