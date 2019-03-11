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
                <div class="table-responsive fix-height">
                    <template v-if="loaded">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="orderMaker" slot-scope="row">
                            <a :href="row.item.maker_url">
                                {{ row.value }}
                                <img
                                    src="../../../img/avatar.png"
                                    class="pl-3"
                                    alt="avatar">
                            </a>
                        </template>
                        <template slot="orderTrader" slot-scope="row">
                            <a :href="row.item.taker_url">
                                {{ row.value }}
                                <img
                                    src="../../../img/avatar.png"
                                    class="pl-3"
                                    alt="avatar">
                            </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <h4 class="text-center p-5">No deal was made yet</h4>
                    </div>
                    </template>
                    <template v-else>
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </template>
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
                    orderMaker: order.maker != null
                        ? order.maker.profile ? this.truncateFullName(order.maker.profile): 'Anonymous'
                        : '',
                    orderTrader: order.taker != null
                        ? order.taker.profile ? this.truncateFullName(order.taker.profile): 'Anonymous'
                        : '',
                    maker_url: order.maker != null
                        ? this.$routing.generate('token_show', {name: order.maker.profile.token.name})
                        : '',
                    taker_url: order.taker != null
                        ? this.$routing.generate('token_show', {name: order.taker.profile.token.name})
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
            if ((first + second).length > 23) {
                return first.slice(0, 5) + '. ' + second.slice(0, 10) + '.';
            } else {
                return first + ' ' + second;
            }
        },
    },
};
</script>

