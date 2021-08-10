<template>
    <span></span>
</template>

<script>
import {toMoney} from '../../utils';
import {isRetryableError} from 'axios-retry';
import {mapGetters, mapMutations} from 'vuex';
import {
    LoggerMixin,
    NotificationMixin,
    WebSocketMixin,
} from '../../mixins';

export default {
    name: 'BalanceInit',
    mixins: [
        LoggerMixin,
        NotificationMixin,
        WebSocketMixin,
    ],
    props: {
        market: Object,
        loggedIn: Boolean,
        isOwner: Boolean,
        precision: Number,
    },
    computed: {
        ...mapGetters('tradeBalance', [
            'getBalances',
            'getQuoteBalance',
            'getBaseBalance',
        ]),
        balances: {
            get: function() {
                return this.getBalances;
            },
            set: function(val) {
                this.setBalances(val);
            },
        },
        quoteBalance: {
            get: function() {
                return this.getQuoteBalance;
            },
            set: function(val) {
                this.setQuoteBalance(val);
            },
        },
        baseBalance: {
            get() {
                return this.getBaseBalance;
            },
            set(val) {
                return this.setBaseBalance(val);
            },
        },
    },
    methods: {
        ...mapMutations('tradeBalance', [
            'setBalances',
            'setQuoteBalance',
            'setBaseBalance',
            'setHasQuoteRelation',
        ]),
        updateAssets: function() {
            if (!this.loggedIn) {
                this.balances = false;
                return;
            }

            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    this.balances = {...res.data.common, ...res.data.predefined};

                    if (!this.balances.hasOwnProperty(this.market.quote.symbol)) {
                        this.balances[this.market.quote.symbol] = {available: toMoney(0, this.precision)};
                    } else {
                        this.setHasQuoteRelation(true);
                    }

                    this.authorize()
                        .then(() => {
                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: [this.market.base.identifier, this.market.quote.identifier],
                                id: parseInt(Math.random().toString().replace('0.', '')),
                            }));
                        })
                        .catch((err) => {
                            this.notifyError(
                                this.$t('toasted.error.can_not_connect')
                            );
                            this.sendLogs('error', 'Can not connect to internal services', err);
                        });
                })
                .catch((err) => {
                    if (!isRetryableError(err)) {
                        this.balances = false;
                    } else {
                        this.notifyError(this.$t('toasted.error.can_not_load_balance'));
                        this.sendLogs('error', 'Can not load current balance', err);
                    }
                });
        },
        listenForAssetsUpdate: function() {
            this.addMessageHandler((response) => {
                if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.market.quote.identifier)) {
                    if (!this.isOwner || this.market.quote.identifier.slice(0, 3) !== 'TOK') {
                        this.quoteBalance = response.params[0][this.market.quote.identifier].available;
                        return;
                    }

                    this.$axios.retry.get(this.$routing.generate('lock-period', {name: this.market.quote.name}))
                        .then((res) => this.quoteBalance = res.data ?
                            new Decimal(response.params[0][this.market.quote.identifier].available).sub(
                                res.data.frozenAmountWithReceived
                            ) : response.params[0][this.market.quote.identifier].available
                        )
                        .catch((err) => {
                            this.sendLogs('error', 'Can not get immutable balance', err);
                        });
                }

                if ('asset.update' === response.method && response.params[0].hasOwnProperty(this.market.base.identifier)) {
                    this.baseBalance = response.params[0][this.market.base.identifier].available;
                }
            }, 'trade-sell-order-asset', 'BalanceInit');
        },
    },
    watch: {
        balances: function() {
            this.quoteBalance = this.balances && this.balances[this.market.quote.symbol]
                ? this.balances[this.market.quote.symbol].available
                : false;

            this.baseBalance = this.balances && this.balances[this.market.base.symbol]
                ? this.balances[this.market.base.symbol].available
                : false;
        },
    },
    mounted() {
        this.addOnOpenHandler(() => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.updateAssets();
        });

        this.listenForAssetsUpdate();
    },
};
</script>
