<template>
    <div>
        <template v-if="hasReleasePeriod">
            <template v-if="notDeployed">
                <div
                    v-if="visible"
                    class="text-left"
                >
                    <div class="alert-block warning p-3 mb-3 d-flex align-items-center">
                        <font-awesome-icon icon="exclamation-triangle" class="text-primary mr-3"/>
                        <div>
                            <p
                                v-if="isSelectedMintme"
                                class="m-0"
                                v-html="$t('token.deploy.frozen', translationsContext)"
                            >
                            </p>
                            <p
                                v-html="$t('token.deploy.irreversible')"
                                class="m-0"
                            ></p>
                        </div>
                    </div>
                    <template v-if="noBlockchainAvailable">
                        <p>
                            {{ $t('token.deploy.disabled') }}
                        </p>
                    </template>
                    <template v-else>
                        <template v-if="singleBlockchainAvailable">
                            <p>
                                {{ $t('token.deploy.to_blockchain') }}
                                <coin-avatar
                                    :symbol="availableCurrencies[0]"
                                    :is-crypto="true"
                                />
                                {{ blockchainContext.blockchainName }}
                            </p>
                        </template>
                        <template v-else>
                            <m-dropdown
                                :label="$t('token.deploy.select_blockchain')"
                                :text="selectedNode"
                                type="primary"
                            >
                                <template v-slot:button-content>
                                    <div class="d-flex align-items-center flex-fill">
                                        <coin-avatar
                                            :is-crypto="true"
                                            :symbol="selectedCurrency"
                                            class="mr-1"
                                        />
                                        <span class="text-truncate">
                                            {{ selectedNode }}
                                        </span>
                                    </div>
                                </template>
                                <m-dropdown-item
                                    v-for="currency in availableCurrencies"
                                    :key="currency"
                                    :value="currency"
                                    :active="selectedCurrency === currency"
                                    :invalid="costExceeds"
                                    @click="onSelect(currency)"
                                >
                                    <div class="row pl-2">
                                        <coin-avatar
                                            :is-crypto="true"
                                            :symbol="currency"
                                            class="col-1 d-flex justify-content-center align-items-center"
                                        />
                                        <span class="col ml-n3">
                                            {{ getBlockchainShortName(currency) }}
                                        </span>
                                    </div>
                                </m-dropdown-item>
                                <template v-slot:errors>
                                    <span v-if="costExceeds">
                                        {{ $t('token.deploy.insufficient_funds') }}
                                    </span>
                                </template>
                            </m-dropdown>
                        </template>
                        <div>
                            {{ $t('token.deploy.current_balance') }}
                            <span class="text-primary">
                                {{ balance | formatMoney }}
                                <coin-avatar
                                    :is-crypto="true"
                                    :symbol="moneySymbol"
                                />
                                {{ selectedCurrencyRebranded }}
                            </span>
                        </div>
                        <div>
                            <span
                                :class="getDepositDisabledClasses(moneySymbol)"
                                @click="openDepositModal(moneySymbol)"
                            >
                                {{ $t('token.deploy.add_more_funds') }}
                            </span>
                        </div>
                        <div>
                            {{ $t('token.deploy.cost') }}
                            <span class="text-primary">
                                {{ cost | formatMoney }}
                                <coin-avatar
                                    :is-crypto="true"
                                    :symbol="moneySymbol"
                                />
                                {{ selectedCurrencyRebranded }}
                            </span>
                        </div>
                        <div class="pt-3">
                            <m-button
                                type="primary"
                                :disabled="btnDisabled"
                                @click="deploy"
                            >
                                <span :class="{'text-muted': isDeploymentDisabled}">
                                    {{ $t('token.deploy.deploy_to_blockchain') }}
                                </span>
                            </m-button>
                            <div class="text-danger small py-1" v-if="!isBlockchainAvailable">
                                {{ $t('blockchain_unavailable', translationsContext) }}
                            </div>
                        </div>
                    </template>
                </div>
                <div v-else class="text-center pt-4" >
                    <span v-if="isServiceUnavailable">
                        {{ this.$t('toasted.error.service_unavailable_short') }}
                    </span>
                    <div v-else class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </template>
            <div
                v-else-if="showPending"
                class="text-left"
            >
                <div class="p-3 d-flex flex-column align-items-center">
                    <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                    {{ $t('token.deploy.pending') }}
                </div>
            </div>
        </template>
        <div
            v-else
            class="m-0 py-5 px-2 text-muted text-center"
        >
            {{ $t('token.deploy.edit_release_period_1') }}
            <span
                class="highlight link c-pointer"
                @click="$emit('click-release-period')"
            >
                {{ $t('token.deploy.edit_release_period_2') }}
            </span>
            {{ $t('token.deploy.edit_release_period_3') }}
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
            :no-close="false"
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
import {faCircleNotch, faExclamationTriangle} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {toMoney, formatMoney, generateCoinAvatarHtml} from '../../../utils';
import {
    WebSocketMixin,
    NotificationMixin,
    BlockchainShortNameMixin,
    DepositModalMixin,
} from '../../../mixins';
import Decimal from 'decimal.js';
import {
    tokenDeploymentStatus,
    HTTP_INTERNAL_SERVER_ERROR,
    MINTME,
    webSymbol,
} from '../../../utils/constants';
import {mapActions, mapGetters, mapState} from 'vuex';
import {MButton, MDropdown, MDropdownItem} from '../../UI';
import CoinAvatar from '../../CoinAvatar';
import DepositModal from '../../modal/DepositModal';

