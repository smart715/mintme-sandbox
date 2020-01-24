import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';

export default {
    props: {
        basePrecision: Number,
        quotePrecision: Number,
        loggedIn: Boolean,
    },
    computed: {
        tooltipContent: function() {
            return this.tooltip;
        },
        ...mapGetters('makeOrder', [
            'getBaseBalance',
            'getQuoteBalance',
            'getUseSellMarketPrice',
            'getUseBuyMarketPrice',
        ]),
    },
    methods: {
        orderClicked: function(order) {
            if (!this.loggedIn) return;

            if (!this.getUseSellMarketPrice) {
                this.setSellPriceInput(toMoney(order.price, this.basePrecision));
            }

            if (!this.getUseBuyMarketPrice) {
                this.setBuyPriceInput(toMoney(order.price, this.basePrecision));
            }

            this.setSellAmountInput(
                parseFloat(order.amount) > parseFloat(this.getQuoteBalance)
                    ? toMoney(this.getQuoteBalance, this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );

            this.setBuyAmountInput(
                new Decimal(order.amount).mul(order.price).greaterThan(this.getBaseBalance)
                    ? toMoney(new Decimal(this.getBaseBalance).div(order.price).toString(), this.quotePrecision)
                    : toMoney(order.amount, this.quotePrecision)
            );
        },
        orderTraderHovered: function(data) {
            let that = this;

            this.$axios.retry.get(this.$routing.generate('traders_with_similar_orders', {
                base: this.$parent.market.base.symbol,
                quote: this.$parent.market.quote.symbol,
                data: data,
            })).then((response) => {
                let data = response.data;
                let linksArray = [];
                let content = 'No data';

                data.traidersData.forEach(function (item) {
                    let traiderFullName = item.firstName + ' ' + item.lastName;
                    let link = that.$routing.generate('profile-view', {
                        'pageUrl': item.page_url,
                    });
                    linksArray.push('<a href="' + link + '">' + traiderFullName + '</a>');
                });

                if (linksArray.length > 0) {
                    content = linksArray.join(', ');
                }

                if (data.moreCount > 0) {
                    content += ' and ' + data.moreCount + ' more';
                }

                this.tooltip = content;
            });
        },
        ...mapMutations('makeOrder', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setBuyPriceInput',
            'setBuyAmountInput',
        ]),
    },
};
