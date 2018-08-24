<template>
    <div class="pb-3">
        <div class="table-responsive">
            <b-table
                :items="history"
                :fields="fields"
                :current-page="currentPage"
                :per-page="perPage">
                <template slot="action" slot-scope="row">
                    <a>
                        <font-awesome-icon
                            icon="times"
                            class="text-danger" />
                    </a>
                </template>
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
    name: 'ActiveOrders',
    data() {
        return {
            history: [],
            currentPage: 1,
            perPage: 10,
            pageOptions: [10, 20, 30],
            fields: {
                date: {
                    label: 'Date',
                    sortable: true,
                },
                type: {
                    label: 'Type',
                    sortable: true,
                },
                name: {
                    label: 'Address',
                    sortable: true,
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                },
                price: {
                    label: 'Price',
                    sortable: true,
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                },
                free: {
                    label: 'Free',
                    sortable: true,
                },
                action: {
                    label: 'Action',
                    sortable: false,
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.history.length;
        },
    },
    created: function() {
        // TODO: This is a dummy simulator.
        for (let i = 0; i < 100; i++) {
            this.history.push({
                date: '12-12-1970',
                type: (i % 2 === 0) ? 'Deposit' : 'Withdraw',
                name: (i % 2 === 0) ? '[Token]' : 'Webchain (WEB)',
                amount: Math.floor(Math.random() * 99) + 10,
                price: Math.floor(Math.random() * 99) + 10,
                total: Math.floor(Math.random() * 99) + 10 + 'WEB',
                free: Math.floor(Math.random() * 99) + 10,
                action: '',
            });
        }
    },
};
</script>

