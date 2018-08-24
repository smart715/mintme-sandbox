<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Trade History
                <span class="card-header-icon">
                    <font-awesome-icon
                        icon="question"
                        class="ml-1 mb-1 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <b-table
                        :items="history"
                        :fields="fields">
                    </b-table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenTradeTradeHistory',
    props: {
        containerClass: String,
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
    created: function() {
        // TODO: This is a dummy simulator.
        for (let i = 0; i < 100; i++) {
            this.history.push({
                date_time: '12-12-1970',
                order_maker: 'John Doe',
                order_trader: 'John Doe',
                type: (i % 2 === 0) ? 'Buy' : 'Sell',
                price_per_token: Math.floor(Math.random() * 99) + 1000,
                token_amount: Math.floor(Math.random() * 99) + 10,
                web_amount: Math.floor(Math.random() * 99) + 10 + 'WEB',
                free: Math.floor(Math.random() * 99) + 10,
            });
        }
    },
};
</script>

