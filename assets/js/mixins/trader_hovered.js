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
                delay: 0,
            };
        },
    },
    methods: {
        mouseoverHandler: function(fullOrdersList, basePrecision, ownerId, price) {
            if (fullOrdersList.lenght === 0 || !ownerId || !price) {
                return;
            }

            let moreCount = 0;
            let tradersArray = [];
            let tradersIdsArray = [];

            let ownerOrder = fullOrdersList.find((order) => parseInt(order.maker.id) === parseInt(ownerId));
            tradersArray.push(this.createTraderLinkFromOrder(ownerOrder));
            let orders = fullOrdersList.filter((order) => {
                let makerId = parseInt(order.maker.id);
                if (
                    tradersIdsArray.includes(makerId)
                    || parseInt(ownerId) === makerId
                    || price !== toMoney(order.price, basePrecision)
                ) {
                    return false;
                }

                tradersIdsArray.push(makerId);

                return true;
            });

            orders.sort((a, b) => a.timestamp - b.timestamp);
            orders.forEach((order) => tradersArray.push(this.createTraderLinkFromOrder(order)));

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
            if (!order || order.maker.profile === null || order.maker.profile.anonymous) {
                return 'Anonymous';
            }

            let traderFullName = order.maker.profile.firstName + ' ' + order.maker.profile.lastName;
            let link = this.$routing.generate('profile-view', {
                'pageUrl': order.maker.profile.page_url,
            });

            return '<a href="' + link + '">' + traderFullName + '</a>';
        },
    },
};
