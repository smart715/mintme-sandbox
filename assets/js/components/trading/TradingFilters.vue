<template>
    <div class="row">
        <div class="col-12">
            <p class="d-flex flex-wrap align-items-center">
                <span class="h2 font-weight-semibold">
                    {{ $t('trading.tokens') }}
                    <span class="text-primary">
                        {{ $t('trading.title.trading') }}
                    </span>
                </span>
                <span class="text-primary-darker ml-2 d-flex flex-wrap">
                    <span class="font-weight-bold">
                        {{ tokensCount }}
                        <span class="font-weight-normal">
                            {{ $t('trading.deployed_tokens') }}
                        </span>
                    </span>
                </span>
            </p>
            <span class="h2 font-weight-semibold">
                {{ $t('trading.deployed') }}
                <span class="text-primary">
                    {{ $t('trading.on') }}
                </span>
            </span>
        </div>
        <promoted-tokens-slider :promotions="tokenPromotions" />
        <div class="col-12 mt-3">
            <div class="d-flex align-content-between flex-wrap c-pointer">
                <div
                    class="btn mr-3 mb-3 py-2 px-3 rounded font-weight-bold"
                    v-for="filter in marketFiltersOptions.deployedOn"
                    :key="filter.key"
                    :class="activeFilter(filter.key)"
                    @click="toggleFilter(filter.key)"
                >
                    <span>
                        {{ filter.label }}
                    </span>
                </div>
            </div>
            <div class="d-flex align-content-between flex-wrap c-pointer mt-3">
                <div
                    class="btn mr-3 mb-3 py-2 px-3 rounded font-weight-bold"
                    v-for="filter in marketFiltersOptions.other"
                    :key="filter.key"
                    :value="filter.label"
                    :class="activeFilter(filter.key)"
                    @click="toggleFilter(filter.key)"
                >
                    <span>
                        {{ filter.label }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="row">
                <div class="col-sm-12 col-md-8 d-flex align-items-center">
                    <ul v-if="newMarketsEnabled" class="nav mt-3">
                        <li v-for="crypto in cryptos" :key="crypto.symbol" class="nav-item">
                            <a
                                class="nav-link text-decoration-none font-weight-semibold"
                                :class="activeCrypto(crypto.symbol)"
                                href="#"
                                @click.prevent="toggleCrypto(crypto.symbol)"
                            >
                                {{ crypto.symbol | rebranding }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div v-if="enableSearch" class="col-sm-12 col-md-4">
                    <div class="d-flex flex-row-reverse container-input-search
                        justify-content-center justify-content-md-start">
                        <m-input
                            v-model="searchPhrase"
                            :invalid="showSearchPhraseInvalidError"
                            class="no-spacer max-search-input"
                            @keyup="handleKeyUp($event)"
                            :label="$t('trading.search.input')"
                            :max-length="60"
                        >
                            <template v-slot:postfix-icon>
                                <span class="p-1 text-primary">
                                    <font-awesome-icon icon="search" />
                                </span>
                            </template>
                            <template v-slot:errors>
                                <div v-if="showSearchPhraseInvalidError">
                                    {{ $t('trading.search.search_phrase_invalid_length', translationsContext) }}
                                </div>
                            </template>
                        </m-input>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faSearch} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {MInput} from '../UI';
import {
    RebrandingFilterMixin,
    MoneyFilterMixin,
} from '../../mixins/';
import debounce from 'lodash/debounce';
import PromotedTokensSlider from './PromotedTokensSlider';

library.add(faSearch);

export default {
    name: 'TradingFilters',
    components: {
        FontAwesomeIcon,
        MInput,
        PromotedTokensSlider,
    },
    mixins: [
        RebrandingFilterMixin,
        MoneyFilterMixin,
    ],
    props: {
        marketFilters: Object,
        marketCapOptions: Object,
        userId: Number,
        cryptos: Object,
        currentCrypto: String,
        tokensCount: Number,
        tokenPromotions: Array,
        enableSearch: {
            type: Boolean,
            default: true,
        },
        newMarketsEnabled: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            searchPhrase: '',
            tableMarketFilters: this.marketFilters,
            searchTyping: false,
            searchPhraseInputTimer: 1000,
            searchPhraseCheckValidTimer: 300,
            searchPhraseMinLength: 3,
            debouncedToggleSearch: null,
            debouncedCheckValid: null,
            preventSearchPhraseWatcher: false,
            showSearchPhraseInvalidError: false,
        };
    },
    created() {
        this.debouncedToggleSearch = debounce(this.toggleSearch, this.searchPhraseInputTimer);
        this.debouncedCheckValid = debounce(this.searchPhraseInvalidLength, this.searchPhraseCheckValidTimer);
    },
    computed: {
        marketFiltersOptions: function() {
            const options = Object.values(this.marketFilters.options);
            const userKey = this.marketFilters.options.user_owns.key;
            const isBlockchain = (filter) => filter.key.includes('deployed') && !filter.key.includes('_');

            return options.reduce((acc, filter) => {
                if (isBlockchain(filter)) {
                    acc.deployedOn.push(filter);
                } else if (userKey !== filter.key || this.userId) {
                    acc.other.push(filter);
                }
                return acc;
            }, {deployedOn: [], other: []});
        },
        translationsContext: function() {
            return {
                minPhraseLength: this.searchPhraseMinLength,
            };
        },
    },
    methods: {
        handleKeyUp: function(event) {
            if ('Backspace' === event.key || 'Delete' === event.key) {
                if (this.searchPhraseInvalidLength(this.searchPhrase)) {
                    this.$emit('toggle-search', '');
                }
            }
        },
        activeFilter: function(filterKey) {
            return this.marketFilters.selectedFilters.includes(filterKey)
                ? 'btn-primary'
                : 'btn-dark';
        },
        activeCrypto: function(cryptoSymbol) {
            return this.currentCrypto === cryptoSymbol
                ? 'active underline'
                : '';
        },
        toggleCrypto: function(cryptoSymbol) {
            this.$emit('toggle-crypto', cryptoSymbol);
            this.preventSearchPhraseWatcher = true;
            this.searchPhrase = '';
        },
        toggleFilter: function(filterKey) {
            this.$emit('toggle-filter', filterKey);
            this.preventSearchPhraseWatcher = true;
            this.searchPhrase = '';
        },
        searchPhraseInvalidLength: function(searchPhrase) {
            return this.showSearchPhraseInvalidError = searchPhrase.length < this.searchPhraseMinLength
                    && 0 < searchPhrase.length;
        },
        toggleSearch: function(searchPhrase) {
            this.$emit('toggle-search', searchPhrase);
        },
    },
    watch: {
        searchPhrase: function(searchPhrase) {
            if (this.preventSearchPhraseWatcher) {
                this.preventSearchPhraseWatcher = false;
            } else {
                this.debouncedCheckValid.cancel();
                this.debouncedCheckValid(searchPhrase);
                this.debouncedToggleSearch.cancel();
                this.debouncedToggleSearch(searchPhrase);
            }
        },
    },
};
</script>
