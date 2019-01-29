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
        marketNames: String,
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
            return this.markets.length;
        },
        markets: function() {
            return JSON.parse(this.marketNames);
        },
        marketsHiddenNames: function() {
            return Object.keys(this.markets);
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
            const makretVolume = parseFloat(marketInfo.volume);

            const priceDiff = marketLastPrice - marketOpenPrice;
            const changePercentage = marketOpenPrice ? priceDiff * 100 / marketOpenPrice : 0;

            const marketCurrency = this.markets[marketName][1];
            const marketToken = this.markets[marketName][0];

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = this.getSanitizedMarket(
                    marketToken,
                    marketCurrency,
                    changePercentage,
                    marketLastPrice,
                    makretVolume
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
                        makretVolume
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
    },
};
</script>
