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
                            List of last closed orders for {{ tokenName }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive table-orders" ref="history">
                    <template v-if="loaded">
                        <b-table v-if="hasOrders" class="w-100" ref="table"
                            :items="ordersList"
                            :fields="fields">
                            <template slot="orderMaker" slot-scope="row">
                                <a :href="row.item.makerUrl">
                                    {{ row.value }}
                                    <img
                                        src="../../../img/avatar.png"
                                        class="pl-3"
                                        alt="avatar">
                                </a>
                            </template>
                            <template slot="orderTrader" slot-scope="row">
                                <a :href="row.item.takerUrl">
                                    {{ row.value }}
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
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeTradeHistory',
    props: {
        tokenName: String,
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
                    label: 'Order trader',
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
                    orderMaker: order.maker !== null
                        ? order.maker.profile ? this.truncateFullName(order.maker.profile): 'Anonymous'
                        : '',
                    orderTrader: order.taker !== null
                        ? order.taker.profile ? this.truncateFullName(order.taker.profile): 'Anonymous'
                        : '',
                    makerUrl: order.maker !== null
                        ? order.maker.profile ?
                            this.$routing.generate('token_show', {name: order.maker.profile.token.name}) : ''
                        : '',
                    takerUrl: order.taker !== null
                        ? order.taker.profile ?
                            this.$routing.generate('token_show', {name: order.taker.profile.token.name}) : ''
                        : '',
                    type: (order.side === 0) ? 'Buy' : 'Sell',
                    pricePerToken: toMoney(order.price),
                    tokenAmount: toMoney(order.amount),
                    webAmount: toMoney(new Decimal(order.price).mul(order.amount).toString()),
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
        this.updateHistory();
        this.$store.state.interval.make(this.updateHistory, 10000);
    },
    methods: {
        updateHistory: function() {
            this.$axios.single.get(this.$routing.generate('executed_orders', {
                'tokenName': this.tokenName,
            })).then((result) => {
                this.history = result.data;
                this.$refs.table.refresh();
            }).catch((error) => { });
        },
        truncateFullName: function(profile) {
            let first = profile.firstName;
            let second = profile.lastName;
            if ((first + second).length > 9) {
                return second.slice(0, 6) + '. ' + first.slice(0, 1);
            } else {
                return first + ' ' + second;
            }
        },
        scrollDown: function() {
            let parentDiv = this.$refs.history;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
    },
};
</script>

