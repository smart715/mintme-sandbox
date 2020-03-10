import {toMoney} from '../utils';
import {WSAPI} from '../utils/constants';

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

            // Handle pending orders
            let executedFilteredOrders = this.executedOrders.filter(
                (order) => price === toMoney(order.price, basePrecision)
            );
            executedFilteredOrders.sort((a, b) => a.timestamp - b.timestamp);
            executedFilteredOrders.forEach((order) => {
                // TRADER IS MARKET MAKER. He creates buy order and wait for owner to make matching order.
                if (
                    order.side === WSAPI.order.type.SELL && side === WSAPI.order.type.SELL && order.taker
                    && !tradersIdsArray.includes(parseInt(order.taker.id))
                ) {
                    tradersIdsArray.push(parseInt(order.taker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.taker.profile));
                }

                // TRADER IS MARKET TAKER. He creates sell order which matches existing owner order.
                if (
                    order.side === WSAPI.order.type.SELL && side === WSAPI.order.type.BUY && order.maker
                    && !tradersIdsArray.includes(parseInt(order.maker.id))
                ) {
                    tradersIdsArray.push(parseInt(order.maker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.maker.profile));
                }

                // TRADER IS MARKET MAKER. He creates sell order and wait for owner to make matching order.
                if (
                    order.side === WSAPI.order.type.BUY && side === WSAPI.order.type.SELL && order.maker
                    && !tradersIdsArray.includes(parseInt(order.maker.id))
                ) {
                    tradersIdsArray.push(parseInt(order.maker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.maker.profile));
                }

                // TRADER IS MARKET TAKER. He creates buy order which matches existing owner order.
                if (
                    order.side === WSAPI.order.type.BUY && side === WSAPI.order.type.BUY && order.taker
                    && !tradersIdsArray.includes(parseInt(order.taker.id))
                ) {
                    tradersIdsArray.push(parseInt(order.taker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.taker.profile));
                }
            });

            // Handle pending orders
            let pendingFilteredOrders = orders.filter((order) => price === toMoney(order.price, basePrecision));
            pendingFilteredOrders.sort((a, b) => a.timestamp - b.timestamp);
            pendingFilteredOrders.forEach((order) => {
                if (order.maker && !tradersIdsArray.includes(parseInt(order.maker.id))) {
                    tradersIdsArray.push(parseInt(order.maker.id));
                    tradersArray.push(this.createTraderLinkFromProfile(order.maker.profile));
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
