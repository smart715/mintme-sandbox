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
                <div class="table-responsive fix-height" ref="history">
                    <template v-if="loaded">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="order_maker" slot-scope="row">
                           {{ row.value }}
                           <img
                               src="../../../img/avatar.png"
                               class="float-right"
                               alt="avatar">
                        </template>
                        <template slot="order_trader" slot-scope="row">
                           {{ row.value }}
                           <img
                               src="../../../img/avatar.png"
                               class="float-right"
                               alt="avatar">
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
                order_maker: {
                    label: 'Order maker',
                },
                order_trader: {
                    label: 'Order trader',
                },
                price_per_token: {
                    label: 'Price per token',
                },
                token_amount: {
                    label: 'Token amount',
                },
                web_amount: {
                    label: 'WEB amount',
                },
                date_time: {
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
                    date_time: new Date(order.timestamp * 1000).toDateString(),
                    order_maker: order.maker != null
                        ? order.maker.profile ? this.profileToString(order.maker.profile): 'Anonymous'
                        : '',
                    order_trader: order.taker != null
                        ? order.taker.profile ? this.profileToString(order.taker.profile): 'Anonymous'
                        : '',
                    type: (order.side === 0) ? 'Buy' : 'Sell',
                    price_per_token: toMoney(order.price),
                    token_amount: toMoney(order.amount),
                    web_amount: toMoney(new Decimal(order.price).mul(order.amount).toString()),
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
        scrollDown: function() {
            let parentDiv = this.$refs.history;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        profileToString: function(profile) {
            return profile.firstName + profile.lastName;
        },
    },
};
</script>

