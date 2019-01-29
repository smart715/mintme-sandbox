<template>
    <div :class="containerClass">
        <div class="card">
            <confirm-modal
                    :visible="confirmModal"
                    @close="switchConfirmModal(false)"
                    @confirm="removeOrder"
            >
                <div>
                    Are you sure that you want to remove order
                    with amount {{ this.currentRow.amount }} and price {{ this.currentRow.price }}
                </div>
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
                                    v-if="row.item.cancel_order_url">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                </a>
                                <a :href="row.item.trader_url">
                                    <span v-if="!row.item.cancel_order_url">{{ row.value }}</span>
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
        buyOrders: String,
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
            actionUrl: '',
            orders: [],
            unfilteredOrders: [],
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
        this.groupOrders(JSON.parse(this.buyOrders));
        setInterval(() => {
            this.$axios.get(this.$routing.generate('pending_buy_orders', {
                tokenName: this.tokenName,
            })).then((result) => {
                this.groupOrders(result.data);
                this.$refs.table.refresh();
            });
        }, 10000);
    },
    methods: {
        removeOrderModal: function(row) {
            this.currentRow = row;
            this.actionUrl = row.cancel_order_url;
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            this.$axios.get(this.actionUrl).catch(() => {
                this.$toasted.show('Service unavailable, try again later');
            });
        },
        groupOrders: function(orders) {
            let grouped = {};
console.log(orders);
            orders.forEach( (item, i, arr) => {
                let price = item.price;

                if (arr[i-1] !== undefined && arr[i-1].price === arr[i].price) {
                    if (grouped[price] === undefined) {
                        grouped[price] = [];
                    }
                    grouped[price].push(item);
                } else {
                    grouped[price] = [];
                    grouped[price].push(item);
                }
            });
console.log(grouped);
            grouped.sort(function() {
                ;
            })
            this.unfilteredOrders = grouped;
            this.filterOrdersList(grouped);
        },
        filterOrdersList: function(orders) {
            let filtered = [];
            for ( let item in orders ) {
                if (orders.hasOwnProperty(item)) {
                    let amount = 0;
                    orders[item].forEach((order) => {
                        amount += parseFloat(order.amount);
                    });

                    orders[item][0].amount = amount;
                    filtered.push(orders[item][0]);
                }
            }
            this.orders = filtered;
        },
    },
};
</script>
