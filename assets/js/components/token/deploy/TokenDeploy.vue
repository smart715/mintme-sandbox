<template>
    <div>
        <template v-if="hasReleasePeriod">
            <template v-if="notDeployed">
                <div
                    v-if="visible"
                    class="text-left"
                >
                    <p>
                        {{ $t('token.deploy.final_step') }}
                    </p>
                    <p v-if="isSelectedMintme" class="bg-info px-2">
                        {{ $t('token.deploy.frozen') }}
                    </p>
                    <p class="bg-info px-2">
                        {{ $t('token.deploy.irreversible') }}
                    </p>
                    <p>
                        {{ $t('token.deploy.select_blockchain') }}
                    </p>
                    <b-dropdown
                        :text="selectedNode"
                        variant="primary"
                    >
                        <b-dropdown-item
                            v-for="currency in currencies"
                            :key="currency"
                            :value="currency"
                            @click="onSelect(currency)"
                        >
                            {{ currency | rebranding | bnbToBsc }}
                        </b-dropdown-item>
                    </b-dropdown>
                    <p class="mt-5">
                        {{ $t('token.deploy.current_balance') }}
                        {{ balance | toMoney(precision) | formatMoney }}
                        {{ selectedCurrencyRebranded }}
                        <br>
                        <span v-if="costExceed" class="text-danger mt-0">Insufficient funds</span>
                    </p>
                    <p>
                        {{ $t('token.deploy.cost') }}
                        {{ cost | toMoney(precision) | formatMoney }}
                        {{ selectedCurrencyRebranded }}
                    </p>
                    <div class="pt-3">
                        <button
                            class="btn btn-primary"
                            :disabled="btnDisabled"
                            @click="deploy"
                        >
                            <span :class="{'text-muted': isDeploymentDisabled}">
                                {{ $t('token.deploy.deploy_to_blockchain') }}
                            </span>
                        </button>
                    </div>
                </div>
                <div
                    v-else
                    class="text-center"
                >
                    <font-awesome-icon
                        icon="circle-notch"
                        spin
                        class="loading-spinner"
                        fixed-width
                    />
                </div>
            </template>
            <div
                v-else-if="showPending"
                class="text-left"
            >
                <p class="bg-info m-0 py-1 px-3">
                    <font-awesome-icon
                        icon="circle-notch"
                        spin
                        class="loading-spinner"
                        fixed-width
                    />
                    {{ $t('token.deploy.pending') }}
                </p>
            </div>
            <div
                v-else-if="deployed"
                class="text-left"
            >
                <p class="bg-info m-0 py-1 px-3">
                    {{ $t('token.deploy.deployed') }}
                </p>
                <br>
                <a v-if="isDeployed" :href="showContractUrl" target="_blank">
                    {{ $t('token.deploy.deployed.contract_created', {tokenDeployedDate: deployedDate}) }}
                </a>
            </div>
        </template>
        <div
            v-else
            class="text-left"
        >
            <p class="bg-info m-0 py-1 px-3">
                {{ $t('token.deploy.edit_release_period') }}
            </p>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {BDropdown, BDropdownItem} from 'bootstrap-vue';
import {toMoney, formatMoney} from '../../../utils';
import {
    WebSocketMixin,
    NotificationMixin,
    LoggerMixin,
    RebrandingFilterMixin,
    BnbToBscFilterMixin,
} from '../../../mixins';
import Decimal from 'decimal.js';
import {
    tokenDeploymentStatus,
    webSymbol,
    ethSymbol,
    bnbSymbol,
    GENERAL,
} from '../../../utils/constants';
import moment from 'moment';

library.add(faCircleNotch);

