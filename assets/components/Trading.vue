<template>
    <div class="trading">
        <slot name="title"></slot>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pair</th>
                        <th>Change</th>
                        <th>Last Price</th>
                        <th>24H Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(token, index) in tokens" :key="index">
                        <td>{{ token.pair }}</td>
                        <td>{{ token.change }}</td>
                        <td>{{ token.lastPrice }}</td>
                        <td>{{ token.volume }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pt-2">
            <b-pagination
                v-model="currentPage"
                align="center"
                :limit="6"
                :total-rows="totalRows"
                :per-page="perPage"
                :hide-goto-end-buttons="true"
            />
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
            perPage: 0,
            totalRows: 0,
        };
    },
    created() {
        this.setTokenData(this.currentPage);
    },
    computed: {
        // TODO: This is a dummy simulator.
        dummyTokens: function() {
            let tokens = [];
            for (let i = 0; i < 1000; i++) {
                tokens.push({
                    pair: 'WEB/BTC',
                    change: Math.floor(Math.random() * 49) + 50 + '%',
                    lastPrice: Math.floor(Math.random() * 99) + 10 + 'WEB',
                    volume: Math.floor(Math.random() * 9999) + 1000,
                });
            }
            return tokens;
        },
    },
    methods: {
        // TODO: This is a dummy simulator.
        dummyGetTokens: function(pageNumber) {
            let perPage = 20;
            let dummyTokens = this.dummyTokens;
            return {
                tokens: dummyTokens.slice(
                    (pageNumber - 1) * perPage,
                    (pageNumber * perPage) - 1
                ),
                page: pageNumber,
                perPage: perPage,
                totalRows: dummyTokens.length,
            };
        },
        setTokenData: function(pageNumber) {
            let firsPageTokens = this.dummyGetTokens(pageNumber);
            this.tokens = firsPageTokens.tokens;
            this.perPage = firsPageTokens.perPage;
            this.totalRows = firsPageTokens.totalRows;
        },
    },
    watch: {
        currentPage: function(value) {
            this.setTokenData(value);
        },
    },
};
</script>
