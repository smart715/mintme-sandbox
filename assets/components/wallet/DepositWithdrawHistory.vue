<template>
    <div class="pb-3">
        <template v-if="loaded">
        <div class="table-responsive deposit-withdraw-history" @scroll.passive="loadMore">
            <b-table
                v-if="!noHistory"
                :items="sanitizedHistory"
                :fields="fields"
                :class="{'empty-table': noHistory}"
                ref="table"
            >
            </b-table>
            <div v-if="noHistory">
                <h4 class="text-center p-5">No transactions were added yet</h4>
            </div>
        </div>
        </template>
        <template v-else>
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </template>
    </div>
</template>

<script>
import moment from 'moment';
import {toMoney} from '../../js/utils';

export default {
    name: 'DepositWithdrawHistory',
    data() {
        return {
            fields: {
                date: {
                    label: 'Date',
                    sortable: true,
                },
                type: {
                    label: 'Type',
                    sortable: true,
                },
                crypto: {
                    label: 'Name',
                    sortable: true,
                },
                toAddress: {
                    label: 'Address',
                    sortable: true,
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                },
                status: {
                    label: 'Status',
                    sortable: true,
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                },
            },
            history: {
                data: null,
                dateFormat: 'MM-DD-YYYY',
            },
            currentPage: 0,
            canRequestNextPage: true,
        };
    },
    computed: {
        sanitizedHistory: function() {
            return this.sanitizeHistory(this.history.data);
        },
        noHistory: function() {
            return this.history.data.length === 0;
        },
        loaded: function() {
            return this.history.data !== null;
        },
    },
    mounted: function() {
        this.getHistory();
    },
    methods: {
        addDetailsForEmptyMessageToHistory: function(historyData) {
            if (0 === historyData.length) {
                historyData.push({_showDetails: true});
            }
            return historyData;
        },
        getHistory: function() {
            if (this.canRequestNextPage) {
                this.canRequestNextPage = false;
                this.$axios.retry.get(
                        this.$routing.generate('payment_history', {page: this.currentPage})
                    )
                    .then((response) => {
                        if (this.history.data === null) {
                            this.history.data = JSON.parse(response.request.response);
                        } else if (response.data.length > 0) {
                            if (this.history.data !== null) {
                                this.history.data = this.history.data.concat(
                                    JSON.parse(response.request.response)
                                );
                            }
                        }
                        this.canRequestNextPage = true;
                        this.currentPage++;
                    })
                    .catch((error) => this.$toasted.error('Can not update payment history. Try again later.'));
            }
        },
        sanitizeHistory: function(historyData) {
            historyData.forEach((item, index) => {
                historyData[index]['date'] = item.date
                    ? moment(item.date).format(this.history.dateFormat)
                    : null;
                historyData[index]['amount'] = item.amount
                    ? toMoney(item.amount)
                    : null;
                historyData[index]['crypto'] = item.crypto.symbol
                    ? item.crypto.symbol
                    : null;
                historyData[index]['status'] = item.status.statusCode
                    ? item.status.statusCode
                    : null;
                historyData[index]['type'] = item.type.typeCode
                    ? item.type.typeCode
                    : null;
                historyData[index]['fee'] = item.fee
                    ? toMoney(item.fee)
                    : null;
            });
            return historyData;
        },
        loadMore: function(evt) {
            if (this.$refs.table.$el.offsetHeight - evt.target.offsetHeight - evt.target.scrollTop < 150) {
                this.getHistory();
            }
        },
    },
};
</script>
