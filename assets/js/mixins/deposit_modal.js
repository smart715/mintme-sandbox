import {
    WEB,
} from '../utils/constants';
import NotificationMixin from './notification';
import {RebrandingFilterMixin} from './filters';
import {mapGetters} from 'vuex';

export default {
    mixins: [
        NotificationMixin,
        RebrandingFilterMixin,
    ],
    props: {
        disabledServicesConfig: [String, Object],
        disabledCryptos: Array,
        isUserBlocked: Boolean,
    },
    data() {
        return {
            showDepositModal: null,
            tokensNetworks: {},
            cryptosNetworks: {},
            isTokenModal: false,
            depositTokens: null,
            predefinedTokens: null,
            selectedCurrency: null,
            depositAddPhoneModalVisible: null,
            addPhoneAlertVisible: false,
            hasConfirmedAlert: false,
        };
    },
    computed: {
        ...mapGetters('user', {
            userId: 'getId',
            hasPhoneVerified: 'getHasPhoneVerified',
            depositPhoneRequired: 'getDepositPhoneRequired',
            withdrawalPhoneRequired: 'getWithdrawalPhoneRequired',
        }),
        currentTokenNetworks: function() {
            return this.tokensNetworks[this.selectedCurrency] || null;
        },
        currentCryptoNetworks: function() {
            return this.cryptosNetworks[this.selectedCurrency];
        },
        currentSubunit: function() {
            if (
                (this.isTokenModal && !this.depositTokens) ||
                (!this.isTokenModal && !this.predefinedTokens)
            ) {
                return WEB.subunit;
            }

            const asset = this.isTokenModal
                ? this.depositTokens[this.selectedCurrency]
                : this.predefinedTokens[this.selectedCurrency];

            return asset ? asset.subunit : WEB.subunit;
        },
        servicesConfig: function() {
            if ('object' === typeof this.disabledServicesConfig) {
                return this.disabledServicesConfig;
            }

            return JSON.parse(this.disabledServicesConfig);
        },
    },
    mounted() {
        this.loadTokensData();
    },
    methods: {
        loadTokensData: async function() {
            if (null === this.userId) {
                return;
            }

            try {
                const response = await this.$axios.retry.get(this.$routing.generate(
                    'tokens',
                    {tokensInfo: true}
                ));
                const tokensData = response.data;

                this.depositTokens = tokensData.common;
                this.predefinedTokens = tokensData.predefined;
            } catch (error) {
                this.$logger.error('Error while load tokens data', error);
            }
        },
        setCryptoNetworks: async function(symbol) {
            try {
                const response = await this.$axios.retry(this.$routing.generate('get_crypto_networks', {symbol}));
                this.$set(this.cryptosNetworks, symbol, this.parseNetworksData(response.data));
            } catch (error) {
                this.$logger.error('Error while get crypto networks', error);
            }
        },
        setTokenNetworks: async function(name) {
            try {
                const response = await this.$axios.retry(this.$routing.generate('get_token_networks', {name}));
                this.$set(this.tokensNetworks, name, this.parseNetworksData(response.data));
            } catch (error) {
                this.$logger.error('Error while get token networks', error);
            }
        },
        parseNetworksData: function(networks) {
            return networks.reduce((acc, network) => {
                const symbol = network.networkInfo.symbol;

                acc[symbol] = {symbol, ...network};

                return acc;
            }, {});
        },
        isCryptoDepositModalActionDisabled: function(name) {
            const action = this.getDepositCryptoActionDisabled(name, 'DepositsDisabled');

            return this.isUserBlocked
                || this.isDisabledCrypto(name)
                || this.servicesConfig[action]
                || this.servicesConfig.coinDepositsDisabled
                || this.servicesConfig.allServicesDisabled;
        },
        getDepositCryptoActionDisabled: function(crypto, action) {
            const symbol = this.rebrandingFunc(crypto).toLowerCase();

            return symbol.concat(action);
        },
        isTokenDepositModalActionDisabled: function(name) {
            return name.blocked
                || this.isUserBlocked
                || this.servicesConfig.tokenDepositsDisabled
                || this.servicesConfig.allServicesDisabled;
        },
        isDisabledCrypto: function(name) {
            return this.disabledCryptos?.includes(name);
        },
        isDepositDisabled: function(name) {
            return this.predefinedTokens && this.predefinedTokens[name]?.identifier
                ? this.isCryptoDepositModalActionDisabled(name)
                : this.isTokenDepositModalActionDisabled(name);
        },
        getDepositDisabledClasses: function(name, textWhite = true) {
            const textColor = textWhite ? 'text-white' : '';

            return this.isDepositDisabled(name)
                ? 'text-muted pointer-events-none underline'
                : 'highlight link ' + textColor;
        },
        getTradeDepositDisabledClasses: function(name) {
            return this.isDepositDisabled(name)
                ? 'text-muted pointer-events-none'
                : 'link-primary';
        },
        openDepositModal: function(currency) {
            const isCrypto = !!this.predefinedTokens[currency];
            const isToken = !!this.depositTokens[currency];
            const isBlockedToken = isToken && this.depositTokens[currency].blocked;
            const action = this.getDepositCryptoActionDisabled(currency, 'DepositsDisabled');

            if (this.isDisabledCrypto(currency)
                || this.servicesConfig[action]
                || (isCrypto && this.servicesConfig.coinDepositsDisabled)
                || (isToken && this.servicesConfig.tokenDepositsDisabled)
                || this.servicesConfig.allServicesDisabled
            ) {
                this.notifyError(this.$t('toasted.error.deposits.disabled'));

                return;
            }

            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }

            if (!this.checkIfUserAbleToDeposit('openDepositModal', currency)) {
                return;
            }

            this.hasConfirmedAlert = false;

            if (isToken && !this.depositTokens[currency].deployed) {
                return;
            }

            this.selectedCurrency = currency;
            this.isTokenModal = !isCrypto;

            if (isCrypto) {
                this.setCryptoNetworks(currency);
            } else {
                this.setTokenNetworks(currency);
            }

            this.showDepositModal = true;
        },
        checkIfUserAbleToDeposit(fnToCall, currency) {
            this.depositFnToCallAfterVerification = {
                name: fnToCall,
                currency: currency,
            };

            if (
                !this.depositPhoneRequired &&
                this.withdrawalPhoneRequired &&
                !this.hasConfirmedAlert &&
                !this.hasPhoneVerified
            ) {
                this.addPhoneAlertVisible = true;
                return false;
            }

            if (this.depositPhoneRequired && !this.hasPhoneVerified) {
                this.depositAddPhoneModalVisible = true;
                return false;
            }

            return true;
        },
        closeAddPhoneModal: function() {
            this.depositAddPhoneModalVisible = false;
        },
        onDepositPhoneVerified: function() {
            this.depositAddPhoneModalVisible = false;

            if (this.depositFnToCallAfterVerification) {
                this[this.depositFnToCallAfterVerification.name](this.depositFnToCallAfterVerification.currency);
                this.depositFnToCallAfterVerification = null;
            }
        },
        closeDepositModal: function() {
            this.showDepositModal = false;
        },
        closeConfirmModal: function() {
            this.addPhoneAlertVisible = false;
        },
        onPhoneAlertConfirm: function(currency) {
            this.hasConfirmedAlert = true;
            this.addPhoneAlertVisible = false;
            this.openDepositModal(currency);
        },
    },
};
