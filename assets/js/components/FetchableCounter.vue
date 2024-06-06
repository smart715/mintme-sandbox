<template>
    <counter-wrapper
        :count="counts"
        :block="block"
        :icon="icon"
    />
</template>

<script>
import CounterWrapper from './CounterWrapper';

export default {
    name: 'FetchableCounter',
    components: {
        CounterWrapper,
    },
    props: {
        urlData: String,
        block: Boolean,
        icon: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            counts: null,
        };
    },
    mounted() {
        this.refreshCounter();
    },
    methods: {
        async refreshCounter() {
            try {
                const {data} = await this.$axios.retry.get(this.urlData);

                this.counts = data || 0;
            } catch (err) {
                this.$logger.error(`Cannot update counter for url ${this.urlData}`, err);
            }
        },
    },
};
</script>
