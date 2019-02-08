<template>
    <div :class="containerClass">
        <div class="card">
            <confirm-modal
                    :visible="confirmModal"
                    @close="switchConfirmModal(false)"
                    @confirm="removeOrder"
            >
                <ul>
                    You want to delete these orders:
                    <li v-for="order in this.removeOrders" :key="order.id">
                        Price {{ order.price }} Amount {{ order.amount }}
                    </li>
                    Are you sure?
                </ul>
            </confirm-modal>
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
                                <a
                                    @click="removeOrderModal(row.item)"
                                    v-if="row.item.trader_id">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                </a>
                                <a :href="row.item.trader_url">
                                    <span v-if="!row.item.trader_id">{{ row.value }}</span>
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
import ConfirmModal from '../../modal/ConfirmModal';
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeBuyOrders',
    props: {
        containerClass: String,
        buyOrders: Array,
        tokenName: String,
        userId: Number,
    },
    components: {
        Guide,
        ConfirmModal,
    },
    data() {
        return {
            confirmModal: false,
            currentRow: {},
            orders: [],
            removeOrders: [],
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
            return toMoney(this.ordersList.reduce((sum, order) => parseFloat(order.sum_web) + sum, 0));
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
                    trader_id: order.maker.id === this.userId ? this.userId : null,
                };
            });
        },
        hasOrders: function() {
              return this.buyOrders.length > 0;
        },
    },
    methods: {
        removeOrderModal: function(row) {
            this.removeOrders = [];
            this.currentRow = row;
            this.buyOrders.forEach( (order) => {
                if (toMoney(order.price) === row.price && order.maker.id === row.trader_id) {
                    order.price = toMoney(order.price);
                    order.amount = toMoney(order.amount);
                    this.removeOrders.push(order);
                }
            });
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            this.$axios.get(
                this.$routing.generate('orders_cancel', {
                    orders: JSON.stringify(
                        this.removeOrders.map((order) => {
                            return [order.market.hiddenName, order.id];
                        })
                    ),
                })
            ).catch(() => {
                this.$toasted.show('Service unavailable, try again later');
            });
        },
        groupByPrice: function(orders) {
            this.orders = [];
            let grouped = [];
            JSON.parse(JSON.stringify(orders)).forEach( (item) => {
                let price = toMoney(item.price);
                if (grouped[price] === undefined) {
                    grouped[price] = [];
                }
                grouped[price].push(item);
            });
            for (let orders in grouped) {
                if (grouped.hasOwnProperty(orders)) {
                    grouped[orders].forEach((order, i, arr) => {
                        if (arr[i-1] !== undefined && arr[i-1].maker.id === order.maker.id) {
                            order.amount = parseFloat(order.amount) + parseFloat(arr[i-1].amount);
                        }
                    });
                    grouped[orders].sort((first, second) => {
                        if (parseFloat(first.amount) > parseFloat(second.amount)) {
                            return -1;
                        }
                    });
                this.orders.push(grouped[orders][0]);
                }
            }
        },
    },
    watch: {
        buyOrders: function(val) {
            this.groupByPrice(val);
        },
    },
};
</script>
