import Decimal from 'decimal.js';

export default {
    data() {
        return {
            tableData: [Array],
        };
    },
    computed: {
        ordersAmount: function() {
            return this.tableData?.length || 0;
        },
        totalAmount: function() {
            return this.tableData.reduce(
                (currentSum, order) => new Decimal(currentSum).add(order.amount).toNumber(),
                0,
            );
        },
        averageOrderAmount: function() {
            return 0 < this.ordersAmount
                ? new Decimal(this.totalAmount).dividedBy(this.ordersAmount).toNumber()
                : 0;
        },
        ordersWithFillWidth: function() {
            return this.tableData.map((order) => {
                order.fillWidth = this.calcFillWidth(order.amount);
                return order;
            });
        },
    },
    methods: {
        calcFillWidth: function(orderAmount) {
            const fillWidth = 0 < this.averageOrderAmount
                ? new Decimal(orderAmount)
                    .dividedBy(this.averageOrderAmount)
                    .times(100)
                    .round()
                    .toNumber()
                : 0;

            return Math.min(fillWidth, 100);
        },
        orderFillingStyle: function(fillWidth) {
            return `--orderFillWidth: ${fillWidth}%;`;
        },
    },
};
