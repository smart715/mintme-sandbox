<template>
    <span></span>
</template>

<script>
import {toMoney} from '../../utils';
import {isRetryableError} from 'axios-retry';
import {mapGetters, mapMutations} from 'vuex';
import {
    NotificationMixin,
    WebSocketMixin,
} from '../../mixins';
import Decimal from 'decimal.js';
import {debounce} from 'lodash';

export default {
    name: 'BalanceInit',
    mixins: [
        NotificationMixin,
        WebSocketMixin,
    ],
    props: {
        marketProp: {
            type: Object,
            default: null,
        },
        loggedIn: Boolean,
        isOwner: Boolean,
        precision: Number,
    },
    data() {
        return {
            market: null,
            setMessageBalanceDebounced: null,
        };
    },
    computed: {
        ...mapGetters('tradeBalance', [
            'getBalances',
            'getQuoteBalance',
            'getQuoteBonusBalance',
            'getBaseBalance',
        ]),
        ...mapGetters('market', [
            'getCurrentMarket',
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
        quoteBonusBalance: {
            get: function() {
                return this.getQuoteBonusBalance;
            },
            set: function(val) {
                this.setQuoteBonusBalance(val);
            },
        },
        quoteFullBalance: {
            get: function() {
                return this.quoteFullBalance;
            },
            set: function(val) {
                this.setQuoteFullBalance(val);
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
            'setBalance',
            'setQuoteBalance',
            'setQuoteBonusBalance',
            'setQuoteFullBalance',
            'setBaseBalance',
            'setHasQuoteRelation',
            'setServiceUnavailable',
        ]),
        updateAssets: function() {
            if (!this.loggedIn) {
                this.balances = false;

                this.$axios.retry.get(this.$routing.generate('tokens_ping'))
                    .catch(() => this.setServiceUnavailable(true));

                return;
            }

            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    const balances = {...res.data.common, ...res.data.predefined};

                    if (!balances.hasOwnProperty(this.market.quote.symbol)) {
                        balances[this.market.quote.symbol] = {
                            available: toMoney(0, this.precision),
                            subunit: this.market.quote.subunit,
                            identifier: this.market.quote.identifier,
                        };
                    } else {
                        this.setHasQuoteRelation(true);
                    }

                    this.balances = balances;

                    this.authorize()
                        .then(() => {
                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: Object.values(this.balances).map((balance) => balance.identifier),
                                id: parseInt(Math.random().toString().replace('0.', '')),
                            }));

                            this.listenForAssetsUpdate();
                        })
                        .catch((err) => {
                            this.notifyError(
                                this.$t('toasted.error.can_not_connect')
                            );
                            this.$logger.error('Can not connect to internal services', err);
                        });
                })
                .catch((err) => {
                    if (!isRetryableError(err)) {
                        this.balances = false;
                    } else {
                        this.notifyError(this.$t('toasted.error.can_not_load_balance'));
                        this.$logger.error('Can not load current balance', err);
                    }

                    this.setServiceUnavailable(true);
                });
        },
        setMessageBalance: async function(balanceToUpdate, responseBalance) {
            balanceToUpdate.available = await this.getAvailableBalance(balanceToUpdate, responseBalance);
            this.setBalance(balanceToUpdate);

            if (balanceToUpdate.identifier === this.market.quote.identifier) {
                this.quoteBalance = balanceToUpdate.available;
            }

            if (balanceToUpdate.identifier === this.market.base.identifier) {
                this.baseBalance = balanceToUpdate.available;
            }
        },
        listenForAssetsUpdate: function() {
            this.addMessageHandler(async (response) => {
                if ('asset.update' === response.method) {
                    // balance from websocket
                    let responseBalance = null;

                    // balance from storage
                    const balanceToUpdate = Object.values(this.balances).find((balance) => {
                        return responseBalance = response.params[0][balance.identifier];
                    });

                    if (!balanceToUpdate || !responseBalance) {
                        return;
                    }

                    if (this.isOwner && balanceToUpdate.identifier === this.market.quote.identifier) {
                        this.setMessageBalanceDebounced.cancel();
                        this.setMessageBalanceDebounced(balanceToUpdate, responseBalance);
                    } else {
                        await this.setMessageBalance(balanceToUpdate, responseBalance);
                    }
                }
            }, 'trade-sell-order-asset', 'BalanceInit');
        },
        fetchAvailableBalance: async function(name) {
            try {
                const tokenBalanceResponse = await this.$axios.retry.get(
                    this.$routing.generate('token_balance', {name})
                );

                if (tokenBalanceResponse.data) {
                    return tokenBalanceResponse.data.available;
                }

                return null;
            } catch (err) {
                this.$logger.error('Can not get available balance', err);

                return null;
            }
        },
        getAvailableBalance: async function(currentBalance, nextBalance) {
            let available = new Decimal(nextBalance.available);

            if (currentBalance.owner) {
                const tokenBalance = await this.fetchAvailableBalance(currentBalance.fullname);

                if (tokenBalance) {
                    available = new Decimal(tokenBalance);
                }
            }

            return available.toFixed(currentBalance.subunit);
        },
    },
    watch: {
        balances: function() {
            this.quoteBalance = this.balances && this.balances[this.market.quote.symbol]
                ? this.balances[this.market.quote.symbol].available
                : false;

            this.quoteBonusBalance = this.balances && this.balances[this.market.quote.symbol]
                ? this.balances[this.market.quote.symbol].bonus
                : false;

            this.quoteFullBalance = this.quoteBalance
                ? new Decimal(this.quoteBalance).plus(this.quoteBonusBalance || 0).toString()
                : false;

            this.baseBalance = this.balances && this.balances[this.market.base.symbol]
                ? this.balances[this.market.base.symbol].available
                : false;
        },
    },
    created() {
        this.setMessageBalanceDebounced = debounce(
            this.setMessageBalance,
            1000,
        );
    },
    mounted() {
        this.market = this.marketProp || this.getCurrentMarket;

        this.addOnOpenHandler(() => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));
        });

        this.updateAssets();
    },
};
</script>
