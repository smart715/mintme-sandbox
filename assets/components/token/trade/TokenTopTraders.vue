<template>
    <div class="card h-100">
        <div class="card-header">
            Top Traders
        </div>
        <div class="card-body p-0">
            <div class="table-responsive fix-height" ref="traders">
                <b-table
                    :items="traders"
                    :fields="fields"
                    :current-page="currentPage">
                    <template slot="trader" slot-scope="row">
                        {{ row.value }}
                        <img
                            src="../../../img/avatar.png"
                            class="float-right"
                            alt="avatar">
                    </template>
                </b-table>
            </div>
            <div class="text-center pb-2" v-if="showDownArrow">
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
import Guide from '../../Guide';
export default {
    name: 'TokenTopTraders',
    data() {
        return {
            traders: [],
            currentPage: 0,
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
    components: {
        Guide,
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
