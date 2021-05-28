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
                    <p class="bg-info px-2">
                        {{ $t('token.deploy.frozen') }}
                    </p>
                    <p class="bg-info px-2">
                        {{ $t('token.deploy.irreversible') }}
                    </p>
                    <p class="mt-5">
                      {{ $t('token.deploy.current_balance') }} {{ balance | toMoney(precision) | formatMoney }} {{ $t('mintme') }} <br>
                        <span v-if="costExceed" class="text-danger mt-0">Insufficient funds</span>
                    </p>
                    <p>
                        {{ $t('token.deploy.cost') }} {{ webCost | toMoney(precision) | formatMoney }} {{ $t('mintme') }}
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
                <a v-if="isMintmeDeployed" :href="showContractUrl" target="_blank">
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
import {toMoney, formatMoney} from '../../../utils';
import {WebSocketMixin, NotificationMixin, LoggerMixin} from '../../../mixins';
import Decimal from 'decimal.js';
import {tokenDeploymentStatus, webSymbol, GENERAL} from '../../../utils/constants';
import moment from 'moment';

library.add(faCircleNotch);

export default {
    name: 'TokenDeploy',
    components: {
        FontAwesomeIcon,
    },
    mixins: [WebSocketMixin, NotificationMixin, LoggerMixin],
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
        mintmeExplorerUrlProp: String,
        isMintmeToken: Boolean,
    },
    data() {
        return {
            balance: null,
            deploying: false,
            status: this.statusProp,
            webCost: null,
            mintmeExplorerUrl: this.mintmeExplorerUrlProp,
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
            return null !== this.webCost || null !== this.balance;
        },
        costExceed: function() {
            return new Decimal(this.webCost || 0).greaterThan(this.balance || 0);
        },
        deployedDate: function() {
            return moment(this.tokenDeployedDate.date).format(GENERAL.dateFormat);
        },
        isMintmeDeployed: function() {
            return this.deployed && this.isOwner && this.isMintmeToken && this.tokenDeployedDate;
        },
        showContractUrl: function() {
            return this.mintmeExplorerUrl.concat('/tx/' + this.tokenTxHashAddress);
        },
    },
    methods: {
        fetchBalances: function() {
            this.$axios.retry.get(this.$routing.generate('token_deploy_balances', {
                name: this.name,
            }))
            .then(({data}) => {
                this.balance = data.balance;
                this.webCost = data.webCost;
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
            this.$axios.single.post(this.$routing.generate('token_deploy', {
                name: this.name,
            }))
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
    },
    mounted() {
        if (this.currentLocale) {
            moment.locale(this.currentLocale);
        }

        if (this.notDeployed && this.isOwner) {
            this.fetchBalances();
            this.addMessageHandler((response) => {
                if (
                    'asset.update' === response.method &&
                    response.params[0].hasOwnProperty(webSymbol)
                ) {
                    this.balance = response.params[0][webSymbol].available;
                }
            }, 'trade-buy-order-asset', 'TokenDeploy');
        } else {
            this.webCost = 0;
            this.balance = 0;
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
