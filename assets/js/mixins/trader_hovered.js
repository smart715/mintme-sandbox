import {toMoney} from '../utils';

export default {
    data() {
        return {
            tooltipData: 'Loading...',
        };
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
            };
        },
    },
    methods: {
        mouseoverHandler: function(ownerId, price) {
            if (!ownerId || !price) {
                return;
            }

            let moreCount = 0;
            let tradersArray = [];
            let tradersIdsArray = [];
            let basePrecision = this.basePrecision;

            let hoveredOrder = this.fullOrdersList.find((order) => parseInt(order.maker.id) === parseInt(ownerId));
            tradersArray.push(this.createTraderLinkFromOrder(hoveredOrder));
            let orders = this.fullOrdersList.filter(function(order) {
                return price === toMoney(order.price, basePrecision) && parseInt(ownerId) !== parseInt(order.maker.id);
            });

            let self = this;
            orders.sort((a, b) => a.timestamp - b.timestamp);
            orders.forEach(function(order) {
                // Avoid duplicates
                if (tradersIdsArray.includes(order.maker.id)) {
                    return;
                }

                tradersIdsArray.push(order.maker.id);

                if (order.maker.profile.anonymous) {
                    tradersArray.push('Anonymous');
                } else {
                    tradersArray.push(self.createTraderLinkFromOrder(order));
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
        createTraderLinkFromOrder: function(order) {
            let traderFullName = order.maker.profile.firstName + ' ' + order.maker.profile.lastName;
            let link = this.$routing.generate('profile-view', {
                'pageUrl': order.maker.profile.page_url,
            });

            return '<a href="' + link + '">' + traderFullName + '</a>';
        },
    },
};
