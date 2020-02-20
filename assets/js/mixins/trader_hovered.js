export default {
    data() {
        return {
            tooltipData: 'Loading...',
            side: '',
            isLoading: false,
        };
    },
    mounted: function() {
        this.$root.$on('bv::tooltip::hidden', () => {
            this.tooltipData = 'Loading...';
            this.isLoading = false;
        });
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
                show: 300,
                hide: 100,
                customClass: 'tooltip-traders',
            };
        },
        orderSide: function() {
            return this.side;
        },
    },
    methods: {
        mouseoverHandler: function(event) {
            if (this.isLoading) {
                return;
            }

            let target = event.target;

            if (target.hasAttribute('data-owner-id') && target.hasAttribute('data-price')) {
                this.orderTraderHovered({
                    side: this.orderSide,
                    ownerId: target.getAttribute('data-owner-id'),
                    price: target.getAttribute('data-price'),
                });
            }
        },
        orderTraderHovered: function(params) {
            let that = this;
            this.isLoading = true;

            this.$axios.retry.get(this.$routing.generate('traders_with_similar_orders', {
                base: this.$parent.market.base.symbol,
                quote: this.$parent.market.quote.symbol,
                params,
            })).then((response) => {
                let tradersData = response.data.tradersData || [];
                let moreCount = response.data.moreCount || 0;
                let tradersArray = [];
                let content = 'No data.';

                tradersData.forEach(function(traderData) {
                    if (traderData.anonymous) {
                        tradersArray.push('Anonymous');
                    } else {
                        let traderFullName = traderData.firstName + ' ' + traderData.lastName;
                        let link = that.$routing.generate('profile-view', {
                            'pageUrl': traderData.page_url,
                        });

                        tradersArray.push('<a href="' + link + '">' + traderFullName + '</a>');
                    }
                });

                if (tradersArray.length > 0) {
                    content = tradersArray.join(', ');
                }

                if (moreCount > 0) {
                    content += ' and ' + moreCount + ' more.';
                }

                this.tooltipData = content;
                this.isLoading = false;
            });
        },
    },
};
