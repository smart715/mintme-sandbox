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
<<<<<<< HEAD:assets/components/token/TokenInvestSellOrders.vue
    created: function() {
        // TODO: This is a dummy simulator.
        for (let i = 0; i < 100; i++) {
            this.orders.push({
                price: Math.floor(Math.random() * 99) + 1000,
                amount: Math.floor(Math.random() * 99) + 10,
                sum_web: Math.floor(Math.random() * 99) + 10 + 'WEB',
                trader: 'John Doe',
            });
        }
    },
=======
>>>>>>> cd3f2a2bbe81b0335ffddce2ba830f52f4b74726:assets/components/token/TokenTradeSellOrders.vue
};
</script>

