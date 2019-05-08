<template>
    <div class="trading">
        <slot name="title"></slot>
        <template v-if="loaded">
            <div class="table-responsive text-nowrap">
                <b-table
                    :items="tokens"
                    :fields="fields">
                    <template slot="pair" slot-scope="row">
                        <a class="d-block text-truncate truncate-responsive text-white"
                            v-b-tooltip:title="row.value"
                            :href="row.item.tokenUrl">
                            {{ row.value }}
                        </a>
                    </template>
                </b-table>
            </div>
            <div class="row justify-content-center">
                <b-pagination
                    @change="updateData"
                    :total-rows="totalRows"
                    :per-page="perPage"
                    v-model="currentPage"
                    class="my-0" />
            </div>
        </template>
        <template v-else>
            <div class="p-5 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
        </template>
    </div>
</template>

<script>
import {FiltersMixin, WebSocketMixin} from '../../mixins';
import {toMoney} from '../../utils';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin, FiltersMixin],
    props: {
        page: Number,
    },
    data() {
        return {
            markets: null,
            currentPage: this.page,
            perPage: 25,
            totalRows: 25,
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
                {currency: 'BTC', token: 'WEB'},
            ],
        };
    },
    computed: {
        marketsHiddenNames: function() {
            return Object.keys(this.markets);
        },
        tokens: function() {
            let tokens = [];
            Object.keys(this.sanitizedMarkets).forEach((marketName) => {
                tokens.push(this.sanitizedMarkets[marketName]);
            });

            tokens.sort((first, second) => parseFloat(second.volume) - parseFloat(first.volume));

            return 1 === this.currentPage
                ? this.sanitizedMarketsOnTop.concat(tokens)
                : tokens;
        },
        loaded: function() {
            return this.markets !== null;
        },
    },
    mounted: function() {
        this.updateData(1);
    },
    methods: {
        updateData: function(page) {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('markets_info', {page}))
                    .then((res) => {
                        this.currentPage = page;
                        this.markets = res.data.markets;
                        this.perPage = res.data.limit;
                        this.totalRows = res.data.rows;
                        this.updateDataWithMarkets();

                        resolve();
                    })
                    .catch((err) => {
                        this.$toasted.error('Can not update the markets data. Try again later.');
                        reject(err);
                    });
            });
        },
        sanitizeMarket: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketName = marketData.params[0];
            const marketInfo = marketData.params[1];

            const marketLastPrice = parseFloat(marketInfo.last);
            const changePercentage = this.getPercentage(marketLastPrice, parseFloat(marketInfo.open));

            const marketCurrency = this.markets[marketName].crypto.symbol;
            const marketToken = this.markets[marketName].quote.symbol;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            const market = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                changePercentage,
                marketLastPrice,
                parseFloat(marketInfo.volume)
            );

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = market;
            } else {
                delete this.sanitizedMarkets[marketName];
                this.sanitizedMarkets[marketName] = market;
            }
        },
        getSanitizedMarket: function(currency, token, changePercentage, lastPrice, volume) {
            let hiddenName = this.findHiddenName(token);

            return {
                pair: `${currency}/${token}`,
                change: changePercentage.toFixed(2) + '%',
                lastPrice: toMoney(lastPrice) + ' ' + currency,
                volume: volume.toFixed(2),
                tokenUrl: hiddenName && hiddenName.indexOf('TOK') !== -1 ?
                    this.$routing.generate('token_show', {name: token}) :
                    this.$routing.generate('coin', {base: currency, quote: token}),
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
            this.sanitizedMarkets = {};
            for (let market in this.markets) {
                if (this.markets.hasOwnProperty(market)) {
                    const cryptoSymbol = this.markets[market].crypto.symbol;
                    const tokenName = this.markets[market].quote.symbol;
                    const marketOnTopIndex = this.getMarketOnTopIndex(cryptoSymbol, tokenName);
                    const sanitizedMarket = this.getSanitizedMarket(
                        cryptoSymbol,
                        tokenName,
                        this.getPercentage(
                            parseFloat(this.markets[market].lastPrice),
                            parseFloat(this.markets[market].openPrice)
                        ),
                        parseFloat(this.markets[market].lastPrice),
                        parseFloat(this.markets[market].dayVolume)
                    );

                    if (marketOnTopIndex > -1) {
                        this.sanitizedMarketsOnTop[marketOnTopIndex] = sanitizedMarket;
                    } else {
                        this.sanitizedMarkets[market] = sanitizedMarket;
                    }
                }
            }

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
        findHiddenName: function(tokenOrCrypto) {
            let result = null;

            for (let key in this.markets) {
                if (this.markets.hasOwnProperty(key) && this.markets[key].quote !== null) {
                    if (this.markets[key].quote.symbol === tokenOrCrypto) {
                        result = key;
                        break;
                    }
                }
            }

            return result;
        },
    },
};
</script>
