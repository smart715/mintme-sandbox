<template>
    <div>
        <template v-if="hasReleasePeriod">
            <template v-if="notDeployed">
                <div
                    v-if="visible"
                    class="text-left"
                >
                    <p>
                        This is final step for token creation. After you pay for deploying token to blockchain
                        you and others will be able to withdraw tokens from mintme to your Webchain wallet.
                    </p>
                    <p class="bg-danger">
                        WEB spent on mintMe, will be inaccessible by us (frozen) for 5 years. So you lower WEB circulating supply with each purchase and increase probability of WEB price going up.
                    </p>
                    <p class="bg-danger">
                        This process is irreversible, once confirm payment there is no going back.
                    </p>
                    <p class="mt-5">
                        Your current balance: {{ balance | toMoney(precision) | formatMoney }} WEB coins <br>
                        <span v-if="costExceed" class="text-danger mt-0">Insufficient funds</span>
                    </p>
                    <p>Cost of deploying token to blockchain: {{ webCost | toMoney(precision) | formatMoney }}</p>
                    <div class="pt-3">
                        <button
                            class="btn btn-primary"
                            :disabled="btnDisabled"
                            @click="deploy"
                        >
                            Deploy to blockchain
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
                <two-factor-modal
                    :visible="showTwoFactorModal"
                    :twofa="twofa"
                    @verify="doDeploy"
                    @close="closeTwoFactorModal"
                />
            </template>
            <div
                v-else-if="showPending"
                class="text-left"
            >
                <p class="bg-info m-0 py-1 px-3">
                    Deploy is pending.
                </p>
            </div>
            <div
                v-else-if="deployed"
                class="text-left"
            >
                <p class="bg-info m-0 py-1 px-3">
                    Token is already deployed.
                </p>
            </div>
        </template>
        <div
            v-else
            class="text-left"
        >
            <p class="bg-info m-0 py-1 px-3">
                Please edit token release period before deploying.
            </p>
        </div>
    </div>
</template>

<script>
import TwoFactorModal from '../../modal/TwoFactorModal';
import {toMoney, formatMoney} from '../../../utils';
import {WebSocketMixin} from '../../../mixins';
import Decimal from 'decimal.js';
import {tokenDeploymentStatus, webSymbol} from '../../../utils/constants';

export default {
    name: 'TokenDeploy',
    components: {
        TwoFactorModal,
    },
    mixins: [WebSocketMixin],
    props: {
        twofa: Boolean,
        hasReleasePeriod: Boolean,
        isOwner: Boolean,
        name: String,
        precision: Number,
        statusProp: String,
    },
    data() {
        return {
            showTwoFactorModal: false,
            balance: null,
            deploying: false,
            status: this.statusProp,
            webCost: null,
        };
    },
    computed: {
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
            return new Decimal(this.webCost).greaterThan(this.balance);
        },
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        fetchBalances: function() {
            this.$axios.retry.get(this.$routing.generate('token_deploy_balances', {
                name: this.name,
            }))
            .then(({data}) => {
                this.balance = data.balance;
                this.webCost = data.webCost;
            });
        },
        deploy: function() {
            if (this.twofa) {
                this.showTwoFactorModal = true;
            } else {
                this.doDeploy();
            }
        },
        doDeploy: function(code = '') {
            if (this.deploying) {
                return;
            }

            this.deploying = true;
            this.$axios.single.post(this.$routing.generate('token_deploy', {
                name: this.name,
                code,
            }))
            .then(() => {
                this.status = tokenDeploymentStatus.pending;
                this.$emit('pending');
                this.$toasted.success('Process in pending status and it will take some minutes to be done.');
            })
            .catch(({response}) => {
                if (!response) {
                    this.$toasted.error('Network error');
                } else if (response.data.message) {
                    this.$toasted.error(response.data.message);
                } else {
                    this.$toasted.error('An error has occurred, please try again later');
                }
            })
            .then(() => this.deploying = false);
        },
    },
    mounted() {
        if (this.notDeployed && this.isOwner) {
            this.fetchBalances();
            this.addMessageHandler((response) => {
                if (
                    'asset.update' === response.method &&
                    response.params[0].hasOwnProperty(webSymbol)
                ) {
                    this.balance = response.params[0][webSymbol].available;
                }
            }, 'trade-buy-order-asset');
        } else {
            this.webCost = 0;
            this.balance = 0;
        }
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
    },
};
</script>

