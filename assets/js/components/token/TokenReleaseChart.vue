<template>
    <div class="w-100">
        <h5 class="page-title text-center mb-4" v-html="$t('page.pair.token_release_chart')"></h5>
        <doughnut-chart
            class="token-release-chart m-auto"
            :chart-data="dataCollection"
            :chart-options="chartOptions"
            :plugins="plugins"
        />
    </div>
</template>

<script>
import DoughnutChart from '../UI/charts/DoughnutChart.vue';
import {tokenReleaseChartColors} from '../../utils/constants';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import Decimal from 'decimal.js';

export default {
    name: 'TokenReleaseChart',
    components: {
        DoughnutChart,
    },
    props: {
        released: Number,
        notReleased: Number,
    },
    data() {
        return {
            dataCollection: null,
            plugins: [ChartDataLabels],
            chartOptions: {
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        offset: '4',
                        clip: false,
                        color: 'white',
                        font: {
                            weight: 'bold',
                        },
                        formatter: function(value) {
                            return `${value}%`;
                        },
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            color: 'white',
                            fontSize: 14,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function(data) {
                                return `${data.label}: ${data.parsed}%`;
                            },
                        },
                    },
                },
                cutout: '85%',
                radius: '80%',
                responsive: true,
            },
        };
    },
    created() {
        this.fillData();
    },
    methods: {
        fillData() {
            const notReleased = new Decimal(this.notReleased);
            const released = new Decimal(this.released);
            const sum = notReleased.plus(released);

            this.dataCollection = {
                labels: [
                    this.$t('token.intro.statistics.release_chart.not_yet_released'),
                    this.$t('token.intro.statistics.release_chart.released'),
                ],
                datasets: [{
                    data: [
                        notReleased.dividedBy(sum).mul(100).toFixed(1),
                        released.dividedBy(sum).mul(100).toFixed(1),
                    ],
                    borderWidth: 0,
                    backgroundColor: tokenReleaseChartColors,
                    hoverOffset: 4,
                }],
            };
        },
    },
};
</script>