export default {
    name: 'TokenDeploy',
    components: {
        FontAwesomeIcon,
        BDropdown,
        BDropdownItem,
    },
    mixins: [
        WebSocketMixin,
        NotificationMixin,
        LoggerMixin,
        RebrandingFilterMixin,
        BnbToBscFilterMixin,
    ],
    props: {
        hasReleasePeriod: Boolean,
        isOwner: Boolean,
        name: String,
        precision: Number,
        statusProp: String,
        disabledServicesConfig: String,
        currentLocale: String,
        tokenDeployedDate: {
            type: Object,
            default: null,
        },
        tokenTxHashAddress: {
            type: String,
            default: null,
        },
        mintmeExplorerUrl: String,
        ethExplorerUrl: String,
        bnbExplorerUrl: String,
        tokenCrypto: Object,
        isControlledToken: Boolean,
    },
    data() {
        return {
            balances: null,
            deploying: false,
            status: this.statusProp,
            costs: null,
            selectedCurrency: webSymbol,
            currencies: [
                webSymbol,
                ethSymbol,
                bnbSymbol,
            ],
        };
    },
    computed: {
        isDeploymentDisabled: function() {
            let services = JSON.parse(this.disabledServicesConfig);

            return services.allServicesDisabled || services.deployDisabled;
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
            return this.costExceed || this.deploying;
        },
        visible: function() {
            return null !== this.costs || null !== this.balances;
        },
        cost: function() {
            return this.costs ? this.costs[this.selectedCurrency] || 0 : 0;
        },
        costExceed: function() {
            return new Decimal(this.cost).greaterThan(this.balance);
        },
        deployedDate: function() {
            return moment(this.tokenDeployedDate.date).format(GENERAL.dateFormat);
        },
        isDeployed: function() {
            return this.deployed && this.isOwner && this.isControlledToken && this.tokenDeployedDate;
        },
        showContractUrl: function() {
            return this.contractUrls[this.tokenCrypto.symbol];
        },
        contractUrls: function() {
            return {
                WEB: this.getTxUrl(this.mintmeExplorerUrl, this.tokenTxHashAddress),
                ETH: this.getTxUrl(this.ethExplorerUrl, this.tokenTxHashAddress),
                BNB: this.getTxUrl(this.bnbExplorerUrl, this.tokenTxHashAddress),
            };
        },
        isSelectedMintme: function() {
            return webSymbol === this.selectedCurrency;
        },
        selectedCurrencyRebranded: function() {
            return this.rebrandingFunc(this.selectedCurrency);
        },
        selectedNode: function() {
            return this.bnbToBscFunc(this.selectedCurrencyRebranded);
        },
        balance: function() {
            return this.balances ? this.balances[this.selectedCurrency] || 0 : 0;
        },
    },
    methods: {
        fetchBalances: function() {
            this.$axios.retry.get(this.$routing.generate('token_deploy_balances', {
                name: this.name,
            }))
            .then(({data}) => {
                this.costs = data.costs;
                this.balances = [];
                this.currencies.forEach((symbol) => this.balances[symbol] = data.balances[symbol].available);
            }).catch((err) => {
                this.sendLogs('error', 'Can not get token deploy balances', err);
            });
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
                this.$emit('pending');
                this.notifySuccess(this.$t('toasted.success.deploy_pending'));
            })
            .catch(({response}) => {
                if (!response) {
                    this.notifyError(this.$t('toasted.error.network'));
                    this.sendLogs('error', 'Token deploy network error', response);
                } else if (response.data.message) {
                    this.notifyError(response.data.message);
                    this.sendLogs('error', 'Error of deploying token', response);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.sendLogs('error', 'An error has occurred, please try again later', response);
                }
            })
            .then(() => this.deploying = false);
        },
        onSelect: function(newCurrency) {
            this.selectedCurrency = newCurrency;
        },
        getTxUrl: function(url, hash) {
            return url.concat('/tx/' + hash);
        },
    },
    mounted() {
        if (this.currentLocale) {
            moment.locale(this.currentLocale);
        }

        if (this.notDeployed && this.isOwner) {
            this.fetchBalances();
            this.addMessageHandler((response) => {
                if ('asset.update' === response.method) {
                    this.currencies.forEach((symbol) => {
                        if (response.params[0].hasOwnProperty(symbol)) {
                            this.balances[symbol] = response.params[0][symbol].available;
                        }
                    });
                }
            }, 'trade-buy-order-asset', 'TokenDeploy');
        } else {
            this.costs = [];
            this.balances = [];
        }
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val || 0, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
    },
};
</script>
