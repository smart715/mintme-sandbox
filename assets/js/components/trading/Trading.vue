<template>
    <div class="trading">
        <div class="card-header">
            <span>Trading</span>
                <b-dropdown
                    id="currency"
                    variant="primary"
                    class="float-right"
                    :lazy="true"
                >
                <template slot="button-content">
                    Currency:
                <span v-if="showUsd">
                    USD
                </span>
                <span v-else>
                    Crypto
                </span>
                </template>
                <template>
                    <b-dropdown-item @click="toggleUsd(false)">
                        Crypto
                    </b-dropdown-item>
                    <b-dropdown-item class="usdOption" :disabled="!enableUsd" @click="toggleUsd(true)">
                        USD
                    </b-dropdown-item>
                </template>
            </b-dropdown>
        </div>
        <div slot="title" class="card-title font-weight-bold pl-3 pt-3 pb-1">
            <span class="float-left">Top {{ tokensCount }} tokens | Market Cap: {{ globalMarketCap | formatMoney }}</span>
            <b-dropdown
                v-if="userId" class="float-right pr-3"
                id="customFilter"
                variant="primary"
                v-model="marketFilters.selectedFilter"
            >
                <template slot="button-content">
                    <span>{{ marketFilters.options[marketFilters.selectedFilter].label }}</span>
                </template>
                <template>
                    <b-dropdown-item
                        v-for="filter in marketFilters.options"
                        :key="filter.key"
                        :value="filter.label"
                        @click="toggleFilter(filter.key)"
                    >
                        {{ filter.label }}
                    </b-dropdown-item>
                </template>
            </b-dropdown>
        </div>
        <template v-if="loaded">
            <div class="trading-table table-responsive text-nowrap">
                <b-table
                    thead-class="trading-head"
                    :items="tokens"
                    :fields="fieldsArray"
                    :sort-compare="sortCompare"
                    sort-direction="desc"
                    :sort-by.sync="sortBy"
                    :sort-desc.sync="sortDesc"
                >
                    <template v-slot:[`head(${fields.volume.key})`]="data">
                        <b-dropdown
                            id="volume"
                            variant="primary"
                            :lazy="true"
                        >
                            <template slot="button-content">
                                {{ data.label|rebranding }}
                            </template>
                            <template>
                                <b-dropdown-item
                                    v-for="(volume, key) in volumes"
                                    :key="key"
                                    @click="toggleActiveVolume(key)"
                                >
                                    {{ volume.label|rebranding }}
                                </b-dropdown-item>
                            </template>
                        </b-dropdown>
                        <guide class="ml-1 mr-2">
                            <template slot="header">
                                {{ data.label|rebranding }}
                            </template>
                            <template slot="body">
                                {{ data.field.help|rebranding}}
                            </template>
                        </guide>
                    </template>
                    <template v-slot:[`head(${fields.marketCap.key})`]="data">
                        {{ data.label|rebranding }}
                        <guide>
                            <template slot="header">
                                Market Cap
                            </template>
                            <template slot=body>
                                Market cap based on max supply of 10 million tokens.
                                Market cap is not shown if 30d volume is lower than
                                {{ minimumVolumeForMarketcap | formatMoney }} MINTME.
                            </template>
                        </guide>
                    </template>
                    <template v-slot:cell(pair)="row">
                        <div class="truncate-name w-100">
                            <a :href="row.item.tokenUrl" class="text-white" v-b-tooltip:title="row.value">
                                {{ row.value }}
                            </a>
                            <guide
                                placement="top"
                                max-width="150px"
                                v-if="row.item.tokenized">
                                <template slot="icon">
                                    <img src="../../../img/mintmecoin_W.png" alt="deployed">
                                </template>
                                <template slot="body">
                                    This token exists on the blockchain.
                                </template>
                            </guide>
                        </div>
                    </template>
                </b-table>
            </div>
            <template v-if="marketFilters.selectedFilter === 'deployed' && tokens.length < 2">
                <div class="row justify-content-center">
                    <p class="text-center p-5">No one deployed his token yet</p>
                </div>
            </template>
            <template v-if="marketFilters.selectedFilter === 'user' && tokens.length < 2">
                <div class="row justify-content-center">
                    <p class="text-center p-5">No any token yet</p>
                </div>
            </template>
            <template v-if="userId && (marketFilters.selectedFilter === 'deployed' || marketFilters.selectedFilter === 'user')">
                <div class="row justify-content-center">
                    <b-link @click="toggleFilter('all')">Show rest of tokens</b-link>
                </div>
            </template>
            <div class="row justify-content-center">
                <b-pagination
                    @change="fetchData"
                    :total-rows="totalRows"
                    :per-page="perPage"
                    v-model="currentPage"
                    class="my-0" />
            </div>
        </template>
        <template v-else>
            <div class="p-5 text-center text-white">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
        </template>
    </div>
