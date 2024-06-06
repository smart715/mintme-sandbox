<template>
    <div>
        <div v-if="serviceUnavailable" class="text-center pt-4">
            {{ this.$t('toasted.error.service_unavailable_short') }}
        </div>
        <p v-else>
            {{ $t('token.connect.connected_to') }}
            <ul v-if="!isLoading">
                <li v-for="data in deploysData" :key="data.symbol">
                    <coin-avatar
                        :symbol="data.cryptoSymbol"
                        :is-crypto="true"
                        class="d-inline avatar avatar__coin"
                    />
                    <a class="highlight link" :href="data.txHashUrl" target="_blank">
                        {{ data.chainSymbol }}
                        {{ $t('token.deploy.deployed.contract_created', {date: data.date}) }}
                    </a>
                </li>
            </ul>
            <font-awesome-icon v-else icon="circle-notch" spin class="loading-spinner" fixed-width />
        </p>
        <div v-if="enabled && !isLoading && !serviceUnavailable && !isFullConnected">
            <p class="alert-block warning p-3 d-flex align-items-center">
                <font-awesome-icon icon="exclamation-triangle" class="text-primary mr-3"/>
                <span v-html="$t('token.deploy.irreversible')"/>
            </p>
            <m-dropdown
                label="Choose blockchain"
                :text="getBlockchainShortName(selectedCrypto)"
                type="primary"
                :disabled="isConnecting"
                :invalid="costExceeds"
            >
                <template v-slot:button-content>
                    <div class="d-flex align-items-center flex-fill">
                        <coin-avatar
                            :symbol="selectedCrypto"
                            :is-crypto="true"
                            class="d-inline avatar avatar__coin"
                        />
                        <span class="pl-1">
                            {{ getBlockchainShortName(selectedCrypto) }}
                        </span>
                    </div>
                </template>
                <m-dropdown-item
                    v-for="crypto in availableCryptos"
                    :key="crypto"
                    :value="crypto"
                    :active="selectedCrypto === crypto"
                    @click="selectedCrypto = crypto"
                >
                    <div class="d-flex align-items-center">
                        <div class="row pl-2">
                            <coin-avatar
                                :symbol="crypto"
                                :is-crypto="true"
                                class="d-inline col-1 d-flex justify-content-center align-items-center"
                            />
                            <span class="col ml-n3">
                                {{ getBlockchainShortName(crypto) }}
                            </span>
                        </div>
                    </div>
                </m-dropdown-item>
                <template v-slot:errors>
                    <div v-if="costExceeds">
                        {{ $t('token.deploy.insufficient_funds') }}
                    </div>
                </template>
            </m-dropdown>
            <div>
                {{ $t('token.deploy.current_balance') }}
                <span class="text-primary">
                    {{ balance | toMoney(balancePrecision) | formatMoney }}
                    <coin-avatar
                        :symbol="moneySymbol"
                        :is-crypto="true"
                        class="d-inline avatar avatar__coin"
                    />
                    {{ moneySymbol | rebranding }}
                </span>
                <span
                    :class="getDepositDisabledClasses(moneySymbol)"
                    @click="openDepositModal(moneySymbol)"
                >
                    {{ $t('token.deploy.add_more_funds') }}
                </span>
            </div>
            <div>
                {{ $t('token.connect.cost') }}
                <coin-avatar
                    :symbol="selectedCrypto"
                    :is-crypto="true"
                />
                {{ getBlockchainShortName(selectedCrypto) }}:
                <span class="text-primary">
                    {{ cost | toMoney(balancePrecision) | formatMoney }}
                    <coin-avatar
                        :symbol="moneySymbol"
                        :is-crypto="true"
                        class="d-inline avatar avatar__coin"
                    />
                    {{ moneySymbol | rebranding }}
                </span>
            </div>
            <div class="pt-3">
                <m-button
                    class="btn btn-primary"
                    :disabled="btnDisabled"
                    :loading="isConnecting"
                    @click="doConnection"
                >
                    {{ $t('token.connect.connect_to_blockchain') }}
                    <coin-avatar
                        class="mx-1"
                        :symbol="selectedCrypto"
                        :is-crypto="true"
                        :is-grey-color="true"
                        :class="{
                            'dark-coin-avatar': !isMintmeToken,
                            'dark-coin-avatar text-muted': isDeploymentDisabled
                        }"
                    />
                    {{ getBlockchainShortName(selectedCrypto) }}
                </m-button>
                <div class="text-danger small py-1" v-if="!isBlockchainAvailable">
                    {{ $t('blockchain_unavailable', translationContext) }}
                </div>
            </div>
        </div>
        <deposit-modal
            :visible="showDepositModal"
            :currency="selectedCrypto"
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
            @phone-alert-confirm="onPhoneAlertConfirm(selectedCrypto)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
    </div>
</template>
<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faExclamationTriangle} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import {mapActions, mapGetters} from 'vuex';
import {
    GENERAL,
    MINTME,
    webSymbol,
    WEB,
} from '../../../utils/constants';
import {
    MoneyFilterMixin,
    NotificationMixin,
    BlockchainShortNameMixin,
    DepositModalMixin,
} from '../../../mixins';
import moment from 'moment';
import {MDropdown, MDropdownItem, MButton} from '../../UI';
import DepositModal from '../../modal/DepositModal';
import CoinAvatar from '../../CoinAvatar';

library.add(faCircleNotch, faExclamationTriangle);

