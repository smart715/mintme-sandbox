<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Buy Orders
                <span class="card-header-icon">
                    Total: xxxWEB
                    <font-awesome-icon
                        icon="question"
                        class="ml-1 mb-1 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <b-table ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="trader" slot-scope="row">
                           {{ row.value }}
                           <img
                               src="../../../img/avatar.png"
                               class="float-right"
                               alt="avatar">
                        </template>
                    </b-table>
                </div>
            </div>
        </div>
    </div>
</template>

>
<script>
import {toMoney} from '../../../js/utils';

export default {
    name: 'TokenTradeBuyOrders',
    props: {
        containerClass: String,
        buyOrders: String,
        tokenName: String,
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
        ordersList: function() {
            return this.orders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: order.total,
                    trader: order.maker.profile.firstName + ' ' + order.maker.profile.lastName,
                };
            });
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
