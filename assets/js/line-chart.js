import {Line} from 'vue-chartjs';

export default {
    extends: Line,
    props: {
        data: Object,
        options: Object,
    },
    mounted() {
        this.renderLineChart();
    },
    methods: {
        renderLineChart: function() {
            this.renderChart(
                this.data,
                this.options
            );
        },
        updateLineChart: function() {
            this.$data._chart.update();
        },
    },
};
