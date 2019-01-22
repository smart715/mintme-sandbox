<template>
    <div class="p-3">
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
    </div>
</template>

<script>
import moment from 'moment';
import axios from 'axios';
import Routing from '../../js/routing.js';
import {toMoney} from '../../js/utils';

export default {
    name: 'DepositWithdrawHistory',
    props: {
        initHistory: Array,
    },
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
                data: this.initHistory,
                dateFormat: 'MM-DD-YYYY',
            },
            currentPage: 1,
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
                axios.get(
                    Routing.generate('api_history', {page: page})
                ).then((response) => {
                    if (response.data.length > 0) {
                        this.history.data = this.history.data.concat(response.data);
                        this.canRequestNextPage = true;
                        this.currentPage++;
                    }
                }).catch((error) => { });
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
