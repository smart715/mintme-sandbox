<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header"></div>
            <div class="card-body p-2">
                <div class="text-center">
                    <div class="pt-2">
                        Last price: 0.000000001WEB
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
                            <div>+2.002%</div>
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
                            <div>52555.25354 Tokens</div>
                        </div>
                    </div>
                </div>
                <div class="pt-3">
                    [Volume]WEB(+2%)
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
import {w3cwebsocket as W3CWebSocket} from 'websocket';

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
                labels: [1, 2, 3, 4, 5, 6, 7, 8, 9],
                datasets: [
                    {
                        borderWidth: 0,
                        borderColor: 'transparent',
                        backgroundColor: '#8ec63f',
                        radius: 0,
                        data: [0, 35, 21, 69, 53, 87, 15, 10, 19],
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
            wsClient: null,
            wsResult: {},
        };
    },
    computed: {
        market: function() {
            return JSON.parse(this.marketName);
        },
    },
    mounted() {
        if (this.websocketUrl) {
            this.wsClient = new W3CWebSocket(this.websocketUrl);
            this.wsClient.onmessage = (result) => {
                if (typeof result.data === 'string') {
                    this.wsResult = JSON.parse(result.data);
                }
            };
            this.wsClient.onopen = () => {
                this.wsClient.send(`{
                    "method": "state.subscribe",
                    "params": ["${this.market.hiddenName}"],
                    "id": 1
                }`);
            };
        }
    },
    components: {
        LineChart,
    },
};
</script>
