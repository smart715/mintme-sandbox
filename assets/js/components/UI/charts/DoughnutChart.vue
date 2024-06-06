<template>
  <Doughnut :chart-data="chartData" :chart-options="chartOptions" :plugins="plugins" />
</template>

<script>
import {Doughnut} from 'vue-chartjs/legacy';
import {Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale);

export default {
    name: 'DoughnutChart',
    components: {
        Doughnut,
    },
    props: {
        chartData: Object,
        chartOptions: Object,
        plugins: Array,
    },
};
</script>
