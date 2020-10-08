<template>
    <div>
        <div v-if="deployed" class="deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/mintmecoin_W.png" :alt="this.$t('token.deploy_icon.img_alt.deployed')">
                </template>
                <template slot="body">
                    {{ $t('token.deploy_icon.body') }}
                </template>
            </guide>
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else-if="notDeployed" class="not-deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" src="../../../../img/mintmecoin_W.png" :alt="this.$t('token.deploy_icon.img_alt.not_deployed')">
                </template>
                <template slot="body">
                    {{ $t('token.deploy_icon.doesnt_exist_on_blockchain') }}
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
