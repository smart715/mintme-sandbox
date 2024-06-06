<template>
    <div>
        <modal
            v-if="currency"
            dialog-class="dw-modal"
            :visible="visible"
            :no-close="noClose"
            @close="closeModal"
        >
            <template slot="header">
                <h3
                    class="modal-header d-flex justify-content-center align-items-center m-0 w-100"
                    v-b-tooltip="modalTooltip"
                >
                    <span class="mr-2">
                        {{ $t('withdraw_modal.title') }}
                    </span>
                    <coin-avatar
                        :symbol="currency"
                        :is-crypto="!isToken"
                        :is-user-token="isToken"
                        :image="tokenAvatar"
                        image-class="coin-avatar-md"
                        class="avatar avatar__coin mb-2"
                        :class="getCoinAvatarClasses"
                        :is-white-color="true"
                    />
                    <span class="text-white pl-2 text-center text-truncate">
                        {{ currency | rebranding | truncate(tokenTruncateLength) }}
                    </span>
                </h3>
            </template>
            <template slot="body">
                <div class="text-center overflow-wrap-break-word">
                    <div v-if="selectedNetwork && selectedNetwork.address">
                        <div class="token-address-buttons mt-3 text-left">
                            <copy-link
                                class="c-pointer"
                                :content-to-copy="selectedNetwork.address"
                            >
                                {{ $t('token.intro.statistics.token_address.header') }}
                                <a href="#">
                                    {{ selectedNetwork.address | preview }}
                                    <font-awesome-icon :icon="['far', 'copy']" class="icon-default"/>
                                </a>
                            </copy-link>
                        </div>
                    </div>
                    <div class="pt-2">
                        <span v-if="withdrawInfoMessage" class="pb-3">
                            {{ withdrawInfoMessage }}
                        </span>
                        <div
                            class="col-12 mt-3 input-container d-flex align-items-center py-2"
                            :class="{ 'is-input-invalid': $v.address.$error }"
                        >
                            <label for="wallet-address" class="legend pb-0 px-1">
                                {{ addressLabel }}
                            </label>
                            <div class="input-group">
                                <div class="w-100">
                                    <input
                                        v-model="$v.address.$model"
                                        type="text"
                                        id="wallet-address"
                                        class="form-control"
                                    >
                                </div>
                            </div>
                        </div>
                        <div v-if="this.selectedNetwork && $v.address.$error" class="invalid-message">
                            {{ addressInvalidErrorMessage }}
                        </div>
                        <div v-if="shouldShowCoinNetworkSelector" class="text-left mt-4">
                            <coin-network-selector
                                v-model="selectedNetwork"
                                :networks="networkObjects"
                            />
                            <div v-if="showSelectCoinNetworkError" class="invalid-message">
                                {{ $t('withdraw_modal.coin_network_selector.error_message') }}
                            </div>
                        </div>
                        <div
                            v-if="showAmountInput"
                            class="col-12 mt-3 input-container d-flex align-items-center py-2"
                            :class="{ 'is-invalid': $v.amount.$error }"
                        >
                            <label for="wamount" class="legend pb-0 px-1">
                                {{ $t('withdraw_modal.amount', translationsContext) }}
                            </label>
                            <price-converter-input
                                class="d-inline-block flex-grow-1"
                                input-id="wamount"
                                v-model="$v.amount.$model"
                                :from="currency"
                                :to="USD.symbol"
                                :is-token="isToken"
                                :subunit="4"
                                symbol="$"
                                @keypress="onAmountInput"
                                @paste="onAmountInput"
                            />
                            <button
                                class="btn btn-input align-self-center text-uppercase"
                                type="button"
                                @click="setMaxAmount"
                            >
                                {{ $t('withdraw_modal.max') }}
                            </button>
                        </div>
                        <div
                            v-if="showAmountInput"
                            class="invalid-message text-center"
                        >
                            <template v-if="!$v.amount.maxValue && $v.amount.decimal">
                                {{ $t('withdraw_modal.insufficient_funds', translationsContext) }}
                            </template>
                            <template v-if="!$v.amount.minValue
                                && $v.amount.decimal
                                && typeof $v.amount.$model === 'string'"
                            >
                                {{ $t('withdraw_modal.min_withdraw', translationsContext) }}
                            </template>
                            <template v-if="!$v.amount.decimal || !$v.amount.invalidAmount">
                                {{ $t('withdraw_modal.invalid_amount') }}
                            </template>
                            <template v-if="hasNetworkAndInsufficientFee">
                                {{ $t('withdraw_modal.insufficient_funds', {currency: rebrandingFunc(feeCurrency)}) }}
                            </template>
                        </div>
                    </div>
                    <div class="mt-4 withdraw-totals">
                        <div class="text-left">
                            <label>
                                {{ $t('withdraw_modal.fee') }}
                            </label>
                            <span class="float-right">
                                <template v-if="!selectedNetwork">
                                -
                                </template>
                                <template v-else>
                                    <span class="text-primary">
                                        {{ feeAmount }}
                                    </span>
                                    <coin-avatar
                                        :symbol="feeCurrency"
                                        :is-crypto="isCrypto(feeCurrency)"
                                        :is-user-token="isToken"
                                        :image="tokenAvatar"
                                        class="d-inline avatar avatar__coin"
                                    />
                                    <span>
                                        {{ feeCurrency | rebranding }}
                                    </span>
                                </template>
                            </span>
                        </div>
                        <div class="mt-3 text-left">
                            <label>
                                {{ $t('withdraw_modal.total') }}
                            </label>
                            <span class="overflow-wrap-break-word word-break-all float-right">
                                <span class="text-primary">
                                    {{ fullAmount | toMoney(subunit) }}
                                </span>
                                <coin-avatar
                                    :symbol="currency"
                                    :is-crypto="!isToken"
                                    :is-user-token="isToken"
                                    :image="tokenAvatar"
                                    class="d-inline avatar avatar__coin"
                                />
                                <span>
                                    {{ currency | rebranding }}
                                </span>
                            </span>
                        </div>
                    </div>
                    <div v-if="showTwofactor" class="col-12 pt-4 px-0">
                        <label for="twofactor" class="d-block text-left">
                            {{ $t('withdraw_modal.twofa_code') }}
                        </label>
                        <verify-code
                            id="twofactor"
                            :disabled="withdrawing"
                            :focused="false"
                            @code-entered="onVerifyCodeEntered"
                        />
                    </div>
                    <div class="input-group pt-5 d-flex justify-content-center align-items-center">
                        <div v-if="isTokenTransfersPaused" class="invalid-message pb-2">
                            {{ tokenTransfersPausedErrorMessage }}
                        </div>
                        <button
                            v-if="!validCode"
                            class="btn btn-primary d-flex justify-content-center align-items-center"
                            :disabled="disableWithdrawButton"
                            @click="onWithdraw"
                        >
                            <font-awesome-icon
                                class="check-mark"
                                :icon="{prefix: 'far', iconName: 'check-square'}"
                            />
                            <span class="ml-2">
                                {{ $t('withdraw_modal.submit') }}
                            </span>
                        </button>
                        <button
                            v-if="!validCode"
                            class="btn btn-cancel ml-2 pl-3 c-pointer bg-transparent"
                            tabindex="0"
                            @click="onCancel"
                            @keyup.enter="onCancel"
                        >
                            <slot name="cancel">
                                {{ $t('withdraw_modal.cancel') }}
                            </slot>
                        </button>
                        <div v-if="validCode" class="spinner-border spinner-border-sm" role="status"></div>
                    </div>
                </div>
            </template>
        </modal>
        <add-phone-alert-modal
            :visible="withdrawAddPhoneModalVisible"
            :message="addPhoneModalMessage"
            :no-close="false"
            @close="withdrawCloseAddPhoneModal"
            @phone-verified="onWithdrawPhoneVerified"
        />
    </div>
