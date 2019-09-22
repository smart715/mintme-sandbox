<template>
    <div>
        <div v-if="deployed" class="deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/webchain_W.svg" alt="deployed">
                </template>
                <template slot="body">
                    This token exists on blockchain.
                </template>
            </guide>
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else-if="notDeployed" class="not-deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/webchain_W.svg" alt="not-deployed">
                </template>
                <template slot="body">
                    This token does not exist on blockchain. Token creator can create it on blockchain in token general settings.
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
