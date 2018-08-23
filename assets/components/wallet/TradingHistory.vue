<template>
    <div class="pb-3">
        <div class="table-responsive">
            <b-table
                :items="history"
                :fields="fields"
                :current-page="currentPage"
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
    name: 'DepositWithdrawHistory',
    data() {
        return {
            history: [],
            currentPage: 1,
            perPage: 10,
            fields: {
                date: {
                    label: 'Date',
                    sortable: true,
                },
                type: {
                    label: 'Type',
                    sortable: true,
                },
                address: {
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
                address: '0xe1f05911dba854fwc...',
                amount: Math.floor(Math.random() * 99) + 10,
                price: Math.floor(Math.random() * 99) + 10,
                total: Math.floor(Math.random() * 99) + 10 + 'WEB',
                free: Math.floor(Math.random() * 99) + 10,
            });
        }
    },
};
</script>
