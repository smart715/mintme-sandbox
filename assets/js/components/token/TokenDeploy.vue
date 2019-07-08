<template>
    <div v-if="checked">
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
                        <p class="bg-danger">This process is irreversible, once confirm payment there is no going back.</p>
                        <p class="mt-5">
                            Your current balance: {{ baseBalance | toMoney(4) }} WEB coins <br>
                            <span v-if="costExceed" class="text-danger mt-0">Insufficient funds</span>
                        </p>
                        <p>Cost of deploying token to blockchain: {{ cost | toMoney(4) }}</p>
                        <div class="pt-3">
                            <button :disabled="costExceed" class="btn btn-info">Deploy to blockchain</button>
                            <span class="btn-cancel pl-3 c-pointer" @click="setModalVisible(false)">Cancel</span>
                        </div>
                    </div>
                </template>
            </modal>
        </div>
        <div v-if="deployed" class="text-white">deployed</div>
    </div>
    <div v-else>
        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
    </div>
</template>

<script>
import Modal from '../modal/Modal';
import {mapGetters} from 'vuex';
import {toMoney} from '../../utils';

export default {
    name: 'TokenDeploy',
    props: {
        name: String,
        isOwner: Boolean,
        cost: Number,
    },
    data() {
        return {
            deployed: null,
            modalVisible: false,
        };
    },
    computed: {
        btnEnabled: function() {
            return this.isOwner && !this.deployed;
        },
        checked: function() {
            return this.deployed !== null;
        },
        costExceed: function() {
            return this.cost > this.baseBalance;
        },
        ...mapGetters('makeOrder', {
            baseBalance: 'getBaseBalance',
        }),
    },
    methods: {
        checkIfDeployed: function() {
            this.$axios.retry.get(this.$routing.generate('is_token_deployed', {
                name: this.name,
            }))
            .then(({data}) => this.deployed = data);
        },
        setModalVisible: function(visible) {
            this.modalVisible = visible;
        },
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
    mounted() {
        this.checkIfDeployed();
    },
    components: {
        Modal,
    },
};
</script>

