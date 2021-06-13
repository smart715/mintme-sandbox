<template>
    <div class="trading">
        <div class="card card-fixed-large mx-auto mb-3">
            <div class="card-body p-0">
                <div class="card-header">
                    <span>{{ $t('trading.mintme_markets') }}</span>
                </div>
                <template v-if="marketsOnTopIsLoaded">
                    <div class="row coin-markets">
                        <div v-for="(market, index) in this.sanitizedMarketsOnTop"
                             :key="market.pair"
                             class="col-12 col-lg-4 my-2 px-1"
                             v-bind:class="{'market-border': sanitizedMarketsOnTop.length-1 > index}"
                        >
                            <a  :href="rebrandingFunc(market.tokenUrl)" class="d-inline text-white text-decoration-none">
                                <div class="d-inline-block pl-md-2 pr-md-2 py-2">
                                    <img :src="require('../../../img/' + market.base + '.png')"/>
                                </div>
                                <div class="crypto-pair d-inline-block align-middle">
                                    <div class="text-center">{{ market.pair|rebranding }}</div>
                                    <div v-if="parseFloat(market.change) > 0" class="market-up text-center">
                                        &#9650;+{{ market.change }}
                                    </div>
                                    <div v-else-if="parseFloat(market.change) < 0" class="market-down text-center">
                                        &#9660;{{ market.change }}
                                    </div>
                                    <div class="text-center" v-else>
                                        {{ market.change }}
                                    </div>
                                    <div class="text-center">
                                        {{ ( showUsd ? market.lastPriceUSD : market.lastPrice ) | formatMoney }}
                                    </div>
                                </div>
                            </a>
                            <div class="d-inline-block pl-2 pt-2 pt-lg-0 align-middle market-data float-right float-lg-none">
                                <span>{{ $t('trading.table.volume_30d') }}</span>
                                <span class="float-lg-right pl-1 pl-sm-4 pl-lg-0">{{ ( showUsd ? market.monthVolumeUSD : market.monthVolume ) | formatMoney}}</span>
                                <br/>
                                <span>{{ $t('trading.table.volume_24h') }}</span>
                                <span class="float-lg-right pl-1 pl-sm-4 pl-lg-0">{{ ( showUsd ? market.dayVolumeUSD : market.dayVolume ) | formatMoney}}</span>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="p-4 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
        <div class="card card-fixed-large mx-auto">
            <div class="card-body p-0">
                <div class="token-trading-title card-header d-flex flex-wrap align-items-center px-0 pb-0">
                    <span class="px-3 pb-2 mr-auto">{{ $t('trading.tokens') }}</span>
                    <div>
                        <b-dropdown
                                id="customFilter"
                                variant="primary"
                                class="px-3 pb-2"
                                :lazy="true"
                                v-model="marketFilters.selectedFilter"
                        >
                            <template slot="button-content">
                                <span>{{ marketFilters.options[marketFilters.selectedFilter].label }}</span>
                            </template>
                            <template>
                                <b-dropdown-item
                                        v-for="filter in marketFiltersOptions"
                                        :key="filter.key"
                                        :value="filter.label"
                                        @click="toggleFilter(filter.key)"
                                >
                                    {{ filter.label }}
                                </b-dropdown-item>
                            </template>
                        </b-dropdown>
                    </div>
                </div>
                <div slot="title" class="card-title font-weight-bold pl-3 pt-3 pb-1">
                    <span class="float-left">{{ tokensCount }} {{ $t('trading.tokens_and_market_cap') }} {{ globalMarketCap | formatMoney }}</span>
                </div>
                <template v-if="loaded">
                    <div class="trading-table table-responsive text-nowrap">
                        <b-table
                                thead-class="trading-head"
                                tbody-class="trading-body"
                                :items="tokens"
                                :fields="fieldsArray"
                                :sort-compare="sortCompare"
                                sort-direction="desc"
                                :sort-by.sync="sortBy"
                                :sort-desc.sync="sortDesc"
                                sort-icon-left
                                :busy="tableLoading"
                                @sort-changed="sortChanged"
                        >
                            <template v-slot:[`head(${fields.rank.key})`]="data">
                                <span>
                                    {{ data.label }}
                                </span>
                                <guide class="ml-1 mr-2"
                                    tippy-class="d-inline-flex align-items-center"
                                >
                                    <template slot="body">
                                        {{ data.field.help }}
                                    </template>
                                </guide>
                            </template>
                            <template v-slot:[`head(${fields.volume.key})`]="data">
                                <b-dropdown
                                        id="volume"
                                        variant="primary"
                                        :lazy="true"
                                        boundary="viewport"
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
                                <b-dropdown
                                        id="marketCap"
                                        variant="primary"
                                        :lazy="true"
                                        boundary="viewport"
                                >
                                    <template slot="button-content">
                                        {{ data.label|rebranding }}
                                    </template>
                                    <template>
                                        <b-dropdown-item
                                                v-for="(option, key) in marketCapOptions"
                                                :key="key"
                                                @click="setActiveMarketCap(key)"
                                        >
                                            {{ option.label|rebranding }}
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
                            <template v-slot:cell(pair)="row">
                                <div>
                                    <a v-if="row.value.length <= 20"
                                       :href="row.item.tokenUrl" class="text-white">
                                        <span v-if="showFullPair(row.value)">
                                            <avatar
                                                    :image="row.item.baseImage"
                                                    type="token"
                                                    size="small"
                                                    :symbol="row.item.base"
                                                    class="d-inline"
                                                    :key="row.item.baseImage"
                                            />
                                            {{ row.item.base }}/
                                        </span>
                                        <avatar
                                            :image="row.item.quoteImage"
                                            type="token"
                                            size="small"
                                            class="d-inline"
                                            :key="row.item.quoteImage"
                                        />
                                        {{ row.item.quote }}
                                    </a>
                                    <a v-else :href="row.item.tokenUrl" class="text-white"
                                       v-b-tooltip="{title: row.value, boundary:'window', customClass: 'tooltip-custom'}">
                                        <span v-if="showFullPair(row.value)">
                                            <avatar
                                                :image="row.item.baseImage"
                                                type="token"
                                                size="small"
                                                :symbol="row.item.base"
                                                class="d-inline"
                                                :key="row.item.baseImage"
                                            />
                                            {{ row.item.base }}/
                                        </span>
                                        <avatar
                                            v-if="row.item.quoteImage"
                                            :image="row.item.quoteImage"
                                            type="token"
                                            size="small"
                                            class="d-inline"
                                            :key="row.item.quoteImage"
                                        />
                                        <span class="token-link">
                                            {{ row.item.quote | truncate(20 - (showFullPair(row.value) ? (row.item.base+1) : 0)) }}
                                        </span>
                                    </a>
                                    <guide
                                        v-if="row.item.tokenized &&
                                        row.item.quoteImage &&
                                        row.item.cryptoSymbol === MINTME.symbol"
                                        placement="top"
                                        max-width="150px">
                                        <template slot="icon">
                                            <img :src="row.item.baseImage" alt="deployed">
                                        </template>
                                        <template slot="body">
                                            {{ $t('trading.exist_on_blockchain_guide') }}
                                        </template>
                                    </guide>
                                </div>
                            </template>
                            <template v-slot:[`head(${fields.holders.key})`]="data">
                                <span>
                                    {{ data.label }}
                                </span>
                                <guide class="ml-1 mr-2"
                                       tippy-class="d-inline-flex align-items-center"
                                >
                                    <template slot="body">
                                        {{ data.field.help }}
                                    </template>
                                </guide>
                            </template>
                        </b-table>
                    </div>
                    <template v-if="!tableLoading">
                        <template v-if="marketFilters.selectedFilter === marketFilters.options.deployed.key && !tokens.length">
                            <div class="row justify-content-center">
                                <p class="text-center p-5">{{ $t('trading.no_one_deployed') }}</p>
                            </div>
                        </template>
                        <template v-if="marketFilters.selectedFilter === marketFilters.options.airdrop.key && !tokens.length">
                            <div class="row justify-content-center">
                                <p class="text-center p-5">{{ $t('trading.no_one_airdrop') }}</p>
                            </div>
                        </template>
                        <template v-if="marketFilters.selectedFilter === marketFilters.options.user.key && !tokens.length">
                            <div class="row justify-content-center">
                                <p class="text-center p-5">{{ $t('trading.no_any_token') }}</p>
                            </div>
                        </template>
                        <template v-if="shouldShowAll">
                            <div class="row justify-content-center">
                                <b-link @click="toggleFilter('all')">{{ $t('trading.show_all_tokens') }}</b-link>
                            </div>
                        </template>
                    </template>
                    <div class="row justify-content-center">
                        <b-pagination
                                @change="updateMarkets($event, deployedFirst)"
                                :total-rows="totalRows"
                                :per-page="perPage"
                                v-model="currentPage"
                                class="my-0" />
                    </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width/>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import _ from 'lodash';
