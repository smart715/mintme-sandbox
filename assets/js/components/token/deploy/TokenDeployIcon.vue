<template>
    <div>
        <div v-if="deployed" class="deployed-icon">
            <guide>
                <template slot="icon">
                    <img class="h-100" :src="deployedImg" :alt="this.$t('token.deploy_icon.img_alt.deployed')">
                </template>
                <template slot="body">
                    {{ deployedBodyText }}
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';
import {tokenDeploymentStatus, BNB} from '../../../utils/constants';

library.add(faCircleNotch);

export default {
    name: 'TokenDeployIcon',
    components: {
        Guide,
        FontAwesomeIcon,
    },
    props: {
        isMintme: Boolean,
        tokenCrypto: Object,
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
        deployedImg: function() {
            return this.isMintme
                ? require('../../../../img/mintmecoin_W.png')
                : require('../../../../img/' + this.tokenCrypto.symbol + '.png');
        },
        deployedBodyText: function() {
            return this.isMintme
                ? this.$t('token.deploy_icon.body')
                : this.$t('token.deploy_icon.body_not_mintme', {
                    baseName: this.cryptoChain,
                });
        },
        cryptoChain: function() {
            return BNB.symbol === this.tokenCrypto.symbol
                ? 'Binance Smart Chain'
                : this.tokenCrypto.name;
        },
    },
};
</script>
