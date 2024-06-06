<template>
    <div class="px-0 pt-2 deposit-withdraw-history-tab mt-4">
        <table-header
            :header="$t('page.wallet.deposit_withdraw_history.header')"
        />
        <div v-if="loaded">
            <template v-if="!noHistory">
                <div class="table-responsive fixed-head-table aligned-table text-nowrap px-3 py-4">
                    <b-table
                        thead-class="trading-head"
                        :items="sanitizedHistory"
                        :fields="fieldsArray"
                        :sort-compare="$sortCompare(fields)"
                        :sort-by="fields.date.key"
                        :sort-desc="true"
                        sort-direction="desc"
                        sort-icon-left
                        :class="{'empty-table': noHistory}"
                    >
                        <template v-slot:[`head(${fields.fee.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.amount.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.status.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:cell(date)="data">
                            <div v-b-tooltip="{title: data.value.hoverFormatDate}">
                                {{ data.value.tableFormatDate }}
                            </div>
                        </template>
                        <template v-slot:cell(type)="data">
                            <span v-if="transactionType.DEPOSIT === data.item.type">
                                {{ $t('wallet.type.deposit') }}
                            </span>
                            <span v-else-if="transactionType.WITHDRAW === data.item.type">
                                {{ $t('wallet.type.withdraw') }}
                            </span>
                            <span v-else >
                                {{ data.item.type }}
                            </span>
                        </template>
                        <template v-slot:cell(status)="data">
                            <span v-if="transactionStatus.PAID === data.item.status">
                                {{ $t('wallet.status.paid') }}
                            </span>
                            <span v-else-if="transactionStatus.PENDING === data.item.status">
                                {{ $t('wallet.status.pending') }}
                            </span>
                            <span
                                v-else-if="transactionStatus.MIN_DEPOSIT_PENDING === data.item.status"
                                v-b-tooltip="{title: $t('wallet.status.min_deposit.tooltip')}"
                            >
                                {{ $t('wallet.status.min_deposit') }}
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 text-white rounded-circle square guide-icon"
                                />
                            </span>
                            <span v-else-if="transactionStatus.ERROR === data.item.status" >
                                {{ $t('wallet.status.error') }}
                            </span>
                            <span v-else-if="transactionStatus.CONFIRMATION === data.item.status" >
                                {{ $t('wallet.status.confirmation') }}
                            </span>
                            <span v-else-if="transactionStatus.DISABLED === data.item.status" >
                                {{ $t('wallet.status.disabled') }}
                            </span>
                            <span v-else>
                                {{ data.item.type }}
                            </span>
                        </template>
                        <template v-slot:cell(symbol)="data">
                            <span v-if="!data.item.tradable.blocked">
                                <a :href="data.item.url" class="text-white">
                                    <coin-avatar
                                        :symbol="data.item.symbol"
                                        :is-crypto="!data.item.isToken"
                                        :is-user-token="data.item.isToken"
                                        :image="data.item.image"
                                        class="d-inline avatar avatar__coin"
                                    />
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
                                <copy-link
                                    :content-to-copy="row.value"
                                    class="c-pointer text-primary"
                                    tabindex="0"
                                >
                                    <div class="text-truncate text-blue">
                                        {{ row.value }}
                                    </div>
                                </copy-link>
                            </div>
                        </template>
                        <template v-slot:cell(fee)="row">
                            <div v-b-tooltip="{title: row.value, boundary: 'viewport'}">
                                <span class="d-inline text-truncate">
                                    {{ row.value }}
                                </span>
                                <template v-if="hasFee(row.item)">
                                    <coin-avatar
                                        :symbol="feeCurrencyFilter(row.item.feeCurrency, row.item.moneySymbol)"
                                        :is-crypto="isCrypto(
                                            feeCurrencyFilter(row.item.feeCurrency, row.item.moneySymbol)
                                        )"
                                        :is-user-token="row.item.isToken"
                                        :image="row.item.image"
                                        class="d-inline avatar avatar__coin"
                                    />
                                    <span class="d-inline text-truncate">
                                        {{ feeCurrencyFilter(row.item.feeCurrency, row.item.moneySymbol) | rebranding }}
                                    </span>
                                </template>
                                <template v-else-if="row.item.blockchain">
                                    <coin-avatar
                                        :symbol="row.item.blockchain.moneySymbol"
                                        :is-crypto="isCrypto(row.item.blockchain.moneySymbol)"
                                        :is-user-token="row.item.isToken"
                                        :image="row.item.image"
                                        class="d-inline avatar avatar__coin"
                                    />
                                    <span class="d-inline text-truncate">
                                        {{ row.item.blockchain.moneySymbol | rebranding }}
                                    </span>
                                </template>
                            </div>
                        </template>
                    </b-table>
                </div>
                <div class="table-bottom d-flex justify-content-around mt-2">
                    <div v-if="loading" class="p-1 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                    <m-button
                        v-if="showSeeMoreButton"
                        type="secondary-rounded"
                        @click="updateTableData"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                </div>
            </template>
            <div v-else-if="noHistory">
                <p class="text-center p-5">
                    {{ $t('wallet.history.no_transactions') }}
                </p>
            </div>
        </div>
        <template v-else>
            <div class="p-5 text-center">
                <span v-if="serviceUnavailable">
                    {{ this.$t('toasted.error.service_unavailable_support') }}
                </span>
                <font-awesome-icon
                    v-else
                    icon="circle-notch"
                    class="loading-spinner"
                    fixed-width
                    spin
                />
            </div>
        </template>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faQuestion} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {MButton} from '../UI';
import {BTable, VBTooltip} from 'bootstrap-vue';
import {toMoney, formatMoney} from '../../utils';
import {
    tokSymbol,
    transactionType,
    transactionStatus,
    GENERAL,
    WALLET_ITEMS_BATCH_SIZE,
} from '../../utils/constants';
import {
    FiltersMixin,
    RebrandingFilterMixin,
} from '../../mixins/';
import CopyLink from '../CopyLink';
import TableHeader from './TableHeader';
import CoinAvatar from '../CoinAvatar';
import {mapGetters} from 'vuex';


library.add(faCircleNotch, faQuestion);

export default {
    name: 'DepositWithdrawHistory',
    components: {
        BTable,
        MButton,
        CopyLink,
        FontAwesomeIcon,
        TableHeader,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
        RebrandingFilterMixin,
    ],
    data() {
        return {
            serviceUnavailable: false,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.history.date'),
                    sortable: true,
                    formatter: (date) => {
                        return {
                            tableFormatDate: moment(date).format(GENERAL.dateTimeFormatTable),
                            hoverFormatDate: moment(date).format(GENERAL.dateTimeFormat),
                        };
                    },
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
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                },
                fee: {
                    key: 'fee',
                    label: this.$t('wallet.history.fee'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                },
                status: {
                    key: 'status',
                    label: this.$t('wallet.history.status'),
                    sortable: true,
                    type: 'string',
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                },
            },
            tableData: null,
            scrollListenerAutoStart: false,
            currentPage: 0,
            perPage: WALLET_ITEMS_BATCH_SIZE,
            loading: false,
            allHistoryLoaded: false,
            transactionStatus,
            transactionType,
        };
    },
    computed: {
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
        sanitizedHistory: function() {
            return this.sanitizeHistory(JSON.parse(JSON.stringify(this.tableData)));
        },
        noHistory: function() {
            return 0 === this.tableData.length;
        },
        loaded: function() {
            return null !== this.tableData;
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        showSeeMoreButton: function() {
            return !this.loading
                && !this.allHistoryLoaded
                && !this.noHistory;
        },
        nextPage: function() {
            return this.currentPage + 1;
        },
    },
    mounted: function() {
        this.updateTableData();
    },
    methods: {
        feeCurrencyFilter: function(feeCurrency, currencySymbol) {
            return tokSymbol === feeCurrency ? currencySymbol : feeCurrency;
        },
        addDetailsForEmptyMessageToHistory: function(historyData) {
            if (0 === historyData.length) {
                historyData.push({_showDetails: true});
            }
            return historyData;
        },
        updateTableData: async function() {
            this.loading = true;
            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate(
                        'payment_history',
                        {page: this.nextPage}
                    )
                );
                this.tableData = null === this.tableData
                    ? response.data
                    : this.tableData.concat(response.data);

                if (response.data.length < this.perPage) {
                    this.allHistoryLoaded = true;
                }

                if (0 < response.data.length) {
                    this.currentPage++;
                }
            } catch (err) {
                this.serviceUnavailable = true;
                this.$logger.error('Can not update payment history', err);
            }

            this.loading = false;
        },
        sanitizeHistory: function(historyData) {
            historyData.forEach((item) => {
                item['url'] = this.generatePairUrl(item.tradable);
                item['date'] = item.date ? item.date : null;
                item['fee'] = item.fee
                    ? toMoney(item.fee, item.tradable.subunit)
                    : null;

                const isDepositPendingWithTax = item.tradable.hasTax &&
                    transactionStatus.PENDING === item.status.statusCode &&
                    transactionType.DEPOSIT === item.type.typeCode;

                item['amount'] = item.amount && !isDepositPendingWithTax
                    ? toMoney(item.amount, item.tradable.subunit)
                    : null;
                item['symbol'] = item.tradable.symbol
                    ? this.rebrandingFunc(item.tradable)
                    : null;
                item['isToken'] = item.tradable.quiet !== undefined;
                item['image'] = item.tradable.image;
                item['status'] = item.status.statusCode
                    ? item.status.statusCode
                    : null;
                item['type'] = this.humanizeTransactionType(item.type.typeCode, item.isBonus);
            });

            return historyData;
        },
        humanizeTransactionType: function(type, isBonus) {
            if (!type) {
                return null;
            }

            if (!isBonus) {
                return type;
            }

            return type === transactionType.WITHDRAW
                ? this.$t('wallet.history.bonus_spent')
                : this.$t('wallet.history.bonus_received');
        },
        generatePairUrl: function(quote) {
            if (quote.hasOwnProperty('exchangeble')) {
                /** @TODO In future we need to use another solution and remove hardcoded BTC & MINTME symbols **/
                const params = {
                    base: !quote.exchangeble ? this.rebrandingFunc(quote) : 'BTC',
                    quote: quote.exchangeble && quote.tradable ? this.rebrandingFunc(quote) : 'MINTME',
                    tab: 'trade',
                };
                return this.$routing.generate('coin', params);
            }

            return this.$routing.generate('token_show_trade', {name: quote.name});
        },
        hasFee: function(item) {
            return '0' !== item.fee && !!item.feeCurrency;
        },
        isCrypto: function(symbol) {
            return !!this.enabledCryptosMap[this.reverseRebrandingFunc(symbol)];
        },
    },
};
</script>
