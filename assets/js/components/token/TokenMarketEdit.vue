<template>
    <div class="token-markets-edit mt-2">
        <div v-if="dataLoaded" class="row m-0">
            <div class="card col-12 col-md-7 p-3 order-1 order-md-0">
                <h5 class="card-title">{{ $t('page.token_settings.tab.markets') }}</h5>
                <div
                    v-for="(crypto, index) in tokenCryptos"
                    :key="crypto.cryptoSymbol"
                    class="market-item mb-2"
                    :class="{'is-opened': marketExists(crypto.cryptoSymbol)}"
                >
                    <a
                        v-if="marketExists(crypto.cryptoSymbol)"
                        class="p-2 pb-0 mb-0 h5 d-flex align-items-center justify-content-between market-toggle"
                        :class="{'not-collapsed': openedMarketCollapse === index}"
                        :href="getCryptoMarketLink(crypto.cryptoSymbol)"
                        target="_blank"
                    >
                        <div class="d-flex align-items-center">
                            <coin-avatar
                                :symbol="crypto.cryptoSymbol"
                                :is-crypto="true"
                                image-class="coin-avatar-md"
                                class="mr-2"
                            />
                            {{ crypto.cryptoSymbol | rebranding }} {{ $t('market') }}
                        </div>
                        <font-awesome-icon class="text-green mr-2 my-2" :icon="['fas', 'check-circle']" />
                    </a>
                    <template v-else>
                        <div
                            class="p-2 pb-0 mb-0 h5 d-flex align-items-center justify-content-between market-toggle"
                            :class="{'not-collapsed': openedMarketCollapse === index}"
                            @click="toggleMarketCollapse(index, crypto.cryptoSymbol)"
                        >
                            <div class="d-flex align-items-center">
                                <coin-avatar
                                    :symbol="crypto.cryptoSymbol"
                                    :is-crypto="true"
                                    image-class="coin-avatar-md"
                                    class="mr-2"
                                />
                                {{ crypto.cryptoSymbol | rebranding }} {{ $t('market') }}
                            </div>
                            <m-button type="primary">
                                {{ $t('create') }}
                            </m-button>
                        </div>
                        <b-collapse :id="`market-${index}`" accordion="markets-accordion" class="px-3 pb-3">
                            <div class="pb-1">
                                <span v-html="getTokenMarketCostOpeningText(crypto.cryptoSymbol)" />
                                <span class="text-primary">
                                    {{ getCost(crypto.cryptoSymbol) | toMoney }}
                                    {{ getRebrandedSelectedCurrency(crypto.cryptoSymbol) }}
                                </span>
                            </div>
                            <m-dropdown
                                :text="getRebrandedSelectedCurrency(crypto.cryptoSymbol)"
                                :label="$t('market.edit.currency.label')"
                                type="primary"
                                class="mb-2"
                            >
                                <template v-slot:button-content>
                                    <div class="d-flex align-items-center flex-fill">
                                        <coin-avatar
                                            :symbol="getSelectedCurrency(crypto.cryptoSymbol)"
                                            :is-crypto="true"
                                            class="mb-1 mr-1"
                                        />
                                        <span class="text-truncate">
                                            {{ getRebrandedSelectedCurrency(crypto.cryptoSymbol) }}
                                        </span>
                                    </div>
                                </template>
                                <m-dropdown-item
                                    v-for="option in options"
                                    :key="option"
                                    :value="option"
                                    :class="dropdownItemClass(crypto.cryptoSymbol, option)"
                                    @click="onSelect(crypto.cryptoSymbol, option)"
                                >
                                    <coin-avatar
                                        :symbol="option"
                                        :is-crypto="true"
                                    />
                                    {{ option | rebranding }}
                                </m-dropdown-item>
                                <template v-slot:hint>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            {{ $t('token.market.balance') }}
                                            {{ getBalance(crypto.cryptoSymbol) | toMoney }}
                                            {{ getRebrandedSelectedCurrency(crypto.cryptoSymbol) }}
                                            <div
                                                v-if="checkIsInsufficientFunds(crypto.cryptoSymbol)"
                                                class="text-danger font-size-90"
                                            >
                                                {{ $t('token.market.insufficient_funds') }}
                                            </div>
                                        </div>
                                        <span
                                            :class="getDepositDisabledClasses(selectedCurrency, false)"
                                            class="link-primary"
                                            @click="openDepositModal(selectedCurrency)"
                                        >
                                            {{ $t('token.market.add_more_funds') }}
                                        </span>
                                    </div>
                                </template>
                            </m-dropdown>
                            <div class="d-flex">
                                <m-button
                                    type="primary"
                                    :loading="isMarketProcessing(crypto.cryptoSymbol)"
                                    :disabled="canCreateMarket(crypto.cryptoSymbol)"
                                    @click="openMarket(crypto.cryptoSymbol, getSelectedCurrency(crypto.cryptoSymbol))"
                                >
                                    <template v-slot:prefix>
                                        <font-awesome-icon :icon="['far', 'check-square']" class="mr-2" />
                                    </template>
                                    <span v-html="getMarketEditOpenText(crypto.cryptoSymbol)" />
                                </m-button>
                                <m-button
                                    type="link"
                                    class="ml-2"
                                    @click="toggleMarketCollapse(index, crypto.cryptoSymbol)"
                                >
                                    {{ $t('cancel') }}
                                </m-button>
                            </div>
                        </b-collapse>
                    </template>
                </div>
            </div>
            <div class="card col p-3 ml-0 ml-md-2 mb-2 mb-md-0">
                <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                <span v-html="$t('market.edit.info', translationsContext)" />
            </div>
        </div>
        <div v-else class="p-5 text-center">
            <span v-if="serviceUnavailable">
                {{ this.$t('toasted.error.service_unavailable_short') }}
            </span>
            <div v-else class="spinner-border spinner-border-sm" role="status"></div>
        </div>
        <deposit-modal
            v-if="null !== selectedCurrency"
            :visible="showDepositModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(selectedCurrency)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import {
    RebrandingFilterMixin,
    MoneyFilterMixin,
    NotificationMixin,
    DepositModalMixin,
} from '../../mixins';
import {
    webSymbol,
    HTTP_INTERNAL_SERVER_ERROR,
} from '../../utils/constants';
import {generateCoinAvatarHtml} from '../../utils';
import {mapGetters} from 'vuex';
import {MDropdown, MDropdownItem, MButton} from '../UI';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';
import {faCheckCircle} from '@fortawesome/free-solid-svg-icons';
import {
    BCollapse,
    VBToggle,
} from 'bootstrap-vue';
import CoinAvatar from '../CoinAvatar';
import DepositModal from '../modal/DepositModal';

