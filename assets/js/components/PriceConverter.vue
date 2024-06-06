<template>
    <div v-if="requestingRates || rateLoaded">
        <div v-if="requestingRates || !rateLoaded" class="spinner-border spinner-border-sm">
            <span class="sr-only"> {{ $t('loading') }} </span>
        </div>
        <span v-else>
            {{ convertedAmountWithSymbol }}
        </span>
    </div>
</template>
<script>
import {mapMutations, mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {toMoney} from '../utils';
import {NotificationMixin} from '../mixins';

export default {
    name: 'PriceConverter',
    mixins: [NotificationMixin],
    props: {
        amount: [Number, String],
        from: String,
        to: String,
        subunit: Number,
        symbol: String,
        isToken: Boolean,
        hasParentheses: Boolean,
    },
    data() {
        return {
            markets: {},
            tokenMaxPrice: '0',
        };
    },
    methods: {
        ...mapMutations('rates', [
            'setRates',
            'setRequesting',
        ]),
        getExchangeRates: async function() {
            if (this.rateLoaded || this.requestingRates) {
                return;
            }

            this.requestingRates = true;

            try {
                const response = await this.$axios.retry.get(this.$routing.generate('exchange_rates'));
                this.setRates(response.data);
                this.getMarketsStatus();
            } catch (error) {
                this.$logger.error('Can\'t load conversion rates', error);
                this.notifyError(this.$t('toasted.error.external'));
            } finally {
                this.requestingRates = false;
            }
        },
        getMarketsStatus: async function() {
            if (!this.isToken) {
                return;
            }

            try {
                const response = await this.$axios.retry.get(this.$routing.generate(
                    'markets_status',
                    {quote: this.from}
                ));
                this.markets = response.data;
                this.getTokenMaxPriceInUSD();
            } catch (error) {
                this.notifyError(this.$t('toasted.error.try_reload'));
                this.$logger.error('Can not load market status', error);
            }
        },
        getTokenMaxPriceInUSD: function() {
            const tokenMaxPrice = Object.keys(this.markets).reduce(
                (tokenMaxPrice, market) =>
                    Decimal.max(
                        tokenMaxPrice,
                        Decimal.mul(
                            this.markets[market].last,
                            this.getRates[market].USD
                        )
                    ),
                new Decimal(0)
            );
            this.tokenMaxPrice = tokenMaxPrice.toString();
        },
    },
    computed: {
        ...mapGetters('rates', [
            'getRequesting',
            'getRates',
        ]),
        ...mapGetters('minOrder', {
            minOrder: 'getMinOrder',
        }),
        ...mapGetters('market', {
            market: 'getCurrentMarket',
        }),
        convertedAmount() {
            let amount = '0';

            if (!!parseFloat(this.amount) && this.rateLoaded) {
                amount = this.convertAmountWithRate;
            }

            return toMoney(amount, this.subunit);
        },
        minOrderInCrypto() {
            return Decimal
                .div(this.minOrder, this.rate)
                .toDP(this.baseSubunit, Decimal.ROUND_HALF_UP)
                .toString();
        },
        convertAmountWithRate() {
            return Decimal.mul(this.amount ?? '0', this.rate);
        },
        baseSubunit() {
            return this.market.base.subunit;
        },
        rate() {
            return !this.isToken ? this.getRates[this.from]?.[this.to] : this.tokenMaxPrice;
        },
        rateLoaded() {
            return this.rate !== undefined;
        },
        requestingRates: {
            get() {
                return this.getRequesting;
            },
            set(val) {
                this.setRequesting(val);
            },
        },
        convertedAmountWithSymbol() {
            return this.hasParentheses
                ? `(${this.symbol}${this.convertedAmount})`
                : `${this.symbol}${this.convertedAmount}`;
        },
    },
    mounted() {
        this.getExchangeRates();
    },
};
</script>
