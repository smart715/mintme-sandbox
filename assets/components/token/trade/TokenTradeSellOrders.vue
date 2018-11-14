<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Sell Orders
                <span class="card-header-icon">
                    Total: xxxToken
                    <font-awesome-icon
                        icon="question"
                        class="ml-1 mb-1 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <b-table
                        :items="orders"
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

<script>
export default {
    name: 'TokenTradeSellOrders',
    props: {
        containerClass: String,
        sellOrders: String,
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
    created: function() {
        let orders = JSON.parse(this.sellOrders);
        orders.forEach( (order) => {
            this.orders.push({
                price: order.price,
                amount: order.amount,
                sum_web: order.total,
                trader: 'John Doe',
            });
        });
    },
};
</script>

