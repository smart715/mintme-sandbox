import Decimal from 'decimal.js';

export default {
    methods: {
        handleOrderHighlights: function(orders, newOrders) {
            let delayOrdersUpdating = false;

            if (orders.length < newOrders.length) {
                let newOrder = newOrders.filter((order) => {
                    return !orders.some((order2) => order.price === order2.price);
                });

                if (newOrder.length) {
                    newOrder[0].highlightClass = 'success-highlight';
                }
            }

            if (orders.length === newOrders.length) {
                let newOrder = newOrders.filter((order) => {
                    return !orders.some((order2) => order.price === order2.price && order.amount === order2.amount);
                });

                if (newOrder.length) {
                    newOrder = newOrder[0];
                    let oldOrder = orders.find((order) => order.id === newOrder.id);
                    let newAmount = new Decimal(newOrder.amount);

                    if (newAmount.greaterThanOrEqualTo(oldOrder.amount)) {
                        newOrder.highlightClass = 'success-highlight';
                    } else {
                        oldOrder.highlightClass = 'error-highlight';
                        delayOrdersUpdating = true;
                    }
                }
            }

            if (orders.length > 0 && orders.length > newOrders.length) {
                let removedOrder = orders.filter((order) => {
                    return !newOrders.some((order2) => order.price === order2.price);
                });

                if (removedOrder.length) {
                    removedOrder[0].highlightClass = 'error-highlight';
                    delayOrdersUpdating = true;
                }
            }

            return delayOrdersUpdating;
        },
    },
};
