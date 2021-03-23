<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="active-orders table-responsive table-restricted" ref="table">
                <b-table
                    thead-class="trading-head"
                    ref="btable"
                    v-if="hasOrders"
                    :items="history"
                    :fields="fieldsArray"
                    :sort-compare="$sortCompare(fields)"
                    :sort-by="fields.date.key"
                    :sort-desc="true"
                    sort-direction="desc"
                    sort-icon-left
                    no-sort-reset
                >
                    <template v-slot:cell(name)="row">
                        <div v-if="row.value.full.length <= 7">
                            <span v-if="row.item.blocked && !row.item.isCryptoMarket">
                                <span class="text-muted">
                                    {{ row.value.full }}
                                </span>
                            </span>
                            <span v-else>
                                <a :href="row.item.pairUrl" class="text-white">
                                    {{ row.value.full }}
                                </a>
                            </span>
                        </div>
                        <div v-else v-b-tooltip="{title: row.value.full, boundary: 'viewport'}">
                            <span v-if="row.item.blocked && !row.item.isCryptoMarket">
                                <span class="text-muted">
                                    {{ row.value.truncate }}
                                </span>
                            </span>
                            <span v-else>
                                <a :href="row.item.pairUrl" class="text-white">
                                    {{ row.value.truncate }}
                                </a>
                            </span>
                        </div>
                    </template>
                    <template v-slot:cell(action)="row">
                        <a @click="removeOrderModal(row.item)">
                            <span class="icon-cancel c-pointer" :class="{'cancel-forbidden': row.item.blocked}"></span>
                        </a>
                    </template>
                </b-table>
                <div v-if="!hasOrders">
                    <p class="text-center p-5">{{ $t('wallet.active_orders.no_order') }}</p>
                </div>
            </div>
            <div v-if="loading" class="p-1 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
            <confirm-modal
                    :visible="confirmModal"
                    @close="switchConfirmModal(false)"
                    @confirm="removeOrder"
            >
                <div class="pt-2">
                    {{ $t('wallet.active_orders.confirm_body', translationsContext) }}
                </div>
            </confirm-modal>
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
import ConfirmModal from '../modal/ConfirmModal';
import Decimal from 'decimal.js';
import {GENERAL, WSAPI} from '../../utils/constants';
import {toMoney, formatMoney, getUserOffset} from '../../utils';
import {
    LazyScrollTableMixin,
    FiltersMixin,
    WebSocketMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    LoggerMixin,
    PairNameMixin,
    OrderMixin,
} from '../../mixins/';

