<template>
    <img :src="loadImage" @load="nextLoad">
</template>

<script>
import {mapMutations, mapGetters} from 'vuex';

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
            return this.query.length;
        },
        ...mapGetters('newsImages', {
            query: 'getQuery',
        }),
    },
    watch: {
        query() {
            if (0 == this.queryNumber) {
                this.loadImage = this.src;
            }
            if ((this.queryNumber) === this.query[0]) {
                this.loadImage = this.src;
            }
        },
    },
    mounted: function() {
        this.addOrder(this.queryNumber);
    },
    methods: {
        nextLoad: function() {
            this.deleteOrder();
        },
        ...mapMutations('newsImages', [
            'addOrder',
            'deleteOrder',
        ]),
    },
};
</script>