</template>

<script>
import _ from 'lodash';
import Guide from '../Guide';
import {FiltersMixin, WebSocketMixin, MoneyFilterMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin} from '../../mixins/';
import {toMoney, formatMoney} from '../../utils';
import {USD, WEB, BTC, MINTME} from '../../utils/constants.js';
import Decimal from 'decimal.js/decimal.js';
import {tokenDeploymentStatus} from '../../utils/constants';

export default {
    name: 'Trading',
    mixins: [WebSocketMixin, FiltersMixin, MoneyFilterMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin],
    props: {
        page: Number,
        tokensCount: Number,
        userId: Number,
        coinbaseUrl: String,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
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
            sanitizedMarkets: {},
            sanitizedMarketsOnTop: [],
            marketsOnTop: [
                {currency: BTC.symbol, token: WEB.symbol},
            ],
            showUsd: false,
            enableUsd: true,
            stateQueriesIdsTokensMap: new Map(),
            conversionRates: {},
            sortBy: '',
            sortDesc: true,
            globalMarketCaps: {
                BTC: 0,
                USD: 0,
            },
            activeVolume: 'month',
            marketFilters: {
                userSelected: false,
                selectedFilter: 'deployed',
                options: {
                    deployed: {
                        key: 'deployed',
                        label: 'Deployed tokens',
                    },
                    all: {
                        key: 'all',
                        label: 'All tokens',
                    },
                    user: {
                        key: 'user',
                        label: 'Tokens I own',
                    },
                },
            },
            volumes: {
                day: {
                    key: 'volume',
                    label: '24H Volume',
                    help: 'The amount of crypto that has been traded in the last 24 hours.',
                },
                month: {
                    key: 'monthVolume',
                    label: '30d Volume',
                    help: 'The amount of crypto that has been traded in the last 30 days.',
                },
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
            let tokens = Object.values(this.sanitizedMarkets);
            tokens.sort((first, second) => {
                if (first.tokenized !== second.tokenized) {
                    return first.tokenized ? -1 : 1;
                }
                return parseFloat(second.monthVolume) - parseFloat(first.monthVolume);
            });
            tokens = this.sanitizedMarketsOnTop.concat(tokens);
            tokens = _.map(tokens, (token) => {
                return _.mapValues(token, (item) => {
                    return this.rebrandingFunc(item);
                });
            });
            return tokens;
        },
        loaded: function() {
            return this.markets !== null && !this.loading;
        },
        fields: function() {
            return {
                pair: {
                    key: 'pair',
                    label: 'Market',
                    sortable: true,
                    class: 'pair-cell-trading',
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
                    ...this.volumes[this.activeVolume],
                    key: this.volumes[this.activeVolume].key + (this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: formatMoney,
                },
                marketCap: {
                    label: 'Market Cap',
                    key: 'marketCap' + ( this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: (value, key, item) => formatMoney(this.marketCapFormatter(value, key, item)),
                },
            };
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        globalMarketCap: function() {
            if (this.showUsd) {
                return this.globalMarketCaps[USD.symbol] + ' ' + USD.symbol;
            }
            return this.globalMarketCaps[BTC.symbol] + ' ' + BTC.symbol;
        },
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        toggleFilter: function(value) {
            this.marketFilters.userSelected = true;
            this.marketFilters.selectedFilter = value;
            this.sortBy = '';
            this.sortDesc = true;
            this.fetchData(1);
        },
        toggleUsd: function(show) {
            this.showUsd = show;
        },
        disableUsd: function() {
            this.showUsd = false;
            this.enableUsd = false;
        },
        fetchData: function(page = false) {
            if (page) {
                this.currentPage = page;
            }

            let updateDataPromise = this.updateData(this.currentPage, this.marketFilters.selectedFilter);
            let conversionRatesPromise = this.fetchConversionRates();
            this.fetchGlobalMarketCap();

            Promise.all([updateDataPromise, conversionRatesPromise.catch((e) => e)])
                .then((res) => {
                    if (Object.keys(this.markets).length === 1 && !this.marketFilters.userSelected) {
                        this.marketFilters.selectedFilter = 'all';
                        this.fetchData();
                        return;
                    }
                    this.updateDataWithMarkets();
                    this.loading = false;

                    this.addMessageHandler((result) => {
                        if ('state.update' === result.method) {
                            this.sanitizeMarket(result);
                            this.requestMonthInfo(result.params[0]);
                        } else if (Array.from(this.stateQueriesIdsTokensMap.keys()).indexOf(result.id) != -1) {
                            this.updateMonthVolume(result.id, result.result);
                        }
                    });
                });
        },
        sortCompare: function(a, b, key) {
            let pair = false;
            this.marketsOnTop.forEach((market)=> {
                let currency = this.rebrandingFunc(market.currency);
                let token = this.rebrandingFunc(market.token);

                if (b.pair === currency + '/' + token || a.pair === currency + '/' + token) {
                    pair = true;
                }
            });
            let numeric = key !== this.fields.pair.key;

            if (numeric || (typeof a[key] === 'number' && typeof b[key] === 'number')) {
                let first = parseFloat(a[key]);
                let second = parseFloat(b[key]);

                return pair ? 0 : (first < second ? -1 : ( first > second ? 1 : 0));
            }

            // If the value is not numeric, currently only pair column
            // b and a are reversed so that 'pair' column is ordered A-Z on first click (DESC, would be Z-A)
            return pair ? 0 : b[key].localeCompare(a[key]);
        },
        updateData: function(page) {
            return new Promise((resolve, reject) => {
                let params = {page};
                if (this.marketFilters.selectedFilter === 'user') {
                    params.user = 1;
                } else if (this.marketFilters.selectedFilter === 'deployed' && this.userId) {
                    params.deployed = 1;
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

                        resolve();
                    })
                    .catch((err) => {
                        this.notifyError('Can not update the markets data. Try again later.');
                        this.sendLogs('error', 'Can not update the markets data', err);
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

            const tokenized = this.markets[marketName].quote.deploymentStatus === tokenDeploymentStatus.deployed;

            const market = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                changePercentage,
                marketLastPrice,
                parseFloat(marketInfo.deal),
                monthVolume,
                supply,
                marketPrecision,
                tokenized
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
        getSanitizedMarket: function(currency, token, changePercentage, lastPrice, volume, monthVolume, supply, subunit, tokenized) {
            let hiddenName = this.findHiddenName(token);
            let marketCap = WEB.symbol === currency && parseFloat(monthVolume) < this.minimumVolumeForMarketcap
                ? 0
                : Decimal.mul(lastPrice, supply);

            return {
                pair: BTC.symbol === currency ? `${currency}/${token}` : `${token}`,
                change: toMoney(changePercentage, 2) + '%',
                lastPrice: toMoney(lastPrice, subunit) + ' ' + currency,
                volume: this.toMoney(volume, BTC.symbol === currency ? 4 : 2) + ' ' + currency,
                monthVolume: this.toMoney(monthVolume, BTC.symbol === currency ? 4 : 2) + ' ' + currency,
                tokenUrl: hiddenName && hiddenName.indexOf('TOK') !== -1 ?
                    this.$routing.generate('token_show', {name: token}) :
                    this.$routing.generate('coin', {base: currency, quote: token}),
                lastPriceUSD: this.toUSD(lastPrice, currency, true),
                volumeUSD: this.toUSD(volume, currency),
                monthVolumeUSD: this.toUSD(monthVolume, currency),
                marketCap: this.toMoney(marketCap) + ' ' + currency,
                marketCapUSD: this.toUSD(marketCap, currency),
                tokenized: tokenized,
                base: currency,
                quote: token,
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
                    const cryptoSymbol = this.markets[market].base.symbol;
                    const tokenName = this.markets[market].quote.symbol;
                    const marketOnTopIndex = this.getMarketOnTopIndex(cryptoSymbol, tokenName);
                    const tokenized = this.markets[market].quote.deploymentStatus === tokenDeploymentStatus.deployed;
                    const webBtcOnTop = this.marketsOnTop[0];
                    if (marketOnTopIndex > -1 &&
                        cryptoSymbol === webBtcOnTop.currency &&
                        tokenName === webBtcOnTop.token) {
                        this.markets[market].supply = 0;
                        this.fetchWEBsupply().then(
                            (resolve) => {
                                this.markets[market].supply = resolve;
                                this.updateWEBBTCMarket(this);
                            }
                        );
                    } else {
                        this.markets[market].supply = 1e7;
                    }

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
                        this.markets[market].base.subunit,
                        tokenized
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
        updateMonthVolume: function(requestId, marketInfo) {
            const marketName = this.stateQueriesIdsTokensMap.get(requestId);
            const market = this.markets[marketName];
            const tokenized = market.quote.deploymentStatus === tokenDeploymentStatus.deployed;
            const marketOnTopIndex = this.getMarketOnTopIndex(market.base.symbol, market.quote.symbol);

            const sanitizedMarket = this.getSanitizedMarket(
                market.base.symbol,
                market.quote.symbol,
                this.getPercentage(
                    parseFloat(market.lastPrice),
                    parseFloat(market.openPrice)
                ),
                market.lastPrice,
                market.dayVolume,
                market.monthVolume = marketInfo.deal,
                market.supply,
                market.base.subunit,
                tokenized
                );

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = sanitizedMarket;
            } else {
                this.sanitizedMarkets[marketName] = sanitizedMarket;
            }
        },
        requestMonthInfo: function(market) {
            let id = parseInt(Math.random().toString().replace('0.', ''));
            this.sendMessage(JSON.stringify({
                method: 'state.query',
                params: [
                    market,
                    30 * 24 * 60 * 60,
                ],
                id,
            }));

            this.stateQueriesIdsTokensMap.set(id, market);
        },
        fetchConversionRates: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('exchange_rates'))
                .then((res) => {
                    if (!(res.data && Object.keys(res.data).length)) {
                        return Promise.reject();
                    }

                    this.conversionRates = res.data;
                    resolve();
                })
                .catch((err) => {
                    this.$emit('disable-usd');
                    this.notifyError('Error fetching exchange rates for cryptos. Selecting USD as currency might not work');
                    this.sendLogs('error', 'Error fetching exchange rates for cryptos', err);
                    reject();
                });
            });
        },
        toUSD: function(amount, currency, subunit = false) {
            amount = Decimal.mul(amount, ((this.conversionRates[currency] || [])[USD.symbol] || 1));
            return (subunit ? toMoney(amount, USD.subunit) : this.toMoney(amount)) + ' ' + USD.symbol;
        },
        fetchWEBsupply: function() {
            return new Promise((resolve, reject) => {
                let config = {
                    'transformRequest': function(data, headers) {
                        headers.common = {};
                        return data;
                    },
                    'axios-retry': {
                        retries: 5,
                    },
                };

                this.$axios.retry.get(this.mintmeSupplyUrl, config)
                    .then((res) => {
                        this.markets['WEBBTC'].supply = res.data;
                        resolve(res.data);
                    })
                    .catch((err) => {
                        this.notifyError('Can not update MINTME circulation supply. BTC/MINTME market cap might not be accurate.');
                        this.sendLogs('error', 'Can not update MINTME circulation supply', err);
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
                market.base.subunit,
                false
            );
            Vue.set(this.sanitizedMarketsOnTop, 0, market);
        },
        fetchGlobalMarketCap: function() {
            this.$axios.retry.get(this.$routing.generate('marketcap'))
                .then((res) => {
                    this.globalMarketCaps[BTC.symbol] = this.toMoney(res.data.marketcap);
                })
                .catch((err) => {
                    this.sendLogs('error', 'Can not fetch BTC from global market cap', err);
                });
            this.$axios.retry.get(this.$routing.generate('marketcap', {base: 'USD'}))
                .then((res) => {
                    this.globalMarketCaps[USD.symbol] = this.toMoney(res.data.marketcap);
                })
                .catch((err) => {
                    this.sendLogs('error', 'Can not fetch USD from global market cap', err);
                });
        },
        toMoney: function(val, subunit = 2) {
            val = new Decimal(val);
            let precision = val.lessThan(100)
                ? subunit
                : 0;
            return toMoney(val, precision);
        },
        marketCapFormatter: function(value, key, item) {
            return MINTME.symbol === item.base && parseFloat(item.monthVolume) < this.minimumVolumeForMarketcap
                ? '-'
                : value;
        },
        toggleActiveVolume: function(volume) {
            this.activeVolume = volume;
            this.sortBy = this.volumes[this.activeVolume].key;
            this.sortDesc = true;
        },
    },
};
</script>
