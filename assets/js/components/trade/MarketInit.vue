<template>
    <span></span>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';

export default {
    name: 'MarketInit',
    props: {
        marketIndex: String,
        markets: Object,
    },
    methods: {
        ...mapMutations('market', [
            'setMarkets',
            'setCurrentMarketIndex',
        ]),
    },
    computed: {
        ...mapGetters('market', [
            'getCurrentMarket',
            'getMarkets',
        ]),
    },
    created() {
        this.setCurrentMarketIndex(this.marketIndex);
        this.setMarkets(this.markets);
    },
};
</script>
