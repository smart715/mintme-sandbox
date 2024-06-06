<template>
    <div class="trading">
        <div v-if="enableChartsMarkets" class="row mt-4">
            <div class="col-12">
                <h2 class="font-weight-semibold">
                    {{ $t('trading.title_1') }}
                    <span class="text-primary">
                        {{ $t('trading.coins') }}
                    </span>
                </h2>
            </div>
        </div>
        <charts-markets
            v-if="enableChartsMarkets"
            :sanitized-markets-on-top="sanitizedMarketsOnTop"
        />
        <trading-table
            :tokens-count="tokensCount"
            :current-crypto="currentCrypto"
            :cryptos="cryptos"
            :user-id="userId"
            :sort-by-prop="sortBy"
            :market-filters-prop="marketFilters"
            :fields="fields"
            :volumes="volumes"
            :market-cap-options="marketCapOptions"
            :combined-volume-cap-options="combinedVolumeCapOptions"
            :minimum-volume-for-market-cap="minimumVolumeForMarketcap"
            :table-loading="tableLoading"
            :tokens-prop="sanitizedMarkets"
            :enable-filter="enableFilter"
            :enable-search="enableSearch"
            :last-page="lastPage"
            :is-coins-trading="isCoinsTrading"
            :new-markets-enabled="newMarketsEnabled"
            :token-promotions="tokenPromotions"
            @sort-changed="sortChanged"
            @toggle-crypto="toggleCrypto"
            @toggle-filter="toggleFilter"
            @toggle-search="toggleSearch"
            @toggle-active-volume="toggleActiveVolume"
            @set-active-marketCap="setActiveMarketCap"
            @set-active-volume-cap="setActiveVolumeCapOption"
            @toggle-show-more="toggleShowMore"
        />
    </div>
</template>

<script>
import Decimal from 'decimal.js';
import ChartsMarkets from './ChartsMarkets';
import TradingTable from './TradingTable';
import {
    formatMoney,
    generateMintmeAvatarHtml,
    toMoney,
    getCoinAvatarAssetName,
} from '../../utils';
import {
    USD,
    WEB,
    BTC,
    MINTME,
    ETH,
} from '../../utils/constants.js';
import {tokenDeploymentStatus} from '../../utils/constants';
import {
    FiltersMixin,
    WebSocketMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    StringMixin,
    NotificationMixin,
} from '../../mixins/';
import debounce from 'lodash/debounce';
import axios from 'axios';
import {mapGetters} from 'vuex';

export const tradingTableColumns = {
    pair: 'pair',
    rank: 'rank',
    combinedPairHolders: 'combinedPairHolders',
    lastPrice: 'lastPrice',
    change: 'change',
    combinedPriceChange: 'combinedPriceChange',
    networks: 'networks',
    holders: 'holders',
    combinedVolumeCap: 'combinedVolumeCap',
    volume: 'volume',
    marketCap: 'marketCap',
    tokenUrl: 'tokenUrl',
    dotOption: 'dotOption',
};

const PAGE_TYPE = {
    TOKENS: 'tokens',
    COINS: 'coins',
};

export const tradingColumnsSort = {
    rank: 'rank',
    pair: 'pair',
    lastPrice: 'lastPrice',
    change: 'change',
    networks: 'networks',
    holders: 'holders',
    monthVolume: 'monthVolume',
    dayVolume: 'dayVolume',
    marketCap: 'marketCap',
    buyDepth: 'buyDepth',
};

