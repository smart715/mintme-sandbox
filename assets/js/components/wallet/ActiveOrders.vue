<template>
    <div class="px-0 pt-2 active-orders-tab mt-4">
        <table-header
            :header="$t('page.wallet.active_orders.header')"
        />
        <div v-if="loaded">
            <template v-if="hasOrders">
                <div class="active-orders table-responsive fixed-head-table aligned-table text-nowrap px-3 py-4">
                    <b-table
                        v-if="hasOrders"
                        thead-class="trading-head"
                        :items="history"
                        :fields="fieldsArray"
                        :sort-compare="$sortCompare(fields)"
                        :sort-by="fields.date.key"
                        :sort-desc="false"
                        no-sort-reset
                        sort-icon-left
                    >
                        <template v-slot:[`head(${fields.amount.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.price.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.total.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:cell(name)="row">
                            <div>
                                <span v-if="row.item.blocked && !row.item.isCryptoMarket">
                                    <span class="text-muted" v-html="row.value.full" />
                                </span>
                                <span v-else>
                                    <a
                                        :href="row.item.pairUrl"
                                        class="text-white"
                                        v-html="row.value.full"
                                    />
                                </span>
                            </div>
                        </template>
                        <template v-slot:cell(action)="row">
                            <a
                                tabindex="0"
                                @click="removeOrderModal(row.item)"
                            >
                                <span
                                    class="c-pointer cancel-btn"
                                    :class="{'cancel-forbidden': row.item.blocked}"
                                >
                                    {{ $t('page.profile.cancel') }}
                                </span>
                            </a>
                        </template>
                        <template v-slot:cell(date)="row">
                            <div v-b-tooltip="{title: row.value.hoverFormatDate}">
                                {{ row.value.tableFormatDate }}
                            </div>
                        </template>
                    </b-table>
                </div>
                <div class="table-bottom d-flex justify-content-around mt-2">
                    <div v-if="loading" class="p-1 text-center">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
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
            <div v-if="!hasOrders">
                <p class="text-center p-5">
                    {{ $t('wallet.active_orders.no_order') }}
                </p>
            </div>
            <confirm-modal
                :visible="confirmModal"
                @close="switchConfirmModal(false)"
                @confirm="removeOrder"
            >
                <div class="pt-2" v-html="$t('wallet.active_orders.confirm_body', translationsContext)" />
            </confirm-modal>
        </div>
        <template v-else>
            <div class="p-5 text-center">
                <span v-if="serviceUnavailable">
                    {{ this.$t('toasted.error.service_unavailable_support') }}
                </span>
                <div v-else class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </template>
    </div>
</template>
<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import {MButton} from '../UI';
import {BTable, VBTooltip} from 'bootstrap-vue';
import ConfirmModal from '../modal/ConfirmModal';
import {
    GENERAL,
    WALLET_ITEMS_BATCH_SIZE,
    WSAPI,
    HTTP_ACCESS_DENIED,
} from '../../utils/constants';
import {
    toMoney,
    formatMoney,
    getUserOffset,
    generateCoinAvatarHtml,
} from '../../utils';
import {
    FiltersMixin,
    WebSocketMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    PairNameMixin,
    OrderMixin,
} from '../../mixins/';
import TableHeader from './TableHeader';

export const BREAK_LINE = `<br class="break-line">`;

export default {
    name: 'ActiveOrders',
    components: {
        BTable,
        MButton,
        ConfirmModal,
        TableHeader,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        PairNameMixin,
        OrderMixin,
    ],
    props: {
        userId: Number,
        isUserBlocked: Boolean,
    },
    data() {
        return {
            serviceUnavailable: false,
            loading: false,
            markets: null,
            tableData: null,
            scrollListenerAutoStart: false,
            currentRow: {},
            actionUrl: '',
            currentPage: 0,
            perPage: WALLET_ITEMS_BATCH_SIZE,
            allHistoryLoaded: false,
            confirmModal: false,
            tokenName: null,
            amount: null,
            price: null,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.active_orders.table.date'),
                    sortable: true,
                    formatter: (date) => {
                        return {
                            tableFormatDate: moment.unix(date).format(GENERAL.dateTimeFormatTable),
                            hoverFormatDate: moment.unix(date).format(GENERAL.dateTimeFormat),
                        };
                    },
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
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                },
                price: {
                    key: 'price',
                    label: this.$t('wallet.active_orders.table.price'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                },
                total: {
                    key: 'total',
                    label: this.$t('wallet.active_orders.table.total_cost'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                    thClass: 'text-right sorting-arrows-th',
                    tdClass: 'text-right',
                    thStyle: {width: '7rem'},
                },
                action: {
                    key: 'action',
                    label: this.$t('wallet.active_orders.table.action'),
                    sortable: false,
                    thClass: 'text-right',
                    tdClass: 'text-right',
                },
            },
        };
    },
    computed: {
        nextPage: function() {
            return this.currentPage + 1;
        },
        totalRows: function() {
            return this.tableData
                ? this.tableData.length
                : 0;
        },
        marketNames: function() {
            return this.markets.map((market) => market.identifier);
        },
        hasOrders: function() {
            return 0 < this.totalRows;
        },
        loaded: function() {
            return null !== this.markets && null !== this.tableData;
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        showSeeMoreButton: function() {
            return this.hasOrders
                && !this.allHistoryLoaded
                && !this.loading;
        },
        history: function() {
            return this.tableData.map((order) => {
                return {
                    date: order.timestamp,
                    type: this.getSideByType(order.side),
                    name: this.pairNameFunc(
                        order.market.base,
                        order.market.quote
                    ),
                    amount: toMoney(order.amount, order.market.base.subunit),
                    price: toMoney(order.price, this.getPriceSubunits(order.market)),
                    total: toMoney(
                        new Decimal(order.price).mul(order.amount).toString(), this.getPriceSubunits(order.market)
                    ),
                    action: this.$routing.generate('orders_сancel', {
                        base: order.market.base.symbol,
                        quote: order.market.quote.symbol,
                    }),
                    id: order.id,
                    pairUrl: this.generatePairUrl(order.market),
                    blocked: order.market.quote.hasOwnProperty('blocked')
                        ? order.market.quote.blocked
                        : this.isUserBlocked,
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
            this.getMarkets(),
            this.updateTableData(),
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
                    }
                }, 'active-tableData-update', 'ActiveOrders');
            })
            .catch((err) => {
                this.$logger.error('Service unavailable. Can not update order list now', err);
            });
    },
    methods: {
        getPriceSubunits(market) {
            return market.quote.hasOwnProperty('priceDecimals') && null !== market.quote.priceDecimals
                ? market.quote.priceDecimals
                : market.base.subunit;
        },
        getMarkets: async function() {
            try {
                const res = await this.$axios.retry.get(this.$routing.generate('markets'));

                this.markets = 'object' === typeof res.data
                    ? Object.values(res.data)
                    : res.data;
            } catch (err) {
                this.serviceUnavailable = true;
                this.$logger.error('Service unavailable. Can not get markets for orders history', err);
            }
        },
        updateTableData: async function() {
            this.loading = true;

            try {
                const response = await this.$axios.retry.get(this.$routing.generate('orders', {page: this.nextPage}));

                if (null === this.tableData) {
                    this.tableData = response.data;
                } else if (0 < response.data.length) {
                    const orders = response.data.filter(
                        (order) => !(this.tableData.find((existingOrder) => existingOrder.id === order.id))
                    );

                    this.tableData = this.tableData.concat(orders);
                }

                if (response.data.length < this.perPage) {
                    this.allHistoryLoaded = true;
                }

                if (0 < response.data.length) {
                    this.currentPage++;
                }
            } catch (err) {
                this.serviceUnavailable = true;
                this.$logger.error('Service unavailable. Can not update orders history', err);
            } finally {
                this.loading = false;
            }
        },
        generatePairUrl: function(market) {
            if (market.quote.hasOwnProperty('exchangeble') && market.quote.exchangeble && market.quote.tradable) {
                return this.$routing.generate('coin', {
                    base: this.rebrandingFunc(market.base),
                    quote: this.rebrandingFunc(market.quote),
                    tab: 'trade',
                });
            }
            return this.$routing.generate('token_show_trade', {
                name: market.quote.name,
                crypto: this.rebrandingFunc(market.base.symbol),
            });
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
                .then((response) => {
                    const data = response.data;

                    if (data.hasOwnProperty('error')) {
                        this.notifyError(data.error);
                    }
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.service_unavailable'));
                    }
                    this.$logger.error('Service unavailable. Can not remove orders', err);
                });
        },
        getMarketFromName: function(name) {
            return this.markets.find((market) => market.identifier === name);
        },
        updateOrders: function(data, type) {
            const order = this.tableData.find((order) => data.id === order.id);

            switch (type) {
                case WSAPI.order.status.PUT:
                    this.tableData.unshift({
                        amount: data.left,
                        price: data.price,
                        id: data.id,
                        side: data.side,
                        timestamp: data.mtime,
                        market: this.getMarketFromName(data.market),
                    });
                    break;
                case WSAPI.order.status.UPDATE:
                    if ('undefined' === typeof order) {
                        return;
                    }

                    const index = this.tableData.indexOf(order);
                    order.amount = data.left;
                    order.price = data.price;
                    order.timestamp = data.mtime;
                    this.tableData[index] = order;
                    break;
                case WSAPI.order.status.FINISH:
                    if ('undefined' === typeof order) {
                        return;
                    }

                    this.tableData.splice(this.tableData.indexOf(order), 1);
                    break;
            }

            this.tableData.sort((a, b) => a.timestamp < b.timestamp);
        },
        pairNameFunc(baseSymbol, quoteSymbol) {
            const quoteSymbolAvatar = quoteSymbol?.isToken === undefined
                ? generateCoinAvatarHtml({
                    image: quoteSymbol.image.url,
                    isUserToken: true,
                })
                : generateCoinAvatarHtml({
                    symbol: quoteSymbol.symbol,
                    isCrypto: true,
                    withSymbol: false,
                });
            const baseSymbolAvatar = generateCoinAvatarHtml({
                symbol: baseSymbol.symbol,
                isCrypto: true,
                withSymbol: false,
            });

            return `${quoteSymbolAvatar} ${this.truncateFunc(this.rebrandingFunc(quoteSymbol.symbol), 7)}
                /${BREAK_LINE} ${baseSymbolAvatar} ${this.rebrandingFunc(baseSymbol)}`;
        },
    },
};
</script>
