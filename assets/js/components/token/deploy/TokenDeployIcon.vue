<template>
    <div>
        <div v-if="deployed" class="deployed-icon">
            <img class="h-100" src="../../../../img/webchain_W.svg" alt="deployed">
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
    </div>
</template>

<script>
import {tokenDeploymentStatus} from '../../../utils/constants';

export default {
    name: 'TokenDeployIcon',
    props: {
        isOwner: Boolean,
        statusProp: String,
    },
    data() {
        return {
            deployed: tokenDeploymentStatus.deployed === this.statusProp,
            showPending: this.isOwner && tokenDeploymentStatus.pending === this.statusProp,
        };
    },
};
</script>

