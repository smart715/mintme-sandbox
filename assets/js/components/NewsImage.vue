<template>
        <img :src="loadImage" @load="nextLoad">
</template>

<script>

export default {
    name: 'NewsImage',
    data: function() {
        return {
            loadImage: null,
        };
    },
    props: {
        src: String,
        queryNumber: Number,
    },
    computed: {
        query: function() {
            return this.$store.state.query.length;
        },
    },
    watch: {
        query() {
            if (0 == this.queryNumber) {
                this.loadImage = this.src;
            }
            if ((this.queryNumber) === this.$store.state.query[0]) {
                this.loadImage = this.src;
            }
        },
    },
    mounted: function() {
        this.$store.commit('addOrder', this.queryNumber);
    },
    methods: {
        nextLoad: function() {
            this.$store.commit('deleteOrder');
        },
    },
};
</script>