export default {
    name: 'ActiveOrders',
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
        PairNameMixin,
        OrderMixin,
    ],
    components: {ConfirmModal},
    props: {
        userId: Number,
        isUserBlocked: Boolean,
    },
    data() {
        return {
            markets: null,
            tableData: null,
            currentRow: {},
            actionUrl: '',
            currentPage: 2,
            confirmModal: false,
            tokenName: null,
            amount: null,
            price: null,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.active_orders.table.date'),
                    sortable: true,
                    type: 'date',
                },
                type: {
                    key: 'type',
                    label: this.$t('wallet.active_orders.table.type'),
                    sortable: true,
                    type: 'string',
                },
                name: {
                    key: 'name',
                    label: this.$t('wallet.active_orders.table.name'),
                    sortable: true,
                    formatter: (name) => {
                        return {
                            full: name,
                            truncate: this.truncateFunc(name, 7),
                        };
                    },
                    type: 'string',
                },
                amount: {
                    key: 'amount',
                    label: this.$t('wallet.active_orders.table.amount'),
                    sortable: true,
                    type: 'numeric',
                },
                price: {
                    key: 'price',
                    label: this.$t('wallet.active_orders.table.price'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                total: {
                    key: 'total',
                    label: this.$t('wallet.active_orders.table.total_cost'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                fee: {
                    key: 'fee',
                    label: this.$t('wallet.active_orders.table.fee'),
                    sortable: true,
                    type: 'numeric',
                },
                action: {
                    key: 'action',
                    label: this.$t('wallet.active_orders.table.action'),
                    sortable: false,
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.tableData.length;
        },
        marketNames: function() {
            return this.markets.map((market) => market.identifier);
        },
        hasOrders: function() {
            return this.totalRows > 0;
        },
        loaded: function() {
            return this.markets !== null && this.tableData !== null;
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        history: function() {
            return this.tableData.map((order) => {
                return {
                    date: moment.unix(order.timestamp).format(GENERAL.dateTimeFormat),
                    type: this.getSideByType(order.side),
                    name: this.pairNameFunc(
                        this.rebrandingFunc(order.market.base),
                        this.rebrandingFunc(order.market.quote)
                    ),
                    amount: toMoney(order.amount, order.market.base.subunit),
                    price: toMoney(order.price, order.market.base.subunit),
                    total: toMoney(new Decimal(order.price).mul(order.amount).toString(), order.market.base.subunit),
                    fee: order.fee * 100 + '%',
                    action: this.$routing.generate('orders_сancel', {
                        base: order.market.base.symbol,
                        quote: order.market.quote.symbol,
                    }),
                    id: order.id,
                    pairUrl: this.generatePairUrl(order.market),
                    blocked: order.market.quote.hasOwnProperty('blocked') ? order.market.quote.blocked : this.isUserBlocked,
                    isCryptoMarket: !order.market.base.exchangeble,
                };
            });
        },
        translationsContext: function() {
            return {
                name: this.currentRow.name || '-',
                amount: this.currentRow.amount || 0,
                price: this.currentRow.price || 0,
            };
        },
    },
    mounted: function() {
        Promise.all([
                this.$axios.retry.get(this.$routing.generate('markets')).then((res) =>
                    this.markets = typeof res.data === 'object' ? Object.values(res.data) : res.data
                ),
                this.$axios.retry.get(this.$routing.generate('orders')).then((res) =>
                    this.tableData = typeof res.data === 'object' ? Object.values(res.data) : res.data
                ),
            ])
            .then(() => {
                this.sendMessage(JSON.stringify({
                    method: 'order.subscribe',
                    params: this.marketNames,
                    id: parseInt(Math.random().toString().replace('0.', '')),
                }));

                this.addMessageHandler((response) => {
                    if ('order.update' === response.method &&
                        this.userId + getUserOffset() === response.params[1].user) {
                        this.updateOrders(response.params[1], response.params[0]);
                        if (this.$refs.btable) {
                            this.$refs.btable.refresh();
                        }
                    }
                }, 'active-tableData-update', 'ActiveOrders');
            })
            .catch((err) => {
                this.sendLogs('error', 'Service unavailable. Can not update order list now', err);
            });
    },
    methods: {
        updateTableData: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('orders', {page: this.currentPage}))
                    .then((res) => {
                        res.data = typeof res.data === 'object' ? Object.values(res.data) : res.data;

                        if (this.tableData === null) {
                            this.tableData = res.data;
                            this.currentPage++;
                        } else if (res.data.length > 0) {
                            this.tableData = this.tableData.concat(res.data);
                            this.currentPage++;
                        }

                        if (this.$refs.btable) {
                            this.$refs.btable.refresh();
                        }

                        resolve(this.tableData);
                    })
                    .catch((err) => {
                        this.sendLogs('error', 'Service unavailable. Can not update orders history', err);
                        reject([]);
                    });
            });
        },
        generatePairUrl: function(market) {
            if (market.quote.hasOwnProperty('exchangeble') && market.quote.exchangeble && market.quote.tradable) {
                return this.$routing.generate('coin', {
                    base: this.rebrandingFunc(market.base),
                    quote: this.rebrandingFunc(market.quote),
                    tab: 'trade',
                });
            }
            return this.$routing.generate('token_show', {name: market.quote.name, tab: 'trade'});
        },
        removeOrderModal: function(item) {
            if (item.blocked) {
                return;
            }
            this.currentRow = item;
            this.actionUrl = item.action;
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            this.$axios.single.post(this.actionUrl, {'orderData': [this.currentRow.id]})
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.service_unavailable'));
                    this.sendLogs('error', 'Service unavailable. Can not remove orders', err);
                });
        },
        getMarketFromName: function(name) {
            return this.markets.find((market) => market.identifier === name);
        },
        updateOrders: function(data, type) {
            let order = this.tableData.find((order) => data.id === order.id);

            switch (type) {
                case WSAPI.order.status.PUT:
                    this.tableData.unshift({
                        amount: data.left,
                        price: data.price,
                        fee: WSAPI.order.type.SELL === parseInt(data.type)
                            ? data.maker_fee : data.taker_fee,
                        id: data.id,
                        side: data.side,
                        timestamp: data.mtime,
                        market: this.getMarketFromName(data.market),
                    });
                    break;
                case WSAPI.order.status.UPDATE:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    let index = this.tableData.indexOf(order);
                    order.amount = data.left;
                    order.price = data.price;
                    order.timestamp = data.mtime;
                    this.tableData[index] = order;
                    break;
                case WSAPI.order.status.FINISH:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    this.tableData.splice(this.tableData.indexOf(order), 1);
                    break;
            }

            this.tableData.sort((a, b) => a.timestamp < b.timestamp);
            if (this.$refs.btable) {
                this.$refs.btable.refresh();
            }
        },
    },
};
</script>
