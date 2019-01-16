<template>
    <div :class="containerClass">
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
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="order_maker" slot-scope="row">
                            <a :href="row.item.maker_url">
                                {{ row.value }}
                                <img
                                    src="../../../img/avatar.png"
                                    class="float-right"
                                    alt="avatar">
                            </a>
                        </template>
                        <template slot="order_trader" slot-scope="row">
                            <a :href="row.item.taker_url">
                                {{ row.value }}
                                <img
                                    src="../../../img/avatar.png"
                                    class="float-right"
                                    alt="avatar">
                            </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <h4 class="text-center p-5">No deal was made yet</h4>
                    </div>
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
        containerClass: String,
        ordersHistory: String,
        tokenName: String,
    },
    components: {
        Guide,
    },
    data() {
        return {
            history: [],
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
            return this.history.map((order) => {
                return {
                    date_time: new Date(order.timestamp * 1000).toDateString(),
                    order_maker: order.maker != null
                        ? order.maker.profile.firstName + order.maker.profile.lastName
                        : '',
                    order_trader: order.taker != null
                        ? order.taker.profile.firstName + order.taker.profile.lastName
                        : '',
                    maker_url: order.maker != null
                        ? this.$routing.generate('token_show', { name: order.maker.profile.token.name })
                        : '',
                    taker_url: order.taker != null
                        ? this.$routing.generate('token_show', { name: order.taker.profile.token.name })
                        : '',
                    type: (order.side === 0) ? 'Buy' : 'Sell',
                    price_per_token: toMoney(order.price),
                    token_amount: toMoney(order.amount),
                    web_amount: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                };
            });
        },
    },
    mounted: function() {
        this.history = JSON.parse(this.ordersHistory);
        setInterval(() => {
            this.$axios.get(this.$routing.generate('executed_orders', {
                tokenName: this.tokenName,
            })).then((result) => {
                this.history = result.data;
                this.$refs.table.refresh();
            });
        }, 10000);
    },
};
</script>

