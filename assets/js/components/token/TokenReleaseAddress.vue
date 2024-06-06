<template>
    <div v-if="canUpdate">
        <div class="col-12 px-0">
            <m-input
                v-model.trim="$v.newAddress.$model"
                :invalid="$v.newAddress.$error"
            >
                <template v-slot:label>
                    <div class="d-flex align-items-center">
                        <div class="pointer-events-none">
                            {{ $t('token.release.new_address') }}
                        </div>
                        <guide class="form-control-label-guide">
                            <template slot="body">
                                {{ $t('token.release.tooltip', {network: networkName}) }}
                            </template>
                        </guide>
                    </div>
                </template>
                <template v-slot:errors>
                    <div v-if="$v.newAddress.$error">
                        {{ $t('withdraw_modal.length') }}
                    </div>
                </template>
            </m-input>
            <div class="mb-2">
                <div>
                    {{ $t('token.deploy.current_balance') }}
                    <template v-if="!isLoading">
                        {{ currentBalance | toMoney(cryptoSubunit) | formatMoney }}
                        {{ deployCrypto.symbol | rebranding }}
                        <span
                            :class="getDepositDisabledClasses(deployCrypto.symbol)"
                            @click="openDepositModal(deployCrypto.symbol)"
                        >
                            {{ $t('token.deploy.add_more_funds') }}
                        </span>
                    </template>
                    <span v-else class="ml-2 spinner-border spinner-border-sm" role="status"></span>
                </div>
                <div class="pt-2">
                    {{ $t('token.release.cost') }}
                    <template v-if="!isLoading">
                        ~{{ contractFee | toMoney | formatMoney }}
                        {{ deployCrypto.symbol | rebranding }}
                    </template>
                    <span v-else class="ml-2 spinner-border spinner-border-sm" role="status"></span>
                </div>
            </div>
        </div>
        <div>
            <span v-if="!isLoading && insufficientFunds" class="text-danger mt-0">
                {{ $t('token.deploy.insufficient_funds') }}
            </span>
        </div>

        <m-button
            v-if="isLoading || contractFeeNeedUpdate"
            type="primary"
            :disabled="isLoading || submitting"
            @click="fetchContractFee"
        >
            {{ $t('token.release.refresh') }}
        </m-button>
        <m-button
            v-else
            :disabled="changeDisabled"
            :loading="submitting"
            type="primary"
            @click="editAddress"
        >
            {{ $t('token.release.change') }} ({{contractFeeSecondsLeft}})
        </m-button>

        <two-factor-modal
            :visible="showTwoFactorModal"
            :twofa="twofa"
            :loading="loading"
            @verify="doEditAddress"
            @close="closeTwoFactorModal"
        />
        <deposit-modal
            :visible="showDepositModal"
            :currency="deployCrypto.symbol"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            :no-close="false"
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(deployCrypto.symbol)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
    </div>
    <div v-else-if="serviceUnavailable" class="text-center pt-3">
        {{ this.$t('toasted.error.service_unavailable_short') }}
    </div>
    <div
        v-else-if="!isTokenDeployed"
        class="m-0 py-3 px-2 text-muted text-center"
    >
        {{ $t('token.release.not_deployed') }}
    </div>
    <div
        v-else
        class="text-left"
    >
        <div class="p-3 d-flex flex-column align-items-center">
            {{ $t('token.release.updating_address_pending') }}
        </div>
    </div>
</template>

<script>
import TwoFactorModal from '../modal/TwoFactorModal';
import {required} from 'vuelidate/lib/validators';
import {MButton, MInput} from '../UI';
import Decimal from 'decimal.js';
import {mapGetters} from 'vuex';
import Guide from '../Guide';
import {
    NotificationMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    DepositModalMixin,
} from '../../mixins';
import {
    addressContain,
    MINTME,
} from '../../utils/constants';
import DepositModal from '../modal/DepositModal';

const MAX_CONTRACT_FEE_SECONDS = 5;

