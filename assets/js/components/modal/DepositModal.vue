<template>
    <div>
        <modal
            v-if="currency"
            id="modal"
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
                        {{ $t('deposit_modal.title') }}
                    </span>
                    <coin-avatar
                        :symbol="currency"
                        :is-crypto="!isToken"
                        :image="tokenAvatar"
                        :is-user-token="isToken"
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
                <h2 class="modal-title text-center mb-2 word-break-all">
                    {{ $t('deposit_modal.title') }} <br/>
                    <coin-avatar
                        :symbol="currency"
                        :is-crypto="!isToken"
                        :image="tokenAvatar"
                        :is-user-token="isToken"
                    />
                    <span>
                        ({{ currency | rebranding }})
                    </span>
                </h2>
                <div class="text-center">
                    <network-selector
                        v-if="supportNetworkSelector"
                        class="mb-2"
                        v-model="selectedNetwork"
                        :networks="networks"
                        :is-owner="isOwner"
                        :token-name="token"
                        :is-loading="isNetworksLoading"
                    />
                    <div class="d-block">
                        <h6
                            v-if="showInfoMessage"
                            class="alert-block warning px-4 py-3 text-justify"
                            v-html="showInfoMessage"
                        />
                        <h6
                            v-if="tokenHasTax"
                            class="alert-block warning px-4 py-3 text-justify"
                            v-html="showHasTaxWarningMessage"
                        />
                    </div>
                </div>
                <div class="text-center">
                    <div class="px-0 py-2">
                        <img
                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&qzone=5&data=${address}`"
                            alt="QR Code"
                            class="img-thumbnail img-fluid"
                        >
                    </div>
                    <code class="wallet-code text-blue" id="walletaddress">
                        <span>
                            {{ address }}
                        </span>
                        <copy-link :content-to-copy="address" class="c-pointer">
                            <font-awesome-icon :icon="['far', 'copy']" />
                        </copy-link>
                    </code>
                    <p
                        v-html="description"
                        class="text-center mt-2 overflow-wrap-break-word word-break-all"
                    ></p>
                    <div class="row pt-2 word-break-all">
                        <div class="col">
                            <div v-if="isValidMin" class="float-left">
                                {{ $t('deposit_modal.min_value') }}
                                {{ minDeposit }}
                                <coin-avatar
                                    :symbol="currency"
                                    :is-crypto="!isToken"
                                    :image="tokenAvatar"
                                    :is-user-token="isToken"
                                    class="d-inline avatar avatar__coin"
                                />
                                {{ currency | rebranding }}
                            </div>
                            <div v-if="isValidFee" class="float-right">
                                {{ $t('deposit_modal.fee') }}
                                {{ fee }}
                                <coin-avatar
                                    :symbol="currency"
                                    :is-crypto="!isToken"
                                    :image="tokenAvatar"
                                    :is-user-token="isToken"
                                    class="d-inline avatar avatar__coin"
                                />
                                {{ currency | rebranding }}
                            </div>
                        </div>
                    </div>
                    <div class="pt-2 text-center">
                        <button
                            class="btn btn-primary btn-block"
                            @click="onSuccess"
                        >
                            {{ $t('deposit_modal.ok') }}
                        </button>
                    </div>
                </div>
            </template>
        </modal>
        <add-phone-alert-modal
            :visible="depositAddPhoneModalVisible"
            :message="addPhoneModalMessage"
            :no-close="false"
            @close="closeAddPhoneModal"
            @phone-verified="onDepositPhoneVerified"
        />
        <confirm-modal
            :visible="addPhoneAlertVisible"
            :show-image="false"
            no-title
            type="warning"
            @cancel="closeConfirmModal"
            @close="closeConfirmModal"
            @confirm="onPhoneAlertConfirm"
        >
            <slot>
                <p class="text-white modal-title text-break">
                    {{ $t('wallet.phone_alert_message') }}
                </p>
            </slot>
            <template v-slot:confirm>
                {{ $t('deposit_modal.ok') }}
            </template>
            <template v-slot:cancel>
                {{ $t('cancel') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from './Modal.vue';
import {VBTooltip} from 'bootstrap-vue';
import CopyLink from '../CopyLink';
import {
    RebrandingFilterMixin,
    FiltersMixin,
    NotificationMixin,
    AddPhoneAlertMixin,
} from '../../mixins';
import {
    webSymbol,
    TOKEN_NAME_TRUNCATE_LENGTH,
} from '../../utils/constants';
import {toMoney, generateCoinAvatarHtml} from '../../utils';
import NetworkSelector from '../wallet/NetworkSelector';
import debounce from 'lodash/debounce';
import CoinAvatar from '../CoinAvatar';
import {mapGetters} from 'vuex';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';
import ConfirmModal from './ConfirmModal';

library.add(faCopy);

export default {
    name: 'DepositModal',
    mixins: [
        RebrandingFilterMixin,
        FiltersMixin,
        NotificationMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        CopyLink,
        Modal,
        FontAwesomeIcon,
        NetworkSelector,
        CoinAvatar,
        AddPhoneAlertModal,
        ConfirmModal,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        visible: Boolean,
        currency: {
            type: String,
            default: '',
        },
        isToken: Boolean,
        token: {
            type: String,
            default: null,
        },
        tokenAvatar: null,
        isCreatedOnMintmeSite: Boolean,
        isOwner: Boolean,
        subunit: Number,
        tokenNetworks: {
            type: Object,
            default: null,
        },
        cryptoNetworks: {
            type: Object,
            default: null,
        },
        noClose: {
            type: Boolean,
            default: false,
        },
        tokenHasTax: {
            type: Boolean,
            default: false,
        },
        addPhoneAlertVisible: {
            type: Boolean,
            default: false,
        },
        depositAddPhoneModalVisible: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            fee: null,
            minDeposit: null,
            selectedNetwork: null,
            addresses: {},
            tokenTruncateLength: TOKEN_NAME_TRUNCATE_LENGTH,
            hasConfirmedAlert: false,
        };
    },
    created() {
        this.debounceDepositInfo = debounce(this.getDepositInfo, 250);
    },
    computed: {
        ...mapGetters('crypto', {
            cryptosMap: 'getCryptosMap',
        }),
        getCoinAvatarClasses() {
            return {'mr-2': webSymbol === this.currency};
        },
        supportNetworkSelector: function() {
            return (this.isToken && this.isCreatedOnMintmeSite) || !this.isToken;
        },
        networkObjects: function() {
            return this.isToken ? this.tokenNetworks : this.cryptoNetworks;
        },
        isNetworksLoading: function() {
            return !this.networkObjects;
        },
        networks: function() {
            return Object.values(this.networkObjects || {})
                .sort((a, b) => a.isDefault === b.isDefault ? 0 : a.isDefault ? -1 : 1 );
        },
        address: function() {
            if (!this.selectedNetwork || !this.addresses[this.selectedNetworkSymbol]) {
                return this.$t('wallet.loading');
            }

            return this.addresses[this.selectedNetworkSymbol];
        },
        description: function() {
            return this.$t('wallet.send_to_address', this.translationContext);
        },
        selectedNetworkSymbol: function() {
            return this.selectedNetwork?.networkInfo.symbol;
        },
        coinName: function() {
            return this.isToken ? this.currency : this.getTranslatedCryptoName(this.currency);
        },
        showAcceptInfoMessage: function() {
            return !(this.isToken && this.isCreatedOnMintmeSite);
        },
        showImportedTokenAcceptInfoMessage: function() {
            return this.isToken && !this.isCreatedOnMintmeSite;
        },
        translationContext() {
            const avatarHtml = this.isToken || !this.cryptosMap[this.currency]
                ? generateCoinAvatarHtml({
                    isUserToken: this.isToken,
                    image: this.tokenAvatar,
                })
                : generateCoinAvatarHtml({
                    symbol: this.currency,
                    isCrypto: true,
                    withSymbol: false,
                });
            const networkAvatarHtml = generateCoinAvatarHtml({
                symbol: this.selectedNetworkSymbol,
                isCrypto: true,
                withSymbol: false,
            });

            return {
                currency: this.rebrandingFunc(this.currency),
                coin: this.rebrandingFunc(this.coinName),
                coinAvatar: avatarHtml,
                network: this.selectedNetworkName,
                networkAvatar: networkAvatarHtml,
                enabledNetworks: this.generateAllowedNetworksTranslationBlock(),
            };
        },
        isValidMin: function() {
            return this.minDeposit && '0' !== this.minDeposit;
        },
        isValidFee: function() {
            return this.fee && '0' !== this.fee;
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
        showInfoMessage: function() {
            if (this.$te(`dynamic.deposit_modal.accept_warning_${this.currency}`)) {
                return this.$t(`dynamic.deposit_modal.accept_warning_${this.currency}`, this.translationContext);
            }

            if (this.showImportedTokenAcceptInfoMessage) {
                return this.$t('deposit_modal.accept_warning_imported_tokens', this.translationContext);
            }

            if (this.showAcceptInfoMessage) {
                return this.$t('deposit_modal.accept_warning', this.translationContext);
            }

            return null;
        },
        showHasTaxWarningMessage: function() {
            return this.$t('deposit_modal.token_has_tax', this.translationContext);
        },
    },
    methods: {
        onSelectNetwork: function() {
            this.fee = this.minDeposit = null;
            this.debounceDepositInfo(this.selectedNetworkSymbol);
        },
        fetchDepositAddress: function() {
            if (!this.selectedNetworkSymbol) {
                return;
            }

            this.$axios.retry.get(this.$routing.generate(
                'deposit_credentials',
                {currency: this.selectedNetworkSymbol, token: this.token})
            )
                .then((res) => {
                    this.$set(this.addresses, this.selectedNetworkSymbol, res.data);
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.service_unavailable'));
                    this.$logger.error('Service unavailable. Can not get deposit address', err);
                });
        },
        getDepositInfo: function(symbol) {
            if (!symbol) {
                return;
            }

            this.$axios.retry.get(this.$routing.generate('deposit_info', {
                currency: this.currency,
                cryptoNetwork: symbol,
            }))
                .then((res) => {
                    this.fee = toMoney(res.data.fee || 0, this.subunit);
                    this.minDeposit = toMoney(res.data.minDeposit || 0, this.subunit);
                })
                .catch((err) => {
                    this.$logger.error('Service unavailable. Can not update deposit fee status', err);
                });
        },
        selectDefaultNetwork: function() {
            if (!this.networks) {
                return;
            }

            const defaultNetwork = this.networks.find((n) => n.isDefault)
                || this.networks.find((n) => n.networkInfo.symbol === this.isToken ? webSymbol : this.currency);

            this.selectedNetwork = defaultNetwork || this.networks[0];
        },
        closeModal: function() {
            this.fee = this.minDeposit = null;

            this.$emit('close');
        },
        onSuccess: function() {
            this.closeModal();
            this.$emit('success');
        },
        selectedNetworkName: function() {
            const symbol = this.isToken
                ? this.selectedNetworkSymbol
                : this.currency;

            return this.$t(`dynamic.blockchain_${symbol}_name`);
        },
        generateAllowedNetworksTranslationBlock: function() {
            const networkSymbols = this.networks.map((network) => network.symbol);
            const enabledNetworks = networkSymbols.map((symbol, index) => {
                const isLastIndex = index === networkSymbols.length - 1;
                const separator = isLastIndex ? ` ${this.$t('and')} ` : ', ';
                const coinAvatarHtml = generateCoinAvatarHtml({symbol, isCrypto: true});
                const blockchainName = this.$t(`dynamic.blockchain_${symbol}_name`);

                return (0 !== index ? separator : '') + coinAvatarHtml + blockchainName;
            }).join('');

            return this.$tc(
                'dynamic.deposit_modal.accept_warning.enabled_networks',
                1 < networkSymbols.length ? 2 : 1,
                {enabledNetworks},
            );
        },
        getTranslatedCryptoName: function(symbol) {
            return this.cryptosMap[symbol]?.name || symbol;
        },
        onPhoneAlertConfirm: function() {
            this.$emit('phone-alert-confirm');
        },
        closeConfirmModal: function() {
            this.$emit('close-confirm-modal');
        },
        closeAddPhoneModal: function() {
            this.$emit('close-add-phone-modal');
        },
        onDepositPhoneVerified: function() {
            this.$emit('deposit-phone-verified');
        },
    },
    watch: {
        tokenNetworks: function() {
            if (!this.tokenNetworks) {
                return;
            }

            if (this.visible) {
                this.selectDefaultNetwork();
            }
        },
        cryptoNetworks: function() {
            if (!this.cryptoNetworks) {
                return;
            }

            if (this.visible) {
                this.selectDefaultNetwork();
            }
        },
        selectedNetwork: function() {
            this.onSelectNetwork();

            if (!this.addresses[this.selectedNetworkSymbol]) {
                this.fetchDepositAddress();
            }
        },
    },
};
</script>
