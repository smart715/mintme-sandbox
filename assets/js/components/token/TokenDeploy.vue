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
                        <p class="bg-danger">This process is irreversible, once confirm payment there is no going back.</p>
                        <p class="mt-5">
                            Your current balance: {{ baseBalance | toMoney(precision) }} WEB coins <br>
                            <span v-if="costExceed" class="text-danger mt-0">Insufficient funds</span>
                        </p>
                        <p>Cost of deploying token to blockchain: {{ webCost | toMoney(precision) }}</p>
                        <div class="pt-3">
                            <button :disabled="costExceed" class="btn btn-info">Deploy to blockchain</button>
                            <span class="btn-cancel pl-3 c-pointer" @click="setModalVisible(false)">Cancel</span>
                        </div>
                    </div>
                </template>
            </modal>
        </div>
        <div v-else-if="deployed" class="text-white">deployed</div>
    </div>
</template>

<script>
import Modal from '../modal/Modal';
import {mapGetters} from 'vuex';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';

const API_URL = 'https://api.coingecko.com/api/v3/simple/price';

export default {
    name: 'TokenDeploy',
    props: {
        name: String,
        isOwner: Boolean,
        precision: Number,
        usdCost: Number,
    },
    data() {
        return {
            deployed: null,
            modalVisible: false,
            webCost: null,
        };
    },
    computed: {
        btnEnabled: function() {
            return this.isOwner && !this.deployed;
        },
        visible: function() {
            return this.deployed !== null && this.webCost !== null;
        },
        costExceed: function() {
            return this.webCost > this.baseBalance;
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
        convertCostToWeb: function() {
            this.$axios.single.get(API_URL, {
                params: {
                    ids: 'webchain',
                    vs_currencies: 'usd',
                },
            })
            .then(({data}) => this.webCost = new Decimal(this.usdCost).div(data.webchain.usd));
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
        this.convertCostToWeb();
    },
    components: {
        Modal,
    },
};
</script>