library.add(faCheckSquare, faCheckCircle);

export default {
    name: 'TokenMarketEdit',
    components: {
        FontAwesomeIcon,
        MDropdown,
        MDropdownItem,
        MButton,
        CoinAvatar,
        DepositModal,
        BCollapse,
    },
    directives: {
        'b-toggle': VBToggle,
    },
    mixins: [
        RebrandingFilterMixin,
        MoneyFilterMixin,
        NotificationMixin,
        DepositModalMixin,
    ],
    props: {
        tokenName: String,
        disabledServicesConfig: String,
        isCreatedOnMintmeSite: Boolean,
        isOwner: Boolean,
    },
    data() {
        return {
            options: [
                webSymbol,
            ],
            selectedCurrencies: {},
            preselectedCurrency: webSymbol,
            costs: {},
            marketProcessing: null,
            openedMarketCollapse: null,
            selectedCurrency: webSymbol,
        };
    },
    async beforeMount() {
        try {
            const request = await this.$axios.single.get(this.$routing.generate('markets_costs'));

            const costs = request.data;

            this.options.push(...Object.keys(costs));
            this.costs = costs;
        } catch (err) {
            if (HTTP_INTERNAL_SERVER_ERROR === err.response.status && err.response.data.error) {
                this.notifyError(err.response.data.error);
            } else {
                this.notifyError(this.$t('toasted.error.try_reload'));
            }

            this.$logger.error('error', 'Markets costs response error', err);
        }
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('market', {
            existingMarkets: 'getMarkets',
        }),
        tokenCryptos: function() {
            return this.balances
                ? this.cryptosToArray(this.balances)
                    .filter((crypto) => {
                        const possibleMarket = webSymbol !== crypto.cryptoSymbol && crypto.exchangeble;

                        if (possibleMarket) {
                            this.$set(
                                this.selectedCurrencies,
                                crypto.cryptoSymbol,
                                this.preselectedCurrency
                            );
                        }

                        return possibleMarket;
                    })
                : null;
        },
        dataLoaded: function() {
            return this.balances && 0 < Object.keys(this.costs).length && this.tokenCryptos;
        },
        translationsContext: function() {
            return {
                availableMarkets: this.tokenCryptos.reduce((acc, crypto) => {
                    if ('' !== acc) {
                        acc += ', ';
                    }

                    acc += generateCoinAvatarHtml({symbol: crypto.name, isCrypto: true});

                    return acc;
                }, ''),
            };
        },
        disabledServices: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
    },
    methods: {
        openMarket: async function(marketSymbol, payCryptoSymbol) {
            this.marketProcessing = marketSymbol;

            try {
                await this.$axios.retry.post(this.$routing.generate('token_crypto_create'), {
                    marketCrypto: marketSymbol,
                    payCrypto: payCryptoSymbol,
                    tokenName: this.tokenName,
                });

                this.notifySuccess(this.$t('token.market.created', {symbol: marketSymbol}));

                setTimeout(() => {
                    location.href = this.getCryptoMarketLink(marketSymbol);
                }, 1500);
            } catch (err) {
                this.notifyError(err.response.data.message);
                this.$logger.error('error', 'Error while creating market', err);
            } finally {
                this.marketProcessing = null;
            }
        },
        getCryptoMarketLink: function(marketSymbol) {
            return this.$routing.generate('token_show_trade', {
                name: this.tokenName,
                crypto: marketSymbol,
            });
        },
        marketExists: function(marketSymbol) {
            return !!this.existingMarkets[marketSymbol];
        },
        checkIsInsufficientFunds: function(marketSymbol) {
            return new Decimal(this.getBalance(marketSymbol)).lessThan(this.getCost(marketSymbol));
        },
        onSelect: function(symbol, newCurrency) {
            this.selectedCurrency = newCurrency;

            if (this.selectedCurrencies[symbol] !== newCurrency) {
                this.$set(this.selectedCurrencies, symbol, newCurrency);
            }
        },
        getSelectedCurrency: function(symbol) {
            return this.selectedCurrencies[symbol];
        },
        getRebrandedSelectedCurrency: function(symbol) {
            return this.rebrandingFunc(this.selectedCurrencies[symbol]);
        },
        cryptosToArray: function(cryptos) {
            Object.keys(cryptos).map(function(key) {
                if (!cryptos[key].owner) {
                    cryptos[key].name = key;
                }
            });

            return Object.values(cryptos);
        },
        otherMarketsProcessing: function(symbol) {
            return this.marketProcessing && this.marketProcessing !== symbol;
        },
        isMarketProcessing: function(symbol) {
            return this.marketProcessing && this.marketProcessing === symbol;
        },
        dropdownItemClass: function(cryptoSymbol, option) {
            return {
                'active': this.selectedCurrencies[cryptoSymbol] === option,
            };
        },
        getTranslationsContextBySymbol: function(symbol, isDark) {
            return {
                currencyBlock: generateCoinAvatarHtml({symbol, isCrypto: true, isDark}),
                currency: symbol,
                symbol,
            };
        },
        getTokenMarketCostOpeningText(marketSymbol) {
            return this.$t('token.market.cost_opening', this.getTranslationsContextBySymbol(marketSymbol));
        },
        getMarketEditOpenText(marketSymbol) {
            return this.$t('market.edit.open', this.getTranslationsContextBySymbol(marketSymbol, true));
        },
        toggleMarketCollapse(index, cryptoSymbol) {
            this.selectedCurrency = this.getSelectedCurrency(cryptoSymbol);
            if (this.marketProcessing) {
                return;
            }

            this.openedMarketCollapse = this.openedMarketCollapse === index ? null : index;
            this.$root.$emit('bv::toggle::collapse', `market-${index}`);
        },
        getCost(marketSymbol) {
            return this.costs[marketSymbol][this.getSelectedCurrency(marketSymbol)];
        },
        getBalance(marketSymbol) {
            return this.balances[this.getSelectedCurrency(marketSymbol)].available;
        },
        canCreateMarket(marketSymbol) {
            return this.checkIsInsufficientFunds(marketSymbol)
                || this.otherMarketsProcessing(marketSymbol);
        },
    },
};
</script>