library.add(faCircleNotch, faExclamationTriangle);

export default {
    name: 'TokenDeploy',
    components: {
        FontAwesomeIcon,
        MButton,
        MDropdown,
        MDropdownItem,
        DepositModal,
        CoinAvatar,
    },
    mixins: [
        WebSocketMixin,
        NotificationMixin,
        BlockchainShortNameMixin,
        DepositModalMixin,
    ],
    props: {
        isOwner: Boolean,
        name: String,
        statusProp: String,
        disabledServicesConfig: String,
        tokenCrypto: Object,
        isCreatedOnMintmeSite: Boolean,
    },
    data() {
        return {
            deploying: false,
            status: this.statusProp,
            costs: null,
            selectedCurrency: null,
            serviceUnavailable: false,
        };
    },
    computed: {
        ...mapState('tokenSettings', {
            hasReleasePeriod: (state) => state.hasReleasePeriod,
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            tradeServiceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('crypto', {
            cryptosMap: 'getCryptosMap',
        }),
        isServiceUnavailable: function() {
            return this.tradeServiceUnavailable || this.serviceUnavailable;
        },
        availableCurrencies: function() {
            const availableDeploys = Object.keys(this.services.blockchainDeployStatus).map((symbol) => {
                return symbol == MINTME.symbol ? webSymbol : symbol;
            }).filter((symbol) => this.cryptosMap[symbol]);

            return availableDeploys.filter((symbol) => this.blockchainDeployStatus(symbol));
        },
        noBlockchainAvailable: function() {
            return 0 === this.availableCurrencies.length;
        },
        singleBlockchainAvailable: function() {
            return 1 === this.availableCurrencies.length;
        },
        blockchainContext: function() {
            if (this.singleBlockchainAvailable) {
                return {
                    blockchainName: this.getBlockchainShortName(this.availableCurrencies[0]),
                };
            }

            return null;
        },
        isDeploymentDisabled: function() {
            return this.services.allServicesDisabled || this.services.deployDisabled || this.noBlockchainAvailable;
        },
        notDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.status;
        },
        pending: function() {
            return tokenDeploymentStatus.pending === this.status;
        },
        deployed: function() {
            return tokenDeploymentStatus.deployed === this.status;
        },
        showPending: function() {
            return this.isOwner && this.pending;
        },
        btnDisabled: function() {
            return this.costExceeds || this.deploying || !this.isBlockchainAvailable;
        },
        visible: function() {
            return null !== this.costs && null !== this.balances;
        },
        cost: function() {
            return toMoney(
                this.costs
                    ? this.costs[this.selectedCurrency] || 0
                    : 0,
                this.cryptosMap[this.selectedCurrency].subunit
            );
        },
        costExceeds: function() {
            return new Decimal(this.cost).greaterThan(this.balance);
        },
        isDeployed: function() {
            return this.deployed && this.isOwner && this.isCreatedOnMintmeSite;
        },
        isSelectedMintme: function() {
            return webSymbol === this.selectedCurrency;
        },
        selectedCurrencyRebranded: function() {
            return this.rebrandingFunc(this.moneySymbol);
        },
        selectedNode: function() {
            return this.getBlockchainShortName(this.selectedCurrency);
        },
        balance: function() {
            const balance = this.balances
                ? this.balances[this.moneySymbol].available
                : 0;

            const bonus = this.balances
                ? this.balances[this.moneySymbol].bonus
                : 0;

            return toMoney(
                new Decimal(balance).add(bonus).toString(),
                this.cryptosMap[this.moneySymbol].subunit
            );
        },
        services: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
        translationsContext: function() {
            return {
                mintmeBlock: generateCoinAvatarHtml({symbol: MINTME.symbol, isCrypto: true}),
                blockchainName: this.$t(`dynamic.blockchain_${this.selectedCurrency}_name`),
            };
        },
        isBlockchainAvailable: function() {
            return this.cryptosMap[this.selectedCurrency]?.blockchainAvailable ?? false;
        },
        moneySymbol: function() {
            return this.cryptosMap[this.selectedCurrency].moneySymbol;
        },
    },
    methods: {
        ...mapActions('crypto', ['updateCrypto']),
        fetchCosts: async function() {
            try {
                const request = await this.$axios.single.get(this.$routing.generate('token_deploy_costs'));

                this.costs = request.data;
            } catch (err) {
                if (HTTP_INTERNAL_SERVER_ERROR === err.response.status && err.response.data.error) {
                    this.notifyError(err.response.data.error);
                } else {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                }

                this.serviceUnavailable = true;
                this.$logger.error('Can not get token deploy costs', err);
            }
        },
        deploy: function() {
            if (this.isDeploymentDisabled) {
                this.notifyError(this.$t('toasted.error.deployment_disabled'));

                return;
            }

            if (this.deploying) {
                return;
            }

            this.deploying = true;
            this.$axios.single.post(
                this.$routing.generate('token_deploy', {name: this.name}),
                {currency: this.selectedCurrency}
            )
                .then(() => {
                    this.status = tokenDeploymentStatus.pending;
                    this.$emit('pending', this.selectedCurrency);
                    this.notifySuccess(this.$t('toasted.success.deploy_pending'));
                    this.removeDeployTokenNotification();
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
                        this.$logger.error('Error of deploying token', response);
                    }
                })
                .then(() => this.deploying = false);
        },
        onSelect: function(newCurrency) {
            this.selectedCurrency = newCurrency;
        },
        blockchainDeployStatus: function(symbol) {
            return this.services.blockchainDeployStatus[this.rebrandingFunc(symbol)];
        },
        initializeSelectedCurrency: function() {
            this.selectedCurrency = this.availableCurrencies[0]
                ? this.availableCurrencies[0]
                : null;
        },
        removeDeployTokenNotification: function() {
            const deployTokenNotification = document.getElementById('deploy-token-notification');

            if (deployTokenNotification) {
                deployTokenNotification.remove();
            }
        },
    },
    mounted() {
        if (this.notDeployed && this.isOwner) {
            this.fetchCosts();
        } else {
            this.costs = [];
        }

        this.initializeSelectedCurrency();
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val || 0, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
    },
    watch: {
        statusProp: function() {
            this.status = this.statusProp;
        },
        selectedCurrency: function() {
            if (!this.selectedCurrency) {
                return;
            }

            try {
                this.updateCrypto(this.selectedCurrency);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_later'));
                this.$logger.error(`Could not fetch crypto info for ${this.selectedCurrency}`, response);
            }
        },
    },
};
</script>
