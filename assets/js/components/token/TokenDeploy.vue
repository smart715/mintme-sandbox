<template>
    <div v-if="visible">
        <div v-if="btnEnabled">
            <button class="btn btn-info" @click="setModalVisible(true)">Deploy to blockchain</button>
            <modal
                size="lg"
                :visible="modalVisible"
                @close="setModalVisible(false)">
                <template slot="body">
                    <div class="text-left">
                        <p>
                            This is final step for token creation. After you pay for deploying token to blockchain
                            you and others will be able to withdraw tokens from mintme to your Webchain wallet.
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
                                :disabled="btnModalDisabled"
                                @click="deploy"
                                class="btn btn-info">
                                Deploy to blockchain
                            </button>
                            <span class="btn-cancel pl-3 c-pointer" @click="setModalVisible(false)">Cancel</span>
                        </div>
                    </div>
                </template>
            </modal>
        </div>
        <div v-else-if="deployed" class="deployed-icon">
            <img class="h-100" src="../../../img/webchain_W.svg" alt="deployed">
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
    </div>
</template>

<script>
import Modal from '../modal/Modal';
import {toMoney, formatMoney} from '../../utils';
import {WebSocketMixin} from '../../mixins';
import Decimal from 'decimal.js';

const WEB = 'web';
const STATUS = {notDeployed: 'not-deployed', pending: 'pending', deployed: 'deployed'};

export default {
    name: 'TokenDeploy',
    mixins: [WebSocketMixin],
    props: {
        name: String,
        hasReleasePeriod: Boolean,
        isOwner: Boolean,
        precision: Number,
        statusProp: String,
    },
    data() {
        return {
            status: this.statusProp,
            deploying: false,
            modalVisible: false,
            balance: null,
            webCost: null,
        };
    },
    computed: {
        notDeployed: function() {
            return STATUS.notDeployed === this.status;
        },
        pending: function() {
            return STATUS.pending === this.status;
        },
        deployed: function() {
            return STATUS.deployed === this.status;
        },
        showPending: function(){
            return this.isOwner && this.pending;
        },
        btnEnabled: function() {
            return this.isOwner && this.notDeployed;
        },
        btnModalDisabled: function() {
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
            this.deploying = true;
            this.$axios.single.post(this.$routing.generate('token_deploy', {
                name: this.name,
            }))
            .then(() => {
                this.status = STATUS.pending;
                this.setModalVisible(false);
                this.$toasted.success('Process in pending status and it will take some minutes to be done.');
            })
            .catch(({response}) => {
                if (!response) {
                    this.$toasted.error('Network error');
                } else {
                    this.$toasted.error('An error has occurred, please try again later');
                }
            })
            .then(() => this.deploying = false);
        },
        setModalVisible: function(visible) {
            if (visible && !this.hasReleasePeriod) {
                this.$toasted.info('Please edit token release period before deploying.');
                return;
            }

            this.modalVisible = visible;
        },
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
    },
    mounted() {
        if (this.notDeployed && this.isOwner) {
            this.fetchBalances();
            this.addMessageHandler((response) => {
                if (
                    'asset.update' === response.method &&
                    response.params[0].hasOwnProperty(WEB)
                ) {
                    this.balance = response.params[0][WEB].available;
                }
            }, 'trade-buy-order-asset');
        } else {
            this.webCost = 0;
            this.balance = 0;
        }
    },
    components: {
        Modal,
    },
};
</script>

