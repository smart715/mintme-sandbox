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
                Sell Orders
                <span class="card-header-icon">
                    Total: {{ total }} {{ tokenName }}
                    <guide>
                        <template slot="header">
                            Sell Orders
                        </template>
                        <template slot="body">
                            List of all active sell orders for {{ tokenName }}.
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
    name: 'TokenTradeSellOrders',
    props: {
        containerClass: String,
        sellOrders: String,
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
            return toMoney(this.orders.reduce((sum, order) => parseFloat(order.amount) + sum, 0));
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
        this.orders = JSON.parse(this.sellOrders);
        setInterval(() => {
            this.$axios.get(this.$routing.generate('pending_sell_orders', {
                tokenName: this.tokenName,
            })).then((result) => {
                this.orders = result.data;
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
    },
};
</script>