export default {
    name: 'Trading',
    components: {
        ChartsMarkets,
        TradingTable,
    },
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        StringMixin,
        NotificationMixin,
    ],
    props: {
        deployBlockchains: Array,
        tokensCount: Number,
        userId: Number,
        coinbaseUrl: String,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        marketsProp: Object,
        promotedMarkets: Object,
        cryptoTopListMarketKeys: Array,
        sort: String,
        order: Boolean,
        filterForTokens: Object,
        page: Number,
        lastPageProp: Boolean,
        type: String,
        tokenPromotions: Array,
        newMarketsEnabled: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        const volumesOptions = {
            day: {
                key: 'dayVolume',
                label: this.$t('trading.day_volume.label'),
                short_label: this.$t('trading.day_vol.label'),
                help: this.$t('trading.day_volume.help'),
                sort: tradingColumnsSort.dayVolume,
            },
            month: {
                key: 'monthVolume',
                label: this.$t('trading.month_volume.label'),
                short_label: this.$t('trading.month_vol.label'),
                help: this.$t('trading.month_volume.help'),
                sort: tradingColumnsSort.monthVolume,
            },
        };

        const mintmeBlock = generateMintmeAvatarHtml();

        const marketCapOptions = {
            marketCap: {
                key: 'marketCap',
                label: this.$t('trading.market_cap.label'),
                short_label: this.$t('trading.market_cap.label'),
                help: this.$t('trading.market_cap.help'),
                sort: tradingColumnsSort.marketCap,
            },
            buyDepth: {
                key: 'buyDepth',
                label: this.$t('trading.buy_depth.label'),
                short_label: this.$t('trading.buy_depth.label'),
                help: this.$t('trading.buy_depth.help', {mintmeBlock: mintmeBlock}),
                single_help: this.$t('trade.chart.buy_depth_guide_body'),
                sort: tradingColumnsSort.buyDepth,
            },
        };

        return {
            tokens: {},
            currentCrypto: WEB.symbol,
            MINTME: MINTME,
            tableLoading: false,
            markets: {...this.marketsProp},
            totalRows: this.rowsProp,
            currentPage: this.page,
            sanitizedMarkets: {},
            sanitizedMarketsOnTop: [],
            stateQueriesIdsTokensMap: new Map(),
            conversionRates: {},
            sortBy: this.sort,
            sortDesc: this.order,
            activeVolume: 'month',
            activeMarketCap: 'buyDepth',
            activeVolumeCapOption: 'month',
            searchPhrase: '',
            searchPhraseMinLength: 3,
            updateMarketsDelay: 1000,
            updateMarketsDebounce: null,
            cancelTokenSource: null,
            lastPage: this.lastPageProp,
            volumes: volumesOptions,
            marketCapOptions: marketCapOptions,
            selectedFilters: ['deployedWEB'],
            cachedCryptoSupply: {},
        };
    },
    watch: {
        conversionRates: function(value) {
            if (value) {
                this.updateSanitizedMarkets();
            }
        },
    },
    computed: {
        ...mapGetters('crypto', {
            cryptos: 'getCryptosMap',
        }),
        deployedOptions() {
            return this.deployBlockchains
                .reduce((acc, symbol) => {
                    if (!this.cryptos[symbol]) {
                        return acc;
                    }

                    acc[`deployed${symbol}`] = {
                        key: `deployed${symbol}`,
                        label: this.$t(`dynamic.trading.deployed.label_${symbol}`),
                    };

                    return acc;
                }, {});
        },
        marketFilters() {
            return {
                selectedFilters: this.selectedFilters,
                options: {
                    ...this.deployedOptions,
                    newest_deployed: {
                        key: 'newest_deployed',
                        label: this.$t('trading.newest_deployed.label'),
                    },
                    airdrop: {
                        key: 'airdrop',
                        label: this.$t('trading.airdrop.label'),
                    },
                    user_owns: {
                        key: 'user_owns',
                        label: this.$t('trading.user_owns.label'),
                    },
                },
            };
        },
        availableBlockchains() {
            return this.deployBlockchains.filter((symbol) => this.cryptos[symbol]);
        },
        isCoinsTrading() {
            return PAGE_TYPE.COINS === this.type;
        },
        isTokensPage() {
            return PAGE_TYPE.TOKENS === this.type;
        },
        combinedVolumeCapOptions() {
            return {
                ...this.volumes,
                ...(this.isCoinsTrading
                    ? this.marketCapOptions
                    : {buyDepth: this.marketCapOptions.buyDepth}
                ),
            };
        },
        fields: function() {
            const commonFields =
            {
                rank: {
                    key: 'rank',
                    field: 'rank',
                    label: this.$t('trading.fields.rank'),
                    sortable: true,
                    sort: tradingColumnsSort.rank,
                    firstSortType: 'desc',
                    help: this.$t('trading.fields.rank.help'),
                    single_help: this.$t('trading.fields.rank.help'),
                    hidden: this.isCoinsTrading,
                    thClass: 'sort d-table-cell d-sm-none d-lg-table-cell rank-column',
                    tdClass: 'd-table-cell d-sm-none d-lg-table-cell rank-column',
                },
                pair: {
                    key: 'pair',
                    field: tradingTableColumns.pair,
                    label: this.$t('trading.fields.pair'),
                    class: 'pair-cell-trading',
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.pair,
                    thClass: 'sort pair-column d-none d-sm-table-cell',
                    tdClass: 'pair-column d-none d-sm-table-cell',
                },
                combinedPairHolders: {
                    key: 'combined-pair-holders',
                    field: tradingTableColumns.combinedPairHolders,
                    label: this.$t('trading.fields.pair'),
                    class: 'pair-cell-trading',
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.pair,
                    thClass: 'sort pair-column d-sm-none',
                    tdClass: 'pair-column d-sm-none',
                },
                lastPrice: {
                    key: 'lastPrice' + USD.symbol,
                    field: tradingTableColumns.lastPrice,
                    label: this.$t('trading.fields.last_price'),
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.lastPrice,
                    formatter: formatMoney,
                    thClass: 'sort last-price-column d-none d-sm-table-cell',
                    tdClass: 'last-price-column d-none d-sm-table-cell',
                },
                change: {
                    key: 'change',
                    field: tradingTableColumns.change,
                    label: this.$t('trading.fields.change'),
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.change,
                    thClass: 'sort change-column d-none d-sm-table-cell',
                    tdClass: 'change-column d-none d-sm-table-cell',
                },
                combinedPriceChange: {
                    key: 'combined-price-change',
                    field: tradingTableColumns.combinedPriceChange,
                    label: `${this.$t('trading.fields.last_price')}/${this.$t('trading.fields.change')}`,
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.change,
                    formatter: formatMoney,
                    thClass: 'sort last-price-column d-sm-none',
                    tdClass: 'last-price-column d-sm-none',
                },
                networks: {
                    key: 'networks',
                    field: tradingTableColumns.networks,
                    label: this.$t('trading.fields.networks'),
                    sortable: false,
                    thClass: 'networks-column d-none d-sm-table-cell',
                    tdClass: 'networks-column d-none d-sm-table-cell',
                    hidden: this.isCoinsTrading,
                },
                holders: {
                    thClass: 'd-none',
                    tdClass: 'd-none',
                },
                combinedVolumeCap: {
                    ...this.combinedVolumeCapOptions[this.activeVolumeCapOption],
                    key: this.combinedVolumeCapOptions[this.activeVolumeCapOption].key + USD.symbol,
                    field: tradingTableColumns.combinedVolumeCap,
                    sortable: true,
                    firstSortType: 'desc',
                    sort: this.combinedVolumeCapOptions[this.activeVolumeCapOption].sort,
                    thClass: 'combined-volume-cap-column sort d-custom-xl-none with-dropdown',
                    tdClass: 'combined-volume-cap-column d-custom-xl-none with-dropdown',
                },
                volume: {
                    ...this.volumes[this.activeVolume],
                    key: this.volumes[this.activeVolume].key + USD.symbol,
                    field: tradingTableColumns.volume,
                    sortable: true,
                    firstSortType: 'desc',
                    sort: this.volumes[this.activeVolume].sort,
                    formatter: formatMoney,
                    thClass: 'sort volume-column d-none d-custom-xl-table-cell with-dropdown',
                    tdClass: 'volume-column d-none d-custom-xl-table-cell with-dropdown',
                },
                marketCap: {
                    ...this.marketCapOptions[this.activeMarketCap],
                    key: this.marketCapOptions[this.activeMarketCap].key + USD.symbol,
                    field: tradingTableColumns.marketCap,
                    sortable: true,
                    firstSortType: 'desc',
                    sort: this.marketCapOptions[this.activeMarketCap].sort,
                    formatter: 'marketCap' === this.activeMarketCap ? this.marketCapFormatter : formatMoney,
                    thClass: 'sort marketcap-column column-with-guide d-none d-custom-xl-table-cell with-dropdown',
                    tdClass: 'marketcap-column column-with-guide d-none d-custom-xl-table-cell with-dropdown',
                },
                tokenUrl: {
                    key: 'trade',
                    field: tradingTableColumns.tokenUrl,
                    label: '',
                    sortable: false,
                    firstSortType: 'desc',
                    thClass: 'trade-column d-md-table-cell',
                    tdClass: 'trade-column d-md-table-cell',
                },
                dotOption: {
                    key: 'trade',
                    field: tradingTableColumns.dotOption,
                    label: '',
                    sortable: false,
                    firstSortType: 'desc',
                    thClass: 'menu-dot-column d-sm-table-cell d-lg-none',
                    tdClass: 'menu-dot-column d-sm-table-cell d-lg-none',
                },
            };

            const tokensPageFields =
            {
                combinedPairHolders: {
                    key: 'combined-pair-holders',
                    field: tradingTableColumns.combinedPairHolders,
                    label: this.$t('trading.fields.pair') + '/' + this.$t('trading.fields.holders'),
                    class: 'pair-cell-trading',
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.pair,
                    thClass: 'sort pair-column d-sm-none',
                    tdClass: 'pair-column d-sm-none',
                },
                holders: {
                    key: 'holders',
                    field: tradingTableColumns.holders,
                    label: this.$t('trading.fields.holders'),
                    sortable: true,
                    firstSortType: 'desc',
                    sort: tradingColumnsSort.holders,
                    tooltip: this.$t('trading.fields.holders.help'),
                    thClass: 'sort holders-column d-none d-sm-table-cell',
                    tdClass: 'holders-column d-none d-sm-table-cell',
                },
            };

            return this.isTokensPage ? {...commonFields, ...tokensPageFields} : commonFields;
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        marketsHiddenNames: function() {
            return undefined === this.markets ? {} : Object.keys(this.markets);
        },
        filterAirdropOnly: function() {
            return this.filterForTokens.airdrop_only || 0;
        },
        filterUserOwns: function() {
            return this.filterForTokens.user_owns || 0;
        },
        filterNewestDeployed: function() {
            return this.filterForTokens.newest_deployed || 0;
        },
        selectedBlockchainsAmount: function() {
            const blockchainFilterKeys = this.availableBlockchains
                .map((symbol) => this.marketFilters.options[`deployed${symbol}`].key);

            return blockchainFilterKeys.filter((blockchainFilterKey) => {
                return this.selectedFilters.includes(blockchainFilterKey);
            }).length;
        },
        isMarketCapColumnActive: function() {
            return 'marketCap' === this.activeMarket;
        },
        enableSearch() {
            return PAGE_TYPE.TOKENS === this.type;
        },
        enableFilter() {
            return PAGE_TYPE.TOKENS === this.type;
        },
        enableChartsMarkets() {
            return PAGE_TYPE.COINS === this.type;
        },
    },
    created() {
        this.updateMarketsDebounce = debounce(this.updateMarketsExec, this.updateMarketsDelay);
    },
    async mounted() {
        try {
            this.listenForMarketsUpdate();
            this.updateBuyDepthHelp();
            await this.fetchConversionRates();
            await this.updateSanitizedMarkets();
        } finally {
            this.$emit('ready');
        }
    },
    methods: {
        toggleShowMore: function() {
            this.currentPage += 1;
            this.updateMarkets();
        },
        tokensForFilters: function(tokens) {
            this.tokens = tokens;
        },
        sortChanged: function(ctx) {
            this.sortBy = this.fields[ctx[0].field].sort;
            this.sortDesc = 'desc' === ctx[0].type ? true : false;
            this.currentPage = 1;
            this.selectedFilters = this.selectedFilters.filter(
                (element) => element !== this.marketFilters.options.newest_deployed.key
            );

            this.updateMarkets(1, false);
        },
        mapSortingField: function(field) {
            const fieldsToReplace = {
                [tradingTableColumns.combinedPairHolders]: tradingTableColumns.pair,
                [tradingTableColumns.combinedPriceChange]: tradingTableColumns.lastPrice,
                [tradingTableColumns.combinedVolumeCap]: this.getCombinedVolueCapField(),
            };

            return fieldsToReplace[field] || field;
        },
        getCombinedVolueCapField: function() {
            return this.marketCapOptions[this.activeVolumeCapOption]
                ? tradingTableColumns.marketCap
                : tradingTableColumns.volume;
        },
        toggleCrypto: function(cryptoSymbol) {
            this.currentCrypto = cryptoSymbol;
            this.searchPhrase = '';
            this.currentPage = 1;

            this.updateMarkets();
        },
        toggleFilter: function(filterKey) {
            if (!this.selectedFilters.includes(filterKey)) {
                this.marketFilters.selectedFilters.push(filterKey);
            } else if (this.filterCanBeRemoved(filterKey)) {
                this.selectedFilters = this.selectedFilters
                    .filter((el) => el !== filterKey);
            }

            this.searchPhrase = '';
            this.currentPage = 1;

            this.updateMarkets();
        },
        changeCrypto: function(crypto) {
            this.currentCrypto = crypto;
            this.updateMarkets(1);
        },
        filterCanBeRemoved: function(filterKey) {
            return !this.blockchainFilter(filterKey) || 1 !== this.selectedBlockchainsAmount;
        },
        blockchainFilter: function(filterKey) {
            let isBlockchainFilter = false;

            for (let i = 0; i < this.availableBlockchains.length; i++) {
                if (filterKey === this.marketFilters.options[`deployed${this.availableBlockchains[i]}`].key) {
                    isBlockchainFilter = true;

                    break;
                }
            }

            return isBlockchainFilter;
        },
        toggleSearch: function(searchPhrase) {
            this.searchPhrase = searchPhrase.length >= this.searchPhraseMinLength
                ? searchPhrase
                : '';
            this.currentPage = 1;

            this.updateMarkets();
        },
        listenForMarketsUpdate: function() {
            this.addMessageHandler((result) => {
                if ('state.update' === result.method) {
                    this.sanitizeMarket(result);
                    this.requestMonthInfo(result.params[0]);
                } else if (-1 != Array.from(this.stateQueriesIdsTokensMap.keys()).indexOf(result.id)) {
                    this.updateMonthVolume(result.id, result.result);
                }
            }, null, 'Trading');
        },
        updateRawMarkets: async function() {
            try {
                const params = this.createParams();
                this.cancelTokenSource = axios.CancelToken.source();

                const res = await this.$axios.retry.get(
                    this.$routing.generate('markets_info', params),
                    {cancelToken: this.cancelTokenSource.token},
                );

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
                this.proccessRawMarketsData(res.data);

                if (window.history.replaceState) {
                    // prevents browser from storing history with each change:
                    window.history.replaceState(
                        {}, document.title, this.$routing.generate('trading', {
                            sort: params.sort,
                            order: params.order,
                        })
                    );
                }
            } catch (err) {
                if (err instanceof axios.Cancel) {
                    return;
                }

                this.$logger.error('Can not update the markets data', err);
                this.notifyError(this.$t('trading.modal.cant_load_markets'));
            }
        },
        createParams: function() {
            const sort = this.mapSortingField(this.sortBy.replace(USD.symbol, ''));

            const params = {
                page: this.currentPage,
                sort,
                order: this.sortDesc ? 'DESC' : 'ASC',
                type: this.isCoinsTrading ? PAGE_TYPE.COINS : PAGE_TYPE.TOKENS,
            };

            if (0 !== this.searchPhrase.length) {
                params.searchPhrase = this.searchPhrase;

                return params;
            }

            const deployedOnlyFilters = this.availableBlockchains.reduce((acc, symbol) => {
                acc[this.marketFilters.options[`deployed${symbol}`].key] = this.filterDeployedOnly(symbol);

                return acc;
            }, {});

            const filtersMap = {
                ...deployedOnlyFilters,
                [this.marketFilters.options.airdrop.key]: this.filterAirdropOnly,
                [this.marketFilters.options.user_owns.key]: this.filterUserOwns,
                [this.marketFilters.options.newest_deployed.key]: this.filterNewestDeployed,
            };

            params.crypto = this.currentCrypto;
            params.filters = this.selectedFilters.map((filter) => filtersMap[filter] || null);

            return params;
        },
        filterDeployedOnly: function(symbol) {
            symbol = symbol == WEB.symbol ? MINTME.symbol : symbol;

            return this.filterForTokens[`deployed_only_${symbol.toLowerCase()}`] || 0;
        },
        proccessRawMarketsData: function(rawMarketsData) {
            this.lastPage = rawMarketsData.lastPage;
            const rawMarkets = rawMarketsData.markets;

            if (1 === this.currentPage) {
                this.markets = {};
            }

            for (const marketKey in rawMarkets) {
                if (rawMarkets.hasOwnProperty(marketKey)) {
                    this.$set(this.markets, marketKey, rawMarkets[marketKey]);
                }
            }
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
            const marketNetworks = market.networks;
            const marketPrecision = market.quote.priceDecimals || market.base.subunit;
            const supply = market.supply;
            const monthVolume = market.monthVolume;
            const buyDepth = market.buyDepth;
            const marketCap = !market.marketCap
                ? 0
                : market.marketCap;

            const marketOnTopIndex = this.getMarketOnTopIndex(marketCurrency, marketToken);

            const tokenized = this.isTokenized(market.quote.deploymentStatus);

            const baseImage = market.base.image.avatar_small;
            const quoteImage = this.getQuoteImage(market);

            const tokenizedImage = market.quote.hasOwnProperty('cryptoSymbol')
                ? this.cryptos[market.quote.cryptoSymbol].image.avatar_small
                : '';

            const createdOnMintmeSite = market.quote.createdOnMintmeSite || false;

            const sanitizedMarket = this.getSanitizedMarket(
                marketCurrency,
                marketToken,
                marketNetworks,
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
                tokenizedImage,
                market.quote.cryptoSymbol,
                marketCap,
                market.rank || 0,
                market.holders || 0,
                createdOnMintmeSite,
                market.isPromoted,
                !!market.quote.priceDecimals
            );
            if (-1 < marketOnTopIndex) {
                this.$set(this.sanitizedMarketsOnTop, marketOnTopIndex, sanitizedMarket);
            }
            this.$set(this.sanitizedMarkets, marketName, sanitizedMarket);

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
            connectedNetworks,
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
            tokenizedImage,
            cryptoSymbol,
            marketCap = 0,
            rank = 0,
            holders = 0,
            createdOnMintmeSite = false,
            isPromoted = false,
            isCustomDecimalPrice = false
        ) {
            const hiddenName = this.findHiddenName(token);
            const showDeployedIcon = tokenized && tokenizedImage && createdOnMintmeSite;

            return {
                pair: this.cryptos[token]
                    ? `${token}/${currency}`
                    : `${token}`,
                change: toMoney(changePercentage, 2),
                lastPrice: toMoney(lastPrice, subunit),
                lastPriceCurrency: USD.symbol,
                dayVolume: toMoney(dayVolume, 4),
                monthVolume: toMoney(monthVolume, 4),
                tokenUrl: isPromoted || (hiddenName && -1 !== hiddenName.indexOf('TOK'))
                    ? this.getTokenUrl(token, currency)
                    : this.$routing.generate('coin', {base: currency, quote: token}),
                lastPriceUSD: isCustomDecimalPrice
                    ? this.toUSD(lastPrice, currency, true, subunit)
                    : this.toUSD(lastPrice, currency, true),
                dayVolumeUSD: isCustomDecimalPrice
                    ? this.toUSD(dayVolume, currency, true, subunit)
                    : this.toUSD(dayVolume, currency, true),
                monthVolumeUSD: isCustomDecimalPrice
                    ? this.toUSD(monthVolume, currency, true, subunit)
                    : this.toUSD(monthVolume, currency, true),
                marketCap: toMoney(marketCap),
                marketCapUSD: this.toUSD(marketCap, currency),
                buyDepth: toMoney(buyDepth),
                buyDepthUSD: this.toUSD(buyDepth, currency),
                tokenized: tokenized,
                base: currency,
                quote: token,
                baseImage,
                quoteImage,
                tokenizedImage,
                cryptoSymbol,
                rank,
                holders,
                createdOnMintmeSite,
                showDeployedIcon,
                networks: this.generateNetworksInfo(connectedNetworks),
                isPromoted,
                subunit,
            };
        },
        getTokenUrl: function(name, crypto) {
            return this.$routing.generate('token_show_trade', {
                name: this.dashedString(name),
                crypto: this.rebrandingFunc(crypto),
            });
        },
        getMarketOnTopIndex: function(marketPair) {
            return this.cryptoTopListMarketKeys.indexOf(marketPair);
        },
        getPercentage: function(lastPrice, openPrice) {
            return openPrice ? (lastPrice - openPrice) * 100 / openPrice : 0;
        },
        updateSanitizedMarkets: async function() {
            this.sanitizedMarkets = {};
            const WEBSupply = this.isCoinsTrading
                ? await this.fetchWEBsupply()
                : 0;

            const groupedPromotedMarkets = Object.keys(this.promotedMarkets).reduce((acc, marketName) => {
                const selectedMarket = this.promotedMarkets[marketName];

                selectedMarket.isPromoted = true;
                selectedMarket.marketName = marketName;

                if (acc[selectedMarket.quote.name]) {
                    acc[selectedMarket.quote.name].push(selectedMarket);
                } else {
                    acc[selectedMarket.quote.name] = [selectedMarket];
                }

                return acc;
            }, {});

            for (const market in groupedPromotedMarkets) {
                if (groupedPromotedMarkets.hasOwnProperty[market]) {
                    continue;
                }

                const maxPriceMarket = groupedPromotedMarkets[market]
                    .map((m) => {
                        m.lastPriceUSD = this.toUSD(parseFloat(m.lastPrice), m.base.symbol, true);

                        return m;
                    })
                    .sort((a, b) => b.lastPriceUSD - a.lastPriceUSD)[0];

                await this.updateSanitizedMarket(maxPriceMarket.marketName, maxPriceMarket, WEBSupply);
            }

            for (const market in this.markets) {
                if (!this.markets.hasOwnProperty(market)) {
                    continue;
                }

                await this.updateSanitizedMarket(market, this.markets[market], WEBSupply);
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
        updateSanitizedMarket: async function(marketName, selectedMarket, WEBSupply) {
            const tokenized = this.isTokenized(selectedMarket.quote.deploymentStatus);
            const cryptoSymbol = selectedMarket.base.symbol;
            const tokenName = selectedMarket.quote.symbol;
            const marketNetworks = selectedMarket.networks;
            const marketOnTopIndex = this.getMarketOnTopIndex(marketName);

            if (-1 < marketOnTopIndex && cryptoSymbol === BTC.symbol && tokenName === WEB.symbol) {
                selectedMarket.supply = WEBSupply;
                this.updateWEBBTCMarket(this);
            } else {
                selectedMarket.supply = 1e7;
            }

            const tokenizedImage = selectedMarket.quote.hasOwnProperty('cryptoSymbol')
                ? this.cryptos[selectedMarket.quote.cryptoSymbol].image.avatar_small
                : '';

            if (this.isCoinsTrading) {
                const supply = WEB.symbol === tokenName
                    ? WEBSupply
                    : await this.fetchSupply(tokenName);
                selectedMarket.marketCap = Decimal.mul(supply, selectedMarket.lastPrice);
            }

            const createdOnMintmeSite = selectedMarket.quote.createdOnMintmeSite || false;
            const quoteImage = this.getQuoteImage(selectedMarket);
            const sanitizedMarket = this.getSanitizedMarket(
                cryptoSymbol,
                tokenName,
                marketNetworks,
                this.getPercentage(
                    parseFloat(selectedMarket.lastPrice),
                    parseFloat(selectedMarket.openPrice)
                ),
                parseFloat(selectedMarket.lastPrice),
                parseFloat(selectedMarket.dayVolume),
                parseFloat(selectedMarket.monthVolume),
                selectedMarket.supply,
                selectedMarket.quote.priceDecimals || selectedMarket.base.subunit,
                tokenized,
                parseFloat(selectedMarket.buyDepth),
                selectedMarket.base.image.avatar_small,
                quoteImage,
                tokenizedImage,
                selectedMarket.quote.cryptoSymbol,
                selectedMarket.marketCap || 0,
                selectedMarket.rank || 0,
                selectedMarket.holders || 0,
                createdOnMintmeSite,
                selectedMarket.isPromoted,
                !!selectedMarket.quote.priceDecimals,
            );
            if (-1 < marketOnTopIndex) {
                this.$set(this.sanitizedMarketsOnTop, marketOnTopIndex, sanitizedMarket);
            }
            this.$set(this.sanitizedMarkets, marketName, sanitizedMarket);
        },
        findHiddenName: function(tokenOrCrypto) {
            let result = null;

            for (const key in this.markets) {
                if (this.markets.hasOwnProperty(key) && null !== this.markets[key].quote) {
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

            const tokenized = this.isTokenized(market.quote.deploymentStatus);

            const marketOnTopIndex = this.getMarketOnTopIndex(market.base.symbol, market.quote.symbol);
            const tokenizedImage = market.quote.hasOwnProperty('cryptoSymbol')
                ? this.cryptos[market.quote.cryptoSymbol].image.avatar_small
                : '';

            const createdOnMintmeSite = market.quote.createdOnMintmeSite || false;

            const sanitizedMarket = this.getSanitizedMarket(
                market.base.symbol,
                market.quote.symbol,
                market.networks,
                this.getPercentage(
                    parseFloat(market.lastPrice),
                    parseFloat(market.openPrice)
                ),
                market.lastPrice,
                market.dayVolume,
                market.monthVolume = parseFloat(marketInfo.deal) + parseFloat(marketInfo.dealDonation),
                market.supply,
                market.quote.priceDecimals || market.base.subunit,
                tokenized,
                market.buyDepth,
                market.base.image.avatar_small,
                market.quote.image ? market.quote.image.avatar_small: '',
                tokenizedImage,
                market.quote.cryptoSymbol,
                market.marketCap || 0,
                market.rank || 0,
                market.holders || 0,
                createdOnMintmeSite,
                market.isPromoted,
                !!market.quote.priceDecimals,
            );

            if (-1 < marketOnTopIndex) {
                this.sanitizedMarketsOnTop[marketOnTopIndex] = sanitizedMarket;
            } else {
                this.sanitizedMarkets[marketName] = sanitizedMarket;
            }
        },
        requestMonthInfo: function(market) {
            if (!this.markets[market]) {
                return;
            }

            const id = parseInt(Math.random().toString().replace('0.', ''));
            this.sendMessage(JSON.stringify({
                method: 'state.query',
                params: [market, 30 * 24 * 60 * 60],
                id,
            }));

            this.stateQueriesIdsTokensMap.set(id, market);
        },
        fetchConversionRates: async function() {
            try {
                const response = await this.$axios.single.get(this.$routing.generate('exchange_rates'));

                if (!(response.data && Object.keys(response.data).length)) {
                    throw new Error();
                }

                this.conversionRates = response.data;
            } catch (err) {
                this.$emit('disable-usd');
                this.notifyError(this.$t('toasted.error.external'));
                this.$logger.error('Error fetching exchange rates for cryptos', err);
            }
        },
        toUSD: function(amount, currency, subunit = false, subunitValue = USD.subunit) {
            amount = Decimal.mul(amount, ((this.conversionRates[currency] || [])[USD.symbol] || 1));
            return subunit ? toMoney(amount, subunitValue) : this.toMoney(amount);
        },
        fetchSupply: async function(symbol) {
            if (this.cachedCryptoSupply[symbol]) {
                return this.cachedCryptoSupply[symbol];
            }

            this.cachedCryptoSupply[symbol] = await this.fetchCirculatingSupply(symbol);

            return this.cachedCryptoSupply[symbol];
        },
        fetchWEBsupply: async function() {
            const config = {
                'transformRequest': (data, headers) => {
                    delete headers['X-Requested-With'];
                    delete headers['X-CSRF-TOKEN'];
                    delete headers.common;
                },
            };

            let supply = 0;

            try {
                const res = await this.$axios.retry.get(this.mintmeSupplyUrl, config);
                supply = res.data;
            } catch (err) {
                this.$logger.error('Can not update MINTME circulation supply', err);
            }

            this.markets['WEBBTC'].supply = supply;

            return supply;
        },
        fetchCirculatingSupply: async function(symbol) {
            return this.$axios.retry.get(this.$routing.generate('markets_circulating_supply', {
                symbol,
            }))
                .then((res) => {
                    return res.data.circulatingSupply || 0;
                })
                .catch((err) => {
                    this.$logger.error('Can not load market cap', err);
                });
        },
        updateWEBBTCMarket: function() {
            let market = this.markets['WEBBTC'];
            market = this.getSanitizedMarket(
                market.base.symbol,
                market.quote.symbol,
                market.networks,
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
                '',
                market.quote.cryptoSymbol,
                market.marketCap,
                market.rank,
                market.holders
            );
            this.$set(this.sanitizedMarketsOnTop, 0, market);
        },
        /**
         * @param {string} deploymentStatus
         * @return {boolean} Returns boolean - is deployed token or not
         */
        isTokenized: function(deploymentStatus) {
            return deploymentStatus === tokenDeploymentStatus.deployed;
        },
        toMoney: function(val, subunit = 2) {
            val = new Decimal(val);
            const precision = val.lessThan(100)
                ? subunit
                : 0;
            return toMoney(val, precision);
        },
        toggleActiveVolume: function(volume) {
            this.activeVolume = volume;
        },
        setActiveMarketCap: function(marketCap) {
            this.activeMarketCap = marketCap;
        },
        setActiveVolumeCapOption(option) {
            if (Object.keys(this.volumes).includes(option)) {
                this.activeVolume = option;
            } else {
                this.activeMarketCap = option;
            }

            this.activeVolumeCapOption = option;
        },
        updateMarkets: function() {
            this.tableLoading = true;
            this.updateMarketsDebounce.cancel();
            this.updateMarketsDebounce();
            this.tableLoading = true;

            if (this.cancelTokenSource) {
                this.cancelTokenSource.cancel();
            }
        },
        updateMarketsExec: async function() {
            try {
                await this.updateRawMarkets();
                await this.updateSanitizedMarkets();
            } catch (error) {
                if (axios.isCancel(error)) {
                    return;
                }

                this.$logger.error('Can not update the markets data', error);
                this.notifyError(this.$t('trading.modal.cant_load_markets'));
            }

            this.tableLoading = false;
        },
        generateNetworksInfo(deployedNetworks) {
            if (!deployedNetworks) {
                return [];
            }

            return this.availableBlockchains.map((symbol) => {
                const deployed = !!deployedNetworks.includes(symbol);
                return {
                    'image': this.getSymbolIcon(symbol, deployed),
                    deployed,
                };
            });
        },
        avatarImg: function(crypto) {
            return require(`../../../img/${getCoinAvatarAssetName(crypto)}`);
        },
        getSymbolIcon(symbol, deployed) {
            return !deployed
                ? require(`../../../img/${getCoinAvatarAssetName(symbol, true)}`)
                : require(`../../../img/${getCoinAvatarAssetName(symbol)}`);
        },
        normalizeSortForRequest: function(sort) {
            sort = sort.replace(USD.symbol, '').replace('combined-', '');

            if ('pair-networks' === sort) {
                return 'pair';
            }

            if ('price-change' === sort) {
                return 'lastPrice';
            }

            return sort;
        },
        getTitleWithSymbol(number) {
            if (1000 > number) {
                return '';
            }

            return this.getTitleWithUsd(number);
        },
        getTitleWithMintme(number) {
            if (1000 > number) {
                return '';
            }

            return `${number} ${MINTME.symbol}`;
        },
        getTitleWithUsd(number) {
            if (1000 > number) {
                return '';
            }

            return `${number} USD`;
        },
        hasMarketCapValue(item) {
            return !(MINTME.symbol === item.base && parseFloat(item.monthVolume) < this.minimumVolumeForMarketcap
                || ETH.symbol === item.cryptoSymbol && !item.createdOnMintmeSite);
        },
        getMarketCapCellValue(value, item) {
            if (this.isMarketCapColumnActive && !this.hasMarketCapValue(item)) {
                return '-';
            }

            return this.numberTruncateWithLetterFunc(value);
        },
        getMarketCapCellTooltip(value, item) {
            return !this.isMarketCapColumnActive || this.hasMarketCapValue(item)
                ? this.getTitleWithSymbol(value)
                : null;
        },
        getQuoteImage(selectedMarket) {
            if (!selectedMarket.quote.tokenized && this.isCoinsTrading) {
                return this.avatarImg(selectedMarket.quote.symbol);
            }

            return selectedMarket.quote.image
                ? selectedMarket.quote.image.avatar_small
                : '';
        },
        updateBuyDepthHelp: function() {
            if (this.isCoinsTrading) {
                this.marketCapOptions.buyDepth.help = this.$t('trading.coin.buy_depth.help');
            }
        },
        markMarketsAsPromoted(marketsArr) {
            marketsArr.forEach((r) => r.isPromoted = true);
        },
    },
};
</script>
