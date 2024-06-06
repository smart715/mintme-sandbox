<template>
    <div></div>
</template>

<script>
import {mapMutations} from 'vuex';

export default {
    name: 'CryptoInit',
    props: {
        cryptos: Array,
    },
    created() {
        this.setCryptos(this.cryptos);
    },
    methods: {
        ...mapMutations('crypto', [
            'setCryptos',
        ]),
    },
};
</script>
