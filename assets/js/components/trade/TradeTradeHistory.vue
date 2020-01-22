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
                            List of last closed orders for {{ market.base.symbol|rebranding }}.
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

                            <template v-slot:head(pricePerQuote)="row">
                                <span v-b-tooltip="{title: rebrandingFunc(market.quote.symbol), boundary:'viewport'}">
                                    Price per {{ market.quote.symbol | rebranding | truncate(7) }}
                                </span>
                            </template>

                            <template v-slot:head(quoteAmount)="row">
                                <span v-b-tooltip="{title: rebrandingFunc(market.quote.symbol), boundary:'viewport'}">
                                    {{ market.quote.symbol | rebranding | truncate(7) }} amount
                                </span>
                            </template>

                            <template v-slot:cell(orderMaker)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <span v-if="row.item.isMakerAnonymous" class="d-inline-block truncate-name flex-grow-1">
                                        {{ row.value }}
                                    </span>
                                    <a v-else :href="row.item.makerUrl" class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                        <span class="d-inline-block truncate-name flex-grow-1" v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                            {{ row.value }}
                                        </span>
                                        <img
                                            src="../../../img/avatar.png"
                                            class="d-block flex-grow-0"
                                            alt="avatar">
                                    </a>
                                    <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                    </a>
                                </div>
                            </template>
                            <template v-slot:cell(orderTrader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <span v-if="row.item.isTakerAnonymous" class="d-inline-block truncate-name flex-grow-1">
                                        {{ row.value }}
                                    </span>
                                    <a v-else :href="row.item.takerUrl" class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                        <span class="d-inline-block truncate-name flex-grow-1" v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                            {{ row.value }}
                                        </span>
                                        <img
                                            src="../../../img/avatar.png"
                                            class="d-block flex-grow-0"
                                            alt="avatar">
                                    </a>
                                    <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                    </a>
                                </div>
                            </template>
                            <template v-slot:cell(dateTime)="row">
                                <span class="truncate-name" v-b-tooltip="{title: row.value, boundary:'viewport'}">
                                    {{ row.value | truncate(11) }}
                                </span>
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
import moment from 'moment';
import Guide from '../Guide';
import {formatMoney, toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {GENERAL, WSAPI} from '../../utils/constants';
import {WebSocketMixin, FiltersMixin, LazyScrollTableMixin, RebrandingFilterMixin} from '../../mixins/';

export default {
    name: 'TradeTradeHistory',
    mixins: [WebSocketMixin, FiltersMixin, LazyScrollTableMixin, RebrandingFilterMixin],
    props: {
        market: Object,
    },
    components: {
        Guide,
    },
    data() {
        return {
            fields: [
                {
                    key: 'type',
                    label: 'Type',
                },
                {
                    key: 'orderMaker',
                    label: 'Order maker',
                },
                {
                    key: 'orderTrader',
                    label: 'Order taker',
                },
                {
                    key: 'pricePerQuote',
                    formatter: formatMoney,
                },
                {
                    key: 'quoteAmount',
                    formatter: formatMoney,
                },
                {
                    key: 'baseAmount',
                    label: this.rebrandingFunc(this.market.base.symbol) + ' amount',
                    formatter: formatMoney,
                },
                {
                    key: 'dateTime',
                    label: 'Date & Time',
                },
            ],
        };
    },
    computed: {
        hasOrders: function() {
            return this.ordersList.length > 0;
        },
        ordersList: function() {
            return this.tableData !== false ? this.tableData.map((order) => {
                return {
                    dateTime: moment.unix(order.timestamp).format(GENERAL.dateFormat),
                    orderMaker: order.maker && order.maker.profile && !order.maker.profile.anonymous
                        ? this.traderFullName(order.maker.profile)
                        : 'Anonymous',
                    orderTrader: order.taker && order.taker.profile && !order.taker.profile.anonymous
                        ? this.traderFullName(order.taker.profile)
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
                    isMakerAnonymous: !order.maker || !order.maker.profile || order.maker.profile.anonymous,
                    isTakerAnonymous: !order.taker || !order.taker.profile || order.taker.profile.anonymous,
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
                        if (this.tableData.findIndex((item) => item.id === res.data.id) === -1) {
                            this.tableData.unshift(res.data);
                        }
                    }).catch((err) => {
                        this.sendLogs('error', 'Can not get executed order details', err);
                    });
                }
            }, 'trade-tableData-update-deals');
        }).catch((err) => {
            this.sendLogs('error', 'Can not update table data', err);
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
        traderFullName: function(profile) {
            return profile.firstName + ' ' + profile.lastName;
        },
    },
};
</script>
