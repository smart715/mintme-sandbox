<template>
    <div>
        <div v-if="deployed" class="deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/mintmecoin_W.png" alt="deployed">
                </template>
                <template slot="body">
                    This token exists on the blockchain.
                </template>
            </guide>
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else-if="notDeployed" class="not-deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/mintmecoin_W.png" alt="not-deployed">
                </template>
                <template slot="body">
                    This token does not exist on the blockchain. Tokens on a blockchain allow withdrawals to user wallet and are promoted on trading page. Token creator can activate it by clicking on edit icon on this page.
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import Guide from '../../Guide';
import {tokenDeploymentStatus} from '../../../utils/constants';

export default {
    name: 'TokenDeployIcon',
    components: {
        Guide,
    },
    props: {
        isOwner: Boolean,
        statusProp: String,
    },
    watch: {
        showPending: function() {
            console.log('deployed for icon is' + val);
            this.$emit('deployed', true);
        },
    },
    computed: {
        deployed: function() {
            return tokenDeploymentStatus.deployed === this.statusProp;
        },
        notDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.statusProp;
        },
        showPending: function() {
            return this.isOwner && tokenDeploymentStatus.pending === this.statusProp;
        },
    },
};
</script>
