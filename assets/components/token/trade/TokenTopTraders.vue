<template>
    <div class="card">
        <div class="card-header">
            Top Traders
            <span class="card-header-icon">
                <guide>
                    <template slot="header">
                        Trade History
                    </template>
                    <template slot="body">
                        List of last closed orders for {{ tokenName }}.
                    </template>
                </guide>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive fix-height" ref="traders">
                <b-table
                    :items="traders"
                    :fields="fields"
                    :current-page="currentPage"
                    :per-page="perPage">
                    <template slot="trader" slot-scope="row">
                        {{ row.value }}
                        <img
                            src="../../../img/avatar.png"
                            class="float-right"
                            alt="avatar">
                    </template>
                </b-table>
            </div>
            <div class="text-center" v-if="showDownArrow">
                <img
                    src="../../../img/down-arrows.png"
                    class="icon-arrows-down c-pointer"
                    alt="arrow down"
                    @click="scrollDown">
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenTopTraders',
    data() {
        return {
            traders: [],
            fields: {
                trader: {
                    label: 'Trader',
                },
                date: {
                    label: 'Date',
                },
                amount: {
                    label: 'Amount',
                },
            },
        };
    },
    computed: {
        showDownArrow: function() {
            return (this.traders.length > 7);
        },
    },
    created: function() {
        for (let i = 1; i < 9; i++) {
            this.traders.push({
                trader: '0' + i + ' John Doe',
                date: '2019-01-05',
                amount: Math.floor(Math.random() * 99) + 10,
            });
        }
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.traders;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
    },
};
</script>
