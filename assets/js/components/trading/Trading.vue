<template>
    <div class="trading">
        <div slot="title" class="card-title font-weight-bold pl-3 pt-3 pb-1">
            <span class="float-left">Top {{ marketsCount }} tokens | Market Cap: {{ globalMarketCap }} BTC</span>
            <label v-if="userId" class="custom-control custom-checkbox float-right pr-3">
                <input
                    type="checkbox"
                    class="custom-control-input"
                    id="checkbox"
                    v-model="userTokensEnabled"
                    @change="updateData(1)"
                    :disabled="loading">
                <label for="checkbox" class="custom-control-label">Tokens I own</label>
            </label>

        </div>
        <template v-if="loaded">
            <div class="trading-table table-responsive text-nowrap">
                <b-table
                    :items="tokens"
                    :fields="fields"
                    :sort-by="fields.lastPrice.key"
                    :sort-desc="true"
                    :sort-compare="sortCompare">
                    <template slot="HEAD_volume" slot-scope="data">
                        {{ data.label }}
                        <guide>
                            <template slot="header">
                                24h volume
                            </template>
                            <template slot="body">
                                The amount of crypto that has been traded in the last 24 hours.
                            </template>
                        </guide>
                    </template>
                    <template slot="HEAD_marketCap" slot-scope="data">
                        {{ data.label }}
                        <guide>
                            <template slot="header">
                                Market Cap
                            </template>
                            <template slot=body>
                                Market cap of each token based on 10 million tokens created. To make it simple to compare them between each other, we consider not yet released tokens as already created.
                            </template>
                        </guide>
                    </template>
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
import Guide from '../Guide';
import {FiltersMixin, WebSocketMixin} from '../../mixins';
import {toMoney, formatMoney} from '../../utils';
import Decimal from 'decimal.js/decimal.js';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin, FiltersMixin],
    props: {
        page: Number,
        marketsCount: Number,
        userId: Number,
    },
    components: {
        Guide,
    },
    data() {
        return {
            markets: null,
            currentPage: this.page,
            perPage: 25,
            totalRows: 25,
            loading: false,
            userTokensEnabled: false,
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
                    key: 'lastPrice',
                    sortable: true,
                    formatter: formatMoney,
                },
                volume: {
                    label: '24H Volume',
                    sortable: true,
                    formatter: formatMoney,
                },
                marketCap: {
                    label: 'Market Cap',
                    sortable: true,
                    formatter: formatMoney,
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
            if (undefined === typeof this.markets) {
                return {};
            }

            return Object.keys(this.markets);
        },
        tokens: function() {
            let tokens = [];
            Object.keys(this.sanitizedMarkets).forEach((marketName) => {
                tokens.push(this.sanitizedMarkets[marketName]);
            });
            tokens.sort((first, second) => parseFloat(second.deal) - parseFloat(first.deal));

            return this.sanitizedMarketsOnTop.concat(tokens);
        },
        loaded: function() {
            return this.markets !== null && !this.loading;
        },
        globalMarketCap: function() {
            if (!Object.keys(this.sanitizedMarkets).length) {
                return 0;
            }

            let sanitizedMarketsArray = Object.keys(this.sanitizedMarkets);

            let globalMarketCap = sanitizedMarketsArray.reduce((acc, curr) => {
                return Decimal.mul(this.markets[curr].lastPrice, 1e7).plus(acc);
            }, 0);

            globalMarketCap = globalMarketCap.times(this.markets['WEBBTC'].lastPrice);

            return toMoney(globalMarketCap, 8);
        },
    },
    mounted: function() {
        this.updateData(this.currentPage).then(() => {
            this.addMessageHandler((result) => {
                if ('state.update' === result.method) {
                    this.sanitizeMarket(result);
                }
            });
        });
    },
    methods: {
        sortCompare: function(a, b, key) {
            let pair = false;

            if (typeof a[key] === 'number' && typeof b[key] === 'number') {
                // If both compared fields are native numbers
                return a[key] < b[key] ? -1 : a[key] > b[key] ? 1 : 0;
            } else {
                this.marketsOnTop.forEach((market)=> {
                    if (b.pair === market.currency + '/' + market.token ||
                        a.pair === market.currency + '/' + market.token) {
                        pair = true;
                    }
                });
                return pair ? 0 : a[key].localeCompare(b[key], undefined, {
                    numeric: true,
                });
            }
        },
        updateData: function(page) {
            return new Promise((resolve, reject) => {
                let params = {page};

                if (this.userTokensEnabled) {
                    params.user = this.userTokensEnabled | 0;
                }

                this.loading = true;
                this.$axios.retry.get(this.$routing.generate('markets_info', params))
                    .then((res) => {
                        if (null !== this.markets) {
                            this.addOnOpenHandler(() => {
                                const request = JSON.stringify({
                                    method: 'state.unsubscribe',
                                    params: [],
                                    id: parseInt(Math.random().toString().replace('0.', '')),
                                });
                                this.sendMessage(request);
                            });
                        }
                        this.currentPage = page;
                        this.markets = res.data.markets;
                        this.perPage = res.data.limit;
                        this.totalRows = res.data.rows;
                        this.updateDataWithMarkets();
                        this.loading = false;

                        if (window.history.replaceState) {
                            // prevents browser from storing history with each change:
                            window.history.replaceState(
                                {page}, document.title, this.$routing.generate('trading', {page})
                            );
                        }

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
            const marketPrecision = this.markets[marketName].crypto.subunit;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            const market = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                changePercentage,
                marketLastPrice,
                parseFloat(marketInfo.deal),
                marketPrecision
            );

            if (marketOnTopIndex > -1) {
                Vue.set(this.sanitizedMarketsOnTop, marketOnTopIndex, market);
            } else {
                Vue.set(this.sanitizedMarkets, marketName, market);
            }

            this.markets[marketName] = {
                ...this.markets[marketName],
                openPrice: marketInfo.open,
                lastPrice: marketInfo.last,
                dayVolume: marketInfo.deal,
            };
        },
        getSanitizedMarket: function(currency, token, changePercentage, lastPrice, volume, subunit) {
            let hiddenName = this.findHiddenName(token);

            return {
                pair: `${currency}/${token}`,
                change: changePercentage.toFixed(2) + '%',
                lastPrice: toMoney(lastPrice, subunit) + ' ' + currency,
                volume: toMoney(volume, subunit) + ' ' + currency,
                tokenUrl: hiddenName && hiddenName.indexOf('TOK') !== -1 ?
                    this.$routing.generate('token_show', {name: token}) :
                    this.$routing.generate('coin', {base: currency, quote: token}),
                marketCap: toMoney(Decimal.mul(lastPrice, 1e7), subunit) + ' ' + currency,
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
                        parseFloat(this.markets[market].dayVolume),
                        this.markets[market].crypto.subunit
                    );

                    if (marketOnTopIndex > -1) {
                        Vue.set(this.sanitizedMarketsOnTop, marketOnTopIndex, sanitizedMarket);
                    } else {
                        Vue.set(this.sanitizedMarkets, market, sanitizedMarket);
                    }
                }
            }

            this.addOnOpenHandler(() => {
                const request = JSON.stringify({
                    method: 'state.subscribe',
                    params: this.marketsHiddenNames,
                    id: parseInt(Math.random().toString().replace('0.', '')),
                });
                this.sendMessage(request);
            });
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