export default {
    name: 'TokenConnect',
    mixins: [
        NotificationMixin,
        MoneyFilterMixin,
        BlockchainShortNameMixin,
        DepositModalMixin,
    ],
    components: {
        FontAwesomeIcon,
        MDropdown,
        MDropdownItem,
        MButton,
        DepositModal,
        CoinAvatar,
    },
    props: {
        tokenName: String,
        deployCrypto: Object,
        disabledServicesConfig: String,
        currentLocale: String,
        explorerUrls: Object,
        isOwner: Boolean,
        isCreatedOnMintmeSite: Boolean,
        enabled: Boolean,
    },
    data() {
        return {
            selectedCrypto: null,
            checkingTimeout: null,
            costs: null,
            isRequesting: false,
        };
    },
    created() {
        this.selectDefaultCrypto();

        if (this.enabled) {
            this.fetchConnectCosts();
        }
    },
    computed: {
        ...mapGetters('tokenInfo', {
            deploys: 'getDeploys',
            indexedDeploys: 'getIndexedDeploys',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('crypto', {
            cryptosMap: 'getCryptosMap',
        }),
        pendingCrypto() {
            const pendingDeploy = this.deploys.find((deploy) => deploy.pending);

            return pendingDeploy
                ? pendingDeploy.crypto
                : null;
        },
        isConnecting() {
            return null !== this.pendingCrypto || this.isRequesting;
        },
        isLoading() {
            return !this.balances
                || !this.deploys
                || (this.enabled && !this.costs);
        },
        balance() {
            return this.balances[this.moneySymbol].available;
        },
        cost() {
            return this.costs[this.selectedCrypto];
        },
        balancePrecision() {
            return this.cryptosMap[this.selectedCrypto].subunit;
        },
        costExceeds() {
            return new Decimal(this.cost).greaterThan(this.balance);
        },
        btnDisabled() {
            return this.costExceeds || this.isConnecting || !this.isBlockchainAvailable;
        },
        isDeploymentDisabled() {
            return this.services.allServicesDisabled || this.services.deployDisabled;
        },
        isMintmeToken() {
            return WEB.symbol === this.selectedCrypto;
        },
        selectedCryptoRebranded() {
            return this.rebrandingFunc(this.selectedCrypto);
        },
        deploysData() {
            const deploys = this.deploys.filter((deploy) => !deploy.pending);

            return deploys.map((deploy) => {
                const symbol = deploy.crypto.symbol;

                return {
                    cryptoSymbol: symbol,
                    chainSymbol: this.getBlockchainShortName(symbol),
                    date: this.parseDate(deploy.deployDate),
                    txHashUrl: this.getExplorerUrl(symbol, deploy.txHash),
                };
            });
        },
        availableCryptos() {
            const availableDeploys = Object.keys(this.services.blockchainDeployStatus).map((symbol) => {
                return symbol == MINTME.symbol ? webSymbol : symbol;
            });

            return availableDeploys.filter((symbol) => !this.indexedDeploys[symbol]
                && this.blockchainDeployStatus(symbol)
                && this.cryptosMap[symbol]
            );
        },
        isFullConnected() {
            return !this.isConnecting && 0 === this.availableCryptos.length;
        },
        translationContext() {
            return {
                blockchain: this.getBlockchainShortName(this.selectedCrypto),
                blockchainName: this.$t(`dynamic.blockchain_${this.selectedCrypto}_name`),
            };
        },
        services: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
        isBlockchainAvailable: function() {
            return this.cryptosMap[this.selectedCrypto]?.blockchainAvailable ?? false;
        },
        moneySymbol: function() {
            return this.cryptosMap[this.selectedCrypto].moneySymbol;
        },
    },
    mounted() {
        if (this.currentLocale) {
            moment.locale(this.currentLocale);
        }
    },
    methods: {
        ...mapActions('crypto', ['updateCrypto']),
        async fetchConnectCosts() {
            try {
                const response = await this.$axios.retry.get(this.$routing.generate('token_connect_costs'));

                this.costs = response.data;
            } catch (error) {
                this.$logger.error('Token connect costs error', error);
            }
        },
        selectDefaultCrypto() {
            const pendingCrypto = this.pendingCrypto;

            if (pendingCrypto) {
                this.selectedCrypto = pendingCrypto.symbol;
            } else {
                this.selectedCrypto = this.availableCryptos[0] || null;
            }
        },
        parseDate(date) {
            return moment(date).format(GENERAL.dateFormat);
        },
        getExplorerUrl(symbol, txHash) {
            const url = this.explorerUrls[symbol];

            return url.concat('/tx/' + txHash);
        },
        doConnection() {
            if (this.isDeploymentDisabled || this.isConnecting) {
                this.notifyError(this.$t('toasted.error.deployment_disabled'));

                return;
            }

            if (this.btnDisabled) {
                return;
            }

            this.isRequesting = true;

            this.$axios.single.post(
                this.$routing.generate('token_deploy', {
                    name: this.tokenName,
                }),
                {currency: this.selectedCrypto}
            )
                .then(() => {
                    this.$emit('pending', this.selectedCrypto);
                    this.notifySuccess(this.$t('toasted.success.deploy_pending'));
                })
                .catch(({response}) => {
                    if (!response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.$logger.error('Token deploy network error', response);
                    } else if (response.data.message) {
                        this.notifyError(response.data.message);
                        this.$logger.error('Error of deploying token', response);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', response);
                    }
                })
                .finally(() => this.isRequesting = false);
        },
        blockchainDeployStatus(symbol) {
            return this.services.blockchainDeployStatus[this.rebrandingFunc(symbol)];
        },
    },
    watch: {
        deploys: function() {
            this.selectDefaultCrypto();
        },
        selectedCrypto: function() {
            if (!this.selectedCrypto) {
                return;
            }

            try {
                this.updateCrypto(this.selectedCrypto);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_later'));
                this.$logger.error(`Could not fetch crypto info for ${this.selectedCrypto}`, response);
            }
        },
    },
};
</script>
