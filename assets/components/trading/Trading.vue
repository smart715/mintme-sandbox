<template>
    <div class="trading">
        <slot name="title"></slot>
        <div class="table-responsive">
            <b-table
                :items="tokens"
                :fields="fields"
                :current-page=
                :per-page="perPage">
            </b-table>
        </div>
        <div class="row justify-content-center">
            <b-pagination
                :total-rows="totalRows"
                :per-page="perPage"
                v-model="currentPage"
                class="my-0" />
        </div>
    </div>
</template>

<script>


export default {
    name: 'Trading',
    props: {
        tableContainerClass: String,
        tableClass: String,
    },
    data() {
        return {
            tokens: [],
            currentPage: 1,
            perPage: 10,
            fields: {
                pair: {
                    label: 'Pair',
                    sortable: true,
                },
                change: {
                    label: 'Change',
                    sortable: true,
                },
                lastPrice: {
                    label: 'Last Price',
                    sortable: true,
                },
                volume: {
                    label: '24H Volume',
                    sortable: true,
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.tokens.length;
        },
    },
    created: function() {
        // TODO: This is a dummy simulator.
        for (let i = 0; i < 1000; i++) {
            this.tokens.push({
                pair: 'WEB/BTC',
                change: Math.floor(Math.random() * 49) + 50 + '%',
                lastPrice: Math.floor(Math.random() * 99) + 10 + 'WEB',
                volume: Math.floor(Math.random() * 9999) + 1000,
            });
        }
    },
};
</script>
