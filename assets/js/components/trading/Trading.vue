<template>
    <div class="trading">
        <div slot="title" class="card-title font-weight-bold pl-3 pt-3 pb-1">
            <span class="float-left">Top {{ tokensCount }} tokens | Market Cap: {{ globalMarketCap | formatMoney }}</span>
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
                    :fields="fieldsArray"
                    sort-by="lastPrice"
                    :sort-desc="true"
                    :sort-compare="sortCompare">
                    <template v-slot:[`head(${fields.volume.key})`]="data">
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
                    <template v-slot:[`head(${fields.monthVolume.key})`]="data">
                        {{ data.label }}
                        <guide>
                            <template slot="header">
                                30d volume
                            </template>
                            <template slot="body">
                                The amount of crypto that has been traded in the last 30 days.
                            </template>
                        </guide>
                    </template>
                    <template v-slot=[`head(${fields.marketCap.key})`]="data">
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
                    <template v-slot:cell(pair)="row">
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
import {FiltersMixin, WebSocketMixin, MoneyFilterMixin} from '../../mixins';
import {toMoney, formatMoney} from '../../utils';
import {USD} from '../../utils/constants.js';
import Decimal from 'decimal.js/decimal.js';
import capitalize from 'lodash/capitalize';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin, FiltersMixin, MoneyFilterMixin],
    props: {
        page: Number,
        tokensCount: Number,
        userId: Number,
        cryptos: Object,
        coinbaseUrl: String,
        showUsd: Boolean,
        webchainSupplyUrl,
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
            sanitizedMarkets: {},
            sanitizedMarketsOnTop: [],
            marketsOnTop: [
                {currency: 'BTC', token: 'WEB'},
            ],
            klineQueriesIdsTokensMap: new Map(),
            conversionRates: {},
            globalMarketCaps: {
                BTC: 0,
                USD: 0,
            },
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
        fields: function() {
            return {
                pair: {
                    key: 'pair',
                    label: 'Pair',
                    sortable: true,
                },
                change: {
                    key: 'change',
                    label: 'Change',
                    sortable: true,
                },
                lastPrice: {
                    label: 'Last Price',
                    key: 'lastPrice' + ( this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: formatMoney,
                },
                volume: {
                    label: '24H Volume',
                    key: 'volume' + ( this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: formatMoney,
                },
                monthVolume: {
                    label: '30d Volume',
                    key: 'monthVolume' + ( this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: formatMoney,
                },
                marketCap: {
                    label: 'Market Cap',
                    key: 'marketCap' + ( this.showUsd ? 'USD' : ''),
                    sortable: true,
                    formatter: formatMoney,
                },
            };
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        globalMarketCap: function() {
            if (this.showUsd) {
                return this.globalMarketCaps['USD'] + ' USD';
            }
            return this.globalMarketCaps['BTC'] + ' BTC';
        },
    },
    mounted: function() {
        let updateDataPromise = this.updateData(this.currentPage);
        let conversionRatesPromise = this.fetchConversionRates();
        this.fetchGlobalMarketCap();

        Promise.all([updateDataPromise, conversionRatesPromise])
            .then(() => {
                this.updateDataWithMarkets();
                this.loading = false;

                this.addMessageHandler((result) => {
                    if ('state.update' === result.method) {
                        this.sanitizeMarket(result);
                        this.requestKline(result.params[0]);
                    } else if (Array.from(this.klineQueriesIdsTokensMap.keys()).indexOf(result.id) != -1) {
                        this.updateMonthVolume(result.id, result.result);
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

                        if (window.history.replaceState) {
                            // prevents browser from storing history with each change:
                            window.history.replaceState(
                                {page}, document.title, this.$routing.generate('trading', {page})
                            );
                        }

                        this.fetchWEBsupply().then(this.updateWEBBTCMarket.bind(this));

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

            const marketCurrency = this.markets[marketName].base.symbol;
            const marketToken = this.markets[marketName].quote.symbol;
            const marketPrecision = this.markets[marketName].base.subunit;
            const supply = this.markets[marketName].supply;
            const monthVolume = this.markets[marketName].monthVolume;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            const market = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                changePercentage,
                marketLastPrice,
                parseFloat(marketInfo.deal),
                monthVolume,
                supply,
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
        getSanitizedMarket: function(currency, token, changePercentage, lastPrice, volume, monthVolume, supply, subunit) {
            let hiddenName = this.findHiddenName(token);

            let marketCap = Decimal.mul(lastPrice, supply);

            return {
                pair: `${currency}/${token}`,
                change: changePercentage.toFixed(2) + '%',
                lastPrice: toMoney(lastPrice, subunit) + ' ' + currency,
                volume: toMoney(volume, subunit) + ' ' + currency,
                monthVolume: toMoney(monthVolume, subunit) + ' ' + currency,
                tokenUrl: hiddenName && hiddenName.indexOf('TOK') !== -1 ?
                    this.$routing.generate('token_show', {name: token}) :
                    this.$routing.generate('coin', {base: currency, quote: token}),
                lastPriceUSD: this.toUSD(lastPrice, currency),
                volumeUSD: this.toUSD(volume, currency),
                monthVolumeUSD: this.toUSD(monthVolume, currency),
                marketCap: toMoney(marketCap, subunit) + ' ' + currency,
                marketCapUSD: this.toUSD(marketCap, currency),
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
                    this.markets[market].supply = 1e7;
                    const cryptoSymbol = this.markets[market].base.symbol;
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
                        parseFloat(this.markets[market].monthVolume),
                        this.markets[market].supply,
                        this.markets[market].base.subunit
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
        updateMonthVolume: function(requestId, kline) {
            const marketName = this.klineQueriesIdsTokensMap.get(requestId);
            const marketCurrency = this.markets[marketName].base.symbol;
            const marketToken = this.markets[marketName].quote.symbol;
            const marketPrecision = this.markets[marketName].base.subunit;
            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            let monthVolume = kline.reduce(function(acc, curr) {
                return Decimal.add(acc, curr[6]);
            }, 0);

            let monthVolumeUSD = this.toUSD(monthVolume, marketCurrency);
            monthVolume = toMoney(monthVolume, marketPrecision) + ' ' + marketCurrency;

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex].monthVolume = monthVolume;
                this.sanitizedMarketsOnTop[marketOnTopIndex].monthVolumeUSD = monthVolumeUSD;
            } else {
                this.sanitizedMarkets[marketName].monthVolume = monthVolume;
                this.sanitizedMarkets[marketName].monthVolumeUSD = monthVolumeUSD;
            }
        },
        requestKline: function(market) {
            let id = parseInt(Math.random().toString().replace('0.', ''));
            this.sendMessage(JSON.stringify({
                method: 'kline.query',
                params: [
                    market,
                    Math.round(Date.now() / 1000) - 30 * 24 * 60 * 60,
                    Math.round(Date.now() / 1000),
                    7 * 24 * 60 * 60,
                ],
                id,
            }));

            this.klineQueriesIdsTokensMap.set(id, market);
        },
        fetchConversionRates: function() {
            let ids = Object.keys(this.cryptos).map((name) => name.toLowerCase()).join();

            let config = {
                params: {
                    ids,
                    vs_currencies: USD.symbol.toLowerCase(),
                },
            };

            return new Promise((resolve, reject) => {
                this.$axios.retry.get(`${this.coinbaseUrl}/simple/price/`, config)
                .then((res) => {
                    Object.keys(res.data).map((name) => {
                        this.conversionRates[this.cryptos[capitalize(name)].symbol] = res.data[name][USD.symbol.toLowerCase()];
                    });
                    resolve();
                })
                .catch((err) => {
                    reject();
                });
            });
        },
        toUSD: function(amount, currency) {
            return toMoney(Decimal.mul(amount, this.conversionRates[currency]), USD.subunit) + ' ' + USD.symbol;
        },
        fetchWEBsupply: function() {
            return new Promise((resolve, reject) => {
                let config = {
                    transformRequest: function(data, headers) {
                        headers.common = {};
                        return data;
                    },
                };

                this.$axios.retry.get(this.webchainSupplyUrl, config)
                    .then((res) => {
                        this.markets['WEBBTC'].supply = res.data;
                        resolve();
                    })
                    .catch((err) => {
                        this.$toasted.error('Can not update WEB circulation supply. BTC/WEB market cap might not be accurate.');
                        reject(err);
                    });
            });
        },
        updateWEBBTCMarket: function() {
            let market = this.markets['WEBBTC'];
            market = this.getSanitizedMarket(
                market.base.symbol,
                market.quote.symbol,
                this.getPercentage(
                    parseFloat(market.lastPrice),
                    parseFloat(market.openPrice)
                ),
                parseFloat(market.lastPrice),
                parseFloat(market.dayVolume),
                parseFloat(market.monthVolume),
                market.supply,
                market.base.subunit
            );

            Vue.set(this.sanitizedMarketsOnTop, 0, market);
        },
        fetchGlobalMarketCap: function() {
            this.$axios.retry.get(this.$routing.generate('marketcap'))
                .then((res) => {
                    this.globalMarketCaps['BTC'] = toMoney(res.data.marketcap, 8);
                });
            this.$axios.retry.get(this.$routing.generate('marketcap', {base: 'USD'}))
                .then((res) => {
                    this.globalMarketCaps['USD'] = toMoney(res.data.marketcap, 2);
                });
        },
    },
};
</script>