export default {
    name: 'TokenReleaseAddress',
    mixins: [
        NotificationMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        DepositModalMixin,
    ],
    components: {
        Guide,
        TwoFactorModal,
        MInput,
        MButton,
        DepositModal,
    },
    props: {
        tokenCrypto: Object,
        isTokenDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
        releaseAddress: String,
        isOwner: Boolean,
        isCreatedOnMintmeSite: Boolean,
    },
    data() {
        return {
            currentAddress: this.releaseAddress,
            contractFee: null,
            contractFeeSecondsLeft: 0,
            newAddress: this.releaseAddress,
            showTwoFactorModal: false,
            submitting: false,
            loading: false,
            gatewayServiceUnavailable: false,
        };
    },
    mounted: function() {
        this.fetchContractFee();
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            balanceServiceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('tokenInfo', {
            mainDeploy: 'getMainDeploy',
        }),
        serviceUnavailable: function() {
            return this.balanceServiceUnavailable || this.gatewayServiceUnavailable;
        },
        deployCrypto: function() {
            return this.mainDeploy.crypto;
        },
        cryptoSubunit: function() {
            return this.deployCrypto.subunit;
        },
        insufficientFunds: function() {
            return new Decimal(this.currentBalance).lessThan(this.contractFee);
        },
        isLoading: function() {
            return !this.balances || !this.mainDeploy || !this.contractFee;
        },
        currentBalance: function() {
            const balance = this.balances[this.deployCrypto.symbol];

            return balance.available || '0';
        },
        canUpdate: function() {
            return this.isTokenDeployed
                && '0x' !== this.currentAddress
                && !this.serviceUnavailable
            ;
        },
        sameAddress: function() {
            return this.currentAddress === this.newAddress;
        },
        contractFeeNeedUpdate: function() {
            return 0 > this.contractFeeSecondsLeft;
        },
        changeDisabled: function() {
            return this.submitting
                || this.sameAddress
                || this.$v.newAddress.$error
                || this.isLoading
                || this.insufficientFunds
                || this.contractFeeNeedUpdate;
        },
        networkName() {
            return MINTME.symbol === this.rebrandingFunc(this.tokenCrypto.symbol)
                ? this.$t('dynamic.blockchain_WEB_name')
                : this.tokenCrypto.symbol;
        },
    },
    methods: {
        fetchContractFee: function() {
            if (!this.mainDeploy || this.mainDeploy.pending) {
                return;
            }

            const route = this.$routing.generate('token_contract_fee', {crypto: this.deployCrypto.symbol});

            this.contractFee = null;

            this.$axios.retry.get(route)
                .then(({data}) => {
                    this.contractFeeSecondsLeft = MAX_CONTRACT_FEE_SECONDS;
                    this.contractFee = data;

                    const interval = setInterval(() => {
                        if (this.contractFeeNeedUpdate) {
                            clearInterval(interval);
                            return;
                        }

                        this.contractFeeSecondsLeft--;
                    }, 1000);
                })
                .catch((error) => {
                    this.gatewayServiceUnavailable = true;
                    this.notifyError(this.$t('toasted.error.network'));
                    this.$logger.error('error', 'Could not fetch contract method fee', error);
                });
        },
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        closeModal: function() {
            this.cancelEditingMode();
        },
        setUpdatingState: function() {
            this.currentAddress = '0x';
        },
        cancelEditingMode: function() {
            if (!this.showTwoFactorModal) {
                this.$v.$reset();
                this.newAddress = this.currentAddress;
            }
        },
        editAddress: function() {
            this.$v.$touch();

            if (this.changeDisabled) {
                return;
            }

            if (this.twofa) {
                this.showTwoFactorModal = true;
            } else {
                this.doEditAddress();
            }
        },
        doEditAddress: function(code = '') {
            if (this.submitting) {
                return;
            }
            this.loading = true;
            this.submitting = true;
            this.$axios.single.post(this.$routing.generate('token_contract_update', {
                name: this.tokenName,
            }), {
                address: this.newAddress,
                code,
            })
                .then(({data}) => {
                    this.submitting = false;
                    this.setUpdatingState();

                    const message = this.$t('token.release.updating_address_pending');
                    const feeMessage = this.$t('token.release.fee_was', {
                        fee: this.$options.filters.toMoney(data.fee),
                        currency: this.$options.filters.rebranding(this.deployCrypto.symbol),
                    });

                    this.notifySuccess(`${message} ${feeMessage}`);
                }, (error) => {
                    this.fetchContractFee();
                    this.submitting = false;

                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.$logger.error('Edit address network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.$logger.error('Can not edit address', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', error);
                    }
                })
                .then(() => {
                    this.loading = false;
                });
        },
    },
    watch: {
        mainDeploy: function() {
            this.fetchContractFee();
        },
    },
    validations() {
        return {
            newAddress: {
                required,
                addressContain,
                addressFirstSymbol: (address) => address.startsWith('0x'),
            },
        };
    },
};
</script>

