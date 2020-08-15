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
            if (fullOrdersList.lenght === 0 || !price) {
                return;
            }

            let moreCount = 0;
            let tradersArray = [];
            let tradersIdsArray = [];
            let orders = JSON.parse(JSON.stringify(fullOrdersList));

            orders.sort((a, b) => a.createdTimestamp - b.createdTimestamp);
            orders.forEach((order) => {
                let makerId = parseInt(order.maker.id);
                if (price === toMoney(order.price, basePrecision) && !tradersIdsArray.includes(makerId)) {
                    tradersIdsArray.push(makerId);
                    tradersArray.push(this.createTraderLinkFromOrder(order));
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
            if (!order) {
                return 'Anonymous';
            }

            let link = this.$routing.generate('profile-view', {
                nickname: order.maker.profile.nickname,
            });

            return '<a href="' + link + '">' + order.maker.profile.nickname + '</a>';
        },
    },
};