</template>

<script>
import Decimal from 'decimal.js';
import Modal from './Modal.vue';
import {VBTooltip} from 'bootstrap-vue';
import {required, maxValue, decimal, minValue} from 'vuelidate/lib/validators';
import {toMoney} from '../../utils';
import {
    addressContain,
    twoFACode,
    USD,
    webSymbol,
    HTTP_ACCESS_DENIED,
    HTTP_UNAUTHORIZED,
    TOKEN_NAME_TRUNCATE_LENGTH,
    WALLET_WITHDRAW_ERROR,
} from '../../utils/constants';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    BnbToBscFilterMixin,
    ClearInputMixin,
    FloatInputMixin,
    FiltersMixin,
    AddPhoneAlertMixin,
} from '../../mixins/';
import PriceConverterInput from '../PriceConverterInput';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy, faCheckSquare} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VerifyCode from '../VerifyCode';
import CopyLink from '../CopyLink';
import CoinAvatar from '../CoinAvatar';
import CoinNetworkSelector from '../wallet/CoinNetworkSelector';
import debounce from 'lodash/debounce';
import {mapGetters} from 'vuex';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';

library.add(faCheckSquare, faCopy);

export default {
    name: 'WithdrawModal',
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        BnbToBscFilterMixin,
        ClearInputMixin,
        FloatInputMixin,
        FiltersMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        PriceConverterInput,
        Modal,
        FontAwesomeIcon,
        VerifyCode,
        CopyLink,
        CoinAvatar,
        CoinNetworkSelector,
        AddPhoneAlertModal,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        panelEnv: String,
        visible: Boolean,
        currency: {
            type: String,
            default: '',
        },
        isToken: Boolean,
        tokenAvatar: null,
        isCreatedOnMintmeSite: Boolean,
        isOwner: Boolean,
        tokenNetworks: {
            type: Object,
            default: null,
        },
        cryptoNetworks: {
            type: Object,
            default: null,
        },
        availableBalances: Object,
        withdrawUrl: String,
        subunit: Number,
        twofa: String,
        noClose: Boolean,
        expirationTime: Number,
        currencyMode: String,
        isPausable: Boolean,
        minWithdrawal: Object,
        withdrawAddPhoneModalVisible: Boolean,
    },
    data() {
        return {
            code: null,
            amount: '',
            address: '',
            withdrawing: true,
            USD,
            tokenTruncateLength: TOKEN_NAME_TRUNCATE_LENGTH,
            selectedNetwork: null,
            showSelectCoinNetworkError: false,
            showTwofactor: false,
            showTwofactorDebounced: null,
            addressValidationDebounced: null,
            addressValidationProcessing: false,
            addressInvalid: false,
            isTokenTransfersPaused: false,
            minFee: '0',
        };
    },
    computed: {
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
        getCoinAvatarClasses() {
            return {'mr-2': webSymbol === this.currency};
        },
        currencyMaxAmount: function() {
            const amount = new Decimal(this.currencyBalance);

            return amount.greaterThan(this.currencyFee)
                ? toMoney(amount.sub(this.currencyFee).toString(), this.subunit)
                : toMoney(0, this.subunit);
        },
        currencyBalance: function() {
            return this.availableBalances[this.currency] || '0';
        },
        /*
        * Fee of the currency itself, not dependent on the network.
        *
        * This one should be added to the full amount required
        *
        * A) if currency and feeCurrency are the same, then this fee is from the currency (returns fee)
        *
        * B) if currency and feeCurrency are NOT the same (token, or WMM),
        *   then this fee is from the network. Not the currency (returns 0)
        */
        currencyFee: function() {
            return this.selectedNetwork && this.selectedNetwork.feeCurrency === this.currency
                ? this.feeAmount
                : 0;
        },
        supportNetworkSelector: function() {
            return this.isToken && this.isCreatedOnMintmeSite;
        },
        networkObjects: function() {
            return this.isToken ? Object.values(this.tokenNetworks ?? {}) : Object.values(this.cryptoNetworks ?? {});
        },
        disabledWithdraw: function() {
            return !this.selectedNetwork
                || this.isInsufficientFee
                || this.$v.$anyError
                || this.withdrawing
                || ('' !== this.twofa && !this.code)
                || (this.isToken && this.isTokenTransfersPaused);
        },
        disableWithdrawButton: function() {
            return this.$v.$anyError
                || this.withdrawing
                || ('' !== this.twofa && !this.code)
                || !this.isBlockchainAvailable
                || (this.isToken && this.isTokenTransfersPaused);
        },
        minAmount: function() {
            return toMoney(
                this.minWithdrawal[this.currency] || '1e-' + this.subunit,
                this.subunit
            );
        },
        fullAmount: function() {
            this.changeAmountWithDot();

            Decimal.set({precision: 36});

            const amount = new Decimal(
                new RegExp(/^[0-9]+(\.?[0-9]+)?$/).test(this.amount) ? this.amount : 0
            );

            return toMoney(amount.add(this.currencyFee).toString(), this.subunit);
        },
        feeAmount: function() {
            const defaultFeeDecimal = new Decimal(this.selectedNetwork.fee);
            const minFeeDecimal = new Decimal(this.minFee);
            const feeAmount = defaultFeeDecimal.greaterThan(minFeeDecimal) ? defaultFeeDecimal : minFeeDecimal;

            return toMoney(feeAmount.toFixed(this.subunit));
        },
        feeCurrency: function() {
            return this.selectedNetwork.feeCurrency;
        },
        translationsContext: function() {
            return {
                currency: this.rebrandingFunc(this.currency),
                minAmount: this.minAmount,
                balance: new Decimal(this.currencyBalance),
            };
        },
        addressLabel: function() {
            return this.$te(`dynamic.withdraw_modal_address_label_${this.currency}`)
                ? this.$t(`dynamic.withdraw_modal_address_label_${this.currency}`)
                : this.$t('withdraw_modal.address');
        },
        withdrawInfoMessage: function() {
            return this.$te(`dynamic.withdraw_modal_message_${this.currency}`)
                ? this.$t(`dynamic.withdraw_modal_message_${this.currency}`)
                : null;
        },
        addressInvalidErrorMessage: function() {
            if (!this.$v.address.checkContractAddress) {
                return this.$t('withdraw.withdrawing_to_contract_address');
            }

            return this.$t('withdraw_modal.invalid_addr');
        },
        tokenTransfersPausedErrorMessage: function() {
            return this.$t('withdraw_modal.token_transfers_paused.error_message');
        },
        amountInvalidErrorMessage: function() {
            if (this.isInsufficientAmount) {
                return this.$t('withdraw_modal.insufficient_funds', this.translationsContext);
            }
            if (!this.$v.amount.minValue && this.$v.amount.decimal
                && 'string' === typeof this.$v.amount.$model) {
                return this.$t('withdraw_modal.min_withdraw', this.translationsContext);
            }
            if (!this.$v.amount.decimal || !this.$v.amount.invalidAmount) {
                return this.$t('withdraw_modal.invalid_amount');
            }
            return '';
        },
        validCode: function() {
            return this.code && !this.$v.$anyError && !!this.selectedNetwork && this.withdrawing
                ? twoFACode(this.code)
                : false;
        },
        isInsufficientAmount: function() {
            return !this.$v.amount.maxValue && this.$v.amount.decimal;
        },
        isInsufficientFee: function() {
            if (this.currency === this.feeCurrency) {
                // fee is part of the amount then
                return false;
            }

            const availableBalance = this.availableBalances[this.feeCurrency];

            return availableBalance && new Decimal(availableBalance).lessThan(this.feeAmount);
        },
        currencyLength: function() {
            return this.currency
                ? this.currency.length > this.tokenTruncateLength
                : null;
        },
        modalTooltip: function() {
            return this.currencyLength
                ? {
                    title: this.currency,
                    boundary: 'viewport',
                    placement: 'bottom',
                }
                : null;
        },
        hasNetworkAndInsufficientFee: function() {
            return this.selectedNetwork && this.isInsufficientFee;
        },
        shouldShowCoinNetworkSelector() {
            return !!this.networkObjects;
        },
        showAmountInput() {
            return this.selectedNetwork && this.address;
        },
        isBlockchainAvailable() {
            return this.selectedNetwork?.networkInfo?.blockchainAvailable ?? true;
        },
    },
    mounted() {
        this.showTwofactorDebounce = !!this.twofa ? debounce(() => this.showTwofactor = true, 500) : null;
        this.addressValidationDebounced = debounce(this.validateCryptoAddress, 300);
    },
    methods: {
        closeModal: function(data) {
            this.$v.$reset();
            this.amount = '';
            this.address = '';
            this.code = null;
            this.withdrawing = true;
            this.showSelectCoinNetworkError = false;
            this.selectedNetwork = null;
            this.showTwofactor = false;
            this.isTokenTransfersPaused = false;
            this.addressValidationDebounced.cancel();
            this.$emit('close', data);
        },
        onWithdraw: function() {
            this.$v.$touch();

            if (this.$v.$error) {
                this.code = null;
                this.notifyError(this.$t('toasted.error.correct_form'));
                return;
            }

            if (this.shouldShowCoinNetworkSelector && !this.selectedNetwork) {
                this.showSelectCoinNetworkError = true;
                return;
            }

            if (this.disabledWithdraw) {
                return;
            }

            const availableBalance = this.availableBalances[this.feeCurrency];

            if (new Decimal(availableBalance).lessThan(this.selectedNetwork.fee)) {
                this.code = null;
                this.notifyError(
                    this.$t('toasted.error.do_not_have_enough',
                        {currency: this.rebrandingFunc(this.feeCurrency)}
                    ));
                return;
            }

            this.withdrawing = true;

            this.$axios.single.post(this.withdrawUrl, {
                'currency': this.currency,
                'cryptoNetwork': this.selectedNetwork.symbol,
                'amount': this.amount,
                'address': this.address,
                'code': this.code,
            })
                .then((response) => {
                    if (null === this.code) {
                        this.notifySuccess(
                            this.$t('toasted.success.email_sent', {hours: Math.floor(this.expirationTime / 3600)}));
                    } else {
                        this.notifySuccess(this.$t('toasted.success.withdrawal.queued'));
                    }
                    this.closeModal({
                        currency: this.currency,
                        amount: response.data?.amount,
                        fee: response.data?.fee,
                        feeCurrency: this.feeCurrency,
                    });
                })
                .catch(this.handleWithdrawError.bind(this))
                .finally(() => this.withdrawing = false);

            this.$emit('withdraw', this.currency, this.amount, this.address);
        },
        handleWithdrawError: function(error) {
            this.code = null;

            if (undefined === error.response) {
                this.notifyError(this.$t('toasted.error.network'));
                return;
            }

            if ((HTTP_ACCESS_DENIED === error.response.status || HTTP_UNAUTHORIZED === error.response.status) &&
                error.response.data?.message) {
                this.notifyError(error.response.data.message);
                return;
            }

            if (WALLET_WITHDRAW_ERROR.INCORRECT_ADDRESS_START === error.response.data?.error) {
                this.notifyError(this.$t('withdraw_modal.invalid_addr'));
                return;
            }

            if (WALLET_WITHDRAW_ERROR.INCORRECT_ADDRESS_LENGTH === error.response.data?.error) {
                this.notifyError(this.$t('withdraw_modal.length'));
                return;
            }

            if (WALLET_WITHDRAW_ERROR.SMART_CONTRACT_ADDRESS === error.response.data?.error) {
                this.notifyError(this.$t('withdraw_modal.smart_contract_addr'));
                return;
            }

            this.notifyError(error.response.data?.error ?? this.$t('api.wallet.withdrawal_failed'));
            this.$logger.error('Withdraw response error', error);
        },
        onCancel: function() {
            this.$emit('cancel');
            this.closeModal();
            this.withdrawing = true;
        },
        setMaxAmount: function() {
            this.amount = this.currencyMaxAmount;
            this.onInputChange();
        },
        onAmountInput: function() {
            this.checkInput(this.subunit);

            this.showTwoFactorIfRequired();
        },
        showTwoFactorIfRequired: function() {
            if (this.twofa && !!this.amount && !this.$v.amount.$invalid && !this.hasNetworkAndInsufficientFee) {
                this.showTwofactorDebounce.cancel();
                this.showTwofactorDebounce();
            }
        },
        onInputChange: function() {
            (!this.$v.address.$invalid && !this.$v.amount.$invalid)
                ? this.withdrawing = false
                : this.withdrawing = true;

            this.showTwoFactorIfRequired();
        },
        changeAmountWithDot: function() {
            if (this.amount) {
                this.amount = this.parseFloatInput(this.amount);
            }
        },
        onVerifyCodeEntered: function(code) {
            this.code = code;
        },
        isCrypto: function(symbol) {
            return !!this.enabledCryptosMap[symbol];
        },
        validateCryptoAddress: async function() {
            try {
                const response = await this.$axios.single.get(
                    this.$routing.generate('check_crypto_address'),
                    {params: {symbol: this.selectedNetwork?.symbol, address: this.address}},
                );

                this.addressInvalid = !response.data;
                this.$v.address.$touch();
            } catch (error) {
                this.notifyError(this.$t('toasted.error.try_later'));
            } finally {
                this.onInputChange();
            }
        },
        setWithdrawInfo: function() {
            if (!this.selectedNetwork?.symbol) {
                return;
            }

            this.$axios.retry.get(this.$routing.generate('withdraw_info', {
                symbol: this.currency,
                cryptoNetwork: this.selectedNetwork?.symbol,
            }))
                .then((res) => {
                    this.isTokenTransfersPaused = res.data.paused;
                    this.minFee = res.data.minFee;
                })
                .catch((err) => {
                    this.$logger.error('Service unavailable. Can not update withdraw info', err);
                });
        },
        verifyContractAddress: function() {
            return this.address === this.selectedNetwork.address;
        },
        withdrawCloseAddPhoneModal: function() {
            this.$emit('withdraw-close-add-phone-modal');
        },
        onWithdrawPhoneVerified: function() {
            this.$emit('on-withdraw-phone-verified');
        },
    },
    filters: {
        preview: function(address) {
            const start = address.slice(0, 6);
            const end = address.slice(-6);

            return start + '...' + end;
        },
    },
    watch: {
        code() {
            if (this.code && twoFACode(this.code) && !this.disabledWithdraw) {
                this.onWithdraw();
            }
        },
        amount() {
            this.onInputChange();
        },
        address() {
            if (!this.visible) {
                return;
            }

            this.onInputChange();

            this.addressValidationDebounced.cancel();
            this.addressValidationDebounced();
        },
        selectedNetwork(newVal) {
            if (newVal) {
                this.setWithdrawInfo();

                this.showSelectCoinNetworkError = false;

                if (this.address) {
                    this.$v.address.$touch();
                    this.addressValidationDebounced.cancel();
                    this.addressValidationDebounced();
                }
            }
        },
        visible() {
            this.selectedNetwork = null;
        },
    },
    validations() {
        return {
            amount: {
                required,
                decimal,
                maxValue: maxValue(this.currencyMaxAmount),
                minValue: minValue(this.minAmount),
                invalidAmount: (val) => this.checkInputValue(val, this.subunit),
            },
            address: {
                required,
                addressContain,
                addressCorrect: () => {
                    return !this.addressInvalid;
                },
                checkContractAddress: () => {
                    return !this.verifyContractAddress();
                },
            },
            code: {
                twoFACode,
            },
        };
    },
};
</script>
