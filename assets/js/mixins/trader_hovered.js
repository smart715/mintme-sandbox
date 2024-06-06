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
                boundary: 'window',
                customClass: 'tooltip-traders tooltip-custom',
                delay: 0,
            };
        },
    },
    methods: {
        mouseoverHandler: function(fullOrdersList, basePrecision, price) {
            if (0 === fullOrdersList.lenght || !price) {
                return;
            }

            let moreCount = 0;
            let tradersArray = [];
            const tradersIdsArray = [];
            const orders = JSON.parse(JSON.stringify(fullOrdersList));

            orders.sort((a, b) => a.createdTimestamp - b.createdTimestamp);
            orders.forEach((order) => {
                const makerId = parseInt(order.maker.id);
                if (price === toMoney(order.price, basePrecision) && !tradersIdsArray.includes(makerId)) {
                    tradersIdsArray.push(makerId);
                    tradersArray.push(this.createTraderLinkFromOrder(order));
                }
            });

            if (5 < tradersArray.length) {
                moreCount = tradersArray.length - 5;
                tradersArray = tradersArray.slice(0, 5);
            }

            let content = tradersArray.join(', ');
            if (0 < moreCount) {
                content += ' and ' + moreCount + ' more.';
            }

            this.tooltipData = content;
        },
        createTraderLinkFromOrder: function(order) {
            if (!order) {
                return 'Anonymous';
            }

            const link = this.$routing.generate('profile-view', {
                nickname: order.maker.profile.nickname,
            });

            return '<a href="' + link + '">' + order.maker.profile.nickname + '</a>';
        },
    },
};
