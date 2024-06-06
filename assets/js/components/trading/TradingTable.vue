<template>
    <div class="row">
        <div class="promoted-border">
            <span>{{ $t('page.trading.promoted_label') }}</span>
        </div>
        <div class="col-12">
            <p v-if="!enableFilter" class="d-flex flex-wrap align-items-center">
                <span class="h2 font-weight-semibold">
                    {{ $t('trading.coins_2') }}
                    <span class="text-primary">
                        {{ $t('trading.title.trading') }}
                    </span>
                </span>
            </p>
            <trading-filters
                v-if="enableFilter"
                :market-filters="marketFiltersProp"
                :market-cap-options="marketCapOptions"
                :user-id="userId"
                :cryptos="cryptos"
                :current-crypto="currentCrypto"
                :tokens-count="tokensCount"
                :enable-search="enableSearch"
                :new-markets-enabled="newMarketsEnabled"
                :token-promotions="tokenPromotionsPrepared"
                @toggle-crypto="toggleCrypto"
                @toggle-filter="toggleFilter"
                @toggle-search="toggleSearch"
            ></trading-filters>
        </div>
        <div :class="classCheckTrading">
            <vue-good-table
                mode="remote"
                styleClass="data-table-trading"
                :row-style-class="getRowStyleClass"
                :columns="fieldsArray"
                :rows="tokensRows"
                @on-sort-change="sortChanged"
            >
                <template slot="table-column" slot-scope="props">
                    <span v-if="isSortingLabelHeaderColumn(props.column.field)">
                        <button class="sorting-arrows"></button>
                        {{ props.column.label }}
                        <guide
                            v-if="props.column.field === tradingTableColumns.marketCap"
                            tippy-class="position-relative aside-div d-inline-flex align-items-start"
                        >
                            <template slot="header">
                                {{ props.column.label }}
                            </template>
                            <template slot="body">
                                <span v-html="props.column.single_help"/>
                            </template>
                        </guide>
                    </span>
                    <!-- Rank column -->
                    <span v-else-if="props.column.field === tradingTableColumns.rank" class="ml-2">
                        <span class="d-inline d-sm-none">#</span>
                        <span class="d-none d-lg-inline">
                            {{ props.column.label }}
                        </span>
                        <guide
                            :reactive="true"
                            tippy-class="position-relative aside-div d-inline-flex align-items-start"
                            class-prop="d-none d-lg-inline"
                        >
                            <template slot="header">
                                {{ props.column.label }}
                            </template>
                            <template slot="body">
                                {{ props.column.single_help }}
                            </template>
                        </guide>
                    </span>
                    <!-- MarketCap column -->
                    <span v-else-if="props.column.field === tradingTableColumns.marketCap">
                        <button class="sorting-arrows"></button>
                        <b-dropdown
                            id="marketCap"
                            variant="primary"
                            :lazy="true"
                            boundary="viewport"
                        >
                            <template slot="button-content">
                                {{ props.column.label }}
                            </template>
                            <template>
                                <m-dropdown-item
                                    v-for="(option, key) in marketCapOptions"
                                    :key="key"
                                    @click="setActiveMarketCap(key)"
                                >
                                    {{ option.label }}
                                </m-dropdown-item>
                            </template>
                        </b-dropdown>
                        <guide
                            :reactive="true"
                            tippy-class="position-relative aside-div d-inline-flex align-items-start"
                        >
                            <template slot="header">
                                {{ props.column.label }}
                            </template>
                            <template slot="body">
                                {{ props.column.help }}
                            </template>
                        </guide>
                    </span>
                    <!-- Combined Volume & MarketCap column -->
                    <span v-else-if="props.column.field === tradingTableColumns.combinedVolumeCap">
                        <button class="sorting-arrows"></button>
                        <b-dropdown
                            id="volume"
                            variant="primary"
                            :lazy="true"
                            boundary="viewport"
                            class="position-static"
                        >
                            <template slot="button-content">
                                <span class="d-none d-md-inline">{{ props.column.label }}</span>
                                <span class="d-md-none">{{ props.column.short_label }}</span>
                            </template>
                            <m-dropdown-item
                                v-for="(option, key) in combinedVolumeCapOptions"
                                :key="key"
                                @click="changeVolumeCapOption(key)"
                            >
                                <span class="d-none d-md-inline">{{ option.label }}</span>
                                <span class="d-md-none">{{ option.short_label }}</span>
                            </m-dropdown-item>
                        </b-dropdown>
                    </span>
                    <!-- Volume column -->
                    <span v-else-if="props.column.field === tradingTableColumns.volume">
                        <button class="sorting-arrows"></button>
                        <b-dropdown
                            id="volume"
                            variant="primary"
                            :lazy="true"
                            boundary="viewport"
                            class="position-static"
                        >
                            <template slot="button-content">
                                <span class="d-none d-md-inline">{{ props.column.label }}</span>
                                <span class="d-md-none">{{ props.column.short_label }}</span>
                            </template>
                            <m-dropdown-item
                                v-for="(option, key) in volumes"
                                :key="key"
                                @click="toggleActiveVolume(key)"
                            >
                                <span class="d-none d-md-inline">{{ option.label }}</span>
                                <span class="d-md-none">{{ option.short_label }}</span>
                            </m-dropdown-item>
                        </b-dropdown>
                    </span>
                </template>
                <template slot="table-row" slot-scope="props">
                    <span v-if="props.column.field === tradingTableColumns.rank">
                        {{ 0 === props.row.rank ? '' : props.row.rank }}
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.pair">
                        <div class="d-flex align-items-center position-relative pair-name-wrp">
                            <coin-avatar
                                v-if="isCoinsTrading"
                                class="mr-1"
                                :symbol="props.row.base"
                                is-crypto
                            />
                            <avatar
                                v-else
                                :image="props.row.quoteImage"
                                type="token"
                                size="small"
                                :symbol="props.row.base"
                                :key="props.row.quoteImage"
                                class="mr-1"
                            />
                            <a
                                :id="props.row.pair"
                                :href="props.row.tokenUrl | rebranding"
                                class="pair-name"
                            >
                                {{ props.row.pair }}
                            </a>
                            <b-tooltip
                                :target="props.row.pair"
                                custom-class="tooltip-custom"
                                boundary="viewport"
                                :title="props.row.pair"
                                :disabled="disabledTooltip(props.row.pair)"
                            />
                        </div>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.combinedPairHolders">
                        <div class="d-flex align-items-center position-relative pair-name-wrp">
                            <coin-avatar
                                v-if="isCoinsTrading"
                                class="mr-1"
                                :symbol="props.row.base"
                                is-crypto
                            />
                            <avatar
                                v-else
                                :image="props.row.quoteImage"
                                type="token"
                                size="small"
                                :symbol="props.row.base"
                                :key="props.row.quoteImage"
                                class="mr-1"
                            />
                            <a
                                :id="props.row.pair"
                                :href="props.row.tokenUrl | rebranding"
                                class="pair-name"
                            >
                                {{ props.row.pair }}
                            </a>
                            <b-tooltip
                                :target="props.row.pair"
                                custom-class="tooltip-custom"
                                boundary="viewport"
                                :title="props.row.pair"
                                :disabled="disabledTooltip(props.row.pair)"
                            />
                        </div>
                        <div
                            v-if="!isCoinsTrading"
                            class="mt-1"
                        >
                            {{ props.row.holders }} {{ $t('trading.fields.holders_plural') }}
                        </div>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.change">
                        <span
                            v-b-tooltip.hover
                            :title="fixedPercentage(props.row.change)"
                            class="text-center"
                            :class="getClassForChangeRow(parseFloat(props.row.change))"
                        >
                            {{ props.row.change | toFixed }}%
                        </span>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.combinedPriceChange">
                        <table-numeric-value
                            :value="props.row.lastPrice"
                            :value-usd="props.row.lastPriceUSD"
                            :symbol="props.row.base"
                            :subunit="props.row.subunit"
                            value-abbreviation
                        />
                        <div class="text-right mt-1">
                            {{ props.row.change | toFixed }}%
                        </div>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.lastPrice">
                        <table-numeric-value
                            :value="props.row.lastPrice"
                            :value-usd="props.row.lastPriceUSD"
                            :symbol="props.row.base"
                            :subunit="props.row.subunit"
                            value-abbreviation
                        />
                    </span>
                    <span v-else-if="props.column.field === 'pair'">
                        <coin-avatar
                            v-if="isCoinsTrading"
                            class="mr-1"
                            :symbol="props.row.base"
                            is-crypto
                        />
                        <avatar
                            v-else
                            :image="props.row.quoteImage"
                            type="token"
                            size="small"
                            :symbol="props.row.base"
                            class="deployed-avatar align-middle mb-1"
                            :key="props.row.quoteImage"
                        />
                        <div class="text-right mt-1">
                            {{ props.row.change | toFixed }}%
                        </div>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.networks">
                        <div class="d-flex justify-content-center">
                            <div
                                v-for="(market, index) in props.row.networks"
                                :key="index"
                            >
                                <avatar
                                    v-if="market.deployed"
                                    :image="market.image"
                                    type="token"
                                    size="small"
                                    class="d-inline"
                                    :key="market.image"
                                />
                            </div>
                        </div>
                    </span>
                    <span v-else-if="isNumericValueColumn(props.column.field)">
                        <table-numeric-value
                            :value="props.row[props.column.key]"
                            :value-usd="props.row[props.column.key]"
                            :symbol="props.row.base"
                        />
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.tokenUrl">
                        <a
                            class="btn btn-primary font-weight-bold trading-buy-link"
                            :href="props.row.tokenUrl | rebranding"
                        >
                            {{ $t('trading.buy') }}
                        </a>
                    </span>
                    <span v-else-if="props.column.field === tradingTableColumns.dotOption">
                        <b-dropdown
                            id="marketCap"
                            variant="primary"
                            :lazy="true"
                            boundary="viewport"
                            class="menu-buy-dot"
                        >
                            <template slot="button-content">
                                <font-awesome-icon icon="ellipsis-h" />
                            </template>
                            <template>
                                <b-dropdown-item
                                    :href="props.row.tokenUrl | rebranding"
                                >
                                    {{ $t('trading.buy') }}
                                </b-dropdown-item>
                            </template>
                        </b-dropdown>
                    </span>
                </template>
                <template slot="emptystate">
                    <div v-if="tableLoading" class="d-flex justify-content-center p-3">
                        <div class="spinner-border text-light" role="status">
                            <span class="sr-only">
                                {{ $t('page.trading.loading') }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-center p-3">{{ $t('trading.no_data') }}</div>
                </template>
            </vue-good-table>
            <div v-if="displayShowMoreButton" class="text-center mt-4">
                <span
                    ref="toggleShowMore"
                    role="button"
                    class="btn btn-lg button-secondary rounded-pill c-pointer"
                    @click="toggleShowMore"
                >
                    <span class="py-2 px-3">
                        {{ $t('see_more') }}
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import _ from 'lodash';
import {
    getTokenOnBlockchainMsg,
    getTokenIconBySymbol,
} from '../../utils';
import {VueGoodTable} from 'vue-good-table';
import TradingFilters from './TradingFilters.vue';
import Avatar from '../Avatar';
import CoinAvatar from '../CoinAvatar';
import {MDropdownItem} from '../UI';
import {
    BDropdown,
    BDropdownItem,
    BTooltip,
    VBTooltip,
} from 'bootstrap-vue';
import {
    ETH,
    MINTME,
    MAX_NUMBER_1K,
} from '../../utils/constants';
import {
    RebrandingFilterMixin,
    MoneyFilterMixin,
    FiltersMixin,
    NumberAbbreviationFilterMixin,
} from '../../mixins/';
import Decimal from 'decimal.js';
import 'vue-good-table/dist/vue-good-table.css';
import TableNumericValue from './TableNumericValue';
import Guide from '../Guide';
import {tradingTableColumns} from './Trading';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEllipsisH} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import throttle from 'lodash/throttle';

