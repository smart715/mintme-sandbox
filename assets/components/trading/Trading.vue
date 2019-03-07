<template>
    <div class="trading">
        <slot name="title"></slot>
        <template v-if="loaded">
        <div class="table-responsive">
            <b-table
                :items="tokens"
                :fields="fields"
                :current-page="currentPage"
                :per-page="perPage">
            </b-table>
        </div>
        <div class="row justify-content-center">
            <b-pagination
                :total-rows="totalRows"
                :per-page="perPage"
                v-model="currentPage"
                class="my-0" />
        </div>
        </template>
        <template v-else>
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </template>
    </div>
</template>

<script>
import WebSocketMixin from '../../js/mixins/websocket';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin],
    data() {
        return {
            markets: null,
            currentPage: 1,
            perPage: 25,
            fields: {
                pair: {
                    label: 'Pair',
                    sortable: true,
                },
                change: {
                    label: 'Change',
                    sortable: true,
                },
                lastPrice: {
                    label: 'Last Price',
                    sortable: true,
                },
                volume: {
                    label: '24H Volume',
                    sortable: true,
                },
            },
            sanitizedMarkets: {},
            sanitizedMarketsOnTop: [],
            marketsOnTop: [
                {token: 'WEB', currency: 'BTC'},
            ],
        };
    },
    computed: {
        totalRows: function() {
            return this.markets.length;
        },
        marketsHiddenNames: function() {
            return Object.keys(this.markets);
        },
        tokens: function() {
            let tokens = [];
            Object.keys(this.sanitizedMarkets).forEach((marketName) => {
                tokens.push(this.sanitizedMarkets[marketName]);
            });

            tokens.sort((first, second) => parseFloat(first.volume) < parseFloat(second.volume));

            return this.sanitizedMarketsOnTop.concat(tokens);
        },
        loaded: function() {
            return this.markets !== null;
        },
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('markets_info'))
            .then((res) => {
                this.markets = res.data;
                this.updateDataWithMarkets();
            })
            .catch(() => this.$toasted.error('Can not update the markets data. Try again later.'));
    },
    methods: {
        sanitizeMarket: function(marketData) {
            if (!marketData.params) {
                return;
            }
            const marketName = marketData.params[0];
            const marketInfo = marketData.params[1];

            const marketOpenPrice = parseFloat(marketInfo.open);
            const marketLastPrice = parseFloat(marketInfo.last);
            const marketVolume = parseFloat(marketInfo.volume);
            const changePercentage = this.getPercentage(marketLastPrice, marketOpenPrice);

            const marketCurrency = this.markets[marketName].cryptoSymbol;
            const marketToken = this.markets[marketName].token.name;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = this.getSanitizedMarket(
                    marketToken,
                    marketCurrency,
                    changePercentage,
                    marketLastPrice,
                    marketVolume
                );
            } else {
                this.$set(
                    this.sanitizedMarkets,
                    marketName,
                    this.getSanitizedMarket(
                        marketCurrency,
                        marketToken,
                        changePercentage,
                        marketLastPrice,
                        marketVolume
                    )
                );
            }
        },
        getSanitizedMarket: function(currency, token, changePercentage, lastPrice, volume) {
            return {
                pair: `${currency}/${token}`,
                change: changePercentage.toFixed(2),
                lastPrice: lastPrice.toFixed(2),
                volume: volume.toFixed(2),
            };
        },
        getMarketOnTopIndex: function(currency, token) {
            let index = -1;
            this.marketsOnTop.forEach((market, key) => {
                if (token === market.token && currency === market.currency) {
                    index = key;
                }
            });
            return index;
        },
        getPercentage: function(lastPrice, openPrice) {
            return openPrice ? (lastPrice - openPrice) * 100 / openPrice : 0;
        },
        updateDataWithMarkets: function() {
            let markets = {};
            for (let market in this.markets) {
                if (this.markets.hasOwnProperty(market)) {
                    markets[market] = this.getSanitizedMarket(
                        this.markets[market].cryptoSymbol,
                        this.markets[market].token.name,
                        this.getPercentage(
                            parseFloat(this.markets[market].last),
                            parseFloat(this.markets[market].open)
                        ),
                        parseFloat(this.markets[market].last),
                        parseFloat(this.markets[market].volume)
                    );
                }
            }
            this.sanitizedMarkets = markets;

            if (this.websocketUrl) {
                this.addOnOpenHandler(() => {
                    const request = JSON.stringify({
                        method: 'state.subscribe',
                        params: this.marketsHiddenNames,
                        id: parseInt(Math.random().toString().replace('0.', '')),
                    });
                    this.sendMessage(request);
                });
                this.addMessageHandler((result) => {
                    if ('state.update' === result.method) {
                        this.sanitizeMarket(result);
                    }
                });
            }
        },
    },
};
</script>
