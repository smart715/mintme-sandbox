<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
        <div class="deposit-withdraw-table table-responsive text-nowrap table-restricted" ref="table">
            <b-table
                v-if="!noHistory"
                :items="sanitizedHistory"
                :fields="fields"
                :class="{'empty-table': noHistory}"
            >
                <template slot="symbol" slot-scope="data">
                    <a :href="data.item.url" class="text-white">
                        <span v-b-tooltip="{title: data.item.symbol, boundary:'viewport'}">
                            {{ data.item.symbol | truncate(15) }}
                        </span>
                    </a>
                </template>
                <template slot="toAddress" slot-scope="row">
                    <div v-b-tooltip="{title: row.value, boundary: 'viewport'}">
                        <copy-link :content-to-copy="row.value" class="c-pointer">
                            <div class="text-truncate text-blue">
                                {{ row.value }}
                            </div>
                        </copy-link>
                    </div>
                </template>
            </b-table>
            <div v-if="noHistory">
                <p class="text-center p-5">No transactions were added yet</p>
            </div>
        </div>
        <div v-if="loading" class="p-1 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        </template>
        <template v-else>
            <div class="p-5 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
        </template>
    </div>
</template>

<script>
import moment from 'moment';
import {toMoney, formatMoney} from '../../utils';
import {LazyScrollTableMixin, FiltersMixin} from '../../mixins';
import CopyLink from '../CopyLink';
import {GENERAL} from '../../utils/constants';

export default {
    name: 'DepositWithdrawHistory',
    mixins: [LazyScrollTableMixin, FiltersMixin],
    components: {CopyLink},
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
                symbol: {
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
                    formatter: formatMoney,
                },
                status: {
                    label: 'Status',
                    sortable: true,
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                    formatter: formatMoney,
                },
            },
            tableData: null,
            currentPage: 1,
        };
    },
    computed: {
        sanitizedHistory: function() {
            return this.sanitizeHistory(JSON.parse(JSON.stringify(this.tableData)));
        },
        noHistory: function() {
            return this.tableData.length === 0;
        },
        loaded: function() {
            return this.tableData !== null;
        },
    },
    mounted: function() {
        this.updateTableData();
    },
    methods: {
        addDetailsForEmptyMessageToHistory: function(historyData) {
            if (0 === historyData.length) {
                historyData.push({_showDetails: true});
            }
            return historyData;
        },
        updateTableData: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('payment_history', {page: this.currentPage}))
                    .then((response) => {
                        if (this.tableData === null) {
                            this.tableData = response.data;
                            this.currentPage++;
                        } else if (response.data.length > 0) {
                            this.tableData = this.tableData.concat(response.data);
                            this.currentPage++;
                        }

                        resolve(this.tableData);
                    })
                    .catch(() => {
                        this.$toasted.error('Can not update payment history. Try again later.');
                        reject([]);
                    });
            });
        },
        sanitizeHistory: function(historyData) {
            historyData.forEach((item) => {
                item['url'] = this.generatePairUrl(item.tradable);
                item['date'] = item.date
                    ? moment(item.date).format(GENERAL.dateFormat)
                    : null;
                item['fee'] = item.fee
                    ? toMoney(item.fee, item.tradable.subunit)
                    : null;
                item['amount'] = item.amount
                    ? toMoney(item.amount, item.tradable.subunit)
                    : null;
                item['symbol'] = item.tradable.symbol
                    ? item.tradable.symbol
                    : null;
                item['status'] = item.status.statusCode
                    ? item.status.statusCode
                    : null;
                item['type'] = item.type.typeCode
                    ? item.type.typeCode
                    : null;
            });

            return historyData;
        },
        generatePairUrl: function(quote) {
            if (quote.hasOwnProperty('exchangeble')) {
                /** @TODO In future we need to use another solution and remove hardcoded BTC & WEB symbols **/
                let params = {
                    base: !quote.exchangeble ? quote.symbol : 'BTC',
                    quote: quote.exchangeble && quote.tradable ? quote.symbol : 'WEB',
                };
                return this.$routing.generate('coin', params);
            }

            return this.$routing.generate('token_show', {name: quote.name});
        },
    },
};
</script>
