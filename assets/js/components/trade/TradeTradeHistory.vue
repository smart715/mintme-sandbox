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
                <div class="table-responsive fixed-head-table" ref="history">
                    <template v-if="loaded">
                        <b-table v-if="hasOrders" class="w-100" ref="table"
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
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {WSAPI} from '../../utils/constants';
import WebSocketMixin from '../../mixins/websocket';

export default {
    name: 'TradeTradeHistory',
    mixins: [WebSocketMixin],
    props: {
        market: Object,
        precision: Number,
    },
    components: {
        Guide,
    },
    data() {
        return {
            history: null,
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
                pricePerToken: {
                    label: 'Price per token',
                },
                tokenAmount: {
                    label: 'Token amount',
                },
                webAmount: {
                    label: 'WEB amount',
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
            return this.history !== false ? this.history.map((order) => {
                return {
                    dateTime: new Date(order.timestamp * 1000).toDateString(),
                    orderMaker: order.maker && order.maker.profile
                        ? this.truncateFullName(order.maker.profile)
                        : 'Anonymous',
                    orderTrader: order.taker && order.taker.profile
                        ? this.truncateFullName(order.taker.profile)
                        : 'Anonymous',
                    makerFullName: order.maker && order.maker.profile
                        ? order.maker.profile.firstName + ' ' + order.maker.profile.lastName
                        : 'Anonymous',
                    takerFullName: order.taker && order.taker.profile
                        ? order.taker.profile.firstName + ' ' + order.taker.profile.lastName
                        : 'Anonymous',
                    makerUrl: order.maker && order.maker.profile
                        ? this.$routing.generate('profile-view', {pageUrl: order.maker.profile.page_url})
                        : '',
                    takerUrl: order.taker && order.taker.profile
                        ? this.$routing.generate('profile-view', {pageUrl: order.taker.profile.page_url})
                        : '',
                    type: (order.side === WSAPI.order.type.BUY) ? 'Buy' : 'Sell',
                    pricePerToken: toMoney(order.price, this.precision),
                    tokenAmount: toMoney(order.amount, this.precision),
                    webAmount: toMoney(new Decimal(order.price).mul(order.amount).toString(), this.precision),
                };
            }) : [];
        },
        loaded: function() {
            return this.history !== null;
        },
        showDownArrow: function() {
            return (this.loaded && this.history.length > 7);
        },
    },
    mounted: function() {
        this.updateHistory().then(() => {
            this.addOnOpenHandler(() => {
                this.sendMessage(JSON.stringify({
                    method: 'deals.subscribe',
                    params: [this.market.identifier],
                    id: parseInt(Math.random().toString().replace('0.', '')),
                }));
            });

            this.addMessageHandler((response) => {
                if ('deals.update' === response.method) {
                    this.updateHistory();
                }
            });
        });
    },
    methods: {
        updateHistory: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('executed_orders', {
                    'base': this.market.base.symbol,
                    'quote': this.market.quote.symbol,
                })).then((result) => {
                    this.history = result.data;

                    if (this.$refs.table) {
                        this.$refs.table.refresh();
                    }

                    resolve();
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
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
    },
};
</script>

