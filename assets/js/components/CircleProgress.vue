<template>
    <div>
        <vue-circle
            ref="circleElement"
            :progress="percent"
            :size="40"
            :reverse="false"
            line-cap="square"
            :fill="color"
            :empty-fill="colors.empty"
            :animation-start-value="0.0"
            :start-angle="4.7"
            insert-mode="append"
            :thickness="2"
            :show-percent="true"
        >
        </vue-circle>
    </div>
</template>

<script>
  import VueCircle from 'vue2-circle-progress';

  export default {
    name: 'CircleProgress',
    props: {
        pointsGained: Number,
        totalPoints: Number,
    },
    data() {
        return {
            colors: {
                fill: '#C0C0C0',
                empty: 'rgba(255,255,255,0)',
            },
        };
    },
    components: {
        VueCircle,
    },
    computed: {
        percent: function() {
            return (this.pointsGained * 100 / this.totalPoints) | 0;
        },
        color: function() {
            return {color: this.colors.fill};
        },
    },
    watch: {
        percent: function(value) {
            this.$refs.circleElement.updateProgress(value);
        },
    },
  };
</script>
