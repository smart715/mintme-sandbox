<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Buy Orders
                <span class="card-header-icon">
                    Total: {{ total }} WEB
                    <guide>
                        <template slot="header">
                            Buy Orders
                        </template>
                        <template slot="body">
                            List of all active buy orders for {{ tokenName }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="trader" slot-scope="row">
                            <a :href="row.item.trader_url">
                                <a
                                        v-if="row.item.cancel_order_url"
                                        :href="row.item.cancel_order_url">
                                    <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                </a>
                                <span v-else>
                                    {{ row.value }}
                                </span>
                                <img
                                        src="../../../img/avatar.png"
                                        class="float-right"
                                        alt="avatar">
                            </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <h4 class="text-center p-5">No order was added yet</h4>
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
    name: 'TokenTradeBuyOrders',
    props: {
        containerClass: String,
        buyOrders: String,
        tokenName: String,
        userId: Number,
    },
    components: {
        Guide,
    },
    data() {
        return {
            orders: [],
            fields: {
                price: {
                    label: 'Price',
                },
                amount: {
                    label: 'Amount',
                },
                sum_web: {
                    label: 'Sum WEB',
                },
                trader: {
                    label: 'Trader',
                },
            },
        };
    },
    computed: {
        total: function() {
            return toMoney(this.orders.reduce((sum, order) => parseFloat(order.price) + sum, 0));
        },
        ordersList: function() {
            return this.orders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: order.maker.profile.firstName + ' ' + order.maker.profile.lastName,
                    trader_url: this.$routing.generate('token_show', {
                        name: order.maker.profile.token.name,
                    }),
                    cancel_order_url: order.maker.id === this.userId
                        ? this.$routing.generate('order_cancel', {
                            market: order.market.hiddenName, orderid: order.id,
                        })
                        : null,
                };
            });
        },
        hasOrders: function() {
              return this.orders.length > 0;
        },
    },
    mounted: function() {
        this.orders = JSON.parse(this.buyOrders);
        setInterval(() => {
            this.$axios.get(this.$routing.generate('pending_buy_orders', {
                tokenName: this.tokenName,
            })).then((result) => {
                this.orders = result.data;
                this.$refs.table.refresh();
            });
        }, 10000);
    },
};
</script>
