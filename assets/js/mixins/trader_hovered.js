import {toMoney} from '../utils';

export default {
    data() {
        return {
            tooltipData: 'Loading...',
            executedOrders: [],
        };
    },
    mounted: function() {
        this.$root.$on('trade-history-orders', (data) => this.executedOrders = data);
    },
    computed: {
        tooltipContent: function() {
            return this.tooltipData;
        },
        popoverConfig: function() {
            return {
                title: this.tooltipContent,
                html: true,
                boundary: 'viewport',
                customClass: 'tooltip-traders',
                delay: 0,
            };
        },
    },
    methods: {
        mouseoverHandler: function(pendingOrders, basePrecision, price, side) {
            if ((pendingOrders.length === 0 && this.executedOrders.length === 0) || !price) {
                return;
            }

            let moreCount = 0;
            let tradersArray = [];
            let tradersIdsArray = [];
            let orders = JSON.parse(JSON.stringify(pendingOrders));

            // Filter orders by price
            let pendingFilteredOrders = orders.filter((order) => price === toMoney(order.price, basePrecision));
            let executedFilteredOrders = this.executedOrders.filter(
                (order) => price === toMoney(order.price, basePrecision) && side === order.side
            );

            // Concat executed and pending orders
            let filteredOrders = pendingFilteredOrders.concat(executedFilteredOrders);
            filteredOrders.sort((a, b) => a.timestamp - b.timestamp);

            filteredOrders.forEach((order) => {
                if (order.maker && !tradersIdsArray.includes(parseInt(order.maker.id))) {
                    tradersIdsArray.push(parseInt(order.maker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.maker.profile));

                    if (order.taker && !tradersIdsArray.includes(parseInt(order.taker.id))) {
                        tradersIdsArray.push(parseInt(order.taker.id));
                        tradersArray.push(this.createTraderLinkFromProfile(order.taker.profile));
                    }
                }
            });

            if (tradersArray.length > 5) {
                moreCount = tradersArray.length - 5;
                tradersArray = tradersArray.slice(0, 5);
            }

            let content = tradersArray.join(', ');
            if (moreCount > 0) {
                content += ' and ' + moreCount + ' more.';
            }

            this.tooltipData = content;
        },
        createTraderLinkFromProfile: function(profile) {
            if (profile === null || profile.anonymous) {
                return 'Anonymous';
            }

            let link = this.$routing.generate('profile-view', {
                'pageUrl': profile.page_url,
            });

            return '<a href="' + link + '">' + profile.firstName + ' ' + profile.lastName + '</a>';
        },
    },
};
