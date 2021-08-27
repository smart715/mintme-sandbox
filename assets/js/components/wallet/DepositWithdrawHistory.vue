<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
        <div class="deposit-withdraw-table table-responsive text-nowrap table-restricted" ref="table" v-if="!noHistory">
            <b-table
                thead-class="trading-head"
                :items="sanitizedHistory"
                :fields="fieldsArray"
                :sort-compare="$sortCompare(fields)"
                :sort-by="fields.date.key"
                :sort-desc="true"
                sort-direction="desc"
                :class="{'empty-table': noHistory}"
                sort-icon-left
            >
                <template v-slot:cell(symbol)="data">
                    <span v-if="!data.item.tradable.blocked">
                        <a :href="data.item.url" class="text-white">
                            <span
                                v-if="data.item.symbol.length > 17"
                                v-b-tooltip="{title: data.item.symbol, boundary:'viewport'}">
                                {{ data.item.symbol | truncate(17) }}
                            </span>
                            <span v-else>
                                {{ data.item.symbol }}
                            </span>
                        </a>
                    </span>
                    <span v-else class="text-muted">
                        {{ data.item.symbol }}
                    </span>
                </template>
                <template v-slot:cell(toAddress)="row">
                    <div v-b-tooltip="{title: row.value, boundary: 'viewport'}">
                        <copy-link :content-to-copy="row.value" class="c-pointer">
                            <div class="text-truncate text-blue">
                                {{ row.value }}
                            </div>
                        </copy-link>
                    </div>
                </template>
            </b-table>
        </div>
        <div v-if="loading" class="p-1 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else-if="noHistory">
            <p class="text-center p-5">{{ $t('wallet.history.no_transactions') }}</p>
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {BTable, VBTooltip} from 'bootstrap-vue';
import {toMoney, formatMoney} from '../../utils';
import {
    LazyScrollTableMixin,
    FiltersMixin,
    RebrandingFilterMixin,
    LoggerMixin,
} from '../../mixins/';
import CopyLink from '../CopyLink';
import {GENERAL} from '../../utils/constants';

library.add(faCircleNotch);

export default {
    name: 'DepositWithdrawHistory',
    components: {
        BTable,
        CopyLink,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        LazyScrollTableMixin,
        FiltersMixin,
        RebrandingFilterMixin,
        LoggerMixin,
    ],
    data() {
        return {
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.history.date'),
                    sortable: true,
                    type: 'date',
                },
                type: {
                    key: 'type',
                    label: this.$t('wallet.history.type'),
                    sortable: true,
                    type: 'string',
                },
                symbol: {
                    key: 'symbol',
                    label: this.$t('wallet.history.symbol'),
                    sortable: true,
                    type: 'string',
                },
                toAddress: {
                    key: 'toAddress',
                    label: this.$t('wallet.history.to_address'),
                    sortable: true,
                    type: 'string',
                },
                amount: {
                    key: 'amount',
                    label: this.$t('wallet.history.amount'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                status: {
                    key: 'status',
                    label: this.$t('wallet.history.status'),
                    sortable: true,
                    type: 'string',
                },
                fee: {
                    key: 'fee',
                    label: this.$t('wallet.history.fee'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
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
        fieldsArray: function() {
            return Object.values(this.fields);
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
                    .catch((err) => {
                        this.sendLogs('error', 'Can not update payment history', err);
                        reject([]);
                    });
            });
        },
        sanitizeHistory: function(historyData) {
            historyData.forEach((item) => {
                item['url'] = this.generatePairUrl(item.tradable);
                item['date'] = item.date
                    ? moment(item.date).format(GENERAL.dateTimeFormat)
                    : null;
                item['fee'] = item.fee
                    ? toMoney(item.fee, item.tradable.subunit)
                    : null;
                item['amount'] = item.amount
                    ? toMoney(item.amount, item.tradable.subunit)
                    : null;
                item['symbol'] = item.tradable.symbol
                    ? this.rebrandingFunc(item.tradable)
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
                /** @TODO In future we need to use another solution and remove hardcoded BTC & MINTME symbols **/
                let params = {
                    base: !quote.exchangeble ? this.rebrandingFunc(quote) : 'BTC',
                    quote: quote.exchangeble && quote.tradable ? this.rebrandingFunc(quote) : 'MINTME',
                    tab: 'trade',
                };
                return this.$routing.generate('coin', params);
            }

            return this.$routing.generate('token_show', {name: quote.name, tab: 'trade'});
        },
    },
};
</script>
