<template>
    <div class="trading">
        <slot name="title"></slot>
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
    </div>
</template>

<script>
import WebSocketMixin from '../../js/mixins/websocket';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin],
    props: {
        tableContainerClass: String,
        tableClass: String,
        markets: String,
    },
    data() {
        return {
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
            return this.marketsInfo.length;
        },
        marketsInfo: function() {
            return JSON.parse(this.markets);
        },
        marketsHiddenNames: function() {
            return Object.keys(this.marketsInfo);
        },
        tokens: function() {
            let tokens = [];
            Object.keys(this.sanitizedMarkets).forEach((marketName) => {
                tokens.push(this.sanitizedMarkets[marketName]);
            });

            tokens.sort((first, second) => {
                let firstVolume = parseFloat(first.volume);
                let secondVolume = parseFloat(second.volume);

                if (firstVolume > secondVolume) {
                    return -1;
                }

                if (firstVolume < secondVolume) {
                    return 1;
                }

                return 0;
            });

            return this.sanitizedMarketsOnTop.concat(tokens);
        },
    },
    mounted() {
        let markets = {};
        for (let market in this.marketsInfo) {
            if (this.marketsInfo.hasOwnProperty(market)) {
                markets[market] = this.getSanitizedMarket(
                    this.marketsInfo[market].cryptoSymbol,
                    this.marketsInfo[market].tokenName,
                    this.getPercentage(
                        parseFloat(this.marketsInfo[market].last),
                        parseFloat(this.marketsInfo[market].open)
                    ),
                    parseFloat(this.marketsInfo[market].last),
                    parseFloat(this.marketsInfo[market].volume)
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

            const marketCurrency = this.marketsInfo[marketName].cryptoSymbol;
            const marketToken = this.marketsInfo[marketName].tokenName;

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
    },
};
</script>
