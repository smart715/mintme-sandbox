<template>
    <div v-if="!isLoading">
        <template v-if="activePromotion">
            <div v-html="activePromotionText"></div>
        </template>
        <template v-else>
            <div class="pb-1">
                <span>{{ $t('page.token_settings.token_promotion.cost') }}</span>
                <span class="text-primary">
                    {{ getTariffCost() | toMoney }}
                    {{ selectedCurrency | rebranding }}
                </span>
            </div>
            <m-dropdown
                :text="selectedCurrency | rebranding"
                :label="$t('market.edit.currency.label')"
                type="primary"
                class="mb-2"
            >
                <template v-slot:button-content>
                    <div class="d-flex align-items-center flex-fill">
                        <coin-avatar
                            class="mb-1 mr-1"
                            :symbol="selectedCurrency"
                            is-crypto
                        />
                        <span class="text-truncate">
                            {{ selectedCurrency | rebranding }}
                        </span>
                    </div>
                </template>
                <m-dropdown-item
                    v-for="option in currencyOptions"
                    :key="option"
                    :value="option"
                    :active="option === selectedCurrency"
                    @click="onCurrencySelect(option)"
                >
                    <coin-avatar
                        :symbol="option"
                        is-crypto
                    />
                    {{ option | rebranding }}
                </m-dropdown-item>
                <template v-slot:hint>
                    <div class="d-flex justify-content-between">
                        <div>
                            {{ $t('token.market.balance') }}
                            {{ selectedBalance | toMoney }}
                            {{ selectedCurrency | rebranding }}
                            <div
                                v-if="isInsufficientFunds"
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
            <b-form-group>
                <b-form-radio
                    v-for="tariff in tariffs"
                    v-model="selectedTariff"
                    :key="tariff.duration"
                    :value="tariff"
                >
                    {{ getTariffLabel(tariff.duration) }}
                </b-form-radio>
            </b-form-group>
            <m-button
                type="primary"
                :loading="isSending"
                :disabled="buyDisabled"
                @click="promoteToken"
            >
                {{ $t('page.token_settings.token_promotion.promote') }}
            </m-button>
        </template>
        <deposit-modal
            v-if="null !== selectedCurrency"
            :visible="showDepositModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            is-owner
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(selectedCurrency)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
    </div>
    <div v-else class="d-flex align-items-center justify-content-center">
        <div class="spinner-border spinner-border-sm" role="status"></div>
    </div>
</template>

<script>
import {DepositModalMixin, MoneyFilterMixin, NotificationMixin, RebrandingFilterMixin} from '../../mixins';
import {BFormGroup, BFormRadio} from 'bootstrap-vue';
import {GENERAL, webSymbol} from '../../utils/constants';
import {mapGetters} from 'vuex';
import CoinAvatar from '../CoinAvatar';
import {MDropdown, MDropdownItem, MButton} from '../UI';
import DepositModal from '../modal/DepositModal';
import Decimal from 'decimal.js';
import moment from 'moment';

export default {
    name: 'TokenPromotions',
    components: {
        BFormGroup,
        BFormRadio,
        CoinAvatar,
        MDropdown,
        MDropdownItem,
        MButton,
        DepositModal,
    },
    mixins: [
        NotificationMixin,
        RebrandingFilterMixin,
        DepositModalMixin,
        MoneyFilterMixin,
    ],
    props: {
        tokenName: String,
        disabledServicesConfig: String,
        tariffs: Array,
        isCreatedOnMintmeSite: Boolean,
    },
    data() {
        return {
            isLoading: true,
            activePromotion: null,
            selectedTariff: this.tariffs[0],
            selectedCurrency: webSymbol,
            tariffCosts: {},
            isSending: false,
        };
    },
    beforeMount() {
        this.fetchData();
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
        }),
        currencyOptions() {
            return Object.keys(this.tariffCosts?.[this.selectedTariff.duration] || {});
        },
        isInsufficientFunds() {
            return new Decimal(this.selectedBalance).lessThan(this.getTariffCost());
        },
        activePromotionText() {
            const context = {endDate: moment(this.activePromotion?.endDate).format(GENERAL.dateTimeFormat)};

            return this.$t('page.token_settings.token_promotion.active', context);
        },
        buyDisabled() {
            return this.isInsufficientFunds;
        },
        selectedBalance() {
            return this.balances
                ? this.balances[this.selectedCurrency].available
                : '0';
        },
    },
    methods: {
        async loadActivePromotions() {
            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate('token_promotions_active', {tokenName: this.tokenName})
                );

                this.activePromotion = response.data?.[0] || null;
            } catch (error) {
                this.$logger.error('Error while loading active token promotions', error);

                throw error;
            }
        },
        async loadTariffCosts() {
            try {
                const response = await this.$axios.retry.get(this.$routing.generate('token_promotions_costs'));

                this.tariffCosts = response.data || {};
            } catch (error) {
                this.$logger.error('Error while loading active token promotion tariff costs', error);

                throw error;
            }
        },
        async fetchData() {
            this.isLoading = true;

            try {
                await Promise.all([
                    this.loadActivePromotions(),
                    this.loadTariffCosts(),
                ]);

                this.isLoading = false;
            } catch (error) {
                this.notifyError(error?.response?.data?.message || this.$t('api.something_went_wrong'));
            }
        },
        getTariffLabel(tariffDuration) {
            const tariffName = tariffDuration.replace(' ', '_');

            return this.$te(`dynamic.token_promotions_tariff_${tariffName}`)
                ? this.$t(`dynamic.token_promotions_tariff_${tariffName}`)
                : tariffDuration;
        },
        getTariffCost() {
            return this.tariffCosts[this.selectedTariff.duration][this.selectedCurrency] || 0;
        },
        onCurrencySelect(currency) {
            this.selectedCurrency = currency;
        },
        async promoteToken() {
            this.isSending = true;

            try {
                const response = await this.$axios.single.post(
                    this.$routing.generate('token_promotions_buy', {tokenName: this.tokenName}),
                    {tariff: this.selectedTariff.duration, currency: this.selectedCurrency},
                );

                this.activePromotion = response.data;
            } catch (error) {
                this.$logger.error('Error while buying token promotion', error);

                this.notifyError(error?.response?.data?.message || this.$t('api.something_went_wrong'));
            } finally {
                this.isSending = false;
            }
        },
    },
};
</script>