library.add(faEllipsisH);

export default {
    name: 'TradingTable',
    components: {
        BDropdown,
        BTooltip,
        BDropdownItem,
        MDropdownItem,
        TradingFilters,
        VueGoodTable,
        Avatar,
        CoinAvatar,
        TableNumericValue,
        Guide,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        RebrandingFilterMixin,
        MoneyFilterMixin,
        FiltersMixin,
        NumberAbbreviationFilterMixin,
    ],
    props: {
        tokensCount: Number,
        currentCrypto: String,
        cryptos: Object,
        userId: Number,
        showUsd: {
            type: Boolean,
            default: true,
        },
        sortByProp: String,
        marketFiltersProp: Object,
        fields: Object,
        volumes: Object,
        marketCapOptions: Object,
        combinedVolumeCapOptions: Object,
        minimumVolumeForMarketCap: Number,
        tableLoading: Boolean,
        tokensProp: Object,
        lastPage: Boolean,
        tokenPromotions: Array,
        enableSearch: {
            type: Boolean,
            default: true,
        },
        enableFilter: {
            type: Boolean,
            default: true,
        },
        isCoinsTrading: Boolean,
        newMarketsEnabled: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            getTokenOnBlockchainMsg,
            tokenNameTruncateLength: 17,
            tradingTableColumns: tradingTableColumns,
            handleResizeThrottled: null,
        };
    },
    computed: {
        volumesMarket: function() {
            return this.showUsd
                ? {
                    monthVolume: 'monthVolumeUSD',
                    dayVolume: 'dayVolumeUSD',
                }
                : {
                    monthVolume: 'monthVolume',
                    dayVolume: 'dayVolume',
                };
        },
        tokensRows: function() {
            let tokens = Object.values(this.tokensProp);
            const lastPromotedIndex = tokens.reduce((lastIdx, token, index) => token.isPromoted ? index : lastIdx, -1);
            if (-1 !== lastPromotedIndex) {
                const lastPromotedToken = tokens[lastPromotedIndex];
                const promotedMarkets = tokens.slice(0, lastPromotedIndex + 1);

                // inserting dumb items at start and end of promotion block to use them as spacers for border
                tokens = [
                    {...lastPromotedToken, isFirstPromoted: true},
                    ...promotedMarkets,
                    {...lastPromotedToken, isLastPromoted: true},
                    ...tokens.slice(lastPromotedIndex + 1),
                ];
            }

            return this.sanitizedTokens(tokens);
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        isTokensRowEmpty: function() {
            return 0 === this.tokensRows.length;
        },
        displayShowMoreButton: function() {
            return !this.tableLoading &&
                !this.lastPage &&
                !this.isCoinsTrading &&
                !this.isTokensRowEmpty;
        },
        classCheckTrading: function() {
            return this.isCoinsTrading
                ? 'col-12 p-0'
                : 'col-12 p-0 table-responsive-sm';
        },
        tokenPromotionsPrepared: function() {
            const markets = Object.values(this.tokensProp);

            return this.tokenPromotions.map((promotion) => {
                const market = markets
                    .filter((m) => m.quote === promotion.token.name)
                    .sort((a, b) => b.lastPriceUSD - a.lastPriceUSD)[0];

                promotion.token.price = market ? market.lastPriceUSD : 0;

                return promotion;
            });
        },
    },
    mounted() {
        this.handleResizeThrottled = throttle(() => {
            this.setPromotedBorderLocation();
        }, 200);

        window.addEventListener('resize', this.handleResizeThrottled);
    },
    destroyed() {
        window.removeEventListener('resize', this.handleResizeThrottled);
    },
    methods: {
        getRowStyleClass(row) {
            if (row.isFirstPromoted) {
                return 'first-promoted';
            }

            if (row.isLastPromoted) {
                return 'last-promoted';
            }

            if (row.isPromoted) {
                return 'promoted';
            }
        },
        fixedPercentage(value) {
            return value < MAX_NUMBER_1K ? '' : `${new Decimal(value).toDP(0)} %`;
        },
        sanitizedTokens: function(tokens) {
            if ('' === this.sortByProp) {
                tokens.sort(this.sortTokens());
            }

            tokens = _.map(tokens, (token) => {
                return _.mapValues(token, (item, key) => {
                    return 'quoteImage' !== key && 'tokenUrl' !== key
                        ? this.rebrandingFunc(item)
                        : item;
                });
            });

            tokens = tokens.filter((token) => {
                if (token.isPromoted) {
                    return true;
                }

                return this.isCoinsTrading ? !token.tokenized : token.tokenized;
            });

            return tokens;
        },
        sortTokens: function(first, second) {
            return new Decimal(second.rank).cmp(first.rank);
        },
        setActiveMarketCap: function(marketCap) {
            this.$emit('set-active-marketCap', marketCap);
        },
        toggleCrypto: function(cryptoSymbol) {
            this.$emit('toggle-crypto', cryptoSymbol);
        },
        toggleSearch: function(searchPhrase) {
            this.$emit('toggle-search', searchPhrase);
        },
        sortChanged: function(ctx) {
            this.$emit('sort-changed', ctx);
        },
        toggleActiveVolume: function(volume) {
            this.$emit('toggle-active-volume', volume);
        },
        changeVolumeCapOption: function(option) {
            this.$emit('set-active-volume-cap', option);
        },
        tokensForFilters: function(tokens) {
            this.$emit('tokens-for-filters', tokens);
        },
        toggleSeeMoreButton(tokens) {
            this.showMore = tokens.length < this.tokensOnPage || 0 >= tokens.length;
        },
        dispatchToggleShowMore: function(page) {
            this.$emit('toggle-show-more', page);
        },
        toggleShowMore: function() {
            this.$emit('toggle-show-more');
        },
        toggleFilter: function(value) {
            this.$emit('toggle-filter', value);
        },
        marketCapFormatter: function(value, key, item) {
            return MINTME.symbol === item.base && parseFloat(item.monthVolume) < this.minimumVolumeForMarketcap
                || ETH.symbol === item.cryptoSymbol && !item.createdOnMintmeSite
                ? '-'
                : value;
        },
        disabledTooltip: function(pair) {
            return pair.length < this.tokenNameTruncateLength;
        },
        getClassForChangeRow: function(val) {
            if (0 === val) {
                return '';
            }

            return 0 < val ? 'text-success' : 'text-danger';
        },
        getDeployedAvatar: function(cryptoSymbol) {
            return require(`../../../img/${getTokenIconBySymbol(cryptoSymbol)}`);
        },
        isNumericValueColumn: function(column) {
            return [
                tradingTableColumns.lastPrice,
                tradingTableColumns.volume,
                tradingTableColumns.marketCap,
                tradingTableColumns.combinedVolumeCap,
            ].includes(column);
        },
        isSortingLabelHeaderColumn: function(column) {
            const columnsList = [
                tradingTableColumns.lastPrice,
                tradingTableColumns.change,
                tradingTableColumns.combinedPriceChange,
                tradingTableColumns.holders,
            ];

            if (!this.isCoinsTrading) {
                columnsList.push(tradingTableColumns.marketCap);
            }

            return columnsList.includes(column);
        },
        setPromotedBorderLocation() { // hacky way to implement yellow border inside of table
            if (!this.tokenPromotions) {
                return;
            }

            // wait until row is rendered
            if (!document.querySelector('.first-promoted')) {
                setTimeout(() => this.setPromotedBorderLocation(), 1000);

                return;
            }

            const firstPromoted = document.querySelector('.first-promoted').getBoundingClientRect();
            const lastPromoted = document.querySelector('.last-promoted').getBoundingClientRect();
            const trading = document.querySelector('#trading').getBoundingClientRect();
            const promotedBorderEl = document.querySelector('.promoted-border');

            promotedBorderEl.style.top = (firstPromoted.top - trading.top - 12) + 'px';
            promotedBorderEl.style.height = (lastPromoted.top - firstPromoted.top - 6) + 'px';
            promotedBorderEl.style.visibility = 'visible';
        },
    },
    watch: {
        tokensProp: function() {
            this.setPromotedBorderLocation();
        },
    },
};
</script>