import {
    BDropdown,
    BDropdownItem,
    BTable,
    BLink,
    BPagination,
    VBTooltip,
} from 'bootstrap-vue';
import Guide from '../Guide';
import Avatar from '../Avatar';
import {
  FiltersMixin,
  WebSocketMixin,
  MoneyFilterMixin,
  RebrandingFilterMixin,
  LoggerMixin,
} from '../../mixins/';
import {toMoney, formatMoney} from '../../utils';
import {USD, WEB, BTC, MINTME, USDC, ETH} from '../../utils/constants.js';
import Decimal from 'decimal.js/decimal.js';
import {cryptoSymbols, tokenDeploymentStatus, webSymbol, currencyModes} from '../../utils/constants';

library.add(faCircleNotch);

export default {
    name: 'Trading',
    components: {
        BDropdown,
        BDropdownItem,
        BTable,
        BLink,
        BPagination,
        Guide,
        Avatar,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        LoggerMixin,
    ],
    props: {
        page: Number,
        tokensCount: Number,
        userId: Number,
        coinbaseUrl: String,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        marketsProp: Object,
        sort: String,
        order: Boolean,
        filterForTokens: Object,
        perPage: Number,
        rowsProp: Number,
    },
    data() {
        return {
            MINTME: MINTME,
            deployedFirst: ('' === this.sort),
            tableLoading: false,
            markets: this.marketsProp,
            currentPage: this.page,
            totalRows: this.rowsProp,
            sanitizedMarkets: {},
            sanitizedMarketsOnTop: [],
            currencyModes,
            marketsOnTop: [
                {currency: BTC.symbol, token: WEB.symbol},
                {currency: ETH.symbol, token: WEB.symbol},
                {currency: USDC.symbol, token: WEB.symbol},
            ],
            stateQueriesIdsTokensMap: new Map(),
            conversionRates: {},
            sortBy: this.sort,
            sortDesc: this.order,
            globalMarketCaps: {
                BTC: 0,
                USD: 0,
            },
            activeVolume: 'month',
            activeMarketCap: 'buyDepth',
            marketFilters: {
                userSelected: false,
                selectedFilter: 'deployed',
                options: {
                    deployed: {
                        key: 'deployed',
                        label: this.$t('trading.deployed.label'),
                    },
                    deployedEth: {
                        key: 'deployedEth',
                        label: this.$t('trading.deployed_eth.label'),
                    },
                    airdrop: {
                        key: 'airdrop',
                        label: this.$t('trading.airdrop.label'),
                    },
                    all: {
                        key: 'all',
                        label: this.$t('trading.all_tokens.label'),
                    },
                    user: {
                        key: 'user',
                        label: this.$t('trading.own_tokens.label'),
                    },
                },
            },
            volumes: {
                day: {
                    key: 'dayVolume',
                    label: this.$t('trading.day_volume.label'),
                    help: this.$t('trading.day_volume.help'),
                },
                month: {
                    key: 'monthVolume',
                    label: this.$t('trading.month_volume.label'),
                    help: this.$t('trading.month_volume.help'),
                },
            },
            marketCapOptions: {
                marketCap: {
                    key: 'marketCap',
                    label: this.$t('trading.market_cap.label'),
                    help: this.$t('trading.market_cap.help'),
                },
                buyDepth: {
                    key: 'buyDepth',
                    label: this.$t('trading.buy_depth.label'),
                    help: this.$t('trading.buy_depth.help'),
                },
            },
        };
    },
    computed: {
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        showUsd: function() {
            return this.currencyMode === currencyModes.usd.value;
        },
        marketsHiddenNames: function() {
            return undefined === typeof this.markets ? {} : Object.keys(this.markets);
        },
        tokens: function() {
            let tokens = Object.values(this.sanitizedMarkets);

            if ('' === this.sortBy) {
                tokens.sort((first, second) => {
                    let firstMintmeDeployed = first.tokenized && webSymbol === first.cryptoSymbol;
                    let secondMintmeDeployed = second.tokenized && webSymbol === second.cryptoSymbol;

                    if (firstMintmeDeployed !== secondMintmeDeployed) {
                        return firstMintmeDeployed ? -1 : 1;
                    }
                    return parseFloat(second.monthVolume) - parseFloat(first.monthVolume);
                });
            }

            tokens = _.map(tokens, (token) => {
                return _.mapValues(token, (item, key) => {
                    return cryptoSymbols.includes(token.base) && cryptoSymbols.includes(token.quote)
                    || 'pair' !== key && 'tokenUrl' !== key
                        ? this.rebrandingFunc(item)
                        : item;
                });
            });
            return tokens;
        },
        loaded: function() {
            return this.markets !== null;
        },
        marketsOnTopIsLoaded: function() {
            return this.sanitizedMarketsOnTop.length;
        },
        fields: function() {
            return {
                rank: {
                    key: 'rank',
                    label: this.$t('trading.fields.rank'),
                    sortable: true,
                    help: this.$t('trading.fields.rank.help'),
                },
                pair: {
                    key: 'pair',
                    label: this.$t('trading.fields.pair'),
                    sortable: true,
                    class: 'pair-cell-trading',
                },
                change: {
                    key: 'change',
                    label: this.$t('trading.fields.change'),
                    sortable: true,
                },
                lastPrice: {
                    label: this.$t('trading.fields.last_price'),
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
                    ...this.marketCapOptions[this.activeMarketCap],
                    key: this.marketCapOptions[this.activeMarketCap].key + ( this.showUsd ? USD.symbol : ''),
                    sortable: true,
                    formatter: 'marketCap' === this.activeMarketCap ? this.marketCapFormatter : formatMoney,
                },
                holders: {
                    key: 'holders',
                    label: this.$t('trading.fields.holders'),
                    sortable: true,
                    help: this.$t('trading.fields.holders.help'),
                },
            };
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        globalMarketCap: function() {
            return this.showUsd
                ? this.globalMarketCaps[USD.symbol].toLocaleString() + ' ' + USD.symbol
                : this.globalMarketCaps[BTC.symbol].toLocaleString() + ' ' + BTC.symbol;
        },
        filterDeployedFirst: function() {
            return this.filterForTokens.deployed_first || 0;
        },
        filterDeployedOnlyMintme: function() {
            return this.filterForTokens.deployed_only_mintme || 0;
        },
        filterAirdropOnly: function() {
            return this.filterForTokens.airdrop_only || 0;
        },
        filterDeployedOnlyEth: function() {
            return this.filterForTokens.deployed_only_eth || 0;
        },
        shouldShowAll: function() {
            const totalPages = Math.ceil(this.totalRows / this.perPage);

            return (this.marketFilters.selectedFilter === this.marketFilters.options.deployed.key
                    || this.marketFilters.selectedFilter === this.marketFilters.options.deployedEth.key)
                && this.tokens.length
                && this.currentPage === totalPages;
        },
        marketFiltersOptions: function() {
            const options = Object.values(this.marketFilters.options);
            const allKey = this.marketFilters.options.all.key;
            const userKey = this.marketFilters.options.user.key;

            return options.filter((filter) =>
                (userKey !== filter.key || this.userId) &&
                allKey !== filter.key
            );
        },
    },
    mounted() {
        this.initialLoad();
    },
    methods: {
        showFullPair: function(pair) {
            return pair.indexOf('/') !== -1;
        },
        toggleFilter: function(value) {
            let page = this.marketFilters.selectedFilter !== this.marketFilters.options.user.key
                && (
                    value === this.marketFilters.options.deployed.key
                    || value === this.marketFilters.options.deployedEth.key
                    || value === this.marketFilters.options.all.key
                    || value === this.marketFilters.options.airdrop.key
                )
                && this.tokens.some((token) => token.tokenized) ? this.currentPage : 1;
            this.marketFilters.userSelected = true;
            this.marketFilters.selectedFilter = value;
            this.sortBy = 'rank';
            this.sortDesc = true;
            this.updateMarkets(page, true);
        },
        initialLoad: function() {
            this.fetchGlobalMarketCap();
            this.updateSanitizedMarkets();
            this.fetchConversionRates().catch((e) => e);

            this.addMessageHandler((result) => {
                if ('state.update' === result.method) {
                    this.sanitizeMarket(result);
                    this.requestMonthInfo(result.params[0]);
                } else if (Array.from(this.stateQueriesIdsTokensMap.keys()).indexOf(result.id) != -1) {
                    this.updateMonthVolume(result.id, result.result);
                }
            }, null, 'Trading');
        },
        sortCompare: function(a, b, key) {
            let numeric = key !== this.fields.pair.key;

            if (numeric || (typeof a[key] === 'number' && typeof b[key] === 'number')) {
                let first = parseFloat(a[key]);
                let second = parseFloat(b[key]);

                let rank = key === this.fields.rank.key;

                let compareResult = first < second ? -1 : ( first > second ? 1 : 0);

                return (-1) ** rank * compareResult;
            }

            // If the value is not numeric, currently only pair column
            // b and a are reversed so that 'pair' column is ordered A-Z on first click (DESC, would be Z-A)
            return b[key].localeCompare(a[key]);
        },
        updateRawMarkets: function(page = null, deployedFirst = null) {
            return new Promise((resolve, reject) => {
                page = page === null ? this.currentPage : page;
                deployedFirst = deployedFirst === null ? this.deployedFirst : deployedFirst;

                let sort = this.sortBy.replace(USD.symbol, '');

                // So that 'pair' column will be sorted A-Z on first click (which is DESC and would be Z-A)
                let order = this.fields.pair.key === sort ? !this.sortDesc : this.sortDesc;
                let params = {
                    page,
                    sort,
                    order: order ? 'DESC' : 'ASC',
                };

                if (this.marketFilters.selectedFilter === this.marketFilters.options.user.key) {
                    params.user = 1;
                } else if (
                    this.marketFilters.selectedFilter === this.marketFilters.options.deployed.key
                ) {
                    params.filter = this.filterDeployedOnlyMintme;
                } else if (
                    this.marketFilters.selectedFilter === this.marketFilters.options.deployedEth.key
                ) {
                    params.filter = this.filterDeployedOnlyEth;
                } else if (
                    this.marketFilters.selectedFilter === this.marketFilters.options.airdrop.key
                ) {
                    params.filter = this.filterAirdropOnly;
                } else if (deployedFirst) {
                    params.filter = this.filterDeployedFirst;
                }

                this.$axios.retry.get(this.$routing.generate('markets_info', params))
                    .then((res) => {
                        if (
                            // there are only WEBBTC,WEBETH and WEBUSDC markets
                            Object.keys(res.data.markets).length === 3
                            && !this.marketFilters.userSelected
                            && this.marketFilters.selectedFilter === this.marketFilters.options.deployed.key
                        ) {
                            this.marketFilters.selectedFilter = this.marketFilters.options.all.key;
                            return this.updateRawMarkets(page, deployedFirst).then(resolve, reject);
                        }

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

                        this.deployedFirst = deployedFirst;
                        this.currentPage = page;
                        this.markets = res.data.markets;
                        this.totalRows = res.data.rows;

                        if (window.history.replaceState) {
                            // prevents browser from storing history with each change:
                            window.history.replaceState(
                                {page}, document.title, this.$routing.generate('trading', {
                                    page,
                                    sort,
                                    order: (this.sortDesc ? 'DESC' : 'ASC'),
                                })
                            );
                        }

                        resolve();
                    })
                    .catch((err) => {
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
            const market = this.markets[marketName];

            if (!market) {
                return;
            }

            const marketInfo = marketData.params[1];

            const marketLastPrice = parseFloat(marketInfo.last);
            const changePercentage = this.getPercentage(marketLastPrice, parseFloat(marketInfo.open));

            const marketCurrency = market.base.symbol;
            const marketToken = market.quote.symbol;
            const marketPrecision = market.base.subunit;
            const supply = market.supply;
            const monthVolume = market.monthVolume;
            const buyDepth = market.buyDepth;
            const marketCap = !market.marketCap ||
                WEB.symbol === marketCurrency &&
                parseFloat(monthVolume) < this.minimumVolumeForMarketcap
                    ? 0
                    : market.marketCap;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            const tokenized = market.quote.deploymentStatus === tokenDeploymentStatus.deployed;

            const baseImage = market.base.image.avatar_small;
            const quoteImage = market.quote.image ? market.quote.image.avatar_small : '';

            const sanitizedMarket = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                changePercentage,
                marketLastPrice,
                parseFloat(marketInfo.deal) + parseFloat(marketInfo.dealDonation),
                monthVolume,
                supply,
                marketPrecision,
                tokenized,
                buyDepth,
                baseImage,
                quoteImage,
                market.quote.cryptoSymbol,
                marketCap,
                market.rank || 0,
                market.holders || 0
            );

            if (marketOnTopIndex > -1) {
                this.$set(this.sanitizedMarketsOnTop, marketOnTopIndex, sanitizedMarket);
            } else {
                this.$set(this.sanitizedMarkets, marketName, sanitizedMarket);
            }

            this.markets[marketName] = {
                ...market,
                openPrice: marketInfo.open,
                lastPrice: marketInfo.last,
                dayVolume: parseFloat(marketInfo.deal) + parseFloat(marketInfo.dealDonation),
            };
        },
        getSanitizedMarket: function(
            currency,
            token,
            changePercentage,
            lastPrice,
            dayVolume,
            monthVolume,
            supply,
            subunit,
            tokenized,
            buyDepth,
            baseImage,
            quoteImage,
            cryptoSymbol,
            marketCap = 0,
            rank = 0,
            holders = 0,
        ) {
            let hiddenName = this.findHiddenName(token);

            return {
                pair: [BTC.symbol, ETH.symbol, WEB.symbol, USDC.symbol].includes(token)
                    ? `${token}/${currency}`
                    : `${token}`,
                change: toMoney(changePercentage, 2) + '%',
                lastPrice: toMoney(lastPrice, subunit) + ' ' + currency,
                dayVolume: this.toMoney(dayVolume, BTC.symbol === currency ? 4 : 2) + ' ' + currency,
                monthVolume: this.toMoney(monthVolume, BTC.symbol === currency ? 4 : 2) + ' ' + currency,
                tokenUrl: hiddenName && hiddenName.indexOf('TOK') !== -1 ?
                    this.$routing.generate('token_show', {name: token}) :
                    this.$routing.generate('coin', {base: currency, quote: token}),
                lastPriceUSD: this.toUSD(lastPrice, currency, true),
                dayVolumeUSD: this.toUSD(dayVolume, currency),
                monthVolumeUSD: this.toUSD(monthVolume, currency),
                marketCap: this.toMoney(marketCap) + ' ' + currency,
                marketCapUSD: this.toUSD(marketCap, currency),
                buyDepth: this.toMoney(buyDepth) + ' ' + currency,
                buyDepthUSD: this.toUSD(buyDepth, currency),
                tokenized: tokenized,
                base: currency,
                quote: token,
                baseImage,
                quoteImage,
                cryptoSymbol,
                rank,
                holders,
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
        updateSanitizedMarkets: function() {
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

                    const selectedMarket = this.markets[market];

                    const sanitizedMarket = this.getSanitizedMarket(
                        cryptoSymbol,
                        tokenName,
                        this.getPercentage(
                            parseFloat(selectedMarket.lastPrice),
                            parseFloat(selectedMarket.openPrice)
                        ),
                        parseFloat(selectedMarket.lastPrice),
                        parseFloat(selectedMarket.dayVolume),
                        parseFloat(selectedMarket.monthVolume),
                        selectedMarket.supply,
                        selectedMarket.base.subunit,
                        tokenized,
                        parseFloat(this.markets[market].buyDepth),
                        selectedMarket.base.image.avatar_small,
                        selectedMarket.quote.image? selectedMarket.quote.image.avatar_small: '',
                        selectedMarket.quote.cryptoSymbol,
                        selectedMarket.marketCap || 0,
                        selectedMarket.rank || 0,
                        selectedMarket.holders || 0
                    );
                    if (marketOnTopIndex > -1) {
                        this.$set(this.sanitizedMarketsOnTop, marketOnTopIndex, sanitizedMarket);
                    } else {
                        this.$set(this.sanitizedMarkets, market, sanitizedMarket);
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

            if (!market) {
                return;
            }

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
                market.monthVolume = parseFloat(marketInfo.deal) + parseFloat(marketInfo.dealDonation),
                market.supply,
                market.base.subunit,
                tokenized,
                market.buyDepth,
                market.base.image.avatar_small,
                market.quote.image ? market.quote.image.avatar_small: '',
                market.quote.cryptoSymbol,
                market.marketCap || 0,
                market.rank || 0,
                market.holders || 0
                );

            if (marketOnTopIndex > -1) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = sanitizedMarket;
            } else {
                this.sanitizedMarkets[marketName] = sanitizedMarket;
            }
        },
        requestMonthInfo: function(market) {
            if (!this.markets[market]) {
                return;
            }

            let id = parseInt(Math.random().toString().replace('0.', ''));
            this.sendMessage(JSON.stringify({
                method: 'state.query',
                params: [market, 30 * 24 * 60 * 60],
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
                false,
                market.buyDepth,
                market.base.image.avatar_small,
                market.quote.image.avatar_small,
                market.quote.cryptoSymbol,
                market.marketCap,
                market.rank,
                market.holders
            );
            this.$set(this.sanitizedMarketsOnTop, 0, market);
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
            return MINTME.symbol === item.base && parseFloat(item.monthVolume) < this.minimumVolumeForMarketcap ||
                ETH.symbol === item.cryptoSymbol
                ? '-'
                : value;
        },
        toggleActiveVolume: function(volume) {
            this.activeVolume = volume;
        },
        setActiveMarketCap: function(marketCap) {
            this.activeMarketCap = marketCap;
        },
        updateMarkets: function(page = null, deployedFirst = null) {
            this.tableLoading = true;
            return this.updateRawMarkets(page, deployedFirst)
                .then(() => this.updateSanitizedMarkets())
                .then(() => this.tableLoading = false);
        },
        sortChanged: function(ctx) {
            this.sortBy = ctx.sortBy;
            this.sortDesc = ctx.sortDesc;
            this.updateMarkets(1, false);
        },
    },
};
</script>
