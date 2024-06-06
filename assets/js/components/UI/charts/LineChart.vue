<template>
  <LineCmp :chart-data="chartData" :chart-options="chartOptions" :plugins="plugins" />
</template>

<script>
import {Line as LineCmp} from 'vue-chartjs/legacy';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

export default {
    name: 'LineChart',
    components: {
        LineCmp,
    },
    props: {
        chartData: Object,
        chartOptions: Object,
        plugins: Array,
    },
};
</script>
