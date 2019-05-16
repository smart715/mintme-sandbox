<template>
    <div>
        <div class="card">
            <div class="card-header">
                Trade History
                <span class="card-header-icon">
                    <guide>
                        <template slot="header">
                            Trade History
                        </template>
                        <template slot="body">
                            List of last closed orders for {{ market.base.symbol }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fixed-head-table" ref="tableData">
                    <template v-if="loaded">
                        <b-table
                            v-if="hasOrders"
                            class="w-100"
                            ref="table"
                            :items="ordersList"
                            :fields="fields">
                            <template slot="orderMaker" slot-scope="row">
                                <a :href="row.item.makerUrl">
                                    <span v-b-tooltip="{title: row.item.makerFullName, boundary:'viewport'}">
                                        {{ row.value }}
                                    </span>
                                    <img
                                        src="../../../img/avatar.png"
                                        class="pl-3"
                                        alt="avatar">
                                </a>
                            </template>
                            <template slot="orderTrader" slot-scope="row">
                                <a :href="row.item.takerUrl">
                                    <span v-b-tooltip="{title: row.item.takerFullName, boundary:'viewport'}">
                                        {{ row.value }}
                                    </span>
                                    <img
                                        src="../../../img/avatar.png"
                                        class="pl-3"
                                        alt="avatar">
                                </a>
                            </template>
                        </b-table>
                        <div v-if="!hasOrders">
                            <p class="text-center p-5">No deal was made yet</p>
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
                <div class="text-center pb-2" v-if="showDownArrow">
                    <img
                        src="../../../img/down-arrows.png"
                        class="icon-arrows-down c-pointer"
                        alt="arrow down"
                        @click="scrollDown">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {formatMoney, toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {WSAPI} from '../../utils/constants';
import {WebSocketMixin, LazyScrollTableMixin} from '../../mixins';

export default {
    name: 'TradeTradeHistory',
    mixins: [WebSocketMixin, LazyScrollTableMixin],
    props: {
        market: Object,
    },
    components: {
        Guide,
    },
    data() {
        return {
            fields: {
                type: {
                    label: 'Type',
                },
                orderMaker: {
                    label: 'Order maker',
                },
                orderTrader: {
                    label: 'Order taker',
                },
                pricePerQuote: {
                    label: 'Price per ' + this.market.quote.symbol,
                    formatter: formatMoney,
                },
                quoteAmount: {
                    label: this.market.quote.symbol + ' amount',
                    formatter: formatMoney,
                },
                baseAmount: {
                    label: this.market.base.symbol + ' amount',
                    formatter: formatMoney,
                },
                dateTime: {
                    label: 'Date & Time',
                },
            },
        };
    },
    computed: {
        hasOrders: function() {
            return this.ordersList.length > 0;
        },
        ordersList: function() {
            return this.tableData !== false ? this.tableData.map((order) => {
                return {
                    dateTime: new Date(order.timestamp * 1000).toDateString(),
                    orderMaker: order.maker && order.maker.profile && !order.maker.profile.anonymous
                        ? this.truncateFullName(order.maker.profile)
                        : 'Anonymous',
                    orderTrader: order.taker && order.taker.profile && !order.taker.profile.anonymous
                        ? this.truncateFullName(order.taker.profile)
                        : 'Anonymous',
                    makerFullName: order.maker && order.maker.profile && !order.maker.profile.anonymous
                        ? order.maker.profile.firstName + ' ' + order.maker.profile.lastName
                        : 'Anonymous',
                    takerFullName: order.taker && order.taker.profile && !order.taker.profile.anonymous
                        ? order.taker.profile.firstName + ' ' + order.taker.profile.lastName
                        : 'Anonymous',
                    makerUrl: order.maker && order.maker.profile && !order.maker.profile.anonymous
                        ? this.$routing.generate('profile-view', {pageUrl: order.maker.profile.page_url})
                        : '',
                    takerUrl: order.taker && order.taker.profile && !order.taker.profile.anonymous
                        ? this.$routing.generate('profile-view', {pageUrl: order.taker.profile.page_url})
                        : '',
                    type: (order.side === WSAPI.order.type.BUY) ? 'Buy' : 'Sell',
                    pricePerQuote: toMoney(order.price, this.market.base.subunit),
                    quoteAmount: toMoney(order.amount, this.market.quote.subunit),
                    baseAmount: toMoney(
                        new Decimal(order.price).mul(order.amount).toString(),
                        this.market.base.subunit
                    ),
                };
            }) : [];
        },
        loaded: function() {
            return this.tableData !== null;
        },
        lastId: function() {
            return this.tableData && this.tableData[0] && this.tableData[0].hasOwnProperty('id') ?
                this.tableData[0].id :
                0;
        },
    },
    mounted: function() {
        this.updateTableData().then((res) => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if ('deals.update' === response.method) {
                    const orders = response.params[1];

                    if (orders.length !== 1) {
                        return;
                    }

                    this.$axios.retry.get(this.$routing.generate('executed_order_details', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        id: parseInt(orders[0].id),
                    })).then((res) => {
                        this.tableData.unshift(res.data);
                    });
                }
            }, 'trade-tableData-update-deals');
        });
    },
    methods: {
        startScrollListeningOnce: function(val) {
            // Disable listener from mixin
        },
        updateTableData: function(attach = false) {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('executed_orders', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                    id: this.lastId,
                })).then((result) => {
                    if (!result.data.length) {
                        if (!attach) {
                            this.tableData = result.data;
                        }

                        return resolve([]);
                    }

                    this.tableData = !attach ? result.data : this.tableData.concat(result.data);

                    if (this.$refs.table) {
                        this.$refs.table.refresh();
                    }

                    resolve(result.data);
                }).catch(reject);
            });
        },
        truncateFullName: function(profile) {
            let first = profile.firstName;
            let second = profile.lastName;
            if ((first + second).length > 7) {
                return first.length > 7
                    ? first.slice(0, 7) + '..'
                    : first + ' ' + second.slice(0, 7 - first.length) + '..';
            } else {
                return first + ' ' + second;
            }
        },
    },
};
</script>

