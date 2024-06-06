<template>
    <div v-if="isLoaded" class="d-flex align-items-center">
        <div>
            <div
                v-if="showLastPriceInUsdAbbr"
                v-b-tooltip="modalTooltip"
                class="font-size-3 line-height-1"
            >
                {{ usdSign }}{{ getPriceAbbreviation(lastPriceInUsd) }}
            </div>
            <div
                v-else
                class="font-size-3 line-height-1"
            >
                {{ usdSign }}{{ lastPriceInUsd }}
            </div>
            <div class="line-height-1 pt-1">
                {{ lastPrice }} {{ marketsHighestPrice.symbol | rebranding }}
            </div>
        </div>
        <div
            v-if="priceChange !== 0"
            class="price-change font-size-3 line-height-1 p-2 ml-2"
            :class="priceChangeClass"
        >
            {{ priceChange >= 0 ? '+' : ''}}{{ priceChange }}%
        </div>
    </div>
    <div v-else-if="!serviceUnavailable" class="d-flex aling-items-center justify-content-center">
        <div class="spinner-border spinner-border-sm" role="status"></div>
    </div>
</template>

<script>
import Decimal from 'decimal.js';
import {WebSocketMixin, RebrandingFilterMixin} from '../../mixins';
import {toMoney, getPriceAbbreviation} from '../../utils';
import {MINTME, usdSign, GENERAL, usdCustomPricePrecision} from '../../utils/constants';
import {VBTooltip} from 'bootstrap-vue';

export default {
    name: 'TokenExchangePrice',
    mixins: [
        WebSocketMixin,
        RebrandingFilterMixin,
    ],
    props: {
        market: Object,
        marketsHighestPrice: Object,
        serviceUnavailable: Boolean,
        isToken: Boolean,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    data() {
        return {
            exchangeRates: null,
            priceChange: 0,
            lastPriceInUsd: '0',
            usdSign: usdSign,
            mintmeSymbol: MINTME.symbol,
            getPriceAbbreviation,
        };
    },
    created() {
        this.fetchRates();
    },
    computed: {
        isLoaded: function() {
            return this.market && this.exchangeRates;
        },
        priceChangeClass: function() {
            return 0 <= this.priceChange ? 'positive' : 'negative';
        },
        highestPriceSubunit: function() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.marketsHighestPrice.subunit;
        },
        lastPrice: function() {
            return toMoney(this.marketsHighestPrice.value, this.highestPriceSubunit);
        },
        showLastPriceInUsdAbbr: function() {
            return this.highestPriceSubunit > GENERAL.precision;
        },
        modalTooltip: function() {
            return {
                title: this.lastPriceInUsd,
                boundary: 'viewport',
            };
        },
    },
    methods: {
        fetchRates: function() {
            this.$axios.retry.get(this.$routing.generate('exchange_rates'))
                .then((res) => {
                    this.exchangeRates = res.data;
                    this.calculatePrice();
                })
                .catch((err) => {
                    this.$logger.error('Service unavailable. Can not load convert data now', err);
                });
        },
        calculatePrice: function() {
            if (!this.isLoaded || !this.exchangeRates[this.marketsHighestPrice.symbol]) {
                return;
            }

            const marketLastPrice = new Decimal(this.marketsHighestPrice.value);
            const marketOpenPrice = new Decimal(this.marketsHighestPrice.open);
            const priceDiff = marketLastPrice.minus(marketOpenPrice);

            this.priceChange = !marketOpenPrice.isZero() && !marketLastPrice.isZero()
                ? priceDiff.mul(100).dividedBy(marketOpenPrice).toDP(2).toNumber()
                : !marketLastPrice.isZero() ? 100 : 0;

            this.lastPriceInUsd = this.isToken && this.market.quote.priceDecimals
                ? toMoney(this.marketsHighestPrice.valueInUsd, usdCustomPricePrecision)
                : toMoney(this.marketsHighestPrice.valueInUsd);
        },
    },
};
</script>
