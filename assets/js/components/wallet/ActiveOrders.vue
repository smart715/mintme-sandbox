<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="table-responsive table-restricted" ref="table">
                <b-table
                    ref="btable"
                    v-if="hasOrders"
                    :items="getHistory"
                    :fields="fields">
                    <template slot="name" slot-scope="row">
                        <div v-b-tooltip="{title: row.value.full, boundary: 'viewport'}">{{ row.value.truncate }}</div>
                    </template>
                    <template slot="action" slot-scope="row">
                        <a @click="removeOrderModal(row.item)">
                            <span class="icon-cancel c-pointer"></span>
                        </a>
                    </template>
                </b-table>
                <div v-if="!hasOrders">
                    <p class="text-center p-5">No order was added yet</p>
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
                    Are you sure that you want to remove {{ this.currentRow.name }}
                    with amount {{ this.currentRow.amount }} and price {{ this.currentRow.price }}
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
import {LazyScrollTableMixin, FiltersMixin, WebSocketMixin} from '../../mixins';

export default {
    name: 'ActiveOrders',
    mixins: [WebSocketMixin, FiltersMixin, LazyScrollTableMixin],
    components: {
        ConfirmModal,
    },
    props: {
        userId: Number,
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
                date: {label: 'Date', sortable: true},
                type: {label: 'Type', sortable: true},
                name: {
                    label: 'Name',
                    sortable: true,
                    formatter: (name) => {
                        return {
                            full: name,
                            truncate: this.truncateFunc(name, 7),
                        };
                    },
                },
                amount: {label: 'Amount', sortable: true},
                price: {
                    label: 'Price',
                    sortable: true,
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                },
                fee: {label: 'Fee', sortable: true},
                action: {label: 'Action', sortable: false},
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
                }, 'active-tableData-update');
            })
            .catch(() => this.$toasted.error('Can not update order list now. Try again later'));
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
                    .catch(() => {
                        this.$toasted.error('Can not update orders history. Try again later.');
                        reject([]);
                    });
            });
        },
        getHistory: function() {
            return this.tableData.map((order) => {
                return {
                    date: moment.unix(order.timestamp).format(GENERAL.dateFormat),
                    type: WSAPI.order.type.SELL === parseInt(order.side) ? 'Sell' : 'Buy',
                    name: order.market.base.symbol + '/' + order.market.quote.symbol,
                    amount: formatMoney(toMoney(order.amount, order.market.base.subunit)),
                    price: formatMoney(toMoney(order.price, order.market.base.subunit)),
                    total: formatMoney(toMoney(new Decimal(order.price).mul(order.amount).toString(), order.market.base.subunit)),
                    fee: order.fee * 100 + '%',
                    action: this.$routing.generate('orders_сancel', {
                        base: order.market.base.symbol,
                        quote: order.market.quote.symbol,
                    }),
                    id: order.id,
                };
            });
        },
        removeOrderModal: function(row) {
            this.currentRow = row;
            this.actionUrl = row.action;
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            this.$axios.single.post(this.actionUrl, {'orderData': [this.currentRow.id]})
                .catch(() => {
                    this.$toasted.show('Service unavailable, try again later');
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
