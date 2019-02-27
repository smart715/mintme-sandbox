<template>
    <div>
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
                Sell Orders
                <template v-if="loaded">
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
                </template>
                <template v-else>
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </template>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <template v-if="loaded">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="trader" slot-scope="row">
                            <a :href="row.item.trader_url">
                                <span>{{ row.value }}</span>
                                <img
                                        src="../../../img/avatar.png"
                                        class="float-right"
                                        alt="avatar">
                            </a>
                            <a @click="removeOrderModal(row.item)"
                               v-if="row.item.trader_id">
                                <font-awesome-icon icon="times" class="text-danger c-pointer" />
                            </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <h4 class="text-center p-5">No order was added yet</h4>
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
import ConfirmModal from '../../modal/ConfirmModal';
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeSellOrders',
    props: {
        sellOrders: [Array, Object],
        filtered: [Array, Object],
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
            return toMoney(this.ordersList.reduce((sum, order) => parseFloat(order.amount) + sum, 0));
        },
        ordersList: function() {
            return this.filtered.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: this.truncateFullName(order),
                    trader_url: this.$routing.generate('token_show', {
                        name: order.maker.profile.token.name,
                    }),
                    trader_id: order.maker.id === this.userId ? this.userId : null,
                };
            });
        },
        hasOrders: function() {
            return this.sellOrders.length > 0;
        },
        loaded: function() {
            return this.sellOrders !== null;
        },
    },
    methods: {
        truncateFullName: function(order) {
            let first = order.maker.profile.firstName;
            let second = order.maker.profile.lastName;
            if ((first + second).length > 23) {
                return first.slice(0, 5) + '. ' + second.slice(0, 10) + '.';
            } else {
                return first + ' ' + second;
            }
        },
        removeOrderModal: function(row) {
            this.removeOrders = [];
            this.currentRow = row;
            this.clone(this.sellOrders).forEach( (order) => {
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
            let market = this.removeOrders[0].market.hiddenName;
            this.$axios.single.delete(
                this.$routing.generate('orders_cancel', {
                    'market': market,
                    'ids': JSON.stringify(this.removeOrders.map((order) => order.id)),
                })
            ).catch(() => {
                this.$toasted.show('Service unavailable, try again later');
            });
        },
        clone: function(orders) {
            return JSON.parse(JSON.stringify(orders));
        },
    },
};
</script>

